<?php
declare(strict_types=1);

namespace Firehed\LSPHP;

interface ResponseHandler
{
    public function writeResponse(Message\Response $response): void;
}
