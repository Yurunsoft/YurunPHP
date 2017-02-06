<?php
/**
 * ini配置驱动
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class ConfigIni extends ConfigFileBase
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
			$this->data = parse_ini_file($fileName,true,INI_SCANNER_RAW);
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
		$file = fopen(empty($fileName) ? $this->fileName : $fileName,'w');
		flock($file,LOCK_EX);
		foreach($this->data as $section => $data)
		{
			fwrite($file, '[' . $section . ']' . PHP_EOL);
			foreach($data as $key => $value)
			{
				fwrite($file, $key . '=' . $value . PHP_EOL);
			}
		}
		fclose($file);
	}
}