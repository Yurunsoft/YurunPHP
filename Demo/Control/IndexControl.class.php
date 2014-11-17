<?php
class IndexControl extends Control
{
	public function index()
	{
		echo Model::obj('','test')->getMsg();
	}
}