<?php
abstract class YCCheckRadioGroupBase extends YCBase
{
	/**
	 * 属性默认值们
	 * @var unknown
	 */
	protected $attrsDefault = array(
			'text'			=> '',
			'text_align'	=> 'right',
			'text_field'	=> 'text',
			'value_field'	=> 'value',
			'checked_field'	=> 'checked',
			'theme'			=> 'default'
	);
}