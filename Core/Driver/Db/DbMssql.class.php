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
				if ($result !== false)
				{
					return $result;
				}
				else
				{
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
				$result = array ();
				while ($t = sqlsrv_fetch_array($this->result))
				{
					$result[] = $t;
				}
				return $result;
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
	public function execute($sql)
	{
		// 解决执行存储过程后再执行语句就出错
		if (substr($this->lastSql, 0, 5) == 'call ')
		{
			$this->disconnect();
			$this->connect();
		}
		// 记录最后执行的SQL语句
		$this->lastSql = $sql;
		// 执行SQL语句
		$this->result = sqlsrv_query($this->conn, $sql);
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
	public function execProc($procName)
	{
		$p = func_get_args();
		unset($p[0]);
		if (count($p) === 1 && is_array($p[0]))
		{
			return $this->queryA("exec $procName " . implode(',', $this->filterValue($p[0])));
		}
		else
		{
			return $this->queryA("exec $procName " . $this->filterValue($p));
		}
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
			return $this->queryValue("select $procName(" . implode(',', $this->filterValue($p[0])) . ')');
		}
		else
		{
			return $this->queryValue("select $procName(" . implode(',', $this->filterValue($p)) . ')');
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
				if (strlen($line)>=1)
				{
					if ($line[0]==='-' && $line[1]==='-')
					{
						continue;
					}
				}
				$sql.="{$line}\r\n";
				if (strlen($line)>0)
				{
					if ($line[strlen($line)-1]===';')
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
	
}