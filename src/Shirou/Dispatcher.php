<?php
namespace Shirou;

use Phalcon\Mvc\Dispatcher as PhDispatcher;

class Dispatcher extends PhDispatcher
{
    public function dispatch()
    {
        try {
            $parts = explode('_', $this->_handlerName);
            $finalHandlerName = '';

            foreach ($parts as $part) {
                $finalHandlerName .= ucfirst($part);
            }
            $this->_handlerName = $finalHandlerName;
            $this->_actionName = strtolower($this->_actionName);

            return parent::dispatch();
        } catch (\Exception $e) {
        }

        return parent::dispatch();
    }
}
