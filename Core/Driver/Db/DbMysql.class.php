<?php
/**
 * MySQL数据库驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
class DbMysql extends DbBase
{
	// 参数标识
	protected $param_flag = array ('`','`');
	
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
		if ($this->conn === null)
		{
			// 连接信息
			$server = (isset($config['host']) ? $config['host'] : 'localhost') . ':' . (isset($config['port']) && is_numeric($config['port']) ? $config['port'] : '3306');
			$username = isset($config['username']) ? $config['username'] : 'root';
			$password = isset($config['password']) ? $config['password'] : '';
			$flags = ((isset($config['flags']) && is_numeric($config['flags'])) ? $config['flags'] : 0);
			// 连接
			if (isset($config['pc_connect']) && $config['pc_connect'])
			{
				$this->conn = mysql_pconnect($server, $username, $password, $flags);
			}
			else
			{
				$this->conn = mysql_connect($server, $username, $password, ((isset($config['newlink']) && is_bool($config['newlink'])) ? $config['newlink'] : 1), $flags);
			}
			if ($this->conn !== false)
			{
				// 设置编码
				if (isset($config['charset']))
				{
					$this->execute("set names {$config['charset']}");
				}
				// 选择数据库
				if (isset($config['dbname']))
				{
					$this->selectDb($config['dbname']);
				}
				$this->connect = true;
				return true;
			}
			else
			{
				$this->conn = null;
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
		if ($this->free() && mysql_close($this->conn))
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
		return mysql_select_db($dbName);
	}
	
	/**
	 * 释放结果集
	 */
	public function free()
	{
		if ($this->result !== null && ! is_bool($this->result))
		{
			mysql_free_result($this->result);
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
				$result = mysql_fetch_array($this->result);
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
				while ($t = mysql_fetch_array($this->result))
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
		$this->result = mysql_query($sql, $this->conn);
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
			return $this->queryA("call $procName(" . implode(',', $this->filterValue($p[0])) . ')');
		}
		else
		{
			return $this->queryA("call $procName(" . $this->filterValue($p) . ')');
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
		return mysql_num_rows();
	}
	
	/**
	 * 获取影响行数
	 *
	 * @access public
	 * @return int
	 */
	public function rowCount()
	{
		return mysql_affected_rows();
	}
	
	/**
	 * 最后insert自动编号的ID
	 *
	 * @access public
	 * @return int
	 */
	public function lastInsertID()
	{
		return mysql_insert_id();
	}
	
	/**
	 * 获取最后一条错误信息
	 */
	public function getError()
	{
		$error = mysql_error();
		if ($error !== '')
		{
			$error .= PHP_EOL . '错误代码:' . mysql_errno() . " SQL语句:{$this->lastSql}";
		}
		return $error;
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
			$sql = 'show tables';
		}
		else
		{ // 其他表
			$sql = 'show tables from' . $this->parseField($dbName);
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
		$result = $this->queryA('show columns from ' . $this->parseField($table));
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
				$r[] = array ('name' => $value['Field'],'type' => $value['Type'],'null' => strtolower($value['Null']) === 'yes','default' => $value['Default'],'key' => $value['Key'],'autoinc' => strtolower($value['Extra']) === 'auto_increment','ex' => $value['Extra']);
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
		$this->execute('begin');
	}
	/**
	 * 回滚事务
	 */
	public function rollback()
	{
		$this->execute('rollback');
	}
	/**
	 * 提交事务
	 */
	public function commit()
	{
		$this->execute('commit');
	}
}