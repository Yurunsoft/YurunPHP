<?php
/**
 * 下拉框控件
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCSelect extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text_field'		=> 'text',
			'value_field'		=> 'value',
			'group_field'		=> 'group',
			'group_field_value'	=>	0,
			'show_group'		=>	false,
			'disabled_field'	=>	'disabled',
			'compare_method'	=>	'=='
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
			'text_field','value_field','disabled_field','select_value','first_item_text','first_item_value','select_text','group_field','show_group','group_field_value'
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
	protected function parseDataset()
	{
		parent::parseDataset();
		if($this->show_group)
		{
			$dataset = array();
			foreach($this->dataset as $item)
			{
				if($item[$this->group_field] == $this->group_field_value)
				{
					// 组
					if(isset($dataset[$item[$this->value_field]]))
					{
						$dataset[$item[$this->value_field]] = array_merge($dataset[$item[$this->value_field]],$item);
					}
					else
					{
						$item['children'] = array();
						$dataset[$item[$this->value_field]] = $item;
					}
				}
				else
				{
					// 不是组
					if(!isset($dataset[$item[$this->group_field]]))
					{
						$dataset[$item[$this->group_field]] = array('children'=>array());
					}
					$dataset[$item[$this->group_field]]['children'][] = $item;
				}
			}
			$this->dataset = $dataset;
		}
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
			if('===' === $this->compare_method)
			{
				$option_selected = (null!==$select_value && select_value===$option_value) || (null!==$select_text && $select_text===$option_text);
			}
			else
			{
				$option_selected = (null!==$select_value && select_value==$option_value) || (null!==$select_text && $select_text==$option_text);
			}
			$this->option_text = $option_text;
			$this->option_value = $option_value;
			$this->option_selected = $option_selected;
			$this->isFirstItem = true;
			$this->innerHtml .= $this->getTemplate('option',false);
		}
		$this->isFirstItem = false;
		foreach($this->dataset as $key => $option)
		{
			if(isset($option['children']))
			{
				$this->renderGroupItem($option);
			}
			else 
			{
				$this->renderOptionItem($option,$key);
			}
		}
		$this->view->set('innerHtml',$this->innerHtml);
	}
	protected function renderOptionItem($option,$key = '')
	{
		if(is_array($option))
		{
			$option_text = $option[$this->text_field];
			$option_value = isset($option[$this->value_field])?$option[$this->value_field]:$option[$this->text_field];
			$option_disabled = isset($option[$this->disabled_field]) ? $option[$this->disabled_field] == 1 : false;
		}
		else
		{
			if('value' === $this->text_field)
			{
				$option_text = $option;
			}
			else
			{
				$option_text = $key;
			}
			$option_value = $option;
			$option_disabled = false;
		}
		$this->option_text = $option_text;
		$this->option_value = $option_value;
		$this->option_disabled = $option_disabled;
		$select_value = $this->get('select_value',null);
		$select_text = $this->get('select_text',null);
		$this->option_selected = (null!==$select_value && $select_value==$option_value) || (null!==$select_text && $select_text==$option_text);
		$this->innerHtml .= $this->getTemplate('option',false);
	}
	protected function renderGroupItem($option)
	{
		if(!empty($option))
		{
			if(count($option) > 1)
			{
				$option_text = $option[$this->text_field];
				$this->option_text = $option_text;
				$this->option_value = isset($option[$this->value_field])?$option[$this->value_field]:$option[$this->text_field];
				$this->isGroup = true;
				$this->innerHtml .= $this->getTemplate('group',false);
			}
			foreach($option['children'] as $item)
			{
				$this->renderOptionItem($item);
			}
			echo '</optgroup>';
		}
	}
}