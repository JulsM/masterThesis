<?php
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';
include_once '../database.php';
// session_start();

if (isset($_GET['strava_id'])) {
    $db = Db::getInstance();
    
    $result = $db->query('SELECT * FROM activity WHERE strava_id = '.$_GET['strava_id']);
    if(!empty($result)) {
        $activity = new Activity($result[0], 'db');
    }

    if(isset($_GET['athlete'])) {
        $athleteName = $_GET['athlete'];

    }

    if($activity != null && isset($_GET['update']) && $_GET['update'] == true) {
        $activity->determineSplitType();
        // $activity->determineActivityType();
        $activity->findSegments();
        $activity->determineSurface();
        $activity->calculateElevationGain();
        $activity->calculateVo2max();
        $activity->computePercentageHilly();
        $activity->findClimbs();
        $activity->calculateClimbScore();
        $db->updateActivity($activity);
    } else if($activity != null && isset($_GET['writeData']) && $_GET['writeData'] == true) {
        
        
        // chmod("output/".$athleteName, 0777);
        if(!is_dir("output/".$athleteName)) {
            mkdir("output/".$athleteName, 0777);
        }
        
        $fileWriter = new FileWriter($athleteName);
        $fileWriter->writeControlData($activity->rawDataPoints, 'original');
        SegmentFinder::$writeFiles = true;
        $activity->findSegments();
        $fileWriter->writeControlData($activity->climbs, 'climbs');
        ### write output string
        $fileWriter->writeOutput($activity->segments);
        ###
        
    }
    
    
    
    
    

}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
    
    <?php

    
    

    $activity->printActivity();

    echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="strava_id" value="'.$activity->id.'">
                <input type="hidden" name="update" value="true">
                <input type="hidden" name="athlete" value="'.$athleteName.'">
                <input type="submit" value="Update activity">
            </form>';

    echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="strava_id" value="'.$activity->id.'">
                <input type="hidden" name="writeData" value="true">
                <input type="hidden" name="athlete" value="'.$athleteName.'">
                <input type="submit" value="Write chart data">
            </form>';

    ?>


    </div>
    </body>
</html>

