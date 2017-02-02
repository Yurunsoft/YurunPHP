<?php
class CacheAPC extends CacheBase
{
	public function clear()
	{
		return apc_clear_cache();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = apc_fetch($alias,$success);
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
		return apc_delete($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		return apc_store($alias,$value,$ttl);
	}

	public function exists($alias, $config = array())
	{
		return apc_exists($alias);
	}
}