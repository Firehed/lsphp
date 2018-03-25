<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

class Response implements Message
{
    use MessageTrait;

    private $id;
    private $result;
    private $error;

    public function __construct(Request $request, $result, ResponseError $error = null)
    {
        $this->id = $request->getId();
        $this->result = $result;
        $this->error = $error; // TODO: make this sensible
    }

    protected function getBody()
    {
        return [
            'id' => $this->id,
            'result' => $this->result,
            'error' => $this->error,
        ];
    }

    public function getResult()
    {
        return $this->result;
    }
}
