<?php
class WSDL
{
	public $xml;
	public $namespace = '';
	public $soapUrl = '';
	public $root;
	public $schema;
	public static $types = array(
		'int'				=>	's:int',
		'integer'			=>	's:integer',
		'long'				=>	's:long',
		'unsignedLong'		=>	's:unsignedLong',
		'short'				=>	's:short',
		'unsignedShort'		=>	's:unsignedShort',
		'float'				=>	's:float',
		'double'			=>	's:float',
		'bool'				=>	's:boolean',
		'boolean'			=>	's:boolean',
		'string'			=>	's:string',
		'date'				=>	's:date',
		'time'				=>	's:time',
		'datetime'			=>	's:dateTime',
		'array'				=>	'soap-enc:Array',
		'object'			=>	's:struct',
		'mixed'				=>	's:anyType',
		'normalizedString'	=>	's:normalizedString',
		'token'				=>	's:token',
		'Name'				=>	's:Name',
		'QName'				=>	's:QName',
		'NMTOKEN'			=>	's:NMTOKEN',
		'NMTOKENS'			=>	's:NMTOKENS',
		'byte'				=>	's:byte',
		'unsignedByte'		=>	's:unsignedByte',
		'base64Binary'		=>	's:base64Binary',
		'hexBinary'			=>	's:hexBinary',
		'unsignedInt'		=>	's:unsignedInt',
		'positiveInteger'	=>	's:positiveInteger',
		'negativeInteger'	=>	's:negativeInteger',
		'nonNegativeInteger'=>	's:nonNegativeInteger',
		'nonPositiveInteger'=>	's:nonPositiveInteger',
		'decimal'			=>	's:decimal',
		'duration'			=>	's:duration',
		'anyURI'			=>	's:anyURI',
	);
	public $customTypes = array();
	public function __construct($soapUrl,$namespace = 'http://tempuri.org/')
	{
		$this->soapUrl = $soapUrl;
		$this->namespace = $namespace;
		$this->xml = new DomDocument('1.0', 'UTF-8');
		$this->xml->loadXml(<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<wsdl:definitions xmlns:soap="http://schemas.xmlsoap.org/wsdl/soap/"
				  xmlns:tns="{$this->namespace}"
				  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
				  xmlns:s="http://www.w3.org/2001/XMLSchema"
				  targetNamespace="{$this->namespace}"
>
</wsdl:definitions>
XML
);
		$this->root = $this->xml->documentElement;
		$types = $this->xml->createElement('wsdl:types');
		$this->schema = $this->xml->createElement('s:schema');
		$this->schema->setAttribute('targetNamespace',$this->namespace);
		$this->schema->setAttribute('elementFormDefault','qualified');
		$types->appendChild($this->schema);
		$this->root->appendChild($types);
	}
	public function addComplexType($name,$elements = array())
	{
		$complexType = $this->xml->createElement('s:complexType');
		$complexType->setAttribute('name',$name);
		$sequence = $this->xml->createElement('s:sequence');
		$complexType->appendChild($sequence);
		foreach($elements as $element)
		{
			$elementNode = $this->xml->createElement('s:element');
			$elementNode->setAttribute('name',isset($element['name']) ? $element['name'] : '');
			$elementNode->setAttribute('minOccurs',isset($element['minOccurs']) ? $element['minOccurs'] : '0');
			$elementNode->setAttribute('maxOccurs',isset($element['maxOccurs']) ? $element['maxOccurs'] : 'unbounded');
			if(isset($element['nillable']))
			{
				$elementNode->setAttribute('nillable',$element['nillable']);
			}
			$elementNode->setAttribute('type',$this->getWsdlType(isset($element['type']) ? $element['type'] : 'string'));
			$sequence->appendChild($elementNode);
		}
		$this->schema->appendChild($complexType);
		$wsdlTypeName = 'tns:' . $name;
		$this->customTypes[$name] = $wsdlTypeName;
		return $wsdlTypeName;
	}
	public function addElement($name,$elements = array())
	{
		$elementContainer = $this->xml->createElement('s:element');
		$elementContainer->setAttribute('name',$name);
		$complexType = $this->xml->createElement('s:complexType');
		$elementContainer->appendChild($complexType);
		$sequence = $this->xml->createElement('s:sequence');
		$complexType->appendChild($sequence);
		foreach($elements as $element)
		{
			$elementNode = $this->xml->createElement('s:element');
			$elementNode->setAttribute('name',isset($element['name']) ? $element['name'] : '');
			$elementNode->setAttribute('minOccurs',isset($element['minOccurs']) ? $element['minOccurs'] : '0');
			$elementNode->setAttribute('maxOccurs',isset($element['maxOccurs']) ? $element['maxOccurs'] : '1');
			$elementNode->setAttribute('type',$this->getWsdlType(isset($element['type']) ? $element['type'] : 'string'));
			$sequence->appendChild($elementNode);
		}
		$this->schema->appendChild($elementContainer);
	}
	public function addPortType($name)
	{
		$portType = $this->xml->createElement('wsdl:portType');
		$portType->setAttribute('name',$name);
		$this->root->appendChild($portType);
		return $portType;
	}
	public function addPortOperation($portTypeNode,$name,$input,$output)
	{
		$operation = $this->xml->createElement('wsdl:operation');
		$operation->setAttribute('name',$name);
		$item = $this->xml->createElement('wsdl:input');
		$item->setAttribute('message','tns:' . $input['message']);
		$operation->appendChild($item);
		$item = $this->xml->createElement('wsdl:output');
		$item->setAttribute('message','tns:' . $output['message']);
		$operation->appendChild($item);
		$portTypeNode->appendChild($operation);
	}
	public function addMessage($name,$parts = array())
	{
		$message = $this->xml->createElement('wsdl:message');
		$message->setAttribute('name',$name);
		foreach($parts as $part)
		{
			$partItem = $this->xml->createElement('wsdl:part');
			$partItem->setAttribute('name',$part['name']);
			if(isset($part['type']))
			{
				$partItem->setAttribute('type',$this->getWsdlType($part['type']));
			}
			else if(isset($part['element']))
			{
				$partItem->setAttribute('element','tns:' . $part['element']);
			}
			$message->appendChild($partItem);
		}
		$this->root->appendChild($message);
	}
	public function addBinding($name,$type)
	{
		$binding = $this->xml->createElement('wsdl:binding');
		$binding->setAttribute('name',$name);
		$binding->setAttribute('type','tns:' . $type);
		$this->root->appendChild($binding);
		return $binding;
	}
	public function addSoapBinding($binding,$style = '',$transport = 'http://schemas.xmlsoap.org/soap/http',$soapVersion = 'soap')
	{
		$soapBinding = $this->xml->createElement($soapVersion . ':binding');
		if(null != $style)
		{
			$soapBinding->setAttribute('style',$style);
		}
		$soapBinding->setAttribute('transport',$transport);
		$binding->appendChild($soapBinding);
	}
	public function addBindingOperation($binding,$name)
	{
		$operation = $this->xml->createElement('wsdl:operation');
		$operation->setAttribute('name',$name);
		$binding->appendChild($operation);
		return $operation;
	}
	public function addBindingSoapOperation($operation,$attributes = array(),$soapVersion = 'soap')
	{
		$soapOperation = $this->xml->createElement($soapVersion . ':operation');
		foreach($attributes as $name => $value)
		{
			$soapOperation->setAttribute($name,$value);
		}
		$operation->appendChild($soapOperation);
	}
	public function addBindingBody($operation,$input,$output,$soapVersion = 'soap')
	{
		$item = $this->xml->createElement('wsdl:input');
		$body = $this->xml->createElement($soapVersion . ':body');
		foreach($input['body'] as $name => $value)
		{
			$body->setAttribute($name,$value);
		}
		$item->appendChild($body);
		$operation->appendChild($item);

		$item = $this->xml->createElement('wsdl:output');
		$body = $this->xml->createElement($soapVersion . ':body');
		foreach($output['body'] as $name => $value)
		{
			$body->setAttribute($name,$value);
		}
		$item->appendChild($body);
		$operation->appendChild($item);
	}
	public function addService($name)
	{
		$service = $this->xml->createElement('wsdl:service');
		$service->setAttribute('name',$name);
		$this->root->appendChild($service);
		return $service;
	}
	public function addServicePort($service,$name,$binding,$location,$soapVersion = 'soap')
	{
		$port = $this->xml->createElement('wsdl:port');
		$port->setAttribute('name',$name);
		$port->setAttribute('binding','tns:' . $binding);
		$address = $this->xml->createElement($soapVersion . ':address');
		$address->setAttribute('location',$location);
		$port->appendChild($address);
		$service->appendChild($port);
	}
	public function getWsdl()
	{
		return $this->xml->saveXML();
	}
	public function saveWsdl($filePath)
	{
		file_put_contents($filePath,$this->getWsdl(),LOCK_EX);
	}
	public function getWsdlType($type)
	{
		if(isset(self::$types[$type]))
		{
			return self::$types[$type];
		}
		else if(isset($this->customTypes[$type]))
		{
			return $this->customTypes[$type];
		}
		else
		{
			return $this->addType($type);
		}
	}
	public function addType($type)
	{
		$pos = strrpos($type,'[]');
		if(false === $pos)
		{
			return $this->addTypeByClass($type);
		}
		else
		{
			// 根据[]位置验证是否符合格式的数组
			if('[]' !== substr($type,-2,2))
			{
				throw new Exception('WSDL解析错误，类型 ' . $type . ' 格式不正确');
			}
			// 一层一层创建多维数组
			$count = substr_count($type,'[]');
			list($typeClassName) = explode('[]',$type);
			$arrayTypeFlag = $typeClassName . '[]';
			// 数组类型创建
			$this->getWsdlType($typeClassName);
			$arrayTypeName = 'ArrayOf' . ucfirst($typeClassName);
			$typeName = $this->addComplexType($arrayTypeName,array(
				array(
					'name'			=>	$typeClassName,
					'minOccurs'		=>	'0',
					'maxOccurs'		=>	'unbounded',
					'type'			=>	$typeClassName
				)
			));
			$this->customTypes[$arrayTypeFlag] = $typeName;
			for($i = 1; $i < $count; ++$i)
			{
				$arrayTypeFlag .= '[]';
				$oldArrayTypeName = $arrayTypeName;
				$arrayTypeName = 'ArrayOf' . $arrayTypeName;
				$typeName = $this->addComplexType($arrayTypeName,array(
					array(
						'name'			=>	$oldArrayTypeName,
						'minOccurs'		=>	'0',
						'maxOccurs'		=>	'unbounded',
						'type'			=>	$oldArrayTypeName
					)
				));
				$this->customTypes[$arrayTypeFlag] = $typeName;
			}
			return $typeName;
		}
	}
	public function addTypeByClass($className)
	{
		$reflection = new ReflectionClass($className);
		$props = $reflection->getProperties();
		$elements = array();
		foreach($props as $prop)
		{
			if($prop->isPublic())
			{
				$count = preg_match('/@var\s*([^\s]+)/', $prop->getDocComment(), $matches);
				if(0 === $count)
				{
					$varType = 'string';
				}
				else
				{
					$varType = $matches[1];
				}
				$elements[] = array(
					'name'			=>	$prop->getName(),
					'minOccurs'		=>	'1',
					'maxOccurs'		=>	'1',
					'type'			=>	$varType
				);
			}
		}
		return $this->addComplexType($className,$elements);
	}
	public function getNS($name)
	{
		if('urn:' === substr($this->namespace,0,4))
		{
			return $this->namespace . '#' . $name;
		}
		else
		{
			return $this->namespace . $name;
		}
	}
}