<?php

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

	public function writeRaceFeatures($activities) {	
		
	    $list = [];
	    $list[] = array('distance', 'elevation', 'avgSpeed', 'hilly', 'climbScore', 'finishTime');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
			if($ac->activityType == 'race') {
		    	// $list[] = array($ac->distance / 1000, $ac->elapsedTime / 60);
		    	$list[] = array($ac->distance / 1000, $ac->elevationGain, $ac->averageSpeed, $ac->percentageHilly, $ac->climbScore, $ac->elapsedTime / 60);
		    }
		}
		$this->writeCsv($list, 'raceFeatures');

	}

	public function writeTsbModel($activities) {	
		
	    $list = [];
	    $list[] = array('date', 'tss', 'atl', 'ctl', 'vo2max');
		for($i = 0; $i < count($activities); $i++) {
			$ac = $activities[$i];
	    	$list[] = array(date('Y-m-d H:i:s', strtotime($ac->date)), $ac->tss, $ac->preAtl, $ac->preCtl, $ac->vo2Max);
		    
		}
		$this->writeCsv($list, 'tsbModel');

	}

	public function lock() {
		$this->lock = true;
	}


}