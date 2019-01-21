<?php

class Drive
{
	protected $db;						// db connection
	private $data;						// data from this drive
	protected $user_data; 				// user_id driver/dispatcher, vehicle_id 
	protected $state = null;			// state of drive (assign, accept, start, stop)
	private $type = null;				// type of drive (dispatch, app, gps/tx/deal)
	protected $coords;					// data coords (accept, start, stop, order)
	private $drive_coord_id = null;		// id row from drive_coords table to update from sync link
	private $client_keys;				// client keys Google, Here
	private $client_options;			// client options
	private $log;						// log data
	private $gps_data;					// gps data from sync link to update track table
	private $track_id = false;			// id row tracks table to update from sync link
	private $order_data;				// data from web dispatch to create drive
	private $from = null;				// dispatch/app/driver

	public function __construct($user_id = null, $vehicle_id = null, $id = null, $from = null)
	{
		$this->db = DB::getInstance();
		$this->from = $from;			
		if ($from == 'dispatch') {
			$this->setState(1);
		}
		$user_id || $vehicle_id ? $this->user_data = array($user_id, $vehicle_id) : ' ' ;

		$sql_options = "SELECT co.dispatch, co.client_id FROM client_options as co JOIN companys as com ON co.client_id = com.client_id JOIN users as u ON com.id = u.company_id WHERE u.id = ?";
		$options = $this->db->query($sql_options, array($this->user_data[0]));
		if ($options->getRecords()) {
			$this->client_options = array(
				"dispatch" => $options->getFirst()->dispatch,
				"client_id" => $options->getFirst()->client_id
				);
		}

		$sql_keys = "SELECT here_app_id, here_app_code FROM client_keys as ck JOIN companys as com ON ck.client_id = com.client_id JOIN users as u ON com.id = u.company_id WHERE u.id = ?";
		$keys = $this->db->query($sql_keys, array($this->user_data[0]));
		if ($keys->getRecords()) {
			$this->client_keys = array(
				"here_app_id" => $keys->getFirst()->here_app_id,
				"here_app_code" => $keys->getFirst()->here_app_code); 
		}
		
		if ($id) {
			$drive = $this->db->get('id, drive_coord_id, start_time', 'drives', array('id', '=', $id));
			if ($drive->getRecords()) {
				$this->data = $drive->getFirst();
				$this->drive_coord_id = $drive->getFirst()->drive_coord_id;

			}
		}

		if ($from != 'dispatch') {
			$track = $this->db->get('id', 'tracks', array('user_id', '=', $this->user_data[0]));
			if ($track->getRecords()) {
				$this->track_id = $track->getFirst()->id;
			}
		}
	}

	public function setType($type)
	{
		$this->type = $type;
	}
	
	public function setState($state)
	{
		switch ($state) {
			case 0:
				$state = 'accept';
				break;
			case 1:
				$state = 'start';
				break;
			case 2:
				$state = 'stop';
				break;
			case 3:
				$state = 'update';
				break;
		}
		$this->state = $state;
		return $this->state;
	}

	public function setCoords($coords)
	{
		$this->coords = $coords;
		if ($coords['stop_order_address']) {
			$this->setStopCoords();
		}
		$this->insertCoords();
	}

	public function setStopCoords()
	{
		$this->getCoords($this->coords['stop_order_address'], 'stop');
	}

	public function setGpsData($gps_data = null)
	{
		if ($gps_data) {
			switch ($this->state) {
				case 'start':
					$state = 2;
					break;
				case 'stop':
					$state = 0;
					break;
				case 'accept':
					$state = 1;
					break;
			}
			$gps_data += [ "user_id" => $this->user_data[0], 
							"lat" => $this->coords["{$this->state}_coord1"], 
							"lng" => $this->coords["{$this->state}_coord2"], 
							"state" => $state];
			$this->gps_data = $gps_data;
			$this->setTrack();
		}
	}

	public function setOrderData($order_data)
	{
		$this->order_data = $order_data;
	}

	public function setTrack()
	{
		if ($this->from == 'driver') {
			dd($this->from);
			if ($this->track_id) {
				$data = $this->gps_data;
				unset($data['user_id']);
				$update = $this->db->update('tracks', $data, array('user_id' => $this->user_data[0]));
			} else {
				$insert = $this->db->insert('tracks', $this->gps_data);
			}
		}
	}

	public function getAddressFromTable()
	{
		# code...
	}

	public function getAddressFromApi()
	{
		$url = "https://reverse.geocoder.api.here.com/6.2/reversegeocode.json?";
		$url .= "app_id={$this->client_keys["here_app_id"]}&app_code={$this->client_keys["here_app_code"]}&";
		$url .= "mode=retrieveAddresses&";
		$url .= "prox={$this->coords["{$this->state}_coord1"]},{$this->coords["{$this->state}_coord2"]},0";
		$url .= "&locationattributes=none,address";

		$json = file_get_contents($url);
		$json = json_decode($json);
		$address = $json->{'Response'}->{'View'}[0]->{'Result'}[0]->{'Location'}->{'Address'};
		
		if (property_exists($address, 'HouseNumber')) {
			$street = $address->{'Street'};
			$number = $address->{'HouseNumber'};
			$district = $address->{'District'};
			$city = $address->{'City'};
			$address = "$street, $number, $district, $city";
			$this->coords["{$this->state}_address"] = $address;
			#Helper::loger('api_address', 'with house number');
			return $address;		
		}
		$address = $address->{'Label'};
		$this->coords["{$this->state}_address"] = $address;
		#Helper::loger('api_address', 'just label');
		return $address;
		
	}
	
	public function getAddress()
	{
		$fromTable = $this->getAddressFromTable();
		if (!$fromTable) {
			$fromApi = $this->getAddressFromApi();
		}
	}

	public function getCoordsFromTable($location, $state = null)
	{
		$sql_addr = "SELECT lat, lng FROM addresses as a JOIN companys as com ON a.client_id = com.client_id JOIN users as u ON com.id = u.company_id WHERE u.id = ? AND a.name = ?";
		$address = $this->db->query($sql_addr, array($this->user_data[0], $location));
		if ($address->getRecords()) {
			if ($this->type == 'dispatch' || $this->type == 'app') {
				$this->coords["{$state}_order_coord1"] = $address->getFirst()->lat;
				$this->coords["{$state}_order_coord2"] = $address->getFirst()->lng;
				return true;
			} else {
				$this->coords["{$state}_coord1"] = $address->getFirst()->lat;
				$this->coords["{$state}_coord2"] = $address->getFirst()->lng;
				return true;
			}
		} else {
			
			$sql_poi = "SELECT lat, lng FROM pois as p JOIN companys as com ON p.client_id = com.client_id JOIN users as u ON com.id = u.company_id WHERE u.id = ? AND p.name = ?";
			$poi = $this->db->query($sql_poi, array($this->user_data[0], $location));
			if ($poi->getRecords()) {
				if ($this->type == 'dispatch' || $this->type == 'app') {
					$this->coords["{$state}_order_coord1"] = $poi->getFirst()->lat;
					$this->coords["{$state}_order_coord2"] = $poi->getFirst()->lng;
					return true;
				} else {
					$this->coords["{$state}_coord1"] = $poi->getFirst()->lat;
					$this->coords["{$state}_coord2"] = $poi->getFirst()->lng;
					return true;
				}
			}
		}
		return false;
	}

	public function getCoordsFromApi($address)
	{
		# Here / Google
	}

	public function getCoords($address, $state = null) 
	{
		if ($state == NULL) {
			$state = $this->state;
		}
		$fromTable = $this->getCoordsFromTable($address, $state);
		if (!$fromTable) {
			$fromApi = $this->getCoordsFromApi($address, $state);
		}
	}

	public function analyzeCoords($state = null)
	{
		if ($state == NULL) {
			$state = $this->state;
		}
		if ($this->type == 'dispatch' || $this->type == 'app') {
			if (!$this->coords["{$state}_order_coord1"] || !$this->coords["{$state}_order_coord2"]) {
				$this->getCoords($this->coords["{$state}_order_address"]);
			} elseif ($this->coords["{$state}_order_address"] == NULL) {
				$this->getAddress($this->coords["{$state}_order_coord1"], $this->coords["{$state}_order_coord2"]);
			}
		} else {
			if (!$this->coords["{$state}_coord1"] || !$this->coords["{$state}_coord2"]) {
				$this->getCoords($this->coords["{$state}_address"]);
			} elseif ($this->coords["{$state}_address"] == NULL) {
				$this->getAddress($this->coords["{$state}_coord1"], $this->coords["{$state}_coord2"]);
			}
		}
	}

	public function insertCoords()
	{
		$this->analyzeCoords();
		if ($this->type == 'dispatch' || $this->type == 'app') {
			$coords = Helper::change_key($this->coords, 'start_coord1', 'start_order_coord1');
			$coords = Helper::change_key($coords, 'start_coord2', 'start_order_coord2');
			$coords = Helper::change_key($coords, 'start_address', 'start_order_address');
			$coords = Helper::change_key($coords, 'stop_coord1', 'stop_order_coord1');
			$coords = Helper::change_key($coords, 'stop_coord2', 'stop_order_coord2');
			$coords = Helper::change_key($coords, 'stop_address', 'stop_order_address');
		} else {
			$coords = $this->coords;
		}

		if ($this->drive_coord_id) {
			$update = $this->db->update('drive_coords', $coords, array('id' => $this->drive_coord_id));
			if ($update) {
				return $this->drive_coord_id;
			}
			return false;

		} else {
			$insert = $this->db->insert('drive_coords', $coords);
			if ($insert) {
				if ($this->type == 'dispatch' || $this->type == 'app') {
					$sql = "SELECT id FROM drive_coords WHERE {$this->state}_order_address = ? ORDER BY id DESC LIMIT 1";
					$id = $this->db->query($sql, array($this->coords["{$this->state}_order_address"]));
				} else {
					$sql = "SELECT id FROM drive_coords WHERE {$this->state}_address = ? ORDER BY id DESC LIMIT 1";
					$id = $this->db->query($sql, array($this->coords["{$this->state}_address"]));
				}
				
				if ($id->getRecords()) {
					$id = $id->getFirst()->id;
					return $id;
				}
			}
		}
		return false;
	}

	public function start($data)
	{
		if (!$this->data) {  ///////// Start nove vožnje
			$insert = $this->db->insert('drives', $data);
			if($insert){
				$sql = "SELECT id FROM drives WHERE user_id = ? AND start_time = ? ORDER BY id DESC LIMIT 1";
				$id = $this->db->query($sql, (array($data['user_id'], $data['start_time'])));
				if ($id->getRecords()) {
					$id = $id->getFirst()->id;
					return $id;
				}
			}
		} else {   //////////// Startanje prihvaćene vožnje
			# TO DO update vožnje koja je prihvaćena
		} 	
		return false;
	}

	public function stop($data)
	{
		$update = $this->db->update('drives', $data, array('id' => $this->data->id));
		if ($update) {
			return true;
		}
		Helper::logerArr('stop', $this->user_data[0], $this->coords);
		Helper::logerArr('stop', $this->user_data[0], $data);
		return false;
	}

	/**
	 * $coords1 = array($user_coord1, $user_coord2)
	 * $coords2 = array($address_coord1, $address_coord2)
	 */
	public function getTravelDuration($coords1, $coords2){
	    $out = array('Nema podataka', 'Nema podataka');
	    if (!empty($coords1[0]) && !empty($coords1[1]) && !empty($coords2[0]) && !empty($coords2[1])) {
	        $check = 1;
	    } else {
	        $check = 0;
	    }

	    if ($check) {
	        $url = 'https://route.api.here.com/routing/7.2/calculateroute.json?waypoint0='.$coords1[0].'%2C'.$coords1[1].'&waypoint1='.$coords2[0].'%2C'.$coords2[1].'&mode=fastest%3Bcar%3Btraffic%3Aenabled&app_id=80lVeZw9H1gss5MXagvG&app_code=wO_Bvbe3Zo8-8t9BD_BB6w&departure=now';
	        $json = file_get_contents($url);
	        $json = json_decode($json);

	        if ($json != null) {
	            $dist = $json->{'response'}->{'route'}[0]->{'summary'}->{'distance'};
	            $time = $json->{'response'}->{'route'}[0]->{'summary'}->{'trafficTime'};
				$out = array(intval($time/60), $dist, 'here');

	            if (isset($out[0]) && isset($out[1])) {
	                return $out;
	            }

	            // Ako HERE Maps vrati za dist|time NULL onda ide na Google Maps
	            // Google Maps API
	            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . $coords1[0] . "," . $coords1[1] . "&destinations=" . $coords2[0] . "," . $coords2[1] . "&key=AIzaSyDdey8CeNzmU9sIoDPaH4louChivjrKMns";

	            $json = file_get_contents($url);
	            $response_a = json_decode($json, true);
				
	            if ($response_a != '' && $response_a != 1) {
	                $dist = $response_a['rows'][0]['elements'][0]['distance']['value'];
	                $time = $response_a['rows'][0]['elements'][0]['duration']['value'];
	                $out = array(intval($time/60), $dist, 'google');
	            } 
	        }
	    }
	    return $out;
	}

	/**
	 * @point_coords = array(latitude, longitude);
	 * @center_coords = array(latitude, longitude);
	 * @radius in meters
	 * return true(if point in radius from center)/false
	 */
	public function airDist($point_coords = array(), $center_coords = array())
	{
		if (Helper::coordsExists($point_coords) && Helper::coordsExists($center_coords)) {
			$theta = $point_coords[1] - $center_coords[1];
			$dist = sin(deg2rad($point_coords[0])) * sin(deg2rad($center_coords[0])) +  cos(deg2rad($point_coords[0])) * cos(deg2rad($center_coords[0])) * cos(deg2rad($theta));
			$dist = acos($dist);
			$dist = rad2deg($dist);
			$dist = intval($dist * 60 * 1.1515 * 1.609344 * 1000);
			return $dist;
		}
		return false;
	}

	public function insideRadius($point_coords = array(), $center_coords = array(), $radius)
	{
		if (Helper::arrExists($point_coords) && Helper::arrExists($center_coords)) {
			$dist = $this->airDist($point_coords, $center_coords);
			if ($radius && $dist) {
				if ($dist <= $radius) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * @user_coords, user_id
	 * return stand_id (if exists stand to login)
	 */
	public function standLoginId($user_coords, $user_id)
	{
		if ($user_id) {	# get user coordinates
			$sql="SELECT s.id, s.latitude, s.longitude, s.login_radius FROM stands as s JOIN companys as com ON com.client_id = s.client_id JOIN users as u ON u.company_id = com.id WHERE u.id = ?";
			$stands = $this->db->query($sql, array($user_id));
			if ($stands->getRecords()) {
				$id = NULL;
				foreach ($stands->getResults() as $stand => $value) {
					$center_coords = array(floatval($value->latitude), floatval($value->longitude));
					$inside = $this->insideRadius($user_coords, $center_coords, $value->login_radius);
					if ($inside) {
						$id = $value->id;
					}
				}
				return $id;
			}
		}
		return false;
	}

	/**
	 * update stand_id in tracks table if user can login in the stand
	 * return if update true/false
	 */
	public function standLogin()
	{
		if ($this->standLoginId()) {
			$update = $this->db->update('tracks', array('stand_id' => $this->standLoginId()), array('user_id' => $this->user_data[0]));
			return $update;
		}
	}

	public function usersList()
	{
		$client = $this->client_options['client_id'];
		$extras = $this->order_data['extras'];
		$adds = '';
		if (Helper::arrExists($extras)) { // CPA
			if ($extras[0]) $adds .= ' AND v.cards = 1';
			if ($extras[1]) $adds .= ' AND v.persons = 1';
			if ($extras[2])	$adds .= ' AND v.animals = 1';
		}
		if ($client) {
		$sql = "SELECT u.id, u.state, t.lat, t.lng FROM users u JOIN companys com ON u.company_id = com.id JOIN clients c ON com.client_id = c.id JOIN tracks t ON u.id = t.user_id JOIN vehicles v ON v.user_id = u.id WHERE c.id = ? AND u.active = 1 AND u.login = 1{$adds}";
			$users = $this->db->query($sql, array($client));
			if ($users->getRecords()) {
				$users = $users->getResults();
				return $users;
			}
			return false;
		}
	}

	public function assignDrive($list)
	{
		$log = [];
		$coords2 = array(								// start address coords
			$this->coords['start_order_coord1'], 
			$this->coords['start_order_coord2']);
		$coords3 = array(
			$this->coords['stop_order_coord1'],			// stop address coords
			$this->coords['stop_order_coord2']);
		list($time_start_stop_drive, $dist_start_stop_drive, $provider) = $this->getTravelDuration($coords2, $coords3);

		$assignedUser = $list[0];
		foreach ($list as $user => $value) {
			$coords1 = array($value->lat, $value->lng);     // user coords

			if ($value->state == 0) {												// Dodjela PRVE vožnje
				list($time_user_start, $dist_user_start, $provider) = $this->getTravelDuration($coords1, $coords2);
				$list[$user]->time_to_start = $time_user_start;
				$list[$user]->dist_to_start = $dist_user_start;
				$list[$user]->provider = $provider;
				$list[$user]->time_to_stop = $time_user_start + $time_start_stop_drive;
				$list[$user]->dist_to_stop = $dist_user_start + $dist_start_stop_drive;

				#array_push($log, $list[$user]);

			} else {
				$sql = "SELECT d.id, d.type, d.accept_time, d.time_to_stop, d.dist_to_stop, dc.stop_coord1, dc.stop_coord2, dc.stop_address, dc.stop_order_coord1, dc.stop_order_coord2, dc.stop_order_address FROM drives d JOIN drive_coords dc ON d.drive_coord_id = dc.id WHERE d.user_id = ? AND d.state = 'accept' OR d.state = 'start' ORDER BY d.accept_time asc";
				$acceptedDrive = $this->db->query($sql, array($value->id));
				if ($acceptedDrive->getRecords()) {
					if ($value->state == 1) {
						if ($acceptedDrive->getRecords() <= 1){
							$acceptedDrive = $acceptedDrive->getFirst();
							if ($acceptedDrive->stop_address != NULL) {   // STOP ADRESA
								list($time_stop_start, $dist_stop_start, $provider) = $this->getTravelDuration(array($acceptedDrive->stop_coord1, $acceptedDrive->stop_coord2), $coords2);

							} elseif($acceptedDrive->stop_order_address != NULL) { // STOP ORDER ADRESA
								list($time_stop_start, $dist_stop_start, $provider) = $this->getTravelDuration(array($acceptedDrive->stop_order_coord1, $acceptedDrive->stop_order_coord2), $coords2);
							} else {
								unset($list[$user]);
							}
						}
					} elseif ($value->state == 2){
						if ($acceptedDrive->getRecords() <= 2){
							$acceptedDrive = $acceptedDrive->getFirst();
							if ($acceptedDrive->stop_address != NULL) {   // STOP ADRESA
								list($time_stop_start, $dist_stop_start, $provider) = $this->getTravelDuration(array($acceptedDrive->stop_coord1, $acceptedDrive->stop_coord2), $coords2);

							} elseif($acceptedDrive->stop_order_address != NULL) { // STOP ORDER ADRESA
								list($time_stop_start, $dist_stop_start, $provider) = $this->getTravelDuration(array($acceptedDrive->stop_order_coord1, $acceptedDrive->stop_order_coord2), $coords2);
							} else {
								unset($list[$user]);
							}
						}
					}
				}
				$time_to_start = $acceptedDrive->time_to_stop + $time_stop_start;
				$dist_to_start = $acceptedDrive->dist_to_stop + $dist_stop_start;
				$list[$user]->time_to_start = $time_to_start;
				$list[$user]->dist_to_start = $dist_to_start;
				$list[$user]->provider = $provider;
				$list[$user]->time_to_stop = $time_to_start + $time_start_stop_drive;
				$list[$user]->dist_to_stop = $dist_to_start + $dist_start_stop_drive;

				
			} 
			array_push($log, $list[$user]);
			$option_param = 'dist';					// client option
			$param = "{$option_param}_to_start";

			if ($list[$user]->$param < $assignedUser->$param) {
				$assignedUser = $list[$user];
			}
		}
		dd($log, $assignedUser->id);
		return $assignedUser->id;
	}

	public function createDrive()
	{
		$list = $this->usersList();
		if ($list) {
			$user = $this->assignDrive($list);
			return $user;
		} else {
			return 0;
		}
	}
}