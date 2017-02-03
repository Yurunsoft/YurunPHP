<?php
/**
 * XML配置驱动
 * @author Yurun <admin@yurunsoft.com>
 */
class ConfigXML extends ConfigFileBase
{
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
			$this->data = XML::toArray(file_get_contents($fileName));
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
		file_put_contents(empty($fileName) ? $this->fileName : $fileName, XML::toString($this->data), LOCK_EX);
	}
}