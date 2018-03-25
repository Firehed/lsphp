<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

use OutOfBoundsException;

class Notification implements Message
{
    use MessageTrait {
        MessageTrait::__toString as defaultToString;
    }
    use ParamsTrait;


    public function __construct(string $method, $params = null)
    {
        $this->method = $method;
        $this->params = $params;
    }

    protected function getBody()
    {
        return [
            'method' => $this->method,
            'params' => $this->params,
        ];
    }

    public function __toString()
    {
        switch ($this->method) {
            case 'window/logMessage':
                return sprintf('[%s] %s', $this->params['type'], $this->params['message']);
            default:
                return $this->defaultToString();
        }
    }

    public static function factory(array $jsonData): Notification
    {
        $requiredKeys = ['jsonrpc', 'method'];
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $jsonData)) {
                throw new OutOfBoundsException('Missing key ' . $requiredKey);
            }
        }

        return new Notification(
            $jsonData['method'],
            $jsonData['params'] ?? null
        );
    }

    public function isNotification(): bool
    {
        return true;
    }

    public function isRequest(): bool
    {
        return false;
    }

    public function isResponse(): bool
    {
        return false;
    }
}
