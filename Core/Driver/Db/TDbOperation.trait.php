<?php
trait TDbOperation
{
	/**
	 * 随机参数序号
	 * @var array
	 */
	public static $randomParamNum = 0;

	public $bindValues = array();

	/**
	 * 准备一个查询
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function prepareQuery($sql, $params = array())
	{
		// 如果参数为空则取链式操作传入的参数
		$params = $this->parseParams($params);
		// 记录sql语句和参数
		$this->lastSql = $sql;
		$this->lastSqlParams = $params;
		$beginTime = microtime(true);
		if(empty($params) && empty($this->bindValues))
		{
			// 没有参数的查询
			$result = $this->lastStmt = $this->handler->query($sql);
		}
		else
		{
			// 参数查询
			$result = $this->lastStmt = $this->handler->prepare($sql);
			if($result)
			{
				// 自动绑定的参数
				foreach($params as $key => $value)
				{
					if(is_int($key))
					{
						// 问号形式的参数
						$paramName = ($key) + 1;
					}
					else
					{
						// 名称形式的参数
						$paramName = ':' . $key;
					}
					// 绑定参数
					$this->lastStmt->bindValue($paramName, $value, $this->geetParamTypeByValue($value));
				}
				// 手动绑定的参数
				foreach($this->bindValues as $bindValue)
				{
					$this->lastStmt->bindValue($bindValue[0], $bindValue[1], $bindValue[2]);
				}
				// 执行
				$result = $this->lastStmt->execute();
			}
		}
		Event::trigger('ON_DB_QUERY', array(
			'handler'	=>	$this,
			'time'		=>	microtime(true) - $beginTime,
			'sql'		=>	$sql,
		));
		if(false === $result)
		{
			$GLOBALS['debug']['lastsql'] = $sql;
			throw new Exception($this->getError());
		}
		// 链式操作清空
		$this->operationOption = array();
		$this->randomParamNum = 0;
		$this->bindValues = array();
		return $this->lastStmt;
	}

	/**
	 * 执行一个SQL语句，返回是否执行成功
	 * @param string $sql 
	 * @param array $params 
	 * @return bool 
	 */
	public function execute($sql,$params = array())
	{
		return false !== $this->prepareQuery($sql,$params);
	}

	/**
	 * 查询多条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function query($sql = null,$params = array())
	{
		if(!isset($this->operationOption['params']))
		{
			$this->operationOption['params'] = array();
		}
		$this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params);
		if(false === $this->lastStmt)
		{
			return false;
		}
		else
		{
			return $this->lastStmt->fetchAll(PDO::FETCH_ASSOC);
		}
	}

	/**
	 * 查询一列数据
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function queryColumn($sql = null,$params = array())
	{
		if(!isset($this->operationOption['params']))
		{
			$this->operationOption['params'] = array();
		}
		$this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params);
		if(false === $this->lastStmt)
		{
			return false;
		}
		else
		{
			return $this->lastStmt->fetchAll(PDO::FETCH_COLUMN);
		}
	}

	/**
	 * 查询一条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getOne($sql = null,$params = array())
	{
		if(!isset($this->operationOption['params']))
		{
			$this->operationOption['params'] = array();
		}
		$this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params);
		if(false === $this->lastStmt)
		{
			return false;
		}
		else
		{
			return $this->lastStmt->fetch(PDO::FETCH_ASSOC);
		}
	}

	/**
	 * 执行SQL语句，返回第一行第一列
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getScalar($sql = null,$params = array())
	{
		if(!isset($this->operationOption['params']))
		{
			$this->operationOption['params'] = array();
		}
		$this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params);
		if(false === $this->lastStmt)
		{
			return false;
		}
		else
		{
			return $this->lastStmt->fetchColumn();
		}
	}

	/**
	 * 结尾方法自定义处理
	 * @param array $arguments 
	 */
	protected function __linkLast($arguments,$operation)
	{
		if (isset($arguments[0]))
		{
			$field = $arguments[0];
		}
		else
		{
			$field = '*';
		}
		$this->operationOption['field'] = array($operation . '(' . $field . ')');
		return $this->getScalar();
	}

	/**
	 * 插入数据
	 * @return mixed 
	 */
	public function insert()
	{
		return $this->parseIUD(func_get_args(),'Insert');
	}

	/**
	 * 批量插入数据
	 * @return mixed 
	 */
	public function insertBatch()
	{
		return $this->parseIUD(func_get_args(),'InsertBatch');
	}

	/**
	 * 更新数据
	 * @return mixed 
	 */
	public function update()
	{
		return $this->parseIUD(func_get_args(),'Update');
	}

	/**
	 * 删除数据
	 * @return mixed 
	 */
	public function delete()
	{
		return $this->parseIUD(func_get_args(),'Delete');
	}

	/**
	 * 处理Insert、InsertBatch、Update、Delete
	 * @param mixed $args 
	 * @param mixed $operation 
	 * @return mixed 
	 */
	protected function parseIUD($args,$operation)
	{
		if(isset($args[2]))
		{
			// 三个参数齐全
			list($table,$data,$return) = $args;
		}
		else if(isset($args[1]))
		{
			// 数据+返回值
			list($data,$return) = $args;
		}
		else if(isset($args[0]))
		{
			// 一个参数的情况
			if(is_array($args[0]))
			{
				// 数据
				$data = $args[0];
			}
			else
			{
				// 表名
				$table = $args[0];
			}
			$return = Db::RETURN_ISOK;
		}
		$result = $this->execute($this->{'build' . $operation . 'SQL'}($table, $data));
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
	 * 绑定参数
	 * @param mixed $parameter 
	 * @param mixed $value 
	 * @param int $data_type 
	 */
	public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
	{
		$this->bindValues[$parameter] = array($parameter, $value, $data_type);
	}

	/**
	 * 获取表名，如果为null则获取链式操作设置的表名
	 * @param mixed $table 
	 * @return string 
	 */
	protected function parseTable($table = null)
	{
		if(null === $table)
		{
			if(isset($this->operationOption['table']))
			{
				$table = $this->operationOption['table'];
			}
			else
			{
				return '';
			}
		}
		if(is_array($table))
		{
			$result = '';
			foreach($table as $tTable)
			{
				$result .= $this->parseKeyword($tTable) . ',';
			}
			return isset($result[1]) ? substr($result,0,-1) : '';
		}
		else
		{
			return $this->parseKeyword($table);
		}
	}

	/**
	 * 获取参数，如果为空则获取链式操作设置的参数
	 * @param mixed $params 
	 * @return array 
	 */
	protected function parseParams($params = array())
	{
		if(!empty($params))
		{
			$this->operationOption['params'] = $params;
		}
		return $this->operationOption['params'];
	}

	/**
	 * parseDistinct
	 * @return string
	 */
	public function parseDistinct()
	{
		if(isset($this->operationOption['distinct']) && $this->operationOption['distinct'])
		{
			return 'distinct ';
		}
		else
		{
			return '';
		}
	}

	/**
	 * parseField
	 * @param mixed $field 
	 * @param string $default 
	 * @return string
	 */
	public function parseField($field = null,$default = '')
	{
		if(null === $field)
		{
			if(isset($this->operationOption['field']))
			{
				$field = $this->operationOption['field'];
			}
			else
			{
				return $default . ' ';
			}
		}
		$result = '';
		foreach($field as $tField)
		{
			if(is_array($tField))
			{
				foreach($tField as $key => $value)
				{
					if(is_numeric($key))
					{
						$result .= $this->parseKeyword($value) . ',';
					}
					else
					{
						$result .= $this->parseKeyword($key) . ' as ' . $this->parseKeyword($value) . ',';
					}
				}
			}
			else
			{
				$result .= $tField . ',';
			}
		}
		return isset($result[1]) ? (substr($result,0,-1) . ' ') : '';
	}

	/**
	 * parseJoin
	 * @return string
	 */
	public function parseJoin()
	{
		if(!isset($this->operationOption['join']))
		{
			return '';
		}
		$result = '';
		foreach($this->operationOption['join'] as $join)
		{
			// 不符合参数数量直接跳过
			if(!isset($join[1]))
			{
				continue;
			}
			$result .= $join[0] . ' join ' . $this->parseKeyword($join[1]);
			if(isset($join[2]))
			{
				$result .= ' on ';
			}
			if(is_array($join[2]))
			{
				// 使用数组传参，支持on和where
				$hasOn = false;
				if(isset($join[2]['on']))
				{
					if(isset($join[2]['on'][2]))
					{
						$hasOn = true;
						$result .= $this->parseKeyword($join[2]['on'][0]) . $this->getOperator($join[2]['on'][1]) . $this->parseKeyword($join[2]['on'][2]);
					}
				}
				if(isset($join[2]['where']))
				{
					if($hasOn)
					{
						$result .= ' and ';
					}
					$result .= $this->parseCondition(array($join[2]['where']));
				}
			}
			else
			{
				// 只有on
				$result .= $join[2];
			}
			$result .= ' ';
		}
		return $result;
	}

	/**
	 * parseCondition
	 * @param array $condition 
	 * @return string 
	 */
	protected function parseCondition($condition)
	{
		$result = '';
		// 遍历条件数组
		foreach ($condition as $item)
		{
			if(is_string($item))
			{
				if ('' !== $result)
				{
					// 不是第一个条件，默认加上 and
					$result .= ' ' . $this->getOperator('and') . ' ';
				}
				$result .= $item;
				continue;
			}
			if(isset($item['__str'], $item['__values']))
			{
				// 参数化绑定
				if ('' !== $result)
				{
					// 不是第一个条件，默认加上 and
					$result .= ' ' . $this->getOperator('and') . ' ';
				}
				$count = 0;
				$result .= '(' . preg_replace_callback('/(((%)([bdsl]))|((:)([a-zA-Z0-9_-]+)))/', function($match)use($item, &$count){
					$name = array_pop($match);
					$prefix = array_pop($match);
					switch($prefix)
					{
						case '%':
							$paramName = ':' . $this->getParamName();
							$this->bindValue($paramName, $item['__values'][$count++], $this->getPDOParamType($name));
							return $paramName;
							break;
						case ':':
							$this->bindValue($match[0], $item['__values'][$count++]);
							return $match[0];
							break;
					}
				}, $item['__str']) . ')';
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
					{
						// 不是第一个条件，默认加上 and
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
									$betweenParamName = $this->getParamName();
									$this->operationOption['params'][$betweenParamName] = $value[1];
									$endParamName = $this->getParamName();
									$this->operationOption['params'][$betweenParamName] = $value[2];
									$result .= $this->parseKeyword($key) . ' between :' . $betweenParamName . ' and :' . $endParamName;
								}
							}
							else if('in' === $value[0] || 'not in' === $value[0])
							{
								if ($s === 2)
								{
									$operationName = $value[0];
									if(is_array($value[1]))
									{
										$listData = $value[1];
									}
									else
									{
										$value[1] = explode(',', $value[1]);
									}
								}
								else if ($s > 2)
								{
									$operationName = array_shift($value);
									$listData = $value;
								}
								$list = array();
								foreach($listData as $item)
								{
									$paramName = $this->getParamName();
									$list[] = ':' . $paramName;
									$this->operationOption['params'][$paramName] = $item;
								}
								$result .= $this->parseKeyword($key) . ' ' . $this->getOperator($operationName) . '(' . implode(',', $list) . ')';
							}
							else
							{
								if ($s > 0)
								{
									$result .= $this->parseKeyword($key) . ' ' . $this->getOperator($value[0]);
									for ($i = 1; $i < $s; ++$i)
									{
										$paramName = $this->getParamName();
										$result .= ' :' . $paramName;
										$this->operationOption['params'][$paramName] = $value[$i];
									}
								}
							}
						}
					}
					else if(is_numeric($key))
					{
						$result .= $value;
					}
					else
					{
						// 直接等于
						$paramName = $this->getParamName();
						$this->operationOption['params'][$paramName] = $value;
						$result .= $this->parseKeyword($key) . '=:' . $paramName;
					}
				}
			}
		}
		return $result;
	}

	/**
	 * parseOrder
	 * @return string 
	 */
	protected function parseOrder()
	{
		if(!isset($this->operationOption['order']))
		{
			return '';
		}
		$result = '';
		foreach($this->operationOption['order'] as $order)
		{
			switch(isset($order['type']) ? $order['type'] : '')
			{
				case 'field':
					$result .= $this->parseOrderByField($order) . ',';
					break;
				default:
					if(is_array($order))
					{
						foreach($order as $key => $value)
						{
							if(is_numeric($key))
							{
								$result .= $value . ',';
							}
							else
							{
								$result .= $this->parseKeyword($key) . ' ' . $value . ',';
							}
						}
					}
					else
					{
						$result .= $order . ',';
					}
					break;
			}
		}
		if ('' === $result)
		{
			return '';
		}
		else
		{
			return 'order by ' . substr($result, 0, - 1) . ' ';
		}
	}

	/**
	 * parseGroup
	 * @return string 
	 */
	protected function parseGroup()
	{
		if(!isset($this->operationOption['group']))
		{
			return '';
		}
		$result = '';
		foreach($this->operationOption['group'] as $group)
		{
			$result .= $this->parseKeyword($group) . ',';
		}
		if ('' === $result)
		{
			return '';
		}
		else
		{
			return 'group by ' . substr($result, 0, - 1) . ' ';
		}
	}

	/**
	 * parseHaving
	 * @return string 
	 */
	protected function parseHaving()
	{
		if(!isset($this->operationOption['having']))
		{
			return '';
		}
		$result = $this->parseCondition($this->operationOption['having']);
		if ('' === $result)
		{
			return '';
		}
		else
		{
			return 'having ' . $result . ' ';
		}
	}

	/**
	 * parseOrderByField
	 * @param mixed $order 
	 * @return string 
	 */
	abstract public function parseOrderByField($order);

	/**
	 * parseLock
	 * @return string
	 */
	abstract public function parseLock();
	/**
	 * 构建SELECT语句
	 * @return string 
	 */
	abstract public function buildSelectSQL();

	/**
	 * 构建INSERT语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	abstract public function buildInsertSQL($table = null, $data = array());

	/**
	 * 构建批量INSERT语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	abstract public function buildInsertBatchSQL($table = null, $data = array());

	/**
	 * 构建UPDATE语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	abstract public function buildUpdateSQL($table = null, $data = array());

	/**
	 * 构建DELETE语句
	 * @param string $table 
	 * @return string 
	 */
	abstract public function buildDeleteSQL($table = null);

	/**
	 * 获得参数名
	 * @return string 
	 */
	protected function getParamName()
	{
		return 'p' . dechex(++$this->randomParamNum);
	}

	/**
	 * 获取PDO参数类型
	 * @param string $type
	 * @return int
	 */
	public function getPDOParamType($type)
	{
		switch($type)
		{
			case 'b':
				return PDO::PARAM_BOOL;
			case 'd':
				return PDO::PARAM_INT;
			case 'l':
				return PDO::PARAM_LOB;
			default:
				return PDO::PARAM_STR;
		}
	}
}