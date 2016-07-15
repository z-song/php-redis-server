<?php

namespace Encore\RedisServer;

class Config
{
    protected static $config = [];

    protected static function load()
    {
        static::$config = require __DIR__ . '/../config.php';
    }

    public static function get($key, $default = null)
    {
        static::load();

        return array_key_exists(static::$config, $key) ? static::$config[$key] : $default;
    }
}
