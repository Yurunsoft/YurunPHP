<?php
/**
 * ini配置驱动
 * @author Yurun <admin@yurunsoft.com>
 */
class ConfigIni extends ConfigFileBase
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
			$this->data = parse_ini_file($fileName,true,INI_SCANNER_RAW);
			var_dump($this->data);
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