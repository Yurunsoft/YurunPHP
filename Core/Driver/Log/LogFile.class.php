<?php
/**
 * 文件日志驱动
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class LogFile extends LogBase
{
	// 单文件最大大小
	public $maxSize;
	// 日期格式
	public $dateFormat;
	// 日志路径
	public $path;
	// 日志文件扩展名
	public $ext;
	
	/**
	 * 构造方法
	 * @param type $option        	
	 */
	public function __construct($option = null)
	{
		if(isset($option['max_size']))
		{
			$this->maxSize = $option['max_size'];
		}
		if(isset($option['date_format']))
		{
			$this->dateFormat = $option['date_format'];
		}
		if(isset($option['path']))
		{
			$this->path = $option['path'];
			$last = substr($this->path,-1,1);
			if('/' !== $last && '\\' !== $last)
			{
				$this->path .= DIRECTORY_SEPARATOR;
			}
		}
		if(isset($option['ext']))
		{
			$this->ext = $option['ext'];
		}
	}
	/**
	 * 添加日志
	 * @param string $content
	 * @param array $option
	 */
	public function add($content, $option = array())
	{
		$this->data[] = array(
			'content'	=>	$content,
			'time'		=>	date($this->dateFormat)
		);
	}
	
	/**
	 * 保存
	 * @param array $data
	 * @return bool
	 */
	public function save()
	{
		if(!is_dir($this->path))
		{
			mkdir($this->path, 0777, true);
		}
		$fileName = date('Y-m-d');
		$fileURI = $this->path . $fileName . $this->ext;
		$i = 1;
		while(is_file($fileURI) && filesize($fileURI) > $this->maxSize)
		{
			$fileURI = "{$this->path}{$fileName}-{$i}{$this->ext}";
			++$i;
		}
		$content = '';
		foreach($this->data as $item)
		{
			$content .= "[{$item['time']}] {$item['content']}\r\n";
		}
		if('' !== $content)
		{
			return error_log($content, 3, $fileURI);
		}
		else
		{
			return true;
		}
	}
}