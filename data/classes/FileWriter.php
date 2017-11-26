<?php
require_once "lib/php-kmeans-master/src/KMeans/Space.php";
require_once "lib/php-kmeans-master/src/KMeans/Point.php";
require_once "lib/php-kmeans-master/src/KMeans/Cluster.php";

class FileWriter {
	
	private $path;

	private $lock;

	public function __construct($athleteName) {
		$this->path = 'output/'.$athleteName.'/';
		$this->lock = false;
	
		// chmod("output/".$athleteName, 0777);
        if(!is_dir($this->path)) {
            mkdir($this->path, 0777);
        }

    }
		

	public function writeControlData($data, $type) {
		if(!$this->lock) {
			if($type == 'original') {
				$list = [];
				array_push($list, array('strava', 'google', 'distance'));

				foreach ($data as $point) {
				    $list[] = array($point->stravaAlt, $point->altitude, $point->distance);
				}

				$this->writeCsv($list, 'originalData');
			} else if($type == 'rdp') {
				echo 'Apply RDP: '.count($data).'<br>';
				$list = [];
				foreach ($data as $point) {
				    $list[] = array($point->distance, $point->altitude);
				}

				$this->writeCsv($list, 'rdp');
			} else if($type == 'segments') {
				echo 'Compute segments: '.count($data).'<br>';
				$list = [];
				foreach ($data as $segment) {
				    $list[] = array($segment->start->distance, $segment->start->altitude);
				}
				$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

				$this->writeCsv($list, 'segments');
			} else if($type == 'filtered') {
				echo 'Filter segments: '.count($data).'<br>'; 
				$list = [];
				foreach ($data as $segment) {
				    $list[] = array($segment->start->distance, $segment->start->altitude);
				}
				$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

				$this->writeCsv($list, 'filteredSegments');
			} else if($type == 'recompute') {
				echo 'Recompute segments: '.count($data).'<br>';
				$list = [];
				foreach ($data as $segment) {
				    $list[] = array($segment->start->distance, $segment->start->altitude);
				}
				$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

				$this->writeCsv($list, 'recomputedSegments');
			} else if($type == 'climbs') {
				$list = [];
				foreach ($data as $climb) {
					$a = [];
					foreach ($climb->segments as $segment) {
				    	$a[] = $segment->start->distance;
				    	$a[] = $segment->start->altitude;
				    }
				    $a[] = $climb->end->distance;
				    $a[] = $climb->end->altitude;
				    $list[] = $a;
				}
				

				$this->writeCsv($list, 'climbs');
			} else if($type == 'velocity') {
				$velocity = $data[1];
				$distance = $data[0];
				$rdpSma = $data[2];
				$list = [];
				for($i = 0; $i < count($velocity); $i++) {
				    $list[] = array($distance[$i], $velocity[$i]);
				}
				$this->writeCsv($list, 'velocity');
				$this->writeCsv($rdpSma, 'velocitySMARDP');
			}

		}
	}

	public function writeSegmentsAsGPX($segments)
	{
	    $fp     = fopen($this->path . 'segmentsGPX.gpx', 'w+');
	    $header = '<?xml version="1.0" encoding="UTF-8"?>
	    <gpx creator="Julian" version="1.0" xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" >';
	    fwrite($fp, $header);
	    foreach ($segments as $segment) {
	        $string = '<wpt lat="' . $segment->start->latitude . '" lon="' . $segment->start->longitude . '">
	        <name>' . $segment->start->altitude .'</name>
	        </wpt>
	        ';
	        fwrite($fp, $string);
	    }
	    $end = '</gpx>';
	    fwrite($fp, $end);
	    fclose($fp);
	}

	public function writeCsv($list, $name)
	{	
		if(!$this->lock) {
			$path = $this->path . $name. '.csv';
		    $fp = fopen($path, 'w+');
		    foreach ($list as $line) {
		        fputcsv($fp, $line);
		    }
		    fclose($fp);
		    chmod($path, 0777);
		}
	}



	public function writeOutput($segments)
	{	
		if(!$this->lock) {
		    $str = '';
		    // $prevSegm = $segments[0];
		    for($i = 0; $i < count($segments); $i++) {
		        $str .= $segments[$i]->start->distance . ',' . $segments[$i]->length . ',' . $segments[$i]->gradient . '|';
		        // $prevSegm = $segments[$i];
		    }
		    $str = substr($str, 0, -1);
		    echo 'Output string: <br>' . $str . '<br>';
		    $fp = fopen( $this->path . 'outputString.xml', 'w+');
		    fwrite($fp, $str);
		    fclose($fp);

		}

	}

	public function writeRaceFeatures($activities, $athlete) {	

		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'finishTime', 'avgTrainPace');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			if($ac->activityType == 'race' ) {
		    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, 1, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace);
		    } 
		    
		}
		$this->writeCsv($list, 'raceFeatures');

	}

	public function writeAllActivitiesFeatures($activities, $athlete) {	

	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'finishTime','avgTrainPace');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			$isRace = -1;
			if($ac->activityType == 'race') {
				$isRace = 1;
			}
	    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace);
	    
		    
		}
		$this->writeCsv($list, 'activitiesFeatures');

	}

	public function writeActivitySetFeatures($activities) {	
		global $db;
		$activities = $this->basicActivityFiltering($activities);
		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'finishTime', 'avgTrainPace');
	    $athleteId = 0;
		for($i = 0; $i < count($activities); $i++) {

			$ac = $activities[$i];
			if($athleteId != $ac->athleteId) {
				$avgSpeed = $db->query('SELECT average_training_pace FROM athlete WHERE strava_id =' . $ac->athleteId)[0]['average_training_pace'];
				$athleteId = $ac->athleteId;
			}
			// if($ac->activityType == 'speedwork') {
			// 	continue;
			// }
			
			
			$isRace = -1;
			if($ac->activityType == 'race') {
				$isRace = 1;
			}
	    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $avgSpeed);
	    
		    
		}
		$this->writeCsv($list, 'activitySetFeatures');

	}

	public function writeTrainFeatures($activities, $athlete) {	
		$filterdActivities = $this->basicActivityFiltering($activities);
		$clusters = $this->clusterActivities($filterdActivities);
		$fastClusters = $clusters['fast_clusters'];
		// $fastClusters = $this->kmeansActivities($activities);
		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace');
		for($i = 0; $i < count($fastClusters); $i++) {
			$c = $fastClusters[$i];
			// $speeds = array_map(function($a) {return $a->averageNGP;}, $c);
			// $speedMax = max($speeds);
			// $speedMin = min($speeds);
			// echo $speedMax.' '.$speedMin;
			for($j = 0; $j < count($c); $j++) {
				$ac = $c[$j];
				$isRace = -1;
				if($ac->activityType == 'race') {
					$isRace = 1;
				}
				
				// $percentRacePace = ($ac->averageNGP - $speedMin) / ($speedMax - $speedMin);
				$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace);

			}
			
					    
		}
		$this->writeCsv($list, 'trainFeatures');

	}

	public function writeTsbModel($activities) {	
		$acType = array('base training' => 10,
						'speedwork' => 15,
						'long run' => 20,
						'race' => 30);
		
	    $list = [];
	    $list[] = array('date', 'tss', 'atl', 'ctl', 'vo2max', 'acType');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
	    	$list[] = array(date('Y-m-d H:i:s', strtotime($ac->date)), $ac->tss, $ac->preAtl, $ac->preCtl, $ac->vo2Max, $acType[$ac->activityType]);
		    
		}
		$this->writeCsv($list, 'tsbModel');

	}

	public function writekmeans($activities) {	
		
		$taggedActivities = $this->clusterActivities($activities);
		$fastClusters = $taggedActivities['fast_clusters'];
		// $races = $taggedActivities['races'];
		$fastCluster = [];
		foreach ($fastClusters as $fc) {
			$fastCluster = array_merge($fastCluster, $fc);
		}
		// $fastCluster = array_merge($fastCluster, $races);
		$clusters = array_slice($taggedActivities, 0, -1);
		$taggedActivities = $clusters;

		$taggedActivities[] = $fastCluster;
		$taggedActivities = array_values($taggedActivities);
		// print_r(array_values($taggedActivities));
		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'ngp', 'cluster');
	    for($c = 0; $c < count($taggedActivities); $c++) {
			for($i = 0; $i < count($taggedActivities[$c]); $i++) {
				$ac = $taggedActivities[$c][$i];
		    	$list[] = array($ac->distance, $ac->elevationGain, $ac->averageNGP, $c);
			    
			}
		}
		$this->writeCsv($list, 'kmeans');

	}

	public function basicActivityFiltering($activities) {
		$filteredActivities = [];

		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			if($ac->distance < 2000 || $ac->distance > 50000) {
				continue;
			}
			if($ac->averageNGP < 1.6 || $ac->averageNGP > 7) {
				continue;
			}
			if($ac->averageSpeed < 1.6 || $ac->averageSpeed > 7) {
				continue;
			}
			if($ac->xWeekSummary->averageTrainingPace == 0) {
				continue;
			}
			if($ac->xWeekSummary->averageVo2Max < 10) {
				continue;
			}

			$filteredActivities[] = $ac;
		}


		return $filteredActivities;
	}

	public function clusterActivities($activities) {
		// $noRaces = [];
		// $races = [];
		// foreach ($activities as $ac) {
		// 	if($ac->activityType == 'race') {
		// 		$races[] = $ac;
		// 	} else {
		// 		$noRaces[] = $ac;
		// 	}
		// }
		$filterdActivities = $this->basicActivityFiltering($activities);
		$clusters = $this->kmeansActivities($filterdActivities);
		$taggedActivities = [];
		$fastClusters = [];
		$i = 0;
		foreach ($clusters as $c) {
			$speeds = array_map(function($a) {return $a->averageNGP;}, $c);
			$speedMean = array_sum($speeds) / count($speeds);
			$speedMax = max($speeds);
			// $speedThreshold = ($speedMean + $speedMax) / 2.05;
			$speedThreshold = $speedMean;
			$slowList = [];
			$fastList = [];
			foreach ($c as $ac) {
				if($ac->averageNGP >= $speedThreshold) {
					$fastList[] = $ac;
				} else {
					$slowList[] = $ac;
				}
			}
			$fastClusters[] = $fastList;
			$taggedActivities['cluster_'.$i] = $slowList;
			// echo $speedThreshold.' ';
			$i++;
		}
		$taggedActivities['fast_clusters'] = $fastClusters;
		// $taggedActivities['races'] = $races;
		return $taggedActivities;
	}

	public function kmeansActivities($activities, $nClusters=5) {

		$clusteredActivities = [];

		$vals = array_map(function($a) {return array($a->distance, $a->elevationGain) ;}, $activities);
		
		$dist = array_column($vals, 0);
		$secDim = array_column($vals, 1);
		$distMean = array_sum($dist) / count($dist);
		$distStd = $this->stats_standard_deviation($dist);
		$secDimMean = array_sum($dist) / count($dist);
		$secDimStd = $this->stats_standard_deviation($dist);
		
		// create a 2-dimentions space
		$space = new KMeans\Space(2);
		// add points to space
		foreach ($activities as $ac){
			$standDist = ($ac->distance - $distMean) / $distStd;
			$standSecDim= ($ac->elevationGain - $secDimMean) / $secDimStd;
			
			// echo $standDist.' '.$standSecDim.'; ';
		    $space->addPoint(array($standDist, $standSecDim), $ac);
		}

		// cluster these 50 points in 3 clusters
		$clusters = $space->solve($nClusters, KMeans\Space::SEED_DASV);
		// display the cluster centers and attached points
		foreach ($clusters as $i => $cluster) {
			$c = [];
		    printf("Cluster %s [%f,%f]: %d points<br>", $i, $cluster[0] * $distStd + $distMean, $cluster[1] * $secDimStd + $secDimMean, count($cluster));
		    foreach ($cluster as $point){
		    	$c[] = $space[$point];
		    }
   			$clusteredActivities[] = $c;	
		}
		return $clusteredActivities;
	}

	function stats_standard_deviation(array $a, $sample = false) {
        $n = count($a);
        if ($n === 0) {
            trigger_error("The array has zero elements", E_USER_WARNING);
            return false;
        }
        if ($sample && $n === 1) {
            trigger_error("The array has only 1 element", E_USER_WARNING);
            return false;
        }
        $mean = array_sum($a) / $n;
        $carry = 0.0;
        foreach ($a as $val) {
            $d = ((double) $val) - $mean;
            $carry += $d * $d;
        };
        if ($sample) {
           --$n;
        }
        return sqrt($carry / $n);
    }

	public function lock() {
		$this->lock = true;
	}


}