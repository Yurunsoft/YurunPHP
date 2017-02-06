<?php
/**
 * Memcached缓存驱动类
 * 需要Memcached扩展支持
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
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
	
	/**
	 * 清空缓存
	 * @return bool
	 */
	public function clear()
	{
		return $this->cache->flush();
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

	/**
	 * 删除缓存
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function remove($alias, $config = array())
	{
		$timeout = isset($config['timeout']) ? $config['timeout'] : 0;
		return $this->cache->delete($alias,$timeout);
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
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		return $this->cache->set($alias,$value,$expire);
	}
	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		return false !== $this->cache->get($alias,isset($config['cache_cb']) ? $config['cache_cb'] : null,isset($config['cas_token']) ? $config['cas_token'] : null);
	}
}