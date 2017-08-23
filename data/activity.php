<?php
include_once '../queryApi.php';
include_once 'activityProcessing.php';


if (isset($_POST['token']) && isset($_POST['id'])) {
    // $streamLatLong  = getStream($_POST['token'], $_POST['id'], "latlng");
    // $latlongArray   = $streamLatLong[0]['data'];
    // $distanceArray  = $streamLatLong[1]['data'];
    // $streamElev     = getStream($_POST['token'], $_POST['id'], "altitude");
    // $stravaElevation = $streamElev[1]['data'];
    $route = getRoute($_POST['token'], 10271778);
    $latlongArray = $route[0]['data'];
    $distanceArray = $route[1]['data'];
    $stravaElevation = $route[2]['data'];
}

?>

<!DOCTYPE html>
<html>
    <head>

    </head>
    <body>
    <div style="width: 80%; height: 80%; margin: 5% auto">
    <p>Extrema data:</p>
    <?php
    ### get google elevation data
    $googleElevation = getGoogleElevation($latlongArray);

    ### clean google elevation data
    $elevationDistanceArray = cleanGoogleElevation($googleElevation, $distanceArray);
    ###


    ### write data in CSV and GPX files
    writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray);
    ###

    ### apply RDP algo
    $rdpResult = applyRDP($elevationDistanceArray, 3.5);
    ###

    ### get elevation gain
    $elevationArray   = array_column($rdpResult, 1);
    $elevationGain = computeElevationGain($elevationArray);
    echo 'elevation gain: ' . $elevationGain . '<br>';
    ###

    ### compute extreme points
    $elevationArray   = array_column($rdpResult, 1);
    $distanceArray    = array_column($rdpResult, 0);
    computeExtrema($elevationArray, $distanceArray);

    ###

    ?>


    </div>
    </body>
</html>
