<?php 
class Athlete {

	public $id;

	// public $token;

	public $name;

	public $gender;

	public $activities;

	public $weeklyMileage; 

	public $typeCategory; //todo

	public $averageTrainingPace; 

	public $averageRacePace; 

	public $fitness; //todo

	public $tapering; //todo

	




	public function __construct($stravaAthlete) {
		$this->id = $stravaAthlete['id'];
		$this->name = $stravaAthlete['firstname'].' '.$stravaAthlete['lastname'];
		$this->gender = $stravaAthlete['sex'];
		
	}


	public function calculateAveragePaces($stravaActivities) {
		$racePace = 0;
		$raceCount = 0;
		$trainingPace = 0;
		$trainingCount = 0;
		for($i = 0; $i < count($stravaActivities); $i++) {
			$ac = $stravaActivities[$i];
			if($ac['workout_type'] == 1) {
				$racePace += $ac['average_speed'];
				$raceCount++;
			} else {
				$trainingPace += $ac['average_speed'];
				$trainingCount++;
			}
		}
		if($raceCount > 0) {
			$this->averageRacePace = $racePace / $raceCount;
		}
		if($trainingCount > 0) {
			$this->averageTrainingPace = $trainingPace / $trainingCount;
		}

	}

	public function calculateWeeklyMileage($stravaActivities) {
		$mileage = 0;
		for($i = 0; $i < count($stravaActivities); $i++) {
			$ac = $stravaActivities[$i];
			$mileage += $ac['distance'];
		}
		if($mileage > 0) {
			$this->weeklyMileage = $mileage / abs(Config::$weeksIntoPast);
		}
		
		// echo $mileage;
		// echo date('d.m.y', strtotime(Config::$weeksIntoPast));

	}

	

	public function printAthlete() {
		echo '<h3>Athlete "'.$this->name.'" </h3>';
		echo 'Gender: '.$this->gender.'<br>';
		echo 'Average training pace: '.floor((1000/$this->averageTrainingPace/60)). ':'.(1000/$this->averageTrainingPace%60) .' min/km<br>';
		echo 'Average race pace: '.floor((1000/$this->averageRacePace/60)). ':'.(1000/$this->averageRacePace%60) .' min/km<br>';
		echo 'Weekly mileage: '.round($this->weeklyMileage/1000, 2).' km<br>';
	}

}