<?php

class Helper
{
	# zabrana instanciranja objekta
	private function __construct(){}
	
	#zabrana kloniranja objekta
	private function __clone(){}
	
	public static function getHeader($title, $user = null, $file='header')
	{
		if($file){
			$path = require_once 'includes/'. $file .'.php';
			return $path;
		}
		return false;
	}
	
	public static function getFooter($page, $file='footer')
	{
		if($file){
			$path = require_once 'includes/'. $file .'.php';
			return $path;
		}
		return false;
	}
	
	public static function getModal($file = null, $user = null)
	{
		if($file){
			$path = require_once 'modals/'. $file .'.php';
			return $path;
		}
		return false;
	}

	public static function getConfig($file)
	{
		if($file){
			$path = require 'config/'. $file .'.php';
			return $path;
		}
		return false;
	}

	public static function getPanel($file='')
	{
		if($file){
			$path = require 'includes/'. $file .'.php';
			return $path;
		}
		return false;
	}

	public static function now()
	{
		return (new \DateTime())->format('Y-m-d H:i:s');
	}

	public static function nowYear()
	{
		return (new \DateTime())->format('Y');
	}

	public static function loger ($link, $user, $log) 
	{
		$db = DB::getInstance();

		$data = array(
			'user' => $user,
			'link' => $link,
			'poruka' => $log
		);

		$insert = $db->insert('log', $data);

		if($insert){
			return 1;
		} else {
			return 0;
		}
	}

	public static function logerArr ($link, $user, $log = array()) 
	{
		$db = DB::getInstance();

		$log = implode('||', $log);

		$data = array(
			'user' => $user,
			'link' => $link,
			'poruka' => $log
		);

		$insert = $db->insert('log', $data);
	}

	/**
	// @ output = array()
	// @ return json output for Android
	**/
	public static function respond($output = array()) {
    	return json_encode($output, JSON_PRETTY_PRINT);
	}

	public static function arrExists($array = array())
	{
		foreach ($array as $key => $value) {
			if (isset($value) || !empty($value)) {
				return true;
			}
			return false;
		}
	}

	public static function coordsExists($array = array())
	{
		foreach ($array as $key => $value) {
			if (isset($value) || !empty($value) || $value != NULL) {
				return true;
			}
		}
		return false;
	}

	public static function change_key( $array, $old_key, $new_key ) {

		if( ! array_key_exists( $old_key, $array ) )
			return $array;
	
		$keys = array_keys( $array );
		$keys[ array_search( $old_key, $keys ) ] = $new_key;
	
		return array_combine( $keys, $array );
	}
	
}