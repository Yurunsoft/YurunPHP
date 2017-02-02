<?php
class CacheAPCu extends CacheBase
{
	public function clear()
	{
		return apcu_clear_cache();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = apcu_fetch($alias,$success);
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
		return apcu_delete($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		return apcu_store($alias,$value,$ttl);
	}

	public function exists($alias, $config = array())
	{
		return apcu_exists($alias);
	}
}