<?php
trait TLinkOperation
{
	/**
	 * 链式操作数据
	 * @var array
	 */
	public $operationOption = array();

	/**
	 * 链式操作列表
	 * @var array
	 */
	public static $operations = array(
		'distinct'		=>	array('onlyOne'=>true),
		'field'			=>	array('merge'=>true),
		'from'			=>	array('alias'=>'table','merge'=>true),
		'table'			=>	array('merge'=>true),
		'where'			=>	array('merge'=>true),
		'group'			=>	array('merge'=>true),
		'having'		=>	array('merge'=>true),
		'order'			=>	array('merge'=>true),
		'orderBy'		=>	array('alias'=>'order'),
		'orderByField'	=>	array('custom'=>true),
		'limit'			=>	array('onlyOne'=>true),
		'join'			=>	array(),
		'innerJoin'		=>	array('custom'=>true),
		'leftJoin'		=>	array('custom'=>true),
		'rightJoin'		=>	array('custom'=>true),
		'crossJoin'		=>	array('custom'=>true),
		'page'			=>	array('onlyOne'=>true),
		'headTotal'		=>	array(),
		'footTotal'		=>	array(),
		'params'		=>	array('custom'=>true),
	);

	/**
	 * 魔术方法，实现链式操作
	 * @param string $name        	
	 * @param array $arguments        	
	 * @return Model
	 */
	public function __call($name, $arguments)
	{
		if(method_exists($this,'__callBefore'))
		{
			$result = $this->__callBefore($name, $arguments);
			if(null !== $result)
			{
				return $result;
			}
		}
		if(isset(self::$operations[$name]))
		{
			// 操作别名
			if(isset(self::$operations[$name]['alias']))
			{
				$operationName = self::$operations[$name]['alias'];
			}
			else
			{
				$operationName = $name;
			}
			// 支持链式操作
			if(isset(self::$operations[$name]['onlyOne']) && self::$operations[$name]['onlyOne'])
			{
				// 只存一次
				$this->operationOption[$operationName] = $arguments;
			}
			else
			{
				// 允许存多次
				if(isset(self::$operations[$name]['custom']) && self::$operations[$name]['custom'])
				{
					// 自定义操作
					$this->{'__link' . ucfirst($name)}($arguments);
				}
				else
				{
					// 默认操作
					if(!isset($this->operationOption[$operationName]))
					{
						$this->operationOption[$operationName] = array();
					}
					if(isset(self::$operations[$name]['merge']) && self::$operations[$name]['merge'])
					{
						$this->operationOption[$operationName] = array_merge($this->operationOption[$operationName],$arguments);
					}
					else
					{
						$this->operationOption[$operationName][] = $arguments;
					}
				}
			}
			return $this;
		}
		if(method_exists($this,'__callAfter'))
		{
			return $this->__callAfter($name, $arguments);
		}
	}

	/**
	 * orderByField 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkOrderByField($arguments)
	{
		if(!isset($this->operationOption['order']))
		{
			$this->operationOption['order'] = array();
		}
		$arguments['type'] = 'field';
		$this->operationOption['order'][] = $arguments;
	}

	/**
	 * xxxJoin自定义处理
	 * @param array $arguments 
	 * @param string $method 
	 */
	protected function __parseLinkJoin($arguments, $method)
	{
		if(!isset($this->operationOption['join']))
		{
			$this->operationOption['join'] = array();
		}
		array_unshift($arguments,$method);
		$this->operationOption['join'][] = $arguments;
	}

	/**
	 * innerJoin 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkInnerJoin($arguments)
	{
		$this->__parseLinkJoin($arguments,'inner');
	}

	/**
	 * leftJoin 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkLeftJoin($arguments)
	{
		$this->__parseLinkJoin($arguments,'left');
	}

	/**
	 * rightJoin 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkRightjoin($arguments)
	{
		$this->__parseLinkJoin($arguments,'right');
	}

	/**
	 * crossJoin 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkCrossjoin($arguments)
	{
		$this->__parseLinkJoin($arguments,'cross');
	}

	/**
	 * params 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkParams($arguments)
	{
		if(isset($arguments[0]))
		{
			$this->operationOption['params'] = $arguments[0];
		}
	}
	/**
	 * page 自定义处理
	 * @param array $arguments 
	 */
	protected function __linkPage($arguments)
	{
		if(isset($arguments[1]))
		{
			$this->limit($this->calcLimitStart($arguments[0], $arguments[1]),$arguments[1]);
		}
	}
}