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
    $fileWriter->writeRaceFeatures($athlete->activities, $athlete);
} else if (isset($_GET['train_features']) && isset($athlete)) {
	echo 'train features';
	$fileWriter = new FileWriter($athlete->name);
	$fileWriter->writeTrainFeatures($athlete->activities, $athlete);
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
    $fileWriter->writeAllActivitiesFeatures($athlete->activities, $athlete);
} else if (isset($_GET['complete_set'])) {
    
    echo 'complete set';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeActivitySetFeatures($athlete->id);
} else if (isset($_GET['segments'])) {
    echo 'segment features';
    // $athlete->activities = null;
    // $athlete->activities = Activity::loadActivitiesDb($athlete->id, false);
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeSegmentFeatures($athlete->activities);
} else if (isset($_GET['prediction'])) {
    echo 'prediction data';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writePredictionData($athlete);
} else if (isset($_GET['study'])) {
    echo 'study data';
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeStudyFiles($athlete->activities, $athlete);
} else if (isset($_GET['athletes'])) {
    echo 'athletes data';
    // $names = "'Julian Maurer','Florian Daiber','Joachim Groß','Kerstin de Vries','Tom Holzweg','Thomas Buyse 2090944408008','Torsten Kohlwey','Markus Pfarrkircher','Alexander Lüdemann','DI RK','Yen Mertens 2090951579081','David Chow 2090380755056','Poekie .  * 2090532345791','Benedikt Schilling','Falk Hofmann','Yvonne Dauwalder 🇨🇭','Heiko Ⓥ G.','Donato Lattarulo','Alexander Probst','Marcel Großer','Rebecca Buckingham','Simon Weig','Robert Kühne','Kevin Grimwood','Torsten Baldes','Julia Habitzreither','Alexander Weidenhaupt','Timo Maurer','Kevin Klawitter'";
    $names = "'Julian Maurer','Florian Daiber','Lauflinchen RM','Kai K.','Martin  Mühlhan ','Monika Paul','Chris WA','Kai Detemple','Alexander Zeiner', 'Martin B.', 'Peter Petto', 'Conny Ziegler'";
    $athleteResult = $db->query('SELECT * FROM athlete WHERE name IN ('.$names.')');
    $athletes = [];
    for($i = 0; $i < count($athleteResult); $i++) { // athlete in database
        $athletes[] = new Athlete($athleteResult[$i], 'db');
    }
    $fileWriter = new FileWriter($athlete->name);
    $fileWriter->writeAthletes($athletes);
} else if(isset($_GET['evaluation'])) {
    $db = Db::getInstance();
    $names = array('Florian Daiber','Fred Wiehr','Julian Maurer','Markus Pfarrkircher','Yen Mertens 2090951579081','David Chow 2090380755056','Torsten Kohlwey','Josefine Rampendahl','Luis Ramírez','Thomas Buyse 2090944408008','Poekie .  * 2090532345791','Benedikt Schilling','linkser_m Stefan','Falk Hofmann','Yvonne Dauwalder 🇨🇭','Inka Liloleinchen 🇩🇪','Ingo Himmeldirk','Heiko Ⓥ G.','Donato Lattarulo','Marcel Großer','Rebecca Buckingham','Simon Weig','Robert Kühne','Torsten Baldes','Julia Habitzreither','Fabian Kattlun','André Romão','Tom Holzweg','Enrico Pabst','Alexander Weidenhaupt','Robin Siegert','Kristin Stiller','Timo Maurer','Johannes Licht','Gergely Steinbach','Howard Sparks','Richard Cockbain','Flavio Velame','John Loke','Max Irle','Remy Sijbom','David Straßenmeyer','Heiko Idler','Alexander Lüdemann','Fabian Kuhn','Oliver Tausend','Kerstin de Vries','Matthieu Dubois','Jörg Gutowski','DI RK','Nici B','Kevin Klawitter','Joachim Groß','Michał Lubecki','Erik Schoob','Kai K.','Martin  Mühlhan ','Chris WA', 'Kai Detemple', 'Alexander Zeiner','Peter Petto', 'Conny Ziegler','Martin B.');

    foreach ($names as $name) {        
        $athleteResult = $db->query("SELECT * FROM athlete WHERE name like '".$name."'");
        if(!empty($athleteResult)) { // athlete in database
            $athlete = new Athlete($athleteResult[0], 'db');
            $athlete->activities = Activity::loadActivitiesDb($athlete->id);
            // $athlete->updateAthlete();
            $fileWriter = new FileWriter($athlete->name);
            $fileWriter->writeTrainFeatures($athlete->activities, $athlete);
            $fileWriter->writeSegmentFeatures($athlete->activities);
        }
    }
}



// echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
//                 <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
//                 <input type="hidden" name="race_features" value="true">
//                 <input type="submit" value="Write race features">
//             </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="hidden" name="train_features" value="true">
                <input type="submit" value="Write train features">
            </form>';

echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="segments" value="true">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="submit" value="Write segment features">
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
// echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
//                 <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
//                 <input type="hidden" name="all_activities" value="true">
//                 <input type="submit" value="Write all activities">
//             </form>';

// echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
//                 <input type="hidden" name="complete_set" value="true">
//                 <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
//                 <input type="submit" value="Write complete activity set">
//             </form>';



// echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
//                 <input type="hidden" name="prediction" value="true">
//                 <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
//                 <input type="submit" value="Write prediction data">
//             </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="athletes" value="true">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="submit" value="Write athletes data">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
                <input type="hidden" name="study" value="true">
                <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
                <input type="submit" value="Write study data">
            </form>';
echo '<form action="'.$_SERVER["PHP_SELF"].'" method="get">
            <input type="hidden" name="athlete_id" value="'.$athlete->id.'">
            <input type="hidden" name="evaluation" value="true">
            <input type="submit" value="Write evaluation">
        </form>';
?>
</div>
</body>
</html>