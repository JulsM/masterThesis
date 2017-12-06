<!DOCTYPE html>
<html>
    
    
<?php
include_once '../database.php';
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';


// echo (memory_get_peak_usage(true)/1024/1024) . "\n";


if (isset($_GET['strava_id'])) {
    $db = Db::getInstance();
    $athleteResult = $db->query('SELECT * FROM athlete WHERE strava_id =' . $_GET['strava_id']);

    if(!empty($athleteResult)) { // athlete in database

        $athlete = new Athlete($athleteResult[0], 'db');
        if(isset($_GET['load']) && $_GET['load'] == true) { // download new activities from strava
            $numNewActivities = Activity::downloadNewActivities($athlete->id, $athlete->token);

            if($numNewActivities == 0) {
                $_GET['load'] = false;
                echo 'no more new activities';
            }
        }

        
        if(!isset($_GET['surface'])) {
            $athlete->activities = Activity::loadActivitiesDb($athlete->id); // load available activities from database
        }

        if(isset($_GET['load']) && $_GET['load'] == true) {
            $athlete->updateAthlete();
        }

        if(isset($_GET['update']) && $_GET['update'] == true) { // update athlete in database
            
            echo 'update';
            $athlete->updateAthlete();
        } else if(isset($_GET['surface']) && $_GET['surface'] == true) { // update surfaces in db
            
            echo ' surface update';
            $query = "SELECT * FROM activity WHERE athlete_id =" . $athlete->id." AND (surface = '' OR surface IS NULL) LIMIT 3";
            $result = $db->query($query);
            $updateActivities = [];
            if(!empty($result)) {
                foreach ($result as $ac) {
                    $activity = new Activity($ac, 'db', null, false);
                    $updateActivities[] = $activity;
                }
            } else {
                echo ' no more surface empty ';
            }
            for($i = 0; $i < count($updateActivities); $i++) {
                $ac = $updateActivities[$i];
                if($ac->surface == null) {
                    $ac->determineSurface();
                    $db->updateActivity($ac);
                }
            }
            $athlete->activities = Activity::loadActivitiesDb($athlete->id); // load available activities from database
            
        } else if(isset($_GET['delete_activities']) && $_GET['delete_activities'] == true) { // delete all activities from db
            $db->deleteActivities($athlete->id);
            $athlete->activities = array();
        }

    } else { // athlete from strava
        $result = $db->query('SELECT token FROM users WHERE strava_id =' . $_GET['strava_id']);
        if (!empty($result)) {
            $token = $result[0]['token'];
            $app->createStravaApi($token);
            $api = $app->getApi();
            $stravaAthlete = $api->getAthlete($_GET['strava_id']);
            $athlete = new Athlete($stravaAthlete, 'strava', $token);
            $db->saveAthlete($athlete);
        }
    }
}
?>
<head>
<meta charset="utf-8">
<?php 
if(isset($_GET['load']) && $_GET['load'] == true) {
    $url = $_SERVER['PHP_SELF'].'?strava_id='.$_GET['strava_id'].'&load=true';
    echo '<meta http-equiv="refresh" content="1; URL='.$url.'">';
} else if(isset($_GET['load']) && $_GET['load'] == false) {
    $url = $_SERVER['PHP_SELF'].'?strava_id='.$_GET['strava_id'];
    echo '<meta http-equiv="refresh" content="1; URL='.$url.'">';
}
?>
</head>
<body>
<div style="width: 80%; height: 80%; margin: 5% auto">
<?php
// echo (memory_get_usage()/1024/1024) . "\n";

    $athlete->printAthlete();

    // $athlete->updateAllActivities();

    echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="strava_id" value="'.$athlete->id.'">
                <input type="hidden" name="update" value="true">
                <input type="submit" value="Update athlete">
            </form>';

    echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="strava_id" value="'.$athlete->id.'">
                <input type="hidden" name="load" value="true">
                <input type="submit" value="Download new activities">
            </form>';

    echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="strava_id" value="'.$athlete->id.'">
                <input type="hidden" name="surface" value="true">
                <input type="submit" value="Update surface">
            </form>';

    echo '<form action="output.php" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="submit" value="Output page">
            </form>';

    // echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
    //             <input type="hidden" name="strava_id" value="'.$athlete->id.'">
    //             <input type="hidden" name="delete_activities" value="true">
    //             <input type="submit" value="Delete all activities">
    //         </form>';
    

    ### list activities
    echo '<br><br>';
    echo 'All activities of '.$athlete->name.':<br>';

    if(count($athlete->activities) > 0) {
    	$num = 1;
    	foreach (array_reverse($athlete->activities) as $ac) {

            if($ac->activityType == 'speedwork') {
                echo '<div style="color:green">';
            } else if($ac->activityType == 'race') {
                echo '<div style="color:red">';
            } else if($ac->activityType == 'long run') {
                echo '<div style="color:#8A0808">';
            } else {
                echo '<div>';
            }
    	    echo $num.'. ' . $ac->name . ', date: '.$ac->date.', distance: ' . round($ac->distance / 1000, 2) .' km, surface: '.$ac->surface.', type: '.$ac->activityType.'</div>';
            echo '<form action="activity.php" method="get">
                    <input type="hidden" name="strava_id" value="'.$ac->id.'">
                    <input type="hidden" name="athlete" value="'.$athlete->name.'">
                    <input type="submit" value="Show">
                </form>'.' <br><br> ';
    	    $num++;
    	}
    }

    
	?>

	</div>
    </body>
</html>
