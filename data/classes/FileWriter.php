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
				    $list[] = array($segment->start->distance, $segment->start->altitude, $segment->length);
				}
				$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude, 0);

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

	public function writeAthletes($athletes) {	

		### Study ###
		
	    $list = [];
	    $list[] = array('name', 'gender', 'weeklyMileage', 'avgTrainPace', 'avgElevGain', 'numRaces', '5k', '10k', '21k', '42k');
	    $races = [];
	    $races[] = array('name', 'distance', 'elevGain', 'elapsedTime', 'date', 'hilly');
		for($i = 0; $i < count($athletes); $i++) {
			$a = $athletes[$i];
			$gender = ($a->gender == 'M') ? 'male' : 'female';
			$activities = Activity::loadActivitiesDb($a->id);
			$numRaces = 0;
			$five = 0;
			$ten = 0;
			$half = 0;
			$full = 0;

			foreach ($activities as $ac) {
				if(date('Y-m-d',strtotime($ac->date)) == '2017-12-31' && $ac->distance > 5000){
					continue;
				}
				if($ac->activityType == 'race') {
					$races[] = array($a->name, round($ac->distance/1000, 2), round($ac->elevationGain), round($ac->elapsedTime/60), date('d-m-Y', strtotime($ac->date)), $ac->percentageHilly * 100);
					$numRaces++;
					if($ac->distance > 4800 && $ac->distance < 5300) {
						$five++;
					} else if($ac->distance > 9700 && $ac->distance < 10500) {
						$ten++;
					} elseif($ac->distance > 20800 && $ac->distance < 23000) {
						$half++;
					} elseif($ac->distance > 41900 && $ac->distance < 45000) {
						$full++;
					} 
				}
			}
			
	    	$list[] = array($a->name, $gender, $a->weeklyMileage, $a->averageTrainingPace, $a->averageElevationGain, $numRaces, $five, $ten, $half, $full);
	    
		    
		}
		$this->writeCsv($list, 'athletesStudy');
		$this->writeCsv($races, 'racesStudy');

		
	    
			

	}

	public function writeStudyFiles($activities, $athlete) {
		global $db;
		$silvesterlauf = null;
		foreach ($activities as $key => $ac) {
			if(date('Y-m-d',strtotime($ac->date)) == '2017-12-31' && $ac->distance > 5000){
				$silvesterlauf = $ac;
				array_splice($activities, $key, 1);
				// unset($activities[$key]);
			}
		}
		$clusters = $this->clusterActivities($activities);
		$fastClusters = $clusters['fast_clusters'];
		$fastActivities = [];
		for($i = 0; $i < count($fastClusters); $i++) {
			$c = $fastClusters[$i];
			for($j = 0; $j < count($c); $j++) {
				$fastActivities[] = $c[$j];
			}	    
		}
		$fastActivities = $this->sortActivitiesByDate($fastActivities);
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
	    $gender = ($athlete->gender == 'M') ? 1 : -1;
		for($i = 0; $i < count($fastActivities); $i++) {
			$ac = $fastActivities[$i];
			$isRace = ($ac->activityType == 'race') ? 1 : -1;
			
			
			
			$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace, $gender);
		}
		$this->writeCsv($list, 'studyFeatures');
		$predlist[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
		$vo2max = $athlete->xWeekSummary->averageVo2Max;
		$predlist[] = array($silvesterlauf->distance,$silvesterlauf->elevationGain,$silvesterlauf->percentageHilly,$silvesterlauf->climbScore,$silvesterlauf->preAtl,$silvesterlauf->preCtl,1,$vo2max,$silvesterlauf->elapsedTime/ 60,$athlete->averageTrainingPace,$gender);
		$this->writeCsv($predlist, 'predictions');

		// segments
		$seglist = [];
	    $seglist[] = array('id', 'activityDistance', 'activityTime', 'activityElevation', 'isRace', 'segStartDist', 'segEndDist', 'segLength', 'segGrade', 'elevGainDone', 'segTime');
		for($i = 0; $i < count($fastActivities); $i++) {
			$ac = $fastActivities[$i];
			$isRace = ($ac->activityType == 'race') ? 1 : -1;
			$result = $db->query('SELECT serialized_segments FROM activity WHERE strava_id =' . $ac->id);
			$segments = unserialize($result[0]['serialized_segments']);
			$elevGainDone = 0;
			for($m = 0; $m < count($segments); $m++) {
				$seg = $segments[$m];
				// $seg->printSegment();
				$vel = $seg->length / ($seg->end->time - $seg->start->time);
				if($ac->activityType != 'race' && $vel < 1.38) { // 12:00 min/km
					continue;
				}
				if($ac->activityType != 'race' && $seg->length < 50) {
					continue;
				}
				
				$segLength = $seg->end->distance - $seg->start->distance;
				$segTime = $seg->end->time - $seg->start->time;
				$seglist[] = array($i, $ac->distance, $ac->elapsedTime, $ac->elevationGain, $isRace, $seg->start->distance, $seg->end->distance, $segLength, $seg->gradient, $elevGainDone, $segTime);
				$elevGainDone = ($seg->elevation > 0) ? $elevGainDone + $seg->elevation : $elevGainDone;
			}
					    
		}
		$this->writeCsv($seglist, 'segmentFeatures');

		$segpredlist = [];
	    $segpredlist[] = array('id', 'activityDistance', 'activityTime', 'activityElevation', 'isRace', 'segStartDist', 'segEndDist', 'segLength', 'segGrade', 'elevGainDone', 'segTime');
		$result = $db->query('SELECT serialized_segments FROM activity WHERE strava_id =' . $silvesterlauf->id);
		$segments = unserialize($result[0]['serialized_segments']);
		$elevGainDone = 0;
		for($m = 0; $m < count($segments); $m++) {
			$seg = $segments[$m];
			// $seg->printSegment();
						
			$segLength = $seg->end->distance - $seg->start->distance;
			$segTime = $seg->end->time - $seg->start->time;
			$segpredlist[] = array(-1, $silvesterlauf->distance, $silvesterlauf->elapsedTime, $silvesterlauf->elevationGain, 1, $seg->start->distance, $seg->end->distance, $segLength, $seg->gradient, $elevGainDone, $segTime);
			$elevGainDone = ($seg->elevation > 0) ? $elevGainDone + $seg->elevation : $elevGainDone;
		}
		// print_r($segpredlist);
		$this->writeCsv($segpredlist, 'segmentPredictions');		    
		

	}

	public function writeRaceFeatures($activities, $athlete) {	

		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			$gender = ($athlete->gender == 'M') ? 1 : -1;
			if($ac->activityType == 'race' ) {
		    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, 1, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace, $gender);
		    } 
		    
		}
		$this->writeCsv($list, 'raceFeatures');

	}

	public function writeTrainFeatures($activities, $athlete) {	
		// $filterdActivities = $this->basicActivityFiltering($activities);
		$clusters = $this->clusterActivities($activities);
		$fastClusters = $clusters['fast_clusters'];
		$fastActivities = [];
		for($i = 0; $i < count($fastClusters); $i++) {
			$c = $fastClusters[$i];
			for($j = 0; $j < count($c); $j++) {
				$fastActivities[] = $c[$j];
			}	    
		}
		$fastActivities = $this->sortActivitiesByDate($fastActivities);
	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
		for($i = 0; $i < count($fastActivities); $i++) {
			$ac = $fastActivities[$i];
			$isRace = ($ac->activityType == 'race') ? 1 : -1;
			$gender = ($athlete->gender == 'M') ? 1 : -1;
			
			
			$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace, $gender);
		}
		$this->writeCsv($list, 'trainFeatures');

	}

	public function writeAllActivitiesFeatures($activities, $athlete) {	

	    $list = [];
	    $list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time','avgTrainPace', 'gender');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			$isRace = ($ac->activityType == 'race') ? 1 : -1;
			$gender = ($athlete->gender == 'M') ? 1 : -1;
	    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $athlete->averageTrainingPace, $gender);
	    
		    
		}
		$this->writeCsv($list, 'activitiesFeatures');

	}

	public function writeActivitySetFeatures($athleteId) {	
		global $db;
		$list = [];
    	$list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
	    $db = Db::getInstance();

	    $query = 'SELECT strava_id FROM athlete where strava_id != '.$athleteId;
	    $athletes = $db->query($query);
	    foreach ($athletes as $athlete) {
	    	$result = $db->query('SELECT average_training_pace, gender FROM athlete WHERE strava_id =' . $athlete['strava_id']);
			$avgSpeed = $result[0]['average_training_pace'];
			$gender = ($result[0]['gender'] == 'M' ? 1 : -1);

		    $query = 'SELECT strava_id, athlete_id, activity_timestamp, name, elapsed_time, distance, average_speed, elevation_gain, elevation_loss, vo2_max, climb_score, percentage_hilly, surface, activity_type, split_type, average_ngp, training_stress_score, pre_activity_atl, pre_activity_ctl, serialized_xweek_summary FROM activity where athlete_id = '.$athlete['strava_id'];
		    $result = $db->query($query);
		    $activities = [];
		    if(!empty($result)) {
		        foreach ($result as $ac) {
		            $activity = new Activity($ac, 'db');
		            $activities[] = $activity;
		        }
		    }
		    
			$clusters = $this->clusterActivities($activities);
			$fastClusters = $clusters['fast_clusters'];
			$fastActivities = [];
			for($i = 0; $i < count($fastClusters); $i++) {
				$c = $fastClusters[$i];
				for($j = 0; $j < count($c); $j++) {
					$fastActivities[] = $c[$j];
				}	    
			}
			$fastActivities = $this->sortActivitiesByDate($fastActivities);
			
			for($i = 0; $i < count($fastActivities); $i++) {

				$ac = $fastActivities[$i];
				
				$isRace = ($ac->activityType == 'race') ? 1 : -1;
				
		    	$list[] = array($ac->distance, $ac->elevationGain, $ac->percentageHilly, $ac->climbScore, $ac->preAtl, $ac->preCtl, $isRace, $ac->xWeekSummary->averageVo2Max, $ac->elapsedTime / 60, $avgSpeed, $gender);
		    
			    
			}

		}
		$this->writeCsv($list, 'activitySetFeatures');

	}


	public function writeSegmentFeatures($activities) {	
		global $db;
		// $filterdActivities = $this->basicActivityFiltering($activities);
		$clusters = $this->clusterActivities($activities);
		$fastClusters = $clusters['fast_clusters'];
		$fastActivities = [];
		for($i = 0; $i < count($fastClusters); $i++) {
			$c = $fastClusters[$i];
			for($j = 0; $j < count($c); $j++) {
				$fastActivities[] = $c[$j];
			}	    
		}
		$fastActivities = $this->sortActivitiesByDate($fastActivities);
		
	    $list = [];
	    $list[] = array('id', 'activityDistance', 'activityTime', 'activityElevation', 'isRace', 'segStartDist', 'segEndDist', 'segLength', 'segGrade', 'elevGainDone', 'segTime');
		for($i = 0; $i < count($fastActivities); $i++) {
			$ac = $fastActivities[$i];
			$isRace = ($ac->activityType == 'race') ? 1 : -1;
			$result = $db->query('SELECT serialized_segments FROM activity WHERE strava_id =' . $ac->id);
			$segments = unserialize($result[0]['serialized_segments']);
			$elevGainDone = 0;
			$elevationDonePercent = 0;
			for($m = 0; $m < count($segments); $m++) {
				$seg = $segments[$m];
				// $seg->printSegment();
				$vel = $seg->length / ($seg->end->time - $seg->start->time);
				if($ac->activityType != 'race' && $vel < 1.38) { // 12:00 min/km
					continue;
				}
				if($ac->activityType != 'race' && $seg->length < 50) {
					continue;
				}
				
				$segLength = $seg->end->distance - $seg->start->distance;
				$segTime = $seg->end->time - $seg->start->time;
				$list[] = array($i, $ac->distance, $ac->elapsedTime, $ac->elevationGain, $isRace, $seg->start->distance, $seg->end->distance, $segLength, $seg->gradient, $elevGainDone, $segTime);
				$elevGainDone = ($seg->elevation > 0) ? $elevGainDone + $seg->elevation : $elevGainDone;
				// if($ac->elevationGain != 0) {
				// 	$elevationDonePercent = $elevGainDone / $ac->elevationGain;
				// } else {
				// 	$elevationDonePercent = 1;
				// }
			}
					    
		}
		$this->writeCsv($list, 'segmentFeatures');

	}

	public function writePredictionData($athlete) {
		$list[] = array('distance', 'elevation', 'hilly', 'climbScore', 'atl', 'ctl', 'isRace', 'avgVo2max', 'time', 'avgTrainPace', 'gender');
		$vo2max = $athlete->xWeekSummary->averageVo2Max;
		$gender = ($athlete->gender == 'M') ? 1 : -1;
		$list[] = array(5000,0,0,0,30,45,1,$vo2max,17.5,$athlete->averageTrainingPace,$gender);
		$list[] = array(5000,70,0.1,0.2,30,45,1,$vo2max,19,$athlete->averageTrainingPace,$gender);
		$list[] = array(5000,150,0.3,1.5,30,45,1,$vo2max,22,$athlete->averageTrainingPace,$gender);
		$list[] = array(10000,0,0,0,30,45,1,$vo2max,36.5,$athlete->averageTrainingPace,$gender);
		$list[] = array(10000,70,0.3,0.5,30,45,1,$vo2max,39,$athlete->averageTrainingPace,$gender);
		$list[] = array(10000,150,0.5,1.5,30,45,1,$vo2max,41,$athlete->averageTrainingPace,$gender);
		$list[] = array(21097,0,0,0,30,45,1,$vo2max,79,$athlete->averageTrainingPace,$gender);
		$list[] = array(21097,70,0.3,0.5,30,45,1,$vo2max,81,$athlete->averageTrainingPace,$gender);
		$list[] = array(21097,150,0.4,1.5,30,50,1,$vo2max,85,$athlete->averageTrainingPace,$gender);
		$list[] = array(42195,0,0,0,30,50,1,$vo2max,170,$athlete->averageTrainingPace,$gender);
		$list[] = array(42195,150,0.5,1,30,50,1,$vo2max,185,$athlete->averageTrainingPace,$gender);
		$this->writeCsv($list, 'predictions');
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
		$filterdActivities = $this->basicActivityFiltering($activities);
		$taggedActivities = $this->clusterActivities($filterdActivities);
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
			if($ac->distance < 2500 || $ac->distance > 50000) {
				continue;
			}
			if($ac->averageNGP < 1.6 || $ac->averageNGP > 7) {
				continue;
			}
			if($ac->averageSpeed < 1.6 || $ac->averageSpeed > 7) {
				continue;
			}
			if($ac->xWeekSummary->averageTrainingPace > 5.5) {
				continue;
			}
			if($ac->xWeekSummary->averageVo2Max < 10 || $ac->xWeekSummary->averageVo2Max > 85) {
				continue;
			}
			if($ac->tss > 1000) {
				continue;
			}
			if($ac->preAtl > 250) {
				continue;
			}
			if($ac->preCtl > 200) {
				continue;
			}

			$filteredActivities[] = $ac;
		}


		return $filteredActivities;
	}

	public function sortActivitiesByDate($activities) {
		// foreach ($activities as $ac) {
		// 	echo $ac->date.'<br>';
		// }
		usort($activities, function($a, $b) {
		   return strtotime($a->date) - strtotime($b->date);
		});
		// foreach ($activities as $ac) {
		// 	echo $ac->date.'<br>';
		// }
		return $activities;
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
			// $speedThreshold = ($speedMean + $speedMax) / 2.0;
			$speedThreshold = $speedMean;
			$slowList = [];
			$fastList = [];
			foreach ($c as $ac) {
				if($ac->activityType == 'race') {
					$fastList[] = $ac;
					continue;
				}
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