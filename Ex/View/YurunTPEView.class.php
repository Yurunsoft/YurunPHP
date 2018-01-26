<?php
class YurunTPEView
{
	private static $tags = array(
		'if',
		'elseif',
		'else',
		'foreach',
		'for',
		'switch',
		'case',
		'default',
		'counter',
		'include',
		'js',
		'css',
		'image',
		'origin'
	);
	private static $controlsPatterns1,$controlsPatterns2;
	private static $tagsPatterns1,$tagsPatterns2;
	private $xml;
	private static $tagLeft,$tagRight;
	private static $constsKey,$constsValue;
	private static $isInit = false;
	public function __construct()
	{
		$this->xml = new DOMDocument('1.0','utf-8');
		if(!self::$isInit)
		{
			self::init();
		}
	}
	public static function init()
	{
		$tags = implode('|',self::$tags);
		self::$tagLeft = Config::get('@.TEMPLATE_TAG_LEFT');
		self::$tagRight = Config::get('@.TEMPLATE_TAG_RIGHT');
		self::$controlsPatterns1 = '/' . self::$tagLeft . "([a-z0-9_.-]*)((([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*)([\s]*runat=\"server\"[\s]*)(([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*))\s*\/". self::$tagRight .'([^' . self::$tagLeft . ']*?)/is';
		self::$controlsPatterns2 = '/' . self::$tagLeft . "([a-z0-9_.-]*)((([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*)([\s]*runat=\"server\"[\s]*)(([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*))" . self::$tagRight . '(.*?)' . self::$tagLeft . '\/\\1(\s*?)' . self::$tagRight . '/is';
		self::$tagsPatterns1 = '/' . self::$tagLeft . "({$tags})(([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*)" . self::$tagRight . '(.*?)' . self::$tagLeft . '\/\\1(\s*?)' . self::$tagRight . '/is';
		self::$tagsPatterns2 = '/'. self::$tagLeft ."({$tags})(([\s]+[a-z0-9_.-]*[\s]*=\"[^\"]*\")*)\s*\/". self::$tagRight .'([^'. self::$tagLeft .']*?)/is';
		self::initConst();
		self::$isInit = true;
	}
	public function parse($file)
	{
		$html = file_get_contents($file);
		$this->parseConst($html);
		$this->parseTemplate($html);
		$this->parsePrint($html);
		$this->parsePHP($html);
		if(Config::get('@.TEMPLATE_OPTIMIZE_PHP'))
		{
			$this->optimizePHP($html);
		}
		return $html;
	}
	private function parseTemplate(&$html)
	{
		// 原生标签
		$this->parseTag($html);
		// URL标签支持
		$this->parseUrl($html);
		// 扩展控件
		$this->pregParseTagEx($html,array(self::$controlsPatterns1,self::$controlsPatterns2));
	}
	private function pregParseTagEx(&$html,$pattern)
	{
		$_this = $this;
		do
		{
			$count = 0;
			$html = preg_replace_callback(
					$pattern,
					function($matches) use($_this,&$count){
						$attrs = $matches[2];
						$inner = $matches[8];
						$tag = $matches[1];
						$result = $_this->parseTagItem($tag,$attrs,$inner);
						if(false !== $result)
						{
							++$count;
							return $result;
						}
						else
						{
							return $matches[0];
						}
					},
					$html);
		}
		while($count > 0);
	}
	private function parseTag(&$html)
	{
		$this->pregParseTag($html,array(self::$tagsPatterns1,self::$tagsPatterns2));
	}
	private function pregParseTag(&$html,$pattern)
	{
		$_this = $this;
		do
		{
			$count = 0;
			$html = preg_replace_callback(
					$pattern,
					function($matches) use($_this,&$count){
						$attrs = $matches[2];
						$inner = $matches[4];
						$result = $_this->parseTagItem($matches[1],$attrs,$inner);
						if(false !== $result)
						{
							++$count;
							return $result;
						}
						else
						{
							return $matches[0];
						}
					},
					$html);
		}
		while($count > 0);
	}
	public function parseAttrs($attrs)
	{
		if(is_string($attrs))
		{
			$this->xml->loadHTML('<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/><yurun ' . $attrs . '></yurun>');
			$node = $this->xml->getElementsByTagName('yurun')->item(0);
			$attrs = array();
			foreach($node->attributes as $attr)
			{
				$attrs[$attr->name] = $attr->value;
			}
			return $attrs;
		}
		else if(is_array($attrs))
		{
			return $this->parseAttrsString($attrs);
		}
		else
		{
			return $attrs;
		}
	}
	public function parseTagItem($tag,$attrs,$inner)
	{
		$method = '_' . $tag;
		if(method_exists($this,$method))
		{
			return $this->$method($attrs,$inner);
		}
		else
		{
			if(is_string($attrs))
			{
				$attrs = $this->parseAttrs($attrs);
			}
			if(isset($attrs['id']))
			{
				$argPreName = '$c' .str_replace('.','',uniqid('',true));
				$argPre = "{$argPreName} = \"{$attrs['id']}\";";
				$id = $argPreName;
			}
			else
			{
				$id = 'c'.str_replace('.','',uniqid('',true));
			}
			$argName = '$'.$id;
			$attrs['innerHtml'] = base64_encode($inner);
			$attrsStr = self::arrayToOutStr($attrs);
			$php = <<<PHP
<?php {$argPre}{$argName}=YurunComponent::get{$tag}($attrsStr);if(false!=={$argName}->begin()):?>{$inner}<?php endif;{$argName}->end();?>
PHP
			;
			$php = YurunComponent::getTemplatePHP($tag,$php);
			return $php;
		}
	}
	private function parsePrint(&$html)
	{
		$patterns = array(
			// 输出变量
			'/' . Config::get('@.TEMPLATE_ECHO_VAR_TAG_LEFT') . '(.*)' . Config::get('@.TEMPLATE_ECHO_VAR_TAG_RIGHT') . '/isU',
			// 输出常量
			'/' . Config::get('@.TEMPLATE_ECHO_CONST_TAG_LEFT') . '(.*)' . Config::get('@.TEMPLATE_ECHO_CONST_TAG_RIGHT') . '/isU',
		);
		$html = preg_replace_callback(
				$patterns,
				function($matches){
					return '<?php echo ' . $matches[1] . ';?>';
				},
				$html);
		// 输出配置项
		$html = preg_replace_callback(
				'/' . Config::get('@.TEMPLATE_ECHO_CONFIG_TAG_LEFT') . '(.*)' . Config::get('@.TEMPLATE_ECHO_CONFIG_TAG_RIGHT') . '/isU',
				function($matches){
					$arr = explode(' ',$matches[1]);
					if(isset($arr[1]))
					{
						$default = ',' . $arr[1];
					}
					return '<?php echo Config::get(\'' . $arr[0] . '\'' . $default . ');?>';
				},
				$html);
		// 输出语言包
		$html = preg_replace_callback(
				'/' . Config::get('@.TEMPLATE_ECHO_LANG_TAG_LEFT') . '(.*)' . Config::get('@.TEMPLATE_ECHO_LANG_TAG_RIGHT') . '/isU',
				function($matches){
					$arr = explode(' ',$matches[1]);
					return '<?php echo Lang::get(\'' . $arr[0] . '\');?>';
				},
				$html);
	}
	private function parsePHP(&$html)
	{
		$html = str_ireplace(
				array(self::$tagLeft . 'php' . self::$tagRight,self::$tagLeft . '/php' . self::$tagRight),
				array('<?php ','?>'),
				$html);
	}
	private function optimizePHP(&$html)
	{
		$html = preg_replace('/\?>\s*<\?php/', '', $html);
	}
	public function _if($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		$condition = isset($attrs['condition'])?$attrs['condition']:null;
		if(empty($condition))
		{
			return '';
		}
		else
		{
			return "<?php if({$condition}):?>{$inner}<?php endif;?>";
		}
	}
	public function _elseif($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		$condition = isset($attrs['condition'])?$attrs['condition']:null;
		if(empty($condition))
		{
			return '';
		}
		else
		{
			return "<?php elseif({$condition}):?>{$inner}";
		}
	}
	public function _else($attrs,$inner)
	{
		return '<?php else:?>' . $inner;
	}
	public function _for($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		$start = isset($attrs['start'])?$attrs['start']:0;
		$end = isset($attrs['end'])?$attrs['end']:0;
		$step = isset($attrs['step'])?$attrs['step']:1;
		$name = '$'.(isset($attrs['name'])?$attrs['name']:'i');
		$condition = isset($attrs['condition'])?$attrs['condition']:'<';
		return "<?php for({$name}={$start};{$name}{$condition}{$end};{$name}+={$step}){?>{$inner}<?php }?>";
	}
	public function _foreach($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		$list = isset($attrs['list'])?$attrs['list']:'';
		$key = '$'.(isset($attrs['key'])?$attrs['key']:'key');
		$value = '$'.(isset($attrs['value'])?$attrs['value']:'value');
		$index = '$'.(isset($attrs['index'])?$attrs['index']:'index');
		return "<?php {$index}=-1; foreach({$list} as {$key}=>{$value}){++{$index};?>{$inner}<?php }?>";
	}
	public function _switch($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['value']))
		{
			$inner = trim($inner);
			return "<?php switch({$attrs['value']}):?>{$inner}<?php endswitch;?>";
		}
		else
		{
			return false;
		}
	}
	public function _case($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['value']))
		{
			$values = explode('|',$attrs['value']);
			$str = '';
			foreach($values as $value)
			{
				$str .= 'case ' . $value . ':';
			}
			return "<?php {$str}?>{$inner}".(!isset($attrs['break']) || 1==$attrs['break']?'<?php break;?>':'');
		}
		else
		{
			return false;
		}
	}
	public function _default($attrs,$inner)
	{
		return '<?php default:?>' . $inner;
	}
	public function _counter($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		$count = isset($attrs['count'])?$attrs['count']:0;
		$name = '$'.(isset($attrs['name'])?$attrs['name']:'i');
		return "<?php for({$name}=1;{$name}<=$count;++{$name}){?>{$inner}<?php }?>";
	}
	public function _include($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['name']))
		{
			self::parseArgValueOut($attrs['name']);
			return '<?php $this->include(' . $attrs['name'] . ');?>';
		}
		else
		{
			return false;
		}
	}
	public function _js($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['src']))
		{
			$src = $attrs['src'];
			if(self::parseArgValueOut($src))
			{
				return '<script src="<?php echo parseStatic(' . $src . ');?>" type="text/javascript"></script>';
			}
			else
			{
				$src = parseStatic($attrs['src']);
				return "<script src=\"{$src}\" type=\"text/javascript\"></script>";
			}
		}
		else 
		{
			return false;
		}
	}
	public function _css($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['src']))
		{
			$src = $attrs['src'];
			if(self::parseArgValueOut($src))
			{
				return "<link rel=\"stylesheet\" type=\"text/css\" href=\"<?php echo parseStatic({$src});?>\"/>";
			}
			else
			{
				$src = parseStatic($attrs['src']);
				return "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$src}\"/>";
			}
		}
		else
		{
			return false;
		}
	}
	public function _image($attrs,$inner)
	{
		$attrs = $this->parseAttrs($attrs);
		if(isset($attrs['src']))
		{
			$srcOrigin = $attrs['src'];
			unset($attrs['src']);
			$attrs = $this->parseAttrs($attrs);
			$src = $srcOrigin;
			if(self::parseArgValueOut($src))
			{
				return "<img src=\"<?php echo parseStatic({$src});?>\"{$attrs}/>";
			}
			else
			{
				$src = parseStatic($srcOrigin);
				return "<img src=\"{$src}\"{$attrs}/>";
			}
		}
		else
		{
			return false;
		}
	}
	public function _origin($attrs,$inner)
	{
		return $inner;
	}
	public function parseUrl(&$html)
	{
		$html = preg_replace(
					'/' . self::$tagLeft . 'url=(.*)\/' . self::$tagRight . '/isU',
					'<?php echo Dispatch::url(\\1);?>',
					$html);
	}
	private function parseAttrsString($attrs)
	{
		$attrsStr = '';
		if(is_array($attrs))
		{
			foreach($attrs as $key=>$attr)
			{
				$attrsStr .= " {$key}=\"{$attr}\"";
			}
		}
		return $attrsStr;
	}
	private static function initConst()
	{
		$webroot = Request::getHome();
		if('/' === substr($webroot,-1,1))
		{
			$webroot = substr($webroot,0,-1);
		}
		$consts = array(
			'__MODULE__'	=>	Dispatch::module(),				// 当前模块名
			'__CONTROL__'	=>	Dispatch::control(),			// 当前控制器名
			'__ACTION__'	=>	Dispatch::action(),				// 当前动作名
			'__DYNAMIC_MODULE__'	=>	'Dispatch::module()',	// 当前模块名
			'__DYNAMIC_CONTROL__'	=>	'Dispatch::control()',	// 当前控制器名
			'__DYNAMIC_ACTION__'	=>	'Dispatch::action()',	// 当前动作名
			'__WEBROOT__'	=>	$webroot,						// 站点根目录
			'__STATIC__'	=>	STATIC_PATH,					// 静态文件目录
			'__THEME__'		=>	Config::get('@.THEME')			// 当前主题名
		);
		$tplConsts = Config::get('@.TEMPLATE_CONSTS');
		self::$constsKey = array_keys($consts);
		self::$constsValue = array_values($consts);
		foreach($tplConsts as $key => $value)
		{
			self::$constsValue[] = str_replace(self::$constsKey,self::$constsValue,$value);
			self::$constsKey[] = $key;
		}
	}
	private function parseConst(&$html)
	{
		// 预定义常量
		$html = str_replace(self::$constsKey,self::$constsValue,$html);
	}
	public static function arrayToOutStr(&$attrs)
	{
		$keys = array_keys($attrs);
		$result = '';
		foreach($keys as $key)
		{
			if(isset($attrs[$key][0]) && '#' === $attrs[$key][0])
			{
				$attrs[$key] = substr($attrs[$key],1);
			}
			else
			{
				$attrs[$key] = '\'' . addcslashes($attrs[$key],'\'') . '\'';
			}
			$result .= "'{$key}'=>{$attrs[$key]},";
		}
		return 'array('.substr($result,0,-1).')';
	}
	/**
	 * 处理输出参数。PHP语句返回true，否则false
	 * @param type $value
	 * @return boolean
	 */
	public static function parseArgValueOut(&$value)
	{
		if(isset($value[0]) && '#' === $value[0])
		{
			$value = substr($value,1);
			return true;
		}
		else
		{
			$value = '\'' . addcslashes($value,'\'') . '\'';
			return false;
		}
	}
}