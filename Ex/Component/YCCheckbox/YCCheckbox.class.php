<?php
/**
 * 选择框控件
 * @author Yurun <yurun@yurunsoft.com>
 * @copyright 宇润软件(Yurunsoft.Com) All rights reserved.
 */
class YCCheckbox extends YCCheckRadioBase
{
	public function __construct($attrs,$tagName)
	{
		parent::__construct($attrs,$tagName);
		$this->attrsDefault['text'] = '选择框';
	}
}