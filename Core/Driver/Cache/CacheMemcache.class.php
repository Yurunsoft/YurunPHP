<?php
/**
 * Memcache缓存驱动类
 * 需要Memcache扩展支持
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
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
		$flag = isset($config['flag']) ? $config['flag'] : 0;
		$expire = isset($config['expire']) ? $config['expire'] : 0;
		return $this->cache->set($alias,$value,$flag,$expire);
	}

	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		return false !== $this->cache->get($alias,$config);
	}
}