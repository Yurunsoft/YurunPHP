<?php
/**
 * 微软Mssql数据库驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
class DbMssql extends DbBase
{
	// 参数标识
	protected $param_flag = array ('[',']');
	private $fp;
	// 查询返回的结果集
	public $results;
	/**
	 * 连接数据库
	 */
	public function connect($config = null)
	{
		if (empty($config))
		{
			$config = $this->config;
		}
		else
		{
			$this->config = $config;
		}
		if (empty($this->conn))
		{
			// 连接信息
			$server = (isset($config['server']) ? $config['server'] : '.');
			// 连接
			$this->conn = sqlsrv_connect($server, $config['info']);
			if (false !== $this->conn)
			{
				$this->connect = true;
				return true;
			}
			else
			{
				$this->connect = false;
				return false;
			}
		}
		else
		{
			return true;
		}
	}
	
	/**
	 * 断开数据库连接
	 */
	public function disConnect()
	{
		if ($this->free() && sqlsrv_close($this->conn))
		{
			$this->conn = null;
			$this->connect = false;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 选择数据库
	 *
	 * @param
	 *        	string 数据库名
	 */
	public function selectDb($dbName)
	{
		return false;
	}
	
	/**
	 * 释放结果集
	 */
	public function free()
	{
		if (null !== $this->result && ! is_bool($this->result))
		{
			sqlsrv_free_stmt($this->result);
			$this->result = null;
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 查询一条记录
	 *
	 * @param string $sql        	
	 */
	public function &query($sql,$params = array())
	{
		if ($this->execute($sql,$params))
		{
			if (is_bool($this->result))
			{
				return $this->result;
			}
			else
			{
				$result = sqlsrv_fetch_array($this->result);
				if (false === $result)
				{
					$result = array();
					return $result;
				}
				else
				{
					$this->parseResult($result);
					return $result;
				}
			}
		}
		else
		{
			$result = array();
			return $result;
		}
	}
	
	/**
	 * 查询多条记录
	 *
	 * @param string $sql        	
	 */
	public function &queryA($sql,$params = array())
	{
		if ($this->execute($sql,$params))
		{
			if (is_bool($this->result))
			{
				return $this->result;
			}
			else
			{
				$this->results = array();
				do 
				{
					$result = array ();
					while ($t = sqlsrv_fetch_array($this->result))
					{
						$result[] = $t;
					}
					$this->parseResult($result);
					$this->results[]=$result;
				}
				while(sqlsrv_next_result($this->result));
				return $this->results[0];
			}
		}
		else
		{
			$result = array();
			return $result;
		}
	}
	
	/**
	 * 执行SQL语句
	 *
	 * @param string $sql        	
	 */
	public function execute($sql,&$params=array())
	{
		// 记录最后执行的SQL语句
		$this->lastSql = $sql;
		// 执行SQL语句
		$this->result = sqlsrv_query($this->conn,$sql,$params);
		if(false===$this->result)
		{
			// 用于调试
			$GLOBALS['debug']['lastsql']=$this->lastSql;
			throw new Exception($this->getError());
		}
		return false !== $this->result ? true : false;
	}
	
	/**
	 * 执行存储过程
	 *
	 * @access public
	 * @param
	 *        	string procName 存储过程名称
	 * @return array
	 */
	public function execProc($procName,$params=array())
	{
		$config = Config::get('@.DbProc.' . $procName);
		$params2 = array();
		$return = 0;
		$params2[] = array(&$return,SQLSRV_PARAM_OUT);
		if($config)
		{
			if(isset($config['params']))
			{
				$s = count($config['params']);
				for($i=0;$i<$s;++$i)
				{
					$params2[] = array(&$params[$i],$this->parseDirection($config['params'][$i]['direction']),$this->parsePhpType($config['params'][$i]),$this->parseSqlType($config['params'][$i]));
				}
			}
		}
		else
		{
			$s = count($params);
			for($i=0;$i<$s;++$i)
			{
				if(is_array($params[$i]))
				{
					$value = &$params[$i][0];
				}
				else
				{
					$value = &$params[$i];
				}
				$params2[] = array(&$value,$this->parseDirection($params[$i]['direction']));
			}
		}
		// sql语句
		$p = substr(str_repeat('?,',count($params)),0,-1);
		$sql = '{?=call ' . $procName . '(' . $p . ')}';
		// 执行查询
		$this->execute($sql, $params2);
		// 取出结果
		$this->results = array();
		do 
		{
			$result = array ();
			while ($t = sqlsrv_fetch_array($this->result))
			{
				$result[] = $t;
			}
			if(false!==$t)
			{
				$this->parseResult($result);
				$this->results[]=$result;
			}
		}
		while(true===sqlsrv_next_result($this->result));
		return $return;
	}
	
	/**
	 * 执行数据库函数
	 *
	 * @access public
	 * @param
	 *        	string procName 函数名称
	 * @return array
	 */
	public function execFunction($funName)
	{
		$p = func_get_args();
		if (isset($p[1]) && is_array($p[1]))
		{
			return $this->queryValue('select ' . $funName . '(' . $this->filterValue($p[1]) . ')');
		}
		else
		{
			return $this->queryValue('select ' . $funName . '(' . $this->filterValue($p) . ')');
		}
	}
	
	/**
	 * 获取结果行数
	 *
	 * @access public
	 * @return int
	 */
	public function foundRows()
	{
		return sqlsrv_num_rows($this->result);
	}
	
	/**
	 * 获取影响行数
	 *
	 * @access public
	 * @return int
	 */
	public function rowCount()
	{
		return sqlsrv_rows_affected($this->result);
	}
	
	/**
	 * 最后insert自动编号的ID
	 *
	 * @access public
	 * @return int
	 */
	public function lastInsertID()
	{
		return $this->queryValue('SELECT SCOPE_IDENTITY()');
	}
	
	/**
	 * 获取最后一条错误信息
	 */
	public function getError()
	{
		$errors = sqlsrv_errors();
		$error = array_shift($errors);
		if (null !== $error)
		{
			if($this->isConnect())
			{
				$result = iconv('GBK', 'UTF-8//IGNORE', $error['message']);;
			}
			else
			{
				$result = $error['message'];
			}
			$result .= ' 错误代码：' . $error['code'] . '(' . $error['SQLSTATE'] . ')' . (empty($this->lastSql)?'':' SQL语句:' . $this->lastSql);
		}
		return $result;
	}
	
	/**
	 * 获取数据库中所有数据表名
	 *
	 * @param string $dbname        	
	 */
	public function &getTables($dbName = null)
	{
		if (empty($dbName))
		{ // 当前表
			$sql = 'select * from sys.tables';
		}
		else
		{ // 其他表
			$sql = 'select * from ' . $this->parseField($dbName) . '.sys.tables';
		}
		// 查询
		$result = $this->queryA($sql);
		if (false === $result)
		{
			// 失败
			$result = false;
			return $result;
		}
		else
		{
			$r = array ();
			// 处理数据
			foreach ($result as $value)
			{
				$r[] = $value[0];
			}
			// 返回结果
			return $r;
		}
	}
	
	/**
	 * 获取数据表中所有字段详细信息
	 *
	 * @param string $table        	
	 */
	public function &getFields($table)
	{
		// 查询
		$result = &$this->queryA(
<<<SQL
SELECT
	cols.COLUMN_NAME AS name,
	CASE
WHEN CHARACTER_MAXIMUM_LENGTH IS NULL THEN
	DATA_TYPE
ELSE
	(
		DATA_TYPE + '(' + CAST (
			CHARACTER_MAXIMUM_LENGTH AS VARCHAR (32)
		) + ')'
	)
END AS type,
 CASE
WHEN IS_NULLABLE = 'YES' THEN
	1
ELSE
	0
END AS [null],
 COLUMN_DEFAULT,
 COLUMNPROPERTY(
	OBJECT_ID(cols.TABLE_NAME),
	cols.COLUMN_NAME,
	'IsIdentity'
) AS IsIdentity,
 CASE
WHEN kcu.COLUMN_NAME IS NULL THEN
	0
ELSE
	1
END AS [key]
FROM
	INFORMATION_SCHEMA.columns AS cols
LEFT JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu ON kcu.TABLE_NAME = cols.TABLE_NAME
AND kcu.COLUMN_NAME = cols.COLUMN_NAME
WHERE
	cols.TABLE_NAME = '{$table}'
SQL
);
		if (false === $result)
		{
			// 失败
			$result = false;
			return $result;
		}
		else
		{
			arrayColumnToKey($result,'name');
			// 返回结果
			return $result;
		}
	}
	/**
	 * 开始事务
	 */
	public function begin()
	{
		return sqlsrv_begin_transaction($this->conn);
	}
	/**
	 * 回滚事务
	 */
	public function rollback()
	{
		return sqlsrv_rollback($this->conn);
	}
	/**
	 * 提交事务
	 */
	public function commit()
	{
		return sqlsrv_commit($this->conn);
	}
	/**
	 * 解析sql文件，支持返回sql数组，或者使用回调函数
	 * @param string $file
	 * @param callback $callback
	 * @return mixed
	 */
	public function &parseMultiSql($file,$callback=null)
	{
		$this->fp = fopen($file, 'r');
		if(false===$this->fp)
		{
			$result = false;
			return $result;
		}
		else 
		{
			if(empty($callback))
			{
				$result=array();
			}
			$sql='';
			while ($line = fgets($this->fp, 40960))
			{
				$line=trim($line);
				if (isset($line[0]))
				{
					if ('-'===$line[0] && '-'===$line[1])
					{
						continue;
					}
				}
				$sql.= $line . "\r\n";
				if (isset($line[0]))
				{
					if (';' === substr($line,0,-1))
					{
						$sql=trim(preg_replace('\'/\*[^*]*\*/\'', '', $sql));
						if(empty($callback))
						{
							$result[]=$sql;
						}
						else 
						{
							call_user_func($callback,$sql);
						}
						$sql='';
					}
				}
			}
			if(empty($callback))
			{
				return $result;
			}
		}
	}
	public function parseResult(&$data)
	{
		$oldKeys = array_keys($data);
		$keys = array();
		foreach($oldKeys as $key)
		{
			if(is_numeric($str))
			{
				$keys [] = $key;
			}
			else
			{
				$keys [] = iconv('GB2312', 'UTF-8//IGNORE', $key);
			}
			if(is_array($data[$key]))
			{
				$this->parseResult($data[$key]);
			}
		}
		if(!empty($keys))
		{
			$data = array_combine($keys,$data);
		}
	}
	public function parseDirection($direction)
	{
		if('out' === $direction)
		{
			return SQLSRV_PARAM_OUT;
		}
		else if('inout' === $direction)
		{
			return SQLSRV_PARAM_INOUT;
		}
		else
		{
			return SQLSRV_PARAM_IN;
		}
	}
	public function parseSqlType($config)
	{
		$type = strtolower($config['sql_type']);
		if ('bigint' === $type)
		{
			return SQLSRV_SQLTYPE_BIGINT;
		}
		else if ('binary' === $type)
		{
			return SQLSRV_SQLTYPE_BINARY;
		}
		else if ('bit' === $type)
		{
			return SQLSRV_SQLTYPE_BIT;
		}
		else if ('char' === $type)
		{
			return SQLSRV_SQLTYPE_CHAR ($config ['length']);
		}
		else if ('date' === $type)
		{
			return SQLSRV_SQLTYPE_DATE;
		}
		else if ('datetime' === $type)
		{
			return SQLSRV_SQLTYPE_DATETIME;
		}
		else if ('datetime2' === $type)
		{
			return SQLSRV_SQLTYPE_DATETIME2;
		}
		else if ('datetimeoffset' === $type)
		{
			return SQLSRV_SQLTYPE_DATETIMEOFFSET;
		}
		else if ('decimal' === $type)
		{
			return SQLSRV_SQLTYPE_DECIMAL ($config ['precision'], $config ['scale']);
		}
		else if ('float' === $type)
		{
			return SQLSRV_SQLTYPE_FLOAT;
		}
		else if ('image' === $type)
		{
			return SQLSRV_SQLTYPE_IMAGE;
		}
		else if ('int' === $type)
		{
			return SQLSRV_SQLTYPE_INT;
		}
		else if ('money' === $type)
		{
			return SQLSRV_SQLTYPE_MONEY;
		}
		else if ('nchar' === $type)
		{
			return SQLSRV_SQLTYPE_NCHAR ($config ['length']);
		}
		else if ('numeric' === $type)
		{
			return SQLSRV_SQLTYPE_NUMERIC ($config ['precision'], $config ['scale']);
		}
		else if ('nvarchar' === $type)
		{
			return SQLSRV_SQLTYPE_NVARCHAR ($config ['length']);
		}
		else if ('ntext' === $type)
		{
			return SQLSRV_SQLTYPE_NTEXT;
		}
		else if ('real' === $type)
		{
			return SQLSRV_SQLTYPE_REAL;
		}
		else if ('smalldatetime' === $type)
		{
			return SQLSRV_SQLTYPE_SMALLDATETIME;
		}
		else if ('smallint' === $type)
		{
			return SQLSRV_SQLTYPE_SMALLINT;
		}
		else if ('smallmoney' === $type)
		{
			return SQLSRV_SQLTYPE_SMALLMONEY;
		}
		else if ('text' === $type)
		{
			return SQLSRV_SQLTYPE_TEXT;
		}
		else if ('time' === $type)
		{
			return SQLSRV_SQLTYPE_TIME;
		}
		else if ('timestamp' === $type)
		{
			return SQLSRV_SQLTYPE_TIMESTAMP;
		}
		else if ('tinyint' === $type)
		{
			return SQLSRV_SQLTYPE_TINYINT;
		}
		else if ('uniqueidentifier' === $type)
		{
			return SQLSRV_SQLTYPE_UNIQUEIDENTIFIER;
		}
		else if ('UDT' === $type)
		{
			return SQLSRV_SQLTYPE_UDT;
		}
		else if ('varbinary' === $type)
		{
			return SQLSRV_SQLTYPE_VARBINARY ($config ['length']);
		}
		else if ('varchar' === $type)
		{
			return SQLSRV_SQLTYPE_VARCHAR ($config ['length']);
		}
		else if ('xml' === $type)
		{
			return SQLSRV_SQLTYPE_XML;
		}
		else
		{
			return null;
		}
	}
	public function parsePhpType($config)
	{
		if('int' === $config['php_type'])
		{
			return SQLSRV_PHPTYPE_INT;
		}
		else if('datetime' === $config['php_type'])
		{
			return SQLSRV_PHPTYPE_DATETIME;
		}
		else if('float' === $config['php_type'])
		{
			return SQLSRV_PHPTYPE_FLOAT;
		}
		else if('stream' === $config['php_type'])
		{
			return SQLSRV_PHPTYPE_STREAM('UTF-8');
		}
		else
		{
			return SQLSRV_PHPTYPE_STRING('UTF-8');
		}
	}
	/**
	 * 解析查询规则
	 *
	 * @param array $option
	 * @return string
	 */
	public function parseSelectOption($option)
	{
		if(isset($option['limit']))
		{
			$limit = $option['limit'];
			unset($option['limit']);
			$order = $option['order'];
			unset($option['order']);
		}
		else
		{
			$order = $option['order'];
		}
		$order = $this->parseOrder(empty($order) ? array () : $order);
		if(isset($limit))
		{
			$this->parseLimit($limit,$start,$end);
			if($start==0)
			{
				$fields = 'top ' . $end . ' ' . $this->parseSelectField($option);
			}
			else
			{
				$sql = parent::parseSelectOption($option);
				return
<<<EOF
SELECT
	*
FROM
	(
		SELECT
			TOP {$end} *, ROW_NUMBER () OVER ({$order}) AS RowNumber
		FROM
			(
				{$sql}
			) AS TmpPage1
	) TmpPage
WHERE
	RowNumber > {$start}
AND RowNumber <= {$end}
{$order}
EOF
				;
			}
		}
		else 
		{
			$fields = $this->parseSelectField($option);
		}
		$where = $this->parseCondition(isset($option['where']) ? $option['where'] : '');
		if ('' !== $where)
		{
			$where = ' where ' . $where;
		}
		return 'select ' . $this->parseDistinct(isset($option['distinct']) ? $option['distinct'] : '')
		. $fields
		. ' from '
				. $this->parseField(isset($option['from']) ? $option['from'] : '')
				. $this->parseJoin(isset($option['join']) ? $option['join'] : array())
				. $where . $this->parseGroup(isset($option['group']) ? $option['group'] : array ())
				. $this->parseHaving(isset($option['having']) ? $option['having'] : '')
				. $order;
	}
	/**
	 * 解析limit
	 *
	 * @param mixed $limit
	 * @return string
	 */
	public function parseLimit($limit,&$start=0,&$end=0)
	{
		if (! is_array($limit))
		{
			$limit = explode(',', $limit);
		}
		if (is_numeric($limit[0]))
		{
			if (isset($limit[1]) && is_numeric($limit[1]))
			{
				$start = $limit[0];
				$end = $start + $limit[1];
			}
			else
			{
				$start = 0;
				$end = $limit[0];
			}
		}
	}
	/**
	 * 处理order field
	 * @param mixed $data
	 */
	public function parseOrderField($data)
	{
		if(is_array($data))
		{
			return 'CHARINDEX(\'|\' + LTRIM(RTRIM(' . $data[0] . ')) + \'|\', \'|\'' . $this->filterValue($data[1]) . '|\')';
		}
		else
		{
			return $data;
		}
	}
}