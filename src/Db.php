<?php

namespace Encore\RedisServer;

class Db
{
    protected $id = 0;

    protected $dict = [];

    public $expires = [];

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function id()
    {
        return $this->id;
    }

    public function setExpire($key, $when)
    {
        $this->expires[$key] = $when;
    }

    public function getExpire($key)
    {
        if (empty($this->expires) || ! array_key_exists($key, $this->expires)) {
            return -1;
        }

        return $this->expires[$key];
    }

    public function removeExpire($key)
    {
        unset($this->expires[$key]);
    }

    public function setKey($key, $val)
    {
        if ($this->exists($key)) {
            $this->overwrite($key, $val);
        } else {
            $this->add($key, $val);
        }
    }

    public function add($key, $val)
    {
        $this->dict[$key] = $val;
    }

    public function overwrite($key, $val)
    {
        $this->dict[$key] = $val;
    }

    public function exists($key)
    {
        return array_key_exists($this->dict, $key);
    }

    public function randomKey()
    {
        return array_rand(array_keys($this->dict));
    }

    public function delete($key)
    {
        unset($this->dict[$key]);
        unset($this->expires[$key]);
    }
}
