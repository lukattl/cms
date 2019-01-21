<?php

class Token
{
	private static $config;
	
	private function __construct(){}
	
	private static function setConfig()
	{
		self::$config = Helper::getConfig('session');
	}
	
	public static function generate()
	{
		self::setConfig();
		return Session::put(self::$config['session']['token_name'], md5(uniqid()));
	}
	
	public static function check($token)
	{
		self::setConfig();
		$token_name = self::$config['session']['token_name'];
		
		if(Session::get($token_name) === $token){
			Session::delete($token_name);
			return true;
		}
		return false;
	}
}