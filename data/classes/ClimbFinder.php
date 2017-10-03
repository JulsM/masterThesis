<?php
include_once 'Configuration.php';

class ClimbFinder {
	

	public function __construct() {
	}

	public static function findClimbs($segments) {
		$gradientClimbThreshold = Config::$minClimbGradient;
		$minClimbLength = Config::$minClimbLength;
		$maxBetweenDown = Config::$maxDistDownBetween;

		$tempClimb = [];
		$tempClimbDist = 0;
	    $tempDownDist = 0;
	    $tempDownClimb = [];
	    
	    $climbs = [];
	    for ($i = 0; $i < count($segments); $i++) {
	        $gradient = $segments[$i]->gradient;
	        
	        if($gradient > $gradientClimbThreshold && empty($tempClimb)) { // start climb
	            // echo 'climb start: '.$segments[$i]->start->distance.' elev:'.$segments[$i]->start->altitude.' ';
	            $tempClimb[] = $segments[$i];
	            $tempClimbDist = $segments[$i]->length;
	        } else if($gradient > $gradientClimbThreshold) { // continue climb uphill
	        	if(!empty($tempDownClimb)) {
	        		$tempClimb = array_merge($tempClimb, $tempDownClimb);
	        		$tempDownDist = 0;
	        		$tempDownClimb = [];
	        	}
	        	$tempClimb[] = $segments[$i];
	        	$tempClimbDist += $segments[$i]->length;
	        	

	    	} else if($gradient <= $gradientClimbThreshold && !empty($tempClimb)) { // end climb
	    		if($tempClimbDist >= $minClimbLength) { // check if climb length before going under threshold
		    		$tempDownClimb[] = $segments[$i];
		    		$tempDownDist += $segments[$i]->length;
		    	} else {
		    		$tempClimb = [];
		            $tempDownClimb = [];
		            $tempDownDist = 0;
		            $tempClimbDist = 0;
		    	}
	        }
	        // if inbetween downhill is to long, end climb
	        if($tempDownDist > $maxBetweenDown && !empty($tempClimb)) { 

	        	if($tempClimbDist >= $minClimbLength) {
	        		$c = new Climb($tempClimb);
	        		$c->update();
		        	$climbs[] = $c;
		        	
		        	$fiets = $c->getFietsIndex();
		            // echo 'climb end: '.$c->end->distance.' elev:'.$c->end->altitude.' fiets: '.$fiets.'<br>';
		            
		        }
		        $tempClimb = [];
	            $tempDownClimb = [];
	            $tempDownDist = 0;
	            $tempClimbDist = 0;
	        }
	             
	    }
	    if(!empty($tempClimb) && $tempClimbDist >= $minClimbLength) {
	        $c = new Climb($tempClimb);
			$c->update();
	    	$climbs[] = $c;
	    	
	    	$fiets = $c->getFietsIndex();
	        // echo 'climb end: '.$c->end->distance.' elev:'.$c->end->altitude.' fiets: '.$fiets.'<br>';
		    
	    }
	    return $climbs;
	}


}