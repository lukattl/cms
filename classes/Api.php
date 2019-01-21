<?php

class Api
{
	public $dist;
	public $duration;

	public function here($coords = array())
	{
		$url = 'https://route.api.here.com/routing/7.2/calculateroute.json?waypoint0='.$coords[11].'%2C'.$coords[12].'&waypoint1='.$coords[21].'%2C'.$coords[22].'&mode=fastest%3Bcar%3Btraffic%3Aenabled&app_id=80lVeZw9H1gss5MXagvG&app_code=wO_Bvbe3Zo8-8t9BD_BB6w&departure=now';

		$json = file_get_contents($url);
		$json = json_decode($json);

		$distance = $json->{'response'}->{'route'}[0]->{'summary'}->{'distance'};
		$time = $json->{'response'}->{'route'}[0]->{'summary'}->{'trafficTime'};

		$this->dist = $dist;
		$this->$duration = $time;
	}
}