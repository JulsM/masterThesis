<?php
include_once '../StravaApiClient.php';
include_once 'activityProcessing.php';
include_once 'App.php';
include_once 'vo2max.php';
include_once 'Configuration.php';
// session_start();

if (isset($_POST['token']) && isset($_POST['id'])) {
    $actitityId = $_POST['id'];
    $app->createStravaApi($_POST['token']);
    $api = $app->getApi();
    $activity = $api->getActivty($actitityId);
    $stream = $api->getStream($actitityId, "distance,altitude,latlng,time");
    
    $athleteName = $_POST['name'];
    // chmod("output/".$athleteName, 0777);
    
    if(!is_dir("output/".$athleteName)) {
        mkdir("output/".$athleteName, 0777);
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
    
    <?php
    echo '<p>Segment computation for activity: "'.$activity['name'].'"</p>';

    $dataPoints = generateDataPoints($stream);

    echo 'First DataPoint: ';
    $dataPoints[0]->toString();
    echo 'Last DataPoint: ';
    $dataPoints[count($dataPoints)-1]->toString();

    ### clean data points
    $dataPoints = cleanDataPoints($dataPoints);
    ###
    
    ### write data in CSV 
    writeControlData($dataPoints, $athleteName, 'original');
    ###

    ### apply RDP algo
    $dataPoints = RDP::RamerDouglasPeucker2d($dataPoints, Config::$epsilonRPD);
    echo 'apply RDP: '.count($dataPoints).'<br>';
    ###

    ### write data in CSV 
    writeControlData($dataPoints, $athleteName, 'rdp');
    ###


    ### compute segments
    $segments = computeSegments($dataPoints, Config::$lowGradientThreshold, Config::$highGradientThreshold);
    echo 'Compute segments: '.count($segments).'<br>';
    writeControlData($segments, $athleteName, 'segments');
    ###

    

    ### filter segments
    $segments = filterSegments($segments);
    writeControlData($segments, $athleteName, 'filtered');
    echo 'Filter segments: '.count($segments).'<br>';    

    
    ### recompute segments
    $segments = recomputeSegments($segments, Config::$lowGradientThresholdRecomp, Config::$highGradientThresholdRecomp);
    writeControlData($segments, $athleteName, 'recompute');
    echo 'Recompute segments: '.count($segments).'<br>';
    ###


    echo 'First segment: ';
    $segments[0]->toString();
    echo 'Last segment: ';
    $segments[count($segments)-1]->toString();
   


    // writeGPX($recompSegments, $athleteName.'/segmentsGPX', $distanceArray, $latlongArray);

    ### write output string
    writeOutput($segments, $athleteName.'/outputString');
    ###

    echo '<br><br>';

    ### elevation gain
    $elevationGain = calculateElevationGain($segments);
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
    $percentageHilly = getPercentageHilly($segments, $activity['distance']);
    echo 'percentage hilly: '.$percentageHilly.'<br>';
    // compute climbs
    $climbs = computeClimbs($segments);
    writeControlData($climbs, $athleteName, 'climbs');
    echo 'Number climbs: '.count($climbs).'<br>';
    
    // climb score
    $score = calculateClimbScore($climbs, $activity['distance'], 1.0 - $percentageHilly);
    echo 'climb score: '.$score.'<br>';
    ###

    // vertical speed
    echo 'Vertical speed (VAM):';
    $i = 1;
    foreach ($climbs as $climb) {
        echo 'climb '.$i.': '.$climb->getVerticalSpeed().', ';
        $i++;
    }
    echo '<br>';

    ?>


    </div>
    </body>
</html>
