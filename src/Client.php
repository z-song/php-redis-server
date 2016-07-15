<?php

namespace Encore\RedisServer;

use Encore\RedisServer\Server\Stat;

class Client
{
    protected $id;

    protected $fd;

    protected $db;

    protected $name = '';

    protected $cmd;

    protected $reply = [];

    protected $flags = 0;

    protected $server;

    protected $authenticated = false;

    protected $watchedKeys = [];

    protected $pubsubChannels = [];

    protected $pubsubPatterns = [];

    public function __construct(Server $server, $fd)
    {
        $this->server = $server;

        $this->fd = $fd;
        $this->id = Stat::$nextClientId++;

        $this->selectDb();
    }
    
    public function selectDb($id = 0)
    {
        $this->db = $this->server->db[$id];
    }

    public function name()
    {
        return $this->name;
    }

    public function setName($name = '')
    {
        $this->name = $name;
    }

    public function free()
    {
        
    }
}
