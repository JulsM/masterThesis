<?php

class Db
{

    private static $instance = null;
    private $connection;

    private function __construct()
    {
        try {
            $this->connection = pg_connect("dbname=strava user=julian password=prediction");
            //echo 'db connected';
        } catch (PDOException $ex) {
            echo "Error: " . $ex->getMessage();
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Db();
        }

        return self::$instance;
    }

    public function getConnection()
    {

        return $this->connection;
    }

    

    public function query($sql)
    {
        $result = pg_query($this->connection, $sql);

        return pg_fetch_all($result);
    }

    public function saveUser($data)
    {
        $sucess = false;
        try {
            $statement = "INSERT INTO users (strava_id, name, email, token) VALUES ('".$data['stravaId']."', '".$data['name']."', '".$data['mail']."', '".$data['token']."')";
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
        // print( self::query("select id from users where email = '".$data['mail']."'"));
    }

    public function saveStudy($data)
    {
        $sucess = false;
        try {
            $statement = "INSERT INTO study (strava_id, name, email, token, question_1, question_2, question_3) VALUES ('".$data['stravaId']."', '".$data['name']."', '".$data['mail']."', '".$data['token']."', '".$data['q1']."', '".$data['q2']."', '".$data['q3']."')";
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function saveAthlete($athlete)
    {
        $timestamp = date('Y-m-d H:i:s e');
        $sucess = false;
        try {
            $statement = "INSERT INTO athlete (strava_id, name, gender, update_timestamp, token, weekly_mileage, average_training_pace, average_race_pace, serialized_x_week_summary) VALUES ('".$athlete->id."', '".htmlspecialchars($athlete->name, ENT_QUOTES)."', '".$athlete->gender."', '".$timestamp."', '".$athlete->token."', '".$athlete->weeklyMileage."', '".$athlete->averageTrainingPace."', '".$athlete->averageRacePace."', '".serialize($athlete->xWeekSummary)."')";

            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateAthlete($athlete)
    {
        $timestamp = date('Y-m-d H:i:s e');
        try {
            $statement = "UPDATE athlete SET update_timestamp = '".$timestamp."', weekly_mileage = ".$athlete->weeklyMileage.", average_training_pace = ".$athlete->averageTrainingPace.", average_race_pace = ".$athlete->averageRacePace.", average_elevation_gain = ".$athlete->averageElevationGain.", average_percentage_hilly = ".$athlete->averagePercentageHilly.", acute_training_load = ".$athlete->atl.", chronic_training_load = ".$athlete->ctl."  WHERE strava_id = ".$athlete->id.";";
            
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function saveActivity($activity, $athleteId)
    {
        $timestamp = date('Y-m-d H:i:s e');
        try {
            $statement = "INSERT INTO activity (strava_id, athlete_id, name, elapsed_time, distance, average_speed, elevation_gain, elevation_loss, vo2_max, percentage_hilly, surface, activity_type, split_type, update_timestamp, climb_score, serialized_segments, serialized_climbs, activity_timestamp, serialized_raw_data_points, average_ngp, training_stress_score, pre_activity_atl, pre_activity_ctl, serialized_xweek_summary) VALUES ('".$activity->id."', '".$athleteId."','".htmlspecialchars($activity->name, ENT_QUOTES)."', '".$activity->elapsedTime."', '".$activity->distance."', '".$activity->averageSpeed."', '".$activity->elevationGain."', '".$activity->elevationLoss."', '".$activity->vo2Max."', '".$activity->percentageHilly."', '".$activity->surface."', '".$activity->activityType."', '".$activity->splitType."', '".$timestamp."', '".$activity->climbScore."', '".serialize($activity->segments)."', '".serialize($activity->climbs)."', '".$activity->date."', '".serialize($activity->rawDataPoints)."', '".$activity->averageNGP."', '".$activity->tss."', '".$activity->preAtl."', '".$activity->preCtl."', '".serialize($activity->xWeekSummary)."')";
            
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateActivity($activity)
    {
        $timestamp = date('Y-m-d H:i:s e');
        try {
            $statement = "UPDATE activity SET elapsed_time = ".$activity->elapsedTime.", distance = ".$activity->distance.", average_speed = ".$activity->averageSpeed.", elevation_gain = ".$activity->elevationGain.", elevation_loss = ".$activity->elevationLoss.", vo2_max = ".$activity->vo2Max.", percentage_hilly = ".$activity->percentageHilly.", surface = '".$activity->surface."', activity_type = '".$activity->activityType."', split_type = '".$activity->splitType."', update_timestamp = '".$timestamp."', climb_score = ".$activity->climbScore.", average_ngp = ".$activity->averageNGP.", training_stress_score = ".$activity->tss.", pre_activity_atl = ".$activity->preAtl.", pre_activity_ctl = ".$activity->preCtl.", serialized_xweek_summary = '".serialize($activity->xWeekSummary)."' WHERE strava_id = ".$activity->id.";";
            
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function updateSegmentsClimbsActivity($activity)
    {
        $timestamp = date('Y-m-d H:i:s e');
        try {
            $statement = "UPDATE activity SET serialized_segments = '".serialize($activity->segments)."', serialized_climbs = '".serialize($activity->climbs)."' WHERE strava_id = ".$activity->id.";";
            
            $result = pg_query($this->connection, $statement);
            
        } catch(Exception $e) {
            echo $e->getMessage();
        }
    }

    public function getActivities($athleteId, $from, $to=null, $summary=false) {
        // echo $after;
        $to = ($to == null ? date('Y-m-d H:i:s e',time()) : $to);
        if($summary) {
            $query = 'SELECT strava_id, athlete_id, activity_timestamp, name, elapsed_time, distance, average_speed, elevation_gain, elevation_loss, vo2_max, climb_score, percentage_hilly, surface, activity_type, split_type, average_ngp, training_stress_score, pre_activity_atl, pre_activity_ctl, serialized_xweek_summary FROM activity WHERE athlete_id =' . $athleteId.' AND activity_timestamp >= \''.$from.'\' AND activity_timestamp < \''.$to.'\' ORDER BY activity_timestamp';
            
        } else {
            $query = 'SELECT * FROM activity WHERE athlete_id =' . $athleteId.' AND activity_timestamp >= \''.$from.'\' AND activity_timestamp < \''.$to.'\' ORDER BY activity_timestamp';
        }
        
        $result = $this->query($query);
        if (empty($result)) {
            return null;
        } else {
            return $result;
        }
    }

    public function deleteActivities($athleteId) {
        $query = "DELETE FROM activity WHERE athlete_id =" . $athleteId;
        $result = $this->query($query);
    }

}
