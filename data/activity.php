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
    $rawStream = $api->getStream($activtityId, "distance,altitude,latlng,time,velocity_smooth");

    
    
    $athleteName = $_POST['name'];
    // chmod("output/".$athleteName, 0777);
    
    if(!is_dir("output/".$athleteName)) {
        mkdir("output/".$athleteName, 0777);
    }
    $fileWriter = new FileWriter($athleteName);
    // $fileWriter->lock();
    

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
    
    ## Segments ##

    echo '<p>Segment computation for activity: "'.$activity->name.'"</p>';

    $activity->findSegments();

    echo '<br><br>';

    ### surface
    // $activity->determineSurface();
    ###

    ### elevation gain
    $activity->calculateElevationGain();
    ###

    ### VO2max
    $activity->calculateVo2max();
    ### 

    ### Hilly
    $activity->computePercentageHilly();
    ###
    
    ### climbs
    $activity->findClimbs();
    ###
    
    ### climb score
    $activity->calculateClimbScore();
    ###

    $activity->printActivity();

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

