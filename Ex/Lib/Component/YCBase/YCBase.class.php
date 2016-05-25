<?php
class YCBase extends ArrayData
{
	/**
	 * 不渲染，可以选择在类中输出，性能最佳，但不科学
	 * @var unknown
	 */
	const RENDER_MODE_NONE = 0;
	/**
	 * 渲染PHP原生代码，不支持模版引擎。代码和界面分离，性能适中
	 * @var unknown
	 */
	const RENDER_MODE_TEMPLATE_PHP = 1;
	/**
	 * 使用模版引擎渲染模版，能够使用所有模版引擎特性，但性能最差
	 * @var unknown
	 */
	const RENDER_MODE_TEMPLATE_ENGINE = 2;
	/**
	 * 控件们
	 * @var unknown
	 */
	private static $_controls = array();
	/**
	 * 视图层
	 * @var unknown
	 */
	protected $view = null;
	/**
	 * 标签名称
	 * @var unknown
	 */
	protected $tagName;
	/**
	 * 是否输出标签结尾，默认为false
	 * @var unknown
	 */
	protected $printEnd = false;
	/**
	 * 标签属性文本
	 */
	public $attrsStr = '';
	/**
	 * 排除输出的标签属性
	 * @var unknown
	 */
	protected $excludeAttrs = array(
		'runat',
		'dataset',
		'innerHtml',
		'data_func',
		'data_func_args',
		'sql',
		'proc',
		'table_name',
		'proc_params'
	);
	/**
	 * 渲染类型
	 * @var unknown
	 */
	protected $renderMode = self::RENDER_MODE_TEMPLATE_PHP;
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array();
	/**
	 * 构造方法
	 * @param unknown $attrs
	 * @param string $tagName
	*/
	public function __construct($attrs = array(), $tagName = null)
	{
		$this->setTagName($tagName);
		$this->setAttrs($attrs);
		$this->init();
	}
	/**
	 * 初始化
	 */
	protected function init()
	{
		$this->parseAttrs();
		$this->parseDataset();
	}
	/**
	 * 控件入栈
	 */
	protected function _push()
	{
		self::$_controls[] = &$this;
	}
	/**
	 * 控件出栈
	 */
	protected function _pop()
	{
		array_pop(self::$_controls);
	}
	/**
	 * 开始
	 */
	public function begin()
	{
		$this->_push();
		$this->prepareView();
		$this->render();
	}
	/**
	 * 结束
	 */
	public function end()
	{
		if($this->printEnd)
		{
			echo '</' . $this->tagName . '>';
		}
		$this->_pop();
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		// 传入数据
		$data = $this->data;
		$this->attrsStr = YurunComponent::parseAttrsString($data,$this->excludeAttrs);
		$data['attrsStr'] = $this->attrsStr;
		$data['tagName'] = $this->tagName;
		$data['printEnd'] = $this->printEnd;
		// 初始化视图类
		if(null === $this->view)
		{
			$this->view = new View(null,$this);
		}
		$this->view->set($data);
	}
	/**
	 * 渲染控件
	 */
	public function render($fileName='',$renderMode = null)
	{
		if($renderMode === null)
		{
			$renderMode = $this->renderMode;
		}
		switch($renderMode)
		{
			case self::RENDER_MODE_NONE:
				$this->__render();
				break;
			case self::RENDER_MODE_TEMPLATE_PHP:
				echo $this->getTemplate($fileName,false);
				break;
			case self::RENDER_MODE_TEMPLATE_ENGINE:
				echo $this->getTemplate($fileName,true);
				break;
		}
	}
	/**
	 * 自定义渲染内容
	 */
	protected function __render()
	{
		
	}
	public function getTemplateName($fileName='')
	{
		if(is_file($fileName))
		{
			return $fileName;
		}
		$tagName = ucfirst($this->tagName);
		if('' === $fileName)
		{
			$file = '/Component/YC' . $tagName . '/tpl/' . $tagName.Config::get('@.COMPONENT_EXT');
		}
		else
		{
			$file = '/Component/YC' . $tagName . '/tpl/' . $fileName.Config::get('@.COMPONENT_EXT');
		}
		// 模块扩展目录
		$filename = APP_MODULE . Dispatch::module() . '/' . Config::get('@.LIB_FOLDER') . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		// 项目扩展目录
		$filename = APP_LIB . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		// 框架扩展目录
		$filename = PATH_EX_LIB . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		// 默认模版
		$file = '/Component/YCBase/tpl/_Default' . Config::get('@.COMPONENT_EXT');
		// 模块扩展目录
		$filename = APP_MODULE . Dispatch::module() . '/' . Config::get('@.LIB_FOLDER') . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		// 项目扩展目录
		$filename = APP_LIB . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		// 框架扩展目录
		$filename = PATH_EX_LIB . $file;
		if(is_file($filename))
		{
			return $filename;
		}
		return '';
	}
	/**
	 * 获取模版渲染内容
	 * @param string $fileName
	 */
	public function getTemplate($fileName='',$useEngine = false)
	{
		ob_start();
		$file = $this->getTemplateName($fileName);
		if($useEngine)
		{
			$this->view->display($file);
		}
		else
		{
			include $file;
		}
		return ob_get_clean();
	}
	/**
	 * 设置标签名称，如a、input等
	 * @param unknown $tagName
	 */
	public function setTagName($tagName)
	{
		if(is_string($tagName))
		{
			$this->tagName = $tagName;
		}
		else
		{
			$this->tagName = substr(get_called_class(),4);
		}
	}
	/**
	 * 设置控件的属性
	 * @param unknown $attrs
	 */
	public function setAttrs($attrs)
	{
		if(is_array($attrs))
		{
			$this->data = $attrs;
		}
		foreach($this->attrsDefault as $key=>$value)
		{
			if(null===$this->get($key,null))
			{
				$this->set($key,$value);
			}
		}
	}
	/**
	 * 处理传入的属性
	 */
	protected function parseAttrs()
	{
		if(''!==$this->innerHtml)
		{
			$this->innerHtml = base64_decode($this->innerHtml);
		}
	}
	/**
	 * 处理获取数据集
	 */
	protected function parseDataset()
	{
		$dataset = $this->get('dataset',null);
		if(null !== $dataset)
		{
			if(is_string($dataset))
			{
				$this->dataset = json_decode($dataset,true);
			}
		}
		else if(!empty($this->sql))
		{
			$db = Db::getObj();
			if(null === $db)
			{
				new Model;
				$db = Db::getObj();
			}
			if(null !== $db)
			{
				$this->dataset = $db->queryA($this->sql);
			}
		}
		else if(!empty($this->proc))
		{
			if(is_string($this->proc_params))
			{
				$params = json_decode($this->proc_params,true);
			}
			else if(is_array($this->proc_params))
			{
				$params = $this->proc_params;
			}
			else
			{
				$params = array();
			}
			$db = Db::getObj();
			if(null === $db)
			{
				new Model;
				$db = Db::getObj();
			}
			if(null !== $db)
			{
				$db->execProc($this->proc,$params);
				$this->dataset = $db->results[0];
			}
		}
		else if(!empty($this->data_func))
		{
			$data_func_args = $this->get('data_func_args',null);
			if(is_string($data_func_args))
			{
				$data_func_args = json_decode($data_func_args,true);
			}
			else
			{
				$data_func_args = $data_func_args;
			}
			if(null === $data_func_args)
			{
				$data_func_args = array();
			}
			$arr = explode('/',$this->data_func);
			if(count($arr)>1)
			{
				$this->dataset = call_user_func_array(array(Model::obj($arr[0]),$arr[1]), $data_func_args);
			}
			else
			{
				$this->dataset = call_user_func_array($this->data_func, $data_func_args);
			}
		}
		else if(!empty($this->table_name))
		{
			$this->dataset = Model::obj('',$this->table_name)->select();
		}
	}
	public static function __getControl()
	{
		end(self::$_controls);
		return current(self::$_controls);
	}
	public static function __getTemplatePHP($php)
	{
		return $php;
	}
}