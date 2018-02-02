<?php
	$redirect = false;
	if(isset($_GET['redirect'])) {
		$redirect = true;
	}
	
?>
<!DOCTYPE html>
<html>
    <head>
    	<title>Race Time Prediction</title>
    	<meta property="og:title" content="Race Time Prediction - Abschluss-Studie" />
		<meta property="og:url" content="http://umtl.dfki.de/~julian/" />
		<meta property="og:image" content="http://umtl.dfki.de/~julian/spot-862274_1920.jpg">
    	<meta charset="utf-8">
		<link rel="stylesheet" href="style.css" type='text/css'>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">

		
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
				<p><strong>Wichtig:</strong> Keine Passwörter oder ähnliche sensible Daten werden gespeichert. Generell werden die Ergebnisse der Studie ausschließlich in anonymisierter und aggregierter Form publiziert, die keinerlei Rückschlüsse auf einzelne Individuen zulassen. Personenbezogene Daten werden nach Abschluss der Studie gelöscht. Der Nutzung der Daten kann mit einer Mail an julian.maurer@dfki.de widersprochen werden.</p></div>
				<div id="questions">
				<h2>3 Fragen, die auf vor dem Lauf bezogen sind</h2>
					<form id="questions" class="form" action="connect.php" method="post">
						<p>Wie ist deine Motivation für den Lauf? Was hast du dir vorgenommen an Leistung zu geben?<br> (z.B. neue Bestzeit oder lockerer Trainingslauf, wo du nur x-% gibst) *</p>
						<textarea name="q1" rows="4" cols="30"></textarea><br>
						<p>Hast du dir eine Zielzeit vorgenommen? Wenn ja, welche ist das?</p>
						<input type="text" name="q2" style="width: 50px; text-align: right;"> Minuten<br>
						<p>Hast du extra für diesen Lauf trainiert? *</p>
						<input type="radio" name="q3" value="ja" checked> ja<br>
						<input type="radio" name="q3" value="nein" checked> nein<br>
						<p>E-Mail für Teilnahme an der Auslosung der Gutscheine *</p>
						<input type="email" name="email" placeholder="Email"><br>
						<p style="font-size: 80%">* notwendig um an der Verlosung teilzunehmen</p>
						<input type="submit" value="Weiter">
					</form>
				</div>

			<?php } else { ?>
				<!-- <div id="language"><span onClick="document.getElementById('english').style.display = 'none';document.getElementById('german').style.display = 'block';">German</span> <span onClick="document.getElementById('german').style.display = 'none';document.getElementById('english').style.display = 'block';">English</span></div> -->
	  			<h1>Race Time Predictor</h1>
				<p id="german">Account wurde erfolgreich verbunden. Die Teilnahme an der Studie ist abgeschlossen. Vielen Dank!</p>
				
			<?php }
			?>

		</div>
	
    </body>
</html>
