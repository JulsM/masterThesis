<?php 

class DataPoint {

	public $latitude;

	public $longitude;

	public $distance;

	public $altitude;

	public $stravaAlt;

	public $time;

	public $velocity;

	public $grade;

	public $ngp;

	public function __construct($param) {
		$this->latitude = $param['lat'];
		$this->longitude = $param['long'];
		$this->distance = $param['dist'];
		$this->altitude = $param['alt'];
		$this->stravaAlt = $param['stravaAlt'];
		$this->time = $param['time'];
		$this->velocity = $param['velocity'];
		$this->grade = 0;
		$this->ngp = 0;

	}

	public static function calculateGrade($datapoints) {
		$l = $datapoints[1]->distance - $datapoints[0]->distance;
		if ($l > 0) {
		    $datapoints[0]->grade = round(($datapoints[1]->altitude - $datapoints[0]->altitude) / $l * 100 / 2, 2);
		} 

		for($i = 1; $i < count($datapoints) - 1; $i++) {
			$prevPoint = $datapoints[$i - 1];
			$currentPoint = $datapoints[$i];
			$nextPoint = $datapoints[$i + 1];
			$grade = 0;
			$l = $nextPoint->distance - $prevPoint->distance;
			if ($l > 0) {
		        $currentPoint->grade = round(($nextPoint->altitude - $prevPoint->altitude) / $l * 100 / 2, 2);
		    }
		}

		$l = $datapoints[count($datapoints)-1]->distance - $datapoints[count($datapoints)-2]->distance;
		if ($l > 0) {
		    $datapoints[count($datapoints)-1]->grade = round(($datapoints[count($datapoints)-1]->altitude - $datapoints[count($datapoints)-2]->altitude) / $l * 100 / 2, 2);
		} 
		return $datapoints;
	}

	public function toString() {
		echo 'Data point: lat '.$this->latitude.', long '.$this->longitude.', distance '.$this->distance.', altitude '.$this->altitude.', time '.$this->time.', velocity '.$this->velocity.'<br>';
	}

}