<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

class Request implements Message
{
    use MessageTrait;
    use ParamsTrait;

    private $id;

    public function __construct($id, string $method, $params = null)
    {
        $this->id = $id;
        $this->method = $method;
        $this->params = $params;
    }

    protected function getBody()
    {
        return [
            'id' => $this->id,
            'method' => $this->method,
            'params' => $this->params,
        ];
    }

    public static function factory(array $jsonData): Request
    {
        $requiredKeys = ['id', 'jsonrpc', 'method', 'params'];
        foreach ($requiredKeys as $requiredKey) {
            if (!array_key_exists($requiredKey, $jsonData)) {
                throw new \OutOfBoundsException('Missing key ' . $requiredKey);
            }
        }

        return new Request(
            $jsonData['id'],
            $jsonData['method'],
            $jsonData['params']
        );
    }

    /** @return string | int */
    public function getId()
    {
        return $this->id;
    }

    public function isNotification(): bool
    {
        return false;
    }

    public function isRequest(): bool
    {
        return true;
    }

    public function isResponse(): bool
    {
        return false;
    }
}
