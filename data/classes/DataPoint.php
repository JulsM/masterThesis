<?php 

class DataPoint {

	public $latitude;

	public $longitude;

	public $distance;

	public $altitude;

	public $stravaAlt;

	public $time;

	public function __construct($param) {
		$this->latitude = $param['lat'];
		$this->longitude = $param['long'];
		$this->distance = $param['dist'];
		$this->altitude = $param['alt'];
		$this->stravaAlt = $param['stravaAlt'];
		$this->time = $param['time'];

	}

	public function toString() {
		echo 'Data point: lat '.$this->latitude.', long '.$this->longitude.', distance '.$this->distance.', altitude '.$this->altitude.', time '.$this->time.'<br>';
	}

}