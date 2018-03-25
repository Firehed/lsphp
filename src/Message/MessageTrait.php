<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

trait MessageTrait
{
    private static $EOL = "\r\n";
    private $jsonrpc = '2.0';

    abstract protected function getBody();

    public function format(): string
    {
        $body = $this->getBody();
        $body['jsonrpc'] = $this->jsonrpc;
        $json = json_encode($body);

        $headers = [
            sprintf('Content-Length: %s', strlen($json)),
            sprintf('Content-Type: %s', 'application/vscode-jsonrpc; charset=utf-8'),
        ];

        $headerLines = implode(self::$EOL, $headers);

        return sprintf(
            '%s%s%s%s',
            $headerLines,
            self::$EOL,
            self::$EOL,
            $json
        );
    }

    public function __toString()
    {
        return json_encode($this->getBody());
    }
}
