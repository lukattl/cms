<?php

class Input
{
	private function __construct(){}
	
	public static function exists($type = 'post')
	{
		switch ($type){
			case 'post':
				return (!empty($_POST)) ? true : false;
				break;
			case 'get':
				return (!empty($_GET)) ? true : false;
				break;
			default:
				return false;
				break;
		}
	}
	
	public static function get($item, $type = 'post')
	{
		switch ($type){
			case 'post':
				return (isset($_POST[$item])) ? $_POST[$item] : false;
				break;
			case 'get':
				return (isset($GET[$item])) ? $GET[$item] : false;
				break;
			default:
				return false;
				break;
		}
	}
}