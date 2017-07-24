<?php


function vo2max($meterPerMinute, $minutes) {
	$o2cost = -4.6 + 0.182253 * $meterPerMinute + 0.000104 * $meterPerMinute * $meterPerMinute;
	$dropDead = 0.8 + 0.1894393 * exp(-0.012778 * $minutes) + 0.2989558 * exp(-0.1932605 * $minutes);
	return $o2cost / $dropDead;
}