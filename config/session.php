<?php

return [
	'cookie' => [
		'cookie_name' => 'remember',
		'cookie_expiry' => 7 * 24 * 60 * 60
	],
	'session' => [
		'session_name' => 'user',
		'token_name' => 'csrf'
	]
];