<?php
/**
 * 控制器类
 * @author Yurun <admin@yurunsoft.com>
 */
class Control
{
	protected $db;
	protected $view;
	function __construct()
	{
		$this->view = new View(Config::get('@.THEME_ON')?Config::get('@.THEME'):null,$this);
	}
	/**
	 * 返回数据，一般可用于ajax返回json或xml
	 * @param array $data			数据
	 * @param string $returnType	返回类型，默认从配置CONTROL_RETURN_TYPE中读取
	 * @param mixed $option			json_encode函数参数
	 */
	public function returnData($data,$returnType = null,$option = 0)
	{
		if(null === $returnType)
		{
			$returnType = Config::get('@.CONTROL_RETURN_TYPE');
		}
		$returnType = strtolower($returnType);
		if('json' === $returnType)
		{
			Response::setMime('json');
			exit(json_encode($data,$option));
		}
		else if('xml' === $returnType)
		{
			Response::setMime('xml');
			exit(wddx_serialize_value($data));
		}
		else if('html' === $returnType)
		{
			Response::setMime('html');
			echo $data;
		}
		else
		{
			$eventData = array($data,$returnType);
			Event::trigger('YURUN_RETURN_DATA',$eventData);
		}
	}
}