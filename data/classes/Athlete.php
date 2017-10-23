<?php 
class Athlete {

	public $id;

	public $token;

	public $name;

	public $gender;

	public $activities;

	public $weeklyMileage; 

	public $typeCategory; //todo

	public $averageTrainingPace; 

	public $averageRacePace; 

	public $averageElevationGain;

	public $fitness; //todo

	public $tapering; //todo

	




	public function __construct($athlete, $type, $token=null) {
		if($type == 'strava') {
			$this->id = $athlete['id'];
			$this->name = $athlete['firstname'].' '.$athlete['lastname'];
			$this->gender = $athlete['sex'];
			$this->token = $token;
			$this->activities = [];
			$this->weeklyMileage = 0;
			$this->averageRacePace = 0;
			$this->averageTrainingPace = 0;
			$this->averageElevationGain = 0;
			$this->averagePercentageHilly = 0;
		} else {
			$this->id = $athlete['strava_id'];
			$this->token = $athlete['token'];
			$this->name = $athlete['name'];
			$this->gender = $athlete['gender'];
			$this->weeklyMileage = $athlete['weekly_mileage'];
			$this->averageTrainingPace = $athlete['average_training_pace'];
			$this->averageRacePace = $athlete['average_race_pace'];
			$this->averageElevationGain = $athlete['average_elevation_gain'];
			$this->averagePercentageHilly = $athlete['average_percentage_hilly'];
			// $this->activities = Activity::loadActivities($this->id);
		}
	}


	public function calculateAveragePaces() {
		$racePace = 0;
		$raceCount = 0;
		$trainingPace = 0;
		$trainingCount = 0;
		for($i = 0; $i < count($this->activities); $i++) {
			$ac = $this->activities[$i];
			if($ac->activityType == 'race') {
				$racePace += $ac->averageSpeed;
				$raceCount++;
			} else {
				$trainingPace += $ac->averageSpeed;
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

	public function calculateWeeklyMileage() {
		$dateFirstActivity = new DateTime($this->activities[0]->date);
		$dateNow = new DateTime('now');
		$diff = date_diff($dateFirstActivity, $dateNow);
		$weeks = $diff->format('%a') / 7;
		$mileage = 0;
		for($i = 0; $i < count($this->activities); $i++) {
			$ac = $this->activities[$i];
			$mileage += $ac->distance;
		}
		if($mileage > 0) {
			$this->weeklyMileage = $mileage / $weeks;
		}
		
		// echo $mileage;
		// echo date('d.m.y', strtotime(Config::$weeksIntoPast));

	}

	public function calculateAverageElevation() {
		$elevation = 0;
		for($i = 0; $i < count($this->activities); $i++) {
			$elevation += $this->activities[$i]->elevationGain;
		}
		if($elevation > 0) {
			$this->averageElevationGain = $elevation / count($this->activities);
		}
		

	}

	public function calculateAverageHilly() {
		$hilly = 0;
		for($i = 0; $i < count($this->activities); $i++) {
			$hilly += $this->activities[$i]->percentageHilly;
		}
		if($hilly > 0) {
			$this->averagePercentageHilly = $hilly / count($this->activities);
		}
		

	}

	public function updateAthlete() {
		global $db;
		if(count($this->activities) > 0) {
			$this->calculateWeeklyMileage();
			$this->calculateAveragePaces();
			$this->calculateAverageElevation();
			$this->calculateAverageHilly();
		} else {
			$this->weeklyMileage = 0;
			$this->averageRacePace = 0;
			$this->averageTrainingPace = 0;
			$this->averageElevationGain = 0;
			$this->averagePercentageHilly = 0;
		}
		

		$db->updateAthlete($this);
	}

	public function getNumberActyvityType($type) {
		$count = 0;
		foreach ($this->activities as $ac) {
			if($ac->activityType == $type) {
				$count++;
			}
		}
		return $count;
	}

	


	

	public function printAthlete() {
		echo '<h3>Athlete "'.$this->name.'" </h3>';
		echo 'Gender: '.$this->gender.'<br>';
		echo 'Number activities: '.count($this->activities).'<br>';
		echo 'Number races: '.$this->getNumberActyvityType('race').'<br>';
		echo 'Number speedwork: '.$this->getNumberActyvityType('speedwork').'<br>';
		echo 'Number long runs: '.$this->getNumberActyvityType('long run').'<br>';
		if($this->averageTrainingPace > 0) {
			echo 'Average training pace: '.floor((1000/$this->averageTrainingPace/60)). ':'.(1000/$this->averageTrainingPace%60) .' min/km<br>';
		}
		if($this->averageRacePace > 0) {
			echo 'Average race pace: '.floor((1000/$this->averageRacePace/60)). ':'.(1000/$this->averageRacePace%60) .' min/km<br>';
		}
		echo 'Weekly mileage: '.round($this->weeklyMileage/1000, 2).' km<br>';
		echo 'Average elevation gain: '.round($this->averageElevationGain, 2).' m<br>';
		echo 'Average percentage hilly: '.round($this->averagePercentageHilly * 100, 2).' % (flat: '.(100 - round($this->averagePercentageHilly * 100, 2)).' %)<br>';
	}

}