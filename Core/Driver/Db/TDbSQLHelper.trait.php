<?php
trait TDbSQLHelper
{
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
	public $paramFlag = array ('','');

	/**
	 * 是否启用参数标识
	 * @var bool
	 */
	public $isUseParamFlag = true;

	/**
	 * 处理关键词
	 * @param string $keyword 
	 * @return string
	 */
	public function parseKeyword($keyword)
	{
		list($keyword, $alias) = explode(' as ', $keyword);
		if (false !== strpos($keyword, '(') && false !== strpos($keyword, ')'))
		{
			// 字段带函数
			$result = $keyword;
		}
		else
		{
			$names = explode('.', $keyword);
			$last = array_pop($names);
			if(isset($names[0]))
			{
				$result = $this->paramFlag[0] . implode($this->paramFlag[1] . '.' . $this->paramFlag[0],$names) . $this->paramFlag[1];
				$prefix = '.';
			}
			else
			{
				$prefix = '';
			}
			if('*' === $last)
			{
				$result .= $prefix . $last;
			}
			else
			{
				$result .= $prefix . $this->paramFlag[0] . $last . $this->paramFlag[1];
			}
		}
		if(null !== $alias)
		{
			$result .= ' as ' . $this->paramFlag[0] . $alias . $this->paramFlag[1];
		}
		return $result;
	}

	/**
	 * 获取真实操作符
	 * @param string $name 
	 * @return string 
	 */
	public function getOperator($name)
	{
		return isset($this->operators[$name]) ? $this->operators[$name] : $name;
	}
}