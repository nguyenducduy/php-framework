<?php
namespace Shirou;

use Phalcon\{
    DI,
    Loader as PhLoader,
    Events\Manager as PhEventsManager,
    Mvc\Url as PhUrl,
    Mvc\Model\Transaction\Manager as TxManager,
    Mvc\Model\Manager as PhModelsManager,
    Mvc\Model\MetaData\Memory as PhMetadataMemory,
    Mvc\Model\MetaData\Strategy\Annotations as PhStrategyAnnotations,
    Mvc\Router\Annotations as PhRouterAnnotations,
    Http\Response\Cookies as PhCookies,
    Db\Adapter\Pdo\Mysql as PhMysql,
    Security as PhSecurity,
    Annotations\Adapter\Memory as PhAnnotationsMemory,
    Cache\Frontend\Data as PhCacheData,
    Cache\Frontend\Output as PhCacheOutput
};
use League\{
    Flysystem\Adapter\Local as FlyLocalAdapter,
    Flysystem\Filesystem as FlySystem,
    Fractal\Manager as FractalManager
};

trait Init
{
    protected function _initLoader(DI $di, Config $config, PhEventsManager $eventsManager)
    {
        $registry = $di->get('registry');
        $namespaces = [];
        $bootstraps = [];

        foreach ($registry->modules as $module) {
            $moduleName = ucfirst($module);
            $namespaces[$moduleName] = $registry->directories['modules'] . $moduleName;
            $bootstraps[$module] = $moduleName . '\Bootstrap';
        }

        $loader = new PhLoader();
        $loader->registerNamespaces($namespaces);
        $loader->registerDirs([
            ROOT_PATH . '/libs'
        ]);
        $loader->setEventsManager($eventsManager);
        $loader->register();
        $this->registerModules($bootstraps);// This called function in App.php

        $di->set('loader', $loader);

        return $loader;
    }

    protected function _initEngine(DI $di, Config $config)
    {
        /**
         * The URL component is used to generate all kind of urls in the
         * application
         */
        $url = new PhUrl();
        $hostName = getenv('BASE_URL');

        $url->setBaseUri($di->get('request')->getScheme() . '://' . $hostName . '/');
        $di->set('url', $url, true);

        foreach ($di->get('registry')->modules as $module) {
            $di->set(strtolower($module), function () use ($module, $di) {
                return new Service\Locator($module, $di);
            }, true);
        }

        $di->set('transactions', function () {
            return new TxManager();
        }, true);

        $di->set('cookies', function () {
            $cookies = new PhCookies();
            $cookies->useEncryption(false);

            return $cookies;
        }, true);

        $di->set('request', function () {
            return new Http\Request();
        }, true);

        $di->set('response', function () {
            return new Http\Response();
        }, true);
    }

    protected function _initAnnotations(DI $di, Config $config)
    {
        $di->set(
            'annotations',
            function () use ($config) {
                if (getenv('STAGE') === 'dev') {
                    $adapter = new PhAnnotationsMemory();
                } else {
                    $annotationsAdapter = '\Phalcon\Annotations\Adapter\Files';
                    $adapter = new $annotationsAdapter([
                        'annotationsDir' => ROOT_PATH . $config->default->annotationsDir
                    ]);
                }

                return $adapter;
            },
            true
        );
    }

    protected function _initDb(DI $di, Config $config, PhEventsManager $eventsManager)
    {
        $connection = new PhMysql([
            'host' => getenv('DB_HOST'),
            'port' => getenv('DB_PORT'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
            'dbname' => getenv('DB_NAME'),
            'dialectClass' => Db\Dialect\MysqlExtended::class,
            'charset' => 'utf8'
        ]);
        $di->set('db', $connection, true);

        $di->set(
            'modelsManager',
            function () use ($config, $eventsManager) {
                $modelsManager = new PhModelsManager();
                $modelsManager->setEventsManager($eventsManager);

                $eventsManager->attach('modelsManager', new Db\Model\Annotations\Initializer());

                return $modelsManager;
            },
            true
        );

        /**
         * If the configuration specify the use of metadata adapter use it or use memory otherwise.
         */
        $di->set(
            'modelsMetadata',
            function () use ($config) {
                if (getenv('STAGE') === 'dev') {
                    $metaData = new PhMetadataMemory();
                } else {
                    $metadataAdapter = '\Phalcon\Mvc\Model\Metadata\Files';
                    $metaData = new $metadataAdapter([
                        'metaDataDir' => ROOT_PATH . $config->default->metaDataDir
                    ]);
                }

                $metaData->setStrategy(new PhStrategyAnnotations());

                return $metaData;
            },
            true
        );

        return $connection;
    }

    protected function _initCache(DI $di, Config $config)
    {
        if (getenv('STAGE') === 'dev') {
            // Create a dummy cache for system.
            // System will work correctly and the data will be always current for all adapters.
            $dummyCache = new Cache\Dummy(null);
            $di->set('viewCache', $dummyCache);
            $di->set('cacheOutput', $dummyCache);
            $di->set('cacheData', $dummyCache);
            $di->set('modelsCache', $dummyCache);
        } else {
            // Get the parameters.
            $cacheDataAdapter = '\Phalcon\Cache\Backend\\' . $config->default->cache->adapter;
            $frontEndOptions = ['lifetime' => $config->default->cache->lifetime];
            $backEndOptions = $config->default->cache->opts->toArray();
            $frontOutputCache = new PhCacheOutput($frontEndOptions);
            $frontDataCache = new PhCacheData($frontEndOptions);

            $cacheOutputAdapter = new $cacheDataAdapter($frontOutputCache, $backEndOptions);
            $di->set('viewCache', $cacheOutputAdapter, true);
            $di->set('cacheOutput', $cacheOutputAdapter, true);

            $cacheDataAdapter = new $cacheDataAdapter($frontDataCache, $backEndOptions);
            $di->set('cacheData', $cacheDataAdapter, true);
            $di->set('modelsCache', $cacheDataAdapter, true);
        }
    }

    protected function _initRouter(DI $di, Config $config)
    {
        $cacheData = $di->get('cacheData');
        $router = $cacheData->get(Cache\System::CACHE_KEY_ROUTER_DATA);

        if ($router == null) {
            $saveToCache = ($router === null);

            // Load all controllers of all modules for routing system.
            $modules = $di->get('registry')->modules;

            // Use the annotations router.
            $router = new PhRouterAnnotations(false);

            /**
             * Load all route from router file
             */
            foreach ($modules as $module) {
                $moduleName = ucfirst($module);

                // Get all file names.
                $moduleDir = opendir($di->get('registry')->directories['modules'] . $moduleName . '/Controller');
                while ($file = readdir($moduleDir)) {
                    if (preg_match('/^[V]{1}[0-9]+/', $file)) {
                        $versionDir = array_diff(
                            scandir($di->get('registry')->directories['modules'] . $moduleName . '/Controller/' . $file),
                            array('..', '.')
                        );

                        foreach ($versionDir as $versionController) {
                            $controllerVersion = $moduleName . '\Controller\\' . $file . '\\' . str_replace('Controller.php', '', $versionController);
                            $router->addModuleResource(strtolower($module), $controllerVersion);
                        }
                    }

                    if ($file == "." || $file == ".." || strpos($file, 'Controller.php') === false) {
                        continue;
                    }
                }
                closedir($moduleDir);
            }

            if ($saveToCache) {
                $cacheData->save(Cache\System::CACHE_KEY_ROUTER_DATA, $router, 2592000); // 30 days cache
            }
        }

        $router->removeExtraSlashes(true);
        $di->set('router', $router, true);

        return $router;
    }

    protected function _initSecurity(DI $di)
    {
        $di->set('security', function () {
            $security = new PhSecurity();
            $security->setWorkFactor(10);

            return $security;
        }, true);
    }

    protected function _initTransformer(DI $di)
    {
        $di->setShared('transformer', function () {
            $fractal = new FractalManager();
            $fractal->setSerializer(new FractalSerializer());

            return $fractal;
        });
    }

    protected function _initFile(DI $di)
    {
        $di->setShared('file', function () {
            $cache = null;
            $filesystem = new FlySystem(
                new FlyLocalAdapter(ROOT_PATH),
                $cache
            );

            return $filesystem;
        });
    }
}
