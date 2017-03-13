<?php
trait TDbOperation
{
	/**
	 * 链式操作数据
	 * @var array
	 */
	public $operationOption = array();

	/**
	 * 链式操作列表
	 * @var array
	 */
	public static $operations = array(
		'distinct'		=>	'',
		'field'			=>	'',
		'from'			=>	'table',
		'table'			=>	'',
		'where'			=>	'',
		'group'			=>	'',
		'having'		=>	'',
		'order'			=>	'',
		'orderBy'		=>	'order',
		'orderByField'	=>	'',
		'limit'			=>	'',
		'join'			=>	'',
		'page'			=>	'',
		'headTotal'		=>	'',
		'footTotal'		=>	''
	);

	/**
	 * 只存一次的链式操作列表，后面的操作会覆盖之前的
	 * @var array
	 */
	public static $onlyOneOperations = array('distinct','limit','page');

	/**
	 * 自定义操作列表
	 * @var mixed
	 */
	public static $customOperations = array();

	/**
	 * 魔术方法，实现链式操作
	 * @param string $name        	
	 * @param array $arguments        	
	 * @return Model
	 */
	public function __call($name, $arguments)
	{
		if(isset(self::$operations[$name]))
		{
			// 支持链式操作
			if(in_array($name,self::$onlyOneOperations))
			{
				// 只存一次
				$this->operationOption[$name] = $arguments;
			}
			else
			{
				// 允许存多次
				if(in_array($name,self::$customOperations))
				{
					// 自定义操作
					$this->{'__link' . ucfirst($name)}($arguments);
				}
				else
				{
					// 默认操作
					if(!isset($this->operationOption[$name]))
					{
						$this->operationOption[$name] = array();
					}
					$this->operationOption[$name][] = $arguments;
				}
			}
			return $this;
		}
	}

	/**
	 * 准备一个查询
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function prepareQuery($sql, $params = array())
	{
		$this->lastSql = $sql;
		$this->lastSqlParams = $params;
		if(empty($params))
		{
			// 没有参数的查询
			$this->lastStmt = $this->handler->query($sql);
		}
		else
		{
			// 参数查询
			$this->lastStmt = $this->handler->prepare($sql);
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
				$this->lastStmt->bindValue($paramName, $value);
			}
			// 执行
			$this->lastStmt->execute();
		}
		return $this->lastStmt;
	}

	/**
	 * 执行一个SQL语句，返回影响的行数
	 * @param string $sql 
	 * @param array $params 
	 * @return int 
	 */
	public function execute($sql,$params = array())
	{
		return $this->prepareQuery($sql,$params)->rowCount();
	}

	/**
	 * 查询多条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function query($sql = null,$params = array())
	{
		return $this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params)->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * 查询一列数据
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function queryColumn($sql = null,$params = array())
	{
		return $this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params)->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * 查询一条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getOne($sql = null,$params = array())
	{
		return $this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params)->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * 执行SQL语句，返回第一行第一列
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getScalar($sql = null,$params = array())
	{
		return $this->prepareQuery($sql ? $sql : $this->buildSelectSQL(),$params)->fetchColumn();
	}

	/**
	 * 插入数据
	 * @param string $table 
	 * @param array $data 
	 * @param int $return 
	 * @return mixed 
	 */
	public function insert($table, $data, $return = Db::RETURN_ISOK)
	{
		$sql = $this->buildInsertSQL();
		if(isAssocArray($data))
		{
			$keys = array_keys($data);
			$sql = 'insert into ' . $this->parseKeyword($table) . '(' . implode(',',array_map(array($this,'parseKeyword'),$keys)) . ') values(:' . implode(',:',$keys) . ')';
			unset($keys);
		}
		else
		{
			$sql = 'insert into ' . $this->parseKeyword($table) . ' values(' . substr(str_repeat('?,',count($data)),0,-1) . ')';
		}
		$result = $this->execute($sql,$data);
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
}