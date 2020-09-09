<?php

namespace App\Libraries;
class Helpers
{
    static function PRINT_DUMP($data = [], $die = FALSE, $vd = FALSE)
    {
        echo '<pre>';
        if ($vd) {
            var_dump($data);
        } else {
            print_r($data);
        }
        echo '<br>';
        if ($die)
            die();
    }

    static function ASSOCIATIVE($key = '', $array = [])
    {
        if (empty($array) && $key == '')
            return [];
        $new = array();
        foreach ($array as $v) {
            if (!array_key_exists($v[$key], $new))
                $new[$v[$key]] = $v;
        }
        return $new;
    }

    static function OBJECT_TO_ARRAY($data)
    {
        if (is_array($data) || is_object($data)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$key] = self::OBJECT_TO_ARRAY($value);
            }
            return $result;
        }
        return $data;
    }

    static function ASSOCIATIVE_MULTI($key = '', $array = [])
    {
        if (empty($array) && $key == '')
            return [];
        $new = array();
        foreach ($array as $v) {
            $new[$v[$key]][] = $v;
        }
        return $new;
    }

    static function DB_TABLE($db, $table)
    {
        return env($db . '_' . strtoupper(env('APP_ENV'))) . '.' . $table;
    }

    static function FETCH_CURL($url,$post_data = array()){
        ini_set('max_execution_time', 240);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);
        if (is_array($post_data) && !empty($post_data)) {
            curl_setopt($ch, CURLOPT_POST, count($post_data));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        }else{
            curl_setopt($ch, CURLOPT_POST, count($_POST));
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($_POST));
        }
        $result = curl_exec($ch);
        if (curl_errno($ch)) {
          return(json_encode(['error'=>'Timeout']));
        }
        curl_close($ch);

        return  $result;
    }

    static function auto_transfer_reason(){
        return [
            1 => '10 mins Prior to shift',
            2 => 'Late',
            3 => 'AWOL',
            4 => 'Under Time',
            5 => 'Absent',
            6 => 'Absent',
            7 => 'Absent',
            8 => 'Absent',
            9 => 'Leave',
            10 => '15 mins Prior to shift',
            11 => 'Absent'];
    }

    static function VIDEO_DURATION($class_ids = ''){
        //$videoduration_api = "https://query.helputalk.com/log/LessonAction/list/";
        $videoduration_api = "https://data-center.helputalk.com/api/LessonAction/getStaticList/";
        switch (env('APP_ENV')) {
            case 'prod' :
                $videoduration_api = env('VIDEO_DURATION_PROD');
                break;
            case 'test' :
                $videoduration_api = env('VIDEO_DURATION_TEST');
                break;
            default:
                $videoduration_api = env('VIDEO_DURATION_PROD');
           
        }
        $user   = 'web_put_user';
        $time   = time();
        $params = [
            'user' => $user,
            'time' => $time,
            'token' => md5($user.$time.'2f3d53a6-0554-11e8-bcf1-702084e1f452'),
            'lessonid[]' => $class_ids
        ];
        retry:
            $vd = json_decode(self::FETCH_CURL($videoduration_api,$params),true);

        if (isset($vd['error']) && $vd['error'] == 'Timeout') 
            goto retry;
        $vd_list = [];
        if (!empty($vd['data'])) {
            $vd_data = $vd['data']['list'];
            if (!empty($vd_data)) {
                foreach ($vd_data as $key => $c) {
                    if ($c['call_time'] > strtotime('2020-03-22')) {
                       $vd_list[$c['lessonid']] =  $c['client_statis_time'] ? self::second_to_minute($c['client_statis_time']) : '--';
                    }else{
                        $vd_list[$c['lessonid']] =  $c['teacher_all_time'] ? self::second_to_minute($c['teacher_all_time']) : '--';
                    }
                    
                }
            }
        }
        return $vd_list;
    }

    static function second_to_minute($seconds){

    /// get minutes
        $minResult = floor($seconds/60);

    /// if minutes is between 0-9, add a "0" --> 00-09
        if($minResult < 10){$minResult = 0 . $minResult;}

    /// get sec
        $secResult = ($seconds/60 - $minResult)*60;

    /// if secondes is between 0-9, add a "0" --> 00-09
        if($secResult < 10){$secResult = 0 . $secResult;}

    /// return result
        return $minResult.".".$secResult;

    }
}
