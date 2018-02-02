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
	static $highGradientThreshold = 5;
	static $steepGradientThreshold = 17;
	static $lowGradientThresholdRecomp = 1.8;
	static $highGradientThresholdRecomp = 5;
	static $steepGradientThresholdRecomp = 17;

	## Segments Filter
	static $minSegmentLength = 200; // meters
	static $maxSegmentGradient = 30; // %

	## climbs
	static $minClimbLength = 400; // meters
	static $minClimbGradient = 4; // %
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