<?php
require_once '../StravaApiClient.php';
require_once 'activityProcessing.php';
require_once 'App.php';
require_once 'vo2max.php';
// session_start();

if (isset($_POST['token']) && isset($_POST['id'])) {
    $actitityId = $_POST['id'];
    $app->createStravaApi($_POST['token']);
    $api = $app->getApi();
    $activity = $api->getActivty($actitityId);
    $streamLatLong  = $api->getStream($actitityId, "latlng");
    $latlongArray   = $streamLatLong[0]['data'];
    $distanceArray  = $streamLatLong[1]['data'];
    $streamElev     = $api->getStream($actitityId, "altitude");
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

    // $gradeSmooth  = $api->getStream($actitityId, "grade_smooth");
    // $a = array();
    // for($i = 0; $i < count($gradeSmooth[0]['data']); $i++) {
    //     $a[] = array($gradeSmooth[0]['data'][$i], $gradeSmooth[1]['data'][$i]);
    // }
    // writeCsv($a, $athleteName.'/gradeSmooth');
    
    // $hr  = $api->getStream($actitityId, "heartrate");
    // $velocitySmooth  = $api->getStream($actitityId, "velocity_smooth");
    // $a = array();
    // for($i = 0; $i < count($hr[0]['data']); $i++) {
    //     $a[] = array($hr[0]['data'][$i], $hr[1]['data'][$i], $velocitySmooth[1]['data'][$i]);
    // }
    // writeCsv($a, $athleteName.'/hr_velocity');

    
    

}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
    <p>Segment computation:</p>
    <?php

    ### get google elevation data
    $googleElevation = getGoogleElevation($latlongArray);

    echo 'google data, first: distance '.$distanceArray[0].' elevation '.$googleElevation[0]->elevation.'<br>';
    echo 'google data, last: distance '.$distanceArray[count($distanceArray)-1].' elevation '.$googleElevation[count($googleElevation)-1]->elevation.'<br>';

    ### clean google elevation data
    $elevationDistanceArray = cleanGoogleElevation($googleElevation, $distanceArray);
    ###

    // echo 'clean data, first: distance '.$elevationDistanceArray[1][0].' elevation '.$elevationDistanceArray[0][0].'<br>';
    // echo 'clean data, last: distance '.$elevationDistanceArray[1][count($elevationDistanceArray[0])-1].' elevation '.$elevationDistanceArray[0][count($elevationDistanceArray[0])-1].'<br>';


    ### write data in CSV and GPX files
    writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray, $athleteName);
    ###

    ### apply RDP algo
    $rdpResult = applyRDP($elevationDistanceArray, 2.5);
    ###

    // echo 'rdp data, first: distance '.$rdpResult[0][0].' elevation '.$rdpResult[0][1].'<br>';
    // echo 'rdp data, last: distance '.$rdpResult[count($rdpResult)-1][0].' elevation '.$rdpResult[count($rdpResult)-1][1].'<br>';


    ### compute extreme points
    // computeExtrema($elevationArray, $distanceArray);
    $segments = computeSegments($rdpResult, 1.8, 4.5);
    writeCsv($segments, $athleteName.'/segments');
    ###

    // echo 'segment data, first: distance '.$segments[0][0].' elevation '.$segments[0][1].'<br>';
    // echo 'segment data, last: distance '.$segments[count($segments)-1][0].' elevation '.$segments[count($segments)-1][1].'<br>';

    ### filter segments
    $filteredSegments = filterSegments($segments);
    writeCsv($filteredSegments, $athleteName.'/filteredSegments');
    

    
    ### recompute segments
    $recompSegments = computeSegments($filteredSegments, 1, 4);
    writeCsv($recompSegments, $athleteName.'/recomputedSegments');
    ###

    echo 'gradients: ';
    for ($i = 1; $i < count($recompSegments); $i++) {
        
    $length = $recompSegments[$i][0] - $recompSegments[$i - 1][0];
    $gradient = getGradient($length, $recompSegments[$i][1] - $recompSegments[$i - 1][1]);
    echo $recompSegments[$i][0] . ' '.$gradient.', ';
        
    }

    ### get elevation gain
    // $elevArray   = array_column($recompSegments, 1);
    // $elevationGain = computeElevationGain($elevArray);
    // echo 'elevation gain: ' . $elevationGain . '<br>';
    ###

    echo 'segment data, first: distance '.$recompSegments[0][0].' elevation '.$recompSegments[0][1].'<br>';
    echo 'segment data, last: distance '.$recompSegments[count($recompSegments)-1][0].' elevation '.$recompSegments[count($recompSegments)-1][1].'<br><br>';


    writeGPX($recompSegments, $athleteName.'/segmentsGPX', $distanceArray, $latlongArray);

    ### write output string
    writeOutput($recompSegments, $athleteName.'/outputString');
    ###

    echo '<br><br>';

    ### elevation gain
    $elevationArray   = array_column($rdpResult, 1);
    $elevationGain = calculateElevationGain($elevationArray);
    echo 'strava elevation gain: '.$activity['total_elevation_gain'].'<br>';
    echo 'google elevation+ : ' . $elevationGain[0] . ' , elevation- :' . $elevationGain[1].'<br>';
    ###

    ### VO2max
    $standardVo2max = vo2max($activity['distance'], $activity['elapsed_time']);
    echo 'standard VO2max: '.$standardVo2max.'<br>';
    $vo2maxElevation = vo2maxWithElevation($activity['distance'], $activity['elapsed_time'], $elevationGain);
    echo 'VO2max with elevation: '.$vo2maxElevation.'<br>';
    ### 

    ### climbs
    // print climbs
    $climbs = computeClimbs($recompSegments);
    $list = [];
    foreach ($climbs as $climb) {
        $points = [];
        foreach ($climb as $point) {
            $points[] = $point[0];
            $points[] = $point[1];
        }
        $list[] = $points;
        
    }
    // print_r($list);
    writeCsv($list, $athleteName.'/climbs');



    ###

    ?>


    </div>
    </body>
</html>
