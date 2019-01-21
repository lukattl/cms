<?php

class User
{
	protected $db;
	private $config;
	protected $data;
	private $session_name;
	private $is_logged_in = false;
	private $company = false;
	private $queryData;
	private $billResults;
	private $totalsResults;
	private $insert = false;
	
	public function __construct($user = null)
	{
		$this->config = Helper::getConfig('session');
		$this->session_name = $this->config['session']['session_name'];
		$this->db = DB::getInstance();

		if(!$user){
			if(Session::exists($this->session_name)){
				$user = Session::get($this->session_name);
				if($this->find((int)$user)){
					$this->is_logged_in = true;
				} else {
					$this->logout();
				}
			}

		} else {
			$find_user = $this->find($user);

			if ($find_user) {
				$user_id = $this->data->id;
				$login = $this->data->login;
				
				if ($login) {
					$this->is_logged_in = true;
					$this->m_logged_id = $user_id;
				} 

				$this->options($user_id);

			}			
		}
	}

	public function setCompany()
	{
		$company = $this->db->get('id_tvrtke, naziv_tvrtke, oib_tvrtke, klijent_id', 'tvrtka', array('id_tvrtke', '=', $this->data->tvrtka_id));
		if ($company->getRecords()) {
			$this->company = $company->getFirst();
		}
	}

	public function setQueryData($data)
	{
		$from = "{$data['from']} 00:00:00";
		$to = "{$data['to']} 23:59:59";
		$data['from'] = $from;
		$data['to'] = $to;
		
		if ($data['pp'] === 'Posl. prostor') {
			$data['pp'] = 0;
		}
		if ($data['operater'] === 'Operater') {
			$data['operater'] = 0;
		}
		
		$this->queryData = $data;
		$analyse = $this->analyseDates();
		return $analyse;
	}

	public function analyseDates()
	{
		$from = explode('-', $this->queryData['from']);
		$to = explode('-', $this->queryData['to']);
		$fromYear = $from[0];
		$toYear = $to[0];

		if ($fromYear == $toYear) {
			$table = array('year1' => $fromYear, 'year2' => 0,  'table_number' => 1);
			$data = array_merge($this->queryData, $table);
			$this->queryData = $data;
		} elseif ($toYear - $fromYear <= 1) {
			$table = array('year1' => $fromYear, 'year2' => $toYear, 'table_number' => 2);
			$data = array_merge($this->queryData, $table);
			$this->queryData = $data;
		} else {
			return false;
		}
		return true;		
	}

	public function billResults()
	{
		$whereExtras = '';
		if ($this->queryData['pp']) {
		$whereExtras .= " AND naziv_pp = '{$this->queryData['pp']}'";
		}
		if ($this->queryData['operater']) {
		$whereExtras .= " AND ime_prezime_operatera = '{$this->queryData['operater']}'";
		}

		if ($this->queryData['table_number'] == 2) {
			$sql = 
			"SELECT concat(broj_racuna, ' / ', naziv_pp, ' / ', oznaka_nu) as broj_racuna, ukupan_iznos_racuna, datum_vrijeme_kreiranja_racuna, ime_prezime_operatera, tip_racuna, vrsta_placanja_racuna, status_racuna 
			FROM racun_{$this->queryData["year1"]} 
			WHERE oib_tvrtke = {$this->getCompany()->oib_tvrtke} 
			AND datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["year1"]}-12-31 23:59:59'{$whereExtras} 
			UNION ALL 
			SELECT concat(broj_racuna, ' / ', naziv_pp, ' / ', oznaka_nu) as broj_racuna, ukupan_iznos_racuna, datum_vrijeme_kreiranja_racuna, ime_prezime_operatera, tip_racuna, vrsta_placanja_racuna, status_racuna 
			FROM racun_{$this->queryData["year2"]} 
			WHERE oib_tvrtke = {$this->getCompany()->oib_tvrtke} 
			AND datum_vrijeme_kreiranja_racuna between '{$this->queryData["year2"]}-01-01 0:00:00' AND '{$this->queryData["to"]}'{$whereExtras} order by datum_vrijeme_kreiranja_racuna";
			$query = $this->db->query($sql, array($this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke));
		} elseif ($this->queryData['table_number'] == 1){
			$sql = 
			"SELECT concat(broj_racuna, ' / ', naziv_pp, ' / ', oznaka_nu) as broj_racuna, ukupan_iznos_racuna, datum_vrijeme_kreiranja_racuna, ime_prezime_operatera, tip_racuna, vrsta_placanja_racuna, status_racuna 
			FROM racun_{$this->queryData["year1"]} 
			WHERE oib_tvrtke = {$this->getCompany()->oib_tvrtke} 
			AND datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["to"]}'{$whereExtras}  order by datum_vrijeme_kreiranja_racuna";
			$query = $this->db->query($sql, array($this->getCompany()->oib_tvrtke));
		} 
			
		if ($query->getRecords() && !$query->getError()) {
			$this->billResults = $query->getResults();
			return true;
		}
		return false;
	}

	public function totalsResults()
	{
		$whereExtras = '';
		if ($this->queryData['pp']) {
		$whereExtras .= " AND NAZIV_PP = '{$this->queryData['pp']}'";
		}
		if ($this->queryData['operater']) {
		$whereExtras .= " AND IME_PREZIME_OPERATERA = '{$this->queryData['operater']}'";
		}

		if ($this->queryData['table_number'] == 2) {
			$sql = 
			"SELECT sum(ukupno), sum(kartice), sum(gotovina) from 
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) as ukupno,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year1"]}
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["year1"]}-12-31 23:59:59' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Kartice' {$whereExtras}) as kartice,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year1"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["year1"]}-12-31 23:59:59' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Gotovina' {$whereExtras}) as gotovina
			from racun_{$this->queryData["year1"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["year1"]}-12-31 23:59:59' 
			AND oib_tvrtke = ?{$whereExtras}
			UNION ALL 
			SELECT ifnull(sum(ukupan_iznos_racuna), 0) as ukupno,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year2"]}
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["year2"]}-01-01 0:00:00' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Kartice' {$whereExtras}) as kartice,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year2"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["year2"]}-01-01 0:00:00' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Gotovina' {$whereExtras}) as gotovina
			from racun_{$this->queryData["year2"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["year2"]}-01-01 0:00:00' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ?{$whereExtras})";
			$query = $this->db->query($sql, 
			array($this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke));

		} elseif ($this->queryData['table_number'] == 1){
			$sql = 
			"SELECT ifnull(sum(ukupan_iznos_racuna), 0) as ukupno,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year1"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Kartice' {$whereExtras}) as kartice,
			(SELECT ifnull(sum(ukupan_iznos_racuna), 0) from racun_{$this->queryData["year1"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ? AND vrsta_placanja_racuna = 'Gotovina' {$whereExtras}) as gotovina
			from racun_{$this->queryData["year1"]} 
			WHERE datum_vrijeme_kreiranja_racuna between '{$this->queryData["from"]}' AND '{$this->queryData["to"]}' 
			AND oib_tvrtke = ?{$whereExtras}";
			$query = $this->db->query($sql, array($this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke, $this->getCompany()->oib_tvrtke));
		} 
		if ($query->getRecords() && !$query->getError()) {
			$this->totalsResults = $query->getResults();
			return true;
		}
		return false;
	}
	
	public function find($user = null)
	{
		if($user){
			$field = (is_int($user)) ? 'id_korisnika' : 'username_korisnika';
			$data = $this->db->get('id_korisnika, ime_korisnika, prezime_korisnika, oib_korisnika, status_korisnika,  username_korisnika, password_korisnika, uloga, tvrtka_id, web_pass', 'korisnik', array($field, '=', $user));
			if($data->getRecords()){
				$this->data = $data->getFirst();
				$this->setCompany();
				return true;
			}
		}
		return false;
	}
	
	public function create ($fields = array())
	{
		if(!$this->db->insert('users', $fields)){
			throw new Exception('There was a problem creating an user!');
		}
	}

	public function newUser($fields = array())
	{
		if ($fields) {
			$userExists = $this->userExistsActivate($fields['oib'], $fields['name'], $fields['surname']);
			if (!$userExists) {
				$data = array(
					"ime_korisnika" => $fields['name'],
					"prezime_korisnika" => $fields['surname'],
					"username_korisnika" => $fields['oib'],
					"oib_korisnika" => $fields['oib'],
					"password_korisnika" => md5(substr($fields['oib'], 0, 4)),
					"uloga" => $fields['role'],
					"tvrtka_id" => $this->company->id_tvrtke
				);
				$insert = $this->db->insert('korisnik', $data);
				if($insert){
					return true;
				}
			} else {
				return true;
			}
		}
		return false;
	}

	public function userExists($oib)
	{
		if ($oib) {
			$sql ="SELECT id_korisnika from korisnik where oib_korisnika = ? and tvrtka_id = ?";
			$check = $this->db->query($sql, array($oib, $this->company->id_tvrtke));
			if ($check->getRecords() && !$check->getError()) {
				return $check->getFirst()->id_korisnika;
			}
		}
		return false;
	}

	public function userExistsActivate($oib, $name, $surname)
	{
		if ($oib) {
			$sql ="SELECT id_korisnika, status_korisnika from korisnik where ime_korisnika = ? and prezime_korisnika = ? and oib_korisnika = ? and tvrtka_id = ?";
			$check = $this->db->query($sql, array($oib, $name, $surname, $this->company->id_tvrtke));
			if ($check->getRecords() && !$check->getError()) {
				if ($check->getFirst()->status_korisnika) {
					return true;
				} else {
					$activateUser = $this->activateUser($check->getFirst()->id_korisnika);
					if ($activateUser) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function activateUser($id)
	{
		if ($id) {
			$update = $this->db->update('korisnik', array('status_korisnika' => 1), array('id_korisnika' => $id));
			if ($update) {
				return true;
			}
		}
		return false;
	}
	
	public function mLogin()
	{
		$this->db->update('users', array('login' => 1), array('username' => $this->data->username));
		$this->m_logged_id = $this->data->id;
		$this->is_logged_in = true;
	}

	public function changePass($user = null, $new_pass)
	{
		$salt = $this->db->get('salt', 'users', array($field, '=', $user));
		$salt = $salt->getFirst()->salt;

		$password = Hash::make($new_pass, $salt);
		$update = $this->db->update('users', array('password' => $password), array($field => $user));
		if(!$update){
			throw new Exception('There was a problem changing password!');
		}
		return true;
	}

	public function login($username = null, $password = null)
	{
		$user = $this->find($username);
		if($user){
			#if($this->data->password === Hash::make($password, $this->data->salt)){
			if($this->data->web_pass === md5($password)){
				Session::put($this->session_name, $this->data->id_korisnika);
				Session::put('role', $this->getData()->uloga);
				Session::put('client', $this->company->klijent_id);
				return true;
			}
		}
		return false;
	}
	
	public function logout()
	{
		Session::delete($this->session_name);
	}

	public function check()
	{
		return $this->is_logged_in;
	}

	public function logged_id()
	{
		return $this->m_logged_id;
	}

	public function getData()
	{
		return $this->data;
	}

	public function getQueryData()
	{
		return $this->queryData;
	}

	public function getDb()
	{
		return $this->db;
	}

	public function getCompany()
	{
		return $this->company;
	}

	public function showBillResults()
	{
		return $this->billResults;
	}

	public function showTotalsResults()
	{
		return $this->totalsResults;
	}
}


















