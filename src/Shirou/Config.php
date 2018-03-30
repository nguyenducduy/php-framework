<?php
namespace Shirou;

use Phalcon\Config as PhConfig;
use Dotenv\Dotenv;
use Spyc as YmlLoader;

class Config extends PhConfig
{
    const CONFIG_CACHE_PATH = '/app/storage/cache/data/config.php';

    private $_currentStage;

    public function __construct(array $arrayConfig, string $stage)
    {
        $this->_currentStage = $stage;
        parent::__construct($arrayConfig);
    }

    public static function factory(): PhConfig
    {
        if (file_exists(self::CONFIG_CACHE_PATH) && getenv('STAGE') == 'prod') {
            $config = new Config(
                include_once(self::CONFIG_CACHE_PATH),
                getenv('STAGE')
            );
        } else {
            $config = self::_getConfiguration(getenv('STAGE'));
            $config->refreshCache();
        }

        return $config;
    }

    protected static function _getConfiguration(string $stage): PhConfig
    {
        $config = new Config([], $stage);
        $configDirectory = ROOT_PATH . '/app/config/';

        $configFiles = glob($configDirectory .'/*.yml');

        foreach ($configFiles as $file) {
            $data = YmlLoader::YAMLLoad($file);
            $config->offsetSet(basename($file, '.yml'), $data);
        }

        // load secure env config
        $dotenv = new Dotenv($configDirectory, '.' . $stage . '.env');
        $dotenv->load();

        return $config;
    }

    public function refreshCache()
    {
        file_put_contents(ROOT_PATH . self::CONFIG_CACHE_PATH, $this->_toConfigurationString());
    }

    protected function _toConfigurationString($data = null)
    {
        if (!$data) {
            $data = $this->toArray();
        }

        $configText = var_export($data, true);

        // Fix pathes. This related to windows directory separator.
        $configText = str_replace('\\\\', DS, $configText);

        $configText = str_replace("'" . ROOT_PATH, "ROOT_PATH . '", $configText);
        $headerText = '<?php
/**
* WARNING
*
* Manual changes to this file may cause a malfunction of the system.
* Be careful when changing settings!
*
*/

return ';

        return $headerText . $configText . ';';
    }
}
