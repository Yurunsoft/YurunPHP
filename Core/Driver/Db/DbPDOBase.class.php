<?php
abstract class DbPDOBase implements IDb
{
	use TDbSQLHelper;
	use TDbOperation;
	use TLinkOperation;
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
	 * 表名前缀
	 * @var string
	 */
	public $tablePrefix = 'tb_';

	/**
	 * 字段类型和PDO类型关联
	 * @var array
	 */
	public $paramType = array(
		// 'varchar' => PDO::PARAM_STR
	);

	/**
	 * 驱动类型
	 * @var string
	 */
	protected $type = '';

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
	 * 处理类似varchar(32)和decimal(10,2)格式的字段类型
	 * @param string $text 
	 * @param string $typeName 
	 * @param int $length 
	 * @param int $accuracy 
	 * @return bool 
	 */
	public function parseFieldType($text, &$typeName, &$length, &$accuracy)
	{
		if(preg_match('/([^(]+)(\((\d+)(,(\d+))?\))?/', $text, $match))
		{
			$typeName = $match[1];
			$length = (int)$match[3];
			if(isset($match[5]))
			{
				$accuracy = (int)$match[5];
			}
			else
			{
				$accuracy = 0;
			}
			return true;
		}
		else
		{
			$typeName = '';
			$length = 0;
			$accuracy = 0;
			return false;
		}
	}

	/**
	 * 根据字段类型获取PDO的参数数据类型
	 * @param string $fieldType 
	 * @return int 
	 */
	public function getParamType($fieldType)
	{
		$fieldType = strtolower($fieldType);
		return isset($this->paramType[$fieldType]) ? $this->paramType[$fieldType] : PDO::PARAM_STR;
	}

	/**
	 * 根据值获取PDO的参数数据类型
	 * @param mixed $value 
	 * @return int 
	 */
	public function geetParamTypeByValue($value)
	{
		switch(gettype($value))
		{
			case 'boolean':
				return PDO::PARAM_BOOL;
				break;
			case 'integer':
				return PDO::PARAM_INT;
				break;
			case 'NULL':
				return PDO::PARAM_NULL;
				break;
			default:
				return PDO::PARAM_STR;
				break;
		}
	}

	/**
	 * 获取驱动类型
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}
}