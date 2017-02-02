<?php
class CacheWinCache extends CacheBase
{
	public function clear()
	{
		return wincache_ucache_clear();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = wincache_ucache_get ($alias,$success);
		if(false === $success)
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
		return wincache_ucache_delete($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		return wincache_ucache_set($alias,$value,$ttl);
	}

	public function exists($alias, $config = array())
	{
		return wincache_ucache_exists($alias);
	}
}