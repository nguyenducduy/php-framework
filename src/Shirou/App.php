<?php
namespace Shirou;

use Phalcon\{
    Mvc\Application as PhApp,
    DI\FactoryDefault as PhDi,
    Registry as PhRegistry,
    Events\Manager as PhEventsManager,
    Http\Response as PhResponse
};

class App extends PhApp
{
    use Init;

    protected $_config;

    private $_loaders = [
        'engine',
        'annotations',
        'db',
        'cache',
        'router',
        'security',
        'transformer',
        'file'
    ];

    public function __construct(array $options = [])
    {
        $di = new PhDi();

        $this->_config = Config::factory();
        $di->set('config', $this->_config, true);

        $registry = new PhRegistry();
        $registry->modules = $this->_config->default->modules;
        $registry->directories = [
            'modules' => ROOT_PATH . '/app/modules/'
        ];
        $di->set('registry', $registry);

        parent::__construct($di);
    }

    public function init()
    {
        $di = $this->_dependencyInjector;

        $di->set('app', $this, true);

        $eventsManager = new PhEventsManager();
        $this->setEventsManager($eventsManager);

        $this->_initLoader($di, $this->_config, $eventsManager);

        /**
         * Init services and engine system.
         */
        foreach ($this->_loaders as $service) {
            $serviceName = ucfirst($service);
            $eventsManager->fire('init:before' . $serviceName, null);
            $result = $this->{'_init' . $serviceName}($di, $this->_config, $eventsManager);
            $eventsManager->fire('init:after' . $serviceName, $result);
        }

        $di->set('eventsManager', $eventsManager, true);
    }

    public function run()
    {
      $this->useImplicitView(false);
      $this->handle();
    }

    public function registerModules(array $modules, $merge = null): PhApp
    {
        $bootstraps = [];
        $di = $this->getDI();

        foreach ($modules as $moduleName => $moduleClass) {
            if (isset($this->_modules[$moduleName])) {
                continue;
            }

            $bootstrap = new $moduleClass($di, $this->getEventsManager());
            $bootstraps[$moduleName] = function () use ($bootstrap, $di) {
                $bootstrap->registerServices();

                return $bootstrap;
            };
        }

        return parent::registerModules($bootstraps, $merge);
    }

    public function isConsole()
    {
        return (php_sapi_name() === 'cli');
    }
}
