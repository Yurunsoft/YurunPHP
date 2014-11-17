<?php
/**
 * 配置驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class ConfigBase extends ArrayData
{
	// 配置文件名
	protected $fileName;
	
	/**
	 * 构造方法
	 *
	 * @param type $p1        	
	 */
	public function __construct($p1 = null)
	{
		if (is_array($p1))
		{
			// 传入数组，把数组作为数据，初始化实例
			$this->set($p1);
		}
	}
	
	/**
	 * 从文件载入数据，将清空原数据
	 *
	 * @param string $fileName        	
	 */
	public function fromFile($fileName)
	{
		// 清空数据
		$this->clear();
		$this->fileName = $fileName;
		$this->parseFileToData($fileName);
	}
	/**
	 * 从文件载入数据，将合并覆盖原数据
	 *
	 * @param string $fileName        	
	 */
	public function fromFileMerge($fileName)
	{
		$this->parseFileToData($fileName);
	}
	/**
	 * 获取配置文件名
	 *
	 * @return string
	 */
	public function fileName()
	{
		return $this->fileName;
	}
	/**
	 * 将文件转换为数据
	 *
	 * @abstract
	 *
	 * @access protected
	 * @param string $fileName        	
	 */
	protected abstract function parseFileToData($fileName);
	/**
	 * 将数据保存至文件
	 *
	 * @abstract
	 *
	 * @access protected
	 * @param string $fileName        	
	 */
	protected abstract function save($fileName = null);
}