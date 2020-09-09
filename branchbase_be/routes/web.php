<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
| recommit recommit recommit
*/
 
$router->get('/', function () use ($router) {
    return $router->app->version();
});

// API route group
$router->group(['prefix' => 'api'], function () use ($router) {

    $router->post('login', 'AuthController@login');

    //forget password
    $router->get('forget_pass', 'ForgetPassController@forget_pass');
    $router->get('forget_pass_otp', 'ForgetPassController@checkOtp');
    $router->patch('change_pass', 'ForgetPassController@change_pass');

    $router->get('profile', 'UserController@profile');
    $router->get('header-data', 'UserController@header_data');
//    $router->get('users/{id}', 'UserController@singleUser');
//    $router->get('users', 'UserController@allUsers');
    $router->get('bb-payroll','BBPayrollController@get_teachers');
    $router->get('earnings-log','Cron_scripts@el_generate_teacher');
    $router->get('my-earnings-log','BBPayrollController@get_bb_earnings_log');
    $router->get('call-in/{id}/{dingid}/{from}/{to}','BBPayrollController@dingtalk_callins');
    $router->get('my-pb','BBPayrollController@bb_performance_bonus');
    $router->get('my-transferred-class/total','BBPayrollController@bb_total_transferred_class');
    $router->get('my-transferred-class/view','BBPayrollController@bb_transferred_class');
    $router->get('my-complaints','BBPayrollController@bb_complaints');
    $router->get('my-pi','BBPayrollController@bb_performance_improvement');
    $router->get('my-class-list','BBPayrollController@get_class_list');
    $router->get('my-attendance','BBPayrollController@bb_attendance');
    $router->get('my-class-list/deducted','BBPayrollController@bb_class_count_deducted');
    $router->get('my-class-list/view/{class_id}','BBPayrollController@bb_view_class');
    $router->get('my-class-list/maxtalktime','BBPayrollController@get_bb_max_talktime');
    $router->get('my-monthly-reports','BBPayrollController@bb_monthly_report');
    $router->get('test','BBPayrollController@test');
    $router->get('my-earnings-count','BBPayrollController@get_bb_earnings_count');
    $router->get('get-payslip','PayslipController@getPayslip');
    $router->get('generate-levels','Cron_scripts@generate_starting_level');
    $router->get('monthly-earnings','BBPayrollController@monthly_earnings_log');
    $router->get('test-duration','Cron_scripts@test_video_duration');
    $router->get('my-class-list/transferred','BBPayrollController@get_transferred_class_details');
    $router->get('get-payroll-v2','PayslipController@getPayslipV2');
    $router->get('current-classrate','BBPayrollController@get_current_classrate');
    $router->get('my-ir','BB_ir@ir');
    $router->post('disputes','Cron_scripts@disputes');
});
