<?php

class FileWriter {
	
	private $path;

	public function __construct($athleteName) {
		$this->path = 'output/'.$athleteName.'/';
	}

	public function writeControlData($data, $type) {
		if($type == 'original') {
			$list = [];
			array_push($list, array('strava', 'google', 'distance'));

			foreach ($data as $point) {
			    $list[] = array($point->stravaAlt, $point->altitude, $point->distance);
			}

			$this->writeCsv($list, 'originalData');
		} else if($type == 'rdp') {
			$list = [];
			foreach ($data as $point) {
			    $list[] = array($point->distance, $point->altitude);
			}

			$this->writeCsv($list, 'rdp');
		} else if($type == 'segments') {
			$list = [];
			foreach ($data as $segment) {
			    $list[] = array($segment->start->distance, $segment->start->altitude);
			}
			$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

			$this->writeCsv($list, 'segments');
		} else if($type == 'filtered') {
			$list = [];
			foreach ($data as $segment) {
			    $list[] = array($segment->start->distance, $segment->start->altitude);
			}
			$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

			$this->writeCsv($list, 'filteredSegments');
		} else if($type == 'recompute') {
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
		$path = $this->path . $name. '.csv';
	    $fp = fopen($path, 'w+');
	    foreach ($list as $line) {
	        fputcsv($fp, $line);
	    }
	    fclose($fp);
	}



	public function writeOutput($segments)
	{
	    $str = '';
	    // $prevSegm = $segments[0];
	    for($i = 0; $i < count($segments); $i++) {
	        $str .= $segments[$i]->start->distance . ',' . $segments[$i]->length . ',' . $segments[$i]->gradient . '|';
	        // $prevSegm = $segments[$i];
	    }
	    $str = substr($str, 0, -1);
	    echo 'output string: ' . $str;
	    $fp = fopen( $this->path . 'outputString.xml', 'w+');
	    fwrite($fp, $str);
	    fclose($fp);

	}


}