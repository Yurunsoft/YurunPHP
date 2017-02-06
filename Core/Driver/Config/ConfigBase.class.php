<?php
/**
 * 配置驱动基类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class ConfigBase extends ArrayData
{
	public $merge = true;
	/**
	 * 构造方法
	 * @param array $option        	
	 */
	public function __construct($option = null)
	{
		if(isset($option['merge']))
		{
			$this->merge = $option['merge'];
		}
	}
	/**
	 * 保存数据
	 * @param array $option 参数
	 */
	public abstract function save($option = array());
}