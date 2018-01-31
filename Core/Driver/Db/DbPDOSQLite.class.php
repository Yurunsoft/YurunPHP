<?php
class DbPDOSQLite extends DbPDOBase
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
		'null'				=>	PDO::PARAM_NULL,
		'int'				=>	PDO::PARAM_INT,
		'integer'			=>	PDO::PARAM_INT,
		'tinyint'			=>	PDO::PARAM_INT,
		'smallint'			=>	PDO::PARAM_INT,
		'mediumint'			=>	PDO::PARAM_INT,
		'bigint'			=>	PDO::PARAM_INT,
		'unsigned big int'	=>	PDO::PARAM_INT,
		'int2'				=>	PDO::PARAM_INT,
		'int8'				=>	PDO::PARAM_INT,
		'blob'				=>	PDO::PARAM_LOB,
	);

	/**
	 * 驱动类型
	 * @var string
	 */
	protected $type = 'SQLite';

	/**
	 * 是否初始化了链式操作
	 * @var boolean
	 */
	private static $isInitLinkOperation = false;

	/**
	 * 构造方法
	 * @param array $option 
	 */
	public function __construct($option = array())
	{
		parent::__construct($option);
		if(!self::$isInitLinkOperation)
		{
			self::$operations['fieldBefore'] = array();
			self::$isInitLinkOperation = true;
		}
	}

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
		if(isset($option['dsn']))
		{
			return $option['dsn'];
		}
		return 'sqlite' . (isset($option['version']) ? $option['version'] : '') . ':'
				 . (isset($option['path']) ? $option['path'] : ':memory:')
				 ;
	}
	
	/**
	 * 获取结果行数
	 * @return int
	 */
	public function foundRows()
	{
		return 0;
	}

	/**
	 * 获取数据库中所有数据表名
	 * @param string $dbname
	 * @return array
	 */
	public function getTables($dbName = null)
	{
		$sql = <<<SQL
SELECT
	name
FROM
	(
		SELECT
			*
		FROM
			sqlite_master
		UNION ALL
			SELECT
				*
			FROM
				sqlite_temp_master
	)
WHERE
	type IN ('table', 'view')
SQL;
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
		$result = $this->query('PRAGMA table_info(' . $this->parseKeyword($table) . ')');
		if (false === $result)
		{
			// 失败
			$result = false;
			return $result;
		}
		else
		{
			$fields = array ();
			$tableSQL = $this->getScalar(<<<SQL
SELECT
	sql
FROM
	(
		SELECT
			*
		FROM
			sqlite_master
		UNION ALL
			SELECT
				*
			FROM
				sqlite_temp_master
	)
WHERE
	name = :name
limit 1
SQL
			, ['name'=>$table]);
			// 处理数据
			foreach($result as $item)
			{
				$fields[$item['name']] = array(
					'name'			=>	$item['name'],
					'type'			=>	$item['type'],
					'length'		=>	0,
					'accuracy'		=>	0,
					'null'			=>	'0' === $item['notnull'],
					'default'		=>	$item['dflt_value'],
					'pk'			=>	'1' === $item['pk'],
					'is_auto_inc'	=>	'1' === $item['pk'] && preg_match('/[^`\[\'"]+[`\[\'"\s]AUTOINCREMENT/im', $tableSQL) > 0,
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
				. $this->parseFieldBefore()
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
			$valuesKeys = $keys;
			foreach($valuesKeys as $i => $v)
			{
				$valuesKeys[$i] = $this->parseParamName($valuesKeys[$i]);
			}
			return 'insert into ' . $this->parseTable($table) . '(' . implode(',',array_map(array($this,'parseKeyword'),$keys)) . ') values(:' . implode(',:',$valuesKeys) . ')' . $this->parseLock();
		}
		else
		{
			return 'insert into ' . $this->parseTable($table) . ' values(' . substr(str_repeat('?,',count($data)),0,-1) . ')' . $this->parseLock();
		}
	}

	/**
	 * 构建批量INSERT语句
	 * @param string $table 
	 * @param array $data 
	 * @return string 
	 */
	public function buildInsertBatchSQL($table = null, $data = array())
	{
		$keys = array_keys($data[0]);
		$values = array();
		foreach($data as $item)
		{
			$params = array();
			foreach($keys as $key)
			{
				$paramName = ':' . $this->getParamName();
				$this->bindValue($paramName, $item[$key]);
				$params[] = $paramName;
			}
			$values[] = '(' . implode(',', $params) . ')';
		}
		$this->operationOption['params'] = array();
		return 'insert into ' . $this->parseTable($table) . ' (' . implode(',',array_map(array($this,'parseKeyword'),$keys)) . ') values ' . implode(',', $values) . $this->parseLock();
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
				$sql .= $this->parseKeyword($key) . '=:' . $this->parseParamName($key) . ',';
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
		return '';
	}

	/**
	 * parseFieldBefore
	 * @return string
	 */
	public function parseFieldBefore()
	{
		if(isset($this->operationOption['fieldBefore']))
		{
			return implode(' ', $this->operationOption['fieldBefore']) . ' ';
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
		return true;
	}	

	/**
	 * 解锁表
	 * @param array $option 
	 * @return bool 
	 */
	public function unlockTable($option = null)
	{
		return true;
	}
}