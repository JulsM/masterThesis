<?php
ini_set('max_execution_time', 120);
include 'StravaPHP/vendor/autoload.php';

// use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;


class StravaApiClient {

    private $token;

    private $client;

    public function __construct($token) {
        $this->token = $token;
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $this->client = new Client($service);

    }

    


    public function queryAthlete()
    {
        try {

            $athlete = $this->client->getAthlete();
            $data    = array('stravaId' => $athlete['id'], 'mail' => $athlete['email'], 'name' => $athlete['firstname'] . ' ' . $athlete['lastname'], 'token' => $token);
         
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $data;
    }

    public function getActivties($number)
    {

        try {

            $activities     = $this->client->getAthleteActivities(null, null, null, $number);
            $returnActivity = array();

            foreach ($activities as $activity) {
                if (strtolower($activity['type']) == 'run') {
                    $ac      = $this->client->getActivity($activity['id']);
                    if(array_key_exists('device_name', $ac)) {
                        $data = array_merge(array('device_name' => $ac['device_name']), $activity);
                    }
                    $data = array_merge(array('id' => $ac['id']), $data);
                    array_push($returnActivity, $data);
                }

            }

        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $returnActivity;
    }

    public function getActivty($id)
    {

        try {
            $ac = $this->client->getActivity($id);
            
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $ac;
    }



    public function getStream($activityId, $type)
    {

        try {
            $stream = $this->client->getStreamsActivity($activityId, $type);

        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $stream;
    }

    public function getRoute($id)
    {

        try {

            $stream = $this->client->getStreamsRoute($id);

        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $stream;
    }

}

