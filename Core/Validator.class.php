<?php
/**
 * 数据验证类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class Validator
{
	/**
	 * 映射已存在的判断方法
	 */
	protected static $checkTypes = array ('array' => 'is_array','bool' => 'is_bool','numeric' => 'is_numeric','string' => 'is_string','int' => 'is_integer','long' => 'is_long','float' => 'is_float','double' => 'is_float','obj' => 'is_object','resource' => 'is_resource','null' => 'is_null','dir' => 'is_dir',	// 是否目录
	'file' => 'is_file',	// 是否文件
	'nan' => 'is_nan',	// 是否非法数值
	'scalar' => 'is_scalar'); // 是否标量
	/**
	 * 反射
	 */
	protected static $ref;
	/**
	 * 规则
	 */
	protected $rule = array ();
	/**
	 * 数据
	 */
	protected $data = array ();
	/**
	 * 验证结果
	 */
	protected $result = array ();
	/**
	 * 构造方法
	 * @param array $data        	
	 */
	public function __construct($data = array())
	{
		if (is_array($data))
		{
			$this->data = $data;
		}
	}
	
	/**
	 * 添加规则
	 * @param array $rule        	
	 */
	public function setRule($rule)
	{
		if (is_array($rule))
		{
			$this->rule[] = $rule;
		}
	}
	
	/**
	 * 设置规则
	 * @param string $name        	
	 * @param mixed $rule        	
	 */
	public function setAllRule($rule)
	{
		if (is_array($rule))
		{
			$this->rule = $rule;
		}
	}
	
	/**
	 * 设置数据
	 * @param array $data        	
	 */
	public function setData($data)
	{
		if (is_array($data))
		{
			$this->data = $data;
		}
	}
	/**
	 * 清理数据
	 */
	public function clear()
	{
		$this->rule = array ();
		$this->result = array ();
		$this->data = array ();
	}
	
	/**
	 * 验证数据
	 * @param boolean $all        	
	 * @param array $data        	
	 * @param array $rules        	
	 * @return array
	 */
	public function validate($all = false, $data = null, $rules = null)
	{
		if (null === $data)
		{
			$data = $this->data;
		}
		if (null === $rules)
		{
			$rules = $this->rule;
		}
		$this->result = array ();
		foreach ($rules as $rule)
		{
			// 规则合法性判断
			if (isset($rule['name']))
			{
				if (empty($rule['type']))
				{ // 无类型就不需要验证
					continue;
				}
				if (! isset($rule['msg']))
				{
					$rule['msg'] = false;
				}
			}
			else
			{
				if (count($rule) < 3)
				{
					throw new Exception(Lang::get('VAL_ARGS_LESS'));
					break;
				}
				$t = $rule;
				$rule = array ();
				// 直接传值处理
				$rule['name'] = $t[0];
				$rule['msg'] = $t[1];
				$rule['type'] = $t[2];
			}
			// 同名参数失败一次就不继续验证
			if (isset($this->result[$rule['name']]))
			{
				continue;
			}
			// 验证类型支持字符串方式
			if (! is_array($rule['type']))
			{
				$rule['type'] = explode(',', $rule['type']);
			}
			foreach ($rule['type'] as $k => $v)
			{
				// 验证方法传值处理
				if (is_numeric($k))
				{
					$type = $v;
					$args = array ($type);
				}
				else
				{
					$type = $k;
					$args = array_merge(array ($type), $v);
				}
				// 规则验证类型为是否存在
				if ('required' === $type)
				{
					$result = $this->required($rule['name'], $data);
				}
				else if ($this->required($rule['name'], $data))
				{
					// 值
					array_unshift($args, $data[$rule['name']]);
					// 调用验证方法
					$result = call_user_func_array(array ('self','check'), $args);
				}
				else
				{
					// 值不存在
					$result = false;
				}
				// 判断验证失败
				if (! $result)
				{
					if ($all)
					{
						$this->result[$rule['name']] = array ('name' => $rule['name'],'msg' => $rule['msg']);
					}
					else
					{
						$this->result = array ('name' => $rule['name'],'msg' => $rule['msg']);
						return $this->result;
					}
					break;
				}
			}
		}
		return $this->result;
	}
	
	/**
	 * 获取验证结果
	 */
	public function getResult()
	{
		return $this->result;
	}
	
	/**
	 * 是否含有指定名称的值
	 * @param string $name
	 * @param mixed $data
	 * @return boolean
	 */
	public function required($name, $data = null)
	{
		if (null === $data)
		{
			return isset($this->data[$name]);
		}
		else
		{
			return isset($data[$name]);
		}
	}
	/**
	 * 验证数据
	 * @param mixed $value
	 * @param string $type
	 */
	public static function check($value, $type)
	{
		if(null === self::$ref)
		{
			self::$ref = new ReflectionClass(get_called_class());
		}
		$args = func_get_args();
		unset($args[1]);
		if (isset(self::$checkTypes[$type]))
		{
			// 映射函数
			return call_user_func_array(self::$checkTypes[$type], $args);
		}
		else if (self::$ref->hasMethod($type) && self::$ref->getMethod($type)->isStatic())
		{
			// 验证类内部判断方法
			return call_user_func_array(array ('self',$type), $args);
		}
		else if (function_exists($type))
		{
			// 函数
			return call_user_func_array($type, $args);
		}
		else
		{
			// 无匹配的验证方法，返回false
			return false;
		}
	}
	
	/**
	 * 正则验证
	 * @param mixed $value        	
	 * @param string $rule        	
	 * @return boolean
	 */
	public static function regex($value, $rule)
	{
		return preg_match($rule, $value) > 0;
	}
	
	/**
	 * 判断文本长度，以字节为单位
	 * @param string $val        	
	 * @param int $min        	
	 * @param int $max        	
	 * @return boolean
	 */
	public static function length($val, $min, $max=null)
	{
		return isset($val[$min-1]) && (null===$max || !isset($val[$max]));
	}
	
	/**
	 * 判断文本长度，以字符为单位
	 * @param string $val
	 * @param int $min
	 * @param int $max
	 * @return boolean
	 */
	public static function lengthChar($val, $min, $max=null)
	{
		$len = mb_strlen($val,'utf8');
		$result = ($len >= $min);
		if($max!==null)
		{
			$result = ($result && $len <= $max);
		}
		return $result;
	}
	/**
	 * 判断空文本
	 * @param string $str        	
	 * @return boolean
	 */
	public static function empty_str($str)
	{
		return '' === $str;
	}
	
	/**
	 * 判断不为空文本
	 * @param string $str        	
	 * @return boolean
	 */
	public static function not_empty_str($str)
	{
		return '' !== $str;
	}
	
	/**
	 * 检测邮箱格式
	 * @access public static
	 * @param $email
	 * @return bool
	 */
	public static function email($email)
	{
		$atIndex = strrpos($email, '@');
		if (false === $atIndex)
		{
			return false;
		}
		else
		{
			$domain = substr($email, $atIndex + 1);
			$local = substr($email, 0, $atIndex);
			if (! strpos($domain, '.'))
			{
				return false;
			}
			else
			{
				$localLen = strlen($local);
				$domainLen = strlen($domain);
				if ($localLen < 1 || $localLen > 64)
				{
					// 本地部分长度超过
					return false;
				}
				else if ($domainLen < 1 || $domainLen > 255)
				{
					// 超出域部分长度
					return false;
				}
				else if ('.' === $local[0] || '.' === $local[$localLen - 1])
				{
					// 本地部分开始或结尾'.'
					return false;
				}
				else if (preg_match('/\\.\\./', $local))
				{
					// 本地部分已经连续两个点
					return false;
				}
				else if (! preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain))
				{
					// 字符不是有效的域部分
					return false;
				}
				else if (preg_match('/\\.\\./', $domain))
				{
					// 域部分已经连续两个点
					return false;
				}
				else if (! preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace('\\\\', '', $local)))
				{
					// 除非本地部分是引用字符不是有效的本地部分
					if (! preg_match('/^"(\\\\"|[^"])+"$/', str_replace('\\\\', '', $local)))
					{
						return false;
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * 检测中国手机号码格式
	 * @access public static
	 * @param string $str
	 * @return bool
	 */
	public static function mobile($str)
	{
		return is_numeric($str) && $str[0] > 0 && 11 === strlen($str);
	}
	
	/**
	 * 检测中国电话号码格式，支持400、800等
	 * @access public static
	 * @param string $str
	 * @return bool
	 */
	public static function tel($str)
	{
		$rule = array ('/^(\d{3,4}-)?(\d{7,8}){1}(-\d{2,4})?$/',		// 普通电话
		'/^(\d{3,4}-)?(\d{3,4}){1}(-\d{3,4})?$/');
		foreach ($rule as $t)
		{
			if (1 === preg_match($t, $str))
			{
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 检测是否符合中国固话或手机格式，支持400、800等
	 * @param string $str        	
	 * @return string
	 */
	public static function phone($str)
	{
		return self::mobile($str) || self::tel($str);
	}
	
	/**
	 * 检测中国邮政编码
	 * @access public static
	 * @param string $str
	 * @return bool
	 */
	public static function postcode($str)
	{
		return is_numeric($str) && $str[0] > 0 && 6 === strlen($str);
	}
	
	/**
	 * 检测URL地址
	 * @access public static
	 * @param string $str
	 * @return bool
	 */
	public static function url($str)
	{
		return preg_match('/^([a-z]*:\/\/)?(localhost|(([A-Z0-9][A-Z0-9_-]*(?:\.[A-Z0-9][A-Z0-9_-]*)+):?(\d+)?))\.?\/?/i', $str) > 0;
	}
	
	/**
	 * 检测QQ号是否符合规则
	 * @access public static
	 * @param string $str
	 * @return bool
	 */
	public static function qq($str)
	{
		return preg_match('/^[1-9]{1}[0-9]{4,10}$/', $str) > 0;
	}
	
	/**
	 * 判断IP地址是否符合IP的格式，ipv4或ipv6
	 * @param string $str        	
	 * @return boolean
	 */
	public static function ip($str)
	{
		return self::ipv4($str) || self::ipv6($str);
	}
	
	/**
	 * 判断IP地址是否是合法的ipv4格式
	 * @param string $str        	
	 * @return boolean
	 */
	public static function ipv4($str)
	{
		return preg_match('/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/', $str) > 0;
	}
	
	/**
	 * 判断IP地址是否是合法的ipv6格式
	 * @param string $str
	 * @return boolean
	 */
	public static function ipv6($str)
	{
		return preg_match('/\A 
(?: 
(?: 
(?:[a-f0-9]{1,4}:){6} 
| 
::(?:[a-f0-9]{1,4}:){5} 
| 
(?:[a-f0-9]{1,4})?::(?:[a-f0-9]{1,4}:){4} 
| 
(?:(?:[a-f0-9]{1,4}:){0,1}[a-f0-9]{1,4})?::(?:[a-f0-9]{1,4}:){3} 
| 
(?:(?:[a-f0-9]{1,4}:){0,2}[a-f0-9]{1,4})?::(?:[a-f0-9]{1,4}:){2} 
| 
(?:(?:[a-f0-9]{1,4}:){0,3}[a-f0-9]{1,4})?::[a-f0-9]{1,4}: 
| 
(?:(?:[a-f0-9]{1,4}:){0,4}[a-f0-9]{1,4})?:: 
) 
(?: 
[a-f0-9]{1,4}:[a-f0-9]{1,4} 
| 
(?:(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])\.){3} 
(?:[0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5]) 
) 
| 
(?: 
(?:(?:[a-f0-9]{1,4}:){0,5}[a-f0-9]{1,4})?::[a-f0-9]{1,4} 
| 
(?:(?:[a-f0-9]{1,4}:){0,6}[a-f0-9]{1,4})?:: 
) 
)\Z/ix', $str) > 0;
	}
	
	/**
	 * 在两个数之间，不包含这2个数字
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function between($value, $min, $max)
	{
		return $value > $min && $value < $max;
	}
	
	/**
	 * 在两个数之间，包含这2个数字
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function betweenEqual($value, $min, $max)
	{
		return $value >= $min && $value <= $max;
	}

	/**
	 * 小于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function lt($value, $num)
	{
		return $value < $num;
	}
	
	/**
	 * 小于等于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function ltEqual($value, $num)
	{
		return $value <= $num;
	}
	
	/**
	 * 大于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function gt($value, $num)
	{
		return $value > $num;
	}
	
	/**
	 * 大于等于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function gtEqual($value, $num)
	{
		return $value >= $num;
	}
	
	/**
	 * 等于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function equal($value, $num)
	{
		return $value == $num;
	}
	
	/**
	 * 不等于
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function unequal($value, $num)
	{
		return $value != $num;
	}
	
	/**
	 * 值在范围内
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function in($value, $nums)
	{
		if (! is_array($nums))
		{
			$nums = explode(',', $nums);
		}
		return in_array($value, $nums);
	}
	
	/**
	 * 值不在范围内
	 * @param numeric $value        	
	 * @param array $param        	
	 * @return boolean
	 */
	public static function notin($value, $nums)
	{
		if (! is_array($nums))
		{
			$nums = explode(',', $nums);
		}
		return ! in_array($value, $nums);
	}
	
	/**
	 * 检测中国居民身份证，支持15位和18位
	 * @access public static
	 * @param string $id_card
	 * @return bool
	 */
	public static function idcard($id_card)
	{
		/**
		 * 计算身份证校验码，根据国家标准GB 11643-1999
		 * @access protected static
		 * @param string $idcard_base
		 * @return int
		 */
		$idcard_verify_number = function () use(&$id_card)
		{
			if (17 !== strlen($id_card))
			{
				return false;
			}
			// 加权因子
			$factor = array (7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
			// 校验码对应值
			$verify_number_list = array ('1','0','X','9','8','7','6','5','4','3','2');
			$checksum = 0;
			$len = strlen($id_card);
			for ($i = 0; $i < $len; ++ $i)
			{
				$checksum += substr($id_card, $i, 1) * $factor[$i];
			}
			$mod = $checksum % 11;
			$verify_number = $verify_number_list[$mod];
			return $verify_number;
		};
		/**
		 * 18位身份证校验码有效性检查
		 * @access protected static
		 * @param string $idcard
		 * @return bool
		 */
		$idcard_checksum18 = function () use(&$id_card, $idcard_verify_number)
		{
			if (18 !== strlen($id_card))
			{
				return false;
			}
			$id_card1 = $id_card;
			$id_card = substr($id_card, 0, 17);
			if ($idcard_verify_number() !== strtoupper(substr($id_card1, 17, 1)))
			{
				return false;
			}
			else
			{
				return true;
			}
		};
		/**
		 * 将15位身份证升级到18位
		 * @access protected static
		 * @param string $idcard
		 * @return string
		 */
		$idcard_15to18 = function () use(&$id_card, $idcard_verify_number)
		{
			if (15 !== strlen($id_card))
			{
				return false;
			}
			else
			{
				// 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
				if (false !== array_search(substr($id_card, 12, 3), array ('996','997','998','999')))
				{
					$id_card = substr($id_card, 0, 6) . '18' . substr($id_card, 6, 9);
				}
				else
				{
					$id_card = substr($id_card, 0, 6) . '19' . substr($id_card, 6, 9);
				}
			}
			$id_card = $id_card . $idcard_verify_number();
			return $id_card;
		};
		$len = strlen($id_card);
		if (18 === $len)
		{
			return $idcard_checksum18();
		}
		else if (15 === $len)
		{
			$id_card = $idcard_15to18();
			return $idcard_checksum18();
		}
		else
		{
			return false;
		}
	}
}