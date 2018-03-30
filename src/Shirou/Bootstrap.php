<?php
namespace Shirou;

use Phalcon\{
    DI,
    DiInterface,
    Events\Manager as PhEventsManager
};
use Shirou\Behavior\DIBehavior;

abstract class Bootstrap implements Interfaces\IBootstrap
{
    use DIBehavior {
        DIBehavior::__construct as protected __DIConstruct;
    }

    protected $_moduleName = "";
    private $_config;
    private $_em;

    public function __construct(DI $di, PhEventsManager $em)
    {
        $this->__DIConstruct($di);
        $this->_em = $em;
        $this->_config = $di->get('config');
    }

    public function registerServices()
    {
        if (empty($this->_moduleName)) {
            $class = new \ReflectionClass($this);
            throw new \Exception('Bootstrap has no module name: ' . $class->getFileName());
        }

        $di = $this->getDI();
        $config = $this->getConfig();
        $eventsManager = $this->getEventsManager();
        $moduleDirectory = $this->getModuleDirectory();

        /*************************************************/
        //  Initialize dispatcher.
        /*************************************************/
        $eventsManager->attach('dispatch:beforeException', new Plugin\DispatchError());

        // Create dispatcher.
        $dispatcher = new Dispatcher();
        $dispatcher->setEventsManager($eventsManager);
        $di->set('dispatcher', $dispatcher);
    }

    public function getModuleDirectory()
    {
        return $this->getDI()->get('registry')->directories['modules'] . $this->_moduleName;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    public function getEventsManager()
    {
        return $this->_em;
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }
}
