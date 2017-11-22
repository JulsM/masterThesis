<?php 

class Activity {

	public $id;

	public $athleteId;

	public $name;

	public $date;

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

	public $averageNGP;

	public $tss;

	public $preAtl;

	public $preCtl;

	public $xWeekSummary;

	public $writeFiles;





	public function __construct($data, $type, $rawStream=null, $summary = true, $writeFiles = false) {
		if($type == 'strava') {
			$this->writeFiles = $writeFiles;
			$this->id = $data['id'];
			$this->athleteId = $data['athlete']['id'];
			$this->date = $data['start_date'];
			$this->name = $data['name'];
			$this->elapsedTime = $data['elapsed_time'];
			$this->distance = $data['distance'];
			$this->averageSpeed = $data['average_speed'];
			// $this->averageSpeed = $data['distance'] / $data['elapsed_time'];
			$this->rawStream = $rawStream;
			$this->rawStravaActivity = $data;
			$dataPoints = $this->generateDataPoints($rawStream);
			$this->rawDataPoints = $this->cleanDataPoints($dataPoints, Config::$cleanMinDist);
			$this->determineSplitType();
	   		$this->determineActivityType();
	   		$this->findSegments();
	   		$this->calculateElevationGain();
	   		$this->calculateVo2max();
	   		$this->computePercentageHilly();
	   		$this->findClimbs();
	   		$this->calculateClimbScore();
	   		$this->calculateNGP();
	   		$this->calculateTSS();
	   		$this->preAtl = Athlete::getATL($this->athleteId, $this->date);
	   		$this->preCtl = Athlete::getCTL($this->athleteId, $this->date);
	   		$this->xWeekSummary = new XWeekSummary();
	   		$this->updateXWeekSummary();
	   		
	   	} else {
	   		$this->id = $data['strava_id'];
	   		$this->athleteId = $data['athlete_id'];
			$this->date = $data['activity_timestamp'];
			$this->name = $data['name'];
			$this->elapsedTime = $data['elapsed_time'];
			$this->distance = $data['distance'];
			$this->averageSpeed = $data['average_speed'];
			$this->rawStream = null;
			$this->rawStravaActivity = null;
			if($summary) {
				$this->rawDataPoints = null;
			} else {
				$this->rawDataPoints = unserialize($data['serialized_raw_data_points']);
			}
			if($summary) {
				$this->segments = null;
			} else {
				$this->segments = unserialize($data['serialized_segments']);
			}
			$this->elevationGain = $data['elevation_gain'];
			$this->elevationLoss = $data['elevation_loss'];
			$this->vo2Max = $data['vo2_max'];
			if($summary) {
				$this->climbs = null;
			} else {
				$this->climbs = unserialize($data['serialized_climbs']);
			}
			$this->climbScore = $data['climb_score'];
			$this->percentageHilly = $data['percentage_hilly'];
			$this->surface = $data['surface'];
			$this->activityType = $data['activity_type'];
			$this->splitType = $data['split_type'];
			$this->averageNGP = $data['average_ngp'];
			$this->tss = $data['training_stress_score'];
			$this->preAtl = $data['pre_activity_atl'];
			$this->preCtl = $data['pre_activity_ctl'];
			$this->xWeekSummary = unserialize($data['serialized_xweek_summary']);
	   		
	   	}
    
    	
	}

	private function generateDataPoints($stream) {
		$latlongArray   = $stream[0]['data'];
	    $timeArray   = $stream[1]['data'];
	    $distanceArray  = $stream[2]['data'];
	    $stravaElevation = $stream[3]['data'];
	    $velocityArray = $this->rawStream[4]['data'];
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
	    				   'time' => $timeArray[$i],
	    				   'velocity' => $velocityArray[$i]);
	    	$dataPoints[] = new DataPoint($param);
	    }
	    $dataPoints = DataPoint::calculateGrade($dataPoints);
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
		$this->segments = SegmentFinder::findSegments($this->rawDataPoints);
	}

	public function findClimbs() {
		$this->climbs = ClimbFinder::findClimbs($this->segments);
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
		$result = null;
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
	        $result = $surface.' '.$percent;
	    } else {
	        echo 'Surface: Error<br>';
	    }
    
		// $this->surface = array($surface, $percent);
		$this->surface = $result;
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
		global $db;
		$average = $db->query('SELECT average_training_pace FROM athlete WHERE strava_id ='.$this->athleteId);

        if (!empty($average)) {
        	if($average[0]['average_training_pace'] == 0) {
        		$averageTrainingPace = 300;
        		
        	} else {
        		$averageTrainingPace = round(1000/ $average[0]['average_training_pace']);
        	}
        }
		
		if($this->rawStravaActivity['workout_type'] == 1) {
			$this->activityType = 'race';
		} else if($this->rawStravaActivity['workout_type'] == 2) {
			$this->activityType = 'long run';
		} else if($this->rawStravaActivity['workout_type'] == 3) {

			if($this->isActivityInterval($averageTrainingPace)){
				$this->activityType = 'speedwork';
			} else {
				$this->activityType = 'base training';
			}

		} else if($this->rawStravaActivity['workout_type'] == 0) {
			$longRunDistance = $this->getLongRunDistance();
			if((($this->distance > 4900 && $this->distance < 5300) || ($this->distance > 9900 && $this->distance < 10300) || ($this->distance > 20000 && $this->distance < 21500) || ($this->distance > 41000 && $this->distance < 43000)) && 1000 / $this->averageSpeed < $averageTrainingPace / 1.3) {
				$this->activityType = 'race';
			} else if($this->distance >= $longRunDistance && 1000 / $this->averageSpeed > $averageTrainingPace / 1.3) {
				$this->activityType = 'long run';
			} else if($this->isActivityInterval($averageTrainingPace)){
				$this->activityType = 'speedwork';
			} else {
				$this->activityType = 'base training';
			}
		}
	}

	private function getLongRunDistance() {
		global $db;
		$longRunDistance = 17000;
		$result = $db->query('SELECT serialized_x_week_summary FROM athlete WHERE strava_id = '.$this->athleteId);
		if(!empty($result)) {
			$summary = unserialize($result[0]['serialized_x_week_summary']);

			if($summary->numActivities > 0) {
				$avgDist = $summary->weeklyMileage / ($summary->numActivities / $summary->numWeeks);
			} else {
				$avgDist = 0;
				$longRunDistance = 17000;
			}
			if($avgDist > 25000) {
				$longRunDistance = $avgDist * Config::$weeklyMileagePercentLow;
			} else if($avgDist > 0) {
				$longRunDistance = $avgDist * Config::$weeklyMileagePercentHigh;
			}
			
		}
		// echo $longRunDistance;
		return $longRunDistance;
	}

	private function isActivityInterval($averageTrainingSpeed) {
		global $fileWriter;
		$intervalProbabilties = [];
		if(1000/$this->averageSpeed < $averageTrainingSpeed / 1.3 && $this->distance > 2500) {
			$intervalProbabilties[] = 1;
		} else if($this->distance > 2500) {
			$velocity = $this->rawStream[4]['data'];
			$distance = $this->rawStream[2]['data'];
			$sma = $this->simpleMovingAverage($velocity, 3);
			
			$tuple = [];
			for($i = 0; $i < count($sma); $i++) {
				$tuple[] = array($distance[$i], $sma[$i]);
			}
			$rdpSMA = RDP::RamerDouglasPeucker2d($tuple, 0.8);

			// $maxSpeed = 1000 / max(array_column($rdpSMA, 1));
			$index = 0;
			$sum = 0;
			for($i = 0; $i < count($velocity); $i++) {
				$mpers = $velocity[$i];
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
						} else if($intervalLength >= 80) {
							$intervalProbabilties[] = 0.70;
						} else {
							$intervalProbabilties[] = 0.35;
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
			if($this->writeFiles) {
				### write data in CSV 
				$fileWriter->writeControlData(array($distance, $velocity, $rdpSMA), 'velocity');
				###
			}
		}

		// echo ' max speed '.$maxSpeed;
		// echo ' mean positive speed '. $meanPositiveSpeed;
		// echo ' average training pace '. $averageTrainingSpeed;
		// echo ' average activity speed '.(1000 / $this->averageSpeed);
		// print_r($intervalProbabilties);

		$prob = 0;
		if(count($intervalProbabilties) > 0) {
			$prob = array_sum($intervalProbabilties) / count($intervalProbabilties);
		}
		// echo 'Probabilty: '.$prob;
		
		

		return $prob > 0.5;
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
				if($averageSpeed >= $activitySpeedSec + Config::$evenSplitThreshold || $averageSpeed <= $activitySpeedSec - Config::$evenSplitThreshold) {
					$this->splitType = 'mixed';
				} 
			}
		}
		
	}


	public function calculateTSS() {
		// global $fileWriter;

		$FTP = $this->getFTP();

		$intensityFactor = $this->averageNGP / $FTP;

		$this->tss = ($this->elapsedTime * $intensityFactor * $intensityFactor) / 3600 * 100;
			

		// $list = [];
		// for($i = -0.3; $i <= 0.3; $i += 0.01) {
		// 	$list[] = array($i, $this->getEnergyCost($i));
		// }
		// $fileWriter->writeCsv($list, 'minetti');

	}

	public function getFTP() {
		global $db;

		$weeksAfter = date('Y-m-d H:i:s e',strtotime($this->date.' -'.Config::$FTPWeeks.' weeks'));
		$ftp = 0;

		// avg race pace 10k faster than 45 min
		$query = 'SELECT * FROM activity WHERE athlete_id =' . $this->athleteId.' AND activity_timestamp >= \''.$weeksAfter.'\' AND activity_type = \'race\' AND distance BETWEEN 9800 AND 10300 ORDER BY elapsed_time LIMIT 1';
        $result10k = $db->query($query);
        if(!empty($result10k)) {
        	$best10kPace = $result10k[0]['average_speed'];
        	if(!$best10kPace > 3.704) { // 3.704 m/s = 4:30 min/km
	        	$ftp = $best10kPace;
	        }
        }

        if($ftp == 0) { // race 10k and 21k
        	if(!empty($result10k)) {
	        	$query = 'SELECT * FROM activity WHERE athlete_id =' . $this->athleteId.' AND activity_timestamp >= \''.$weeksAfter.'\' AND activity_type = \'race\' AND distance BETWEEN 20800 AND 21300 ORDER BY elapsed_time LIMIT 1';
	        	$result = $db->query($query);
	        	if(!empty($result)) {
	        		$best21kPace = $result[0]['average_speed'];
	        		$pred1HourDist10k = pow((3600 / $result10k[0]['elapsed_time']), 50/53) * ($result10k[0]['distance']); // T2 = T1 * (D2/D1) ^ 1.06 , Riegels Formula
	        		$pred1HourDist21k = pow((3600 / $result[0]['elapsed_time']), 50/53) * ($result[0]['distance']); 
	        		$pred1hourPace = ($pred1HourDist10k + $pred1HourDist21k) / 2 / 3600;
	        		$ftp = $pred1hourPace;
	        	}
	        }
        }

        if($ftp == 0) { // race around 45 - 60 min
        	$query = 'SELECT * FROM activity WHERE athlete_id =' . $this->athleteId.' AND activity_timestamp >= \''.$weeksAfter.'\' AND activity_type = \'race\' AND distance > 10300 AND elapsed_time BETWEEN 2700 AND 3600 ORDER BY elapsed_time LIMIT 1';
        	$result = $db->query($query);
        	if(!empty($result)) {
        		$pred1HourDist = pow((3600 / $result[0]['elapsed_time']), 50/53) * ($result[0]['distance']); 
        		$pred1hourPace = $pred1HourDist / 3600;
        		$ftp = $pred1hourPace;
        	}

        }

		if($ftp == 0) { // training around 60 min best time
        	$query = 'SELECT * FROM activity WHERE athlete_id =' . $this->athleteId.' AND activity_timestamp >= \''.$weeksAfter.'\' AND activity_type != \'race\' AND distance > 7000  AND distance < 18000 ORDER BY average_speed desc LIMIT 1';
        	$result = $db->query($query);
        	if(!empty($result)) {
        		$movingTime = 1 / $result[0]['average_speed'] * $result[0]['distance'];
        		// echo $movingTime.' ';
        		$pred1HourDist = pow((3600 / $movingTime), 50/53) * ($result[0]['distance']);
        		$pred1hourPace = $pred1HourDist / 3600;
        		// echo $result[0]['elapsed_time'] .' '.$pred1HourDist.' '.$result[0]['average_speed'].' '.$result[0]['distance'];
        		$ftp = $pred1hourPace;
        	}

        }
        // echo ' '.(1000/$ftp);
        if($ftp == 0) {
        	$ftp = 330; // 5:30
        }
        return $ftp;
	}

	public function calculateNGP() {
		//smooth grade in DataPoints
		$gradeData = array_map(function($p) {return $p->grade;},$this->rawDataPoints);
		$smoothedGrade = $this->simpleMovingAverage($gradeData, 7);
		for($i = 0; $i < count($this->rawDataPoints); $i++) {
			$this->rawDataPoints[$i]->grade = $smoothedGrade[$i];
		}

		// get energy cost for grades
		$ngp = [];
		foreach ($this->rawDataPoints as $p) {
			$p->ngp = $p->velocity * $this->getEnergyCost(round($p->grade/100, 6));
			$ngp[] = $p->ngp;
		}

		// give fast velocities a weightening
		$lastSegTime = 0;
		$ngpSegments = [];
		$segSum = 0;
		$pointCounter = 0;
		foreach ($this->rawDataPoints as $p) {
			if($p->time - $lastSegTime < 30) {
				$segSum += $p->ngp;
				$pointCounter++;
			} else {
				$lastSegTime = $p->time;
				$ngpSegments[] = pow($segSum / $pointCounter, 4);
				$segSum = $p->ngp;
				$pointCounter = 1;
			}
		}
		if($pointCounter > 0) {
			$ngpSegments[] = pow($segSum / $pointCounter, 4) * 2;
		}
		$averageNGP = pow(array_sum($ngpSegments) / count($ngpSegments), 1/4);


		// echo 1000/ $averageNGP . ' ';

		// echo 1000/(array_sum($ngp)/count($ngp));

		// $list = [];
		// $index = 0;
		// foreach ($this->rawDataPoints as $point) {
		// 	$list[] = array($point->distance, $point->altitude, $point->grade, $smoothedGrade[$index], $point->velocity, $point->ngp, $point->velocity * $this->getEnergyCostAdjusted($point->grade/100));
		// 	$index++;
		// }
		// $fileWriter->writeCsv($list, 'tss');

		// $stravaGrade = $strava[1]['data'];
		// $stravaDistance = $strava[0]['data'];
		// $stravaVelocity = $strava[2]['data'];

		// $ngp = [];
		// for($i = 0; $i < count($stravaDistance); $i++) {
		// 	$ngp[] = $this->getNGP($stravaVelocity[$i], $stravaGrade[$i]);
		// }
		// echo 1000/(array_sum($ngp)/count($ngp));
		// $list = [];
		// for($i = 0; $i < count($stravaDistance); $i++) {
		// 	$list[] = array($stravaDistance[$i], $stravaGrade[$i], $ngp[$i], $stravaVelocity[$i]);
		// }
		// $fileWriter->writeCsv($list, 'stravaNGP');

		$this->averageNGP = $averageNGP;

	}


	
	private function getEnergyCost($g) {
        // return (155.4 * pow($g, 5)  - 30.4 * pow($g, 4) - 43.3 * pow($g, 3) + 46.3 * pow($g, 2) + (19.5 * $g) + 3.6) / 3.6; 
        // return (350 * pow($g, 5)  - 40 * pow($g, 4) - 36 * pow($g, 3) + 65 * pow($g, 2) + (11.8 * $g) + 3.6) / 3.6; 
        return -26.093883514404297 * pow($g, 5)  -42.793968200683594 * pow($g, 4) - 0.6524703502655029 * pow($g, 3) + 18.595693588256836 * pow($g, 2) + (2.929260015487671 * $g) + 1; 
    }

    

	private function simpleMovingAverage($numbers, $n) {
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

        //fill with border values
        $i = 0;
        while($i < floor($n / 2)) {
        	$add[] = $numbers[$i];
        	$i++;
        }
        $SMA = array_merge($add, $SMA);
        //fill with end values;
        $i = floor($n / 2);
        while($i > 0) {
        	$SMA[] = $numbers[$m - $i];
        	$i--;
        }
        // echo count($SMA). count($numbers);
        return $SMA;
    }

    public function updateXWeekSummary() {
    	global $db;
    	$from = date('Y-m-d H:i:s e',strtotime($this->date.' -'.Config::$XWeeks.' weeks'));
    	$result = $db->getActivities($this->athleteId, $from, $this->date, true);
    	$activities = [];
    	if(!empty($result)) {
	        foreach ($result as $ac) {
	        	$activity = new Activity($ac, 'db');
	        	$activities[] = $activity;
	        }
	    }
    	$summaryObject = Athlete::getXWeekSummary($activities);
    	$this->xWeekSummary->update($summaryObject);
    }









	public static function downloadNewActivities($athleteId, $token) {
		global $db, $app;
		// echo 'load activities';
		$dateInPast = date('Y-m-d H:i:s e',strtotime(Config::$maxActivityAgo.' years'));
        // echo $dateInPast.' ';
        $query = 'SELECT activity_timestamp FROM activity WHERE athlete_id =' . $athleteId.' AND activity_timestamp > \''.$dateInPast.'\' ORDER BY "activity_timestamp" desc LIMIT 1';
        $newestActivityDate = $db->query($query)[0]['activity_timestamp'];
        $stravaDatePast = strtotime(Config::$maxActivityAgo.' years');
        if(!empty($newestActivityDate)) {
            $stravaDatePast = strtotime($newestActivityDate);
        }
        echo 'activities from this date on '.date('Y-m-d H:i:s e', $stravaDatePast);
        $app->createStravaApi($token);
        $api = $app->getApi();
        $newStravaActivities = $api->getActivties($stravaDatePast); 
        $counter = 0;
        foreach ($newStravaActivities as $ac) {
        	$rawStream = $api->getStream($ac['id'], "distance,altitude,latlng,time,velocity_smooth");
        	$activity = new Activity($ac, 'strava', $rawStream);
        	unset($rawStream);
        	$counter++;
        	// $activity->printActivity();
        	$db->saveActivity($activity, $athleteId);
        }

        return $counter;
        
	}

	public static function loadActivitiesDb($athleteId) {
		global $db;
		// echo 'load activities db';
		$dateInPast = date('Y-m-d H:i:s e',strtotime('-10 years'));
        $result = $db->getActivities($athleteId, $dateInPast, null, true);

        $returnObjects = [];
        if(count($result) > 0) {
	        foreach ($result as $ac) {
	        	$activity = new Activity($ac, 'db');
	        	$returnObjects[] = $activity;
	        }
	    }

        return $returnObjects;
        
	}


    public static function getActivitiesAfter($activities, $timestamp) {
    	// echo date('d.m.y H:i:s',$timestamp);
    	$return = [];
    	for($i = 0; $i < count($activities); $i++) {
    		if(strtotime($activities[$i]->date) >= $timestamp) {
    			$return[] = $activities[$i];
    		}
    		
    	}
    	return $return;
    }


	public function printActivity() {
		echo '<h3>Activity "'.$this->name.'" </h3>';
		echo 'Date: '.date('Y-m-d H:i:s e',strtotime($this->date)).'<br>';
		echo 'Distance: '.round($this->distance/1000, 2).' km<br>';
		echo 'Elapsed time: '.round(($this->elapsedTime / 60), 2).' min<br>';
		echo 'Average pace: '.floor((1000/$this->averageSpeed/60)). ':'.(1000/$this->averageSpeed%60) .' min/km<br>';
		if($this->averageNGP > 0) {
			$avgNgp = floor((1000/$this->averageNGP/60)). ':'.(1000/$this->averageNGP%60);
		} else {
			$avgNgp = '00:00';
		}
		echo 'Average normalized graded pace: '.$avgNgp.' min/km<br>';
		echo 'Activity type: '.$this->activityType.'<br>';
		echo 'Split type: '.$this->splitType.'<br>';
		echo 'Elevation + : '.round($this->elevationGain, 2).' m, Elevation - : '.round($this->elevationLoss, 2).' m<br>';
		echo 'Percentage hilly: '.round($this->percentageHilly * 100, 2).' %<br>';
		echo 'VO2max: '.round($this->vo2Max, 2).'<br>';
		echo 'Training Stress Score: '.round($this->tss, 2).'<br>';
		echo 'Pre-activity ATL: '.round($this->preAtl, 2).'<br>';
		echo 'Pre-activity CTL: '.round($this->preCtl, 2).'<br>';
		echo 'Surface: '.$this->surface.' %<br>';
		echo '#Segments: '.count($this->segments).'<br>';
		echo '#Climbs: '.count($this->climbs).'<br>';
		echo 'Climb score: '.round($this->climbScore, 2).'<br>';
		// echo '<br>';
		if(count($this->climbs) > 0) {
		    $i = 1;
		    foreach ($this->climbs as $climb) {
		        echo $i.'. Climb: VAM '.$climb->getVerticalSpeed().' m/h, gradient '.$climb->gradient.' %, length '.$climb->length.' m, Fiets index '.round($climb->fietsIndex, 2).' <br>';
		        $i++;
		    }
		}
	    echo '<br>';
	    $this->xWeekSummary->printSummary();

	}

}