<?php
include_once 'googleElevationClient.php';
include_once 'RDP.php';
### processing elevation data ###


function getGoogleElevation($latlongArray) {
	$googleClient = new GoogleElevationClient();
	foreach ($latlongArray as $coord) {
	    $googleClient->addCoordinate($coord[0], $coord[1]);
	}
	$response = $googleClient->fetchJSON();
	// echo count($response) . ' ' . count($latlongArray);
	return $response;
}

// remove points which are too close to eachother
function cleanGoogleElevation($elevArray, $distArray)
{	
    $elevResponse  = array();
    $distResponse  = array();
    $distThreshold = 5;
    $sum           = 0;
    // echo $distArray[0];
    $lastDist = $distArray[0];

    // push first one
    array_push($elevResponse, $elevArray[0]->elevation);
    array_push($distResponse, $distArray[0]);

    for ($i = 1; $i < count($distArray); $i++) {
        $dist = $distArray[$i] - $lastDist;
        $sum += $dist;
        // echo $sum . ' '.$dist . ', ';
        if ($sum >= $distThreshold) {
            array_push($elevResponse, $elevArray[$i]->elevation);
            array_push($distResponse, $distArray[$i]);
            $sum = 0;
        }

        $lastDist = $distArray[$i];
    }
    // push last one
    array_push($elevResponse, $elevArray[count($elevArray) - 1]->elevation);
    array_push($distResponse, $distArray[count($distArray) - 1]);
    echo 'remove measure points that are closer than 5 m distance, count before: ' . count($distArray) . ' count after: ' . count($distResponse) . ' <br>';
    return [$elevResponse, $distResponse];
}

function applyRDP($elevationDistanceArray, $delta) {
	$rdpList   = array();
	$i         = 0;
	foreach ($elevationDistanceArray[0] as $elev) {
	    $rdpList[] = array($elevationDistanceArray[1][$i], $elev);
	    $i++;
	}
	$rdpResult = RDP::RamerDouglasPeucker2d($rdpList, $delta);
	echo 'apply RDP algo. count: ' . count($rdpResult) . '<br>';
	return $rdpResult;
}

function computeSegments($distElevArray, $flatGradientTreshold, $steepGradientTreshold) {

    $elevArray = array_column($distElevArray, 1);
    $distArray = array_column($distElevArray, 0);
	$segments = [];
	// $flatGradientTreshold = 1.5;
	// $steepGradientTreshold = 4.5;
	$prevElevPoint = $elevArray[0];
	$prevDistPoint = $distArray[0];
	$currentState = 0;
	$prevState = 0;

	$prevSegmentElev = $elevArray[0];
	$prevSegmentDist = $distArray[0];
	// $segments[] = array($prevSegmentDist, $prevSegmentElev);


	for ($i = 1; $i < count($elevArray); $i++) {
		$currentElevPoint = $elevArray[$i];
		$currentDistPoint = $distArray[$i];
	    $relDist = $currentDistPoint - $prevDistPoint;
	    $gradient = 0;

	    // compute gradient between two GPS points
	    if ($relDist > 0) {
	        $gradient = getGradient($relDist, $currentElevPoint - $prevElevPoint);
	    }

	    // check for abnormal gradients
	    // if ($gradient < 20 || $gradient > -20) {

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
	        	$segments[] = array($prevSegmentDist, $prevSegmentElev);
                $prevSegmentElev = $prevElevPoint;
                $prevSegmentDist = $prevDistPoint;
                $prevState = $currentState;
	            
	        } else {
	            $prevState = $currentState;
	        }
	    // }
	    $prevElevPoint = $currentElevPoint;
	    $prevDistPoint = $currentDistPoint;

	}
	// add last 
	$segments[] = array($prevSegmentDist, $prevSegmentElev);
	$segments[] = array($prevDistPoint, $prevElevPoint);
	echo 'resulted segment points: ' . count($segments) . '<br>';
	
	return $segments;
}

function filterSegments($segments) {
	$thresholdGradient = 7.5;
	$minLength = 200;
	// for ($i = 1; $i < count($segments); $i++) {
		
	// 	$length = $segments[$i][0] - $segments[$i - 1][0];
	// 	$gradient = 0;
	// 	if ($length > 0) {
	//         $gradient = round(($segments[$i][1] - $segments[$i - 1][1]) / $length * 100, 4);
	//     }
	// 	echo $length . ' '.$gradient.', ';
		
	// }

	## filter too steep gradients
	for ($i = 1; $i < count($segments); $i++) {
		
		$length = $segments[$i][0] - $segments[$i - 1][0];
		$gradient = 0;
		if ($length > 0) {
	        $gradient = getGradient($length, $segments[$i][1] - $segments[$i - 1][1]);
	    }
		// echo $length . ' '.$gradient.', ';
		if(($gradient > $thresholdGradient || $gradient < -$thresholdGradient) && $length < $minLength) {
			array_splice($segments, $i, 1);
		}
	}

	## filter too short segments
	for ($i = 1; $i < count($segments); $i++) {
		
		$length = $segments[$i][0] - $segments[$i - 1][0];
		
		if($length < $minLength) {
			array_splice($segments, $i, 1);
		}
	}
	echo 'filtered segment points: ' . count($segments) . '<br>';
	
	return $segments;
}


function getGradient($length, $height) {
	$gradient = 0;
	if ($length > 0) {
        $gradient = round($height / $length * 100, 2);
    }
    return $gradient;
}


function calculateElevationGain($elevArray) {
	$up = 0;
	$down = 0;
	$lastAbsElev = $elevArray[0];
	foreach ($elevArray as $absElev) {
		$relElev = $absElev - $lastAbsElev;

		if($relElev > 0) {
			$up += $relElev;
		} else if($relElev < 0) {
			$down += $relElev;
		}
		$lastAbsElev = $absElev;
	}
	return [$up, $down];
}

function getFietsIndex($distance, $relElevation, $altitudeAtTop) {
        if ($relElevation < 0) {
            return 0.0;
        }
        $index = $relElevation * $relElevation / ($distance * 10);
        $altitudeBonus = max(0, ($altitudeAtTop - 1000) / 1000);
        return $index + $altitudeBonus;
}

function computeClimbs($segments) {
	$gradientClimbThreshold = 2;
	$startDist = 0;
    $startElev = 0;
    $climbs = [];
    $tempClimb = [];
    for ($i = 1; $i < count($segments); $i++) {
        $length = $segments[$i][0] - $segments[$i - 1][0];
        $gradient = getGradient($length, $segments[$i][1] - $segments[$i - 1][1]);
        if($gradient > $gradientClimbThreshold && $startDist == 0) {
        	$startDist = $segments[$i-1][0];
        	$startElev = $segments[$i-1][1];
            echo 'climb start: '.$startDist.' elev:'.$startElev.' ';
            $tempClimb[] = array($startDist, $startElev);
        } else if($gradient > $gradientClimbThreshold) {
        	$tempClimb[] = array($segments[$i-1][0], $segments[$i-1][1]);
    	} else if($gradient <= $gradientClimbThreshold && $startDist > 0) {
    		$tempClimb[] = array($segments[$i-1][0], $segments[$i-1][1]);
        	$climbs[] = $tempClimb;
        	$tempClimb = [];
        	$fiets = getFietsIndex($segments[$i-1][0] - $startDist, $segments[$i-1][1] - $startElev, $segments[$i-1][1]);
            echo 'climb end: '.$segments[$i-1][0].' elev:'.$segments[$i-1][1].' fiets: '.$fiets.'<br>';
            $startDist = 0;
            $startElev = 0;
        }
             
    }
    if($startDist > 0) {
    	$tempClimb[] = array($segments[$i-1][0], $segments[$i-1][1]);
        $climbs[] = $tempClimb;
    	$fiets = getFietsIndex($segments[$i-1][0] - $startDist, $segments[$i-1][1] - $startElev, $segments[$i-1][1]);
        echo 'climb end: '.$segments[$i-1][0].' elev:'.$segments[$i-1][1].' fiets: '.$fiets.'<br>';
    }
    return $climbs;
}




### write functions ###

function writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray, $athleteName) {
	$index        = 0;
	$diffList     = array();
	array_push($diffList, array('strava', 'google', 'distance'));

	foreach ($googleElevation as $obj) {
	    array_push($diffList, array($stravaElevation[$index], $obj->elevation, $distanceArray[$index]));
	    $index++;
	}

	$originalList = array();
	$i         = 0;
	foreach ($elevationDistanceArray[0] as $elev) {
	    array_push($originalList, array($elev, $elevationDistanceArray[1][$i]));
	    $i++;
	}
	writeCsv($originalList, $athleteName.'/originalData');
	writeCsv($diffList, $athleteName.'/stravaGoogleDifference');
}

function writeGPX($segments, $name, $distanceArray, $latlongArray)
{
    $fp     = fopen('output/' . $name . '.gpx', 'w+');
    $header = '<?xml version="1.0" encoding="UTF-8"?>
    <gpx creator="Julian" version="1.0" xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" >';
    fwrite($fp, $header);
    foreach ($segments as $point) {

    	$index = array_search($point[0], $distanceArray);
    	$latlong = $latlongArray[$index];
        $string = '<wpt lat="' . $latlong[0] . '" lon="' . $latlong[1] . '">
        <name>' . $point[1] .'</name>
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
    $prevSegm = $segments[0];
    for($i = 1; $i < count($segments); $i++) {
    	$relDist = $segments[$i][0] - $prevSegm[0];
    	$gradient = getGradient($relDist, $segments[$i][1] - $prevSegm[1]);
        $str .= $prevSegm[0] . ',' . $relDist . ',' . $gradient . '|';
        $prevSegm = $segments[$i];
    }
    $str = substr($str, 0, -1);
    echo 'output string: ' . $str;
    $fp = fopen('output/' . $name . '.xml', 'w+');
    fwrite($fp, $str);
    fclose($fp);

}

function writeRegressionCsv($list, $name)
{
    $fp = fopen('data/output/' . $name . '_data.csv', 'w+');
    fputcsv($fp, array('time', 'distance', 'pace', 'elevation', 'vo2max'));
    foreach ($list as $line) {
        fputcsv($fp, $line);
    }
    fclose($fp);
}
