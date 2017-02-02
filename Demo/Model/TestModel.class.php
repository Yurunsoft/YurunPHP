<?php
class TestModel extends Model
{
	public $autoFields = false;
	public function getMsg()
	{
		return '这是一个YurunPHP演示！';
	}
}