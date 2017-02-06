<?php
/**
 * 缓存驱动基类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class LogBase
{
	public $data = array();
	/**
	 * 构造方法
	 */
	public function __construct($option = array())
	{
	}
	/**
	 * 添加日志
	 * @param string $content
	 * @param array $option
	 */
	public abstract function add($content, $option = array());

	/**
	 * 保存方法，需要实现
	 * @return bool
	 */
	public abstract function save();
}