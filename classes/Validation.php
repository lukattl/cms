<?php

class Validation
{
	private $passed = false;
	private $errors = array();
	private $db = null;
	private $imei_checked = false;
	
	public function __construct()
	{
		$this->db = DB::getInstance();
	}
	
	public function check($fields = array())
	{
		foreach($fields as $field => $rules){
			foreach($rules as $rule => $rule_value){
				$field_value = trim(Input::get($field));

				if($rule === 'required' && empty($field_value)){
					$this->addError($field, "Field {$field} is required!");
				} else if(!empty($field_value)){
					$sql = "SELECT id_korisnika from korisnik where username_korisnika = ? and web_pass = ?";
					$user_check = $this->db->query($sql, array(Input::get('username_korisnika'), md5(Input::get('password_korisnika'))));
					if ($user_check->getRecords() && !$user_check->getError()) {
						switch($rule){
							case 'active':
								$sql_active ="SELECT id_korisnika from korisnik where status_korisnika = 1 and $field = ?";
								$active = $this->db->query($sql_active, array($field_value));
								if (!$active->getRecords() || $active->getError()) {
									$this->addError($field, "Korisnik {$field_value} nije aktivan!");
								}
								break;
							case 'role':
								$sql_role ="SELECT id_korisnika from korisnik where uloga LIKE '%Admin%' and $field = ?";
								$role = $this->db->query($sql_role, array($field_value));
								if (!$role->getRecords() || $role->getError()) {
									$this->addError($field, "Korisnik {$field_value} nema ulogu admina!");
								}
								break;
							/* case 'matches':
								$salt = $this->db->get('salt', 'users', array('username', '=', Input::get('username')));
								if ($salt->getRecords()) {
									$pass = Hash::make(Input::get('password'), $salt->getFirst()->salt);
									$exists = $this->db->get('password', 'users', array('password', '=', $pass));
									if (!$exists->getRecords()) {
										$this->addError($field, 3);
									}
								}
								break; */
						}
					} else {
						$this->addError('username_korisnika', "Krivi podaci. Pokušajte ponovo!");
						$this->addError('password_korisnika', "Krivi podaci. Pokušajte ponovo!");
					}
				}
			}
		}
		
		if(empty($this->errors)){
			$this->passed = true;
		}
		return $this;
	}

	private function addError($field, $error)
	{
		$this->errors[$field]= $error;
	}
	
	public function hasError($field)
	{
		if(isset($this->errors{$field})){
			return $this->errors{$field};
		}
		return false;
	}
	
	public function passed()
	{
		return $this->passed;
	}

	public function imei_check()
	{
		if($this->passed){
			$sql = "SELECT imei_check FROM client_options as co JOIN companys as c ON c.client_id = co.client_id JOIN users as u ON u.company_id = c.id WHERE u.username = ?";
			$imei_check = $this->db->query($sql, array(Input::get('username')));
			$imei_check = $imei_check->getFirst()->imei_check;
			
			if (!$imei_check) {    // imei ne treba provjeravati
				$this->imei_checked = true;
			} else {     // imei treba provjeriti
				$sql_imei = "SELECT id, company_id FROM mobiles WHERE imei_1 = ? OR imei_2 = ?";
				$check_imei = $this->db->query($sql_imei, array(Input::get('imei'), Input::get('imei')));

				if($check_imei->getRecords()){
					// Provjera da li imei mobitela pripada istoj tvrtci kao i user
					$com_id = $this->db->get('company_id', 'users', array('username', '=', Input::get('username') ));
					if($check_imei->getFirst()->company_id == $com_id->getFirst()->company_id){
						$this->imei_checked = true;
					}					
				}
			}
		}
		return $this->imei_checked;
	}
}