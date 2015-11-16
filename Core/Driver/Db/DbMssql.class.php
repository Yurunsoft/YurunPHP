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
			if ($this->conn !== false)
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
		if ($this->result !== null && ! is_bool($this->result))
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
	public function query($sql)
	{
		if ($this->execute($sql))
		{
			if (is_bool($this->result))
			{
				return $this->result;
			}
			else
			{
				$result = sqlsrv_fetch_array($this->result);
				if ($result === false)
				{
					return false;
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
			return array ();
		}
	}
	
	/**
	 * 查询多条记录
	 *
	 * @param string $sql        	
	 */
	public function queryA($sql)
	{
		if ($this->execute($sql))
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
			return array ();
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
		$this->result = sqlsrv_query($this->conn, $sql, $params);
		if($this->result===false)
		{
			// 用于调试
			$GLOBALS['debug']['lastsql']=$this->lastSql;
			throw new Exception($this->getError());
		}
		return $this->result !== false ? true : false;
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
		return $this->execProcRef($procName,$params);
	}
	/**
	 * 执行存储过程
	 *
	 * @access public
	 * @param
	 *        	string procName 存储过程名称
	 * @return array
	 */
	public function execProcRef($procName,&$params=array())
	{
		$config = Config::get("YBProc.{$procName}");
		if($config)
		{
			$params2 = array();
			$return = 0;
			$params2[] = array(&$return,SQLSRV_PARAM_OUT);
			if(isset($config['params']))
			{
				$s = count($config['params']);
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
					$params2[] = array(&$value,$this->parseDirection(isset($params[$i][1])?$params[$i][1]:$config['params'][$i]['direction']),$this->parsePhpType($config['params'][$i]),$this->parseSqlType($config['params'][$i]));
				}
			}
		}
		else
		{
			$params2 = array();
			$return = 0;
			$params2[] = array(&$return,SQLSRV_PARAM_OUT);
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
				$params2[] = array(&$value,$this->parseDirection($params[$i]['direction']),$this->parsePhpType($params[$i]),$this->parseSqlType($params[$i]));
			}
		}
		// sql语句
		$p = substr(str_repeat('?,',count($params)),0,-1);
		$sql = "{?=call {$procName}({$p})}";
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
			if($t!==false)
			{
				$this->parseResult($result);
				$this->results[]=$result;
			}
		}
		while(sqlsrv_next_result($this->result)===true);
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
		unset($p[0]);
		if (count($p) === 1 && is_array($p[0]))
		{
			return $this->queryValue("select {$funName}(" . $this->filterValue($p[0]) . ')');
		}
		else
		{
			return $this->queryValue("select {$funName}(" . $this->filterValue($p) . ')');
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
		if ($error !== null)
		{
			if($this->isConnect())
			{
				$result = iconv('GBK', 'UTF-8//IGNORE', $error['message']);;
			}
			else
			{
				$result = $error['message'];
			}
			$result .= " 错误代码：{$error['code']}({$error['SQLSTATE']})" . (empty($this->lastSql)?'':" SQL语句:{$this->lastSql}");
		}
		return $result;
	}
	
	/**
	 * 获取数据库中所有数据表名
	 *
	 * @param string $dbname        	
	 */
	public function getTables($dbName = null)
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
		if ($result === false)
		{ // 失败
			return false;
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
	public function getFields($table)
	{
		// 查询
		$result = $this->queryA('select sys.syscolumns.*,sys.types.name as xtype_name ,(case when sys.index_columns.object_id is null then 0 else 1 end) as is_pk from sys.syscolumns
join sys.types on xtype=system_type_id 
left join sys.index_columns on sys.index_columns.column_id=sys.syscolumns.colid and sys.index_columns.object_id=id where id=object_id(\'' . $this->parseField($table) . '\')');
		if ($result === false)
		{ // 失败
			return false;
		}
		else
		{
			$r = array ();
			// 处理数据
			foreach ($result as $value)
			{
				$r[] = array ('name' => $value['name'],'type' => $value['xtype_name'],'null' => strtolower($value['isnullable']) === 'yes','default' => $value['autoval'],'key' => $value['is_pk'],'autoinc' => $value['colstat']);
			}
			// 返回结果
			return $r;
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
	public function parseMultiSql($file,$callback=null)
	{
		$this->fp = fopen($file, 'r');
		if($this->fp===false)
		{
			return false;
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
					if ($line[0]==='-' && $line[1]==='-')
					{
						continue;
					}
				}
				$sql.="{$line}\r\n";
				if (isset($line[0]))
				{
					if (substr($line,0,-1)===';')
					{
						$sql=trim(preg_replace("'/\*[^*]*\*/'", '', $sql));
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
		switch($direction)
		{
			case 'out':
				return SQLSRV_PARAM_OUT;
			case 'inout':
				return SQLSRV_PARAM_INOUT;
			default:
				return SQLSRV_PARAM_IN;
		}
	}
	public function parseSqlType($config)
	{
		switch($config['sql_type'])
		{
			case 'bigint':
				return SQLSRV_SQLTYPE_BIGINT;
			case 'binary':
				return SQLSRV_SQLTYPE_BINARY;
			case 'bit':
				return SQLSRV_SQLTYPE_BIT;
			case 'char':
				return SQLSRV_SQLTYPE_CHAR($config['length']);
			case 'date':
				return SQLSRV_SQLTYPE_DATE;
			case 'datetime':
				return SQLSRV_SQLTYPE_DATETIME;
			case 'datetime2':
				return SQLSRV_SQLTYPE_DATETIME2;
			case 'datetimeoffset':
				return SQLSRV_SQLTYPE_DATETIMEOFFSET;
			case 'decimal':
				return SQLSRV_SQLTYPE_DECIMAL($config['precision'],$config['scale']);
			case 'float':
				return SQLSRV_SQLTYPE_FLOAT;
			case 'image':
				return SQLSRV_SQLTYPE_IMAGE;
			case 'int':
				return SQLSRV_SQLTYPE_INT;
			case 'money':
				return SQLSRV_SQLTYPE_MONEY;
			case 'nchar':
				return SQLSRV_SQLTYPE_NCHAR($config['length']);
			case 'numeric':
				return SQLSRV_SQLTYPE_NUMERIC($config['precision'],$config['scale']);
			case 'nvarchar':
				return SQLSRV_SQLTYPE_NVARCHAR($config['length']);
			case 'ntext':
				return SQLSRV_SQLTYPE_NTEXT;
			case 'real':
				return SQLSRV_SQLTYPE_REAL;
			case 'smalldatetime':
				return SQLSRV_SQLTYPE_SMALLDATETIME;
			case 'smallint':
				return SQLSRV_SQLTYPE_SMALLINT;
			case 'smallmoney':
				return SQLSRV_SQLTYPE_SMALLMONEY;
			case 'text':
				return SQLSRV_SQLTYPE_TEXT;
			case 'time':
				return SQLSRV_SQLTYPE_TIME;
			case 'timestamp':
				return SQLSRV_SQLTYPE_TIMESTAMP;
			case 'tinyint':
				return SQLSRV_SQLTYPE_TINYINT;
			case 'uniqueidentifier':
				return SQLSRV_SQLTYPE_UNIQUEIDENTIFIER;
			case 'UDT':
				return SQLSRV_SQLTYPE_UDT;
			case 'varbinary':
				return SQLSRV_SQLTYPE_VARBINARY($config['length']);
			case 'varchar':
				return SQLSRV_SQLTYPE_VARCHAR($config['length']);
			case 'xml':
				return SQLSRV_SQLTYPE_XML;
			default:
				return null;
		}
	}
	public function parsePhpType($config)
	{
		switch($config['php_type'])
		{
			case 'int':
				return SQLSRV_PHPTYPE_INT;
			case 'datetime':
				return SQLSRV_PHPTYPE_DATETIME;
			case 'float':
				return SQLSRV_PHPTYPE_FLOAT;
			case 'stream':
				return SQLSRV_PHPTYPE_STREAM('UTF-8');
			default:
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
		$sql = parent::parseSelectOption($option);
		if(isset($limit))
		{
			$order = $this->parseOrder($order);
			$this->parseLimit($limit,$start,$end);
			$sql = <<<EOF
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
		return $sql;
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
}