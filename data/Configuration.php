<?php 
class Config {

	## API
	static $numOfActivities = 20;

	## Clean Google data
	static $cleanMinDist = 5; // meters
	static $surfaceStepsDist = 400; // meters

	## RDP
	static $epsilonRPD = 2.5;

	## Compute Segments
	static $lowGradientThreshold = 1.8; // %
	static $highGradientThreshold = 4.5;
	static $lowGradientThresholdRecomp = 1;
	static $highGradientThresholdRecomp = 4;

	## Segments Filter
	static $minSegmentLength = 200; // meters
	static $maxSegmentGradient = 7.5; // %

	## climbs
	static $minClimbLength = 400; // meters
	static $minClimbGradient = 2; // %
	static $maxDistDownBetween = 250; // meters
	static $hillySegmentThreshold = 2; // %
}