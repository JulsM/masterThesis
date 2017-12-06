<?php


class GoogleElevationClient
{

    /**
     * URL endpoint for API requests
     */
    protected static $URL = 'https://maps.googleapis.com/maps/api/elevation/%s';


    /**
     * API key
     */
    protected static $KEY = 'AIzaSyCVCtGgsoqmFZYGGiBwnFgOLdc61YwuPZU';



    /**
     * Array to hold onto coordinates
     * Coordinates will be saved as lat/long pairs
     */
    protected $coordinate_array = array();

    /**
     * Placeholder construct - we don't need anything instantiated
     */
    public function __construct() {}

    /**
     * Add a new coordinate pair to the coordinate array
     *
     * @param   float   $latitude   latitude in decimal format
     * @param   flat    $longitude  longitude in decimal format
     */
    public function addCoordinate($latitude, $longitude)
    {
        $coordinate = array($latitude, $longitude);
        array_push($this->coordinate_array, $coordinate);
    }

    /**
     * Format the URL endpoint with all the parameters
     * Note: this does not validate the parameters
     * @url https://developers.google.com/maps/documentation/elevation/#ElevationRequests
     *
     * @param   string  $output_format  google output format (currently json or xml)
     * @return  string  full url endpoint for the request
     */
    protected function buildURL($output_format)
    {
        $urlArray = array();
        $coordsParam = $this->buildCoordinateParameter();
        foreach ($coordsParam as $param) {
           $query = array(
                'locations' => $param,
                'key' => self::$KEY
            );
            
            $url = sprintf(self::$URL, $output_format);
            $url .= '?' . http_build_query($query);
            array_push($urlArray, $url);
        }
        
        return $urlArray;
    }

    /**
     * Prepares the coordinates for the final url parameter
     * Structured in such a way to handle a single point or multiple points
     *
     * @return  string  list of locations formatted for the googles
     */
    protected function buildCoordinateParameter()
    {
        $param_list = array();
        $str = '';
        foreach ($this->coordinate_array as $coordinate) {
            if(strlen($str) < 6000) {
                $str.= implode(',', $coordinate).'|';
            } else {
                $str = substr($str, 0, -1);
                array_push($param_list, $str);
                $str = implode(',', $coordinate).'|';
            }
            
        }
        $str = substr($str, 0, -1);
        array_push($param_list, $str);
        // print_r($param_list);
        return $param_list;
    }

    /**
     * Fetch the response as a JSON string
     * @url https://developers.google.com/maps/documentation/elevation/#ElevationResponses
     *
     * @return  string  json response from the googles
     */
    public function fetchJSON()
    {

        $jsonResults = [];
        $urlArray = $this->buildURL('json');
        foreach ($urlArray as $url) {
            // echo strlen($url). ' ';
            // echo $url;
            $response = $this->executeRequest($url);
            $json = json_decode($response);
            if($json->status != 'OK') {
                echo 'Google Elevation API error '.$json->status; 
                exit; 
            }
            
            foreach($json->results as $i) {
                $jsonResults[] = $i->elevation;
            }
        }
        return $jsonResults;
    }

    /**
     * Fetch the response as a XML string (yes, a string, you'll need to do the SimpleXML)
     * @url https://developers.google.com/maps/documentation/elevation/#ElevationResponses
     *
     * @return  string  xml response from the googles
     */
    public function fetchXML()
    {
        $url = $this->buildURL('xml');
        return $this->executeRequest($url);
    }

    /**
     * Actual request execution step via the curl
     * Accepts fully built and parameterized endpoint and asks google for information
     *
     * @param   $url    string  full endpoint for the service request
     * @return  string  string response from the request
     */
    protected function executeRequest($url)
    {
        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        return curl_exec($handle);
    }

}
