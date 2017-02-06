<?php
/**
 * XML处理类
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class XML
{
	/**
	 * XML文本转为数组
	 * @param string $content
	 * @return array
	 */
	public static function toArray($content)
	{
		$doc = new DomDocument('1.0', 'UTF-8');
		$doc->loadXML($content,LIBXML_COMPACT | LIBXML_NOBLANKS | LIBXML_NOCDATA);
		$result = array();
		self::parseToArray($doc->documentElement,$result);
		return $result;
	}
	/**
	 * 处理转换为数组
	 * @param DOMElement $node
	 * @param array $result
	 * @param bool $first
	 */
	private static function parseToArray($node,&$result,$first = true)
	{
		$resultItem = array();
		// 处理节点属性
		if($node->attributes->length > 0)
		{
			$resultItem['__attributes'] = array();
			for($i = 0; $i < $node->attributes->length; ++$i)
			{
				$item = $node->attributes->item($i);
				$resultItem['__attributes'][$item->nodeName] = $item->nodeValue;
			}
		}
		// 处理子节点
		for($i = 0; $i < $node->childNodes->length; ++$i)
		{
			$item = $node->childNodes->item($i);
			if(XML_ELEMENT_NODE === $item->nodeType)
			{
				self::parseToArray($item,$resultItem,false);
			}
			else
			{
				$resultItem['__value'] = $node->nodeValue;
			}
		}
		// 处理结果
		if($first)
		{
			$result[$node->tagName] = $resultItem;
		}
		else
		{
			if(!isset($result[$node->tagName]))
			{
				$result[$node->tagName] = array();
			}
			$result[$node->tagName][] = $resultItem;
		}
	}
	/**
	 * PHP数组转为XML文本
	 * @param type $object
	 * @param type $format 是否格式化
	 * @param type $noXMLDecl 去除xml声明
	 * @return string
	 */
	public static function toString($object,$format = false,$noXMLDecl = false)
	{
		$doc = new DomDocument('1.0', 'UTF-8');
		$doc->formatOutput = $format;
		$keys = array_keys($object);
		if(isset($keys[0]))
		{
			// 创建节点
			$item = $doc->createElement($keys[0]);
			// 处理属性
			if(isset($object[$keys[0]]['__attributes']))
			{
				foreach($object[$keys[0]]['__attributes'] as $key => $value)
				{
					$item->setAttribute($key, $value);
				}
			}
			$doc->appendChild($item);
			self::parseToString($doc,$doc->documentElement,$object[$keys[0]]);
			if($noXMLDecl)
			{
				return $doc->saveXML($doc->documentElement);
			}
			else
			{
				return $doc->saveXML();
			}
		}
		else
		{
			return '';
		}
	}
	/**
	 * 处理转换为文本
	 * @param DomDocument $doc
	 * @param DOMElement $node
	 * @param array $object
	 * @param bool $first
	 */
	private static function parseToString($doc,$node,$object)
	{
		foreach($object as $tagName => $tags)
		{
			if('__attributes' !== $tagName)
			{
				foreach($tags as $tag)
				{
					$item = $doc->createElement($tagName);
					// 处理属性
					if(isset($tag['__attributes']))
					{
						foreach($tag['__attributes'] as $key => $value)
						{
							$item->setAttribute($key, $value);
						}
					}
					if(isset($tag['__value']))
					{
						// 无子节点
						$item->nodeValue = $tag['__value'];
					}
					else
					{
						// 有子节点
						self::parseToString($doc, $item, $tag);
					}
					$node->appendChild($item);
				}
			}
		}
	}
}