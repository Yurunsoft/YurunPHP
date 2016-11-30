<?php
/**
 * PHP数组配置驱动
 * @author Yurun <admin@yurunsoft.com>
 */
class ConfigPhp extends ConfigBase
{
	/**
	 * 构造方法
	 *
	 * @param type $p1        	
	 */
	public function __construct($p1 = null)
	{
		parent::__construct($p1);
		if (is_string($p1))
		{
			// 传入文件名，把该文件内数据，初始化实例
			$this->fromFile($p1);
		}
	}
	
	/**
	 * 将文件转换为数据
	 *
	 * @abstract
	 *
	 * @access protected
	 * @param string $fileName        	
	 */
	protected function parseFileToData($fileName)
	{
		if (is_file($fileName))
		{
			$file = fopen($fileName,'r');
			flock($file,LOCK_SH);
			$this->data = include $fileName;
			fclose($file);
			return true;
		}
		else
		{
			return false;
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
	public function save($fileName = null)
	{
		file_put_contents(empty($fileName) ? $this->fileName : $fileName, '<?php return ' . var_export($this->data, true) . ';', LOCK_EX);
	}
}