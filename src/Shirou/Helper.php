<?php
namespace Shirou;

use Phalcon\{
    DI,
    DiInterface,
    Tag as PhTag
};

abstract class Helper extends PhTag
{
    protected function __construct($di)
    {
        $this->setDI($di);
    }

    public static function getInstance($nameOrDI, $module = 'engine')
    {
        if ($nameOrDI instanceof DiInterface) {
            $di = $nameOrDI;
            $helperClassName = get_called_class();
        } else {
            $di = DI::getDefault();
            $nameOrDI = ucfirst($nameOrDI);
            $module = ucfirst($module);
            $helperClassName = sprintf('%s\Helper\%s', $module, $nameOrDI);
        }

        if (!$di->has($helperClassName)) {
            /** @var Helper $helperClassName */
            if (!class_exists($helperClassName)) {
                throw new Exception(
                    sprintf('Can not find Helper with name "%s". Searched in module: %s', $nameOrDI, $module)
                );
            }

            $helper = new $helperClassName($di);
            $di->set($helperClassName, $helper, true);
            return $helper;
        }

        return $di->get($helperClassName);
    }
}
