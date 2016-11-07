<?php
/**
 * 模型类
 * @author Yurun <admin@yurunsoft.com>
 */
class Model extends ArrayData
{
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
	// 主键
	protected $pk = 'id';
	// 前缀
	protected $prefix ='';
	// 表名
	protected $table = '';
	// 数据库操作对象
	protected $db;
	// 字段映射
	protected $fieldsMap = array (
			// '表单字段'=>'数据库字段'
	);
	// 连贯操作
	protected $options = array ();
	// 连贯操作方法名
	protected $methods = array ('distinct','field','from','where','group','having','order','orderfield','limit','join','page','headtotal','foottotal');
	// 连贯操作函数
	protected $funcs = array ('sum','max','min','avg','count');
	// 从表单创建数据并验证的规则
	protected $rules = array ();
	/**
	 * 构造方法
	 */
	function __construct($table = null, $dbConfig = array())
	{
		if (null === $table)
		{
			if('' === $this->table)
			{
				// 根据Model名称自动
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
		if (empty($dbConfig))
		{
			// 尝试获取数据库配置
			$dbConfig = Config::get('@.DB');
		}
		if(!empty($dbConfig))
		{
			$this->db = Db::create(isset($dbConfig['type']) ? $dbConfig['type'] : Config::get('@.DB_DEFAULT_TYPE'), '', $dbConfig);
			if(false!==$this->db && !$this->db->isConnect())
			{
				throw new Exception($this->db->getError());
			}
		}
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
	public static function &obj($model='',$table=null)
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
	public function &select($first = false)
	{
		$option = $this->getOption();
		$data = $this->db->select($option, $first);
		$this->parseTotal($data,$option);
		return $data;
	}
	
	/**
	 * 分页查询，获取总记录数
	 * @param unknown $recordCount
	 */
	public function &selectPage($page = 1,$show = 10,&$recordCount = 0)
	{
		$option = $this->options;
		$recordCount = $this->count();
		$this->options = $option;
		$data = $this->db->select($this->page($page,$show)->getOption(), false);
		$this->parseTotal($data,$option);
		return $data;
	}
	
	private function parseTotal(&$data,$option,$headOrFoot = null)
	{
		if(null === $headOrFoot)
		{
			if(isset($option['headtotal']))
			{
				$this->parseTotal($data,$option,'head');
			}
			if(isset($option['foottotal']))
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
				array_unshift($data,$this->db->select($option, true));
			}
			else if('foot' === $headOrFoot)
			{
				$data[] = $this->db->select($option, true);
			}
		}
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
	 * 根据字段查询记录
	 *
	 * @return mixed
	 */
	public function &selectBy($field,$value)
	{
		return $this->where(array($field=>$value))->select();
	}
	/**
	 * 根据字段获取一条记录
	 *
	 * @return mixed
	 */
	public function &getBy($field,$value)
	{
		return $this->where(array($field=>$value))->select(true);
	}
	
	/**
	 * 随机获取记录，不依靠主键，效率略低。$num=1时效率比random高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function &randomEx($num = 1)
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
		if('Mssql' === $this->db->getType() && !isset($opt['order']))
		{
			$opt['order'] = array($this->pk);
		}
		// 循环取出多条记录
		foreach ($limits as $value)
		{
			$opt['limit'] = $value . ',1';
			$results[] = $this->db->select($opt, true);
		}
		return $results;
	}

	/**
	 * 随机获取记录，依靠主键，效率高。$num=1时效率比randomEx低，$num>1时效率比randomEx高。
	 * @param int $num 获取记录数量，默认为1条
	 * @return array
	 */
	public function &random($num = 1)
	{
		$opt = $this->getOption();
		$this->setOption($opt);
		$result = $this->field(array('count(*)'=>'count','max(' . $this->pk . ')'=>'max','min(' . $this->pk . ')'=>'min'))->select(true);
		$this->setOption($opt);
		$max_count=$result['max']-$result['count']+$num;
		// 随机出记录位置
		$limits = randomNums($result['min'], $result['max'], $max_count);
		$this->where(array($this->pk=>array('in',$limits)));
		$this->limit($num);
		$results = $this->orderfield($this->pk,$limits)->select();
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
			// 方法名是否存在于预定义的连贯操作方法名中
			if (false !== in_array($name, $this->methods))
			{
				if('join' === $name)
				{
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
							$this->options[$name][] = array('type'=>$arguments[0],'table'=>$arguments[1],'on'=>$arguments[2]);
						}
						else
						{
							// sql形式
							$this->options[$name][] = $arguments[0];
						}
					}
				}
				else if('limit' === $name)
				{
					if(isset($arguments[1]))
					{
						$this->options['limit'] = $arguments;
					}
					else
					{
						$this->options['limit'] = $arguments[0];
					}
				}
				else if('page' === $name)
				{
					if(isset($arguments[1]))
					{
						$this->options['limit'] = array($this->calcLimitStart($arguments[0], $arguments[1]),$arguments[1]);
					}
				}
				else if('distinct' === $name)
				{
					$this->options['distinct'] = $arguments[0];
				}
				else if('orderfield' === $name)
				{
					if(!isset($this->options['order']))
					{
						$this->options['order'] = array();
					}
					if(isset($arguments[1]))
					{
						$this->options['order'][] = array('#orderfield#'=>true,'data'=>$arguments);
					}
					else
					{
						$this->options['order'][] = array('#orderfield#'=>true,'data'=>$arguments[0]);
					}
				}
				else
				{
					if(!isset($this->options[$name]))
					{
						$this->options[$name] = array();
					}
					$this->options[$name][] = $arguments[0];
				}
				return $this;
			}
		}
		// 是否连贯操作函数
		if (false !== in_array($name, $this->funcs))
		{
			if (isset($arguments[0]))
			{
				$field = $arguments[0];
			}
			else
			{
				$field = '*';
			}
			$this->options['field'] = $name . '(' . $field . ')';
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
	public function add($data = null, $return = Db::RETURN_ISOK)
	{
		$option=$this->getOption();
		return $this->db->insert(isset($option['from'])?$option['from']:$this->tableName(), null === $data ? $this->data : $data, $return);
	}
	
	/**
	 * 编辑数据
	 *
	 * @param array $data        	
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function edit($data = null, $return = Db::RETURN_ISOK)
	{
		return $this->db->update(null === $data ? $this->data : $data, $this->getOption(), $return);
	}
	
	/**
	 * 删除数据
	 *
	 * @param array $condition        	
	 * @param int $return        	
	 * @return mixed
	 */
	public function delete($return = Db::RETURN_ISOK)
	{
		return $this->db->delete($this->getOption(), $return);
	}
	
	/**
	 * 获取连贯配置
	 *
	 * @return array
	 */
	public function &getOption()
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
	 * 设置连贯操作配置
	 * @param array $option
	 */
	public function setOption($option)
	{
		$this->options=$option;
	}
	
	/**
	 * 取一条记录
	 *
	 * @param array $config        	
	 * @param boolean $sqlMode
	 *        	是否是SQL语句
	 * @return array
	 */
	function &find($config = array(), $sqlMode = false)
	{
		if ($sqlMode)
		{
			$data = $this->db->query($config);
		}
		else
		{
			if (isset($config['limit']))
			{
				if (! is_array($config['limit']))
				{
					$config['limit'] = explode(',', $config['limit']);
					if (1 === count($config))
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
			$data = $this->db->select($config, true);
		}
		if (!is_array($data))
		{
			$data = array();
		}
		return $data;
	}
	
	/**
	 * 取多条数据
	 *
	 * @param array $config        	
	 * @param boolean $sqlMode
	 *        	是否是使用SQL语句
	 * @return array
	 */
	function &findA($config = array(), $sqlMode = false)
	{
		if ($sqlMode)
		{
			return $this->db->queryA($config);
		}
		else
		{
			return $this->db->select($config);
		}
	}
	
	/**
	 * 设置或获取不包含前缀的数据表名
	 *
	 * @param string $table        	
	 * @return string
	 */
	public function table($table = null)
	{
		if (null !== $table)
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
		if (null !== $prefix)
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
		if(null === $table)
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
	public function &parseFieldsMap($data = null, $type = Model::TO_DB)
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
	 *
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
				$f = $this->db->parseField($key);
				$data[] = "{$f}={$f}+{$value}";
			}
		}
		else
		{
			// 单个单数
			$f = $this->db->parseField($field);
			$data[] = "{$f}={$f}+{$num}";
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
	public function dec($field, $num = 1, $return = Db::RETURN_ISOK)
	{
		$data = array ();
		// 参数都在field
		if (is_array($field))
		{
			// 数组参数，多个
			foreach ($field as $key => $value)
			{
				$f = $this->db->parseField($key);
				$data[] = "{$f}={$f}-{$value}";
			}
		}
		else
		{
			// 单个单数
			$f = $this->db->parseField($field);
			$data[] = "{$f}={$f}-{$num}";
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
	 * 
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
	public function &getByPk($value)
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
	}
	public function lastSql()
	{
		return $this->db->lastSql();
	}
	/**
	 * 查询结果自动添加编号字段，从1开始编号
	 * @param unknown $field
	 */
	public function number($field)
	{
		$this->options['number']=$field;
		return $this;
	}
	/**
	 * 生成select查询的SQL语句
	 */
	public function buildSql()
	{
		return $this->db->parseSelectOption($this->getOption());
	}
}