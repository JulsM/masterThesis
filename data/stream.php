<?php
include_once '../queryApi.php';
include_once 'googleElevationClient.php';
include_once 'RDP.php';

if (isset($_POST['token']) && isset($_POST['id'])) {
    $streamLatLong  = getStream($_POST['token'], $_POST['id'], "latlng");
    $latlongArray   = $streamLatLong[0]['data'];
    $distanceArray  = $streamLatLong[1]['data'];
    $streamElev     = getStream($_POST['token'], $_POST['id'], "altitude");
    $elevationArray = $streamElev[1]['data'];

    // $streamGrade = getStream($_POST['token'], $_POST['id'], "grade_smooth");
    // $gradeArray  = $streamGrade[1]['data'];
    // foreach ($gradeArray as $el) {
    //     echo $el.', ';
    // }
}

function writeGPX($list, $name)
{
    $fp     = fopen('output/'.$name.'.gpx', 'w+');
    $header = '<?xml version="1.0" encoding="UTF-8"?>
    <gpx creator="Julian" version="1.0" xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" >';
    fwrite($fp, $header);
    foreach ($list as $point) {

        $string = '<wpt lat="'.$point[0][0].'" lon="'.$point[0][1].'">
        <ele>'.$point[1].'</ele>
        <name>'.$point[1].': '.$point[2].'</name>
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
    $fp = fopen('output/'.$name . '.csv', 'w+');
    foreach ($list as $line) {
        fputcsv($fp, $line);
    }
    fclose($fp);
}

function writeOutput($array, $name)
{
    $str = '';
    foreach ($array as $set) {
        $str.= $set[0] . ',' . $set[1] . ',' . $set[2] . '|';
    }
    $str = substr($str, 0, -1);
    echo 'output string: '.$str;
    $fp     = fopen('output/'.$name.'.xml', 'w+');
    fwrite($fp, $str);
    fclose($fp);
   
}

function cleanGoogleElevation($elevArray, $distArray) {
    $elevResponse = array();
    $distResponse = array();
    $distThreshold = 10;
    $sum = 0;
    $lastDist = $distArray[0];
    for($i = 1; $i < count($distArray); $i++) {
        $dist = $distArray[$i] - $lastDist;
        $sum+= $dist;
        if($sum >= $distThreshold) {
            array_push($elevResponse, $elevArray[$i]);
            array_push($distResponse, $distArray[$i]);
            $sum = 0;
        }

        $lastDist = $distArray[$i];
    }
    array_push($elevResponse, $elevArray[count($elevArray)-1]);
    array_push($distResponse, $distArray[count($distArray)-1]);
    echo 'remove measure points that are closer than 10 m distance, count before: ' .count($distArray). ' count after: '.count($distResponse).' <br>';
    return [$elevResponse, $distResponse];
}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
    <p>Extrema data:</p>
    <?php
    ### get google elevation data
    $googleClient = new GoogleElevationClient();
    foreach ($latlongArray as $coord) {
        $googleClient->addCoordinate($coord[0], $coord[1]);
    }
    $response = $googleClient->fetchJSON();
    // echo count($response) . ' ' . count($latlongArray);
    ###

    ### clean google elevation data
    $cleanedData = cleanGoogleElevation($response, $distanceArray);
    ###

    ### write data in CSV and GPX files
    $index = 0;
    $diffList = array();
    $googlePoints = array();
    array_push($diffList, array('strava', 'google'));
    
    foreach ($response as $obj) {
        array_push($diffList, array($elevationArray[$index], $obj->elevation));
        array_push($googlePoints, array(array($obj->location->lat, $obj->location->lng), $obj->elevation, $distanceArray[$index]));
        
        $index++;
    }

    $cleanList = array();
    $rdpList = array();
    $i = 0;
    foreach ($cleanedData[0] as $obj) {
        array_push($cleanList, array($obj->elevation, $cleanedData[1][$i]));
        array_push($rdpList, array($cleanedData[1][$i], $obj));
        $i++;
    }
    writeCsv($cleanList, 'cleanedData');
    writeCsv($diffList, 'stravaGoogleElevation');
    writeGPX($googlePoints, 'googlePoints');
    ###

    ### apply RDP algo
    $rdpResult = RDP::RamerDouglasPeucker2d($rdpList, 2.5);
    echo 'apply RDP algo. count: '.count($rdpResult) . '<br>';
    ###
    
    ### compute extreme points
    $wayPointsArray = array_column($rdpResult, 1);
    $distanceArray = array_column($rdpResult, 0);
    $gpxWP = array();
    $gradientTreshold = 2.5;
    $lastWayPoint = $response[0];
    $lastWPDist = $distanceArray[0];
    $state = 'null';
    $lastState = 'null';

    $lastExtremePoint = $response[0];
    $lastExtremeDist = 0;
    $outXPoints;
    $pythonOut;
    $pythonOut[] = array($lastWPDist , $lastWayPoint->elevation);
    for($i = 1; $i < count($wayPointsArray); $i++) {
        $currentWayPoint = $wayPointsArray[$i];
        $distBetween = $distanceArray[$i] - $lastWPDist;
        if($distBetween > 0) {
            $gradient = round(($currentWayPoint->elevation - $lastWayPoint->elevation) / $distBetween * 100, 4);
        } else {
            $gradient = 0;
        }

        if($gradient < 20 || $gradient > -20) {  
            if($gradient > $gradientTreshold && $state != 'up') {
                $state = 'up';
                // echo $state . ' ';

            } elseif ($gradient < -$gradientTreshold && $state != 'down') {
                $state = 'down';
                // echo $state. ' ';
                
            } elseif ($gradient >= -$gradientTreshold && $gradient <= $gradientTreshold && $state != 'flat') {
                $state = 'flat';
                // echo $state. ' ';
                
            }
            if($state != $lastState && $lastState != 'null') {
                
                $xtremGrad = round(($lastWayPoint->elevation - $lastExtremePoint->elevation)/($lastWPDist - $lastExtremeDist)  * 100, 2);
                array_push($gpxWP, array(array($lastExtremePoint->location->lat, $lastExtremePoint->location->lng), $lastExtremePoint->elevation, $lastState.' '.$lastExtremeDist. ' '.$xtremGrad));
                $outXPoints[] = array($lastWPDist , round(($lastWPDist - $lastExtremeDist), 0), $xtremGrad);
                $pythonOut[] = array($lastWPDist , $lastWayPoint->elevation);
                $lastExtremeDist = $lastWPDist;
                $lastExtremePoint = $lastWayPoint;
                $lastState = $state;
            } else {
                $lastState = $state;
            }
        }
        $lastWayPoint = $currentWayPoint;
        $lastWPDist = $distanceArray[$i];

    }
    // $pythonOut[] = array($distanceArray[$i-1] , $lastWayPoint->elevation);
    echo 'resulted extrema points: '.count($gpxWP). '<br>';
    writeGPX($gpxWP, 'googleGPX');
    writeOutput($outXPoints, 'outputXPoints');
    writeCsv($pythonOut, 'elevProfile');
    ###

?>

    </div>
    </body>
</html>
