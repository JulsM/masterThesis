<?php
include_once 'Configuration.php';

function my_autoload($class_name) {
    $file = 'classes/'.$class_name.'.php';
    if(file_exists($file)) {
      	include_once($file);
    }
}
spl_autoload_register('my_autoload');
### processing elevation data ###

function generateDataPoints($stream) {
	$latlongArray   = $stream[0]['data'];
    $timeArray   = $stream[1]['data'];
    $distanceArray  = $stream[2]['data'];
    $stravaElevation = $stream[3]['data'];
    ### get google elevation data
    $googleElevation = getGoogleElevation($latlongArray);

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


function getGoogleElevation($latlongArray) {
	$googleClient = new GoogleElevationClient();
	foreach ($latlongArray as $coord) {
	    $googleClient->addCoordinate($coord[0], $coord[1]);
	}
	$response = $googleClient->fetchJSON();
	return $response;
}

// remove points which are too close to eachother
function cleanDataPoints($dataPoints)
{	
    $response  = [];
    $distThreshold = Config::$cleanMinDist;
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
    echo 'remove measure points that are closer than '.Config::$cleanMinDist.' m distance, count before: ' . count($dataPoints) . ' count after: ' . count($response) . ' <br>';
    return $dataPoints;
}


function computeSegments($dataPoints, $flatGradientTreshold, $steepGradientTreshold) {

	$segments = [];
	$prevPoint = $dataPoints[0];
	$currentState = 0;
	$prevState = 0;

	$startSegment = $dataPoints[0];

	for ($i = 1; $i < count($dataPoints); $i++) {
		$currentPoint = $dataPoints[$i];
	    // compute gradient between two data points
	    $relDist = $currentPoint->distance - $prevPoint->distance;
	    $height = $currentPoint->altitude - $prevPoint->altitude;
	    $gradient = 0;
		if ($relDist > 0) {
	        $gradient = round($height / $relDist * 100, 2);
	    }
        

        if ($gradient >= $steepGradientTreshold) {
            $currentState = 'up2';
        } elseif ($gradient >= $flatGradientTreshold && $gradient < $steepGradientTreshold) {
            $currentState = 'up';
        } elseif ($gradient < $flatGradientTreshold && $gradient > -$flatGradientTreshold) {
            $currentState = 'level';
        } elseif ($gradient <= -$flatGradientTreshold && $gradient > -$steepGradientTreshold) {
            $currentState = 'down';
        } elseif ($gradient <= -$steepGradientTreshold) {
            $currentState = 'down2';
        }
        // if state changed add point to segments
        if ($currentState != $prevState && $prevState != 'null') {
        	$s = new Segment($startSegment, $prevPoint);
        	$s->update();
        	$segments[] = $s;
            $startSegment = $prevPoint;
            $prevState = $currentState;
            
        } else {
            $prevState = $currentState;
        }

	    $prevPoint = $currentPoint;

	}
	// add last 
	$s = new Segment($startSegment, $prevPoint);
	$s->update();
	$segments[] = $s;
	
	return $segments;
}

function recomputeSegments($segments, $flatGradientTreshold, $steepGradientTreshold) {

	$response = [];
	$currentState = 0;
	$prevState = 0;

	$startSegment = $segments[0]->start;

	for ($i = 0; $i < count($segments); $i++) {
		$currentSegment = $segments[$i];
	    // compute gradient between two data points
        $gradient = $currentSegment->gradient;


        if ($gradient >= $steepGradientTreshold) {
            $currentState = 'up2';
        } elseif ($gradient >= $flatGradientTreshold && $gradient < $steepGradientTreshold) {
            $currentState = 'up';
        } elseif ($gradient < $flatGradientTreshold && $gradient > -$flatGradientTreshold) {
            $currentState = 'level';
        } elseif ($gradient <= -$flatGradientTreshold && $gradient > -$steepGradientTreshold) {
            $currentState = 'down';
        } elseif ($gradient <= -$steepGradientTreshold) {
            $currentState = 'down2';
        }
        // if state changed add new segment
        if ($currentState != $prevState && $prevState != 'null') {
        	$s = new Segment($startSegment, $currentSegment->start);
        	$s->update();
        	$response[] = $s;
            $startSegment = $currentSegment->start;
            $prevState = $currentState;
            
        } else {
            $prevState = $currentState;
        }

	}
	// add last 
	$s = new Segment($startSegment, $segments[(count($segments)-1)]->end);
	$s->update();
	$response[] = $s;

	// foreach ($segments as $s) {
	// 	echo $s->length.' '.$s->gradient.', ';
	// }
	
	
	return $response;
}

function filterSegments($segments) {
	$thresholdGradient = Config::$maxSegmentGradient;
	$minLength = Config::$minSegmentLength;
	$response = [];

	## filter too steep gradients
	for ($i = 0; $i < count($segments); $i++) {
		
		$gradient = $segments[$i]->gradient;
		// echo $segments[$i]->length . ' '.$gradient.', ';
		if(($gradient <= $thresholdGradient || $gradient >= -$thresholdGradient) && $segments[$i]->length >= $minLength) {
			$response[] = $segments[$i];
		} else {
			if($i < count($segments) - 2) {
				$s = new Segment($segments[$i]->start, $segments[$i + 1]->end);
				$s->update();
				$response[] = $s;
				$i++;
			} else {
				$response[] = $segments[$i];
			}
		}
	}

	
	$segments = $response;
	$response = [];
	## filter too short segments
	for ($i = 0; $i < count($segments); $i++) {
		if($segments[$i]->length >= $minLength) {
			$response[] = $segments[$i];
		} else {
			if($i < count($segments) - 2) {
				$s = new Segment($segments[$i]->start, $segments[$i + 1]->end);
				$s->update();
				$response[] = $s;
				$i++;
			} else {
				$response[] = $segments[$i];
			}
		}
	}
	// foreach ($segments as $s) {
	// 	echo $s->length.' '.$s->gradient.', ';
	// }
	
	return $response;
}



function calculateElevationGain($segments) {
	$up = 0;
	$down = 0;
	foreach ($segments as $segment) {
		$relElev = $segment->elevation;
		if($relElev > 0) {
			$up += $relElev;
		} else if($relElev < 0) {
			$down += $relElev;
		}
	}
	return [$up, $down];
}



function getPercentageHilly($segments, $totalDistance) {
	$hilly = 0;
    for ($i = 0; $i < count($segments); $i++) {
        $gradient = $segments[$i]->gradient;
        if($gradient > Config::$hillySegmentThreshold || $gradient < -Config::$hillySegmentThreshold) {
        	$hilly += $segments[$i]->length;
        }

    }
    return round($hilly / $totalDistance, 2);
}

function computeClimbs($segments) {
	$gradientClimbThreshold = Config::$minClimbGradient;
	$minClimbLength = Config::$minClimbLength;
	$maxBetweenDown = Config::$maxDistDownBetween;

	$tempClimb = [];
	$tempClimbDist = 0;
    $tempDownDist = 0;
    $tempDownClimb = [];
    
    $climbs = [];
    for ($i = 0; $i < count($segments); $i++) {
        $gradient = $segments[$i]->gradient;
        
        if($gradient > $gradientClimbThreshold && empty($tempClimb)) { // start climb
            // echo 'climb start: '.$segments[$i]->start->distance.' elev:'.$segments[$i]->start->altitude.' ';
            $tempClimb[] = $segments[$i];
            $tempClimbDist = $segments[$i]->length;
        } else if($gradient > $gradientClimbThreshold) { // continue climb uphill
        	if(!empty($tempDownClimb)) {
        		$tempClimb = array_merge($tempClimb, $tempDownClimb);
        		$tempDownDist = 0;
        		$tempDownClimb = [];
        	}
        	$tempClimb[] = $segments[$i];
        	$tempClimbDist += $segments[$i]->length;
        	

    	} else if($gradient <= $gradientClimbThreshold && !empty($tempClimb)) { // end climb
    		if($tempClimbDist >= $minClimbLength) { // check if climb length before going under threshold
	    		$tempDownClimb[] = $segments[$i];
	    		$tempDownDist += $segments[$i]->length;
	    	} else {
	    		$tempClimb = [];
	            $tempDownClimb = [];
	            $tempDownDist = 0;
	            $tempClimbDist = 0;
	    	}
        }
        // if inbetween downhill is to long, end climb
        if($tempDownDist > $maxBetweenDown && !empty($tempClimb)) { 

        	if($tempClimbDist >= $minClimbLength) {
        		$c = new Climb($tempClimb);
        		$c->update();
	        	$climbs[] = $c;
	        	
	        	$fiets = $c->getFietsIndex();
	            // echo 'climb end: '.$c->end->distance.' elev:'.$c->end->altitude.' fiets: '.$fiets.'<br>';
	            
	        }
	        $tempClimb = [];
            $tempDownClimb = [];
            $tempDownDist = 0;
            $tempClimbDist = 0;
        }
             
    }
    if(!empty($tempClimb) && $tempClimbDist >= $minClimbLength) {
        $c = new Climb($tempClimb);
		$c->update();
    	$climbs[] = $c;
    	
    	$fiets = $c->getFietsIndex();
        // echo 'climb end: '.$c->end->distance.' elev:'.$c->end->altitude.' fiets: '.$fiets.'<br>';
	    
    }
    return $climbs;
}

function calculateClimbScore($climbs, $totalDistance, $percentageFlat) {
	$fietsSum = 0;
	foreach ($climbs as $climb) {
		$endIndex = count($climb) - 1;
		$fietsSum += $climb->fietsIndex;
	}
	$singleScores = $fietsSum / max(1.0, sqrt($totalDistance / 20000));
	$compensateFlats = min(1.0, max(0.0, 1.0 - $percentageFlat * $percentageFlat));
	$score = min(10.0, max(0.0, 2.0 * log(0.5 + (1.0 + $singleScores) * $compensateFlats, 2.0)));
	return round($score, 2);
}





### write functions ###

function writeControlData($data, $athleteName, $type) {
	if($type == 'original') {
		$list = [];
		array_push($list, array('strava', 'google', 'distance'));

		foreach ($data as $point) {
		    $list[] = array($point->stravaAlt, $point->altitude, $point->distance);
		}

		writeCsv($list, $athleteName.'/originalData');
	} else if($type == 'rdp') {
		$list = [];
		foreach ($data as $point) {
		    $list[] = array($point->distance, $point->altitude);
		}

		writeCsv($list, $athleteName.'/rdp');
	} else if($type == 'segments') {
		$list = [];
		foreach ($data as $segment) {
		    $list[] = array($segment->start->distance, $segment->start->altitude);
		}
		$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

		writeCsv($list, $athleteName.'/segments');
	} else if($type == 'filtered') {
		$list = [];
		foreach ($data as $segment) {
		    $list[] = array($segment->start->distance, $segment->start->altitude);
		}
		$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

		writeCsv($list, $athleteName.'/filteredSegments');
	} else if($type == 'recompute') {
		$list = [];
		foreach ($data as $segment) {
		    $list[] = array($segment->start->distance, $segment->start->altitude);
		}
		$list[] = array($data[count($data)-1]->end->distance, $segment->end->altitude);

		writeCsv($list, $athleteName.'/recomputedSegments');
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
		

		writeCsv($list, $athleteName.'/climbs');
	}
}

function writeGPX($segments, $name)
{
    $fp     = fopen('output/' . $name . '.gpx', 'w+');
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

function writeCsv($list, $name)
{
    $fp = fopen('output/' . $name . '.csv', 'w+');
    foreach ($list as $line) {
        fputcsv($fp, $line);
    }
    fclose($fp);
}



function writeOutput($segments, $name)
{
    $str = '';
    // $prevSegm = $segments[0];
    for($i = 0; $i < count($segments); $i++) {
        $str .= $segments[$i]->start->distance . ',' . $segments[$i]->length . ',' . $segments[$i]->gradient . '|';
        // $prevSegm = $segments[$i];
    }
    $str = substr($str, 0, -1);
    echo 'output string: ' . $str;
    $fp = fopen('output/' . $name . '.xml', 'w+');
    fwrite($fp, $str);
    fclose($fp);

}


