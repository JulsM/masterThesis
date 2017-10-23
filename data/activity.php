
<?php
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';
include_once '../database.php';
// session_start();

if (isset($_GET['strava_id'])) {
    $db = Db::getInstance();
    
    

    if(isset($_GET['athlete'])) {
        $athleteName = $_GET['athlete'];

    }

    if(!isset($_GET['update'])) {
        $result = $db->query('SELECT * FROM activity WHERE strava_id = '.$_GET['strava_id']);
        if(!empty($result)) {

            $activity = new Activity($result[0], 'db', null, false);
        }
    } else if(isset($_GET['update']) && $_GET['update'] == true) {
        $fileWriter = new FileWriter($athleteName);
        $token = $db->query('SELECT athlete.token FROM athlete, activity WHERE activity.strava_id =' . $_GET['strava_id']. 'AND activity.athlete_id = athlete.strava_id');

        if (!empty($token)) {
            $app->createStravaApi($token[0]['token']);
            $api = $app->getApi();
            $stravaActivity = $api->getActivity($_GET['strava_id']);

            $rawStream = $api->getStream($_GET['strava_id'], "distance,altitude,latlng,time,velocity_smooth");
            $activity = new Activity($stravaActivity, 'strava', $rawStream, false, true);
            // $activity->determineSurface();
            $db->updateActivity($activity);
        }
        
    } 
    if($activity != null && isset($_GET['writeData']) && $_GET['writeData'] == true) {
        
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
        <meta charset="UTF-8">
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

