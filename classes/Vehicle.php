<?php

class Vehicle
{
	private $db;
	private $data;
	private $dataAll;
	private $sum;

	public function __construct($company_id = null)
	{
		$this->db = DB::getInstance();
		if($company_id){
			$this->find($company_id);
			return $this->data;
		}

		return false;
	}

	public function find()
	{
		if($company){
			$vehicle_id = $this->db->get('id', 'vehicle', array('taxi_num', '=', $taxi_num));
			dd($vehicle_id);
			$data = $this->db->get('*', 'vehicle', array('id', '=', $vehicle_id));
			
			if($data->getRecords()){
				$this->data = $data->getFirst();
				$this->findAll();
				return true;
			}
		}
		
		return false;
	}
}