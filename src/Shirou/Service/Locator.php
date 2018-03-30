<?php
namespace Shirou\Service;

use Phalcon\DI;
use Shirou\Behavior\DIBehavior;

class Locator
{
    use DIBehavior {
        DIBehavior::__construct as protected __DIConstruct;
    }

    protected $_moduleName;

    public function __construct($moduleName, $di)
    {
        $this->_moduleName = $moduleName;
        $this->__DIConstruct($di);
    }

    public function __call($name, $arguments)
    {
        $ServiceClassName = sprintf('%s\Service\%s', ucfirst($this->_moduleName), ucfirst($name));
        $di = $this->getDI();

        if (!$di->has($ServiceClassName)) {
            if (!class_exists($ServiceClassName)) {
                throw new \Exception(sprintf('Can not find Service with name "%s".', $name));
            }

            $Service = new $ServiceClassName($this->getDI(), $arguments);
            $di->set($ServiceClassName, $Service, true);
            return $Service;
        }

        return $di->get($ServiceClassName);
    }
}
