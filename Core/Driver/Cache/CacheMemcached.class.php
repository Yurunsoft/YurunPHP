<?php
class CacheMemcached extends CacheBase
{
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		$servers = isset($option['servers']) ? $option['servers'] : array();
		$this->cache = new Memcached;
		$this->cache->addServers($servers);
		if(!empty($option['options']))
		{
			$this->cache->setOptions($option['options']);
		}
	}
	
	public function clear()
	{
		return $this->cache->flush();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = $this->cache->get($alias,isset($config['cache_cb']) ? $config['cache_cb'] : null,isset($config['cas_token']) ? $config['cas_token'] : null);
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
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		return $this->cache->set($alias,$value,$expire);
	}
}