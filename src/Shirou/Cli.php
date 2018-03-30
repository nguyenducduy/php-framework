<?php
namespace Shirou;

use Shirou\Console\ConsoleUtil;
use Phalcon\Mvc\Application as PhApp;
use Phalcon\DI\FactoryDefault as PhDi;
use Phalcon\Registry as PhRegistry;
use Phalcon\Events\Manager as PhEventsManager;

class Cli extends PhApp
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
        'file'
    ];

    private $_commands = [];

    public function __construct()
    {
        // Create default DI
        $di = new PhDi();

        // Get config
        $this->_config = Config::factory();

        // Store config in the DI container.
        $di->set('config', $this->_config, true);

        /**
         * Adding modules to registry to load.
         * Module namespace - directory will be load from here.
         */
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
        /**
         * Set application main objects.
         */
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

        // Init commands.
        $this->_initCommands();
    }

    /**
     * Init modules and register them. (Call from _initLoader)
     *
     * @param array $modules Modules bootstrap classes.
     * @param null  $merge   Merge with existing.
     *
     * @return $this
     */
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

    /**
     * Init commands.
     *
     * @return void
     */
    protected function _initCommands()
    {
        // Get modules commands.
        foreach ($this->getDI()->get('registry')->modules as $module) {
            $module = ucfirst($module);
            $path = $this->getDI()->get('registry')->directories['modules'] . $module . '/Command';
            $namespace = $module . '\Command\\';
            $this->_getCommandsFrom($path, $namespace);
        }
    }

    /**
     * Get commands located in directory.
     *
     * @param string $commandsLocation  Commands location path.
     * @param string $commandsNamespace Commands namespace.
     *
     * @return void
     */
    protected function _getCommandsFrom($commandsLocation, $commandsNamespace)
    {
        if (!file_exists($commandsLocation)) {
            return;
        }

        // Get all file names.
        $files = scandir($commandsLocation);

        // Iterate files.
        foreach ($files as $file) {
            if ($file == "." || $file == "..") {
                continue;
            }

            $commandClass = $commandsNamespace . str_replace('.php', '', $file);
            $this->_commands[] = new $commandClass($this->getDI());
        }
    }

    /**
     * Handle all data and output result.
     *
     * @throws Exception
     * @return mixed
     */
    public function run()
    {
        print ConsoleUtil::infoLine(
            'Commands Manager',
            false,
            1
        );

        // Not arguments?
        if (!isset($_SERVER['argv'][1])) {
            $this->printAvailableCommands();
            die();
        }

        // Check if 'help' command was used.
        if ($this->_helpIsRequired()) {
            return;
        }

        // Try to dispatch the command.
        if ($cmd = $this->_getRequiredCommand()) {
            return $cmd->dispatch();
        }

        // Check for alternatives.
        $available = [];
        foreach ($this->_commands as $command) {
            $providedCommands = $command->getCommands();
            foreach ($providedCommands as $command) {
                $soundex = soundex($command);
                if (!isset($available[$soundex])) {
                    $available[$soundex] = [];
                }
                $available[$soundex][] = $command;
            }
        }

        // Show exception with/without alternatives.
        $soundex = soundex($_SERVER['argv'][1]);
        if (isset($available[$soundex])) {
            print ConsoleUtil::warningLine(
                'Command "' . $_SERVER['argv'][1] .
                '" not found. Did you mean: ' . join(' or ', $available[$soundex]) . '?'
            );
            $this->printAvailableCommands();
        } else {
            print ConsoleUtil::warningLine('Command "' . $_SERVER['argv'][1] . '" not found.');
            $this->printAvailableCommands();
        }
    }

    /**
     * Output available commands.
     *
     * @return void
     */
    public function printAvailableCommands()
    {
        print ConsoleUtil::headLine('Available commands:');
        foreach ($this->_commands as $command) {
            print ConsoleUtil::commandLine(join(', ', $command->getCommands()), $command->getDescription());
        }
        print PHP_EOL;
    }

    /**
     * Get required command.
     *
     * @param string|null $input Input from console.
     *
     * @return AbstractCommand|null
     */
    protected function _getRequiredCommand($input = null)
    {
        if (!$input) {
            $input = $_SERVER['argv'][1];
        }

        foreach ($this->_commands as $command) {
            $providedCommands = $command->getCommands();
            if (in_array($input, $providedCommands)) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Check help system.
     *
     * @return bool
     */
    protected function _helpIsRequired()
    {
        if ($_SERVER['argv'][1] != 'help') {
            return false;
        }

        if (empty($_SERVER['argv'][2])) {
            $this->printAvailableCommands();
            return true;
        }

        $command = $this->_getRequiredCommand($_SERVER['argv'][2]);
        if (!$command) {
            print ConsoleUtil::warningLine('Command "' . $_SERVER['argv'][2] . '" not found.');
            return true;
        }

        $command->getHelp((!empty($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : null));
        return true;
    }

    /**
     * Check if application is used from console.
     *
     * @return bool
     */
    public function isConsole()
    {
        return (php_sapi_name() === 'cli');
    }
}
