<?php

class Company
{
	private $data;
	private $dataAll;
	private $sum;

	public function __construct($company_id = null)
	{
		if($company_id){
			$this->find($company_id);
			return $this->data;
		}
		return false;
	}
	
	public function find($company = null)
	{
		if($company){
			$data = DB::getInstance()->get('*', 'companys', array('id', '=', $company));
			
			if($data->getRecords()){
				$this->data = $data->getFirst();
				$this->findAll();
				return true;
			}
		}
		
		return false;
	}

	public function findAll()
	{
		$dataAll = DB::getInstance()->get('*', 'companys', array('client_id', '=', $this->data->client_id));
			
		if($dataAll->getRecords()){
			$this->dataAll = $dataAll->getResults();
			return true;
		}
		
		return false;
	}

	public function create ($fields = array())
	{
		if(!$this->db->insert('companys', $fields)){
			throw new Exception('There was a problem creating an account!');
		}
	}

	public function getData()
	{
		return $this->data;
	}

	public function getDataAll()
	{
		return $this->dataAll;
	}
}