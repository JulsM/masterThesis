<?php


function vo2max($distance, $elapsedTime) {
	$minutes = $elapsedTime / 60;
	$mPerMin = $distance / $minutes;

	$o2cost = -4.6 + 0.182258 * $mPerMin + 0.000104 * $mPerMin * $mPerMin;
	$dropDead = 0.8 + 0.1894393 * exp(-0.012778 * $minutes) + 0.2989558 * exp(-0.1932605 * $minutes);
	return $o2cost / $dropDead;
}

function vo2maxWithElevation($distance, $elapsedTime, $elevation) {
	$extraDistance = 2*$elevation[0] + $elevation[1];
	$distance += $extraDistance;
	$minutes = $elapsedTime / 60;
	$mPerMin = $distance / $minutes;

	$o2cost = -4.6 + 0.182258 * $mPerMin + 0.000104 * $mPerMin * $mPerMin;
	$dropDead = 0.8 + 0.1894393 * exp(-0.012778 * $minutes) + 0.2989558 * exp(-0.1932605 * $minutes);
	return $o2cost / $dropDead;
}

