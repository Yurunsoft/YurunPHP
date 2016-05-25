<?php
class YCSelect extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text_field'	=> 'text',
			'value_field'	=> 'value',
	);
	/**
	 * 构造方法
	 * @param unknown $attrs
	 * @param string $tagName
	 */
	public function __construct($attrs = array(), $tagName = null)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,array(
				'text_field','value_field','select_value','first_item_text','first_item_value','select_text'
		));
	}
	/**
	 * 渲染控件
	 */
	public function render()
	{
		$this->renderOption();
		parent::render();
	}
	protected function renderOption()
	{
		$this->innerHtml = '';
		if(null!==$this->get('first_item_text',null))
		{
			$option_text = $this->first_item_text;
			$option_value = null!==$this->first_item_value?$this->first_item_value:$this->first_item_text;
			$select_value = $this->get('select_value',null);
			$select_text = $this->get('select_text',null);
			$option_selected = (null!==$select_value && select_value==$option_value) || (null!==$select_text && $select_text==$option_text);
			$this->option_text = $option_text;
			$this->option_value = $option_value;
			$this->option_selected = $option_selected;
			$this->innerHtml .= $this->getTemplate('option',false);
		}
		foreach($this->dataset as $key => $option)
		{
			$data = array();
			if(is_array($option))
			{
				$option_text = $option[$this->text_field];
				$option_value = isset($option[$this->value_field])?$option[$this->value_field]:$option[$this->text_field];
			}
			else
			{
				$option_text = $key;
				$option_value = $option;
			}
			$this->option_text = $option_text;
			$this->option_value = $option_value;
			$select_value = $this->get('select_value',null);
			$select_text = $this->get('select_text',null);
			$this->option_selected = (null!==$select_value && $select_value==$option_value) || (null!==$select_text && $select_text==$option_text);
			$this->view->set($data);
			$this->innerHtml .= $this->getTemplate('option',false);
		}
		$this->view->set('innerHtml',$this->innerHtml);
	}
}