<?php
declare(strict_types=1);

namespace Firehed\LSPHP\Plugins;

use Firehed\LSPHP\ResponseHandler;
use Firehed\LSPHP\Message\Notification;
use Firehed\LSPHP\Message\Request;
use Psr\Log\LoggerAwareInterface;

interface PluginInterface extends LoggerAwareInterface
{
    public function getName(): string;

    public function handleNotification(Notification $notification, ResponseHandler $handler);

    public function handleRequest(Request $request, ResponseHandler $handler);

    // Future tasks:
    // - Availability detection
    // - Message type subscription
}
