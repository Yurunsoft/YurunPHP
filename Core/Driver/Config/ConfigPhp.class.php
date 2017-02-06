<?php
/**
 * PHP数组配置驱动
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class ConfigPHP extends ConfigFileBase
{
	/**
	 * 将文件转换为数据
	 * @param string $fileName  
	 * @return bool     	
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
	 * 保存数据
	 * @param array $option 参数
	 */
	public function save($fileName = null)
	{
		file_put_contents(empty($fileName) ? $this->fileName : $fileName, '<?php return ' . var_export($this->data, true) . ';', LOCK_EX);
	}
}