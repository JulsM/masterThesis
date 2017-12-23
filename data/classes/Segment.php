<?php 

class Segment {

	public $start;

	public $end;

	public $length;

	public $gradient;

	public $elevation;



	public function __construct($startPoint, $endPoint) {
		$this->start = $startPoint;
		$this->end = $endPoint;
		$this->length = $endPoint->distance - $startPoint->distance;
		
	}

	function update() {
		$this->gradient = 0;
		if ($this->length > 0) {
	        $this->gradient = round(($this->end->altitude - $this->start->altitude) / $this->length * 100, 2);
	    }
	    $this->elevation = $this->end->altitude - $this->start->altitude;
	}

	public function printSegment() {
		$vel = 1000/ ($this->length / ($this->end->time - $this->start->time));
		$min = floor($vel / 60);
		$s = $vel % 60;
		echo 'Segment start '.$this->start->distance.', end '.$this->end->distance.', length '.$this->length.', gradient '.$this->gradient.', vel: '.$min.':'.$s.'<br>';
	}

}