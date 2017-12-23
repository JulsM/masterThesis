<?php
	$redirect = false;
	if(isset($_GET['redirect'])) {
		$redirect = true;
	}
	
?>
<!DOCTYPE html>
<html>
    <head>
    <meta charset="utf-8">
		<link rel="stylesheet" href="style.css" type='text/css'>
    </head>
    <body>
	    <div id="main">

	  		<?php 
	  		if(!$redirect) {
	  			?>
	  			<!-- <div id="language"><span onClick="document.getElementById('english').style.display = 'none';document.getElementById('german').style.display = 'block';">German</span> <span onClick="document.getElementById('german').style.display = 'none';document.getElementById('english').style.display = 'block';">English</span></div> -->
	  			<h1>Race Time Predictor</h1>
				<div id="german">
				<p>Mit meiner Masterarbeit am Deutschen Forschungszentrum für Künstliche Intelligenz (DFKI) und der Universität des Saarlandes habe ich es mir zur Aufgabe gemacht aufgrund der Analyse von historischen Trainingsdaten (GPS-Tracks aller Aktivitäten + Höhenprofil) die Endzeit eines Rennens vorherzusagen. Ziel der Arbeit ist die Verbesserung der Endzeitvorhersage bestehender Dienste, die in der Regel keine oder wenig Information über die Laufhistorie haben und keine oder nur vereinfachte Höhenprofile berücksichtigen. Als Datenquelle dient Strava, da hier Hersteller übergreifend Athleten ihre Daten hochladen.</p>
				<p><strong>Wichtig:</strong> Keine Passwörter oder ähnliche sensible Daten werden gespeichert. Generell werden die Ergebnisse der Studie ausschließlich in anonymisierter und aggregierter Form publiziert, die keinerlei Rückschlüsse auf einzelne Individuen zulassen. Personenbezogene Daten werden nach Abschluss der Studie gelöscht. Der Nutzung der Daten kann mit einer Mail an julian.maurer@dfki.de widersprochen werden.</p>
				<p>Bitte E-Mail Adresse des Strava-Account eingeben und Zugriff auf die Trainingsdaten gew&auml;hren</p></div>
				<!-- <div id="english"><p>With connecting your Strava account with the app you agree that your training data can get used for scientific purposes in the scope of a master thesis at the "Saarland University" and the "German Research Center for Artificial Intelligence" (DFKI). You can always undo the authorization in the settings of your Strava account.</p> -->
				<!-- <p>Please enter your email address and grant access to your training data</p></div> -->
				<form id="questions" action="connect.php" method="post">
					<input type="email" name="email" placeholder="Email">
					<input type="submit" value="Connect">
				</form>

			<?php } else { ?>
				<!-- <div id="language"><span onClick="document.getElementById('english').style.display = 'none';document.getElementById('german').style.display = 'block';">German</span> <span onClick="document.getElementById('german').style.display = 'none';document.getElementById('english').style.display = 'block';">English</span></div> -->
	  			<h1>Race Time Predictor</h1>
				<p id="german">Account wurde erfolgreich verbunden. Die Teilnahme an der Studie ist abgeschlossen. Vielen Dank!</p>
				<!-- <p id="english">Account successfully connected. Thank you very much!</p> -->
				<form id="formMail" action="strava_callback.php" method="post">
					<input type="email" name="email" placeholder="E-Mail">
					<input type="submit" value="Connect">
				</form>
			<?php }
			?>

		</div>
	
    </body>
</html>
