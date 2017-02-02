<?php
class CacheEAccelerator extends CacheBase
{
	public function clear()
	{
		return eaccelerator_gc();
	}

	public function get($alias, $default = false, $config = array())
	{
		$result = eaccelerator_get($alias);
		if(null === $success)
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
		return eaccelerator_rm($alias);
	}

	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		eaccelerator_lock($alias);
		$result = eaccelerator_put($alias,$value,$ttl);
		eaccelerator_unlock($alias);
		return $result;
	}

	public function exists($alias, $config = array())
	{
		return null !== eaccelerator_get($alias);
	}
}