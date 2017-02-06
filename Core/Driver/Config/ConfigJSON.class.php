<?php
/**
 * JSON配置驱动
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class ConfigJSON extends ConfigFileBase
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
			$this->data = json_decode(file_get_contents($fileName),true);
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
		file_put_contents(empty($fileName) ? $this->fileName : $fileName, json_encode($this->data), LOCK_EX);
	}
}