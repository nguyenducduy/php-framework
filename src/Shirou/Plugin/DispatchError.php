<?php
namespace Shirou\Plugin;

use Phalcon\{
    Events\Event as PhEvent,
    Mvc\Dispatcher as PhDispatcher,
    Mvc\User\Plugin as PhUserPlugin
};

class DispatchError extends PhUserPlugin
{
    public function beforeException(PhEvent $event, PhDispatcher $dispatcher, $exception)
    {
    }
}
