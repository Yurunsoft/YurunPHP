<?php
import('IDb.implements');
abstract class DbPDOBase implements IDb
{
	/**
	 * 数据库操作对象
	 * @var PDO
	 */
	public $handler;

	/**
	 * 是否已连接
	 * @var bool
	 */
	protected $isConnect;

	/**
	 * 最后使用的PDOStatement
	 * @var PDOStatement
	 */
	public $lastStmt;

	/**
	 * 运算符
	 * @var array
	 */
	public $operators = array ();

	/**
	 * 逻辑运算符
	 * @var array
	 */
	public $logicOperators = array ('and','or','xor','and not','or not','xor not');

	/**
	 * 参数标识
	 * @var array
	 */
	public $param_flag = array ('','');

	/**
	 * 最后执行的SQL语句
	 * @var string
	 */
	public $lastSql = '';

	/**
	 * 最后执行的SQL语句参数
	 * @var array
	 */
	public $lastSqlParams = array();

	/**
	 * 是否启用参数标识
	 * @var bool
	 */
	public $isUseParamFlag = true;

	/**
	 * 构造方法
	 * @param array $option 
	 */
	public function __construct($option = array())
	{
		$this->connect($option);
	}

	/**
	 * 连接数据库
	 * @param array $option 
	 */
	public function connect($option = array())
	{
		$this->option = $option;
		$this->tablePrefix = isset($option['prefix']) ? $option['prefix'] : Config::get('@.DB_PREFIX');
		if(!isset($this->option['username']))
		{
			$this->option['username'] = 'root';
		}
		if(!isset($option['password']))
		{
			$this->option['password'] = '';
		}
		if(!isset($option['options']))
		{
			$this->option['options'] = array();
		}
		$this->lastSql = '';
		$this->lastSqlParams = array();
		$this->lastStmt = null;
		$this->handler = new PDO($this->buildDSN(),$this->option['username'],$this->option['password'],$this->option['options']);
		$this->isConnect = true;
	}

	/**
	 * 获取是否已连接
	 * @return boolean
	 */
	public function isConnect()
	{
		return $this->isConnect;
	}

	/**
	 * 获取错误信息
	 * @return string 
	 */
	public function getError()
	{
		if($this->lastStmt)
		{
			$errorInfo = $this->lastStmt->errorInfo();
		}
		else
		{
			$errorInfo = $this->handler->errorInfo();
		}
		return implode(' ',$errorInfo);
	}

	/**
	 * 获取错误代码
	 * @return mixed 
	 */
	public function getErrorCode()
	{
		if($this->lastStmt)
		{
			return $this->lastStmt->errorCode();
		}
		else
		{
			return $this->handler->errorCode();
		}
	}

	/**
	 * 准备一个查询
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function prepareQuery($sql,$params = array())
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
	public function query($sql,$params = array())
	{
		return $this->prepareQuery($sql,$params)->fetchAll(PDO::FETCH_ASSOC);
	}

	/**
	 * 查询一列数据
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function queryColumn($sql,$params = array())
	{
		return $this->prepareQuery($sql,$params)->fetchAll(PDO::FETCH_COLUMN);
	}

	/**
	 * 查询一条记录
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getOne($sql,$params = array())
	{
		return $this->prepareQuery($sql,$params)->fetch(PDO::FETCH_ASSOC);
	}

	/**
	 * 执行SQL语句，返回第一行第一列
	 * @param string $sql 
	 * @param array $params 
	 * @return array 
	 */
	public function getScalar($sql,$params = array())
	{
		return $this->prepareQuery($sql,$params)->fetchColumn();
	}

	/**
	 * 返回最后插入行的ID或序列值
	 * @param string $name 应该返回ID的那个序列对象的名称。比如，PDO_PGSQL() 要求为 name 参数指定序列对象的名称。
	 * @return string
	 */
	public function lastInsertID($name = null)
	{
		return $this->handler->lastInsertID($name);
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
	 * 获取影响行数
	 * @return int
	 */
	public function rowCount()
	{
		return null === $this->lastStmt ? 0 : $this->lastStmt->rowCount();
	}

	/**
	 * 开启事务
	 * @return bool 
	 */
	public function begin()
	{
		return $this->handler->beginTransaction();
	}

	/**
	 * 提交事务
	 * @return bool 
	 */
	public function commit()
	{
		return $this->handler->commit();
	}

	/**
	 * 回滚事务
	 * @return bool 
	 */
	public function rollback()
	{
		return $this->handler->rollBack();
	}

	/**
	 * 检查是否在一个事务内
	 * @return bool 
	 */
	public function inTransaction()
	{
		return $this->handler->inTransaction();
	}

	/**
	 * 获取数据库中所有数据表名
	 * @param string $dbname
	 * @return array
	 */
	public function getTables($dbName = null)
	{

	}

	/**
	 * 获取数据表中所有字段详细信息
	 * @param string $table
	 * @return array
	 */
	public function getFields($table)
	{

	}
}