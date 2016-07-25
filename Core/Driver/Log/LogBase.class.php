<?php
/**
 * 缓存驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class LogBase
{
	/**
	 * 构造方法
	 */
	public function __construct()
	{
	}

	/**
	 * 保存方法，需要实现
	 * @param array $data
	 */
	public function save($data)
	{
		
	}
}