<?php
	$redirect = false;
	if(isset($_GET['redirect'])) {
		$redirect = true;
	}
	
?>
<!DOCTYPE html>
<html>
    <head>
		<link rel="stylesheet" href="style.css" type='text/css'>
    </head>
    <body>
	    <div id="main">
	  		<h1>Pacing-Strategy Predictor</h1>
	  		<?php 
	  		if(!$redirect) {
				echo 
				'<p>Durch das Verbinden der App mit deinem Strava-Account bist du damit einverstanden, dass deine Trainingsdaten anonym für wissenschaftliche Zwecke in Form einer Masterarbeit an der Universität des Saarlandes und des Deutschen Forschungsinstitut für Künstliche Intelligenz (DFKI) verwendet werden dürfen. Die Erlaubnis kann jederzeit in den Einstellungen des Strava-Accounts widerrufen werden.</p>
				<p>Bitte E-Mail Adresse des Strava-Account eingeben und Zugriff auf die Trainingsdaten gewähren</p>
				<form id="formMail" action="data/strava_callback.php" method="post">
					<input type="email" name="email" placeholder="E-Mail">
					<input type="submit" value="Verbinden">
				</form>';

			} else {
				echo 
				'<p>Account wurde erfolgreich verbunden. Vielen Dank!</p>
				<form id="formMail" action="data/strava_callback.php" method="post">
					<input type="email" name="email" placeholder="E-Mail">
					<input type="submit" value="Verbinden">
				</form>';
			}
			?>

		</div>
	
    </body>
</html>
