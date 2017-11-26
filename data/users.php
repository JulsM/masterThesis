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
				$numAll = $db->query('SELECT count(*) FROM activity')[0]['count'];
				echo 'List of all users in database: ('.$numAll.' activities in DB)';
				$n = 1;	
				if ($result != null) {
					foreach ($result as $row) {
						$count = $db->query('SELECT count(*) FROM activity WHERE athlete_id = '.$row['strava_id'])[0]['count'];
						$style='';
						if(intval($count) > 0) {
               				$style = 'color:green;';
						}

					    echo '<div style="margin: 10px; '.$style.'">'.$n.' - '.$row['name'].', '.$row['email'] . ', <a href="https://www.strava.com/athletes/'.$row['strava_id'].'">' . $row['strava_id'].'</a>';
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
