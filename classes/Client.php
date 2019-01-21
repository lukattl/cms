<?php

class Client
{
	private $db;
	private $data;
	private $driveSum;


	public function __construct()
	{
		$this->db = DB::getInstance();
	}
	
	public function create($client = array(), $user = array())
	{
		$insertClient = $this->db->insert('klijent', $client);

		if(!empty($user)){
			$insertUser = $this->db->insert('users', $user);
		}
		
		if($insert){
			$obj = new Client();
			$this->set($client);
			
			return $this;
		}
	}

	#public function update(){}

	public function drives($date1, $date2, $type)
	{
		
	}
}