<?php
/**
 * 缓存驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class LogBase
{
	public $data = array();
	/**
	 * 添加日志
	 * @param string $content
	 * @param array $option
	 */
	public abstract function add($content, $option = array());

	/**
	 * 保存方法，需要实现
	 */
	public abstract function save();
}