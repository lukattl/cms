<?php

class Hash
{
	private function __construct(){}
	
	public static function salt($lenght)
	{
		return bin2hex(random_bytes($lenght));
	}
	
	public static function make($string, $salt='')
	{
		return hash('sha256', $string.$salt);
	}
}