<?php
if (!isset($_SESSION)) {
	session_start();
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'functions/sanitize.php';
require_once 'functions/debug.php';

spl_autoload_register(function ($class){
	require_once 'classes/' . $class . '.php';
});
