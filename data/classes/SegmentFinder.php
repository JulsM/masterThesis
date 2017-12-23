<?php
class SegmentFinder {

	public static $writeFiles = false;

	public function __construct() {
	}

	public static function findSegments($dataPoints) {
		global $fileWriter;
		### apply RDP algo
	    $smoothDataPoints = RDP::RamerDouglasPeuckerSegments($dataPoints, Config::$epsilonRPD);
	    
	    ###
	    if(self::$writeFiles) {
		    ### write data in CSV 
		    $fileWriter->writeControlData($smoothDataPoints, 'rdp');
		    ###
		}

	    ### compute segments
	    $segments = self::computeSegments($smoothDataPoints);
	    ###
	    if(self::$writeFiles) {
		    ### write data in CSV 
		    $fileWriter->writeControlData($segments, 'segments');
		    ###
		}

	    ### filter segments
	    $filteredSegments = self::filterSegments($segments);
	    ###
	    if(self::$writeFiles) {
		    ### write data in CSV 
		    $fileWriter->writeControlData($filteredSegments, 'filtered');
		    ###
		}

	    ### recompute segments
	    $recompSegments = self::recomputeSegments($filteredSegments);
	    ### 
	    if(self::$writeFiles) {
		    ### write data in CSV 
		    $fileWriter->writeControlData($recompSegments, 'recompute');
		    ###
		}
	    return $recompSegments;
	}

	public static function computeSegments($dataPoints) {
		$flatGradientTreshold = Config::$lowGradientThreshold;
		$highGradientThreshold = Config::$highGradientThreshold;
		$steepGradientThreshold = Config::$steepGradientThreshold;
		$segments = [];
		$prevPoint = $dataPoints[0];
		$currentState = 0;
		$prevState = 0;

		$startSegment = $dataPoints[0];

		for ($i = 1; $i < count($dataPoints); $i++) {
			$currentPoint = $dataPoints[$i];
		    // compute gradient between two data points
		    $relDist = $currentPoint->distance - $prevPoint->distance;
		    $height = $currentPoint->altitude - $prevPoint->altitude;
		    $gradient = 0;
			if ($relDist > 0) {
		        $gradient = round($height / $relDist * 100, 2);
		    }
	        // echo $gradient.', ';

	        if ($gradient >= $steepGradientThreshold) {
	            $currentState = 'upSteep';
	        } elseif ($gradient >= $highGradientThreshold && $gradient < $steepGradientThreshold) {
	            $currentState = 'upHigh';
	        } elseif ($gradient >= $flatGradientTreshold && $gradient < $highGradientThreshold) {
	            $currentState = 'up';
	        } elseif ($gradient < $flatGradientTreshold && $gradient > -$flatGradientTreshold) {
	            $currentState = 'level';
	        } elseif ($gradient <= -$flatGradientTreshold && $gradient > -$highGradientThreshold) {
	            $currentState = 'down';
	        } elseif ($gradient <= -$highGradientThreshold && $gradient > -$steepGradientThreshold) {
	            $currentState = 'downHigh';
	        } elseif ($gradient <= -$steepGradientThreshold) {
	            $currentState = 'downSteep';
	        }
	        // echo $currentState;
	        // if state changed add point to segments
	        if ($currentState != $prevState && $prevState != 'null') {
	        	$s = new Segment($startSegment, $prevPoint);
	        	$s->update();
	        	$segments[] = $s;
	            $startSegment = $prevPoint;
	            $prevState = $currentState;
	            
	        } else {
	            $prevState = $currentState;
	        }

		    $prevPoint = $currentPoint;

		}
		// add last 
		$s = new Segment($startSegment, $prevPoint);
		$s->update();
		$segments[] = $s;
		
		return $segments;
	}

	public static function recomputeSegments($segments) {
		$flatGradientTreshold = Config::$lowGradientThresholdRecomp;
		$highGradientThreshold = Config::$highGradientThresholdRecomp;
		$steepGradientThreshold = Config::$steepGradientThresholdRecomp;
		$response = [];
		$currentState = 0;
		$prevState = 0;

		$startSegment = $segments[0]->start;

		for ($i = 0; $i < count($segments); $i++) {
			$currentSegment = $segments[$i];
		    // compute gradient between two data points
	        $gradient = $currentSegment->gradient;


	        if ($gradient >= $steepGradientThreshold) {
	            $currentState = 'upSteep';
	        } elseif ($gradient >= $highGradientThreshold && $gradient < $steepGradientThreshold) {
	            $currentState = 'upHigh';
	        } elseif ($gradient >= $flatGradientTreshold && $gradient < $highGradientThreshold) {
	            $currentState = 'up';
	        } elseif ($gradient < $flatGradientTreshold && $gradient > -$flatGradientTreshold) {
	            $currentState = 'level';
	        } elseif ($gradient <= -$flatGradientTreshold && $gradient > -$highGradientThreshold) {
	            $currentState = 'down';
	        } elseif ($gradient <= -$highGradientThreshold && $gradient > -$steepGradientThreshold) {
	            $currentState = 'downHigh';
	        } elseif ($gradient <= -$steepGradientThreshold) {
	            $currentState = 'downSteep';
	        }
	        // if state changed add new segment
	        if ($currentState != $prevState && $prevState != 'null') {
	        	$s = new Segment($startSegment, $currentSegment->start);
	        	$s->update();
	        	$response[] = $s;
	            $startSegment = $currentSegment->start;
	            $prevState = $currentState;
	            
	        } else {
	            $prevState = $currentState;
	        }

		}
		// add last 
		$s = new Segment($startSegment, $segments[(count($segments)-1)]->end);
		$s->update();
		$response[] = $s;

		// foreach ($segments as $s) {
		// 	echo $s->length.' '.$s->gradient.', ';
		// }
		
		
		return $response;
	}

	public static function filterSegments($segments) {
		$thresholdGradient = Config::$maxSegmentGradient;
		$minLength = Config::$minSegmentLength;
		$response = [];

		## filter too steep gradients
		for ($i = 0; $i < count($segments); $i++) {
			
			$gradient = $segments[$i]->gradient;
			// echo $segments[$i]->length . ' '.$gradient.', ';
			if(($gradient <= $thresholdGradient || $gradient >= -$thresholdGradient) && $segments[$i]->length >= $minLength) {
				$response[] = $segments[$i];
			} else {
				if($i < count($segments) - 2) {
					$s = new Segment($segments[$i]->start, $segments[$i + 1]->end);
					$s->update();
					$response[] = $s;
					$i++;
				} else {
					$response[] = $segments[$i];
				}
			}
		}

		
		$segments = $response;
		$response = [];
		## filter too short segments
		for ($i = 0; $i < count($segments); $i++) {
			if($segments[$i]->length >= $minLength) {
				$response[] = $segments[$i];
			} else {
				if($i < count($segments) - 2) {
					$s = new Segment($segments[$i]->start, $segments[$i + 1]->end);
					$s->update();
					$response[] = $s;
					$i++;
				} else {
					$response[] = $segments[$i];
				}
			}
		}
		// foreach ($segments as $s) {
		// 	echo $s->length.' '.$s->gradient.', ';
		// }
		
		return $response;
	}


}