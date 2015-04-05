<?php
/**
 * 数组数据基类
 * @author Yurun <admin@yurunsoft.com>
 */
class ArrayData
{
	// 数据
	protected $data = array ();
	/**
	 * 设置数据
	 *
	 * @param string $name        	
	 * @param mixed $value        	
	 */
	public function set($name, $value = null)
	{
		if (is_array($name))
		{
			// 如果传入数组就合并当前数据
			$this->data = multimerge($this->data,$name);
		}
		else
		{
			// 设置数据
			$this->data[$name] = $value;
		}
		return true;
	}
	

	/**
	 * 设置数据
	 *
	 * @param type $name        	
	 * @param type $value        	
	 * @return boolean
	 */
	public function setVal($name, $value = null)
	{
		if (is_string($name))
		{
			$name = explode('.', $name);
		}
		else if (! is_array($name))
		{
			return false;
		}
		$last = array_pop($name);
		$data = &$this->data;
		foreach ($name as $val)
		{
			if (! isset($data[$val]))
			{
				$data[$val] = array ();
			}
			$data = &$data[$val];
		}
		$data[$last] = $value;
		return true;
	}
	/**
	 * 获取数据
	 *
	 * @param string $name        	
	 * @param mixed $default        	
	 * @return mixed
	 */
	public function get($name = null, $default = false)
	{
		if (empty($name))
		{
			return $this->data;
		}
		if (is_string($name))
		{
			$name = explode('.', $name);
		}
		else if (! is_array($name))
		{
			return $default;
		}
		$result = $this->data;
		foreach ($name as $value)
		{
			if (is_array($result))
			{
				// 数组
				if (isset($result[$value]))
				{
					$result = $result[$value];
				}
				else
				{
					return $default;
				}
			}
			else if (is_object($result))
			{
				// 对象
				if (property_exists($result, $value))
				{
					$result = $result->$value;
				}
				else
				{
					return $default;
				}
			}
			else
			{
				return $default;
			}
		}
		if (count($name) > 0)
		{
			return $result;
		}
		else
		{
			return $default;
		}
	}
	/**
	 * 删除数据
	 *
	 * @param name $name        	
	 */
	public function remove($name)
	{
		if(!is_array($name))
		{
			$name=func_get_args();
		}
		foreach($name as $val)
		{
			if (is_string($val))
			{
				$val = explode('.', $val);
			}
			else if (!is_array($val))
			{
				return false;
			}
			$last = array_pop($val);
			$result = &$this->data;
			foreach ($val as $value)
			{
				if (isset($result[$value]))
				{
					$result = &$result[$value];
				}
			}
			unset($result[$last]);
		}
		return true;
	}
	/**
	 * 清空数据
	 */
	public function clear()
	{
		$this->data = array ();
	}
	/**
	 * 获取数据的数量
	 *
	 * @return int
	 */
	public function length()
	{
		return count($this->data);
	}
	/**
	 * 键名对应的值是否存在
	 *
	 * @param string $name        	
	 * @return boolean
	 */
	public function exists($name)
	{
		return isset($this->data[$name]);
	}
}