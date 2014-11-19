<?php
/**
 * 模型类
 * @author Yurun <admin@yurunsoft.com>
 */
class Model extends ArrayData
{
	// 读
	const TO_DB = 0;
	// 写
	const TO_FORM = 1;
	// 主键
	protected $pk = 'id';
	// 前缀
	protected $prefix = '';
	// 表名
	protected $table = '';
	// 数据库操作对象
	protected $db;
	// 字段映射
	protected $fieldsMap = array ();
	// 连贯操作
	protected $options = array ();
	// 连贯操作方法名
	protected $methods = array ('distinct','field','from','where','group','having','order','orderfield','limit','join');
	// 连贯操作函数
	protected $funcs = array ('sum','max','min','avg','count');
	// 从表单创建数据并验证的规则
	protected $rules = array ();
	/**
	 * 构造方法
	 */
	function __construct($table = '', $dbConfig = array())
	{
		if (! empty($table))
		{
			$this->table($table);
		}
		if (empty($dbConfig))
		{
			// 尝试获取数据库配置
			$dbConfig = Config::get('@.DB');
		}
		$this->db = Db::create(isset($dbConfig['type']) ? $dbConfig['type'] : Config::get('@.DB_DEFAULT_TYPE'), '', $dbConfig);
		// 表前缀
		$this->prefix=Config::get('@.DB.prefix');
	}
	
	/**
	 * 获取Model实例对象
	 *
	 * @param string $table
	 *        	数据表名
	 * @param type $model        	
	 * @return Model
	 */
	public static function obj($model,$table=null)
	{
		$ref = new ReflectionClass(ucfirst($model) . 'Model');
		$args = func_get_args();
		unset($args[0]);
		return $ref->newInstanceArgs($args);
	}

	/**
	 * 设置模型数据库对象
	 *
	 * @param resource $db        	
	 */
	public function setDb($db)
	{
		$this->db = $db;
	}
	
	/**
	 * 获取模型数据库对象
	 *
	 * @return type
	 */
	public function getDb()
	{
		return $this->db;
	}
	
	/**
	 * 查询记录
	 *
	 * @param boolean $first
	 *        	是否只获取一条记录
	 * @return array
	 */
	public function select($first = false)
	{
		$this->data = $this->db->select($this->getOption(), $first);
		return $this->data;
	}
	
	/**
	 * 查询获取值
	 *
	 * @return mixed
	 */
	public function selectValue()
	{
		return $this->db->selectValue($this->getOption());
	}
	
	/**
	 * 随机获取记录，不依靠主键，效率略低。$num=1时效率比random高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function randomEx($num = 1)
	{
		$opt = $this->getOption();
		$field = isset($opt['field']) ? $opt['field'] : '';
		$opt['field'] = 'count(*)';
		// 取记录数量
		$sum = $this->db->selectValue($opt);
		$opt['field'] = $field;
		// 随机出记录位置
		$limits = randomNums(0, $sum - 1, $num);
		$results = array ();
		// 循环取出多条记录
		foreach ($limits as $value)
		{
			$opt['limit'] = "{$value},1";
			$results[] = $this->db->select($opt, true);
		}
		return $results;
	}

	/**
	 * 随机获取记录，依靠主键，效率高。$num=1时效率比randomEx低，$num>1时效率比randomEx高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function random($num = 1)
	{
		$opt = $this->getOption();
		$field = isset($opt['field']) ? $opt['field'] : '';
		$opt['field'] = array('count(*)'=>'count',"max({$this->pk})"=>'max',"min({$this->pk})"=>'min');
		$result = $this->db->select($opt,true);
		$opt['field'] = $field;
		$max_count=$result['max']-$result['count'];
		if($max_count>0)
		{
			++$max_count;
		}
		// 随机出记录位置
		$limits = randomNums($result['min'], $result['max'], $max_count);
		if(isset($opt['where']))
		{
			if(is_array($opt['where']))
			{
				$opt['where'][$this->pk]=array('in',$limits);
			}
			else
			{
				$opt['where']=array('_exp'=>$opt['where'],$this->pk=>array('in',$limits));
			}
		}
		else
		{
			$opt['where']=array($this->pk=>array('in',$limits));
		}
		$opt['limit'] = $num;
		if(isset($opt['order']))
		{
			if(is_array($opt))
			{
				$opt['order'][]=array('_exp'=>"field({$this->pk},".implode(',',$limits).")");
			}
			else
			{
				$opt['order']=$opt['order'].",field({$this->pk},".implode(',',$limits).")";
			}
		}
		else
		{
			$opt['order']="field({$this->pk},".implode(',',$limits).")";
		}
		$results = $this->db->select($opt);
		return $results;
	}
	/**
	 * 实现连贯操作
	 *
	 * @param type $name        	
	 * @param type $arguments        	
	 * @return \Model
	 */
	public function __call($name, $arguments)
	{
		// 全部转为小写，照顾所有大小写习惯的用户
		$name = strtolower($name);
		// 方法名是否存在于预定义的连贯操作方法名中
		if (in_array($name, $this->methods) && count($arguments) > 0)
		{
			switch($name)
			{
				case 'join':
					// 为空则创建空数组
					if(!isset($this->options[$name]))
					{
						$this->options[$name]=array();
					}
					// 判断是否批量join
					if(is_array($arguments[0]))
					{
						$this->options[$name]=array_merge($this->options[$name],$arguments[0]);
					}
					else
					{
						if(count($arguments)>1)
						{
							// 参数形式
							$this->options[$name][]=array('type'=>$arguments[0],'table'=>$arguments[1],'on'=>$arguments[2]);
						}
						else
						{
							// sql形式
							$this->options[$name][]=$arguments[0];
						}
					}
					break;
				default:
					$this->options[$name] = $arguments[0];
					break;
			}
			return $this;
		}
		// 是否连贯操作函数
		else if (in_array($name, $this->funcs))
		{
			if (isset($arguments[0]))
			{
				$field = $arguments[0];
			}
			else
			{
				$field = '*';
			}
			$this->options['field'] = "{$name}({$field})";
			return $this->db->selectValue($this->getOption());
		}
	}
	
	/**
	 * 添加数据
	 *
	 * @param array $data        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function add($data = null, $return = DbBase::RETURN_ISOK)
	{
		return $this->db->insert($this->tableName(), $data === null ? $this->data : $data, $return);
	}
	
	/**
	 * 编辑数据
	 *
	 * @param array $data        	
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function edit($data = null, $return = DbBase::RETURN_ISOK)
	{
		return $this->db->update($data === null ? $this->data : $data, $this->getOption(), $return);
	}
	
	/**
	 * 删除数据
	 *
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function delete($return = DbBase::RETURN_ISOK)
	{
		return $this->db->delete($this->getOption(), $return);
	}
	
	/**
	 * 获取连贯配置
	 *
	 * @return array
	 */
	public function getOption()
	{
		$option = $this->options;
		// 清空连贯配置
		$this->options = array ();
		if (! isset($option['from']))
		{
			// 未设置表明则为模型表名
			$option['from'] = $this->tableName();
		}
		return $option;
	}
	
	/**
	 * 使用存储过程添加记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function addByP()
	{
		return $this->quickPOF($this->fields_add, func_get_args(), 'Proc', 'add');
	}
	
	/**
	 * 使用Function添加记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function addByF()
	{
		return $this->quickPOF($this->fields_add, func_get_args(), 'Function', 'add');
	}
	
	/**
	 * 使用存储过程编辑记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function editByP()
	{
		return $this->quickPOF($this->fields_edit, func_get_args(), 'Proc', 'edit');
	}
	
	/**
	 * 使用Function编辑记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function editByF()
	{
		return $this->quickPOF($this->fields_edit, func_get_args(), 'Function', 'edit');
	}
	
	/**
	 * 使用存储过程删除记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function deleteByP()
	{
		return $this->quickPOF($this->fields_delete, func_get_args(), 'Proc', 'delete');
	}
	
	/**
	 * 使用Function删除记录
	 *
	 * @param array $data        	
	 * @return mixed
	 */
	function deleteByF()
	{
		return $this->quickPOF($this->fields_delete, func_get_args(), 'Function', 'delete');
	}
	
	/**
	 * 存储过程和Function快捷操作
	 *
	 * @param array $fields        	
	 * @param array $data        	
	 * @param array $type        	
	 * @param array $operation        	
	 * @return mixed
	 */
	protected function quickPOF($fields, $data, $type, $operation)
	{
		$d = array ("{$operation}_{$this->table}");
		foreach ($fields as $value)
		{
			if (isset($data[$value]))
			{
				$d[] = $data[$value];
			}
			else
			{
				$d[] = $this->get($value);
			}
		}
		return call_user_func_array(array ($this->db,"exec{$type}"), $d);
	}
	
	/**
	 * 取一条记录
	 *
	 * @param array $config        	
	 * @param boolean $sqlMode
	 *        	是否是SQL语句
	 * @return array
	 */
	function find($config = array(), $sqlMode = false)
	{
		if ($sqlMode)
		{
			$this->data = $this->db->query($config);
		}
		else
		{
			if (isset($config['limit']))
			{
				if (! is_array($config['limit']))
				{
					$config['limit'] = explode(',', $config['limit']);
					if (count($config) === 1)
					{
						$config['limit'][0] = 1;
					}
					else
					{
						$config['limit'][1] = 1;
					}
				}
			}
			else
			{
				$config['limit'] = '1';
			}
			$this->data = $this->db->select($config, true);
		}
		if (is_array($this->data))
		{
			return $this->data;
		}
		else
		{
			$this->data = array ();
			return array ();
		}
	}
	
	/**
	 * 取多条数据
	 *
	 * @param array $config        	
	 * @param boolean $sqlMode
	 *        	是否是使用SQL语句
	 * @return array
	 */
	function findA($config = array(), $sqlMode = false)
	{
		if ($sqlMode)
		{
			$this->data = $this->db->queryA($config);
		}
		else
		{
			$this->data = $this->db->select($config);
		}
		return $this->data;
	}
	
	/**
	 * 设置或获取不包含前缀的数据表名
	 *
	 * @param string $table        	
	 * @return string
	 */
	public function table($table = null)
	{
		if ($table !== null)
		{
			$this->table=$table;
		}
		return $this->table;
	}
	
	/**
	 * 设置或获取数据表前缀
	 *
	 * @param string $prefix        	
	 * @return string
	 */
	public function prefix($prefix = null)
	{
		if ($prefix !== null)
		{
			$this->prefix = $prefix;
		}
		return $this->prefix;
	}
	
	/**
	 * 获取数据表全名
	 *
	 * @return string
	 */
	public function tableName($table=null)
	{
		if($table===null)
		{
			return $this->prefix.$this->table;
		}
		else
		{
			return $this->prefix.$table;
		}
	}
	
	/**
	 * 字段映射处理
	 *
	 * @param array $data        	
	 * @param int $type        	
	 * @return array
	 */
	public function parseFieldsMap($data = null, $type = Model::TO_DB)
	{
		if ($data === null)
		{
			$data = $this->data;
		}
		foreach ($this->fieldsMap as $key => $value)
		{
			if ($type === Model::TO_DB)
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
	 *
	 * @param mixed $field        	
	 * @param mixed $num        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function inc($field, $num = null, $return = DbBase::RETURN_ISOK)
	{
		$data = array ();
		if ($num === null)
		{ // 参数都在field
			if (is_array($field))
			{ // 数组参数，多个
				foreach ($field as $key => $value)
				{
					$f = $this->db->parseField($key);
					$data[$key] = array ('_exp' => "{$f}={$f}+{$value}");
				}
			}
			else
			{ // 单个单数
				$f = $this->db->parseField($field);
				$data[$field] = array ('_exp' => "{$f}={$f}+1");
			}
		}
		else
		{ // $field为字段名,$num为增加的值
			$f = $this->db->parseField($field);
			$data[$field] = array ('_exp' => "{$f}={$f}+{$num}");
		}
		return $this->db->update($data, $this->getOption(), $return);
	}
	
	/**
	 * 递减
	 *
	 * @param mixed $field        	
	 * @param mixed $num        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function dec($field, $num = null, $return = DbBase::RETURN_ISOK)
	{
		$data = array ();
		if ($num === null)
		{ // 参数都在field
			if (is_array($field))
			{ // 数组参数，多个
				foreach ($field as $key => $value)
				{
					$f = $this->db->parseField($key);
					$data[$key] = array ('_exp' => "{$f}={$f}-{$value}");
				}
			}
			else
			{ // 单个单数
				$f = $this->db->parseField($field);
				$data[$field] = array ('_exp' => "{$f}={$f}-1");
			}
		}
		else
		{ // $field为字段名,$num为减少的值
			$f = $this->db->parseField($field);
			$data[$field] = array ('_exp' => "{$f}={$f}-{$num}");
		}
		return $this->db->update($data, $this->getOption(), $return);
	}
	
	/**
	 * 从表单创建数据并验证，返回验证结果
	 *
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
				if (! isset($value['from']))
				{
					$value['from'] = '';
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
					$value['from'] = $t[3];
				}
				else
				{
					$value['from'] = '';
				}
			}
			// 获取数据
			$d = Request::getAll($value['from'], $value['name']);
			// 判断获取是否成功
			if ($d === false)
			{
				// 判断是否有默认值
				if(isset($value['default']))
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
		if ($result === array ())
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
	 * 
	 * @param int $page        	
	 * @param int $quantity        	
	 * @return int
	 */
	public function calcLimitStart($page, $quantity)
	{
		return (int)(($page - 1) * $quantity);
	}

	/**
	 * 以主键为条件获取一条数据
	 * @param int $id
	 * @return array
	 */
	public function getByPk($value)
	{
		return $this->where(array($this->pk=>$value))
					->limit(1)
					->select(true);
	}

	/**
	 * 切换数据表
	 * @param string $table 可空，如果为空则为恢复之前的数据表
	 */
	public function switchTable($table=null)
	{
		static $tTable;
		if($table===null)
		{
			if($tTable!==null)
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
	}
}