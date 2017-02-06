<?php
/**
 * YurunPHP控制器基类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class Control
{
	/**
	 * 视图层对象
	 */
	protected $view;
	/**
	 * 构造方法
	 */
	function __construct()
	{
		// 实例化视图层对象
		$this->view = new View(Config::get('@.THEME_ON')?Config::get('@.THEME'):null,$this);
	}
	/**
	 * 返回数据，一般可用于ajax返回json或xml
	 * @param array $data			数据
	 * @param string $returnType	返回类型，默认从配置CONTROL_RETURN_TYPE中读取
	 * @param mixed $option			json_encode函数参数
	 */
	public function returnData($data,$returnType = null,$option = null)
	{
		if(null === $returnType)
		{
			$returnType = Config::get('@.CONTROL_RETURN_TYPE');
		}
		$returnType = strtolower($returnType);
		if('json' === $returnType)
		{
			Response::setMime('json');
			exit(json_encode($data,null === $option?0:$option));
		}
		else if('xml' === $returnType)
		{
			Response::setMime('xml');
			exit(ArrayToXML::parse($data,null === $option?'xml':$option));
		}
		else if('html' === $returnType)
		{
			Response::setMime('html');
			echo $data;
		}
		else
		{
			$eventData = array('data'=>$data,'returnType'=>$returnType,'option'=>$option);
			Event::trigger('YURUN_RETURN_DATA',$eventData);
		}
	}
}