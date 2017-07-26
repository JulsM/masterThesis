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
        // print $athlete['firstname'] . ' ' . $athlete['lastname'] . '<br>';
        $stats = $client->getAthleteStats($athlete['id']);

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $data;
}

function queryActivties($token, $name)
{

    try {
        $adapter = new Pest('https://www.strava.com/api/v3');
        $service = new REST($token, $adapter);
        $client  = new Client($service);

        $activities     = $client->getAthleteActivities(null, null, null, 50);
        $returnActivity = array();
        $regressionData = array();

        foreach ($activities as $activity) {
            if (strtolower($activity['type']) == 'run') {

                $ac      = $client->getActivity($activity['id']);
                $mPerSec = $ac['distance'] / ($ac['elapsed_time'] / 60);
                $vo2max  = vo2max($mPerSec, $ac['elapsed_time'] / 60);

                $data = array('id' => $ac['id'],
                    'name'             => $ac['name'],
                    'distance'         => $ac['distance'],
                    'time'             => $ac['elapsed_time'],
                    'average_speed'    => $mPerSec,
                    'elevation'        => $ac['total_elevation_gain'],
                    'vo2max'           => $vo2max);
                array_push($returnActivity, $data);

                $regressionData[] = array($ac['elapsed_time'] / 60, $ac['distance'] / 1000, $mPerSec, $ac['total_elevation_gain'] / 1000, $vo2max);

                // foreach ($ac['laps'] as $lap) {
                //     print 'distance: ' . $lap['distance'] . ', elapsed time: ' . $lap['elapsed_time'] . ', elevation gain: ' . $lap['total_elevation_gain'] . '<br>';
                // }
            }

        }
        writeRegressionCsv($regressionData, $name);
        // $stream = $client->getStreamsActivity(936000754, 'latlng');

    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $returnActivity;
}

function writeRegressionCsv($list, $name)
{
    $fp = fopen('output/'.$name . '_data.csv', 'w+');
    fputcsv($fp, array('time', 'distance', 'pace', 'elevation', 'vo2max'));
    foreach ($list as $line) {
        fputcsv($fp, $line);
    }
    fclose($fp);
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
