<?php
include 'StravaPHP/vendor/autoload.php';
include 'queryApi.php';

$callbackUrl = 'http://localhost/data/strava_callback.php';
$callbackUrl = 'http://umtl.dfki.de/~julian/data/strava_callback.php';

use Strava\API\Exception;
use Strava\API\OAuth;
include 'database.php';

$db = Db::getInstance();
$conn = $db->getConnection();


if (isset($_POST['email']) && $_POST['email'] != "" && !isset($_GET['code'])) {
    $result = $db->query('SELECT * FROM users WHERE email = \'' . $_POST['email'] . '\'');
    // print_r($result);
    if (empty($result)) {
        echo 'Email not in db. Connect with Strava';
        signIn();
    } else {
        echo 'Already in DB';
        header('Location: ../index.php?redirect');
        exit;

    }
} else if (isset($_GET['code'])) {
    $token = signIn();
    if ($token != '') {
        $athleteData = queryAthlete($token);
        $db->saveAthlete($athleteData);
        header('Location: ../index.php?redirect');
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
            'redirectUri'  => 'http://umtl.dfki.de/~julian/data/strava_callback.php',
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


