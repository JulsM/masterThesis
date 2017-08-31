<?php
include '../database.php';
include '../queryApi.php';

if (isset($_POST['id'])) {
    $db     = Db::getInstance();
    $conn   = $db->getConnection();
    $result = $db->query('SELECT token FROM users WHERE id =' . $_POST['id']);
    if (!empty($result)) {
        $token         = $result[0]['token'];
        $activityArray = queryActivties($token, 20);
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


		    echo '<div>'.$num.'. ' . $ac['name'] . ', distance: ' . $ac['distance'] / 1000 .' km, elapsed time: ' . round($ac['elapsed_time'] / 60, 2 ). ' min, elevation gain: ' . $ac['total_elevation_gain'] . ' m, workout type: '.$ac['workout_type'];
            if(array_key_exists('device_name', $ac)) {
                echo ' device: '.  $ac['device_name'];
            }
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
