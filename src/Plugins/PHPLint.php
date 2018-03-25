<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Plugins;

use Firehed\LSPHP\ResponseHandler;
use Firehed\LSPHP\Message\Notification;
use Firehed\LSPHP\Message\Request;
use Firehed\LSPHP\Message\RequestType;
use Firehed\LSPHP\Process;
use Psr\Log\LoggerAwareTrait;

class PHPLint implements PluginInterface
{
    use LoggerAwareTrait;

    public function getName(): string
    {
        return 'php -l';
    }

    public function handleNotification(Notification $notification, ResponseHandler $handler)
    {
        $params = $notification->getParams();
        switch ($notification->getMethod()) {
            case RequestType::TEXTDOCUMENT_DIDOPEN:
                $this->lintFile($params['textDocument']['uri'], $params['textDocument']['text'], $handler);
                break;
            case RequestType::TEXTDOCUMENT_DIDCHANGE:
                $this->lintFile($params['textDocument']['uri'], $params['contentChanges'][0]['text'], $handler);
                break;
        }
    }

    public function handleRequest(Request $request, ResponseHandler $handler)
    {
    }

    private function lintFile(string $uri, string $text, ResponseHandler $handler)
    {
        $lint = new Process('php -l');
        $lint->setStdin($text);
        $lint->execute(function ($ret, $stdout, $stderr) use ($handler, $uri) {
            $this->handleLintResponse($ret, $stderr, $uri, $handler);
        });
    }

    private function handleLintResponse(int $ret, string $stderr, string $uri, ResponseHandler $handler): void
    {
        if (!$ret) {
            $this->logger->debug('php -l on {uri} returned no errors', ['uri' => $uri]);
            // Send diag ok message
            $params = [
                'uri' => $uri,
                'diagnostics' => [],
            ];
            $notif = new Notification('textDocument/publishDiagnostics', $params);
            $handler->writeMessage($notif);
            return;
        }
        $this->logger->debug($stderr);
        $matches = [];
        $count = preg_match('/^(.*) in - on line (\d+)$/', $stderr, $matches);
        if (!$count) {
            $this->logger->error(
                'Got non-zero return code but failed to match a line number. Message: {stderr}',
                compact('stderr')
            );
            return;
        }
        $message = $matches[1];
        $line = $matches[2] - 1; // Zero-index the line number
        $diag = [
            'range' =>  [
                'start' => ['line' => $line, 'character' => 0],
                'end' => ['line' => $line, 'character' => 0],
            ],
            'message' => $message,
        ];
        $params = [
            'uri' => $uri,
            'diagnostics' => [$diag],
        ];
        $notif = new Notification('textDocument/publishDiagnostics', $params);
        $handler->writeMessage($notif);
    }
}
