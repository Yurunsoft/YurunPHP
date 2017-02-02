<?php
class DenyControl extends Control
{
	public function index()
	{
		echo 'index.php不可访问这个控制器哦！';
	}
}