<?php
declare(strict_types=1);

namespace Firehed\LSPHP;

use Firehed\LSPHP\Message\Message;
use Firehed\LSPHP\Message\MessageFactory;
use Firehed\LSPHP\Message\Request;
use Firehed\LSPHP\Message\RequestType;
use Firehed\LSPHP\Message\Response;
use OutOfBoundsException;
use Psr\Log\LoggerInterface;

class Server implements ResponseHandler
{
    /** @var resource */
    private $stdin;

    /** @var resource */
    private $stdout;

    /** @var resource */
    private $stderr;

    /** @var LoggerInterface */
    private $log;

    /** @var bool */
    private $initialized = false;

    /** @var MessageFactory */
    private $messageFactory;

    /** @var string */
    private $root;

    /** @var Plugin\PluginInterface[] */
    private $plugins = [];

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
        $this->messageFactory = new MessageFactory($log);
    }

    public function addPlugin(Plugins\PluginInterface $plugin)
    {
        $this->log->info('Registered plugin "{name}"', [
            'name' => $plugin->getName(),
        ]);
        // TODO: turn this into some sort of composed logger that prefixes the
        // plugin name to the message
        $plugin->setLogger($this->log);
        $this->plugins[] = $plugin;
    }

    public function start()
    {
        $this->log->info(sprintf('Starting server on PID %d', getmypid()));
        $this->stdin = $this->open('php://stdin', 'r');
        $this->stdout = $this->open('php://stdout', 'w');
        $this->stderr = $this->open('php://stderr', 'w');
        $this->log->info('Starting to listen on stdin');
        while (true) {
//            $this->handleMessagesFromStdin();
            $this->readLoop();
        }
    }

    private function readLoop(): void
    {
        $read = [$this->stdin];
        $write = $except = [];
        $changes = stream_select($read, $write, $except, 0, 1000);
        if ($changes === false) {
            $this->log->error('`stream_select` error');
        } else {
            foreach ($read as $resource) {
                if ($message = $this->readMessage($resource)) {
                    $this->handleMessage($message);
                }
            }
        }
    }

    private function handleMessagesFromStdin(): void
    {
        $message = $this->readMessage($this->stdin);
        if (!$message) {
            return;
        }
        $this->handleMessage($message);
    }

    private function handleMessage(Message $message): void
    {
        /*
        if !this->initialized && not initialize message
            write response; error Message\ErrorCode::SERVER_NOT_INITIALZED`-32002`
            drop notification if not exit`
        */
        if ($message->getMethod() === RequestType::INITIALIZE) {
            $this->initialize($message);
            return;
        }
        $this->dispatchMessageToPlugins($message);
    }

    private function dispatchMessageToPlugins(Message $message): void
    {
        // At some point, this should work asynchronously. Suffice to say,
        // doing so is non-trivial.
        $this->log->debug('Dispatching {message}', ['message' => print_r($message, true)]);
        // This will also need to replace the handler with some sort of
        // aggergator - right now each plugin will battle with each other for
        // a given file and potentially overwite each other's notifications.
        if ($message->isNotification()) {
            foreach ($this->plugins as $plugin) {
                $plugin->handleNotification($message, $this);
            }
        } elseif ($message->isRequest()) {
            foreach ($this->plugins as $plugin) {
                $plugin->handleRequest($message, $this);
            }
        }
    }

    private function initialize(Request $initializeRequest)
    {
        $params = $initializeRequest->getParams();
        if (array_key_exists('rootUri', $params)) {
            $parsed = parse_url($params['rootUri']);
            if ($parsed['scheme'] !== 'file') {
                throw new OutOfBoundsException('Unexpected scheme');
            }
            $root = $parsed['path'];
        } elseif (array_key_exists('rootPath', $params)) {
            $root = $params['rootPath'];
        } else {
            throw new OutOfBoundsException('rootUri and rootPath are missing from InitializeParams');
        }
        $this->log->debug('Setting workspace root path to ' . $root);
        $this->root = $root;

        $this->initialized = true;
        $response = new Response($initializeRequest, [
            'capabilities' => [
            ],
        ]);

        $this->writeResponse($response);
    }

    private function readMessage($pipe): ?Message
    {
        $buf = '';
        $headers = [];
        while (true) {
            $byte = fread($pipe, 1);
            $read_bytes = strlen($byte);
            if (!$read_bytes) {
                return null;
            }
            $buf .= $byte;
            if (substr($buf, -2) == "\r\n") {
                // Catch solo \r\n indicating end of header
                if (strlen($buf) == 2) {
                    break;
                }
                list($header, $value) = explode(': ', $buf);
                $headers[$header] = trim($value);
                $buf = '';
            }
        }
        $len = (int) $headers['Content-Length'] ?? 0;
        $jsonBody = fread($pipe, $len);
        $this->log->debug('<<< ' . $jsonBody);

        $data = json_decode($jsonBody, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log->error('Invalid JSON: {json}', ['json' => $jsonBody]);
            return null;
        }

        return $this->messageFactory->factory($data);
    }

    public function writeResponse(Response $response): void
    {
        $this->writeMessage($response);
    }

    public function writeMessage(Message $response): void
    {
        $message = $response->format();
        $this->log->debug('>>> ' . $message);
        fwrite($this->stdout, $message);
    }

    /**
     * Open a nonblocking stream to a file
     */
    private function open(string $filename, string $mode)
    {
        $fh = fopen($filename, $mode);
        if (!is_resource($fh)) {
            $this->log->error('Could not open ' . $filename);
            exit(1);
        }
        $this->log->debug($filename . ' opened');
        if (stream_set_blocking($fh, false)) {
            $this->log->debug($filename . ' set to nonblocking');
            return $fh;
        } else {
            $this->log->error('Could not make ' . $filename . ' nonblocking');
            exit(1);
        }
    }
}
