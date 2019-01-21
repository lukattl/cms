<?php

function dd($data = 'ok', $data1 = ''){
	echo '<pre>';
	var_dump($data);
	var_dump($data1);
	echo '</pre>';
	die();
}