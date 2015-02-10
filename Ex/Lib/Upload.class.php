<?php
class Upload
{
	/**
	 * 上传文件存储目录
	 * @var string
	 */
	public $savePath;
	/**
	 * 上传文件的路径规则。为空则不指定子目录；为数组：函数返回值；为字符串：原样使用
	 * 例:array('date','Y-m-d')	代表:上传目录/年-月-日/上传文件名
	 * 例:array('myFunction','__ITEM__')	代表:上传目录/myFunction($_FILES[名称])/上传文件名
	 * @var array
	 */
	public $subPath='';
	/**
	 * 上传文件的最大大小，单位KB，1KB=1024B，0为不限制大小。
	 * @var int
	 */
	public $maxSize;
	/**
	 * 文件名生成规则，支持字符串和数组定义。默认使用原文件名，不推荐！
	 * 例:array(array('$this','fileMd5'),'__ITEM__') 代表使用本类中的FileMd5方法返回值
	 * @var string
	 */
	public $fileRule;
	/**
	 * 允许上传的文件扩展名
	 * @var array
	 */
	public $allowExts;
	/**
	 * 允许上传的文件类型
	 * @var array
	 */
	public $allowMimes;
	/**
	 * 是否允许覆盖已存在的文件
	 */
	public $allowOverWrite=false;
	/**
	 * 结果数组
	 * @var array
	 */
	public $result;
	/**
	 * 已上传的所有文件
	 * @var array
	 */
	public $files;
	/**
	 * 构造方法
	 * @param string $savePath
	 * @param string $subPath
	 * @param string $fileRule
	 * @param string $allowExts
	 * @param number $maxSize
	 */
	public function __construct($savePath=null,$subPath=null,$fileRule=null,$allowExts=null,$maxSize=2048)
	{
		$this->savePath=empty($savePath)?(defined('PATH_UPLOAD')?PATH_UPLOAD:''):$savePath;
		$this->subPath=$subPath===null?Config::get('@.UPLOAD.SUBPATH'):$subPath;
		$this->fileRule=$fileRule===null?Config::get('@.UPLOAD.FILERULE'):$fileRule;
		$this->maxSize=$maxSize===null?Config::get('@.UPLOAD.MAXSIZE'):$maxSize;
		if(is_array($allowExts))
		{
			$this->allowExts=$allowExts;
		}
		else
		{
			$this->setExtMode($allowExts);
		}
	}
	/**
	 * 设置文件扩展名模式。成功返回true，失败返回false。
	 * @param string $mode 模式名。图像：image或img；视频：video；音频：music或audio
	 */
	public function setExtMode($mode)
	{
		switch($mode)
		{
			case 'image':
			case 'img':
				$this->allowExts=array('png','jpg','gif');
				break;
			case 'video':
				$this->allowExts=array('mp4','avi','rmvb','rm','mpg','flv','mov','wmv','3gp','mkv');
				break;
			case 'music':
			case 'audio':
				$this->allowExts=array('mp3','wma','aiff','au','midi','aac','ape');
				break;
			default:
				return false;
				break;
		}
		return true;
	}
	/**
	 * 上传文件，返回是否全部上传成功。成功返回true，有一个文件失败就返回false
	 * @return bool
	 */
	public function up($name=null)
	{
		if($name===null)
		{
			$files=$this->parseFiles($_FILES);
		}
		else 
		{
			$files=$this->parseFiles(array($name=>$_FILES[$name]));
		}
		$this->result=array();
		$this->files=array();
		$result=true;
		foreach($files as $key=>$item)
		{
			if(isset($item['name']))
			{
				// 单文件上传
				if(!$this->uploadSingle($item,$r))
				{
					$result=false;
				}
			}
			else
			{
				// 多文件上传
				if(!$this->uploadMulti($item,$r))
				{
					$result=false;
				}
			}
			$this->result[$key]=$r;
		}
		return $result;
	}
	/**
	 * 上传单文件
	 * @param array $item
	 * @param array $result
	 * @return bool
	 */
	private function uploadSingle($item,&$result)
	{
		$result=$item;
		$result['error_msg']=$this->getErrorMsg($item['error']);
		if($item['error']===UPLOAD_ERR_OK)
		{
			// 各种检查
			if(!is_uploaded_file($item['tmp_name']))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_ILLEGAL');
				return false;
			}
			if(!$this->checkMime($item['type']))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_MIME');
				return false;
			}
			if(!$this->checkExt($item['name']))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_EXT');
				return false;
			}
			if(!$this->checkSize($item['size']))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_SIZE');
				return false;
			}
			// 上传文件目录的路径
			$path=$this->getUploadPath($item);
			$subPath=$this->getSubPath($item);
			$path.=$subPath;
			// 判断目录是否存在
			if(!is_dir($path))
			{
				// 自动创建目录
				mkdir($path,0777,true);
			}
			$filename=$this->getFileName($item);
			// 是否使用原文件名
			if(empty($filename))
			{
				$filename=$item['name'];
			}
			$file=$path.$filename;
			// 判断文件是否存在，是否允许覆盖
			if(!$this->allowOverWrite && is_file($file))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_SAME_FILE');
				return false;
			}
			if(!move_uploaded_file($item['tmp_name'], $file))
			{
				$result['error_msg']=Lang::get('UPLOAD_ERR_MOVE_FAIL');
				return false;
			}
			$result['file']=$file;
			$result['subpath']=$subPath;
			$result['filename']=$filename;
			$this->files[]=$file;
			return true;
		}
		else 
		{
			return false;
		}
	}
	private function uploadMulti($files,&$result)
	{
		$result=$files;
		$r=true;
		foreach($files as $key=>$item)
		{
			if(!$this->uploadSingle($item,$result2))
			{
				$r=false;
			}
			$result[$key]=$result2;
		}
		return $r;
	}
	private function checkMime($mime)
	{
		return empty($this->allowMimes)?true:in_array($mime,$this->allowMimes);
	}
	private function checkExt($name)
	{
		return empty($this->allowExts)?true:in_array(pathinfo($name, PATHINFO_EXTENSION),$this->allowExts);
	}
	private function checkSize($size)
	{
		return empty($this->maxSize)?true:$size<=$this->maxSize*1024;
	}
	private function getUploadPath($item)
	{
		$path=$this->savePath;
		if(substr($this->savePath,-1)!=='/')
		{
			$path.='/';
		}
		return $path;
	}
	private function getSubPath($item)
	{
		return $this->execRule($this->subPath,$item).'/';
	}
	private function getFileName($item)
	{
		return $this->execRule($this->fileRule,$item).'.'.pathinfo($item['name'], PATHINFO_EXTENSION);
	}
	private function getErrorMsg($error)
	{
		switch($error)
		{
			case UPLOAD_ERR_OK:
				return '';
				break;
			case UPLOAD_ERR_INI_SIZE:
				return Lang::get('UPLOAD_ERR_INI_SIZE');
				break;
			case UPLOAD_ERR_FORM_SIZE:
				return Lang::get('UPLOAD_ERR_FORM_SIZE');
				break;
			case UPLOAD_ERR_PARTIAL:
				return Lang::get('UPLOAD_ERR_PARTIAL');
				break;
			case UPLOAD_ERR_NO_FILE:
				return Lang::get('UPLOAD_ERR_NO_FILE');
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				return Lang::get('UPLOAD_ERR_NO_TMP_DIR');
				break;
			case UPLOAD_ERR_CANT_WRITE:
				return Lang::get('UPLOAD_ERR_CANT_WRITE');
				break;
			default:
				return Lang::get('UPLOAD_ERR_UNKNOWN');
				break;
		}
	}
	/**
	 * 回滚上传操作，把上传的文件删除
	 */
	public function rollback()
	{
		if(is_array($this->files))
		{
			foreach($this->files as $item)
			{
				unlink($item);
			}
		}
	}
	public function parseFiles($files)
	{
		$result=array();
		foreach($files as $key=>$item)
		{
			if(is_array($item['name']))
			{
				$s=count($item['name']);
				$keys=array_keys($item);
				$result[$key]=array();
				for($i=0;$i<$s;++$i)
				{
					foreach($keys as $key2)
					{
						$result[$key][$i][$key2]=$files[$key][$key2][$i];
					}
				}
			}
			else
			{
				$result[$key]=$item;
			}
		}
		return $result;
	}
	/**
	 * 获取文件MD5
	 * @param array $item
	 */
	private function fileMd5($item)
	{
		return md5(file_get_contents($item['tmp_name']));
	}
	/**
	 * 获取唯一码
	 */
	private function unique()
	{
		return str_replace('.','',uniqid('',true));
	}
	private function execRule($rule,$item)
	{
		if(is_array($rule))
		{
			array_walk_recursive($rule,array($this,'parseRuleParam'),$item);
			if(count($rule)>0)
			{
				$name=$rule[0];
				unset($rule[0]);
				return call_user_func_array($name,$rule);
			}
			else
			{
				return '';
			}
		}
		else 
		{
			return $rule;
		}
	}
	private function parseRuleParam(&$value,$key,$item)
	{
		if($value==='$this')
		{
			$value=$this;
		}
		else if($value==='__ITEM__')
		{
			$value=$item;
		}
	}
}