<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Message;

use OutOfBoundsException;
use Psr\Log\LoggerInterface;

class MessageFactory
{
    private const MAP = [
        RequestType::INITIALIZE => Request::class,
        RequestType::TEXTDOCUMENT_DIDCHANGE => Notification::class,
        RequestType::TEXTDOCUMENT_DIDOPEN => Notification::class,
    ];

    /** @var LoggerInterface */
    private $log;

    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    public function factory(array $jsonData): ?Message
    {
        if (!isset($jsonData['method'])) {
            throw new OutOfBoundsException('Message does not ccontain a method');
        }
        $method = $jsonData['method'];

        switch (self::MAP[$method] ?? null) {
            case Request::class:
                return Request::factory($jsonData);
            case Notification::class:
                return Notification::factory($jsonData);
            case null:
                $this->log->info('Received message with unknown method {method}', compact('method'));
                return null;
        }
    }
}
