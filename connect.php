
<!DOCTYPE html>
<html>
    <head>
    	<meta charset="utf-8">
		<link rel="stylesheet" href="style.css" type='text/css'>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    </head>
    <body>
	    <div id="main">

	  		
  			
  			<h1>Zugriff auf Strava Trainingsdaten gewähren</h1>
			<div id="german"><p>Durch das Verbinden der App mit deinem Strava-Account bist du damit einverstanden, dass deine Trainingsdaten anonym f&uuml;r wissenschaftliche Zwecke in Form einer Masterarbeit an der Universit&auml;t des Saarlandes und des Deutschen Forschungsinstitut f&uuml;r K&uuml;nstliche Intelligenz (DFKI) verwendet werden d&uuml;rfen. Die Erlaubnis kann jederzeit in den Einstellungen des Strava-Accounts widerrufen werden.</p>
			
			<!-- <p>Bitte E-Mail Adresse des Strava-Account eingeben und Zugriff auf die Trainingsdaten gew&auml;hren</p> -->
			</div>
			
			<form class="form" action="strava_callback.php" method="post">
				<?php 
					echo '<input type="hidden" name="email" value="'.$_POST['email'].'">';
					echo '<input type="hidden" name="q1" value="'.$_POST['q1'].'">';
					echo '<input type="hidden" name="q2" value="'.$_POST['q2'].'">';
					echo '<input type="hidden" name="q3" value="'.$_POST['q3'].'">';
				?>
				
				<input type="submit" value="Zugriff gewähren">
			</form>

			

		</div>
	
    </body>
</html>
