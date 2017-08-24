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

    echo 'google data, first: distance '.$distanceArray[0].' elevation '.$googleElevation[0]->elevation.'<br>';
    echo 'google data, last: distance '.$distanceArray[count($distanceArray)-1].' elevation '.$googleElevation[count($googleElevation)-1]->elevation.'<br>';

    ### clean google elevation data
    $elevationDistanceArray = cleanGoogleElevation($googleElevation, $distanceArray);
    ###

    echo 'clean data, first: distance '.$elevationDistanceArray[1][0].' elevation '.$elevationDistanceArray[0][0].'<br>';
    echo 'clean data, last: distance '.$elevationDistanceArray[1][count($elevationDistanceArray[0])-1].' elevation '.$elevationDistanceArray[0][count($elevationDistanceArray[0])-1].'<br>';


    ### write data in CSV and GPX files
    writeControlData($googleElevation, $stravaElevation, $distanceArray, $elevationDistanceArray);
    ###

    ### apply RDP algo
    $rdpResult = applyRDP($elevationDistanceArray, 2.5);
    ###

    echo 'rdp data, first: distance '.$rdpResult[0][0].' elevation '.$rdpResult[0][1].'<br>';
    echo 'rdp data, last: distance '.$rdpResult[count($rdpResult)-1][0].' elevation '.$rdpResult[count($rdpResult)-1][1].'<br>';

    ### get elevation gain
    $elevationArray   = array_column($rdpResult, 1);
    $elevationGain = computeElevationGain($elevationArray);
    echo 'elevation gain: ' . $elevationGain . '<br>';
    ###

    ### compute extreme points
    $elevationArray   = array_column($rdpResult, 1);
    $distanceArray    = array_column($rdpResult, 0);
    // computeExtrema($elevationArray, $distanceArray);
    $segments = computeSegments($elevationArray, $distanceArray);

    echo 'segment data, first: distance '.$segments[0][0].' elevation '.$segments[0][1].'<br>';
    echo 'segment data, last: distance '.$segments[count($segments)-1][0].' elevation '.$segments[count($segments)-1][1].'<br>';

    ###

    ?>


    </div>
    </body>
</html>
