<?php 
class Activity {

	public $id;

	public $name;

	public $rawStream;

	public $rawDataPoints;

	public $rawStravaActivity;

	public $elapsedTime;

	public $distance;

	public $averageSpeed;

	public $segments;

	public $elevationGain;

	public $elevationLoss;

	public $vo2Max;

	public $climbs;

	public $climbScore;

	public $percentageHilly;

	public $surface;

	public $activityType;

	public $temperature; //todo

	public $splitType; 





	public function __construct($activtityId, $stravaActivity, $rawStream) {
		global $fileWriter;
		$this->id = $activtityId;
		$this->name = $stravaActivity['name'];
		$this->elapsedTime = $stravaActivity['elapsed_time'];
		$this->distance = $stravaActivity['distance'];
		$this->averageSpeed = $stravaActivity['average_speed'];
		$this->rawStream = $rawStream;
		$this->rawStravaActivity = $stravaActivity;
		$dataPoints = $this->generateDataPoints($rawStream);
		$this->rawDataPoints = $this->cleanDataPoints($dataPoints, Config::$cleanMinDist);
		$this->determineSplitType();
   		$this->determineActivityType();
    
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
	    // echo 'remove measure points that are closer than '.$distThreshold.' m distance, count before: ' . count($dataPoints) . ' count after: ' . count($response) . ' <br>';
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

	public function calculateVo2max() {
		$this->vo2Max = Vo2Max::vo2maxWithElevation($this->distance, $this->elapsedTime, $this->elevationGain, $this->elevationLoss);
	}

	public function computePercentageHilly() {
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

	public function calculateClimbScore() {
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

	public function determineActivityType() {
		
		$averageTrainingPace = 270;
		if($this->rawStravaActivity['workout_type'] == 1) {
			$this->activityType = 'race';
		} else if($this->rawStravaActivity['workout_type'] == 2) {
			$this->activityType = 'long run';
		} else if($this->rawStravaActivity['workout_type'] == 3) {

			if($this->isActivityInterval()){
				$this->activityType = 'speedwork';
			} else {
				$this->activityType = 'base training';
			}

		} else if($this->rawStravaActivity['workout_type'] == 0) {
			if(($this->distance > 4900 || $this->distance < 5300 || $this->distance > 9900 || $this->distance < 10300 || $this->distance > 20000 || $this->distance < 21500 || $this->distance > 41000 || $this->distance < 43000) && 1000 / $this->averageSpeed < $averageTrainingPace - 20) {
				$this->activityType = 'race';
			} else if($this->distance > 15000 && 1000 / $this->averageSpeed > $averageTrainingPace - 20) {
				$this->activityType = 'long run';
			} else if($this->isActivityInterval()){
				$this->activityType = 'speedwork';
			} else {
				$this->activityType = 'base training';
			}
		}
	}

	public function isActivityInterval() {
		global $fileWriter;
		$averageTrainingSpeed = 270;
		$intervalProbabilties = [];
		$velocity = $this->rawStream[4]['data'];
		$distance = $this->rawStream[2]['data'];
		$sma = $this->simpleMovingAverage($velocity, 3);
		
		$tuple = [];
		for($i = 0; $i < count($sma); $i++) {
			$tuple[] = array($distance[$i], $sma[$i]);
		}
		$rdpSMA = RDP::RamerDouglasPeucker2d($tuple, 1);

		$maxSpeed = 1000 / max(array_column($rdpSMA, 1));
		$index = 0;
		$sum = 0;
		for($i = 0; $i < count($rdpSMA); $i++) {
			$mpers = $rdpSMA[$i][1];
			if($mpers > $this->averageSpeed) {
				$sum+= $mpers;
				$index++;
			}
		}
		$meanPositiveSpeed = 1000 / ($sum / $index);
		if($meanPositiveSpeed < $averageTrainingSpeed * Config::$intervalTrainingPercent) {
			$intervalLength = 0;
			$lastDist = 0;
			for($i = 0; $i < count($rdpSMA); $i++) {
				
				$secPerKm = 0;
				if($rdpSMA[$i][1] > 0) {
					$secPerKm = 1000 / $rdpSMA[$i][1];
				}

				if($secPerKm < $meanPositiveSpeed) {
					if($lastDist == 0) {
						$lastDist = $rdpSMA[$i][0];
					} else {
						$length = $rdpSMA[$i][0] - $lastDist;
						$intervalLength += $length;
					}
				} else if($intervalLength > 0) {
					if($intervalLength >= 1000) {
						$intervalProbabilties[] = 1;
					} else if($intervalLength >= 700) {
						$intervalProbabilties[] = 0.9;
					} else if($intervalLength >= 250) {
						$intervalProbabilties[] = 0.8;
					} else if($intervalLength >= 90) {
						$intervalProbabilties[] = 0.51;
					} else {
						$intervalProbabilties[] = 0.3;
					}
					// echo 'length '.$intervalLength;
					$intervalLength = 0;
					$lastDist = 0;
				} else {
					$lastDist = 0;
				}

			}
		} else {
			$intervalProbabilties[] = 0;
		}

		// echo ' max speed '.$maxSpeed;
		// echo ' mean positive '. $meanPositiveSpeed;
		// echo ' average '.(1000 / $this->averageSpeed);
		// print_r($intervalProbabilties);
		$prob = array_sum($intervalProbabilties) / count($intervalProbabilties);
		// echo 'Probabilty: '.$prob;
		
		### write data in CSV 
		$fileWriter->writeControlData(array($distance, $velocity, $rdpSMA), 'velocity');
		###

		return $prob > 0.5;
	}

    public function simpleMovingAverage($numbers, $n) {
        $m   = count($numbers);
        $SMA = [];
        // Counters
        $new       = $n; // New value comes into the sum
        $drop      = 0;  // Old value drops out
        $yesterday = 0;  // Yesterday's SMA
        // Base case: initial average
        $SMA[] = array_sum(array_slice($numbers, 0, $n)) / $n;
        // Calculating successive values: New value comes in; old value drops out
        while ($new < $m) {
            $SMA[] = $SMA[$yesterday] + ($numbers[$new] / $n) - ($numbers[$drop] / $n);
            $drop++;
            $yesterday++;
            $new++;
        }
        return $SMA;
    }

    public function determineSplitType() {


    	$halfDistance = round($this->distance / 2, 2);
    	$timeStream = $this->rawStream[1]['data'];
    	$distanceStream = $this->rawStream[2]['data'];
    	$index = 0;
    	for($i = 0; $i < count($distanceStream); $i++) {
    		$index++;
    		if($distanceStream[$i] >= $halfDistance) {
    			break;
    		}
    	}

    	$timeFirstHalf = $timeStream[$index];
    	$timeSecondHalf = $timeStream[count($timeStream) - 1] - $timeFirstHalf;
    	$threshold = Config::$evenSplitThreshold * round($this->distance / 1000) / 2;

    	if($timeFirstHalf - $threshold > $timeSecondHalf + $threshold) {
    		$this->splitType = 'negative';
    	} else if($timeFirstHalf + $threshold < $timeSecondHalf - $threshold) {
    		$this->splitType = 'positive';
    	} else {
    		$this->splitType = 'even';
    		$activitySpeedSec = 1000 / $this->averageSpeed;
			$splits = $this->rawStravaActivity['splits_metric'];
			for($i = 0; $i < count($splits); $i++) {
				$averageSpeed = 1000 / $splits[$i]['average_speed'];
				if($averageSpeed >= $activitySpeedSec + $secThreshold || $averageSpeed <= $activitySpeedSec - $secThreshold) {
					$this->splitType = 'mixed';
				} 
			}
		}
		

	}

    


	public function printActivity() {
		echo '<h3>Activity "'.$this->name.'" </h3>';
		echo 'Distance: '.$this->distance.' m<br>';
		echo 'Elapsed time: '.round(($this->elapsedTime / 60), 2).' min<br>';
		echo 'Average speed: '.floor((1000/$this->averageSpeed/60)). ':'.(1000/$this->averageSpeed%60) .' min/km<br>';
		echo 'Activity type: '.$this->activityType.'<br>';
		echo 'Split type: '.$this->splitType.'<br>';
		echo 'Elevation + : '.round($this->elevationGain, 2).' m, Elevation - : '.round($this->elevationLoss, 2).' m<br>';
		echo 'Percentage hilly: '.round($this->percentageHilly * 100, 2).' %<br>';
		echo 'VO2max: '.round($this->vo2Max, 2).'<br>';
		echo 'Surface: '.$this->surface[0] . ' '. $this->surface[1].' %<br>';
		echo '#Segments: '.count($this->segments).'<br>';
		echo '#Climbs: '.count($this->climbs).'<br>';
		echo 'Climb score: '.round($this->climbScore, 2).'<br>';
		// echo '<br>';
	    $i = 1;
	    foreach ($this->climbs as $climb) {
	        echo $i.'. Climb: VAM '.$climb->getVerticalSpeed().' m/h, gradient '.$climb->gradient.' %, length '.$climb->length.' m, Fiets index '.round($climb->fietsIndex, 2).' <br>';
	        $i++;
	    }
	    // echo '<br>';

	}

}