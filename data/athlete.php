<?php
include '../database.php';
include '../queryApi.php';

if (isset($_POST['id'])) {
    $db     = Db::getInstance();
    $conn   = $db->getConnection();
    $result = $db->query('SELECT token FROM users WHERE id =' . $_POST['id']);
    if (!empty($result)) {
        $token         = $result[0]['token'];
        $activityArray = queryActivties($token, $_POST['name']);
    }
}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
	<p>All activities of <?php echo $_POST['name']?>:</p>
	<?php
		$num = 1;

		foreach ($activityArray as $ac) {
		    echo '<div>'.$num.'. ' . $ac['name'] . ', distance: ' . $ac['distance'] / 1000 .' km, elapsed time: ' . round($ac['time'] / 60, 2 ). ' min, pace: ' . round($ac['average_speed'], 2 ). ' m/min, elevation gain: ' . $ac['elevation'] . ' m, VO2max: '. round($ac['vo2max'], 2) . ' workout type: '.$ac['workout_type'] . ' device: '.  $ac['device'];
            echo '<form action="activity.php" method="post">
                    <input type="hidden" name="id" value="'.$ac['id'].'">
                    <input type="hidden" name="token" value="'.$token.'">
                    <input type="hidden" name="name" value="'.$_POST['name'].'">
                    <input type="submit" value="Compute string">
                </form>'.' <br><br> </div>';
		    $num++;
		}
	?>

	</div>
    </body>
</html>
