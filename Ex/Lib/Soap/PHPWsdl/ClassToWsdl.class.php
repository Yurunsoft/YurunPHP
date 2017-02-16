<?php
class ClassToWsdl
{
	public $namespace = '';
	public $class = '';
	public $soapUrl = '';
	public $service = array();
	public $types = array();
	public $style;
	public $use;
	/**
	 * @var WSDL
	 */
	public $wsdl;
	public function __construct($class,$soapUrl,$style = null,$use = null)
	{
		$this->parse($class,$soapUrl,$style,$use);
	}
	public function parse($class,$soapUrl,$style = null,$use = null)
	{
		$this->style = null === $style ? 'document' : $style;
		$this->use = null === $use ? 'literal' : $use;
		$this->service = array();
		$this->types = array();
		$this->class = $class;
		$this->soapUrl = $soapUrl;
		$reflection = new ReflectionClass($class);
		// 处理注释
		$comment = $this->parseComment($reflection->getDocComment());
		// 命名空间
		$count = preg_match('/@namespace\s*([^\s]+)/', $comment, $matches);
		if(0 === $count)
		{
			$this->namespace = 'http://tempuri.org/';
		}
		else
		{
			if('/' === substr($matches[1],-1,1))
			{
				$this->namespace = $matches[1];
			}
			else
			{
				$this->namespace = $matches[1] . '/';
			}
		}
		// 类注释
		$this->service['description'] = $this->getCommentContent($comment);
		// 处理方法
		$this->service['methods'] = array();
		foreach ($reflection->getMethods() as $method)
		{
			if ($method->isPublic())
			{
				$this->parseMethod($class,$method);
			}
        }
		$this->wsdl = null;
	}
	public function getWsdl()
	{
		if(null === $this->wsdl)
		{
			$this->wsdl = new WSDL($soapUrl,$this->namespace);
			$soapName = $this->class . 'Soap';
			// PortType
			$portType = $this->wsdl->addPortType($soapName);
			// Binding
			$binding = $this->wsdl->addBinding($soapName,$soapName);
			$this->wsdl->addSoapBinding($binding);
			foreach($this->service['methods'] as $methodName => $method)
			{
				// 请求参数
				$elements = array();
				foreach($method['params'] as $paramName => $param)
				{
					$elements[] = array(
						'name'		=>	$paramName,
						'minOccurs'	=>	'1',
						'maxOccurs'	=>	'1',
						'type'		=>	$param['type']
					);
				}
				$this->wsdl->addElement($methodName,$elements);
				$inName = $methodName . 'SoapIn';
				$this->wsdl->addMessage($inName,array(
					array('name'=>'parameters','element'=>$methodName)
				));
				// 返回参数
				$responseName = $methodName . 'Response';
				$this->wsdl->addElement($responseName,array(
					array(
						'name'		=>	$methodName . 'Result',
						'minOccurs'	=>	'1',
						'maxOccurs'	=>	'1',
						'type'		=>	$method['return']['type']
					)
				));
				$outName = $methodName . 'SoapOut';
				$this->wsdl->addMessage($outName,array(
					array('name'=>'parameters','element'=>$responseName)
				));
				// PortType
				$this->wsdl->addPortOperation($portType,$methodName,array('message'=>$inName),array('message'=>$outName));
				// Binding
				$operation = $this->wsdl->addBindingOperation($binding,$methodName);
				$this->wsdl->addBindingSoapOperation($operation,array(
					'soapAction'	=>	$this->wsdl->getNS($methodName),
					'style'			=>	$this->style
				));
				$body = array(
					'use'	=>	$this->use
				);
				if('encoded' === $this->use)
				{
					$body['encodingStyle'] = 'http://schemas.xmlsoap.org/soap/encoding/';
				}
				$this->wsdl->addBindingBody($operation,
					array(
						'body'	=>	$body
					),
					array(
						'body'	=>	$body
					)
				);
			}
			// Service
			$service = $this->wsdl->addService($this->class);
			$this->wsdl->addServicePort($service,$soapName,$soapName,$this->soapUrl);
		}
		return $this->wsdl->getWsdl();
	}
	public function saveWsdl($filePath)
	{
		file_put_contents($filePath,$this->getWsdl(),LOCK_EX);
	}
	private function parseMethod($class,$method)
	{
		$comment = $method->getDocComment();
		// 必须带有@soap注释才可以作为服务方法
		if (false === strpos($comment, '@soap'))
        {
			return;
		}
		// 处理注释
		$comment = $this->parseComment($comment);
		// 方法名
		$methodName = $method->getName();
		$methodData = array(
			'comment'	=>	'',
			'params'	=>	array(),
			'return'	=>	array('type'=>'','elements'=>array())
		);
		// 方法参数
		$params = $method->getParameters();
		$count = preg_match_all('/@param\s*([^\s]+)\s*([^\s]+)\s*([^\s]*)/', $comment, $matches);
		// 判断注释中的参数个数是否正确
		if(!isset($params[$count - 1]))
		{
			throw new Exception('Soap解析错误，' . $class . '::' . $methodName . '()参数个数不正确');
		}
		// 处理方法参数
		foreach($params as $index => $param)
		{
			$paramName = $param->getName();
			$methodData['params'][$paramName] = array(
				'type'		=>	$matches[1][$index],
				'comment'	=>	$matches[3][$index],
			);
		}
		// 处理方法返回值
		$count = preg_match('/@return\s*([^\s]+)\s*([^\s]*)/', $comment, $matches);
		if(0 === $count)
		{
			$methodData['return']['type'] = null;
		}
		else
		{
			$methodData['return']['type'] = $matches[1];
		}
		// 方法注释
		$methodData['comment'] = $this->getCommentContent($comment);
		$this->service['methods'][$methodName] = $methodData;
	}
	private function parseComment($comment)
	{
		return trim(preg_replace('/^\/*\s*\**(\s*?$|\s*\**\/*)/m', '', $comment));
	}
	private function getCommentContent($comment)
	{
		if(preg_match_all('/^[^@](.*)$/m', $comment, $matches) > 0)
		{
			return implode("\r\n",$matches[0]);
		}
		else
		{
			return '';
		}
	}
}