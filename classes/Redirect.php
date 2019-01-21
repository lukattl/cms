<?php

class Redirect
{
	private function __construct(){}
	
	public static function to($location = null)
	{
		if($location){
			header('Location: ' . $location . '.php');
			exit();
		}
	}
}