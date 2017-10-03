<?php 


class OverpassApiClient {
	private static $radius = 4.0;

	public function __construct() {
	}

	public function buildQuery($dataPoints) {
		$query = "[out:json][timeout:60];";
		foreach($dataPoints as $point) {
			$query .= 'way["highway"]["surface"](around:' . $this::$radius . ', ' . $point->latitude . ', ' . $point->longitude . ');out;';
		}
		// $query .= 'out;';
		
		return $query;
	}

	public function fetchJson($query) {
		try {
    		$json = json_decode(file_get_contents("http://overpass-api.de/api/interpreter?data=" . urlencode($query)));
    		// print_r($json->elements);
    		return $json->elements;
    	} catch(Exception $e) {
    		echo $e->getMessage();
    		return null;
    	}
	}

	
}