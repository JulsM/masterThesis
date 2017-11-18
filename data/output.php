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
if (isset($_GET['train_features']) && isset($athlete)) {
	echo 'train features';
	$fileWriter = new FileWriter($athlete->name);
	$fileWriter->writeTrainFeatures($athlete->activities);
} else if (isset($_GET['tsb_model']) && isset($athlete)) {
    echo 'tsb model';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeTsbModel($athlete->activities);
} else if (isset($_GET['k_means']) && isset($athlete)) {
    echo 'k-means';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writekmeans($athlete->activities);
} else if (isset($_GET['all_activities']) && isset($athlete)) {
    echo 'all activities';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeAllActivitiesFeatures($athlete->activities);
} else if (isset($_GET['complete_set'])) {
    echo 'complete set';
    $db = Db::getInstance();
    $result = $db->query('SELECT * FROM activity');
    $activities = [];
    if(!empty($result)) {
        foreach ($result as $ac) {
            $activity = new Activity($ac, 'db');
            $activities[] = $activity;
        }
    }
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeActivitySetRelation($athlete->activities);
}




echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="train_features" value="true">
                <input type="submit" value="Write train features">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="tsb_model" value="true">
                <input type="submit" value="Write tsb model">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="k_means" value="true">
                <input type="submit" value="Write k-means">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="all_activities" value="true">
                <input type="submit" value="Write all activities">
            </form>';

echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="complete_set" value="true">
                <input type="submit" value="Write complete activitie set">
            </form>';

?>
</div>
</body>
</html>