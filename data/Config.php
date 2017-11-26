<?php 
class Config {



	## API
	static $numOfActivities = 8;

	## Clean Google data
	static $cleanMinDist = 5; // meters
	static $surfaceStepsDist = 500; // meters

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

	## split type
	static $evenSplitThreshold = 2; // sec

	## Intervals
	static $intervalTrainingPercent = 0.9; // 120 %

	## Long runs 
	static $weeklyMileagePercentHigh = 1.3; //130 %
	static $weeklyMileagePercentLow = 1.2; // 120%,  over 25 km per run

	## athlete summary
	static $XWeeks = 6; //weeks
	static $maxActivityAgo = -2; //year

	## TSS
	static $FTPWeeks = 20; // weeks

	## energy cost model
	static $maxGrade = 40;
	static $maxVelocity = 8.33; // 2:00 min/km

}