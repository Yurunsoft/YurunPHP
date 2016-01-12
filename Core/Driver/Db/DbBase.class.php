<?php
/**
 * 数据库驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class DbBase
{
	// 连接
	protected $conn;
	// 结果集
	protected $result;
	// 数据库连接设置
	protected $config;
	// 运算符
	protected $operators = array ();
	// 运算符
	protected $logicOperators = array ('and','or','xor','and not','or not','xor not');
	// 参数标识
	protected $param_flag = array ('','');
	// 是否连接
	protected $connect = false;
	// 最后执行的SQL语句
	protected $lastSql = '';
	// 是否启用参数标识
	public $isUseParamFlag = true;
	public function __construct($config = array())
	{
		$this->config = $config;
		$this->connect();
	}
	/**
	 * 析构函数
	 *
	 * @access public
	 */
	function __destruct()
	{
		// 释放结果集
		$this->free();
		// 断开连接
		$this->disconnect();
	}
	
	/**
	 * 处理字段名，防止因关键词出错
	 *
	 * @param mixed $param        	
	 * @return string
	 */
	public function parseArgs($param)
	{
		if (! is_array($param))
		{
			$param = explode(',', $param);
		}
		if($this->isUseParamFlag)
		{
			$result = array ();
			foreach ($param as $value)
			{
				$result[] = $this->param_flag[0].$value.$this->param_flag[1];
			}
			return implode(',', $result);
		}
		else
		{
			return implode(',', $param);
		}
	}
	
	/**
	 * 获取真实操作符
	 *
	 * @access public
	 * @param string $name        	
	 */
	public function getOperator($name)
	{
		return isset($this->operators[$name]) ? $this->operators[$name] : $name;
	}
	
	/**
	 * 字符串过滤，防止SQL注入
	 *
	 * @access public
	 * @param string $str        	
	 * @return string
	 */
	public function filterString($str)
	{
		return addslashes($str);
	}
	
	/**
	 * 值过滤，防止SQL注入
	 *
	 * @access public
	 * @param string $value        	
	 * @return string
	 */
	public function filterValue($value)
	{
		$type = gettype($value);
		if('string' === $type)
		{
			return '\'' . $this->filterString($value) . '\'';
		}
		else if('array' === $type)
		{
			return implode(',', array_map(array ($this,'filterValue'), $value));
		}
		else if('boolean' === $type)
		{
			return $value ? '1' : '0';
		}
		else if('NULL' === $type)
		{
			return 'null';
		}
		else
		{
			return $value;
		}
	}
	
	/**
	 * 获取或输出最后执行的SQL语句
	 *
	 * @param boolean $print        	
	 * @return string
	 */
	public function lastSql($print = false)
	{
		if ($print)
		{
			echo $this->lastSql;
		}
		return $this->lastSql;
	}
	
	/**
	 * 插入记录
	 *
	 * @param string $table        	
	 * @param array $data        	
	 */
	public function insert($table, $data, $return = Db::RETURN_ISOK)
	{
		$sql = 'insert into ' . $this->parseField($table) . '(' . $this->parseField(array_keys($data)) . ') values(' . $this->filterValue($data) . ')';
		$result = $this->execute($sql);
		if(Db::RETURN_ROWS === $return)
		{
			return $this->rowCount();
		}
		else if(Db::RETURN_INSERT_ID === $return)
		{
			return $this->lastInsertId();
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * 更新记录
	 *
	 * @param string $table        	
	 * @param array $data        	
	 * @param mixed $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function update($data, $option, $return = Db::RETURN_ISOK)
	{
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = ' where ' . $where;
		}
		$sql = 'update ' . $this->parseField(isset($option['from']) ? $option['from'] : '') . $this->parseUpdateSet($data) . $where . $this->parseOrder(isset($option['order']) ? $option['order'] : array ()) . $this->parseLimit(isset($option['limit']) ? $option['limit'] : '');
		$result = $this->execute($sql);
		if(Db::RETURN_ROWS === $return)
		{
			return $this->rowCount();
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * 删除记录
	 *
	 * @param string $table        	
	 * @param mixed $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function delete($option, $return = Db::RETURN_ISOK)
	{
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = ' where ' . $where;
		}
		$sql = 'delete from ' . $this->parseField(isset($option['from']) ? $option['from'] : '') . $where . $this->parseOrder(isset($option['order']) ? $option['order'] : '') . $this->parseLimit(isset($option['limit']) ? $option['limit'] : '');
		$result = $this->execute($sql);
		if(Db::RETURN_ROWS === $return)
		{
			return $this->rowCount();
		}
		else
		{
			return $result;
		}
	}
	
	/**
	 * 查询获取记录
	 *
	 * @param array $option        	
	 * @param boolean $first        	
	 * @return array
	 */
	public function &select($option, $first = false)
	{
		$sql = $this->parseSelectOption($option);
		if ($first)
		{
			return $this->query($sql);
		}
		else
		{
			$result=$this->queryA($sql);
			if(!empty($option['number']))
			{
				autoNumber($result, $option['number']);
			}
			return $result;
		}
	}
	
	/**
	 * 查询获取值
	 *
	 * @return mixed
	 */
	public function selectValue($option)
	{
		return $this->queryValue($this->parseSelectOption($option));
	}
	
	/**
	 * 解析查询规则
	 *
	 * @param array $option        	
	 * @return string
	 */
	public function parseSelectOption($option)
	{
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = ' where ' . $where;
		}
		return 'select ' . $this->parseDistinct(isset($option['distinct']) ? $option['distinct'] : '')
				. $this->parseField(isset($option['field']) ? $option['field'] : '*')
				. ' from '
				. $this->parseField(isset($option['from']) ? $option['from'] : '')
				. $this->parseJoin(isset($option['join']) ? $option['join'] : array())
				. $where . $this->parseGroup(isset($option['group']) ? $option['group'] : array ())
				. $this->parseHaving(isset($option['having']) ? $option['having'] : '')
				. $this->parseOrder(isset($option['order']) ? $option['order'] : array ())
				. $this->parseLimit(isset($option['limit']) ? $option['limit'] : '');
	}
	
	/**
	 * 查询取值
	 *
	 * @param string $sql        	
	 * @return mixed
	 */
	public function queryValue($sql,$params = array(),$isReturnParams = false)
	{
		$data = $this->query($sql,$params,$isReturnParams);
		if (isset($data[0]))
		{
			return $data[0];
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取连接
	 *
	 * @return mixed
	 */
	public function getConn()
	{
		return $this->conn;
	}
	
	/**
	 * 获取查询结果
	 *
	 * @return mixed
	 */
	public function getResult()
	{
		return $this->result;
	}
	
	/**
	 * 获取是否已连接
	 *
	 * @return boolean
	 */
	public function isConnect()
	{
		return $this->connect;
	}
	
	/**
	 * 解析distinct
	 *
	 * @param boolean $distinct        	
	 * @return string
	 */
	public function parseDistinct($distinct)
	{
		if ($distinct)
		{
			return 'distinct ';
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 解析字段规则
	 *
	 * @param mixed $field        	
	 * @return string
	 */
	public function parseField($field)
	{
		if (!is_array($field))
		{
			$field = $this->parseNameAlias($field);
			if (empty($field))
			{
				return '*';
			}
			else
			{
				return $field;
			}
		}
		$fields = array ();
		foreach ($field as $k => $value)
		{
			if(is_array($value))
			{
				foreach($value as $key=>$item)
				{
					if(is_array($item))
					{
						$fields[] = $this->parseConstValue($item);
					}
					else if (is_numeric($key))
					{
						// 无键名，无别名
						$fields[] = $this->parseNameAlias($item);
					}
					else
					{
						// 有键名，有别名
						$fields[] = $this->parseNameAlias($key, $item);
					}
				}
			}
			else if(!is_numeric($k))
			{
				// 有键名，有别名
				$fields[] = $this->parseNameAlias($k, $value);
			}
			else if(is_string($value))
			{
				$fields[] = $value;
			}
		}
		$fields = implode(',', $fields);
		if ('' === $fields)
		{
			return '*';
		}
		else
		{
			return $fields;
		}
	}
	/**
	 * 解析静态值
	 * @param array $data
	 */
	public function parseConstValue($data)
	{
		return implode(',',$data);
	}
	/**
	 * 解析条件
	 *
	 * @param array $condition
	 * @return string
	 */
	public function parseCondition($condition)
	{
		$result = '';
		// 遍历条件数组
		foreach ($condition as $item)
		{
			if(is_string($item))
			{
				if ('' !== $result)
				{ // 不是第一个条件，默认加上 and
					$result .= ' ' . $this->getOperator('and') . ' ';
				}
				$result .= $item;
				continue;
			}
			foreach($item as $key => $value)
			{
				$skey = strtolower($key);
				// 判断是否是逻辑运算符
				if (in_array($skey, $this->logicOperators))
				{
					if('' !== $result)
					{
						// 当前键名是逻辑运算符
						$result .= ' ' . $this->getOperator($skey);
					}
					$result .= ' (' . $this->parseCondition(array($value)) . ')';
				}
				else
				{
					if ('' !== $result)
					{ // 不是第一个条件，默认加上 and
						$result .= ' ' . $this->getOperator('and') . ' ';
					}
					if (is_array($value))
					{
						$s = count($value);
						// 条件解析
						if ($s > 0)
						{
							if('between' === $value[0])
							{
								if ($s >= 3)
								{
									$result .= $this->parseField($key) . ' between ' . $this->filterValue($value[1]) . ' and ' . $this->filterValue($value[2]);
								}
							}
							else if('in' === $value[0] || 'not in' === $value[0])
							{
								if ($s === 2)
								{
									if(!is_array($value[1]))
									{
										$value[1]=explode(',',$value[1]);
									}
									$result .= $this->parseField($key) . ' ' . $this->getOperator($value[0]) . '(' . $this->filterValue($value[1]) . ')';
								}
								else if ($s > 2)
								{
									$o = array_shift($value);
									$result .= $this->parseField($key) . ' ' . $this->getOperator($o) . '(' . $this->filterValue($value) . ')';
								}
							}
							else
							{
								if ($s > 0)
								{
									$result .= $this->parseField($key) . ' ' . $this->getOperator($value[0]);
									if ($s > 1)
									{
										$result .= ' ' . $this->filterValue($value[1]);
										if ($s > 2)
										{
											for ($i = 2; $i < $s; ++ $i)
											{
												$result .= ' ' . $value[$i];
											}
										}
									}
								}
							}
						}
					}
					else
					{ // 直接等于
						$result .= $this->parseField($key) . '=' . $this->filterValue($value);
					}
				}
			}
		}
		return $result;
	}
	
	/**
	 * 解析limit
	 *
	 * @param mixed $limit        	
	 * @return string
	 */
	public function parseLimit($limit)
	{
		if (! is_array($limit))
		{
			$limit = explode(',', $limit);
		}
		if (is_numeric($limit[0]))
		{
			$result = ' limit ' . $limit[0];
			if (isset($limit[1]) && is_numeric($limit[1]))
			{
				$result .= ',' . $limit[1];
			}
			return $result;
		}
		else
		{
			return '';
		}
	}
	
	/**
	 * 解析group by
	 *
	 * @param mixed $group        	
	 * @return string
	 */
	public function parseGroup($group)
	{
		if(empty($group))
		{
			return '';
		}
		if (is_string($group))
		{
			return ' group by ' . $group;
		}
		$result = '';
		foreach ($group as $key => $value)
		{
			$result .= $this->parseField($value) . ',';
		}
		if ('' === $result)
		{
			return '';
		}
		else
		{
			return ' group by ' . substr($result, 0, - 1);
		}
	}
	
	/**
	 * 解析having
	 *
	 * @param mixed $having        	
	 * @return string
	 */
	public function parseHaving($having)
	{
		$result = $this->parseCondition(array($having));
		if ('' === $result)
		{
			return $result;
		}
		else
		{
			return ' having ' . $result;
		}
	}
	
	/**
	 * 解析order by
	 *
	 * @param type $order        	
	 * @return string
	 */
	public function parseOrder($order)
	{
		if(empty($order))
		{
			return '';
		}
		if (is_string($order))
		{
			return ' order by ' . $order;
		}
		$result = '';
		foreach ($order as $key => $value)
		{
			if (is_numeric($key))
			{
				$result .= $this->parseField($value).',';
			}
			else
			{
				$result .= $this->parseField($key) . ' ' . $value . ',';
			}
		}
		if ('*,' === $result || ''===$result)
		{
			return '';
		}
		else
		{
			return ' order by ' . substr($result, 0, - 1);
		}
	}

	/**
	 * 解析join
	 * @param array $join
	 * @return string
	 */
	public function parseJoin($join)
	{
		$result='';
		$isTable=false;
		foreach($join as $key=>$item)
		{
			if($isTable)
			{
				$result.=' on '.$this->parseCondition(array($item));
				$isTable=false;
			}
			else if(is_string($item))
			{
				$result .= ' ' . $item;
			}
			else if(isset($item['type'],$item['table'],$item['on']))
			{
				$result .= ' ' .$item['type'] . ' join ';
				if(is_array($item['table']))
				{
					$result .= $this->parseField($item['table']);
				}
				else
				{
					$result .= $item['table'];
				}
				$result .= ' on ' . $this->parseCondition(array($item['on']));
			}
			else
			{
				$result.=' join '.$this->parseField($item);
				$isTable=true;
			}
		}
		return $result;
	}

	/**
	 * 解析update set
	 *
	 * @param array $data        	
	 * @return string
	 */
	public function parseUpdateSet($data)
	{
		$sql = ' set ';
		if (is_array($data))
		{
			foreach ($data as $key => $value)
			{
				if (is_numeric($key))
				{
					$sql .= $value . ',';
				}
				else
				{
					$sql .= $this->parseField($key) . '=' . $this->filterValue($value) . ',';
				}
			}
		}
		else
		{
			$sql .= $data . ',';
		}
		return substr($sql, 0, - 1);
	}
	
	/**
	 * 解析名称和别名
	 *
	 * @param string $name        	
	 * @param string $alias        	
	 * @return string
	 */
	private function parseNameAlias($name, $alias = '')
	{
		if ('*' === $name)
		{
			return $name;
		}
		else
		{
			if (false !== strpos($name, '(') && false !== strpos($name, ')'))
			{
				// 字段带函数
				$result = $name;
			}
			else
			{
				$result = '';
				$last = '';
				$arr = explode('.', $name);
				foreach ($arr as $value)
				{
					if('' === $value)
					{
						// 处理出现名称中出现.的情况
						$last .= '.';
					}
					else if('*' === $value)
					{
						$result .= $last.'*.';
						$last = '';
					}
					else 
					{
						$result .= $this->parseArgs($last . $value) . '.';
						$last = '';
					}
				}
				$result = substr($result, 0, - 1);
			}
			if ('' !== $alias)
			{
				$result = $result . ' as ' . $this->parseArgs($alias);
			}
			return $result;
		}
	}
	/**
	 * 导入sql文件，成功返回true，失败返回false
	 * @param string $file
	 * @return bool
	 */
	public function importSql($file)
	{
		$_this=$this;
		$this->parseMultiSql($file,function($sql)use($_this){
			if(!$_this->execute($sql))
			{
				return false;
			}
		});
		return true;
	}
	public function getType()
	{
		return substr(get_called_class(),2);
	}
	/**
	 * 解析sql文件，支持返回sql数组，或者使用回调函数
	 * @param string $file
	 * @param callback $callback
	 * @return mixed
	 */
	abstract public function &parseMultiSql($file,$callback=null);
	/**
	 * 查询一条记录
	 */
	abstract public function &query($sql);
	
	/**
	 * 查询多条记录
	 */
	abstract public function &queryA($sql);
	
	/**
	 * 执行SQL语句
	 */
	abstract public function execute($sql);
	
	/**
	 * 连接数据库
	 */
	abstract public function connect($config);
	
	/**
	 * 断开数据库连接
	 */
	abstract public function disConnect();
	
	/**
	 * 释放结果集
	 */
	abstract public function free();
	
	/**
	 * 选择数据库
	 */
	abstract public function selectDb($dbName);
	
	/**
	 * 获取结果行数
	 */
	abstract public function foundRows();
	/**
	 * 获取影响行数
	 */
	abstract public function rowCount();
	/**
	 * 最后插入的自动编号ID
	 */
	abstract public function lastInsertID();
	/**
	 * 获取最后一条错误信息
	 */
	abstract public function getError();
	/**
	 * 获取数据库中所有数据表名
	 *
	 * @param string $dbname        	
	 */
	abstract public function &getTables($dbName = null);
	/**
	 * 获取数据表中所有字段详细信息
	 *
	 * @param string $table        	
	 */
	abstract public function &getFields($table);
}