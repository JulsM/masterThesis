<?php
include 'StravaPHP/vendor/autoload.php';
include 'StravaApiClient.php';

// $callbackUrl = 'http://localhost/data/strava_callback.php';
// $callbackUrl = 'http://umtl.dfki.de/~julian/data/strava_callback.php';

use Strava\API\Exception;
use Strava\API\OAuth;
include 'database.php';


session_start(); 


if (isset($_POST['email']) && $_POST['email'] != "" && !isset($_GET['code'])) {
    $_SESSION['email'] = $_POST['email'];
    $_SESSION['q1'] = $_POST['q1'];
    $_SESSION['q2'] = $_POST['q2'];
    $_SESSION['q3'] = $_POST['q3'];

    signIn();
    
} else if (isset($_GET['code'])) {
    $db = Db::getInstance();
    $conn = $db->getConnection();
    $token = signIn();
    if ($token != '') {
        $api = new StravaApiClient($token);
        $athleteData = $api->queryAthlete();
        $athleteData['email'] = $_SESSION['email'];
        $athleteData['q1'] = $_SESSION['q1'];
        $athleteData['q2'] = $_SESSION['q2'];
        $athleteData['q3'] = $_SESSION['q3'];
        $db->saveStudy($athleteData);
        header('Location: index.php?redirect');
        exit;
    }
} else {

    echo '<p>Please go back and enter email</p>';
}

function signIn()
{
    $token = '';
    try {
        $options = array(
            'clientId'     => 17350,
            'clientSecret' => 'f60291ad212a269051a09e3e18a8147c819c4342',
            'redirectUri'  => 'http://umtl.dfki.de/~julian/strava_callback.php',
        );
        $oauth = new OAuth($options);

        if (!isset($_GET['code'])) {
            // echo '<form action="' . $oauth->getAuthorizationUrl() . '" method="post">
            //         <input type="submit" value="Connect" />
            //     </form>';
            $url = $oauth->getAuthorizationUrl();
            // echo $url;
            header('Location: '.$url);
            exit;
            // print '<a href="' . $oauth->getAuthorizationUrl() . '">Connect</a>';
        } else {
            $token = $oauth->getAccessToken('authorization_code', array(
                'code' => $_GET['code'],
            ));
        }
    } catch (Exception $e) {
        print $e->getMessage();
    }
    return $token;
}


