<?php
class CacheMemcache extends CacheBase
{
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		$host = isset($option['host']) ? $option['host'] : '127.0.0.1';
		$port = isset($option['port']) ? $option['port'] : 11211;
		$timeout = isset($option['timeout']) ? $option['timeout'] : 1;
		$pconnect = isset($option['pconnect']) ? $option['pconnect'] : false;
		$this->cache = new Memcache;
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
		return $this->cache->flush();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = $this->cache->get($alias,$config);
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
		$timeout = isset($config['timeout']) ? $config['timeout'] : 0;
		return $this->cache->delete($alias,$timeout);
	}

	public function set($alias, $value, $config = array())
	{
		$flag = isset($config['flag']) ? $config['flag'] : 0;
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		return $this->cache->set($alias,$value,$flag,$expire);
	}
}