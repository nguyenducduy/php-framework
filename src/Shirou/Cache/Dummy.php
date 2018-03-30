<?php
namespace Shirou\Cache;

use Phalcon\{
    Cache\Backend,
    Cache\BackendInterface
};

class Dummy extends Backend implements BackendInterface
{
    public function __construct($frontend, $options = null)
    {
    }

    public function start($keyName, $lifetime = null)
    {
        return null;
    }

    public function stop($stopBuffer = null)
    {
    }

    public function getFrontend()
    {
        return null;
    }

    public function getOptions()
    {
        return [];
    }

    public function isFresh()
    {
        return true;
    }

    public function isStarted()
    {
        return true;
    }

    public function setLastKey($lastKey)
    {
    }

    public function getLastKey()
    {
        return '';
    }

    public function get($keyName, $lifetime = null)
    {
        return null;
    }

    public function save($keyName = null, $content = null, $lifetime = null, $stopBuffer = null)
    {
    }

    public function delete($keyName)
    {
        return false;
    }

    public function queryKeys($prefix = null)
    {
        return [];
    }

    public function exists($keyName = null, $lifetime = null)
    {
        return false;
    }

    public function increment($key_name = null, $value = null)
    {
    }

    public function decrement($key_name = null, $value = null)
    {
    }

    public function flush()
    {
    }
}
