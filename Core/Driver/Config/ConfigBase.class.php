<?php
/**
 * 配置驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class ConfigBase extends ArrayData
{
	public $merge = true;
	/**
	 * 构造方法
	 *
	 * @param type $option        	
	 */
	public function __construct($option = null)
	{
		if(isset($option['merge']))
		{
			$this->merge = $option['merge'];
		}
	}
	/**
	 * 将数据保存至文件
	 *
	 * @abstract
	 *
	 * @access protected
	 * @param string $fileName        	
	 */
	public abstract function save($fileName = null);
}