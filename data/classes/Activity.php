<?php 
// include_once 'Configuration.php';
// include_once 'vo2max.php';
function classes_autoload($class_name) {
    $file = 'classes/'.$class_name.'.php';
    if(file_exists($file)) {
      	include_once($file);
    }
}
spl_autoload_register('classes_autoload');
class Activity {

	public $id;

	public $name;

	public $rawStream;

	public $rawDataPoints;

	public $elapsedTime;

	public $distance;

	public $segments;

	public $elevationGain;

	public $elevationLoss;

	public $vo2Max;

	public $climbs;

	public $climbScore;

	public $percentageHilly;

	public $surface;

	public $type;

	public $temperature;

	public $splitType;





	public function __construct($activtityId, $stravaActivity, $rawStream) {
		global $fileWriter;
		$this->id = $activtityId;
		$this->name = $stravaActivity['name'];
		$this->elapsedTime = $stravaActivity['elapsed_time'];
		$this->distance = $stravaActivity['distance'];
		$this->rawStream = $rawStream;
		$dataPoints = $this->generateDataPoints($rawStream);
		$this->rawDataPoints = $this->cleanDataPoints($dataPoints, Config::$cleanMinDist);
		### write data in CSV 
	    $fileWriter->writeControlData($this->rawDataPoints, 'original');
	    ###
		
	}

	private function generateDataPoints($stream) {
		$latlongArray   = $stream[0]['data'];
	    $timeArray   = $stream[1]['data'];
	    $distanceArray  = $stream[2]['data'];
	    $stravaElevation = $stream[3]['data'];
	    ### get google elevation data
	    $googleClient = new GoogleElevationClient();
		foreach ($latlongArray as $coord) {
		    $googleClient->addCoordinate($coord[0], $coord[1]);
		}
		$googleElevation = $googleClient->fetchJSON();

	    $dataPoints = [];
	    for($i = 0; $i < count($distanceArray); $i++) {
	    	$param = array('lat' => $latlongArray[$i][0],
	    				   'long' => $latlongArray[$i][1],
	    				   'dist' => $distanceArray[$i],
	    				   'alt' => $googleElevation[$i]->elevation,
	    				   'stravaAlt' => $stravaElevation[$i],
	    				   'time' => $timeArray[$i]);
	    	$dataPoints[] = new DataPoint($param);
	    }
	    return $dataPoints;
	}

	private function cleanDataPoints($dataPoints, $distThreshold) {	
	    $response  = [];
	    $sum           = 0;
	    $lastDist = $dataPoints[0]->distance;

	    // push first one
	    $response[] = $dataPoints[0];

	    for ($i = 1; $i < count($dataPoints); $i++) {
	        $dist = $dataPoints[$i]->distance - $lastDist;
	        $sum += $dist;
	        // echo $sum . ' '.$dist . ', ';
	        if ($sum >= $distThreshold) {
	        	$response[] = $dataPoints[$i];
	            $sum = 0;
	        }

	        $lastDist = $dataPoints[$i]->distance;
	    }
	    // push last one
	    $response[] = $dataPoints[count($dataPoints) - 1];
	    echo 'remove measure points that are closer than '.$distThreshold.' m distance, count before: ' . count($dataPoints) . ' count after: ' . count($response) . ' <br>';
	    return $response;
	}

	public function findSegments() {
		global $fileWriter;
		$this->segments = SegmentFinder::findSegments($this->rawDataPoints);
		### write output string
	    $fileWriter->writeOutput($this->segments);
	    ###
	}

	public function findClimbs() {
		global $fileWriter;
		$this->climbs = ClimbFinder::findClimbs($this->segments);
		### write data in CSV 
		$fileWriter->writeControlData($this->climbs, 'climbs');
		###
	}

	public function calculateElevationGain() {
		$up = 0;
		$down = 0;
		foreach ($this->segments as $segment) {
			$relElev = $segment->elevation;
			if($relElev > 0) {
				$up += $relElev;
			} else if($relElev < 0) {
				$down += $relElev;
			}
		}
		$this->elevationGain = $up;
		$this->elevationLoss = $down;
	}

	function computePercentageHilly() {
		$hilly = 0;
	    for ($i = 0; $i < count($this->segments); $i++) {
	        $gradient = $this->segments[$i]->gradient;
	        if($gradient > Config::$hillySegmentThreshold || $gradient < -Config::$hillySegmentThreshold) {
	        	$hilly += $this->segments[$i]->length;
	        }

	    }
	    $this->percentageHilly = round($hilly / $this->distance, 2);
	}

	public function determineSurface() {
		$surface = 'No data';
		$percent = 0;
    	$surfacePoints = $this->cleanDataPoints($this->rawDataPoints, Config::$surfaceStepsDist);
    	$overpass = new OverpassApiClient();
    	$query = $overpass->buildQuery($surfacePoints);
    	$waySet = $overpass->fetchJson($query);
	    if($waySet != null) {
	    	$surfaces = [];
			foreach ($waySet as $way) {
				$surface = $way->tags->surface;
				if(!array_key_exists($surface, $surfaces)) {
					$surfaces[$surface] = 1;
				} else {
					$surfaces[$surface] += 1;
				}
			}
			$total = array_sum($surfaces);
			foreach($surfaces as $key => $value) {
				$surfaces[$key] = round($value * 100 / $total, 2);
			}
			arsort($surfaces);
			$surface = key($surfaces);
			$percent = reset($surfaces);
	        
	    } else {
	        echo 'Surface: Error<br>';
	    }
    
		$this->surface = array($surface, $percent);
	}

	function calculateClimbScore() {
		$fietsSum = 0;
		$percentageFlat = 1.0 - $this->percentageHilly;
		foreach ($this->climbs as $climb) {
			$fietsSum += $climb->fietsIndex;
		}
		$singleScores = $fietsSum / max(1.0, sqrt($this->distance / 20000));
		$compensateFlats = min(1.0, max(0.0, 1.0 - $percentageFlat * $percentageFlat));
		$score = min(10.0, max(0.0, 2.0 * log(0.5 + (1.0 + $singleScores) * $compensateFlats, 2.0)));
		$this->climbScore = round($score, 2);
	}


	// public function toString() {
	// }

}