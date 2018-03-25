<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

interface Message
{
    public function __toString();

    public function format(): string;

    public function getMethod(): string;

    public function isNotification(): bool;

    public function isRequest(): bool;

    public function isResponse(): bool;
}
