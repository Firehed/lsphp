<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

trait ParamsTrait
{
    private $params;

    public function getParams()
    {
        return $this->params;
    }
}
