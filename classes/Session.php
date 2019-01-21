<?php

class Session
{
	private function __construct(){}
	
	public static function all()
	{
		return $_SESSION;
	}
	
	public static function exists($key)
	{
		return (isset($_SESSION[$key])) ? true : false;
	}
	
	public static function put($key, $value)
	{
		return $_SESSION[$key] = $value;
	}
	
	public static function get($key)
	{
		if(self::exists($key)){
			return $_SESSION[$key];
		}
		return false;
	}
	
	public static function delete($key)
	{
		if(self::exists($key)){
			unset($_SESSION[$key]);
		}
		return false;
	}

	public static function flash($key, $value = '')
	{
		if(self::exists($key)){
			$session = self::get($key);
			self::delete($key);
			return $session;
		} else {
			self::put($key, $value);
		}
		
		return '';
	}
}


















