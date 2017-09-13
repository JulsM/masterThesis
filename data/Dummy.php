<?php

include_once 'activityProcessing.php';
include_once 'Configuration.php';

$dummy = new Dummy();
$dummy->elevationGain();
$dummy->computeProfile();
$dummy->climbs();

class Dummy {

	private $segments;
	private $name = 'Julian Maurer';
	private $computedSegments = [];
	private $totalDistance;

	public function __construct() {
		$this->segments = 
		array(
			array(0, 100),
			array(500, 150),
			array(800, 151),
			array(1250, 148),
			array(1489, 120),
			array(1666, 135),
			array(1900, 147),
			array(2455, 180),
			array(3021, 240),
			array(3800, 100),
			array(4030, 80),
			array(4201, 75),
			array(4500, 100)
			);
		echo 'Test Dummy<br>';
		$this->totalDistance = $this->segments[count($this->segments)-1][0];
	}

	public function computeProfile() {
		$originalList = array();
		$i = 0;
		foreach ($this->segments as $seg) {
		    array_push($originalList, array($seg[1], $seg[0]));
		    $i++;
		}
		writeCsv($originalList, $this->name.'/originalData');
		$segments = computeSegments($this->segments, Config::$lowGradientThreshold, Config::$highGradientThreshold);
    	writeCsv($segments, $this->name.'/segments');
    	$filteredSegments = filterSegments($segments);
   	 	writeCsv($filteredSegments, $this->name.'/filteredSegments');
   	 	$recompSegments = computeSegments($filteredSegments, Config::$lowGradientThresholdRecomp, Config::$highGradientThresholdRecomp);
    	writeCsv($recompSegments, $this->name.'/recomputedSegments');
    	$this->computedSegments = $recompSegments;
	}

	public function climbs() {
		$climbs = computeClimbs($this->computedSegments);
	    writeClimbs($climbs, $this->name.'/climbs');
	    echo 'number of climbs: '.count($climbs).'<br>';
	    $percentageHilly = getPercentageHilly($this->computedSegments, $this->totalDistance);
	    echo 'percentage hilly: '.$percentageHilly.'<br>';

	    // climb score
	    $score = calculateClimbScore($climbs, $this->totalDistance, 1.0 - $percentageHilly);
	    echo 'climb score: '.$score.'<br>';
	}

	public function elevationGain() {
		$altitude = array_column($this->segments, 1);
		$gain = calculateElevationGain($altitude);
		echo 'elevation gain: '.$gain[0].'<br>';
		
	}

}