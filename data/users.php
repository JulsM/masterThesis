<?php
include '../database.php';


$db     = Db::getInstance();
$conn   = $db->getConnection();
$result = $db->query('SELECT * FROM users');


?>

<!DOCTYPE html>
<html>
    <head>
    	<meta charset="utf-8">
		<link rel="stylesheet" href="../style.css" type='text/css'>
    </head>
    <body>
	    <div id="mainUsers">
	  		
			<div>
				<?php
				echo 'List of all users in database:';
				$n = 1;	
				if ($result != null) {
					foreach ($result as $row) {
					    echo '<div style="margin: 20px">'.$n.' - '.$row['name'].', '.$row['email'] . ', <a href="https://www.strava.com/athletes/'.$row['strava_id'].'">' . $row['strava_id'].'</a>';
					    echo '<form style="display: inline; margin: 10px" action="athlete.php" method="get">
					    		<input type="hidden" value="'.$row['strava_id'].'" name="strava_id">
								<input type="submit" value="Show athlete">
							</form></div>';
						$n++;
					}
				}
				?>
			</div>

		</div>
    </body>
</html>
