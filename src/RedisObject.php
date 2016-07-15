<?php

namespace Encore\RedisServer;

class RedisObject
{
    const TYPE_STRING = 0;
    const TYPE_LIST   = 1;
    const TYPE_SET    = 2;
    const TYPE_ZSET   = 3;
    const TYPE_HASH   = 4;

    protected $validTypes = [
        self::TYPE_STRING,
        self::TYPE_LIST,
        self::TYPE_SET,
        self::TYPE_ZSET,
        self::TYPE_HASH,
    ];

    private $type;

    private $value;

    public function __construct($value, $type)
    {
        if (! in_array($type, $this->validTypes)) {
            throw new \Exception('invalid data type');
        }

        $this->type  = $type;
        $this->value = $value;
    }

    public function value()
    {
        return $this->value;
    }

    public function isString()
    {
        return $this->type == static::TYPE_STRING;
    }

    public function isList()
    {
        return $this->type == static::TYPE_LIST;
    }

    public function isRedisSet()
    {
        return $this->type == static::TYPE_SET;
    }

    public function isZset()
    {
        return $this->type == static::TYPE_ZSET;
    }

    public function isHash()
    {
        return $this->type == static::TYPE_HASH;
    }
}
