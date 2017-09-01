<?php  

$app = new App();

class App {

	private $api;

	private $token;

	public function __construct() {

	}

	public function createStravaApi($token) {
        $this->api = new StravaApiClient($token);
        $this->token = $token;
    }

	public function getApi() {
        return $this->api;
    }

    public function getToken() {
    	return $this->token;
    }
}