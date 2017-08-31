<?php
ini_set('max_execution_time', 120);
include 'StravaPHP/vendor/autoload.php';
include 'formula.php';

// use Pest;
use Strava\API\Client;
use Strava\API\Exception;
use Strava\API\Service\REST;

function queryAthlete($token)
{

    try {
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $client  = new Client($service);

        $athlete = $client->getAthlete();
        $data    = array('stravaId' => $athlete['id'], 'mail' => $athlete['email'], 'name' => $athlete['firstname'] . ' ' . $athlete['lastname'], 'token' => $token);
        // print_r($athlete);
        $stats = $client->getAthleteStats($athlete['id']);

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $data;
}

function queryActivties($token, $number)
{

    try {
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $client  = new Client($service);

        $activities     = $client->getAthleteActivities(null, null, null, $number);
        $returnActivity = array();

        foreach ($activities as $activity) {
            if (strtolower($activity['type']) == 'run') {
                $ac      = $client->getActivity($activity['id']);
                $data = array_merge(array('id' => $ac['id']), $activity);
                array_push($returnActivity, $data);
            }

        }

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $returnActivity;
}


function getStream($token, $id, $type)
{

    try {
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $client  = new Client($service);

        $stream = $client->getStreamsActivity($id, $type);

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $stream;
}

function getRoute($token, $id)
{

    try {
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $client  = new Client($service);

        $stream = $client->getStreamsRoute($id);

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $stream;
}

