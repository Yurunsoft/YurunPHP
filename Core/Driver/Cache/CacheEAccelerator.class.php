<?php
/**
 * eAccelerator缓存驱动类
 * 需要EAccelerator扩展支持
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class CacheEAccelerator extends CacheBase
{
	/**
	 * 清空缓存
	 * @return bool
	 */
	public function clear()
	{
		return eaccelerator_gc();
	}

	/**
	 * 获取缓存内容
	 * @param string $alias 别名
	 * @param mixed $default 默认值或者回调
	 * @param array $config 配置
	 * @return mixed
	 */
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

	/**
	 * 删除缓存
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function remove($alias, $config = array())
	{
		return eaccelerator_rm($alias);
	}

	/**
	 * 设置缓存
	 * @param string $alias 别名
	 * @param string $value 缓存内容
	 * @param array $config 配置
	 * @return bool
	 */
	public function set($alias, $value, $config = array())
	{
		$ttl = isset($config['ttl']) ? $config['ttl'] : 0;
		eaccelerator_lock($alias);
		$result = eaccelerator_put($alias,$value,$ttl);
		eaccelerator_unlock($alias);
		return $result;
	}

	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		return null !== eaccelerator_get($alias);
	}
}