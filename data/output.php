<!DOCTYPE html>
<html>
	<head>
		<meta charset="UTF-8">
	</head> 
<body>
<div style="width: 80%; height: 80%; margin: 5% auto">
<?php
include_once '../database.php';
include_once 'Autoloader.php';

if (isset($_GET['athlete_id'])) {
    $db = Db::getInstance();
    $athleteResult = $db->query('SELECT * FROM athlete WHERE strava_id =' . $_GET['athlete_id']);

    if(!empty($athleteResult)) { // athlete in database

        $athlete = new Athlete($athleteResult[0], 'db');
        $athlete->activities = Activity::loadActivitiesDb($athlete->id);
    }
}
if (isset($_GET['race_features']) && isset($athlete)) {
	echo 'race features';
	$fileWriter = new FileWriter($athlete->name);
	$fileWriter->writeRaceFeatures($athlete->activities);
} else if (isset($_GET['tsb_model']) && isset($athlete)) {
    echo 'tsb model';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeTsbModel($athlete->activities);
}


echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="race_features" value="true">
                <input type="submit" value="Write race features">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="tsb_model" value="true">
                <input type="submit" value="Write tsb model">
            </form>';

?>
</div>
</body>
</html>