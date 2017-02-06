<?php
/**
 * 文件缓存驱动类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class CacheFile extends CacheBase
{
	// 缓存路径
	protected $path;
	// 缓存文件扩展名
	protected $ext;
	
	/**
	 * 构造方法
	 */
	public function __construct($config = null)
	{
		parent::__construct();
		// 缓存设置
		if (empty($config))
		{
			$this->path = APP_CACHE;
			$this->ext = Config::get('@.CACHE_EXT');
		}
		else
		{
			$this->path = $config['CACHE_PATH'];
			$this->ext = $config['CACHE_EXT'];
		}
	}

	/**
	 * 设置缓存
	 * @param string $alias 别名
	 * @param string $value 缓存内容
	 * @param array $config 配置
	 * @return bool
	 */
	public function set($alias, $value, $config = array())
	{
		// 打开或创建缓存文件
		$fp = fopen($this->getFileName($alias), 'w');
		if (false === $fp)
		{
			return false;
		}
		else
		{
			// 写锁
			if (flock($fp, LOCK_EX))
			{
				if(isset($config['raw']) && $config['raw'])
				{
					fwrite($fp, $value);
				}
				else 
				{
					// 防止泄露数据
					fwrite($fp, '<?php exit;?>');
					// 写入有效期
					fwrite($fp, sprintf('%012d', isset($config['expire']) ? $config['expire'] : 0));
					// 写入序列化后的值
					fwrite($fp, serialize($value));
				}
				$result = true;
			}
			else
			{
				$result = false;
			}
			// 关闭文件
			fclose($fp);
			return $result;
		}
	}
	
	/**
	 * 获取缓存内容
	 * @param string $alias 别名
	 * @param mixed $default 默认值或者回调
	 * @param array $config 配置
	 * @return mixed
	 */
	public function get($alias, $default = false, $config = array())
	{
		$fileName = $this->getFileName($alias);
		if (! is_file($fileName))
		{
			return $this->parseDefault($default);
		}
		// 打开或创建缓存文件
		$fp = fopen($fileName, 'r');
		if (false === $fp)
		{
			return $this->parseDefault($default);
		}
		else
		{
			if (flock($fp, LOCK_SH))
			{
				$data = '';
				while (! feof($fp))
				{
					$data .= fread($fp, 4096);
				}
				// 关闭文件
				fclose($fp);
				// 获取缓存有效时间
				$expire = (int)substr($data, 13, 12);
				$isRaw = isset($config['raw']) && $config['raw'];
				// 缓存文件最后修改时间和有效时间判定
				if (!$isRaw && $this->isExpired1(filemtime($fileName), $expire))
				{
					// 过期删除
					unlink($fileName);
					return $this->parseDefault($default);
				}
				else if($isRaw)
				{
					// 返回源数据
					return $data;
				}
				else if($isRaw)
				{
					// 返回源数据
					return $data;
				}
				else
				{
					// 返回源数据
					return unserialize(substr($data, 25));
				}
			}
			else
			{
				// 关闭文件
				fclose($fp);
				return $this->parseDefault($default);
			}
		}
	}

	/**
	 * 删除缓存
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function remove($alias, $config = array())
	{
		unlink($this->getFileName($alias));
	}
	
	/**
	 * 清空缓存
	 * @return bool
	 */
	public function clear()
	{
		enumFiles($this->path, 'unlink');
	}

	/**
	 * 缓存是否存在
	 * @param string $alias 别名
	 * @param array $config 配置
	 * @return bool
	 */
	public function exists($alias, $config = array())
	{
		return is_file($this->getFileName($alias));
	}
	
	/**
	 * 获取缓存文件名
	 *
	 * @param string $alias        	
	 * @return string
	 */
	public function getFileName($alias)
	{
		$n = dirname($alias);
		$path = $this->path;
		if ('.' !== $n)
		{
			$path .= $n . DIRECTORY_SEPARATOR;
		}
		if (! is_dir($path))
		{
			mkdir($path, 0755, true);
		}
		return $path . md5(basename($alias)) . $this->ext;
	}
}