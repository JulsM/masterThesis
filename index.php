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

	  		<?php 
	  		if(!$redirect) {
	  			?>
	  			<div id="language"><span onClick="document.getElementById('english').style.display = 'none';document.getElementById('german').style.display = 'block';">German</span> <span onClick="document.getElementById('german').style.display = 'none';document.getElementById('english').style.display = 'block';">English</span></div>
	  			<h1>Pacing-Strategy Predictor</h1>
				<div id="german"><p>Durch das Verbinden der App mit deinem Strava-Account bist du damit einverstanden, dass deine Trainingsdaten anonym f&uuml;r wissenschaftliche Zwecke in Form einer Masterarbeit an der Universit&auml;t des Saarlandes und des Deutschen Forschungsinstitut f&uuml;r K&uuml;nstliche Intelligenz (DFKI) verwendet werden d&uuml;rfen. Die Erlaubnis kann jederzeit in den Einstellungen des Strava-Accounts widerrufen werden.</p>
				<p>Bitte E-Mail Adresse des Strava-Account eingeben und Zugriff auf die Trainingsdaten gew&auml;hren</p></div>
				<div id="english"><p>With connecting your Strava account with the app you agree that your training data can get used for scientific purposes in the scope of a master thesis at the "Saarland University" and the "German Research Center for Artificial Intelligence" (DFKI). You can always undo the authorization in the settings of your Strava account.</p>
				<p>Please enter your email address and grant access to your training data</p></div>
				<form id="formMail" action="data/strava_callback.php" method="post">
					<input type="email" name="email" placeholder="Email">
					<input type="submit" value="Connect">
				</form>

			<?php } else { ?>
				<div id="language"><span onClick="document.getElementById('english').style.display = 'none';document.getElementById('german').style.display = 'block';">German</span> <span onClick="document.getElementById('german').style.display = 'none';document.getElementById('english').style.display = 'block';">English</span></div>
	  			<h1>Pacing-Strategy Predictor</h1>
				<p id="german">Account wurde erfolgreich verbunden. Vielen Dank!</p>
				<p id="english">Account successfully connected. Thank you very much!</p>
				<form id="formMail" action="data/strava_callback.php" method="post">
					<input type="email" name="email" placeholder="E-Mail">
					<input type="submit" value="Connect">
				</form>
			<?php }
			?>

		</div>
	
    </body>
</html>
