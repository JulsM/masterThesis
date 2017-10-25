<?php 
class XWeekSummary {

	public $numWeeks;

	public $weeklyMileage; 

	public $weeklyElevationGain;

	public $numActivities;

	public $numRaces;

	public $numLongRuns;

	public $longRunAverageDist;

	public $numSpeedwork;

	public $averageSpeedworkVo2Max;

	public $averageRaceVo2Max;

	public $averageVo2Max;

	public $averageTrainingPace; 

	public $averageElevationGain;

	

	public function __construct() {
		$this->numWeeks = Config::$XWeeks;
		$this->weeklyMileage = $this->weeklyElevationGain = $this->numActivities = $this->numRaces = $this->numLongRuns = $this->longRunAverageDist = $this->numSpeedwork = $this->averageSpeedworkVo2Max = $this->averageRaceVo2Max = $this->averageVo2Max = $this->averageTrainingPace = $this->averageElevationGain = 0;
		
	}

	public function update($data) {
		$this->numWeeks = Config::$XWeeks;
		$this->weeklyMileage = $data['weeklyMileage'];
		$this->weeklyElevationGain = $data['weeklyElevation'];
		$this->numActivities = $data['numActivities'];
		$this->numRaces = $data['numRaces'];
		$this->numLongRuns = $data['numLongRuns'];
		$this->longRunAverageDist = $data['longRunAvgDist'];
		$this->numSpeedwork = $data['numSpeedWork'];
		$this->averageSpeedworkVo2Max = $data['avgSpeedworkVo2Max'];
		$this->averageRaceVo2Max = $data['avgRaceVo2Max'];
		$this->averageVo2Max = $data['avgVo2Max'];
		$this->averageTrainingPace = $data['avgTrainingPace'];
		$this->averageElevationGain = $data['avgElevation'];;
	}





	public function printSummary() {
		echo '<h3>'.$this->numWeeks.' weeks summary:</h3>';
		echo 'Weekly mileage: '.round($this->weeklyMileage/1000, 2).' km<br>';
		echo 'Weekly elevation gain: '.round($this->weeklyElevationGain, 2).' m<br>';
		echo 'Average elevation gain: '.round($this->averageElevationGain, 2).' m<br>';
		echo '# activities: '.$this->numActivities.'<br>';
		echo '# races: '.$this->numRaces.'<br>';
		echo '# speedwork: '.$this->numSpeedwork.'<br>';
		echo '# long runs: '.$this->numLongRuns.'<br>';
		$avgPace = '0:00';
		if($this->averageTrainingPace > 0) {
			$avgPace = floor((1000/$this->averageTrainingPace/60)). ':'.(1000/$this->averageTrainingPace%60);
		}
		echo 'Average training pace: '.$avgPace.' min/km<br>';
		echo 'Long run average distance: '.round($this->longRunAverageDist/1000, 2).' km<br>';
		echo 'Average Vo2max: '.round($this->averageVo2Max, 2).'<br>';
		echo 'Average speedwork Vo2max: '.round($this->averageSpeedworkVo2Max, 2).'<br>';
		echo 'Average race Vo2max: '.round($this->averageRaceVo2Max,2).'<br>';

	}

}