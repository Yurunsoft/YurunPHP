<?php
class CacheRedis extends CacheBase
{
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		$host = isset($option['host']) ? $option['host'] : '127.0.0.1';
		$port = isset($option['port']) ? $option['port'] : 6379;
		$timeout = isset($option['timeout']) ? $option['timeout'] : 0;
		$pconnect = isset($option['pconnect']) ? $option['pconnect'] : false;
		$this->cache = new Redis;
		if($pconnect)
		{
			$this->cache->pconnect($host, $port, $timeout);
		}
		else
		{
			$this->cache->connect($host, $port, $timeout);
		}
	}
	
	public function clear()
	{
		return $this->cache->flushDB();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = $this->cache->get($alias);
		if(false === $result)
		{
			return $this->parseDefault($default);
		}
		else
		{
			return $result;
		}
	}

	public function remove($alias, $config = array())
	{
		return $this->cache->delete($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		if($expire > 0)
		{
			return $this->cache->setex($alias,$expire,$value);
		}
		else
		{
			return $this->cache->set($alias,$value);
		}
	}

	public function exists($alias, $config = array())
	{
		return $this->cache->exists($alias,$config);
	}
}