<?php
/**
 * 文件日志驱动
 * @author Yurun <admin@yurunsoft.com>
 */
class LogFile extends LogBase
{
	// 日志路径
	protected $path;
	// 日志文件扩展名
	protected $ext;
	
	/**
	 * 保存
	 * @param array $data
	 */
	public function save($data)
	{
		$this->path = Config::get('@.LOG_PATH');
		$this->ext = Config::get('@.LOG_EXT');
		if(!is_dir($this->path))
		{
			mkdir($this->path,0777,true);
		}
		$fileName=date('Y-m-d');
		$fileURI=$this->path.$fileName.$this->ext;
		$i=1;
		while(is_file($fileURI) && filesize($fileURI)>Config::get('@.LOG_MAX_SIZE'))
		{
			$fileURI="{$this->path}{$fileName}-{$i}{$this->ext}";
			++$i;
		}
		$content='';
		foreach($data as $item)
		{
			$content.="[{$item['time']}] {$item['content']}\r\n";
		}
		if(''!==$content)
		{
			return error_log($content,3,$fileURI);
		}
		else
		{
			return true;
		}
	}
}