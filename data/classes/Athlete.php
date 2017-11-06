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

	public $atl;

	public $ctl; 

	public $xWeekSummary;

	




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
			$this->xWeekSummary = new XWeekSummary();
			$this->atl = $this->ctl = 0;
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
			$this->xWeekSummary = unserialize($athlete['serialized_x_week_summary']);
			$this->atl = $athlete['acute_training_load'];
			$this->ctl = $athlete['chronic_training_load'];
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

	public static function getATL($athleteId, $histDate=null) {
		global $db;
		$to = ($histDate == null ? date('Y-m-d H:i:s e',time()) : $histDate);
		$days = 7;
		$from = date('Y-m-d H:i:s e',strtotime($to.' -'.$days.' days'));
        $result = $db->getActivities($athleteId, $from, $to);
        
        $activities = [];
        if(count($result) > 0) {
	        foreach ($result as $ac) {
	        	$activity = new Activity($ac, 'db');
	        	$activities[] = $activity;
	        }
	    }

	    $lambda = 2 / ($days + 1);
	    $atl = 50;
	    if(count($activities) > 2) { // pick start value
	    	$atl = ($activities[0]->tss + $activities[1]->tss) / 2;
	    }
	    for ($i = 0; $i < count($activities); $i++) {
            $atl = $activities[$i]->tss * $lambda + ((1 - $lambda) * $atl);
            // echo $atl . ' '.$activities[$i]->tss.' ';
        }
        return $atl;

	}

	public static function getCTL($athleteId, $histDate=null) {
		global $db;
		$to = ($histDate == null ? date('Y-m-d H:i:s e',time()) : $histDate);
		$days = 42;
		$from = date('Y-m-d H:i:s e',strtotime($to.' -'.$days.' days'));
        $result = $db->getActivities($athleteId, $from, $to);
        $activities = [];
        if(count($result) > 0) {
	        foreach ($result as $ac) {
	        	$activity = new Activity($ac, 'db');
	        	$activities[] = $activity;
	        }
	    }

	    $lambda = 2 / ($days + 1);
	    $ctl = 50;
	    if(count($activities) > 5) { // pick start value
	    	$ctl = ($activities[0]->tss + $activities[1]->tss + $activities[2]->tss + $activities[3]->tss + $activities[4]->tss) / 5;
	    }
	    for ($i = 0; $i < count($activities); $i++) {
            $ctl = $activities[$i]->tss * $lambda + ((1 - $lambda) * $ctl);
            // echo $ctl . ' '.$activities[$i]->tss.' ';
        }
        return $ctl;
		
	}

	public function updateXWeekSummary() {
		global $db;
		$xWeeksDate = strtotime('-'.Config::$XWeeks.' weeks');
		$activities = Activity::getActivitiesAfter($this->activities, $xWeeksDate);

		$sumMileage = 0;
		$sumElevation = 0;
		$numRaces = 0;
		$numLongRuns = 0;
		$numSpeedWork = 0;
		$longRunSumDist = 0;
		$speedworkSumVo2max = 0;
		$raceSumVo2max = 0;
		$sumVo2max = 0;
		$sumTrainingPace = 0;
		foreach ($activities as $ac) {
			$sumMileage+= $ac->distance;
			$sumElevation+= $ac->elevationGain;
			$sumVo2max += $ac->vo2Max;
			if($ac->activityType == 'race') {
				$numRaces++;
				$raceSumVo2max += $ac->vo2Max;
			} else if($ac->activityType == 'long run') {
				$numLongRuns++;
				$longRunSumDist += $ac->distance;
			} else if($ac->activityType == 'speedwork') {
				$numSpeedWork++;
				$speedworkSumVo2max += $ac->vo2Max;
			}
			if($ac->activityType != 'race') {
				$sumTrainingPace += $ac->averageSpeed;
			}

		}

		$weeklyMileage = $sumMileage / Config::$XWeeks;
		$weeklyElevation = $sumElevation / Config::$XWeeks;
		$longRunAvgDist = 0;
		$avgRaceVo2Max = 0;
		$avgSpeedworkVo2Max = 0;
		$avgVo2Max = 0;
		$avgTrainingPace = 0;
		$avgElevation = 0;
		if($numLongRuns > 0) {
			$longRunAvgDist = $longRunSumDist / $numLongRuns;
		}
		if($numRaces > 0) {
			$avgRaceVo2Max = $raceSumVo2max / $numRaces;
		}
		if($numSpeedWork > 0) {
			$avgSpeedworkVo2Max = $speedworkSumVo2max / $numSpeedWork;
		}
		if(count($activities) > 0) {
			$avgVo2Max = $sumVo2max / count($activities);
			$avgTrainingPace = $sumTrainingPace / (count($activities) - $numRaces);
			$avgElevation = $sumElevation / count($activities);
		}

		$summaryObject = array('weeklyMileage' => $weeklyMileage, 
								'weeklyElevation' => $weeklyElevation,
								'numActivities' => count($activities),
								'numRaces' => $numRaces,
								'numLongRuns' => $numLongRuns,
								'numSpeedWork' => $numSpeedWork,
								'longRunAvgDist' => $longRunAvgDist,
								'avgRaceVo2Max' => $avgRaceVo2Max,
								'avgSpeedworkVo2Max' => $avgSpeedworkVo2Max,
								'avgVo2Max' => $avgVo2Max,
								'avgTrainingPace' => $avgTrainingPace,
								'avgElevation' => $avgElevation);

		$this->xWeekSummary->update($summaryObject);
		$update = "UPDATE athlete SET serialized_x_week_summary = '".serialize($this->xWeekSummary)."' WHERE strava_id = ".$this->id;
		$db->query($update);
	}

	public function updateAthlete() {
		global $db;
		if(count($this->activities) > 0) {
			$this->calculateWeeklyMileage();
			$this->calculateAveragePaces();
			$this->calculateAverageElevation();
			$this->calculateAverageHilly();
			$this->atl = Athlete::getATL($this->id);
			$this->ctl = Athlete::getCTL($this->id);
		} else {
			$this->weeklyMileage = 0;
			$this->averageRacePace = 0;
			$this->averageTrainingPace = 0;
			$this->averageElevationGain = 0;
			$this->averagePercentageHilly = 0;
			$this->atl = 0;
			$this->ctl = 0;
		}
		$this->updateXWeekSummary();
		

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
		echo 'Acute Training Load: '.round($this->atl, 2).' <br>';
		echo 'Chronic Training Load: '.round($this->ctl, 2).' <br>';
		echo 'Training Stress Balance: '.round($this->ctl - $this->atl, 2).' <br>';
		$ftp = 0;
		if(count($this->activities) > 0) {
			$ftp = $this->activities[count($this->activities)-1]->getFTP();
		}
		echo 'Functional Threshold Pace: '.floor((1000/$ftp/60)). ':'.(1000/$ftp%60) .' min/km<br>';
		$this->xWeekSummary->printSummary();
	}

}