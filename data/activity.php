<?php
require_once '../StravaApiClient.php';
require_once 'activityProcessing.php';
require_once 'App.php';
session_start();

if (isset($_SESSION['token']) && isset($_POST['id'])) {
    $app->createStravaApi($_SESSION['token']);
    $api = $app->getApi();
    $streamLatLong  = $api->getStream($_POST['id'], "latlng");
    $latlongArray   = $streamLatLong[0]['data'];
    $distanceArray  = $streamLatLong[1]['data'];
    $streamElev     = $api->getStream($_POST['id'], "altitude");
    $stravaElevation = $streamElev[1]['data'];
    $athleteName = $_POST['name'];
    if(!is_dir("output/".$athleteName)) {
        mkdir("output/".$athleteName);
    }

    ## test route
    // $route = getRoute($_POST['token'], 10393066);
    // $latlongArray = $route[0]['data'];
    // $distanceArray = $route[1]['data'];
    // $stravaElevation = $route[2]['data'];

    $gradeSmooth  = $api->getStream($_POST['id'], "grade_smooth");
    $a = array();
    for($i = 0; $i < count($gradeSmooth[0]['data']); $i++) {
        $a[] = array($gradeSmooth[0]['data'][$i], $gradeSmooth[1]['data'][$i]);
    }
    writeCsv($a, $athleteName.'/gradeSmooth');
    
    $hr  = $api->getStream($_POST['id'], "heartrate");
    $velocitySmooth  = $api->getStream($_POST['id'], "velocity_smooth");
    $a = array();
    for($i = 0; $i < count($hr[0]['data']); $i++) {
        $a[] = array($hr[0]['data'][$i], $hr[1]['data'][$i], $velocitySmooth[1]['data'][$i]);
    }
    writeCsv($a, $athleteName.'/hr_velocity');

    
    

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
    $googleElevation = getGoogleElevation($latlongArray);

    echo 'google data, first: distance '.$distanceArray[0].' elevation '.$googleElevation[0]->elevation.'<br>';
    echo 'google data, last: distance '.$distanceArray[count($distanceArray)-1].' elevation '.$googleElevation[count($googleElevation)-1]->elevation.'<br>';

    ### clean google elevation data
    $elevationDistanceArray = cleanGoogleElevation($googleElevation, $distanceArray);
    ###

    echo 'clean data, first: distance '.$elevationDistanceArray[1][0].' elevation '.$elevationDistanceArray[0][0].'<br>';
    echo 'clean data, last: distance '.$elevationDistanceArray[1][count($elevationDistanceArray[0])-1].' elevation '.$elevationDistanceArray[0][count($elevationDistanceArray[0])-1].'<br>';


    ### write data in CSV and GPX files
    writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray, $athleteName);
    ###

    ### apply RDP algo
    $rdpResult = applyRDP($elevationDistanceArray, 2.5);
    ###

    echo 'rdp data, first: distance '.$rdpResult[0][0].' elevation '.$rdpResult[0][1].'<br>';
    echo 'rdp data, last: distance '.$rdpResult[count($rdpResult)-1][0].' elevation '.$rdpResult[count($rdpResult)-1][1].'<br>';

    ### get elevation gain
    $elevationArray   = array_column($rdpResult, 1);
    $elevationGain = computeElevationGain($elevationArray);
    echo 'elevation gain: ' . $elevationGain . '<br>';
    ###

    ### compute extreme points
    // computeExtrema($elevationArray, $distanceArray);
    $segments = computeSegments($rdpResult, 1.8, 4.5);
    writeCsv($segments, $athleteName.'/segments');
    ###

    echo 'segment data, first: distance '.$segments[0][0].' elevation '.$segments[0][1].'<br>';
    echo 'segment data, last: distance '.$segments[count($segments)-1][0].' elevation '.$segments[count($segments)-1][1].'<br>';

    ### filter segments
    $filteredSegments = filterSegments($segments);
    writeCsv($filteredSegments, $athleteName.'/filteredSegments');
    

    // for ($i = 1; $i < count($s); $i++) {
        
    //  $length = $s[$i][0] - $s[$i - 1][0];
    //  $gradient = 0;
    //  if ($length > 0) {
    //         $gradient = round(($s[$i][1] - $s[$i - 1][1]) / $length * 100, 4);
    //     }
    //  echo $s[$i][0] . ' '.$gradient.', ';
        
    // }
    ### recompute segments
    $recompSegments = computeSegments($filteredSegments, 1, 4);
    writeCsv($recompSegments, $athleteName.'/recomputedSegments');
    ###

    ### get elevation gain
    // $elevArray   = array_column($recompSegments, 1);
    // $elevationGain = computeElevationGain($elevArray);
    // echo 'elevation gain: ' . $elevationGain . '<br>';
    ###

    echo 'segment data, first: distance '.$recompSegments[0][0].' elevation '.$recompSegments[0][1].'<br>';
    echo 'segment data, last: distance '.$recompSegments[count($recompSegments)-1][0].' elevation '.$recompSegments[count($recompSegments)-1][1].'<br>';


    writeGPX($recompSegments, $athleteName.'/segmentsGPX', $distanceArray, $latlongArray);

    ### write output string
    writeOutput($recompSegments, $athleteName.'/outputString');
    ###

    ?>


    </div>
    </body>
</html>
