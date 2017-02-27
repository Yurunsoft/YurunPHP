<?php
/**
 * Soap控制器
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
abstract class SoapControl extends Control
{
	/**
	 * 服务首页动作名
	 */
	protected $index = 'index';
	/**
	 * wsdl文档动作名
	 */
	protected $wsdl = 'wsdl';
	/**
	 * 服务接口执行动作名
	 */
	protected $exec = 'exec';
	/**
	 * 服务接口测试页面动作名
	 */
	protected $test = 'test';
	/**
	 * wsdl中的Style
	 */
	protected $wsdlStyle = 'document';
	/**
	 * wsdl中的use
	 */
	protected $wsdlUse = 'literal';
	/**
	 * 是否启用缓存
	 */
	protected $cacheStatus = false;
	/**
	 * 缓存驱动对象
	 */
	protected $cacheInstance;
	/**
	 * 服务首页动作
	 */
	protected function __index($serviceName)
	{
		$ctw = new ClassToWsdl($serviceName,Dispatch::url($this->exec,array('serviceName'=>$serviceName)),$this->wsdlStyle,$this->wsdlUse);
		$this->view->serviceName = $serviceName;
		$this->view->service = $ctw->service;
		$this->view->wsdlUrl = Dispatch::url($this->wsdl,array('serviceName'=>$serviceName));
		$this->view->indexMCA = $this->index;
		$this->view->wsdlMCA = $this->wsdl;
		$this->view->execMCA = $this->exec;
		$this->view->testMCA = $this->test;
		$fileName = $this->view->getTemplateFile();
		if(!is_file($fileName))
		{
			$fileName = ROOT_PATH . 'Ex/Lib/Soap/Template/index.php';
		}
		$this->view->display($fileName);
	}
	/**
	 * wsdl文档
	 * @param $serviceName 服务名称
	 */
	public function __wsdl($serviceName)
	{
		Response::setMime('xml');
		echo $this->getWsdl($serviceName);
	}
	/**
	 * 服务接口执行
	 * @param $serviceName 服务名称
	 */
	public function __exec($serviceName)
	{
		try {
			$server = new SoapServer($this->getWsdlFileName($serviceName));
			$server->setObject(new SoapProxy($serviceName,$this->wsdlStyle,$this->wsdlUse));
			$server->handle();
		} catch (Exception $exception) {
			Log::add('Soap服务接口执行错误:'.$exception->getMessage().' 文件:'.$exception->getFile().' 行数:'.$exception->getLine());
		}
		Log::add(microtime(true) - YURUN_START);
	}
	/**
	 * 服务接口测试页面
	 * @param $serviceName 服务名称
	 * @param $method 方法名
	 */
	public function __test($serviceName,$methodName)
	{
		if(Request::method('get'))
		{
			$this->__testGet($serviceName,$methodName);
		}
		else
		{
			$this->__testPost($serviceName,$methodName);
		}
	}
	protected function __testGet($serviceName,$methodName)
	{
		$ctw = new ClassToWsdl($serviceName,Dispatch::url($this->exec,array('serviceName'=>$serviceName)),$this->wsdlStyle,$this->wsdlUse);
		if(!isset($ctw->service['methods'][$methodName]))
		{
			$this->returnData('方法不存在','html');
			exit;
		}
		$this->view->method = $ctw->service['methods'][$methodName];
		$this->view->serviceName = $serviceName;
		$this->view->methodName = $methodName;
		$this->view->indexUrl = Dispatch::url($this->index,array('serviceName'=>$serviceName));
		$fileName = $this->view->getTemplateFile();
		if(!is_file($fileName))
		{
			$fileName = ROOT_PATH . 'Ex/Lib/Soap/Template/test.php';
		}
		$this->view->display($fileName);
	}
	protected function __testPost($serviceName,$methodName)
	{
		try {
			$client = new SoapClient($this->getWsdlFileName($serviceName));
			$result = $client->$methodName(Request::post());
			$propName = $methodName . 'Result';
			$this->returnData(array('success'=>true,'result'=>print_r($result->$propName,true)));
		} catch (Exception $exception) {
			$message = $exception->getMessage();
			Log::add('Soap调用错误:'.$message.' 文件:'.$exception->getFile().' 行数:'.$exception->getLine());
			$this->returnData(array('success'=>false,'message'=>'Soap调用错误：' . $message));
		}
	}
	/**
	 * 创建缓存驱动实例
	 */
	protected function createCacheInstance()
	{
		$this->cacheInstance = Cache::getInstance();
		if(!method_exists($this->cacheInstance,'getFileName'))
		{
			$this->cacheInstance = Cache::create(array(
				'type' => 'File',
			));
		}
	}
	/**
	 * 获取wsdl缓存文件名
	 * @param $serviceName 服务名称
	 * @return string
	 */
	protected function getWsdlFileName($serviceName)
	{
		if(null === $this->cacheInstance)
		{
			$this->createCacheInstance();
		}
		$cacheAlias = 'WSDL' . $serviceName;
		$fileName = $this->cacheInstance->getFileName($cacheAlias);
		if(!is_file($fileName))
		{
			$this->getWsdlNoCache($serviceName);
		}
		return $fileName;
	}
	/**
	 * 获取wsdl文件内容，可能是缓存
	 * @param $serviceName 服务名称
	 * @return string
	 */
	protected function getWsdl($serviceName)
	{
		if($this->cacheStatus)
		{
			if(null === $this->cacheInstance)
			{
				$this->createCacheInstance();
			}
			$_this = $this;
			return $this->cacheInstance->get($cacheName,function()use($serviceName,$_this){
				return $_this->getWsdlNoCache($serviceName);
			},array('raw'=>true));
		}
		else
		{
			return $this->getWsdlNoCache($serviceName);
		}
	}
	/**
	 * 获取wsdl文件内容，无缓存
	 * @param $serviceName 服务名称
	 * @return string
	 */
	public function getWsdlNoCache($serviceName)
	{
		if(null === $this->cacheInstance)
		{
			$this->createCacheInstance();
		}
		$ctw = new ClassToWsdl($serviceName,Dispatch::url($this->exec,array('serviceName'=>$serviceName)),$this->wsdlStyle,$this->wsdlUse);
		$result = $ctw->getWsdl();
		$cacheName = 'WSDL' . $serviceName;
		$this->cacheInstance->set($cacheName,$result,array('raw'=>true));
		return $result;
	}
}