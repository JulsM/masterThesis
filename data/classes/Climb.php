<?php 

class Climb extends Segment {

	public $segments;

	public $fietsIndex;


	public function __construct($segments) {
		parent::__construct($segments[0]->start, $segments[count($segments)-1]->end);
		$this->segments = $segments;
	}

	function getFietsIndex() {
        if ($this->elevation <= 0) {
            return 0.0;
        }
        $index = $this->elevation * $this->elevation / ($this->length * 10);
        $altitudeBonus = max(0, ($this->end->altitude - 1000) / 1000);
        return $index + $altitudeBonus;
	}

	// m/h
	function getVerticalSpeed() {
		$duration = $this->end->time - $this->start->time;
		$speed = $this->elevation / ($duration / 3600);
		return round($speed, 2);
	}

	public function toString() {
		echo 'Climb start '.$this->start->distance.', end '.$this->end->distance.', length '.$this->length.', gradient '.$this->gradient.'<br>';
	}


}