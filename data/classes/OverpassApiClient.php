<?php 


class OverpassApiClient {
	private static $radius = 4.0;

	public function __construct() {
	}

	public function buildQuery($dataPoints) {
		$query = "[out:json][timeout:120];";
		foreach($dataPoints as $point) {
			$query .= 'way["highway"]["surface"](around:' . $this::$radius . ', ' . $point->latitude . ', ' . $point->longitude . ');out;';
		}
		// $query .= 'out;';
		
		return $query;
	}

	public function fetchJson($query) {
		try {
			$time_pre = microtime(true);

    		$json = json_decode($this->file_get_contents_curl("http://overpass-api.de/api/interpreter?data=" . urlencode($query)));
    		// print_r($json->elements);
    		$time_post = microtime(true);
			$exec_time = $time_post - $time_pre;
			echo ' time: '.$exec_time. ' ';
      		if(!isset($json)) {
      			$error = error_get_last();
      			echo "HTTP request failed. Error was: " . $error['message'];
      			return null;
      		} else {
    			return $json->elements;
    		}
    	} catch(Exception $e) {
    		echo $e->getMessage();
    		return null;
    	}
	}

	private function file_get_contents_curl($url) {
	    $ch = curl_init();

	    curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);   
	    curl_setopt($ch, CURLOPT_ENCODING, ''); 
	    // curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );   

	    $data = curl_exec($ch);

	    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

	    if($http_status != 200) {		
		    echo 'error:' . curl_error($ch).' http status: '.$http_status;
		}
	    curl_close($ch);

	    return $data;
	}

	
}