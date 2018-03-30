<?php
namespace Shirou\Behavior;

use Phalcon\{
    DI,
    DiInterface
};

trait DIBehavior
{
    private $_di;

    public function __construct($di = null)
    {
        if ($di == null) {
            $di = DI::getDefault();
        }
        $this->setDI($di);
    }

    public function setDI($di)
    {
        $this->_di = $di;
    }

    public function getDI()
    {
        return $this->_di;
    }
}
