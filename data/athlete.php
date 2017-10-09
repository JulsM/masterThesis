<?php
include_once '../database.php';
include_once '../StravaApiClient.php';
include_once 'App.php';
include_once 'Autoloader.php';
// include_once 'Config.php';

// session_start();
if (isset($_POST['id'])) {
    $db     = Db::getInstance();
    $conn   = $db->getConnection();
    $result = $db->query('SELECT token FROM users WHERE id =' . $_POST['id']);
    if (!empty($result)) {
        $token = $result[0]['token'];
        // $_SESSION['token'] = $token;
        $app->createStravaApi($token);
        $api = $app->getApi();
        
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

    $stravaAthlete = $api->getAthlete($_POST['id']);
    $athlete = new Athlete($stravaAthlete);

    $dateInPast = strtotime('-20 weeks');
    // echo date('Y-m-d', $dateInPast);
    $stravaActivities = $api->getActivties($dateInPast);
    // echo count($stravaActivities);

    $athlete->calculateAveragePaces($stravaActivities);

    $athlete->printAthlete();



    ### list activities
    echo '<br><br>';
    echo 'All activities of '.$athlete->name.':<br>';

	$num = 1;
	foreach ($stravaActivities as $ac) {


	    echo '<div>'.$num.'. ' . $ac['name'] . ', distance: ' . $ac['distance'] / 1000 .' km, workout type: '.$ac['workout_type'];
        // if(array_key_exists('device_name', $ac)) {
        //     echo ' device: '.  $ac['device_name'];
        // }
        echo '<form action="activity.php" method="post">
                <input type="hidden" name="id" value="'.$ac['id'].'">
                <input type="hidden" name="token" value="'.$token.'">
                <input type="hidden" name="name" value="'.$_POST['name'].'">
                <input type="submit" value="Process">
            </form>'.' <br><br> </div>';
	    $num++;
	}
	?>

	</div>
    </body>
</html>
