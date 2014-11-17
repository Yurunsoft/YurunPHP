<?php
/**
 * 缓存驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class CacheBase
{
	/**
	 * 构造方法
	 */
	public function __construct()
	{
	}
	
	/**
	 * 设置缓存
	 *
	 * @abstract
	 *
	 */
	abstract public function set($alias, $value, $config = array());
	
	/**
	 * 获取缓存
	 *
	 * @abstract
	 *
	 */
	abstract public function get($alias, $default = false, $config = array());
	
	/**
	 * 删除缓存
	 *
	 * @abstract
	 *
	 */
	abstract public function remove($alias, $config = array());
	
	/**
	 * 清空缓存
	 */
	abstract public function clear();
	
	/**
	 * 缓存是否过期。根据缓存写入时间和有效期限判断。
	 *
	 * @param int $startTime        	
	 * @param int $expire        	
	 * @return boolean
	 */
	protected static function isExpired1($startTime, $expire)
	{
		return $expire != 0 && $startTime + $expire < $_SERVER["REQUEST_TIME"];
	}
	
	/**
	 * 缓存是否过期。根据失效时间判断
	 *
	 * @param int $endTime        	
	 * @return boolean
	 */
	protected static function isExpired2($endTime)
	{
		return $endTime != 0 && $endTime < $_SERVER["REQUEST_TIME"];
	}
}