<?php
/**
 * Redis缓存驱动类
 * 需要Redis扩展支持
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class CacheRedis extends CacheBase
{
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		$host = isset($option['host']) ? $option['host'] : '127.0.0.1';
		$port = isset($option['port']) ? $option['port'] : 6379;
		$timeout = isset($option['timeout']) ? $option['timeout'] : 0;
		$pconnect = isset($option['pconnect']) ? $option['pconnect'] : false;
		$this->cache = new Redis;
		if($pconnect)
		{
			$this->cache->pconnect($host, $port, $timeout);
		}
		else
		{
			$this->cache->connect($host, $port, $timeout);
		}
		if (isset($option['auth']))
		{
			$this->cache->auth($option['auth']);
		}
	}
	
	/**
	 * 清空缓存
	 * @return bool
	 */
	public function clear()
	{
		return $this->cache->flushDB();
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
		$result = $this->cache->get($alias);
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
		return $this->cache->delete($alias);
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
		if($expire > 0)
		{
			return $this->cache->setex($alias,$expire,$value);
		}
		else
		{
			return $this->cache->set($alias,$value);
		}
	}

	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		return $this->cache->exists($alias,$config);
	}
}