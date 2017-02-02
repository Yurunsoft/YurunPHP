<?php
class CacheXCache extends CacheBase
{
	public function clear()
	{
		return xcache_clear_cache(1, -1);
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = xcache_get($alias);
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
		return xcache_unset($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		return xcache_set($alias,$value,$ttl);
	}

	public function exists($alias, $config = array())
	{
		return xcache_isset($alias);
	}
}