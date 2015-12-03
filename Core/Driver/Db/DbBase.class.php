<?php
/**
 * 数据库驱动基类
 * @author Yurun <admin@yurunsoft.com>
 */
abstract class DbBase
{
	const RETURN_ISOK = 0;
	const RETURN_ROWS = 1;
	const RETURN_INSERT_ID = 2;
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
		if (is_string($value))
		{
			return '\'' . $this->filterString($value) . '\'';
		}
		else if (is_array($value))
		{
			return implode(',', array_map(array ($this,'filterValue'), $value));
		}
		else if (is_bool($value))
		{
			return $value ? '1' : '0';
		}
		else if (is_null($value))
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
	public function insert($table, $data, $return = self::RETURN_ISOK)
	{
		$sql = 'insert into ' . $this->parseField($table) . '(' . $this->parseField(array_keys($data)) . ') values(' . $this->filterValue($data) . ')';
		$result = $this->execute($sql);
		switch ($return)
		{
			case self::RETURN_ROWS :
				return $this->rowCount();
				break;
			case self::RETURN_INSERT_ID :
				return $this->lastInsertId();
				break;
			default :
				return $result;
				break;
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
	public function update($data, $option, $return = self::RETURN_ISOK)
	{
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = " where {$where}";
		}
		$sql = 'update ' . $this->parseField(isset($option['from']) ? $option['from'] : '') . $this->parseUpdateSet($data) . $where . $this->parseOrder(isset($option['order']) ? $option['order'] : array ()) . $this->parseLimit(isset($option['limit']) ? $option['limit'] : '');
		$result = $this->execute($sql);
		switch ($return)
		{
			case self::RETURN_ROWS :
				return $this->rowCount();
				break;
			default :
				return $result;
				break;
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
	public function delete($option, $return = self::RETURN_ISOK)
	{
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = " where {$where}";
		}
		$sql = 'delete from ' . $this->parseField(isset($option['from']) ? $option['from'] : '') . $where . $this->parseOrder(isset($option['order']) ? $option['order'] : '') . $this->parseLimit(isset($option['limit']) ? $option['limit'] : '');
		$result = $this->execute($sql);
		switch ($return)
		{
			case self::RETURN_ROWS :
				return $this->rowCount();
				break;
			default :
				return $result;
				break;
		}
	}
	
	/**
	 * 查询获取记录
	 *
	 * @param array $option        	
	 * @param boolean $first        	
	 * @return array
	 */
	public function select($option, $first = false)
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
			$where = " where {$where}";
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
	public function queryValue($sql)
	{
		$data = $this->query($sql);
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
		if (! is_array($field))
		{
			$field = explode(',', $field);
		}
		$fields = array ();
		foreach ($field as $key => $value)
		{
			if(is_array($value))
			{
				$fields[]=$this->parseConstValue($value);
			}
			else if (is_numeric($key))
			{
				// 无键名，无别名
				$fields[] = $this->parseNameAlias($value);
			}
			else
			{
				// 有键名，有别名
				$fields[] = $this->parseNameAlias($key, $value);
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
		if (is_array($condition))
		{ // 数组条件
			$result = '';
			// 遍历条件数组
			foreach ($condition as $key => $value)
			{
				$key = strtolower($key);
				// 判断是否是逻辑运算符
				if (in_array($key, $this->logicOperators))
				{ // 当前键名是逻辑运算符
					$result .= ' ' . $this->getOperator($key) . ' (' . $this->parseCondition($value) . ')';
				}
				else
				{
					if ('' !== $result)
					{ // 不是第一个条件，默认加上 and
						$result .= ' ' . $this->getOperator('and') . ' ';
					}
					if ('_exp' === $key)
					{ // sql表达式，直接加上
						$result .= $value;
					}
					else if (is_array($value))
					{
						if (isset($value['_exp']))
						{ // 表达式，原样加上
							$result .= $this->parseField($key) . ' ' . $value['_exp'];
						}
						else
						{
							$s = count($value);
							// 条件解析
							if ($s > 0)
							{
								switch ($value[0])
								{
									case 'between' :
										if ($s >= 3)
										{
											$result .= $this->parseField($key) . ' between ' . $this->filterValue($value[1]) . ' and ' . $this->filterValue($value[2]);
										}
										break;
									case 'in' :
									case 'not in' :
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
										break;
									default :
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
										break;
								}
							}
						}
					}
					else
					{ // 直接等于
						$result .= $this->parseField($key) . "=" . $this->filterValue($value);
					}
				}
			}
			return $result;
		}
		else
		{ // 文本条件直接返回
			return $condition;
		}
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
			$result = " limit {$limit[0]}";
			if (isset($limit[1]) && is_numeric($limit[1]))
			{
				$result .= ",{$limit[1]}";
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
		$result = '';
		if (! is_array($group))
		{
			$group = explode(',', $group);
		}
		foreach ($group as $key => $value)
		{
			$result .= $this->parseField($key) . " $value,";
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
		$result = $this->parseCondition($having);
		if ('' === $result)
		{
			return $result;
		}
		else
		{
			return " having {$result}";
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
		if (! is_array($order))
		{
			$order = explode(',', $order);
		}
		$result = '';
		foreach ($order as $key => $value)
		{
			if(is_array($value))
			{
				if(isset($value['_exp']))
				{
					$result.="{$value['_exp']},";
				}
			}
			if (is_numeric($key))
			{
				$result .= $this->parseField($value).',';
			}
			else
			{
				$result .= $this->parseField($key) . " {$value},";
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
				$result.=' on '.$this->parseCondition($item);
				$isTable=false;
			}
			else if(is_string($item))
			{
				$result.=" {$item}";
			}
			else if(isset($item['type'],$item['table'],$item['on']))
			{
				$result.=" {$item['type']} join ".$this->parseField($item['table']).' on '.$this->parseCondition($item['on']);
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
					$sql .= $value;
				}
				else if (is_array($value) && isset($value['_exp']))
				{
					$sql .= "{$value['_exp']},";
				}
				else
				{
					$sql .= $this->parseField($key) . '=' . $this->filterValue($value) . ',';
				}
			}
		}
		else
		{
			$sql .= $data;
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
					if ('' === $value)
					{ // 处理出现名称中出现.的情况
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
				$result = "{$result} as " . $this->parseArgs($alias);
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
	/**
	 * 解析sql文件，支持返回sql数组，或者使用回调函数
	 * @param string $file
	 * @param callback $callback
	 * @return mixed
	 */
	abstract public function parseMultiSql($file,$callback=null);
	/**
	 * 查询一条记录
	 */
	abstract public function query($sql);
	
	/**
	 * 查询多条记录
	 */
	abstract public function queryA($sql);
	
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
	abstract public function getTables($dbName = null);
	/**
	 * 获取数据表中所有字段详细信息
	 *
	 * @param string $table        	
	 */
	abstract public function getFields($table);
}