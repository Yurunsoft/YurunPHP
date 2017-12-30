<?php
/**
 * 模型类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class Model extends ArrayData
{
	use TLinkOperation;
	/**
	 * 转换为数据库字段
	 * @var int
	 */
	const TO_DB = 0;
	/**
	 * 转换为表单字段
	 * @var int
	 */
	const TO_FORM = 1;
	/**
	 * 主键
	 */
	public $pk = 'id';
	/**
	 * 前缀
	 */
	public $prefix ='';
	/**
	 * 表名
	 */
	public $table = '';
	/**
	 * 数据库操作对象
	 */
	public $db;
	/**
	 * 字段映射
	 */
	public $fieldsMap = array (
		// '表单字段'=>'数据库字段'
	);
	/**
	 * 从表单创建数据并验证的规则
	 */
	public $rules = array ();
	/**
	 * 是否自动加载字段信息
	 */
	public $autoFields = null;
	/**
	 * 字段名数组
	 */
	public $fieldNames = array();
	/**
	 * 字段所有属性数组
	 */
	public $fields = array();
	/**
	 * 数据库连接配置别名，为空则使用默认连接
	 */
	public $dbAlias = null;
	/**
	 * 操作错误信息
	 */
	public $error = '';
	/**
	 * 是否执行查询前置方法
	 */
	public $isSelectBefore = true;

	/**
	 * 是否已初始化
	 * @var bool
	 */
	protected static $isInit = false;

	/**
	 * 缓存的字段信息，当MODEL_DYNAMIC_FIELDS_CACHE为true时启用
	 * @var array
	 */
	public static $cacheFields = array();

	/**
	 * 初始化
	 * @return mixed 
	 */
	public static function init()
	{
		self::$operations['page'] = array('custom'=>true);
		self::$operations['headTotal'] = array('onlyOne'=>true);
		self::$operations['footTotal'] = array('onlyOne'=>true);
		self::$isInit = true;
	}

	/**
	 * 构造方法
	 */
	function __construct($table = null, $dbAlias = null)
	{
		if(!self::$isInit)
		{
			self::init();
		}
		if (null === $table)
		{
			if('' === $this->table)
			{
				// 根据Model名称取出表名
				$this->table = strtolower(substr(preg_replace_callback(
						'/[A-Z]/',
						function($matches){
							return '_' . $matches[0];
						},
						substr(get_called_class(),0,-5)),1));
			}
		}
		else
		{
			$this->table($table);
		}
		if(null !== $dbAlias)
		{
			$this->dbAlias = $dbAlias;
		}
		if(null === $this->autoFields)
		{
			$this->autoFields = Config::get('@.MODEL_AUTO_FIELDS',true);
		}
		$this->db = Db::get($this->dbAlias);
		if(false !== $this->db)
		{
			if($this->db->isConnect())
			{
				// 表前缀
				$this->prefix = $this->db->tablePrefix;
				if($this->autoFields && $this->table != '')
				{
					$this->loadFields($this->fields,$this->fieldNames,$this->pk);
				}
			}
			else
			{
				throw new Exception($this->db->getError());
			}
		}
	}
	
	/**
	 * 获取Model实例对象
	 * @param type $model
	 * @param string $table 数据表名
	 * @return Model
	 */
	public static function obj($model='',$table=null)
	{
		$ref = new ReflectionClass(ucfirst($model) . 'Model');
		$args = func_get_args();
		unset($args[0]);
		return $ref->newInstanceArgs($args);
	}

	/**
	 * 设置模型数据库对象
	 * @param resource $db        	
	 */
	public function setDb($db)
	{
		$this->db = $db;
	}
	
	/**
	 * 获取模型数据库对象
	 * @return type
	 */
	public function getDb()
	{
		return $this->db;
	}
	
	/**
	 * 查询记录
	 * @param boolean $first 是否只获取一条记录
	 * @return array
	 */
	public function select($first = false)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$option = $this->getOption();
		$this->db->operationOption = $option;
		if($first)
		{
			$data = $this->db->getOne();
		}
		else
		{
			$data = $this->db->query();
			$this->__selectAfter($data,$option);
		}
		return $data;
	}

	/**
	 * 获取一条记录
	 * @return array 
	 */
	public function getOne()
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$option = $this->getOption();
		$this->db->operationOption = $option;
		$data = $this->db->getOne();
		$this->__selectOneAfter($data,$option);
		return $data;
	}

	/**
	 * 查询某一列数据
	 * @param string $columnName 
	 * @return array 
	 */
	public function selectColumn($columnName)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$option = $this->getOption();
		$option['field'] = array($columnName);
		$this->db->operationOption = $option;
		$data = $this->db->queryColumn();
		$this->__selectAfter($data,$option);
		return $data;
	}
	
	/**
	 * 分页查询，获取总记录数
	 * @param int $page
	 * @param int $show
	 * @param int $recordCount
	 * @return array
	 */
	public function selectPage($page = 1,$show = 10,&$recordCount = null)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$option = $this->operationOption;
		if(null === $recordCount)
		{
			if('Mysql' === $this->db->getType())
			{
				$isMysqlCount = true;
			}
			else
			{
				// 去除排序，提高效率
				if(isset($this->operationOption['order']))
				{
					unset($this->operationOption['order']);
				}
				$recordCount = $this->count();
				$this->operationOption = $option;
			}
		}
		if(isset($isMysqlCount) && $isMysqlCount)
		{
			$this->operationOption['fieldBefore'] = array('SQL_CALC_FOUND_ROWS');
		}
		$this->db->operationOption = $this->page($page,$show)->getOption();
		$data = $this->db->query();
		if(isset($isMysqlCount) && $isMysqlCount)
		{
			$recordCount = $this->db->getScalar('select FOUND_ROWS()');
		}
		$this->__selectAfter($data,$option);
		return $data;
	}

	/**
	 * 分页查询，获取总记录数
	 * @param int $page
	 * @param int $show
	 * @param int $recordCount
	 * @return array
	 */
	public function selectPageEx($page = 1,$show = 10,&$recordCount = null)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$option = $this->operationOption;
		if(null === $recordCount)
		{
			if('Mysql' === $this->db->getType())
			{
				$isMysqlCount = true;
			}
			else
			{
				// 去除排序，提高效率
				if(isset($this->operationOption['order']))
				{
					unset($this->operationOption['order']);
				}
				$recordCount = $this->count();
				$this->operationOption = $option;
			}
		}
		if(isset($isMysqlCount) && $isMysqlCount)
		{
			$this->operationOption['fieldBefore'] = array('SQL_CALC_FOUND_ROWS');
		}
		$this->operationOption['field'] = array($this->tableName() . '.' . $this->pk);
		$this->db->operationOption = $this->page($page,$show)->getOption();
		$pks = $this->db->queryColumn();
		if(!isset($pks[0]))
		{
			return array();
		}
		if(isset($isMysqlCount) && $isMysqlCount)
		{
			$recordCount = $this->db->getScalar('select FOUND_ROWS()');
		}
		$this->operationOption = $option;
		$this->operationOption['where'] = array(
			array($this->tableName() . '.' . $this->pk => array('in', $pks))
		);
		$this->db->operationOption = $this->getOption();
		$data = $this->db->query();
		$this->__selectAfter($data,$option);
		return $data;
	}

	/**
	 * 结尾方法自定义处理
	 * @param array $arguments 
	 */
	protected function __linkLast($arguments,$operation)
	{
		if (isset($arguments[0]))
		{
			$field = $arguments[0];
		}
		else
		{
			$field = '*';
		}
		$option = $this->getOption();
		$this->db->operationOption = $option;
		return $this->db->$operation($field);
	}

	/**
	 * 处理合计行数据
	 * @param array &$data
	 * @param array $option
	 * @param string $headOrFoot
	 */
	private function parseTotal(&$data,$option,$headOrFoot = null)
	{
		if(null === $headOrFoot)
		{
			if(isset($option['headTotal']))
			{
				$this->parseTotal($data,$option,'head');
			}
			if(isset($option['footTotal']))
			{
				$this->parseTotal($data,$option,'foot');
			}
		}
		else
		{
			unset($option['limit']);
			$k = $headOrFoot . 'total';
			$option['field'] = array();
			foreach($option[$k] as $array)
			{
				foreach($array as $key => $value)
				{
					if(is_array($value))
					{
						$option['field'][] = $value[0] . '(' . $key . ') as ' . $value[1];
					}
					else
					{
						$option['field'][] = $value . '(' . $key . ') as ' . $key;
					}
				}
			}
			if('head' === $headOrFoot)
			{
				$this->db->operationOption = $option;
				array_unshift($data,$this->db->getOne());
			}
			else if('foot' === $headOrFoot)
			{
				$this->db->operationOption = $option;
				$data[] = $this->db->getOne();
			}
		}
	}
	
	/**
	 * 查询获取值，getScalar的别名，兼容旧版
	 * @return mixed
	 */
	public function selectValue($field = null)
	{
		return $this->getScalar($field);
	}

	/**
	 * 查询获取值，getScalar的别名
	 * @return mixed
	 */
	public function getField($field = null)
	{
		return $this->getScalar($field);
	}

	/**
	 * 查询获取值，第一行第一列
	 * @return mixed 
	 */
	public function getScalar($field = null)
	{
		$option = $this->operationOption;
		$this->__getScalarBefore($option);
		if(null !== $field)
		{
			$this->operationOption['field'] = array($field);
		}
		$this->db->operationOption = $this->getOption();
		$result = $this->db->getScalar();
		$this->__getScalarAfter($result,$option);
		return $result;
	}
	
	/**
	 * 根据字段查询记录
	 * @param $field 字段名
	 * @param $value 字段值条件
	 * @param $table 表名/表别名，为空则为当前表名，为false不使用表名
	 * @return mixed
	 */
	public function selectBy($field, $value, $table = null)
	{
		if(null === $table)
		{
			$table = $this->tableName();
		}
		return $this->where(array((false === $table ? '' : ($table . '.')) . $field=>$value))->select();
	}
	/**
	 * 根据字段获取一条记录
	 * @param $field 字段名
	 * @param $value 字段值条件
	 * @param $table 表名/表别名，为空则为当前表名，为false不使用表名
	 * @return mixed
	 */
	public function getBy($field, $value, $table = null)
	{
		if(null === $table)
		{
			$table = $this->tableName();
		}
		return $this->where(array((false === $table ? '' : ($table . '.')) . $field=>$value))->getOne();
	}
	
	/**
	 * 随机获取记录，不依靠主键，效率略低。$num=1时效率比random高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function randomEx($num = 1)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$opt = $this->getOption();
		$field = isset($opt['field']) ? $opt['field'] : '*';
		$opt['field'] = array('count(*)');
		// 取记录数量
		$this->db->operationOption = $opt;
		$sum = $this->db->getScalar();
		$opt['field'] = $field;
		// 随机出记录位置
		$limits = randomNums(0, $sum - 1, $num);
		$results = array ();
		// 循环取出多条记录
		foreach ($limits as $value)
		{
			$opt['limit'] = array($value,1);
			$this->db->operationOption = $opt;
			$results[] = $this->db->getOne();
		}
		$this->__selectAfter($results,$opt);
		return $results;
	}

	/**
	 * 随机获取记录，依靠主键，效率高。$num=1时效率比randomEx低，$num>1时效率比randomEx高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function random($num = 1)
	{
		if($this->isSelectBefore)
		{
			$this->__selectBefore();
		}
		$opt = $this->getOption();
		$this->setOption($opt);
		$result = $this->field(array('count(*)'=>'count','max(' . $this->pk . ')'=>'max','min(' . $this->pk . ')'=>'min'))->getOne();
		$this->setOption($opt);
		$max_count=$result['max']-$result['count']+$num;
		// 随机出记录位置
		$limits = randomNums($result['min'], $result['max'], $max_count);
		$this->where(array($this->pk=>array('in',$limits)));
		$this->limit($num);
		$results = $this->orderByField($this->pk,$limits)->select();
		$this->__selectAfter($results,$opt);
		return $results;
	}
	/**
	 * 实现连贯操作
	 * @param type $name        	
	 * @param type $arguments        	
	 * @return Model
	 */
	public function __callBefore($name, $arguments)
	{
		if(isset($arguments[0]))
		{
			// getBy字段支持：
			$arr = explode('getBy',$name);
			if(''===$arr[0] && isset($arr[1]))
			{
				return $this->getBy($arr[1], $arguments[0]);
			}
			// selectBy字段支持：
			$arr = explode('selectBy',$name);
			if(''===$arr[0] && isset($arr[1]))
			{
				return $this->selectBy($arr[1], $arguments[0]);
			}
			unset($arr);
			// 全部转为小写，照顾所有大小写习惯的用户
			$name = strtolower($name);
		}
	}
	
	/**
	 * 添加数据
	 * @param array $data        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function add($data = null, $return = Db::RETURN_ISOK)
	{
		if(null === $data)
		{
			$data = $this->data;
		}
		$option = $this->getOption();
		$result = $this->__saveBefore($data,$option);
		if(null !== $result && true !== $result)
		{
			return false;
		}
		$result = $this->__addBefore($data,$option);
		if(null !== $result && true !== $result)
		{
			return false;
		}
		$saveData = $this->parseSaveData($data, Config::get('@.MODEL_AUTO_FIELDS'));
		$this->db->operationOption = $option;
		$saveResult = $this->db->insert(isset($option['table']) ? null : $this->tableName(), $saveData, $return);
		if(!$saveResult)
		{
			$this->error = '数据库操作失败';
			return false;
		}
		$result = $this->__saveAfter($data,$saveResult,$option);
		if(null === $result || true === $result)
		{
			$result = $this->__addAfter($data,$saveResult,$option);
			if(null === $result || true === $result)
			{
				return $saveResult;
			}
		}
		return false;
	}

	public function import($data = null, $return = Db::RETURN_ISOK)
	{
		if(null === $data)
		{
			$data = $this->data;
		}
		$option = $this->operationOption;
		$saveData = array();
		foreach($data as $index => $item)
		{
			$result = $this->__saveBefore($data[$index],$option);
			if(null !== $result && true !== $result)
			{
				return false;
			}
			$result = $this->__addBefore($data[$index],$option);
			if(null !== $result && true !== $result)
			{
				return false;
			}
			$saveData[$index] = $this->parseSaveData($data[$index], false);
		}
		$this->db->operationOption = $option;
		$this->operationOption = array();
		$saveResult = $this->db->insertBatch(isset($option['table']) ? null : $this->tableName(), $saveData, $return);
		if(!$saveResult)
		{
			$this->error = '数据库操作失败';
			return false;
		}
		$result = $this->__saveAfter($data,$saveResult,$option);
		if(null === $result || true === $result)
		{
			$result = $this->__addAfter($data,$saveResult,$option);
			if(null === $result || true === $result)
			{
				return $saveResult;
			}
		}
		return false;
	}
	
	/**
	 * 编辑数据
	 * @param array $data        	
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function edit($data = null, $return = Db::RETURN_ISOK)
	{
		if(null === $data)
		{
			$data = $this->data;
		}
		$option = $this->getOption();
		$result = $this->__saveBefore($data,$option,$option);
		if(null !== $result && true !== $result)
		{
			return false;
		}
		$result = $this->__editBefore($data,$option,$option);
		if(null !== $result && true !== $result)
		{
			return false;
		}
		$saveData = $this->parseSaveData($data, Config::get('@.MODEL_AUTO_FIELDS'));
		$this->db->operationOption = $option;
		$saveResult = $this->db->update(isset($option['table']) ? null : $this->tableName(), $saveData, $return);
		if(!$saveResult)
		{
			$this->error = '数据库操作失败';
			return false;
		}
		$result = $this->__saveAfter($data,$saveResult,$option);
		if(null === $result || true === $result)
		{
			$result = $this->__editAfter($data,$saveResult,$option);
			if(null === $result || true === $result)
			{
				return $saveResult;
			}
		}
		return false;
	}
	
	/**
	 * 保存数据，自动判断有主键则修改，没主键则插入
	 * @param type $data
	 * @param type $return
	 */
	public function save($data = null, $return = Db::RETURN_ISOK)
	{
		if(null === $data)
		{
			$data = $this->data;
		}
		$table = $this->getOptionTable();
		if(empty($this->fields) || $table !== $this->tableName())
		{
			$this->loadFields($fields, $fieldNames, $pk, $table);
		}
		else
		{
			$pk = $this->pk;
		}
		$pk = explode(',', $pk);
		$isEdit = true;
		foreach($pk as $tpk)
		{
			if(!array_key_exists($tpk, $data))
			{
				$isEdit = false;
				break;
			}
		}
		// 判断记录是否存在，来决定$isEdit的值
		if($isEdit)
		{
			$option = $this->operationOption;
			$isEdit = $this->wherePk($data,$table)->count() > 0;
			$this->operationOption = $option;
		}
		// 2个if不要合并！
		if($isEdit)
		{
			$result = $this->wherePk($data,$table)->edit($data,$return);
			if(Db::RETURN_INSERT_ID === $result)
			{
				return $pk[0];
			}
			else
			{
				return $result;
			}
		}
		else
		{
			return $this->add($data);
		}
	}
	
	/**
	 * 处理保存的数据
	 * @param type $data
	 */
	public function parseSaveData($data, $parseBindValue = false)
	{
		$table = $this->getOptionTable();
		if(empty($this->fields) || $table !== $this->tableName())
		{
			$this->loadFields($fields, $fieldNames, $pk, $table);
		}
		else
		{
			$fieldNames = $this->fieldNames;
		}
		$result = array();
		foreach($fieldNames as $field)
		{
			if(isset($data[$field]))
			{
				$result[$field] = $data[$field];
				if($parseBindValue)
				{
					$this->db->bindValue(':' . $this->db->parseParamName($field), $result[$field], $this->db->getParamType($this->fields[$field]['type']));
				}
			}
		}
		return $result;
	}
	
	/**
	 * 删除数据
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function delete($pkData = null,$return = Db::RETURN_ROWS)
	{
		if(null !== $pkData)
		{
			$this->wherePk($pkData,$this->getOptionTable());
		}
		$option = $this->getOption();
		$result = $this->__deleteBefore($pkData, $option);
		if(null !== $result && true !== $result)
		{
			return false;
		}
		$this->db->operationOption = $option;
		$deleteResult = $this->db->delete(isset($option['table']) ? null : $this->tableName(), $return);
		if($deleteResult <= 0)
		{
			$this->error = '删除失败';
			return false;
		}
		$result = $this->__deleteAfter($deleteResult, $option);
		if(null === $result || true === $result)
		{
			return $deleteResult;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * 获取连贯配置
	 * @return array
	 */
	public function getOption()
	{
		$option = $this->operationOption;
		// 清空连贯配置
		$this->operationOption = array ();
		if (! isset($option['table']))
		{
			// 未设置表明则为模型表名
			$option['table'] = $this->tableName();
		}
		$this->isSelectBefore = true;
		return $option;
	}

	/**
	 * 设置连贯操作配置
	 * @param array $option
	 */
	public function setOption($option)
	{
		$this->operationOption = $option;
	}
	
	/**
	 * 获取连贯操作配置中的表
	 */
	public function getOptionTable()
	{
		if(!isset($this->operationOption['table']))
		{
			return $this->tableName();
		}
		if(is_array($this->operationOption['table']))
		{
			foreach($this->operationOption['table'] as $table => $alias)
			{
				if(is_numeric($table))
				{
					return $alias;
				}
				else
				{
					return $table;
				}
			}
			return $this->tableName();
		}
		else
		{
			return $this->operationOption['table'];
		}
	}
	
	/**
	 * 设置或获取不包含前缀的数据表名
	 * @param string $table        	
	 * @return string
	 */
	public function table($table = null)
	{
		if (null !== $table)
		{
			$this->table = $table;
		}
		return $this->table;
	}
	
	/**
	 * 设置或获取数据表前缀
	 * @param string $prefix        	
	 * @return string
	 */
	public function prefix($prefix = null)
	{
		if (null !== $prefix)
		{
			$this->prefix = $prefix;
		}
		return $this->prefix;
	}
	
	/**
	 * 获取数据表全名
	 * @return string
	 */
	public function tableName($table=null)
	{
		if(null === $table)
		{
			return $this->prefix . $this->table;
		}
		else
		{
			return $this->prefix . $table;
		}
	}
	
	/**
	 * 字段映射处理
	 * @param array $data        	
	 * @param int $type        	
	 * @return array
	 */
	public function parseFieldsMap($data = null, $type = Model::TO_DB)
	{
		if (null === $data)
		{
			$data = $this->data;
		}
		foreach ($this->fieldsMap as $key => $value)
		{
			if (Model::TO_DB === $type)
			{
				if (isset($data[$key]))
				{
					$data[$value] = $data[$key];
					unset($data[$key]);
				}
			}
			else
			{
				if (isset($data[$value]))
				{
					$data[$key] = $data[$value];
					unset($data[$value]);
				}
			}
		}
		return $data;
	}
	
	/**
	 * 递增
	 * @param mixed $field        	
	 * @param mixed $num        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function inc($field, $num = 1, $return = Db::RETURN_ISOK)
	{
		$data = array ();
		// 参数都在field
		if (is_array($field))
		{
			// 数组参数，多个
			foreach ($field as $key => $value)
			{
				$f = $this->db->parseKeyword($key);
				$data = "{$f}={$f}+{$value}";
			}
		}
		else
		{
			// 单个单数
			$f = $this->db->parseKeyword($field);
			$data = "{$f}={$f}+{$num}";
		}
		$this->db->operationOption = $this->getOption();
		return $this->db->update($data, $return);
	}
	
	/**
	 * 递减
	 * @param mixed $field        	
	 * @param mixed $num        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function dec($field, $num = 1, $return = Db::RETURN_ISOK)
	{
		$data = array ();
		// 参数都在field
		if (is_array($field))
		{
			// 数组参数，多个
			foreach ($field as $key => $value)
			{
				$f = $this->db->parseKeyword($key);
				$data = "{$f}={$f}-{$value}";
			}
		}
		else
		{
			// 单个单数
			$f = $this->db->parseKeyword($field);
			$data = "{$f}={$f}-{$num}";
		}
		$this->db->operationOption = $this->getOption();
		return $this->db->update($data, $return);
	}
	
	/**
	 * 从表单创建数据并验证，返回验证结果
	 * @param mixed $rule        	
	 * @param boolean $all        	
	 * @return mixed
	 * @throws Exception
	 */
	public function fromForm($rule = 'default', $all = false)
	{
		// 获取规则
		if (! is_array($rule))
		{
			if (isset($this->rules[$rule]))
			{
				$rule = $this->rules[$rule];
			}
			else
			{
				return false;
			}
		}
		// 清空当前数据
		$this->data = array ();
		// 循环获取数据
		foreach ($rule as $value)
		{
			// 规则合法性判断
			if (isset($value['name']))
			{
				// 处理没有传入from的情况
				if (! isset($value['table']))
				{
					$value['table'] = '';
				}
			}
			else
			{
				// 判断数量是否正确
				if (count($value) < 3)
				{
					throw new Exception(Lang::get('MODEL_FORM_ARGS_LESS'));
					break;
				}
				$t = $value;
				$value = array ();
				// 直接传值处理
				$value['name'] = $t[0];
				if (isset($t[3]))
				{
					$value['table'] = $t[3];
				}
				else
				{
					$value['table'] = '';
				}
			}
			// 获取数据
			$d = Request::getAll($value['table'], $value['name']);
			// 判断获取是否成功
			if (false === $d)
			{
				// 判断是否有默认值
				if(array_key_exists('default',$value))
				{
					$this->data[$value['name']] = $value['default'];
				}
			}
			else
			{
				// 过滤器支持
				if(isset($value['filter']))
				{
					$this->data[$value['name']] = execFilter($d,$value['filter']);
				}
				else
				{
					$this->data[$value['name']] = execFilter($d,Config::get('@.DEFAULT_FILTER'));
				}
			}
		}
		// 开始验证
		$v = new Validator();
		// 保存验证结果
		$result = $v->validate($all, $this->data, $rule);
		if (array () === $result)
		{ // 验证通过
		  // 字段映射
			$this->data = $this->parseFieldsMap($this->data);
			return true;
		}
		else
		{
			// 清空数据
			$this->data = array ();
			// 返回验证结果
			return $result;
		}
	}
	
	/**
	 * 使用页码和取出数量计算出从哪里开始取
	 * @param int $page        	
	 * @param int $quantity        	
	 * @return int
	 */
	public function calcLimitStart($page, $quantity)
	{
		return max((int)(($page - 1) * $quantity),0);
	}

	/**
	 * 以主键为条件获取一条数据
	 * @param int $id
	 * @return array
	 */
	public function getByPk($value)
	{
		return $this->wherePk($value)
					->limit(1)
					->getOne();
	}

	/**
	 * 切换数据表
	 * @param string $table 可空，如果为空则为恢复之前的数据表
	 */
	public function switchTable($table=null)
	{
		static $tTable;
		if(null===$table)
		{
			if(null!==$tTable)
			{
				$this->table=$tTable;
				$tTable=null;
			}
		}
		else
		{
			$tTable=$this->table;
			$this->table=$table;
		}
		$this->loadFields($this->fields,$this->fieldNames,$this->pk);
	}
	/**
	 * 获取最后执行的sql语句
	 * @return string 
	 */
	public function lastSql()
	{
		return $this->db->lastSql;
	}
	/**
	 * 查询结果自动添加编号字段，从1开始编号
	 * @param string $field
	 */
	public function number($field)
	{
		$this->operationOption['number']=$field;
		return $this;
	}
	/**
	 * 生成select查询的SQL语句
	 * @param string $method 
	 * @return string 
	 */
	public function buildSql($method = 'select')
	{
		$this->db->operationOption = $this->getOption();
		switch($method)
		{
			case 'select':
				return $this->db->buildSelectSQL();
			case 'insert':
				return $this->db->buildInsertSQL();
			case 'update':
				return $this->db->buildUpdateSQL();
			case 'delete':
				return $this->db->buildDeleteSQL();
			default:
				return '';
		}
	}
	/**
	 * 加载字段数据
	 * @param array $fields
	 * @param array $fieldNames
	 * @param mixed $pk
	 * @param string $table
	 * @param bool $refresh
	 */
	public function loadFields(&$fields,&$fieldNames,&$pk,$table = null,$refresh = false)
	{
		if(null === $table)
		{
			$table = $this->tableName();
		}
		// 变量中动态缓存模型字段缓存读取
		if(Config::get('@.MODEL_DYNAMIC_FIELDS_CACHE'))
		{
			$data = $this->getDynamicCacheFields($table);
			if(null !== $data)
			{
				$fields = $data['fields'];
				$fieldNames = $data['fieldNames'];
				$pk = $data['pk'];
				return;
			}
		}
		$cacheName = 'Db/TableFields/' . $table;
		if($refresh || !Config::get('@.MODEL_FIELDS_CACHE'))
		{
			$fields = $this->db->getFields($table);
			if(Config::get('@.MODEL_FIELDS_CACHE'))
			{
				Cache::set($cacheName,$fields);
			}
		}
		else
		{
			$_this = $this;
			$isNewCache = false;
			$data = Cache::get($cacheName,function() use($_this,&$fields,&$fieldNames,&$pk,$table,&$isNewCache){
				$_this->loadFields($fields,$fieldNames,$pk,$table,true);
				$isNewCache = true;
			});
			if($isNewCache)
			{
				return;
			}
			else
			{
				$fields = $data;
			}
			unset($data);
		}
		
		$fieldNames = array_keys($fields);
		$pk = array();
		foreach($fields as $field)
		{
			if($field['pk'])
			{
				$pk[] = $field['name'];
			}
		}
		if(isset($pk[0]) && !isset($pk[1]))
		{
			$pk = $pk[0];
		}
		// 变量中动态缓存模型字段缓存保存
		if(Config::get('@.MODEL_DYNAMIC_FIELDS_CACHE'))
		{
			$this->setdynamicCacheFields($table, $fields, $fieldNames, $pk);
		}
	}

	/**
	 * 设置动态缓存字段信息
	 * @return void
	 */
	protected function setdynamicCacheFields($table, $fields, $fieldNames, $pk)
	{
		static::$cacheFields[$table] = array(
			'pk'			=>	$pk,
			'fields'		=>	$fields,
			'fieldNames'	=>	$fieldNames,
		);
	}

	/**
	 * 获取动态缓存字段信息，失败返回null
	 * @return array|null
	 */
	protected function getDynamicCacheFields($tableName)
	{
		return isset(static::$cacheFields[$tableName]) ? static::$cacheFields[$tableName] : null;
	}

	/**
	 * 加入主键条件
	 * @param mixed $pkData
	 * @param mixed $table 表名，为null时获取当前表名
	 * @param mixed $tableAlias 表别名，为null时使用当前表名，为false时不使用别名
	 * @return Model
	 */
	public function wherePk($pkData,$table = null,$tableAlias = null)
	{
		if(null === $table)
		{
			$table = $this->tableName();
		}
		if(null === $tableAlias)
		{
			$tableAlias = $table;
		}
		if(empty($this->fields) || $table !== $this->tableName())
		{
			$this->loadFields($fields, $fieldNames, $pk, $table);
		}
		else
		{
			$pk = $this->pk;
		}
		if(is_array($pk))
		{
			$where = array();
			$tWhere = &$where;
			foreach($pk as $pkName)
			{
				if(isset($pkData[$pkName]))
				{
					$tWhere['and'] = array((false === $tableAlias ? '' : ($tableAlias . '.')) . $pkName => $pkData[$pkName]);
					$tWhere = &$tWhere['and'];
				}
			}
		}
		else
		{
			$where = array((false === $tableAlias ? '' : ($tableAlias . '.')) . $pk=>is_array($pkData) ? $pkData[$pk] : $pkData);
		}
		return $this->where($where);
	}
	/**
	 * 查询记录前置方法
	 * @return mixed 
	 */
	public function __selectBefore()
	{

	}
	/**
	 * 查询多条记录后置方法
	 * @param array $data 
	 * @return mixed
	 */
	public function __selectAfter(&$data,$linkOption)
	{
		$this->parseTotal($data,$linkOption);
		foreach($data as $index => $value)
		{
			$this->__selectOneAfter($data[$index],$linkOption);
		}
	}
	/**
	 * 查询单挑记录后置方法
	 * @param array $data 
	 * @return mixed
	 */
	public function __selectOneAfter(&$data,$linkOption)
	{
		
	}
	/**
	 * 添加数据前置方法
	 * @param $data array 数据
	 * @return mixed
	 */
	public function __addBefore(&$data,$linkOption)
	{

	}
	/**
	 * 添加数据后置方法
	 * @param $data array 数据
	 * @param $result mixed 添加结果
	 * @return mixed
	 */
	public function __addAfter(&$data,$result,$linkOption)
	{

	}
	/**
	 * 修改数据前置方法
	 * @param $data array 数据
	 * @return mixed
	 */
	public function __editBefore(&$data,$linkOption)
	{
	}
	/**
	 * 修改数据后置方法
	 * @param $data array 数据
	 * @param $result mixed 修改结果
	 * @return mixed
	 */
	public function __editAfter(&$data,$result,$linkOption)
	{

	}
	/**
	 * 保存数据前置方法
	 * @param $data array 数据
	 * @return mixed
	 */
	public function __saveBefore(&$data,$linkOption)
	{
		
	}
	/**
	 * 保存数据后置方法
	 * @param $data array 数据
	 * @param $result mixed 保存结果
	 * @return mixed
	 */
	public function __saveAfter(&$data,$result,$linkOption)
	{

	}
	/**
	 * 删除数据前置方法
	 * @param array $pkData 
	 * @return mixed 
	 */
	public function __deleteBefore(&$pkData,$linkOption)
	{

	}
	/**
	 * 删除数据后置方法
	 * @param array $result 
	 * @return mixed 
	 */
	public function __deleteAfter($result,$linkOption)
	{

	}
	/**
	 * 查询获取值前置方法
	 * @param array $pkData 
	 * @return mixed 
	 */
	public function __getScalarBefore($linkOption)
	{

	}
	/**
	 * 查询获取值后置方法
	 * @param array $result 
	 * @return mixed 
	 */
	public function __getScalarAfter($result,$linkOption)
	{

	}
	/**
	 * 可设置取消查询前置方法
	 * @param bool $isSelectBefore 
	 * @return Model 
	 */
	public function selectBefore($isSelectBefore = true)
	{
		$this->isSelectBefore = $isSelectBefore;
		return $this;
	}

	/**
	 * 绑定参数
	 * @param mixed $parameter 
	 * @param mixed $value 
	 * @param int $data_type 
	 * @return Model
	 */
	public function bindValue($parameter, $value, $data_type = PDO::PARAM_STR)
	{
		$this->db->bindValue($parameter, $value, $data_type);
		return $this;
	}
}