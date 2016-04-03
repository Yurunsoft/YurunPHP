<?php
abstract class YCCheckRadioBase extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
		'text'			=> '',
		'text_align'		=> 'right',
		'text_field'	=> 'text',
		'value_field'	=> 'value',
		'checked_field'	=> 'checked',
		'theme'			=> 'default'
	);
	/**
	 * 构造方法
	 * @param unknown $attrs
	 * @param string $tagName
	 */
	public function __construct($attrs = array(), $tagName = null)
	{
		parent::__construct($attrs,$tagName);
		$this->excludeAttrs = array_merge($this->excludeAttrs,
			array(
					'text',
					'text_align',
					'text_field',
					'value_field',
					'checked_field',
					'theme',
					'checked_value',
					'left_text',
					'right_text'
			)
		);
	}
	/**
	 * 为视图层做准备工作
	 */
	public function prepareView()
	{
		$this->getTextAndValue();
		$this->parseChecked();
		$this->parseText();
		parent::prepareView();
	}
	protected function parseChecked()
	{
		if(false!==$this->checked_value)
		{
			$checked = ($this->value==$this->checked_value);
		}
		else 
		{
			$checked = $this->checked;
		}
		if($checked)
		{
			$this->checked = 'checked';
		}
		else
		{
			$this->remove('checked');
		}
	}
	protected function parseText()
	{
		if('left' === $this->text_align)
		{
			$this->left_text = $this->text;
			$this->right_text = '';
		}
		else
		{
			$this->left_text = '';
			$this->right_text = $this->text;
		}
	}
	private function getTextAndValue()
	{
		if(isset($this->text_field) && is_array($this->dataset) && count($this->dataset)>0)
		{
			if(isset($this->dataset[$this->text_field]))
			{
				$this->text = $this->dataset[$this->text_field];
				$this->value = isset($this->dataset[$this->value_field]) ? $this->dataset[$this->value_field] : $this->text;
				$this->checked = null === $this->get('checked_field',null) ? $this->checked : $this->dataset[$this->checked_field];
			}
			else if(isset($this->dataset[0][$this->text_field]))
			{
				$this->text = $this->dataset[0][$this->text_field];
				$this->value = isset($this->dataset[0][$this->value_field]) ? $this->dataset[0][$this->value_field] : $this->text;
				$this->checked = null === $this->get('checked_field',null) ? $this->checked : $this->dataset[0][$this->checked_field];
			}
		}
		$this->value = (false === $this->value ? $this->text : $this->value);
	}
}