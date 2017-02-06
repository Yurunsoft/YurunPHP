<?php
/**
 * 缓存驱动基类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class CacheBase
{
	/**
	 * 缓存操作对象
	 * @var type 
	 */
	public $cache;
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
	}
	
	/**
	 * 设置缓存
	 * @param string $alias 别名
	 * @param string $value 缓存内容
	 * @param array $config 配置
	 * @return bool
	 */
	abstract public function set($alias, $value, $config = array());
	
	/**
	 * 获取缓存内容
	 * @param string $alias 别名
	 * @param mixed $default 默认值或者回调
	 * @param array $config 配置
	 * @return mixed
	 */
	abstract public function get($alias, $default = false, $config = array());
	
	/**
	 * 删除缓存
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	abstract public function remove($alias, $config = array());
	
	/**
	 * 清空缓存
	 * @return bool
	 */
	abstract public function clear();
	
	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	abstract function exists($alias, $config = array());
	
	/**
	 * 缓存是否过期。根据缓存写入时间和有效期限判断。
	 * @param int $startTime        	
	 * @param int $expire        	
	 * @return boolean
	 */
	protected static function isExpired1($startTime, $expire)
	{
		return $expire != 0 && $startTime + $expire < $_SERVER['REQUEST_TIME'];
	}
	
	/**
	 * 缓存是否过期。根据失效时间判断
	 * @param int $endTime        	
	 * @return boolean
	 */
	protected static function isExpired2($endTime)
	{
		return $endTime != 0 && $endTime < $_SERVER['REQUEST_TIME'];
	}
	/**
	 * 处理默认值
	 * @param mixed $default
	 * @return mixed
	 */
	protected function parseDefault($default)
	{
		if(is_callable($default))
		{
			return $default();
		}
		else
		{
			return $default;
		}
	}
}