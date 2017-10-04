<?php
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';
// session_start();

if (isset($_POST['token']) && isset($_POST['id'])) {
    $activtityId = $_POST['id'];
    $app->createStravaApi($_POST['token']);
    $api = $app->getApi();
    $stravaActivity = $api->getActivty($activtityId);
    $rawStream = $api->getStream($activtityId, "distance,altitude,latlng,time");

    
    
    $athleteName = $_POST['name'];
    // chmod("output/".$athleteName, 0777);
    
    if(!is_dir("output/".$athleteName)) {
        mkdir("output/".$athleteName, 0777);
    }
    $fileWriter = new FileWriter($athleteName);

    

}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
    
    <?php

    $activity = new Activity($activtityId, $stravaActivity, $rawStream);
    echo '<p>Segment computation for activity: "'.$activity->name.'"</p>';

  
    $activity->determineSurface();
    echo 'Surface: '.$activity->surface[0] . '<br>';
    
    
    ## Segments ##

    $activity->findSegments();

    echo 'First segment: ';
    $activity->segments[0]->toString();
    echo 'Last segment: ';
    $activity->segments[count($activity->segments)-1]->toString();
   

    echo '<br><br>';

    ### elevation gain
    $activity->calculateElevationGain();
    echo 'strava elevation gain: '.$stravaActivity['total_elevation_gain'].'<br>';
    echo 'google elevation+ : ' . $activity->elevationGain . ' , elevation- :' . $activity->elevationLoss.'<br>';
    ###

    ### VO2max
    $standardVo2max = Vo2Max::vo2max($activity->distance, $activity->elapsedTime);
    echo 'standard VO2max: '.$standardVo2max.'<br>';
    $activity->vo2max = Vo2Max::vo2maxWithElevation($activity->distance, $activity->elapsedTime, $activity->elevationGain, $activity->elevationLoss);
    echo 'VO2max with elevation: '.$activity->vo2max.'<br>';
    ### 

    ### climbs
    $activity->computePercentageHilly();
    echo 'percentage hilly: '.$activity->percentageHilly.'<br>';
    // compute climbs
    $activity->findClimbs();
    echo 'Number climbs: '.count($activity->climbs).'<br>';
    

    // vertical speed
    $i = 1;
    foreach ($activity->climbs as $climb) {
        echo 'climb '.$i.': VAM '.$climb->getVerticalSpeed().' m/h, gradient '.$climb->gradient.' %, length '.$climb->length.' m, fiets '.$climb->fietsIndex.' <br>';
        $i++;
    }

    // climb score
    $activity->calculateClimbScore();
    echo 'Climb score: '.$activity->climbScore.'<br>';
    ###

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

    ?>


    </div>
    </body>
</html>

