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

function computeSegments($elevArray, $distanceArray) {

	$segments = [];
	$flatGradientTreshold = 1.8;
	$steepGradientTreshold = 4.5;
	// $currentElevPoint     = $elevArray[0];
	// $currentDistPoint       = $distanceArray[0];
	$prevElevPoint = $elevArray[0];
	$prevDistPoint = $distanceArray[0];
	$currentState = 0;
	$prevState = 0;

	$prevExtremeElev = $elevArray[0];
	$prevExtremeDist = $distanceArray[0];
	$segments[] = array($prevExtremeDist, $prevExtremeElev);


	for ($i = 1; $i < count($elevArray); $i++) {
		$currentElevPoint = $elevArray[$i];
		$currentDistPoint = $distanceArray[$i];
	    $relDist     = $currentDistPoint - $prevDistPoint;
	    $gradient = 0;

	    // compute gradient between two GPS points
	    if ($relDist > 0) {
	        $gradient = round(($currentElevPoint - $prevElevPoint) / $relDist * 100, 4);
	    }

	    // check for abnormal gradients
	    if ($gradient < 20 || $gradient > -20) {

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

	        if ($currentState != $prevState && $prevState != 'null') {
	        	$segments[] = array($prevExtremeDist, $prevExtremeElev);
                $prevExtremeElev  = $prevElevPoint;
                $prevExtremeDist = $prevDistPoint;
                $prevState        = $currentState;
	            
	        } else {
	            $prevState = $currentState;
	        }
	    }
	    $prevElevPoint = $currentElevPoint;
	    $prevDistPoint   = $currentDistPoint;

	}

	$segments[] = array($prevDistPoint, $prevElevPoint);
	echo 'resulted segment points: ' . count($segments) . '<br>';
	writeCsv($segments, 'segments');
	return $segments;
}

function computeExtrema($elevObjArray, $distanceArray) {

	$gpxWP            = array();
	$gradientTreshold = 2.5;
	$minSegmentLength = 150;
	$segmentLength    = 0;
	$lastWayPoint     = $elevObjArray[0];
	$lastWPDist       = $distanceArray[0];
	$state            = 'null';
	$lastState        = 'null';

	$lastExtremePoint = $lastWayPoint;
	$lastExtremeDist  = $lastWPDist;
	$outXPoints;
	$pythonOut;
	$pythonOut[] = array($lastWPDist, $lastWayPoint->elevation);
	for ($i = 1; $i < count($elevObjArray); $i++) {
	    $currentWayPoint = $elevObjArray[$i];
	    $distBetween     = $distanceArray[$i] - $lastWPDist;
	    $segmentLength += $distBetween;
	    if ($distBetween > 0) {
	        $gradient = round(($currentWayPoint->elevation - $lastWayPoint->elevation) / $distBetween * 100, 4);
	    } else {
	        $gradient = 0;
	    }

	    if ($gradient < 20 || $gradient > -20) {
	        if ($gradient > $gradientTreshold && $state != 'up') {
	            $state = 'up';
	            // echo $state . ' ';

	        } elseif ($gradient < -$gradientTreshold && $state != 'down') {
	            $state = 'down';
	            // echo $state. ' ';

	        } elseif ($gradient >= -$gradientTreshold && $gradient <= $gradientTreshold && $state != 'flat') {
	            $state = 'flat';
	            // echo $state. ' ';

	        }
	        if ($state != $lastState && $lastState != 'null') {
	            if($segmentLength >= $minSegmentLength) {
	                $xtremGrad = round(($lastWayPoint->elevation - $lastExtremePoint->elevation) / ($lastWPDist - $lastExtremeDist) * 100, 2);

	                array_push($gpxWP, array(array($lastExtremePoint->location->lat, $lastExtremePoint->location->lng), $lastExtremePoint->elevation, $lastState . ' ' . $lastExtremeDist . ' ' . $xtremGrad));

	                $outXPoints[]     = array($lastWPDist, round(($lastWPDist - $lastExtremeDist), 0), $xtremGrad);
	                $pythonOut[]      = array($lastWPDist, $lastWayPoint->elevation);
	                $lastExtremeDist  = $lastWPDist;
	                $lastExtremePoint = $lastWayPoint;
	                $lastState        = $state;
	                $segmentLength    = 0;
	            }
	        } else {
	            $lastState = $state;
	        }
	    }
	    $lastWayPoint = $currentWayPoint;
	    $lastWPDist   = $distanceArray[$i];

	}

	$xtremGrad = round(($lastWayPoint->elevation - $lastExtremePoint->elevation) / ($lastWPDist - $lastExtremeDist) * 100, 2);
	$gpxWP[] = array(array($lastExtremePoint->location->lat, $lastExtremePoint->location->lng), $lastExtremePoint->elevation, $lastState . ' ' . $lastExtremeDist . ' ' . $xtremGrad);
	$outXPoints[] = array($lastWPDist, round(($lastWPDist - $lastExtremeDist), 0), $xtremGrad);
	$pythonOut[]  = array($lastWPDist, $lastWayPoint->elevation);
	echo 'resulted extrema points: ' . count($gpxWP) . '<br>';
	writeGPX($gpxWP, 'googleGPX');
	writeOutput($outXPoints, 'outputXPoints');
	writeCsv($pythonOut, 'elevProfile');
}

function computeElevationGain($elevArray) {
	$elevGain = 0;
	$lastAbsElev = $elevArray[0];
	foreach ($elevArray as $absElev) {
		$relElev = $absElev - $lastAbsElev;

		if($relElev > 0) {
			$elevGain += $relElev;
			// echo $relElev . ' ';
		}
		$lastAbsElev = $absElev;
	}
	return $elevGain;
}




### write functions ###

function writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray) {
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
	writeCsv($originalList, 'originalData');
	writeCsv($diffList, 'stravaGoogleDifference');
}

function writeGPX($list, $name)
{
    $fp     = fopen('output/' . $name . '.gpx', 'w+');
    $header = '<?xml version="1.0" encoding="UTF-8"?>
    <gpx creator="Julian" version="1.0" xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" >';
    fwrite($fp, $header);
    foreach ($list as $point) {

        $string = '<wpt lat="' . $point[0][0] . '" lon="' . $point[0][1] . '">
        <ele>' . $point[1] . '</ele>
        <name>' . $point[1] . ': ' . $point[2] . '</name>
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

function writeOutput($array, $name)
{
    $str = '';
    foreach ($array as $set) {
        $str .= $set[0] . ',' . $set[1] . ',' . $set[2] . '|';
    }
    $str = substr($str, 0, -1);
    echo 'output string: ' . $str;
    $fp = fopen('output/' . $name . '.xml', 'w+');
    fwrite($fp, $str);
    fclose($fp);

}
