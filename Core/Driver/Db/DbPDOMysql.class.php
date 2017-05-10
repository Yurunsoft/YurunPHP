<?php
class DbPDOMysql extends DbPDOBase
{
	/**
	 * 参数标识
	 * @var array
	 */
	public $paramFlag = array ('`','`');

	/**
	 * 字段类型和PDO类型关联
	 * @var array
	 */
	public $paramType = array(
		'int'			=>	PDO::PARAM_INT,
		'smallint'		=>	PDO::PARAM_INT,
		'tinyint'		=>	PDO::PARAM_INT,
		'mediumint'		=>	PDO::PARAM_INT,
		'bigint'		=>	PDO::PARAM_INT,
		'bit'			=>	PDO::PARAM_BOOL,
		'year'			=>	PDO::PARAM_INT,
	);

	/**
	 * 构建DNS字符串
	 * @param array $option 
	 * @return string 
	 */
	public function buildDSN($option = null)
	{
		if(null === $option)
		{
			$option = $this->option;
		}
		$charset = (isset($option['charset']) ? $option['charset'] : 'utf8');
		// php<5.3.6的版本不认charset的处理
		if(-1 === version_compare(PHP_VERSION, '5.3.6'))
		{
			$this->option['options'][PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $charset;
		}
		return 'mysql:'
				 . 'host=' . (isset($option['host']) ? $option['host'] : '127.0.0.1')
				 . ';port=' . (isset($option['port']) ? $option['port'] : '3306')
				 . ';dbname=' . (isset($option['dbname']) ? $option['dbname'] : '')
				 . ';unix_socket=' . (isset($option['unix_socket']) ? $option['unix_socket'] : '')
				 . ';charset=' . $charset
				 ;
	}
	
	/**
	 * 获取结果行数
	 * @return int
	 */
	public function foundRows()
	{
		return (int)$this->handler->query('select found_rows()')->fetchColumn();
	}

	/**
	 * 获取数据库中所有数据表名
	 * @param string $dbname
	 * @return array
	 */
	public function getTables($dbName = null)
	{
		if (empty($dbName))
		{ // 当前表
			$sql = 'show tables';
		}
		else
		{ // 其他表
			$sql = 'show tables from ' . $this->parseKeyword($dbName);
		}
		// 查询
		$result = $this->query($sql);
		if (false === $result)
		{
			// 失败
			$r = false;
			return $r;
		}
		else
		{
			$keys = array_keys($result[0]);
			$r = array ();
			// 处理数据
			foreach ($result as $value)
			{
				$r[] = $value[$keys[0]];
			}
			// 返回结果
			return $r;
		}
	}

	/**
	 * 获取数据表中所有字段详细信息
	 * @param string $table
	 * @return array
	 */
	public function getFields($table)
	{
		// 查询
		$result = $this->query('show columns from ' . $this->parseKeyword($table));
		if (false === $result)
		{
			// 失败
			$result = false;
			return $result;
		}
		else
		{
			$fields = array ();
			// 处理数据
			foreach($result as $item)
			{
				$this->parseFieldType($item['Type'], $typeName, $length, $accuracy);
				$fields[$item['Field']] = array(
					'name'			=>	$item['Field'],
					'type'			=>	$typeName,
					'length'		=>	$length,
					'accuracy'		=>	$accuracy,
					'null'			=>	'yes' === strtolower($item['Null']),
					'default'		=>	$item['Default'],
					'pk'			=>	'PRI' === $item['Key'],
					'is_auto_inc'	=>	false !== strpos($item['Extra'], 'auto_increment'),
					'key'			=>	$item['Key'],
					'extra'			=>	$item['Extra']
				);
			}
			// 返回结果
			return $fields;
		}
	}

	/**
	 * 构建SELECT语句
	 * @return string 
	 */
	public function buildSelectSQL()
	{
		$where = $this->parseCondition(isset($this->operationOption['where']) ? $this->operationOption['where'] : '');
		if ('' !== $where)
		{
			$where = 'where ' . $where . ' ';
		}
		return 'select ' . $this->parseDistinct()
				. $this->parseField(null,'*')
				. 'from '
				. $this->parseTable() . ' '
				. $this->parseJoin()
				. $where
				. $this->parseGroup()
				. $this->parseHaving()
				. $this->parseOrder()
				. $this->parseLimit()
				. $this->parseLock()
				;
	}

	/**
	 * 构建INSERT语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	public function buildInsertSQL($table = null, $data = array())
	{
		$data = $this->parseParams($data);
		if(isAssocArray($data))
		{
			$keys = array_keys($data);
			return 'insert into ' . $this->parseTable($table) . '(' . implode(',',array_map(array($this,'parseKeyword'),$keys)) . ') values(:' . implode(',:',$keys) . ')' . $this->parseLock();
		}
		else
		{
			return 'insert into ' . $this->parseTable($table) . ' values(' . substr(str_repeat('?,',count($data)),0,-1) . ')' . $this->parseLock();
		}
	}

	/**
	 * 构建UPDATE语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	public function buildUpdateSQL($table = null, $data = array())
	{
		$sql = 'update ' . $this->parseTable($table) . ' set ';
		if(is_string($data))
		{
			$sql .= $data . ' ';
		}
		else
		{
			$data = $this->parseParams($data);
			foreach($data as $key => $value)
			{
				$sql .= $this->parseKeyword($key) . "=:{$key},";
			}
		}
		$where = $this->parseCondition(isset($this->operationOption['where']) ? $this->operationOption['where'] : '');
		if ('' !== $where)
		{
			$where = 'where ' . $where . ' ';
		}
		return substr($sql,0,-1) . ' '
				. $where
				. $this->parseOrder()
				. $this->parseLimit()
				. $this->parseLock()
				;
	}

	/**
	 * buildDeleteSQL
	 * @param string $table 
	 * @return string 
	 */
	public function buildDeleteSQL($table = null)
	{
		$sql = 'delete from ' . $this->parseTable($table) . ' ';
		$where = $this->parseCondition(isset($this->operationOption['where']) ? $this->operationOption['where'] : '');
		if ('' !== $where)
		{
			$where = 'where ' . $where . ' ';
		}
		return $sql
				. $where
				. $this->parseOrder()
				. $this->parseLimit()
				. $this->parseLock()
				;
	}

	/**
	 * parseOrderByField
	 * @param mixed $order 
	 * @return string 
	 */
	public function parseOrderByField($order)
	{
		if(isset($order[0],$order[1]))
		{
			if(!is_array($order[1]))
			{
				$order[1] = explode(',', $order[1]);
			}
			$list = array();
			foreach($order[1] as $item)
			{
				$paramName = $this->getParamName();
				$this->operationOption['params'][$paramName] = $item;
				$list[] = ':' . $paramName;
			}
			return 'field(' . $this->parseKeyword($order[0]) . ',' . implode(',', $list) . ')';
		}
		else
		{
			return 1;
		}
	}

	/**
	 * parseLimit
	 * @return string 
	 */
	public function parseLimit()
	{
		if(!isset($this->operationOption['limit'],$this->operationOption['limit'][0]))
		{
			return '';
		}
		return 'limit ' . $this->operationOption['limit'][0] . (isset($this->operationOption['limit'][1]) ? (',' . $this->operationOption['limit'][1]) : '');
	}

	/**
	 * parseLock
	 * @return string
	 */
	public function parseLock()
	{
		if(isset($this->operationOption['lock']))
		{
			if('share' === $this->operationOption['lock'])
			{
				return ' LOCK IN SHARE MODE';
			}
			else if('ex' === $this->operationOption['lock'])
			{
				return ' FOR UPDATE';
			}
		}
		else
		{
			return '';
		}
	}

	/**
	 * 锁定表
	 * @param array $option 
	 * @return bool 
	 */
	public function lockTable($option = null)
	{
		if(empty($option))
		{
			return false;
		}
		else
		{
			$sql = 'lock table ';
			foreach($option as $tableName => $type)
			{
				$sql .= $tableName . ' ' . $type . ',';
			}
			return $this->execute(substr($sql, 0, -1));
		}
	}	

	/**
	 * 解锁表
	 * @param array $option 
	 * @return bool 
	 */
	public function unlockTable($option = null)
	{
		return $this->execute('unlock table');
	}
}