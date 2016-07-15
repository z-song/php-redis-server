<?php

namespace Encore\RedisServer;

use Encore\RedisServer\Server\Stat;

class Server
{
    const VERSION = '0.0.1';

    /**
     * @var Stat
     */
    public $stat;

    public $db = [];

    /**
     * @var int
     */
    protected $pid;

    protected $pidFile = '';

    protected $clients = [];

    public function __construct()
    {

    }

    public function initServer()
    {
        $this->stat = new Stat();

        $this->pid = posix_getpid();

        for ($i = 0; $i < Config::get('dbnum'); $i++) {
            $this->db[$i] = new Db($i);
        }
    }

    protected function createPidFile()
    {
        if (empty($this->pidFile)) {
            $this->pidFile = Config::get('pidfile');
        }

        if (false === file_put_contents($this->pidFile, posix_getpid())) {
            throw new \Exception('can not save pid to ' . $this->pidFile);
        }
    }
    
    public function listen()
    {

    }
    
    public function run()
    {
        $this->initServer();
    }

    protected function createClient($fd)
    {
        $client = new Client($this, $fd);

        $this->clients[] = $client;
    }

    protected function freeClient(Client $client)
    {
        
    }

    protected function resetClient(Client $client)
    {

    }
}
