<?php
/**
 * MySQLi数据库驱动类
 * @author Yurun <admin@yurunsoft.com>
 */
class DbMysqli extends DbBase
{
	// 参数标识
	protected $param_flag = array ('`','`');
	private $fp;
	/**
	 * mysqli对象
	 * @var unknown
	 */
	private $db;
	private $stmt;
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
			$server = (isset($config['host']) ? $config['host'] : 'localhost');
			$username = isset($config['username']) ? $config['username'] : 'root';
			$password = isset($config['password']) ? $config['password'] : '';
			$dbname = isset($config['dbname']) ? $config['dbname'] : '';
			$port = isset($config['port']) && is_numeric($config['port']) ? $config['port'] : 3306;
			$socket = ((isset($config['socket'])) ? $config['socket'] : null);
			// 连接
			$this->db = new Mysqli($server,$username,$password,$dbname,$port,$socket);
			if (0 === $this->db->connect_errno)
			{
				// 设置编码
				if (isset($config['charset']))
				{
					$this->db->set_charset($config['charset']);
				}
				// 选择数据库
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
		if ($this->free() && $this->db->close())
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
		return $this->db->select_db($dbName);
	}
	
	/**
	 * 释放结果集
	 */
	public function free()
	{
	}
	
	/**
	 * 查询一条记录
	 *
	 * @param string $sql        	
	 */
	public function &query($sql,$params = array(),$isReturnParams = false)
	{
		if ($this->execute($sql,$params,$isReturnParams) && isset($this->results[0][0]))
		{
			return $this->results[0][0];
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
	public function &queryA($sql,$params = array(),$isReturnParams = false)
	{
		if ($this->execute($sql,$params,$isReturnParams) && isset($this->results[0]))
		{
			return $this->results[0];
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
	public function execute($sql,$params = array(),$isReturnParams = false)
	{
		$this->results = array();
		// 记录最后执行的SQL语句
		$this->lastSql = $sql;
		if($isReturnParams || !empty($params))
		{
			// 解析预定义变量
			$varsTypes = '';
			if($isReturnParams)
			{
				$vars = '';
				$varsSql = 'set ';
			}
			$i = 0;
			$sql = preg_replace_callback(
					'/%(i|d|s|b)/',
					function($matches)use(&$i,$isReturnParams,&$varsTypes,&$vars,&$varsSql) {
						$varsTypes .= $matches[1] . '';
						if($isReturnParams)
						{
							$vars .= "@{$matches[1]}{$i},";
							$varsSql .= "@{$matches[1]}{$i}=?,";
							return '@' . $matches[1] . $i++;
						}
						else
						{
							++$i;
							return '?';
						}
					},
					$sql);
			if(isset($vars))
			{
				$vars = substr($vars,0,-1);
			}
			// 给变量设置值
			if($isReturnParams)
			{
				$varsSql = substr($varsSql,0,-1);
				$this->stmt = $this->db->prepare($varsSql);
				if(false === $this->stmt)
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				$tparam = $params;
				array_unshift($tparam,$varsTypes);
				call_user_func_array(array(&$this->stmt,'bind_param'),arrayRefer($tparam));
				$result = $this->stmt->execute();
				if(false === $result)
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				// 执行SQL
				if(false === $this->stmt->prepare($sql))
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				if(false === $this->stmt->execute())
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
			}
			else
			{
				// 执行SQL
				$this->stmt = $this->db->prepare($sql);
				if(false === $this->stmt)
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				$tparam = $params;
				array_unshift($tparam,$varsTypes);
				call_user_func_array(array(&$this->stmt,'bind_param'),arrayRefer($tparam));
				if(false === $this->stmt->execute())
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
			}
			// 返回的结果集
			do
			{
				if($result = $this->stmt->get_result())
				{
					$this->results[] = $result->fetch_all(MYSQLI_ASSOC);
				}
			}
			while($this->stmt->next_result());
			// 取返回SQL值
			if($isReturnParams)
			{
				if(false === $this->stmt->prepare('select ' . $vars))
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				if(false === $this->stmt->execute())
				{
					$GLOBALS['debug']['lastsql'] = $this->lastSql;
					throw new Exception($this->getError());
				}
				call_user_func_array(array(&$this->stmt,'bind_result'),arrayRefer($params));
				$this->stmt->fetch();
			}
			$this->stmt->close();
		}
		else
		{
			// 执行SQL语句
			$result = $this->db->multi_query($sql);
			if(false === $result)
			{
				$GLOBALS['debug']['lastsql'] = $this->lastSql;
				throw new Exception($this->getError());
			}
			do
			{
				if($result = $this->db->use_result())
				{
					$this->results[] = $result->fetch_all();
				}
			}
			while($this->db->next_result());
		}
		return true;
	}
	/**
	 * 执行存储过程
	 *
	 * @access public
	 * @param
	 *        	string procName 存储过程名称
	 * @return array
	 */
	public function &execProc($procName, $params = array(), $paramTypes = null)
	{
		if(null === $paramTypes)
		{
			$config = Config::get('@.DbProc.' . $procName);
			$paramTypes = $config['params'];
			unset($config);
		}
		if($paramTypes)
		{
			$vars = substr(preg_replace_callback(
						'/./',
						function($matches){
							return '%' . $matches[0] . ',';
						},
						$paramTypes
					),0,-1);
			return $this->queryA('call ' . $procName . '(' . $vars . ')',$params,true);
		}
		else
		{
			return $this->queryA('call ' . $procName . '(' . $this->filterValue($params) . ')');
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
	public function execFunction($funName, $params = array(), $paramTypes = null)
	{
		if(null === $paramTypes)
		{
			$config = Config::get('@.DbFunc.' . $procName);
			$paramTypes = $config['params'];
			unset($config);
		}
		if($paramTypes)
		{
			$vars = substr(preg_replace_callback(
					'/./',
					function($matches){
						return '%' . $matches[0] . ',';
					},
					$paramTypes
			),0,-1);
			return $this->queryA('select ' . $funName . '(' . $vars . ')',$params,true);
		}
		else
		{
			return $this->queryA('select ' . $funName . '(' . $this->filterValue($params) . ')');
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
		return 0;
	}
	
	/**
	 * 获取影响行数
	 *
	 * @access public
	 * @return int
	 */
	public function rowCount()
	{
		return $this->db->affected_rows;
	}
	
	/**
	 * 最后insert自动编号的ID
	 *
	 * @access public
	 * @return int
	 */
	public function lastInsertID()
	{
		return $this->db->insert_id;
	}
	
	/**
	 * 获取最后一条错误信息
	 */
	public function getError()
	{
		if($this->connect)
		{
			if (0 !== $this->db->errno)
			{
				$error = iconv('GBK', 'UTF-8//IGNORE', $this->db->error) . '错误代码：' . $this->db->errno . (empty($this->lastSql)?'':' SQL语句:' . $this->lastSql);
			}
		}
		else
		{
			if (0 !== $this->db->connect_errno)
			{
				$error = iconv('GBK', 'UTF-8//IGNORE', $this->db->connect_error) . '错误代码：' . $this->db->connect_errno;
			}
		}
		return $error;
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
			$sql = 'show tables';
		}
		else
		{ // 其他表
			$sql = 'show tables from ' . $this->parseField($dbName);
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
		$result = $this->queryA('show columns from ' . $this->parseField($table));
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
				$r[] = array ('name' => $value['Field'],'type' => $value['Type'],'null' => 'yes' === strtolower($value['Null']),'default' => $value['Default'],'key' => $value['Key'],'autoinc' => strtolower($value['Extra']) === 'auto_increment','ex' => $value['Extra']);
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
		$this->db->begin_transaction();
	}
	/**
	 * 回滚事务
	 */
	public function rollback()
	{
		$this->db->rollback();
	}
	/**
	 * 提交事务
	 */
	public function commit()
	{
		$this->db->commit();
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
				if (isset($line[1]))
				{
					if ('#'===$line[0] || ('-'===$line[0] && '-'===$line[1]))
					{
						continue;
					}
				}
				$sql .= $line . "\r\n";
				if (isset($line[0]))
				{
					if (';'===substr($line,0,-1))
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
}