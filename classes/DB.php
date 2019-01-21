<?php

class DB
{
	private static $instance = null;
	private $config;
	private $conn;
	private $query;
	private $error = false;
	private $results;
	private $records = 0;
	private $lastInsert = 0;
	
	private function __construct()
	{
		$this->config = Helper::getConfig('database');
		$driver = $this->config['driver'];
		$host = $this->config[$driver]['host'];
		$user = $this->config[$driver]['user'];
		$pass = $this->config[$driver]['pass'];
		$db = $this->config[$driver]['db'];
		
		try{
			
			$this->conn = new PDO($driver.':host='.$host.';dbname='.$db.';charset=utf8', $user, $pass);
			
		} catch(PDOException $e){
			
			die($e->getMessage());
		}
	
	}
	
	public static function getInstance()
	{
		if(!self::$instance){
			self::$instance = new DB();  // ili new self()
 		}
		return self::$instance;
	}
	
	/**
	* Create database queries.
	*
	* @param string $sql
	* @param array $params
	* @return DB object
	*/
	public function query($sql, $params = array())
	{
		$this->error = false;
		
		try{
			$this->query = $this->conn->prepare($sql);
		} catch (PDOException $e){
			$this->error = true;
		}
		
		if(!$this->error){
			if(!empty($params)){
				$x = 1;
				foreach($params as $param) {
					$this->query->bindValue($x, $param);
					$x++;
				}
			}
			
			if($this->query->execute()){
			$this->results = $this->query->fetchAll($this->config['fetch']);
			$this->records = $this->query->rowCount();
			} else {
				$this->error = true;
			}
		}
		
		
		return $this;
	}
	
		/**
	* Run database querie.
	*
	* @param string $action
	* @param string $table
	* @param array $where
	* @return DB object
	*/
	public function action($action, $table, $where = array())
	{
		if(count($where) === 3){
			$operators = array('=', '<', '>', '<=', '>=', '<>');
			
			$field 		= $where[0];
			$operator 	= $where[1];
			$value 		= $where[2];
			
			if(in_array($operator, $operators)){
				$sql = "{$action} FROM {$table} WHERE {$field} {$operator} ?";
				
				if(!$this->query($sql, array($value))->error){
					return $this;
				}
			}
			
			return $this;
		} else {
			$sql = "{$action} FROM {$table}";
			if(!$this->query($sql)->error){
				return $this;
			}
		}
		
		return false;
	}
	
	/**
	* Run SELECT query.
	*
	* @param string $fields
	* @param string $table
	* @param array $where
	* @return DB object
	*/
	public function get($fields, $table, $where = array())
	{
		return $this->action("SELECT {$fields}", $table, $where);
	}
	
	/**
	*Select all data by ID.
	*
	* @param int $id
	* @param string $table
	* @return DB object
	*/
	public function find($id, $table)
	{
		return $this->action("SELECT *", $table, array('id', '=', '$id'));
	}
	
	/**
	* Run DELETE .
	*
	* @param string $table
	* @param array $where
	* @return DB object
	*/
	public function destroy($table, $where)
	{
		return $this->action("DELETE", $table, $where);
	}
	
	/**
	* INSERT new data.
	*
	* @param string $table
	* @param array $fields
	* @return bool
	*/
	public function insert($table, $fields)
	{
		$this->lastInsert = 0;
		$fields_name = implode(',', array_keys($fields));
		$values = '';
		$x = 1;
		$el_num = count($fields);
		
		foreach($fields as $field){
			$values .= '?';
			if($x < $el_num){
				$values .= ',';
			}
			$x++;
		}
		
		$sql = "INSERT INTO {$table} ({$fields_name}) VALUES ({$values})";
		
		if(!$this->query($sql, $fields)->error) {
			$this->lastInsert = $this->conn->lastInsertId();
			$lastID = $this->lastInsert;
			return $this;
		}
		return false;
	}
	
	/**
	* Run UPDATE.
	*
	* @param string $table
	* @param array $fields
	* @param array $where
	* @return bool
	*/
	public function update($table, $fields, $where = array())
	{
		$set = '';
		$x = 1;
		$el_num = count($fields);
		
		foreach($fields as $field => $value){
			$set .= "{$field} = ?";
			if($x < $el_num){
				$set .= ',';
			}
			$x++;
		}
		
		$sql = "UPDATE {$table} SET {$set}";
		
		if(!empty($where)){
			$field = array_keys($where)[0];
			$value = $where[$field];
			$sql .= " WHERE {$field} = ?";
			
			$fields = array_merge($fields, (array)$value);
		}
		
		if(!$this->query($sql, $fields)->error){
			return true;
		}
		return false;
	}
	
	
	
	/*###### GETERS #############*/
	/**
	* RETURN $conn
	*/
	public function getConn()
	{
		return $this->conn;
	}
	
	/**
	* Return all results
	* 
	* @return $results
	*/
	public function getResults()
	{
		return $this->results;
	}
	
	/**
	* Return first results
	* 
	* @return $results[0]
	*/
	public function getFirst()
	{
		return $this->results[0];
	}
	
	/**
	* Return number of records
	* 
	* @return $records
	*/
	public function getRecords()
	{
		return $this->records;
	}
	public function getError()
	{
		return $this->error;
	}
	public function getLastId()
	{
		return $this->lastInsert;
	}
}











