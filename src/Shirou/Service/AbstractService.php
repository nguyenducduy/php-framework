<?php
namespace Shirou\Service;

use Phalcon\{
    DI,
    DiInterface
};
use Shirou\{
    Behavior\DIBehavior,
    Interfaces\IService
};

abstract class AbstractService implements ServiceInterface
{
    use DIBehavior {
        DIBehavior::__construct as protected __DIConstruct;
    }

    private $_arguments;

    public function __construct(DiInterface $di, $arguments)
    {
        $this->__DIConstruct($di);
        $this->_arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->_arguments;
    }
}
