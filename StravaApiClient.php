<?php
// ini_set('max_execution_time', 180);
// set_time_limit(180);
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
            $data    = array('stravaId' => $athlete['id'], 'mail' => $athlete['email'], 'name' => $athlete['firstname'] . ' ' . $athlete['lastname'], 'token' => $this->token);
         
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $data;
    }

    public function getAthlete($id)
    {
        try {

            $athlete = $this->client->getAthlete($id);
            
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $athlete;
    }

    public function getActivties($after)
    {

        try {

            $activities     = $this->client->getAthleteActivities(null, $after, 1, Config::$numOfActivities);
            $returnActivity = [];

            foreach ($activities as $activity) {
                if (strtolower($activity['type']) == 'run' && !empty($activity['start_latitude'])) {
                    $returnActivity[] = $this->client->getActivity($activity['id']);
                }
            }

            // $activities = $this->client->getAthleteActivities(null, $after, 2, 100);

            // foreach ($activities as $activity) {
            //     if (strtolower($activity['type']) == 'run') {
            //         $returnActivity[] = $this->client->getActivity($activity['id']);
            //     }
            // }

        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $returnActivity;
    }

    public function getActivity($id)
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

