<?php
include_once '../database.php';
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';




if (isset($_GET['strava_id'])) {
    $db = Db::getInstance();
    $athleteResult = $db->query('SELECT * FROM athlete WHERE strava_id =' . $_GET['strava_id']);

    if(!empty($athleteResult)) { // athlete in database

        $athlete = new Athlete($athleteResult[0], 'db');
        if(isset($_GET['load']) && $_GET['load'] == true) { // download new activities from strava
            Activity::downloadNewActivities($athlete->id, $athlete->token);
        }

        $athlete->activities = Activity::loadActivitiesDb($athlete->id); // load available activities from database

        if(isset($_GET['update']) && $_GET['update'] == true) { // update athlete in database
            
            echo 'update';
            $athlete->updateAthlete();
        } else if(isset($_GET['surface']) && $_GET['surface'] == true) { // update surfaces in db
            
            echo ' surface update';
            $processed = 0;
            for($i = 0; $i < count($athlete->activities); $i++) {
                $ac = $athlete->activities[$i];
                if($ac->surface == null) {
                    $ac->determineSurface();
                    $db->updateActivity($ac);
                    $processed++;
                }
                if($processed == 3) {
                    break;
                }
            }
            if($processed == 0) {
                echo 'all surface data in db';
            }

            
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



<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
	
	<?php
    

    

    $athlete->printAthlete();

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
    

    ### list activities
    echo '<br><br>';
    echo 'All activities of '.$athlete->name.':<br>';

    if(count($athlete->activities) > 0) {
    	$num = 1;
    	foreach ($athlete->activities as $ac) {


    	    echo '<div>'.$num.'. ' . $ac->name . ', date: '.$ac->date.', distance: ' . $ac->distance / 1000 .' km, surface: '.$ac->surface;
            echo '<form action="activity.php" method="get">
                    <input type="hidden" name="strava_id" value="'.$ac->id.'">
                    <input type="hidden" name="athlete" value="'.$athlete->name.'">
                    <input type="submit" value="Show">
                </form>'.' <br><br> </div>';
    	    $num++;
    	}
    }

    
	?>

	</div>
    </body>
</html>
