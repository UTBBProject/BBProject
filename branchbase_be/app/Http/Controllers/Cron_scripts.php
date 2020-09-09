<?php
namespace App\Http\Controllers;
use App\Libraries\Helpers as Helper;
use App\Models\Employee\Employees as Employee;
use App\Models\Edu\Edu_class as Edu;
use App\Models\Payroll\Payroll as Pay;
use Illuminate\Http\Request;
use Log;

class Cron_scripts extends Controller
{
    protected $employee_model;
    protected $search;
    protected $args;
    public function __construct($arguments = [])
    {
        $this->employee_model = new Employee;
        $this->edu_model = new Edu;
        $this->pay_model = new Pay;
        $this->args = $arguments;
    }

    public function earningsLog(){
        Log::info('earningsLog start');
        ini_set('max_execution_time', 0);
        ini_set('memory_limit','512M');
        
        if (!empty($this->args)) {
            $args = $this->args;
            if (isset($args['dFrom'][0])) {
                $d1 = $args['dFrom'][0];
            }else{
                $d1 = date('Y-m-d',strtotime('-2 days'));
            }

            if (isset($args['dTo'][0])) {
                $d2 = $args['dTo'][0];
            }else{
                $d2 = date('Y-m-d',strtotime('-1 days'));
            }
        }else{
            $d1 = date('Y-m-d',strtotime('-2 days'));
            $d2 = date('Y-m-d',strtotime('-1 days'));
        }
        
        $dates = $this->date_range($d1,$d2);
        $batch_saved = 0;
        foreach ($dates as  $_d) {

            $talk_holidays = $this->employee_model->talk_holiday(strtotime($_d));
            if ($talk_holidays != null) {
                if (strtotime($_d) >= $talk_holidays->date_from && strtotime($_d) <= $talk_holidays->date_to) {
                    //return response()->json(['status' => 'success','msg' => 'Holiday (china)']);
                    continue;
                }
            }
            $this->employee_model->delete_earnings_log($_d);
            $d_from = strtotime(date('Y-m-d 00:00:00',strtotime($_d)));
            $d_to   = strtotime(date('Y-m-d 23:59:59',strtotime($_d)));

            $data = [];
            $class_logs = [];
            $transferred_info = [];

            $e_logs = $this->employee_model->get_bb_employees(TRUE,date('Y-m-d',strtotime($_d)));

            if (empty((array)$e_logs)){
                /*Log::info('No employee list');
                die()*/;
                continue;
            }
            $checkin_sched = $this->employee_model->get_bb_checkin_sched($_d);
            if (!empty((array)$checkin_sched)) {
                $checkin_sched_array = Helper::OBJECT_TO_ARRAY($checkin_sched);
                $checkin_sched_array = Helper::ASSOCIATIVE_MULTI('tid',$checkin_sched_array);
            }

            if (!empty($e_logs)) {
                $teachers = $e_logs;
                $ding_ids = [];
                $edu_ids = [];
                $ctr = 0;

                foreach ($teachers as $key => $v) {
                    array_push($ding_ids, $v->ding_emp_id);
                    array_push($edu_ids, $v->edu_id);
                }
                $dt_leaves    = $this->get_dt_leave_status($ding_ids,$d_from);
                $dt_callins =  $this->dingtalk_callins(-1,$ding_ids,$d_from);
                $dt_approvals = $this->dingtalk_valid_class($ding_ids,$d_from);
                $transferred_class_data = $this->employee_model->get_all_transferred_class($edu_ids,$d_from,$d_to);

                if (!empty((array)$transferred_class_data)) {
                    $transferred_class_data = Helper::OBJECT_TO_ARRAY($transferred_class_data);
                    $transferred_class_data = Helper::ASSOCIATIVE_MULTI('teacherid',$transferred_class_data);
                }
                $class_rate = $this->employee_model->get_class_rate();
                $class_rate = Helper::OBJECT_TO_ARRAY($class_rate);
              
                foreach ($teachers as $key => $t) {
                    $talk_class = $this->employee_model->get_all_class([$t->edu_id],$d_from,$d_to);
                    if (empty((array)$talk_class))
                        continue;
                    $transferred_class = isset($transferred_class_data[$t->edu_id]) ? $transferred_class_data[$t->edu_id] : [];
                    $teachers_rc = $this->employee_model->get_running_countv1($t->edu_id);
                   
                    //$_firstRC_onLoop = $teachers_rc;
                    $teachers_total_valid_class = $this->employee_model->test_countv2($t->edu_id,$t->entry_date,$d_to);
                    $first_run_rc = '';
                    
                    if ($teachers_rc == null) {

                        $teachers_rc_data = $this->employee_model->get_bb_level_history($t->edu_id);

                        if ($teachers_rc_data != null) {
                            
                            $teachers_rc = $teachers_total_valid_class - $teachers_rc_data->level_up_count;
                            $check_course_rankup = $this->employee_model->get_teachers_rate($teachers_rc_data->current_level);

                            if ($teachers_rc > $check_course_rankup->course_rank_up) {
                                $total_lvlup_count_class = $this->employee_model->get_tvc_history($t->edu_id);
                                $teachers_rc = $teachers_total_valid_class - $total_lvlup_count_class;
                            }
                            $first_run_rc = $teachers_rc_data->current_level;
                        }else{
                            $teachers_rc = $teachers_total_valid_class;
                            $first_run_rc = $t->starting_level;
                        }
                    }else{
                        $rc_tmp = $teachers_rc;
                        $teachers_rc = $teachers_rc->running_class_count;
                        $teachers_total_valid_class = $rc_tmp->total_valid_class;
                    }
                    
                    if ($t->starting_level == "")
                        continue;

                    if ($first_run_rc != '') {
                        $teachers_cr = $this->employee_model->get_teachers_rate($first_run_rc);
                    }else{
                        if (isset($rc_tmp)) {
                            $teachers_cr = $this->employee_model->get_teachers_rate($rc_tmp->current_level);
                        }else{
                            $teachers_cr = $this->employee_model->get_teachers_rate($teachers_rc->current_level);
                        }
                        
                    }
                    
                    $course_rank_up = $teachers_cr->course_rank_up;

                    $old_rc      = 0;
                    $teachers_class_rate = $teachers_cr->class_rate;
                    $teacher_dt_callins = isset($dt_callins[$t->ding_emp_id]) ? $dt_callins[$t->ding_emp_id] : [];
                    $sched_checkin_data = [];

                    if (isset($checkin_sched_array) && isset($checkin_sched_array[$t->edu_id]))
                        $sched_checkin_data = $checkin_sched_array[$t->edu_id];

                    $current_level = $teachers_cr->level;
                    $teacher_dt_approvals = isset($dt_approvals[$t->ding_emp_id]) ? $dt_approvals[$t->ding_emp_id] : [];
                    $sl = $this->edu_model->get_starting_level($t->row_id,$t->user_id,$t->edu_id);
                    $teacher_sl = !empty((array)$sl) || $sl != null ? $sl->level : $t->starting_level;

                   
                    if (!empty((array)$talk_class)) {
                        if (!empty($transferred_class)) {
                            $ctr_for_append = count($talk_class);
                            foreach ($transferred_class as $ktc => $tc) {
                                $transferred_class[$ktc]['is_transferred'] = TRUE;
                                $talk_class[$ctr_for_append] = (object)$transferred_class[$ktc];
                                $ctr_for_append++;
                            }
                        }

                        $tttt = Helper::OBJECT_TO_ARRAY($talk_class);
                        usort($tttt, function($o1,$o2){
                            return $o1['talktime'] < $o2['talktime'] ? -1 : 1;
                        });

                        $talk_class = json_decode(json_encode($tttt));
                        $tmp_tc = Helper::OBJECT_TO_ARRAY($talk_class);
                        $lesson_ids = array_column($tmp_tc, 'id');
                        $lesson_ids = implode(',', $lesson_ids);

                        $video_duration = $this->video_duration($lesson_ids,$teachers_class_rate);
                        $updated_cr = 0;//level up
                        foreach ($talk_class as $k => $c) {
                            if (isset($c->is_transferred)) {
                                $transferred_type = 3;
                                $t_deduction_amount = 0.0;
                                $scheme_used = 0;
                                $deduction = 0.0;
                                if ($teachers_class_rate == 0.0 || $teachers_class_rate == 0) {
                                    $teachers_class_rate = $teachers_cr->class_rate;

                                }
                                if (!empty($teacher_dt_callins)) {
                                    foreach ($teacher_dt_callins as $x => $cc) {
                                        $create_time = strtotime($cc['create_time']);
                                        if ($create_time >= $d_from && $create_time <= $d_to) {
                                            $value_date_data  = json_decode($cc['value']['value'],true);
                                            $leave_from = $value_date_data[0];
                                            $leave_to   = $value_date_data[1];
                                            if ($c->talktime >= strtotime($leave_from)  && $c->talktime <= strtotime($leave_to)) {
                                               $ct = strtotime($create_time);
                                               $lf = strtotime($leave_from);
                                               $diff = round(abs($ct - $lf) / 60,2);
                                               if ($diff > 180) {
                                                    $deduction = ($teachers_class_rate * .80) * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used         = '80%';
                                                    $transferred_type = 11;
                                                }elseif($diff < 180){
                                                    $deduction = $teachers_class_rate * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used = '100%';
                                                    $transferred_type = 11;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }else{
                                    if (isset($dt_leaves[$t->ding_emp_id])) {
                                       $bb_ls = $dt_leaves[$t->ding_emp_id];
                                       $deduction = ($teachers_class_rate * .80) * -1;
                                       $t_deduction_amount  = $deduction;
                                       $scheme_used         = '80%';
                                       $transferred_type = 0;
                                   }
                                   
                                        /*foreach ($bb_ls as $i => $ls) {
                                            
                                        }*/
                                }

                                $transferred_class_class_rate_amount = $teachers_class_rate;
                                if ($deduction == 0.0) {
                                    $transferred_class_class_rate_amount = $transferred_class_class_rate_amount * -3;
                                    $transferred_type = 3;
                                    $t_deduction_amount  = $transferred_class_class_rate_amount;
                                    $scheme_used = '300%';
                                }else{
                                    $transferred_class_class_rate_amount = $deduction;
                                }


                                //check autotransfer
                                $at = $this->employee_model->get_auto_transfer($t->edu_id,$c->talktime);
                                if ($at != null && !empty((array)$at)) {
                                    $transferred_type = $at->auto_transfer_type;
                                    if (in_array($at->auto_transfer_type,[1,2])) {
                                        # late 10 mins prior to shift
                                        $scheme_used = '200%';
                                        $transferred_class_class_rate_amount = $teachers_class_rate * -2;
                                        $t_deduction_amount  = $transferred_class_class_rate_amount;
                                        
                                    }
                                }

                                $class_count = 0;
                                $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                
                                if ($c->talktime == 'permanent') 
                                    $transferred_class_class_rate_amount = 0;

                                $class_logs[] = [
                                    'user_id'   => $t->user_id,
                                    'edu_id'    => $t->edu_id,
                                    'teacher'   => $t->teacher,
                                    'current_level' => $teachers_cr->level,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                    'class_date_unix' => $c->talktime,
                                    'class_id'  => $c->id,
                                    'class_status'  => 'Transferred',
                                    'class_category' => 0,
                                    'class_cancel_status' => 0,
                                    'teachersignin' => 0,
                                    'class_comment' => 0,
                                    'class_count'   => $class_count,
                                    'amount'        => ($transferred_class_class_rate_amount >= 0) ? 0 : $transferred_class_class_rate_amount,
                                    'running_class_count' => $teachers_rc,
                                    'class_type'    => 'Normal',
                                    'ct_amount'     => 0.0,
                                    'class_rate' => $teachers_class_rate,
                                    'video_duration' => '--',
                                    'video_deduction' => 0.0,
                                    'total_valid_class' => $teachers_total_valid_class
                                ];
                               
                                $transferred_info[] = [
                                    'edu_id'    => $t->edu_id,
                                    'class_id'  => $c->id,
                                    't_type'    => $transferred_type,
                                    'deduction_amount'=> $t_deduction_amount,
                                    'scheme'    => $scheme_used,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                ];
                               
                            }else{
                                if ((int)$c->teachersignin == 1 AND in_array((int)$c->cancelstatus, [0,4,5]) AND (int)$c->commentstatus == 1 AND (int)$c->category != 0) {
                                   
                                    $valid_class_rate = $teachers_class_rate;
                                   
                                    if ($teachers_class_rate == 0.0 || $teachers_class_rate == 0) {
                                        $teachers_class_rate = $teachers_cr->class_rate;
                                        $valid_class_rate =  $teachers_cr->class_rate;

                                    }

                                    if ($updated_cr != 0) {
                                       $valid_class_rate = $updated_cr;
                                    }
									$class_count = 1;
                                    $class_status = 'Valid';
                                  /*  $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                    $class_status = 'Valid';
                                    if (!empty((array)$complaint_check)) {
                                        if ($complaint_check->complaint_date) {
                                            $class_count = -200;
                                            $class_status = 'Complaints';
                                        }
                                    }else{
                                        $class_count = 1;
                                    }*/

                                    $class_type = 'Normal';
                                    $ct_amount = 0.0;
                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';

                                    if (!empty($sched_checkin_data)) {
                                        $scheds = array_chunk($sched_checkin_data, 2);
                                        $sub_class_counter = 0;
                                        foreach ($scheds as $l => $s) {
                                            if (!isset($s[1]) || count($s) < 2)
                                                continue;
                                            $_cFrom = strtotime($s[0]['check_time']);
                                            $_cTo   = strtotime($s[1]['check_time']);
                                            if ($c->talktime >= $_cFrom && $c->talktime < $_cTo){
                                                $sub_class_counter++;
                                            }
                                        }

                                        if ($sub_class_counter == 0) {
                                            $check_class = $this->employee_model->check_if_class_is_tranferred($c->id);
                                            if ($check_class != null) {
                                                if ($class_status == 'Valid') {
                                                   $class_type = 'Subclass';
                                                   $ct_amount = 20.00;
                                                   $valid_class_rate += 20.00;
                                               }
                                            }
                                        }                                        
                                    }else{
                                        $class_type = 'Subclass';
                                        $ct_amount = 20.00;
                                        $valid_class_rate += 20.00;
                                    }

                                     

                                    if (isset($video_duration[$c->id])) {
                                        $vid_deduction  = $video_duration[$c->id]['deduction'];
                                        $vid_duration   = $video_duration[$c->id]['duration'];
                                        $transferred_info[] = [
                                            'edu_id'    => $t->edu_id,
                                            'class_id'  => $c->id,
                                            't_type'    => $video_duration[$c->id]['type'],
                                            'deduction_amount'=> $vid_deduction,
                                            'scheme'    => $video_duration[$c->id]['scheme'],
                                            'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        ];
                                        if ($vid_deduction < 0) {
                                            $class_count = 0;
                                            $class_status = 'Invalid';
                                            $teachers_class_rate = 0.0;
                                            if ($ct_amount > 0) {
                                               
                                                $_cRate = $valid_class_rate - 20;
                                                $valid_class_rate = $valid_class_rate + $vid_deduction;

                                            }else{
                                                $valid_class_rate = $vid_deduction;
                                            }
                                        }


                                    }else{
                                           $class_count = 0;
                                           $class_status = 'Invalid';
                                           $teachers_class_rate = 0.0;
                                           $valid_class_rate = 0;
                                    }

                                   

                                    /*if ($class_count < 0) {
                                        $diff = $class_count + $teachers_rc;
                                        if($diff < 0){
                                            $p = -1;
                                            foreach ($class_rate as $key => $value) {
                                                if ($value['level'] == $current_level) {
                                                    $p = $key-1;
                                                    break;
                                                }
                                            }

                                            if(isset($class_rate[$p]) && ($teacher_sl != $current_level)){
                                                $current_level = $class_rate[$p]['level'];
                                                $teachers_rc += $class_rate[$p]['course_rank_up'];
                                                $teachers_class_rate = $class_rate[$p]['class_rate'];
                                                $valid_class_rate = $teachers_class_rate;

                                            }
                                        }
                                    }*/
                                    $class_count = $class_count == 2 ? 1 : $class_count;
                                    $teachers_rc += $class_count;
                                    //$_firstRC_onLoop = $_firstRC_onLoop + $class_count;
                                    
                                    if ($teachers_rc > $course_rank_up) {

                                        $key_up = -1;

                                        foreach ($class_rate as $c_key => $c_val) {
                                            if ($c_val['level'] == $teachers_cr->level) {
                                                $key_up = $c_key+1;
                                                break;
                                            }
                                        }
                                        $old_cl = $current_level;
                                        $current_level = $class_rate[$key_up]['level'];
                                        $teachers_class_rate = $class_rate[$key_up]['class_rate'];
                                        $valid_class_rate = $teachers_class_rate;
                                        $old_rc      = $teachers_rc;
                                        $teachers_rc = 1;
                                        
                                        $this->employee_model->update_level($t->user_id,$current_level);
                                        $lh = [
                                            'user_id' => $t->user_id,
                                            'edu_id' => $t->edu_id,
                                            'teacher' => $t->teacher,
                                            'old_level' => $old_cl,
                                            'running_count' =>  $teachers_total_valid_class + $class_count,
                                            'level_up_count' => $course_rank_up,
                                            'current_level' => $current_level,
                                        ];
                                        $this->employee_model->insert_level_history($lh);

                                        $video_duration = $this->video_duration($lesson_ids,$valid_class_rate);
                                        $updated_cr = $valid_class_rate;
                                        $teachers_cr = $this->employee_model->get_teachers_rate($current_level);

                                    }
                                    
                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                   
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $current_level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  =>  $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate ,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                    if ($teachers_rc > $course_rank_up) {
                                        $teachers_class_rate = $valid_class_rate;
                                    }
                                }else{
                                    $ct_amount = 0.0;
                                    $class_status  = 'Invalid';
                                    $class_count = 0;
                                    $teachers_rc += $class_count;
                                    $class_type = 'Normal';
                                    $valid_class_rate = $teachers_class_rate;
                                    $is_valid = FALSE;

                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';
                                    
                                    if (!empty($teacher_dt_approvals)) {
                                        foreach ($teacher_dt_approvals as $dta) {
                                            if ($dta['class_id'] == $c->id) {
                                                $date_created = strtotime($dta['create_time']);
                                                $date_generated = strtotime($d_to);

                                                $day_diff = ($date_generated - $date_created) / 60 / 60 / 24;
                                                if ($day_diff < 4) {
                                                    if ($dta['dispute_approved']) {
                                                        $is_valid = TRUE;
                                                        $class_status = 'Valid';
                                                        $class_count = 1;
                                                        $teachers_rc += $class_count;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        if ($is_valid) {
                                          /*  $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                            $class_status = 'Valid';
                                            if (!empty((array)$complaint_check)) {
                                                if ($complaint_check->complaint_date) {
                                                    $class_count = -200;
                                                    $class_status = 'Complaints';
                                                    $valid_class_rate = $teachers_class_rate * 0;
                                                }
                                            }else{
                                                $class_count = 1;
                                            }*/

                                            if ($teachers_rc >= $course_rank_up) {
                                                $old_rc      = $teachers_rc;
                                                $teachers_rc = 0;
                                            }
                                            $ct_amount = 0.0;
                                            if (!empty($sched_checkin_data)) {
                                                $scheds = array_chunk($sched_checkin_data, 2);
                                                if (count($sched_checkin_data) > 1) {
                                                    foreach ($scheds as $l => $s) {
                                                        $_cFrom = strtotime($s[0]['check_time']);
                                                        $_cTo   = strtotime($s[1]['check_time']);

                                                        if ($c->talktime >= $_cFrom && $c->talktime < $_cTo) {
                                                            break;
                                                        }else{
                                                            $class_type = 'Subclass';
                                                            $ct_amount = 20.00;
                                                            $teachers_class_rate += 20.00;
                                                        }

                                                    }
                                                }
                                            }else{
                                                $class_type = 'Subclass';
                                                $ct_amount = 20.00;
                                                $valid_class_rate += 20.00;
                                            }

                                           

                                            if (isset($video_duration[$c->id])) {
                                                $vid_deduction  = $video_duration[$c->id]['deduction'];
                                                $vid_duration   = $video_duration[$c->id]['duration'];
                                                $transferred_info[] = [
                                                    'edu_id'    => $t->edu_id,
                                                    'class_id'  => $c->id,
                                                    't_type'    => $video_duration[$c->id]['type'],
                                                    'deduction_amount'=> $vid_deduction,
                                                    'scheme'    => $video_duration[$c->id]['scheme'],
                                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                                ];

                                                if ($vid_deduction < 0) {
                                                   $class_count = 0;
                                                   $class_status = 'Invalid';
                                                   $teachers_class_rate = 0.0;
                                                   if ($ct_amount > 0) {
                                                       $valid_class_rate = $valid_class_rate + $vid_deduction;
                                                   }else{
                                                        $valid_class_rate = $vid_deduction;
                                                   }
                                                }

                                                if ( $vid_duration  == '--') {
                                                    $valid_class_rate = 0;
                                                }

                                            }else{
                                                $class_count = 0;
                                                $class_status = 'Invalid';
                                                $valid_class_rate = 0;
                                            }

                                        }
                                        
                                    }else{
                                        if (isset($video_duration[$c->id]) && ! in_array((int)$c->cancelstatus, [1,2,3])) {
                                            $vid_deduction  = $video_duration[$c->id]['deduction'];
                                            $vid_duration   = $video_duration[$c->id]['duration'];
                                            $transferred_info[] = [
                                                'edu_id'    => $t->edu_id,
                                                'class_id'  => $c->id,
                                                't_type'    => $video_duration[$c->id]['type'],
                                                'deduction_amount'=> $vid_deduction,
                                                'scheme'    => $video_duration[$c->id]['scheme'],
                                                'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                            ];

                                            if ($vid_deduction < 0) {
                                               $class_count = 0;
                                               $class_status = 'Invalid';
                                               
                                               $teachers_class_rate = 0.0;
                                               if ($ct_amount > 0) {
                                                    $valid_class_rate = $valid_class_rate + $vid_deduction;
                                               }else{
                                                    $valid_class_rate = $vid_deduction;
                                               }
                                            }

                                            if ( $vid_duration  == '--') {
                                                $valid_class_rate = 0;
                                            }

                                        }else{
                                                $class_count = 0;
                                                $class_status = 'Invalid';
                                                $valid_class_rate = 0;
                                                $vid_duration = '--';
                                        }
                                    }

                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                    if (!$is_valid) {
                                         if ($vid_deduction < 0 ) {
                                            
                                        }else{
                                            $valid_class_rate = 0.0;
                                        }
                                        
                                    }
                                    if (!in_array((int)$c->cancelstatus, [0,4,5])) {
                                        $class_status = 'Cancelled';
                                    }
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $current_level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  => $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                }
                            }
                        }//end talk class loop

                    }//end if condition talk class
                   
                    if (!empty($class_logs)) {
                       /* if ($request->has('debug')) {
                            dd($class_logs);
                        }else{*/
                            //check for complaints
                            //$complaint_check = $this->employee_model->get_complaints($t->edu_id,$_d);
                            //if (empty($complaint_check)) {
                                $this->employee_model->transaction($class_logs,1);
                                if (!empty($transferred_info)) {
                                    $this->employee_model->transaction($transferred_info,3);
                                }
                                $batch_saved++;
                                $class_logs = [];
                                $transferred_info = [];
                            //}else{
                                //$complaint_check = OBJECT_TO_ARRAY($complaint_check);
                                //$complaint_check = ASSOCIATIVE('class_id');

                            //}
                            
                        /*}*/
                    }
                }//end teachers loop

            }
        }//end loop date range

        Log::info('earningsLog end');
        die('GENERATION DONE!');
        return response()->json(['status' => 'success','saved_data' => $batch_saved]);
    }
    //for single teachers again again again recommit recommit  recommit
    public function el_generate_teacher(Request $request){
        //Log::info('earningsLog start');
        ini_set('max_execution_time', 0);
        ini_set('memory_limit','512M');
        
       if ($request->has('d1')) {
            $d1 = $request->input('d1');
            if ($request->has('d2')) {
                $d2 = $request->input('d2');
            }else{
                $d2 = date('Y-m-d');
            }
        }else{
            $d1 = date('Y-m-d',strtotime('-1 days'));
            $d2 = date('Y-m-d',strtotime('-1 days'));
        }
        
        $dates = $this->date_range($d1,$d2);
        /*$batch_saved = 0;
        if (!empty($this->args)) {
            $args = $this->args;
            if (isset($args['dFrom'][0])) {
                $d1 = $args['dFrom'][0];
            }else{
                $d1 = date('Y-m-d',strtotime('-2 days'));
            }

            if (isset($args['dTo'][0])) {
                $d2 = $args['dTo'][0];
            }else{
                $d2 = date('Y-m-d',strtotime('-1 days'));
            }
        }else{
            $d1 = date('Y-m-d',strtotime('-2 days'));
            $d2 = date('Y-m-d',strtotime('-1 days'));
        }*/
        
        //$dates = $this->date_range($d1,$d2);
        $batch_saved = 0;
          foreach ($dates as  $_d) {

            $talk_holidays = $this->employee_model->talk_holiday(strtotime($_d));
            if ($talk_holidays != null) {
                if (strtotime($_d) >= $talk_holidays->date_from && strtotime($_d) <= $talk_holidays->date_to) {
                    //return response()->json(['status' => 'success','msg' => 'Holiday (china)']);
                    continue;
                }
            }
            if ($request->has('debug')) {
                
            }else{
                $this->employee_model->delete_earnings_log_single($_d,$request);
            }
            
            $d_from = strtotime(date('Y-m-d 00:00:00',strtotime($_d)));
            $d_to   = strtotime(date('Y-m-d 23:59:59',strtotime($_d)));

            $data = [];
            $class_logs = [];
            $transferred_info = [];

            $e_logs = $this->employee_model->get_bb_employees(TRUE,date('Y-m-d',strtotime($_d)),$request);

            if (empty((array)$e_logs)){
                /*Log::info('No employee list');
                die()*/;
                continue;
            }
            $checkin_sched = $this->employee_model->get_bb_checkin_sched($_d);
            if (!empty((array)$checkin_sched)) {
                $checkin_sched_array = Helper::OBJECT_TO_ARRAY($checkin_sched);
                $checkin_sched_array = Helper::ASSOCIATIVE_MULTI('tid',$checkin_sched_array);
            }

            if (!empty($e_logs)) {
                $teachers = $e_logs;
                $ding_ids = [];
                $edu_ids = [];
                $ctr = 0;

                foreach ($teachers as $key => $v) {
                    array_push($ding_ids, $v->ding_emp_id);
                    array_push($edu_ids, $v->edu_id);
                }
                $dt_leaves    = $this->get_dt_leave_status($ding_ids,$d_from);

                $dt_callins =  $this->dingtalk_callins(-1,$ding_ids,$d_from);

                $dt_approvals = $this->dingtalk_valid_class($ding_ids,$d_from);

                $transferred_class_data = $this->employee_model->get_all_transferred_class($edu_ids,$d_from,$d_to);

                if (!empty((array)$transferred_class_data)) {
                    $transferred_class_data = Helper::OBJECT_TO_ARRAY($transferred_class_data);
                    $transferred_class_data = Helper::ASSOCIATIVE_MULTI('teacherid',$transferred_class_data);
                }
                $class_rate = $this->employee_model->get_class_rate();
                $class_rate = Helper::OBJECT_TO_ARRAY($class_rate);
                
                foreach ($teachers as $key => $t) {
                    $talk_class = $this->employee_model->get_all_class([$t->edu_id],$d_from,$d_to);
                    
                    if (empty((array)$talk_class))
                        continue;
                    $transferred_class = isset($transferred_class_data[$t->edu_id]) ? $transferred_class_data[$t->edu_id] : [];
                    $teachers_rc = $this->employee_model->get_running_countv1($t->edu_id);
                    
                    //$_firstRC_onLoop = $teachers_rc;
                    $teachers_total_valid_class = $this->employee_model->test_countv2($t->edu_id,$t->entry_date,$d_to);
                    $first_run_rc = '';
                    
                    if ($teachers_rc == null) {

                        $teachers_rc_data = $this->employee_model->get_bb_level_history($t->edu_id);
                        
                        if ($teachers_rc_data != null) {
                            
                            $teachers_rc = $teachers_total_valid_class - $teachers_rc_data->level_up_count;
                            $check_course_rankup = $this->employee_model->get_teachers_rate($teachers_rc_data->current_level);

                            if ($teachers_rc > $check_course_rankup->course_rank_up) {
                                $total_lvlup_count_class = $this->employee_model->get_tvc_history($t->edu_id);
                                $teachers_rc = $teachers_total_valid_class - $total_lvlup_count_class;
                            }
                            $first_run_rc = $teachers_rc_data->current_level;
                        }else{
                            $teachers_rc = $teachers_total_valid_class;
                            $first_run_rc = $t->starting_level;
                        }
                    }else{
                        $rc_tmp = $teachers_rc;
                        $teachers_rc = $teachers_rc->running_class_count;
                        $teachers_total_valid_class = $rc_tmp->total_valid_class;
                    }
                    
                    if ($t->starting_level == "")
                        continue;

                    if ($first_run_rc != '') {
                        $teachers_cr = $this->employee_model->get_teachers_rate($first_run_rc);
                    }else{
                        if (isset($rc_tmp)) {
                            $teachers_cr = $this->employee_model->get_teachers_rate($rc_tmp->current_level);
                        }else{
                            $teachers_cr = $this->employee_model->get_teachers_rate($teachers_rc->current_level);
                        }
                        
                    }
                    
                    $course_rank_up = $teachers_cr->course_rank_up;

                    $old_rc      = 0;
                    $teachers_class_rate = $teachers_cr->class_rate;
                    $teacher_dt_callins = isset($dt_callins[$t->ding_emp_id]) ? $dt_callins[$t->ding_emp_id] : [];
                    $sched_checkin_data = [];

                    if (isset($checkin_sched_array) && isset($checkin_sched_array[$t->edu_id]))
                        $sched_checkin_data = $checkin_sched_array[$t->edu_id];

                    $current_level = $teachers_cr->level;
                    $teacher_dt_approvals = isset($dt_approvals[$t->ding_emp_id]) ? $dt_approvals[$t->ding_emp_id] : [];
                    $sl = $this->edu_model->get_starting_level($t->row_id,$t->user_id,$t->edu_id);
                    $teacher_sl = !empty((array)$sl) || $sl != null ? $sl->level : $t->starting_level;

                   
                    if (!empty((array)$talk_class)) {
                        if (!empty($transferred_class)) {
                            $ctr_for_append = count($talk_class);
                            foreach ($transferred_class as $ktc => $tc) {
                                $transferred_class[$ktc]['is_transferred'] = TRUE;
                                $talk_class[$ctr_for_append] = (object)$transferred_class[$ktc];
                                $ctr_for_append++;
                            }
                        }

                        $tttt = Helper::OBJECT_TO_ARRAY($talk_class);
                        usort($tttt, function($o1,$o2){
                            return $o1['talktime'] < $o2['talktime'] ? -1 : 1;
                        });

                        $talk_class = json_decode(json_encode($tttt));
                        $tmp_tc = Helper::OBJECT_TO_ARRAY($talk_class);
                        $lesson_ids = array_column($tmp_tc, 'id');
                        $lesson_ids = implode(',', $lesson_ids);

                        $video_duration = $this->video_duration($lesson_ids,$teachers_class_rate);

                        $updated_cr = 0;//level up
                        foreach ($talk_class as $k => $c) {
                            if (isset($c->is_transferred)) {
                                $transferred_type = 3;
                                $t_deduction_amount = 0.0;
                                $scheme_used = 0;
                                $deduction = 0.0;
                                if ($teachers_class_rate == 0.0 || $teachers_class_rate == 0) {
                                    $teachers_class_rate = $teachers_cr->class_rate;

                                }


                                if (!empty($teacher_dt_callins)) {
                                    foreach ($teacher_dt_callins as $x => $cc) {
                                        $create_time = strtotime($cc['create_time']);
                                        if ($create_time >= $d_from && $create_time <= $d_to) {
                                            $value_date_data  = json_decode($cc['value']['value'],true);
                                            $leave_from = $value_date_data[0];
                                            $leave_to   = $value_date_data[1];
                                            if ($c->talktime >= strtotime($leave_from)  && $c->talktime <= strtotime($leave_to)) {
                                               $ct = strtotime($create_time);
                                               $lf = strtotime($leave_from);
                                               $diff = round(abs($ct - $lf) / 60,2);
                                               if ($diff > 180) {
                                                    $deduction = ($teachers_class_rate * .80) * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used         = '80%';
                                                    $transferred_type = 11;
                                                }elseif($diff < 180){
                                                    $deduction = $teachers_class_rate * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used = '100%';
                                                    $transferred_type = 11;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                    
                                    
                                }else{
                                    if (isset($dt_leaves[$t->ding_emp_id])) {
                                       $bb_ls = $dt_leaves[$t->ding_emp_id];
                                       $deduction = ($teachers_class_rate * .80) * -1;
                                       $t_deduction_amount  = $deduction;
                                       $scheme_used         = '80%';
                                       $transferred_type = 0;
                                      
                                   }else{
                                    
                                   }
                                }

                                $transferred_class_class_rate_amount = $teachers_class_rate;
                                if ($deduction == 0.0) {
                                    $transferred_class_class_rate_amount = $transferred_class_class_rate_amount * -3;
                                    $transferred_type = 3;
                                    $t_deduction_amount  = $transferred_class_class_rate_amount;
                                    $scheme_used = '300%';
                                    
                                }else{
                                    $transferred_class_class_rate_amount = $deduction;
                                    
                                }


                                //check autotransfer
                                $at = $this->employee_model->get_auto_transfer($t->edu_id,$c->talktime);
                                if ($at != null && !empty((array)$at)) {
                                    $transferred_type = $at->auto_transfer_type;
                                    if (in_array($at->auto_transfer_type,[1,2])) {
                                        # late 10 mins prior to shift
                                        $scheme_used = '200%';
                                        $transferred_class_class_rate_amount = $teachers_class_rate * -2;
                                        $t_deduction_amount  = $transferred_class_class_rate_amount;
                                        
                                    }else{
                                        
                                    }
                                }
                               
                                $class_count = 0;
                                $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                if ($c->talktime == 'permanent') 
                                    $transferred_class_class_rate_amount = 0;
                                $class_logs[] = [
                                    'user_id'   => $t->user_id,
                                    'edu_id'    => $t->edu_id,
                                    'teacher'   => $t->teacher,
                                    'current_level' => $teachers_cr->level,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                    'class_date_unix' => $c->talktime,
                                    'class_id'  => $c->id,
                                    'class_status'  => 'Transferred',
                                    'class_category' => 0,
                                    'class_cancel_status' => 0,
                                    'teachersignin' => 0,
                                    'class_comment' => 0,
                                    'class_count'   => $class_count,
                                    'amount'        => ($transferred_class_class_rate_amount >= 0) ? 0 : $transferred_class_class_rate_amount,
                                    'running_class_count' => $teachers_rc,
                                    'class_type'    => 'Normal',
                                    'ct_amount'     => 0.0,
                                    'class_rate' => $teachers_class_rate,
                                    'video_duration' => '--',
                                    'video_deduction' => 0.0,
                                    'total_valid_class' => $teachers_total_valid_class
                                ];
                               
                                $transferred_info[] = [
                                    'edu_id'    => $t->edu_id,
                                    'class_id'  => $c->id,
                                    't_type'    => $transferred_type,
                                    'deduction_amount'=> $t_deduction_amount,
                                    'scheme'    => $scheme_used,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                ];
                               
                            }else{
                                if ((int)$c->teachersignin == 1 AND in_array((int)$c->cancelstatus, [0,4,5]) AND (int)$c->commentstatus == 1 AND (int)$c->category != 0) {
                                   
                                    $valid_class_rate = $teachers_class_rate;
                                   
                                    if ($teachers_class_rate == 0.0 || $teachers_class_rate == 0) {
                                        $teachers_class_rate = $teachers_cr->class_rate;
                                        $valid_class_rate =  $teachers_cr->class_rate;

                                    }

                                    if ($updated_cr != 0) {
                                       $valid_class_rate = $updated_cr;
                                    }
                                    $class_count = 1;
                                    $class_status = 'Valid';
                                  /*  $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                    $class_status = 'Valid';
                                    if (!empty((array)$complaint_check)) {
                                        if ($complaint_check->complaint_date) {
                                            $class_count = -200;
                                            $class_status = 'Complaints';
                                        }
                                    }else{
                                        $class_count = 1;
                                    }*/

                                    $class_type = 'Normal';
                                    $ct_amount = 0.0;
                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';

                                    if (!empty($sched_checkin_data)) {
                                        $scheds = array_chunk($sched_checkin_data, 2);
                                        $sub_class_counter = 0;
                                        foreach ($scheds as $l => $s) {
                                            if (!isset($s[1]) || count($s) < 2)
                                                continue;
                                            $_cFrom = strtotime($s[0]['check_time']);
                                            $_cTo   = strtotime($s[1]['check_time']);
                                            if ($c->talktime >= $_cFrom && $c->talktime < $_cTo){
                                                $sub_class_counter++;
                                            }
                                        }

                                        if ($sub_class_counter == 0) {
                                            $check_class = $this->employee_model->check_if_class_is_tranferred($c->id);
                                            if ($check_class != null) {
                                                if ($class_status == 'Valid') {
                                                   $class_type = 'Subclass';
                                                   $ct_amount = 20.00;
                                                   $valid_class_rate += 20.00;
                                               }
                                            }
                                        }                                        
                                    }else{
                                        $class_type = 'Subclass';
                                        $ct_amount = 20.00;
                                        $valid_class_rate += 20.00;
                                    }

                                     

                                    if (isset($video_duration[$c->id])) {
                                        $vid_deduction  = $video_duration[$c->id]['deduction'];
                                        $vid_duration   = $video_duration[$c->id]['duration'];
                                        $transferred_info[] = [
                                            'edu_id'    => $t->edu_id,
                                            'class_id'  => $c->id,
                                            't_type'    => $video_duration[$c->id]['type'],
                                            'deduction_amount'=> $vid_deduction,
                                            'scheme'    => $video_duration[$c->id]['scheme'],
                                            'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        ];
                                        if ($vid_deduction < 0) {
                                            $class_count = 0;
                                            $class_status = 'Invalid';
                                            $teachers_class_rate = 0.0;
                                            if ($ct_amount > 0) {
                                               
                                                $_cRate = $valid_class_rate - 20;
                                                $valid_class_rate = $valid_class_rate + $vid_deduction;

                                            }else{
                                                $valid_class_rate = $vid_deduction;
                                            }
                                        }


                                    }else{
                                           $class_count = 0;
                                           $class_status = 'Invalid';
                                           $teachers_class_rate = 0.0;
                                           $valid_class_rate = 0;
                                    }

                                   

                                    /*if ($class_count < 0) {
                                        $diff = $class_count + $teachers_rc;
                                        if($diff < 0){
                                            $p = -1;
                                            foreach ($class_rate as $key => $value) {
                                                if ($value['level'] == $current_level) {
                                                    $p = $key-1;
                                                    break;
                                                }
                                            }

                                            if(isset($class_rate[$p]) && ($teacher_sl != $current_level)){
                                                $current_level = $class_rate[$p]['level'];
                                                $teachers_rc += $class_rate[$p]['course_rank_up'];
                                                $teachers_class_rate = $class_rate[$p]['class_rate'];
                                                $valid_class_rate = $teachers_class_rate;

                                            }
                                        }
                                    }*/
                                    $class_count = $class_count == 2 ? 1 : $class_count;
                                    $teachers_rc += $class_count;
                                    //$_firstRC_onLoop = $_firstRC_onLoop + $class_count;
                                    
                                    if ($teachers_rc > $course_rank_up) {

                                        $key_up = -1;

                                        foreach ($class_rate as $c_key => $c_val) {
                                            if ($c_val['level'] == $teachers_cr->level) {
                                                $key_up = $c_key+1;
                                                break;
                                            }
                                        }
                                        $old_cl = $current_level;
                                        $current_level = $class_rate[$key_up]['level'];
                                        $teachers_class_rate = $class_rate[$key_up]['class_rate'];
                                        $valid_class_rate = $teachers_class_rate;
                                        $old_rc      = $teachers_rc;
                                        $teachers_rc = 1;
                                        
                                        $this->employee_model->update_level($t->user_id,$current_level);
                                        $lh = [
                                            'user_id' => $t->user_id,
                                            'edu_id' => $t->edu_id,
                                            'teacher' => $t->teacher,
                                            'old_level' => $old_cl,
                                            'running_count' =>  $teachers_total_valid_class + $class_count,
                                            'level_up_count' => $course_rank_up,
                                            'current_level' => $current_level,
                                        ];
                                        $this->employee_model->insert_level_history($lh);

                                        $video_duration = $this->video_duration($lesson_ids,$valid_class_rate);
                                        $updated_cr = $valid_class_rate;
                                        $teachers_cr = $this->employee_model->get_teachers_rate($current_level);

                                    }
                                    
                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                   
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $current_level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  =>  $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate ,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                    if ($teachers_rc > $course_rank_up) {
                                        $teachers_class_rate = $valid_class_rate;
                                    }
                                }else{
                                    $ct_amount = 0.0;
                                    $class_status  = 'Invalid';
                                    $class_count = 0;
                                    $teachers_rc += $class_count;
                                    $class_type = 'Normal';
                                    $valid_class_rate = $teachers_class_rate;
                                    $is_valid = FALSE;

                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';
                                    
                                    if (!empty($teacher_dt_approvals)) {
                                        foreach ($teacher_dt_approvals as $dta) {
                                            if ($dta['class_id'] == $c->id) {
                                                $date_created = strtotime($dta['create_time']);
                                                $date_generated = strtotime($d_to);

                                                $day_diff = ($date_generated - $date_created) / 60 / 60 / 24;
                                                if ($day_diff < 4) {
                                                    if ($dta['dispute_approved']) {
                                                        $is_valid = TRUE;
                                                        $class_status = 'Valid';
                                                        $class_count = 1;
                                                        $teachers_rc += $class_count;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        if ($is_valid) {
                                          /*  $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                            $class_status = 'Valid';
                                            if (!empty((array)$complaint_check)) {
                                                if ($complaint_check->complaint_date) {
                                                    $class_count = -200;
                                                    $class_status = 'Complaints';
                                                    $valid_class_rate = $teachers_class_rate * 0;
                                                }
                                            }else{
                                                $class_count = 1;
                                            }*/

                                            if ($teachers_rc >= $course_rank_up) {
                                                $old_rc      = $teachers_rc;
                                                $teachers_rc = 0;
                                            }
                                            $ct_amount = 0.0;
                                            if (!empty($sched_checkin_data)) {
                                                $scheds = array_chunk($sched_checkin_data, 2);
                                                if (count($sched_checkin_data) > 1) {
                                                    foreach ($scheds as $l => $s) {
                                                        $_cFrom = strtotime($s[0]['check_time']);
                                                        $_cTo   = strtotime($s[1]['check_time']);

                                                        if ($c->talktime >= $_cFrom && $c->talktime <= $_cTo) {
                                                            break;
                                                        }else{
                                                            $class_type = 'Subclass';
                                                            $ct_amount = 20.00;
                                                            $teachers_class_rate += 20.00;
                                                        }

                                                    }
                                                }
                                            }else{
                                                $class_type = 'Subclass';
                                                $ct_amount = 20.00;
                                                $valid_class_rate += 20.00;
                                            }

                                           

                                            if (isset($video_duration[$c->id])) {
                                                $vid_deduction  = $video_duration[$c->id]['deduction'];
                                                $vid_duration   = $video_duration[$c->id]['duration'];
                                                $transferred_info[] = [
                                                    'edu_id'    => $t->edu_id,
                                                    'class_id'  => $c->id,
                                                    't_type'    => $video_duration[$c->id]['type'],
                                                    'deduction_amount'=> $vid_deduction,
                                                    'scheme'    => $video_duration[$c->id]['scheme'],
                                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                                ];

                                                if ($vid_deduction < 0) {
                                                   $class_count = 0;
                                                   $class_status = 'Invalid';
                                                   $teachers_class_rate = 0.0;
                                                   if ($ct_amount > 0) {
                                                       $valid_class_rate = $valid_class_rate + $vid_deduction;
                                                   }else{
                                                        $valid_class_rate = $vid_deduction;
                                                   }
                                                }

                                                if ( $vid_duration  == '--') {
                                                    $valid_class_rate = 0;
                                                }

                                            }else{
                                                $class_count = 0;
                                                $class_status = 'Invalid';
                                                $valid_class_rate = 0;
                                            }

                                        }
                                        
                                    }else{
                                        if (isset($video_duration[$c->id]) && ! in_array((int)$c->cancelstatus, [1,2,3])) {
                                            $vid_deduction  = $video_duration[$c->id]['deduction'];
                                            $vid_duration   = $video_duration[$c->id]['duration'];
                                            $transferred_info[] = [
                                                'edu_id'    => $t->edu_id,
                                                'class_id'  => $c->id,
                                                't_type'    => $video_duration[$c->id]['type'],
                                                'deduction_amount'=> $vid_deduction,
                                                'scheme'    => $video_duration[$c->id]['scheme'],
                                                'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                            ];

                                            if ($vid_deduction < 0) {
                                               $class_count = 0;
                                               $class_status = 'Invalid';
                                               
                                               $teachers_class_rate = 0.0;
                                               if ($ct_amount > 0) {
                                                    $valid_class_rate = $valid_class_rate + $vid_deduction;
                                               }else{
                                                    $valid_class_rate = $vid_deduction;
                                               }
                                            }

                                            if ( $vid_duration  == '--') {
                                                $valid_class_rate = 0;
                                            }

                                        }else{
                                                $class_count = 0;
                                                $class_status = 'Invalid';
                                                $valid_class_rate = 0;
                                                $vid_duration = '--';
                                        }
                                    }

                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                    if (!$is_valid) {
                                         if ($vid_deduction < 0 ) {
                                            
                                        }else{
                                            $valid_class_rate = 0.0;
                                        }
                                        
                                    }
                                    if (!in_array((int)$c->cancelstatus, [0,4,5])) {
                                        $class_status = 'Cancelled';
                                    }
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $current_level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  => $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                }
                            }
                        }//end talk class loop

                    }//end if condition talk class
            
                    if (!empty($class_logs)) {
                       if ($request->has('debug')) {
                            dd($class_logs);
                        }else{
                            //check for complaints
                            //$complaint_check = $this->employee_model->get_complaints($t->edu_id,$_d);
                            //if (empty($complaint_check)) {
                            $this->employee_model->transaction($class_logs,1);
                            if (!empty($transferred_info)) {
                                $this->employee_model->transaction($transferred_info,3);
                            }
                            $batch_saved++;
                            $class_logs = [];
                            $transferred_info = [];
                            //}else{
                                //$complaint_check = OBJECT_TO_ARRAY($complaint_check);
                                //$complaint_check = ASSOCIATIVE('class_id');

                            //}
                            
                        }
                    }
                }//end teachers loop

            }
        }//end loop date range

       // Log::info('earningsLog end');
        die('GENERATION DONE!');
        return response()->json(['status' => 'success','saved_data' => $batch_saved]);
    }

    public function earnings_log(Request $request){
       ini_set('max_execution_time', 0);
       ini_set('memory_limit','512M');

        if ($request->has('d1')) {
            $d1 = $request->input('d1');
            if ($request->has('d2')) {
                $d2 = $request->input('d2');
            }else{
                $d2 = date('Y-m-d');
            }
        }else{
            $d1 = date('Y-m-d',strtotime('-1 days'));
            $d2 = date('Y-m-d',strtotime('-1 days'));
        }

        $dates = $this->date_range($d1,$d2);
        $batch_saved = 0;

        $talk_holidays = $this->employee_model->talk_holiday(strtotime($d1));
        if ($talk_holidays != null) {
            if (strtotime($d1) >= $talk_holidays->date_from && strtotime($d1) <= $talk_holidays->date_to) {
                   die('CNY HOLIDAY');
            }
        }
        //foreach ($dates as  $_d) {
            $d_from = strtotime(date('Y-m-d 00:00:00',strtotime($d1)));
            $d_to   = strtotime(date('Y-m-d 23:59:59',strtotime($d2)));

            $data = [];
            $class_logs = [];
            $transferred_info = [];
            $e_logs = $this->employee_model->get_bb_employees(TRUE,date('Y-m-d',strtotime($d1)),$request);

            if (empty((array)$e_logs))
                return response()->json(['status' => 'Falied : No employee list']);
            $checkin_sched = $this->employee_model->get_bb_checkin_sched($d1);
            if (!empty((array)$checkin_sched)) {
                $checkin_sched_array = Helper::OBJECT_TO_ARRAY($checkin_sched);
                $checkin_sched_array = Helper::ASSOCIATIVE_MULTI('tid',$checkin_sched_array);
            }

            if (!empty($e_logs)) {
                $teachers = $e_logs;
                $ding_ids = [];
                $edu_ids = [];
                $ctr = 0;

                foreach ($teachers as $key => $v) {
                    array_push($ding_ids, $v->ding_emp_id);
                    array_push($edu_ids, $v->edu_id);
                }

                $dt_leaves    = $this->get_dt_leave_status($ding_ids,$d_from);
                $dt_callins = $this->dingtalk_callins(-1,$ding_ids,$d_from);
                $dt_approvals = $this->dingtalk_valid_class($ding_ids,$d_from);

                $transferred_class_data = $this->employee_model->get_all_transferred_class($edu_ids,$d_from,$d_to);

                if (!empty((array)$transferred_class_data)) {
                    $transferred_class_data = Helper::OBJECT_TO_ARRAY($transferred_class_data);
                    $transferred_class_data = Helper::ASSOCIATIVE_MULTI('teacherid',$transferred_class_data);
                }
                $class_rate = $this->employee_model->get_class_rate();
                $class_rate = Helper::OBJECT_TO_ARRAY($class_rate);
                Log::info('TOTAL TEACHERS :' . count($teachers) . '\n' );
                foreach ($teachers as $key => $t) {
                    $talk_class = $this->employee_model->get_all_class([$t->edu_id],$d_from,$d_to);
                    if (empty((array)$talk_class))
                        continue;
                    $transferred_class = isset($transferred_class_data[$t->edu_id]) ? $transferred_class_data[$t->edu_id] : [];
                    $teachers_rc = $this->employee_model->get_running_countv1($t->edu_id);
                    $teachers_total_valid_class = $this->employee_model->test_countv2($t->edu_id,$t->entry_date,$d_to);
                    if ($teachers_rc == null) {

                        $teachers_rc_data = $this->employee_model->get_bb_level_history($t->edu_id);
                        if ($teachers_rc_data != null) {
                            $teachers_rc = $teachers_total_valid_class - $teachers_rc_data->level_up_count;
                        }else{
                            $teachers_rc = $teachers_total_valid_class;
                        }
                    }else{
                        $rc_tmp = $teachers_rc;
                        $teachers_rc = $teachers_rc->running_class_count;
                        $teachers_total_valid_class = $rc_tmp->total_valid_class;
                    }

                    if ($t->starting_level == "")
                        continue;

                    $teachers_cr = $this->employee_model->get_teachers_rate($t->starting_level);
                    $course_rank_up = $teachers_cr->course_rank_up;

                    $old_rc      = 0;
                    $teachers_class_rate = $teachers_cr->class_rate;
                    $teacher_dt_callins = isset($dt_callins[$t->ding_emp_id]) ? $dt_callins[$t->ding_emp_id] : [];
                    $sched_checkin_data = [];

                    if (isset($checkin_sched_array) && isset($checkin_sched_array[$t->edu_id]))
                        $sched_checkin_data = $checkin_sched_array[$t->edu_id];

                    $current_level = $teachers_cr->level;
                    $teacher_dt_approvals = isset($dt_approvals[$t->ding_emp_id]) ? $dt_approvals[$t->ding_emp_id] : [];
                    $sl = $this->edu_model->get_starting_level($t->row_id,$t->user_id,$t->edu_id);
                    $teacher_sl = !empty((array)$sl) || $sl != null ? $sl->level : $t->starting_level;

                    if (!empty((array)$talk_class)) {
                        if (!empty($transferred_class)) {
                            $ctr_for_append = count($talk_class);
                            foreach ($transferred_class as $ktc => $tc) {
                                $transferred_class[$ktc]['is_transferred'] = TRUE;
                                $talk_class[$ctr_for_append] = (object)$transferred_class[$ktc];
                                $ctr_for_append++;
                            }
                        }

                        $tttt = Helper::OBJECT_TO_ARRAY($talk_class);
                        usort($tttt, function($o1,$o2){
                            return $o1['talktime'] < $o2['talktime'] ? -1 : 1;
                        });

                        $talk_class = json_decode(json_encode($tttt));
                        $tmp_tc = Helper::OBJECT_TO_ARRAY($talk_class);
                        $lesson_ids = array_column($tmp_tc, 'id');
                        $lesson_ids = implode(',', $lesson_ids);
                        $video_duration = [];//$this->video_duration($lesson_ids,$teachers_class_rate);

                        foreach ($talk_class as $k => $c) {
                            if (isset($c->is_transferred)) {
                                $transferred_type = 0;
                                $t_deduction_amount = 0.0;
                                $scheme_used = 0;
                                $deduction = 0.0;

                                if (!empty($teacher_dt_callins)) {
                                    foreach ($teacher_dt_callins as $x => $cc) {
                                        $create_time = strtotime($cc['create_time']);
                                        if ($create_time >= $d_from && $create_time <= $d_to) {
                                            $value_date_data  = json_decode($cc['value']['value'],true);
                                            $leave_from = $value_date_data[0];
                                            $leave_to   = $value_date_data[1];
                                            if ($c->talktime >= strtotime($leave_from)  && $c->talktime <= strtotime($leave_to)) {
                                               $ct = strtotime($create_time);
                                               $lf = strtotime($leave_from);
                                               $diff = round(abs($ct - $lf) / 60,2);
                                               if ($diff > 180) {
                                                    $deduction = ($teachers_class_rate * .80) * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used         = '80%';
                                                    $transferred_type = 0;
                                                }elseif($diff < 180){
                                                    $deduction = $teachers_class_rate * -1;
                                                    $t_deduction_amount  = $deduction;
                                                    $scheme_used = '100%';
                                                    $transferred_type = 0;
                                                }
                                                break;
                                            }
                                        }
                                    }
                                }else{
                                    if (isset($dt_leaves[$t->ding_emp_id])) {
                                        $bb_ls = $dt_leaves[$t->ding_emp_id];
                                        $deduction = ($teachers_class_rate * .80) * -1;
                                        $t_deduction_amount  = $deduction;
                                        $scheme_used         = '80%';
                                        $transferred_type = 0;
                                        /*foreach ($bb_ls as $i => $ls) {
                                            
                                        }*/
                                    }
                                }

                                $transferred_class_class_rate_amount = $teachers_class_rate;
                                if ($deduction == 0.0) {
                                    $transferred_class_class_rate_amount = $transferred_class_class_rate_amount * -3;
                                    $transferred_type = 1;
                                    $t_deduction_amount  = $transferred_class_class_rate_amount;
                                    $scheme_used = '300%';
                                }else{
                                    $transferred_class_class_rate_amount = $deduction;
                                }

                                $class_count = 0;
                                $teachers_total_valid_class = $teachers_total_valid_class + $class_count;

                                $class_logs[] = [
                                    'user_id'   => $t->user_id,
                                    'edu_id'    => $t->edu_id,
                                    'teacher'   => $t->teacher,
                                    'current_level' => $teachers_cr->level,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                    'class_date_unix' => $c->talktime,
                                    'class_id'  => $c->id,
                                    'class_status'  => 'Transferred',
                                    'class_category' => 0,
                                    'class_cancel_status' => 0,
                                    'teachersignin' => 0,
                                    'class_comment' => 0,
                                    'class_count'   => $class_count,
                                    'amount'        => ($transferred_class_class_rate_amount >= 0) ? 0 : $transferred_class_class_rate_amount,
                                    'running_class_count' => $teachers_rc,
                                    'class_type'    => 'Normal',
                                    'ct_amount'     => 0.0,
                                    'class_rate' => $teachers_class_rate,
                                    'video_duration' => '--',
                                    'video_deduction' => 0.0,
                                    'total_valid_class' => $teachers_total_valid_class
                                ];
                                $transferred_info[] = [
                                    'edu_id'    => $t->edu_id,
                                    'class_id'  => $c->id,
                                    't_type'    => $transferred_type,
                                    'deduction_amount'=> $t_deduction_amount,
                                    'scheme'    => $scheme_used,
                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                ];

                            }else{
                                if ((int)$c->teachersignin == 1 AND in_array((int)$c->cancelstatus, [0,4,5]) AND (int)$c->commentstatus == 1 AND (int)$c->category != 0) {
                                    $valid_class_rate = $teachers_class_rate;
                                    if ($teachers_class_rate == 0.0 || $teachers_class_rate == 0) {
                                        $teachers_class_rate = $teachers_cr->class_rate;
                                        $valid_class_rate =  $teachers_cr->class_rate;
                                    }
                                    /*$complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                    $class_status = 'Valid';
                                    if (!empty((array)$complaint_check)) {
                                        if ($complaint_check->complaint_date) {
                                            $class_count = -200;
                                            $class_status = 'Complaints';
                                        }
                                    }else{
                                        $class_count = 1;
                                    }*/

                                    $class_type = 'Normal';
                                    $ct_amount = 0.0;
                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';

                                    if (!empty($sched_checkin_data)) {
                                        $scheds = array_chunk($sched_checkin_data, 2);
                                        $sub_class_counter = 0;
                                        foreach ($scheds as $l => $s) {
                                            if (!isset($s[1]) || count($s) < 2)
                                                continue;
                                            $_cFrom = strtotime($s[0]['check_time']);
                                            $_cTo   = strtotime($s[1]['check_time']);
                                            if ($c->talktime >= $_cFrom && $c->talktime <= $_cTo){
                                                $sub_class_counter++;
                                            }
                                        }

                                        if ($sub_class_counter == 0) {
                                            $check_class = $this->employee_model->check_if_class_is_tranferred($c->id);
                                            if ($check_class != null) {
                                                if ($class_status == 'Valid') {
                                                   $class_type = 'Subclass';
                                                   $ct_amount = 20.00;
                                                   $valid_class_rate += 20.00;
                                               }
                                            }
                                        }                                        
                                    }else{
                                        $class_type = 'Subclass';
                                        $ct_amount = 20.00;
                                        $valid_class_rate += 20.00;
                                    }

                                    if (isset($video_duration[$c->id])) {
                                        $vid_deduction  = $video_duration[$c->id]['deduction'];
                                        $vid_duration   = $video_duration[$c->id]['duration'];
                                        $transferred_info[] = [
                                            'edu_id'    => $t->edu_id,
                                            'class_id'  => $c->id,
                                            't_type'    => $video_duration[$c->id]['type'],
                                            'deduction_amount'=> $vid_deduction,
                                            'scheme'    => $video_duration[$c->id]['scheme'],
                                            'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        ];
                                        if ($vid_deduction < 0) {
                                            $class_count = 0;
                                            $class_status = 'Invalid';
                                            $teachers_class_rate = 0.0;
                                            if ($ct_amount > 0) {
                                                $_cRate = $valid_class_rate - 20;
                                                $valid_class_rate = $valid_class_rate + $vid_deduction;
                                            }else{
                                                $valid_class_rate = $vid_deduction;
                                            }
                                            
                                        }


                                    }else{
                                        if ($request->has('remove_vid')) {
                                        # code...
                                        }else{
                                           $class_count = 0;
                                           $class_status = 'Invalid';
                                           $teachers_class_rate = 0.0;
                                           $valid_class_rate = 0;
                                       }
                                    }

                                    

                                    /*if ($class_count < 0) {
                                        $diff = $class_count + $teachers_rc;
                                        if($diff < 0){
                                            $p = -1;
                                            foreach ($class_rate as $key => $value) {
                                                if ($value['level'] == $current_level) {
                                                    $p = $key-1;
                                                    break;
                                                }
                                            }
                                            if(isset($class_rate[$p]) && ($teacher_sl != $current_level)){
                                                $current_level = $class_rate[$p]['level'];
                                                $teachers_rc += $class_rate[$p]['course_rank_up'];
                                                $teachers_class_rate = $class_rate[$p]['class_rate'];
                                                $valid_class_rate = $teachers_class_rate;

                                            }
                                        }
                                    }*/

                                    $teachers_rc += $class_count;
                                    if ($teachers_rc > $course_rank_up) {

                                        $key_up = -1;

                                        foreach ($class_rate as $c_key => $c_val) {
                                            if ($c_val['level'] == $teachers_cr->level) {
                                                $key_up = $c_key+1;
                                                break;
                                            }
                                        }

                                        $current_level = $class_rate[$key_up]['level'];
                                        $teachers_class_rate = $class_rate[$key_up]['class_rate'];
                                        $valid_class_rate = $teachers_class_rate;
                                        $old_rc      = $teachers_rc;
                                        $teachers_rc = 1;

                                    }

                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $current_level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  =>  $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate ,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                    if ($teachers_rc > $course_rank_up) {
                                        $teachers_class_rate = $valid_class_rate;
                                    }
                                }else{
                                    $ct_amount = 0.0;
                                    $class_status  = 'Invalid';
                                    $class_count = 0;
                                    $teachers_rc += $class_count;
                                    $class_type = 'Normal';
                                    $valid_class_rate = $teachers_class_rate;
                                    $is_valid = FALSE;

                                    $vid_deduction = 0.0;
                                    $vid_duration  = '--';

                                    if (!empty($teacher_dt_approvals)) {
                                        foreach ($teacher_dt_approvals as $dta) {
                                            if ($dta['class_id'] == $c->id) {
                                                $date_created = strtotime($dta['create_time']);
                                                $date_generated = strtotime($d_to);

                                                $day_diff = ($date_generated - $date_created) / 60 / 60 / 24;
                                                if ($day_diff < 4) {
                                                    if ($dta['dispute_approved']) {
                                                        $is_valid = TRUE;
                                                        $class_status = 'Valid';
                                                        $class_count = 1;
                                                        $teachers_rc += $class_count;
                                                        break;
                                                    }
                                                }
                                            }
                                        }

                                        if ($is_valid) {
                                           /* $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                            $class_status = 'Valid';
                                            if (!empty((array)$complaint_check)) {
                                                if ($complaint_check->complaint_date) {
                                                    $class_count = -200;
                                                    $class_status = 'Complaints';
                                                    $valid_class_rate = $teachers_class_rate * 0;
                                                }
                                            }else{
                                                $class_count = 1;
                                            }*/

                                            if ($teachers_rc >= $course_rank_up) {
                                                $old_rc      = $teachers_rc;
                                                $teachers_rc = 0;
                                            }
                                            $ct_amount = 0.0;
                                            if (!empty($sched_checkin_data)) {
                                                $scheds = array_chunk($sched_checkin_data, 2);
                                                if (count($sched_checkin_data) > 1) {
                                                    foreach ($scheds as $l => $s) {
                                                        $_cFrom = strtotime($s[0]['check_time']);
                                                        $_cTo   = strtotime($s[1]['check_time']);

                                                        if ($c->talktime >= $_cFrom && $c->talktime <= $_cTo) {
                                                            break;
                                                        }else{
                                                            $class_type = 'Subclass';
                                                            $ct_amount = 20.00;
                                                            $teachers_class_rate += 20.00;
                                                        }

                                                    }
                                                }
                                            }else{
                                               $class_type = 'Subclass';
                                               $ct_amount = 20.00;
                                               $valid_class_rate += 20.00;
                                            }

                                            if (isset($video_duration[$c->id])) {
                                                $vid_deduction  = $video_duration[$c->id]['deduction'];
                                                $vid_duration   = $video_duration[$c->id]['duration'];
                                                $transferred_info[] = [
                                                    'edu_id'    => $t->edu_id,
                                                    'class_id'  => $c->id,
                                                    't_type'    => $video_duration[$c->id]['type'],
                                                    'deduction_amount'=> $vid_deduction,
                                                    'scheme'    => $video_duration[$c->id]['scheme'],
                                                    'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                                ];

                                                if ($vid_deduction < 0) {
                                                    $class_count = 0;
                                                    $class_status = 'Invalid';
                                                    $teachers_class_rate = 0.0;
                                                    if ($ct_amount > 0) {
                                                        $_cRate = $valid_class_rate - 20;
                                                        $valid_class_rate = $valid_class_rate + $vid_deduction;
                                                    }else{
                                                        $valid_class_rate = $vid_deduction;
                                                    }
                                                }

                                                if ( $vid_duration  == '--') {
                                                    $valid_class_rate = 0;
                                                }

                                            }else{
                                               $class_count = 0;
                                               $class_status = 'Invalid';
                                               $valid_class_rate = 0;
                                            }

                                        }
                                    }else{
                                        if (isset($video_duration[$c->id])) {
                                            $vid_deduction  = $video_duration[$c->id]['deduction'];
                                            $vid_duration   = $video_duration[$c->id]['duration'];
                                            $transferred_info[] = [
                                                'edu_id'    => $t->edu_id,
                                                'class_id'  => $c->id,
                                                't_type'    => $video_duration[$c->id]['type'],
                                                'deduction_amount'=> $vid_deduction,
                                                'scheme'    => $video_duration[$c->id]['scheme'],
                                                'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                            ];

                                            if ($vid_deduction < 0) {
                                               $class_count = 0;
                                               $class_status = 'Invalid';
                                               $teachers_class_rate = 0.0;
                                               $valid_class_rate = $vid_deduction;
                                            }

                                            if ( $vid_duration  == '--') {
                                                $valid_class_rate = 0;
                                            }

                                        }else{
                                            if ($request->has('remove_vid')) {

                                            }else{
                                                $class_count = 0;
                                                $class_status = 'Invalid';
                                                $valid_class_rate = 0;
                                            }
                                        }
                                    }

                                    $teachers_total_valid_class = $teachers_total_valid_class + $class_count;

                                    if (!$is_valid) {
                                         if ($vid_deduction < 0 ) {
                                            
                                        }else{
                                            $valid_class_rate = 0.0;
                                        }
                                        
                                    }
                                    $class_logs[] = [
                                        'user_id'   => $t->user_id,
                                        'edu_id'    => $t->edu_id,
                                        'teacher'   => $t->teacher,
                                        'current_level' => $teachers_cr->level,
                                        'class_date' => date('Y-m-d H:i:s',$c->talktime),
                                        'class_date_unix' => $c->talktime,
                                        'class_id'  => $c->id,
                                        'class_status'  => $class_status,
                                        'class_category' => $c->category,
                                        'class_cancel_status' => $c->cancelstatus,
                                        'teachersignin' => $c->teachersignin,
                                        'class_comment' => $c->commentstatus,
                                        'class_count'   => $class_count,
                                        'amount'        => $valid_class_rate,
                                        'running_class_count' => $teachers_rc,
                                        'class_type'    => $class_type,
                                        'ct_amount'     => $ct_amount,
                                        'class_rate' => $teachers_class_rate,
                                        'video_duration' => $vid_duration,
                                        'video_deduction' => $vid_deduction,
                                        'total_valid_class' => $teachers_total_valid_class
                                    ];

                                }
                            }
                        }//end talk class loop
                    }//end if condition talk class
                    if (!empty($class_logs)) {
                        if ($request->has('debug')) {
                            dd($class_logs);
                        }else{
                            $complaint_check = null;//$this->employee_model->get_complaints($t->edu_id,$d1);

                            if ($complaint_check == null) {
                                 $this->employee_model->transaction($class_logs,1);
                                 $batch_saved++;
                                 Log::info('TEACHERS SAVED :' . $batch_saved . '\n' );
                                 if (!empty($transferred_info)) {
                                    $this->employee_model->transaction($transferred_info,3);
                                }
                                $class_logs = [];
                                $transferred_info = [];
                            }else{
                                $complaint_check = Helper::OBJECT_TO_ARRAY($complaint_check);
                                $complaint_check = Helper::ASSOCIATIVE('class_id',$complaint_check);
                                $tmp_class_logs = [];
                                $last_log = end($class_logs);
                                $last_log_running_count = $last_log['running_class_count'];
                                foreach ($class_logs as $key => $cl) {
                                    if (isset($complaint_check[$cl['class_id']])) {
                                        $_complaint = $complaint_check[$cl['class_id']];
                                        $new_rc = $last_log_running_count - 200;
                                        $tmp_class_logs[] = [
                                            'user_id'   => $cl['user_id'],
                                            'edu_id'    => $cl['edu_id'],
                                            'teacher'   => $cl['teacher'],
                                            'current_level' => $cl['current_level'],
                                            'class_date' => $cl['class_date'],
                                            'class_date_unix' => $cl['class_date_unix'],
                                            'class_id'  => $cl['class_id'],
                                            'class_status'  => 'Complaint',
                                            'class_category' => $cl['class_category'],
                                            'class_cancel_status' => $cl['class_cancel_status'],
                                            'teachersignin' => $cl['teachersignin'],
                                            'class_comment' => $cl['class_comment'],
                                            'class_count'   => -200,
                                            'amount'        => $cl['amount'],
                                            'running_class_count' => $new_rc,
                                            'class_type'    => $cl['class_type'],
                                            'ct_amount'     => $cl['ct_amount'],
                                            'class_rate' => $cl['class_rate'],
                                            'video_duration' => $cl['video_duration'],
                                            'video_deduction' => $cl['video_deduction'],
                                            'total_valid_class' => $cl['total_valid_class']
                                        ];
                                        $last_log_running_count = $new_rc;
                                    }
                                }

                                if (!empty($tmp_class_logs))
                                    $class_logs = array_merge($class_logs,$tmp_class_logs);

                                $this->employee_model->transaction($class_logs,1);
                                $batch_saved++;
                                 Log::info('TEACHERS SAVED :' . $batch_saved . '\n' );
                                 if (!empty($transferred_info)) {
                                    $this->employee_model->transaction($transferred_info,3);
                                }
                                $class_logs = [];
                                $transferred_info = [];
                            }
                            
                        }
                    }
                }//end teachers loop
            }
            Log::info('GENERATE DONE!' );
        //}//end loop date range
        die('GENERATION DONE!');
        return response()->json(['status' => 'success','saved_data' => $batch_saved]);
    }

    public function generate_earnings(){

    }

    public function dingtalk_callins($id,$dingid,$from = '',$to = '',$class = [],$clss_rate = 0.0){

        $process_id = '';
        switch (env('APP_ENV')) {
            case 'prod' :
                $dingtalk_proc_list = env('DT_PROCLIST_PROD');
                $dingtalk_proc_instance = env('DT_PROCINST_PROD');
                $process_id = env('CALLINS_PROCID_PROD');
                break;
            case 'test' :
                $dingtalk_proc_list = env('DT_PROCLIST_TEST');
                $dingtalk_proc_instance = env('DT_PROCINST_TEST');
                $process_id = env('CALLINS_PROCID_TEST');
                break;
            default:
                 $dingtalk_proc_list = env('DT_PROCLIST_TEST');
                $dingtalk_proc_instance = env('DT_PROCINST_TEST');
                $process_id = env('CALLINS_PROCID_TEST');

        }
        $ding_id_chunks = array_chunk($dingid, 5);
        $all_proc_id = [];
        foreach ($ding_id_chunks as  $dic) {
            $params = [
                'process_code'  => $process_id,
                'userid_list[]' => implode(',', $dic),
                'start_time'    => $from,
                'offset'        => '0',
                'limit'         => '10' ,
            ];
            retry:
            $data = json_decode(Helper::FETCH_CURL($dingtalk_proc_list,$params),true);

            if (isset($data['error']) && $data['error'] == 'Timeout') {
                goto retry;
            }


            $has_next_cursor = FALSE;
            if (isset($data['data']['result']['list']) && !empty($data['data']['result']['list'])) {
                $all_proc_id = array_merge($all_proc_id,$data['data']['result']['list']);
                if (isset($data['data']['result']['next_cursor'])) {
                    $has_next_cursor = TRUE;
                }else{
                    $has_next_cursor = FALSE;
                }
            }
            
            $next_cursor = 2;
            while ($has_next_cursor) {
                $params['offset'] = $next_cursor;
                retry2:
                $data = json_decode(Helper::FETCH_CURL($dingtalk_proc_list,$params),true);
                if (isset($data['error']) && $data['error'] == 'Timeout') {
                    goto retry2;
                }


                if (!empty($data['data']) && isset($data['data']['result']['list'])) {
                    $all_proc_id = array_merge($all_proc_id,$data['data']['result']['list']);
                    if (isset($data['data']['result']['next_cursor'])) {
                        $has_next_cursor = TRUE;
                        $next_cursor++;
                    }else{
                        $has_next_cursor = FALSE;
                    }
                }else{
                    $has_next_cursor = FALSE;
                }

            }


        }

        $leave_data = [];
        $talk_lessons = [];
        $all_bb_call_ins = [];

        if (!empty($all_proc_id)) {
            foreach ($all_proc_id as $key => $list_id) {
                $process_instance_id = $list_id;
                 $params = [
                    'process_instance_id'   => "{$process_instance_id}",
                ];
                retry3:
                $process_instance_data = json_decode(Helper::FETCH_CURL($dingtalk_proc_instance,$params),true);
                //dingtlak id $process_instance_data['data']['process_instance']['originator_userid'];

                if (!empty($process_instance_data) && (isset($process_instance_data['error']) && $process_instance_data['error'] == 'Timeout'))
                    goto retry3;

                if (!empty($process_instance_data['data']) && isset($process_instance_data['data']['process_instance'])) {
                    $process_instance = $process_instance_data['data']['process_instance'];
                    $form_component_values = $process_instance['form_component_values'];
                    if (!empty($form_component_values)) {
                        foreach ($form_component_values as  $fcv) {
                            if ($fcv['component_type'] == 'DDHolidayField') {
                                $all_bb_call_ins[$process_instance_data['data']['process_instance']['originator_userid']][] = [
                                    'create_time' => $process_instance['create_time'],
                                    'value' => $fcv,
                                ];
                            }

                        }
                    }


                }

            }
        }
        return $all_bb_call_ins;
    }

    public function dingtalk_valid_class($ding_ids,$from = ''){
        $process_id = '';
        switch (env('APP_ENV')) {
            case 'prod' :
                $dingtalk_proc_list = env('DT_PROCLIST_PROD');
                $dingtalk_proc_instance = env('DT_PROCINST_PROD');
                $process_id = env('VALIDCLASS_PROCID_PROD');
                break;
            case 'test' :
                $dingtalk_proc_list = env('DT_PROCLIST_TEST');
                $dingtalk_proc_instance = env('DT_PROCINST_TEST');
                $process_id = env('VALIDCLASS_PROCID_TEST');
                break;
            default:
                $dingtalk_proc_list = env('DT_PROCLIST_TEST');
                $dingtalk_proc_instance = env('DT_PROCINST_TEST');
                $process_id = env('VALIDCLASS_PROCID_TEST');

        }

        $ding_id_chunks = array_chunk($ding_ids, 5);
        $all_proc_id = [];

        foreach ($ding_id_chunks as  $dic) {
            $dids = implode(',', $dic);
            $params = [
                'process_code'  => $process_id,
                'userid_list[]' => $dids,
                'start_time'    => $from,
                'offset'        => '1',
                'limit'         => '10' ,
            ];

            retry:
            $data = json_decode(Helper::FETCH_CURL($dingtalk_proc_list,$params),true);
            if (isset($data['error']) && $data['error'] == 'Timeout') {
                goto retry;
            }


            $has_next_cursor = FALSE;
            if (isset($data['data']['result']['list']) && !empty($data['data']['result']['list'])) {
                $all_proc_id = array_merge($all_proc_id,$data['data']['result']['list']);
                if (isset($data['data']['result']['next_cursor'])) {
                    $has_next_cursor = TRUE;
                }else{
                    $has_next_cursor = FALSE;
                }
            }
            ;
            $next_cursor = 2;
            while ($has_next_cursor) {
                $params['offset'] = $next_cursor;
                retry2:
                $data = json_decode(Helper::FETCH_CURL($dingtalk_proc_list,$params),true);
                if (isset($data['error']) && $data['error'] == 'Timeout') {
                    goto retry2;
                }


                if (!empty($data['data']) && isset($data['data']['result']['list'])) {
                    $all_proc_id = array_merge($all_proc_id,$data['data']['result']['list']);
                    if (isset($data['data']['result']['next_cursor'])) {
                        $has_next_cursor = TRUE;
                        $next_cursor++;
                    }else{
                        $has_next_cursor = FALSE;
                    }
                }else{
                    $has_next_cursor = FALSE;
                }

            }


        }

        $leave_data = [];
        $talk_lessons = [];
        $all_bb_call_ins = [];

        if (!empty($all_proc_id)) {
            foreach ($all_proc_id as $key => $list_id) {
                $process_instance_id = $list_id;
                 $params = [
                    'process_instance_id'   => "{$process_instance_id}",
                ];
                retry3:
                $process_instance_data = json_decode(Helper::FETCH_CURL($dingtalk_proc_instance,$params),true);
                if (!empty($process_instance_data) && (isset($process_instance_data['error']) && $process_instance_data['error'] == 'Timeout'))
                    goto retry3;
                if (!empty($process_instance_data['data']) && isset($process_instance_data['data']['process_instance'])) {
                    $process_instance = $process_instance_data['data']['process_instance'];
                    $form_component_values = $process_instance['form_component_values'];
                    $class_id = '';
                    $user_ding_id = $process_instance['originator_userid'];
                    if (!empty($form_component_values)) {
                        foreach ($form_component_values as  $fcv) {
                            if (isset($fcv['name']) && $fcv['name'] == 'Class ID') {
                                $class_id = $fcv['value'];
                                break;
                            }
                        }
                    }
                    $dispute_approval = FALSE;
                    if (isset($process_instance['result']) && $process_instance['result'] == 'agree') {
                        $dispute_approval = TRUE;
                    }else{
                        $dispute_approval = FALSE;
                    }

                    $all_bb_call_ins[$user_ding_id][] = [
                        'class_id' => $class_id,
                        'dingtalk_id' => $user_ding_id,
                        'dispute_approved' => $dispute_approval,
                        'create_time' => $process_instance['create_time']

                    ];

                }

            }
        }

        return $all_bb_call_ins;
    }

    public function get_dt_leave_status($ding_ids,$date){
        if (!empty($ding_ids)) {
            switch (env('APP_ENV')) {
                case 'prod' :
                    $dt_leave_status_url = env('DT_LEAVE_STATUS_PROD');
                    break;
                case 'test' :
                    $dt_leave_status_url = env('DT_LEAVE_STATUS_TEST');
                    break;
                default:
                    $dt_leave_status_url = env('DT_LEAVE_STATUS_PROD');
            }

            $ding_id_chunks = array_chunk($ding_ids, 10);

            $params = [
                'start_time' => strtotime(date('Y-m-d 00:00:00',$date)).'000',
                'end_time'   => strtotime(date('Y-m-d 23:59:59',$date)).'000',

            ];
            
            $ls = [];
            foreach ($ding_id_chunks as $k => $ids) {
                $params['userid_list[]'] = implode(',', $ids);

                retry:
                $tmp = json_decode(Helper::FETCH_CURL($dt_leave_status_url,$params),true);

                if (isset($tmp['error']))
                    goto retry;
                
                $result = $tmp['data']['result'];
                if (empty($result['leave_status'])) 
                    continue;
                $ls = array_merge($ls,$result['leave_status']);
                if ($result['has_more']) {
                    # code...
                }
            }

            if (!empty($ls)) {
                $ls = Helper::ASSOCIATIVE_MULTI('userid',$ls);
                return $ls;
            }
        }
        return [];
    }

    public function date_range($first, $last, $step = '+1 day', $output_format = 'Y-m-d' ) {

        $dates = array();
        $current = strtotime($first);
        $last = strtotime($last);

        while( $current <= $last ) {

            $dates[] = date($output_format, $current);
            $current = strtotime($step, $current);
        }

        return $dates;
    }

    public function generate_starting_level(){
        $bb_teachers = $this->employee_model->get_bb_employees();
        $bb_class_rate = $this->employee_model->get_class_rate();
        $level_history = [];

        foreach ($bb_teachers as $key => $bb) {
            //get accumulated classes of teachers
            $teacher_level =$bb->starting_level;
            $ac = $this->employee_model->get_acc($bb->edu_id,$bb->entry_date);
            $complaints = $this->employee_model->get_all_complaints($bb->edu_id);
            $complaints_class_deduction = $complaints * 200;
            $ac = $ac - $complaints_class_deduction;
            $ac_temp = $ac;
            $tmp_cr = [];
            foreach ($bb_class_rate as $cr) {
                if ($cr->level ==  $teacher_level){
                    $tmp_cr[] = (array)$cr;
                    break;
                }
                $tmp_cr[] = (array)$cr;
            }
            $tmp_cr = array_reverse($tmp_cr);

            //Helper::PRINT_DUMP($tmp_cr);
            foreach ($tmp_cr as $i => $tmp) {

                if (!isset($level_history[$bb->edu_id])) {
                    if (isset($tmp_cr[$i+1])) {
                        $ac = $ac - $tmp_cr[$i+1]['course_rank_up'];
                        if ($ac < 0) {
                            $level_history[$bb->edu_id][] = [
                                'user_id' => $bb->user_id,
                                'edu_id' => $bb->edu_id,
                                'teacher' => $bb->teacher,
                                'level' => $tmp['level'],
                                'running_count' => $ac_temp,
                                'current_level' => 1
                            ];
                        }else{
                            $level_history[$bb->edu_id][] = [
                                'user_id' => $bb->user_id,
                                'edu_id' => $bb->edu_id,
                                'teacher' => $bb->teacher,
                                'level' => $tmp['level'],
                                'running_count' => $ac,
                                'current_level' => 1
                            ];
                        }

                    }
                }else{
                    if ( $ac > 0 &&  (($ac + $tmp['course_rank_up']) ==  $ac_temp)){
                        $level_history[$bb->edu_id][] = [
                            'user_id' => $bb->user_id,
                            'edu_id' => $bb->edu_id,
                            'teacher' => $bb->teacher,
                            'level' => $tmp['level'],
                            'running_count' => $tmp['course_rank_up'],
                            'current_level' => 0
                        ];
                        break;
                    }
                }

                /*if ($tmp['course_rank_up'] <= $ac) {
                    $ac = $ac - $tmp['course_rank_up'];
                    $level_history[$bb->edu_id][] = [
                        'user_id' => $bb->user_id,
                        'edu_id' => $bb->edu_id,
                        'teacher' => $bb->teacher,
                        'level' => $tmp['level'],
                        'running_count' => $ac
                    ];
                }else{
                    if (($ac + $tmp['course_rank_up']) ==  $ac_temp){
                        $level_history[$bb->edu_id][] = [
                            'user_id' => $bb->user_id,
                            'edu_id' => $bb->edu_id,
                            'teacher' => $bb->teacher,
                            'level' => $tmp['level'],
                            'running_count' => $tmp['course_rank_up']
                        ];
                        break;
                    }

                }*/
            }

        }

        $this->employee_model->transaction($level_history,2);


    }

    public function test_video_duration(Request $request){
         if ($request->has('d1')) {
            $d1 = $request->input('d1');
            if ($request->has('d2')) {
                $d2 = $request->input('d2');
            }else{
                $d2 = date('Y-m-d');
            }
        }else{
            $d1 = date('Y-m-d');
            $d2 = date('Y-m-d');
        }

        $dates = $this->date_range($d1,$d2);

        foreach ($dates as  $_d) {
            $d_from = strtotime(date('Y-m-d 00:00:00',strtotime($_d)));
            $d_to   = strtotime(date('Y-m-d 23:59:59',strtotime($_d)));
            $data = [];
            $class_logs = [];
            $e_logs = $this->employee_model->get_bb_employees(TRUE,date('Y-m-d',strtotime($_d)));

            foreach ($e_logs as $key => $t) {
                $talk_class = $this->employee_model->get_all_class([$t->edu_id],$d_from,$d_to);
                $tmp_tc = Helper::OBJECT_TO_ARRAY($talk_class);
                $lesson_ids = array_column($tmp_tc, 'id');
                $lesson_ids = implode(',', $lesson_ids);
                if (!empty((array)$talk_class)) {
                    $this->video_duration($lesson_ids,80);
                }

            }

        }//end main loop


    }

    public function video_duration($class_id,$class_rate){
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
            'lessonid[]' => $class_id
        ];
        retry:
        $vd = json_decode(Helper::FETCH_CURL($videoduration_api,$params),true);

        if (isset($vd['error']) && $vd['error'] == 'Timeout')
            goto retry;

        $data_vd = [];
        if (!empty($vd['data'])) {
            $vd_data = $vd['data']['list'];
            //Helper::PRINT_DUMP($vd_data);
            if (!empty($vd_data)) {

                foreach ($vd_data as $key => $c) {
                    if ($c['call_time'] > strtotime('2020-03-22')) {
                       $_duration =  $c['client_statis_time'] ? $this->secondMinute($c['client_statis_time']) : 0;
                       //$_duration = $c['client_statis_time'] ? $this->secondMinute($c['client_statis_time']) : 0;
                    }else{
                        $_duration =  $c['teacher_all_time'] ? $this->secondMinute($c['teacher_all_time']) : 0;
                       //$_duration = $c['client_statis_time'] ? $this->secondMinute($c['client_statis_time']) : 0;
                    }
                       
                       $deduction = 0.0;
                       $scheme = 0;
                       $type = -1;
                       if ($_duration >= 20 && $_duration < 25) {
                           $deduction =  ($class_rate * .50) * -1;
                           $scheme = .50;
                           $type = 3;
                       }elseif($_duration >= 10 && $_duration < 20){
                           $deduction =  ($class_rate * 1) * -1;
                           $scheme = 1;
                           $type = 4;
                       }elseif($_duration < 10){
                           $deduction =  ($class_rate * 1.5) * -1;
                           $scheme = 1.5;
                           $type = 5;
                       }

                       $data_vd[$c['lessonid']] = [
                        'duration' => $_duration,
                        'deduction' => $deduction,
                        'lessonid' => $c['lessonid'],
                        'scheme' => $scheme,
                        'type' => $type,
                       ];
                }
            }
        }
        return $data_vd;
    }

    public function secondMinute($seconds){

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

    public function generate_bb_payroll(Request $request,$key = 0){
        $date = strtotime(date('Y-m-d'));
        if($date >= strtotime(date('Y-m-01')) && $date <= strtotime(date('Y-m-15'))){
            $from = date('Y-m-16',strtotime('-1 month'));
            $to = date('Y-m-t',strtotime('-1 month'));
            $cutoff = 1;
        }else{
            $from = date('Y-m-01');
            $to = date('Y-m-15');
            $cutoff = 2;
        }
        $current_date = date('Y-m-d');

        $employee = $this->pay_model->get_bb_teachers($from,$to);
        if (!empty((array)$employee)) {
            $total_teachers = count($employee);
            $last_index     = $total_teachers - 1;
            if ($key > $last_index)
                return response()->json(['status' => TRUE,'msg' => 'Success']);

            $params = [
                'user_id'   => $employee[$key]->edu_id,
                'from'      => $from,
                'to'        => $to,
                'ding_id'   => $employee[$key]->ding_emp_id,
                'emp_name'  => $employee[$key]->lastName . ', ' . $employee[$key]->firstName . ' ' . $employee[$key]->middleName  ,
                'cutoff'    => $cutoff,
                'key'       => $key,
                'data'      => $employee[$key]
            ];

            if ($request->has('new') && $request->input('new') == TRUE) {
                if ($key == 0)
                    //delete

                $response = [
                    'status'    => TRUE,
                    'total'     => count($employee),
                    'key'       => $key,
                ];

                return response()->json($response);
            }

            $generate = $this->generate($params);
            if ($generate) {
                $key++;
                if (isset($employee[$key])) {
                    $response = [
                    'status' => TRUE,
                    'total'  => count($employee),
                    'key'    => $key,
                    'data_check' => $employee[$key],
                    ];
                }else{
                    $response = [
                    'status' => TRUE,
                    'total'  => count($employee),
                    'key'    => $key,
                    'data_check' => [],
                    ];
                }

                return response()->json($response);
            }
        }
    }

    public function generate($params){

        $user_id    = $params['user_id'];
        $edu_id     = $params['user_id'];
        $dingid     = $params['ding_id'];
        $t_data     = $params['data'];
        $entry_date = $t_data->entry_date;
        $t_level    = $t_data->teacher_level;
        $class_rate_data = $this->employee_model->get_teachers_rate($t_level);

        $running_count_data = $this->edu_model->get_running_count($edu_id);
        $total_class = ($running_count_data != null) ? $running_count_data->running_class_count : 0;
        if ($running_count_data == null)
            return true;

        $teacher_level  = $running_count_data->current_level;
        $class_rate     = $class_rate_data->class_rate;
        $year_month     = date('Y-m',strtotime($from));

        $pb_bonus = 0.0;
        $observation_bonus  = 0.0;
        $trainings_bonus    = 0.0;
        $referrals_bonus    = 0.0;
        $attendance_incentive = 0.0;
        $pb_deductions  = 0.0;
        $deductions     = 0.0;
        $awol_deduction = 0.0;
        $late_deduction = 0.0;
        $late_15_deduction = 0.0;

        if ($cutoff == 2) {
            $pb_month   = date('Y-m',strtotime($year_month. ' -1 month'));
            $pb_from    = date('Y-m-01',strtotime($pb_month));
            $pb_to      = date('Y-m-t',strtotime($pb_month));

            $observation    = [];
            $trainings      = [];
            $referrals      = [];
            $pb_data        = [];

        }
        dd($class_rate);
        return TRUE;
    }

    public function bb_ps_bonus(Request $request, $month){
       $from = date('Y-m-01',strtotime($month));
       $to = date('Y-m-t',strtotime($month));
       $employee = $this->pay_model->get_bb_teachers($from,$to);
       $bb_teams = $this->pay_model->get_bb_teams($month);

       if (!empty((array)$bb_teams)) {
         $tmp_teams =  Helper::OBJECT_TO_ARRAY($bb_teams);
         $tmp_teams =  Helper::ASSOCIATIVE('id',$tmp_teams);
         $team_ids  = array_column($tmp_teams, 'id');
         $team_members = $this->pay_model->get_bb_team_members($team_ids);
         $tmp_team_members = Helper::OBJECT_TO_ARRAY($team_members);
         $tmp_team_members = Helper::ASSOCIATIVE_MULTI('team_id',$tmp_team_members);
         foreach ($tmp_team_members as $key => $value) {
             $data = [
                'month'   => $month,
                'from'    => $from,
                'to'      => $to,
                'team_id' => $key,
                'members' => $value,
                'team'    => $tmp_teams[$key],
                'employee'=> $employee,
             ];
             $this->generate_ps_bonus($data);
         }

        /* $bb_subteams = $this->pay_model->get_bb_subteams($team_ids);
         if (!empty((array)$bb_subteams)) {
             $tmp_subteams = Helper::OBJECT_TO_ARRAY($bb_teams);
         }*/

       }

       dd($bb_teams);
   }

   private function generate_ps_bonus($data){
     $team      = $data['team'];
     $members   = $data['members'];
     $from      = $data['from'];
     $to        = $data['to'];
     $month     = $data['month'];
     $emp_data  = Helper::ASSOCIATIVE('edu_id',Helper::OBJECT_TO_ARRAY($data['employee']));



     foreach ($members as $key => $member) {
         if (!isset($emp_data[$member['edu_id']]))
            continue;
         $emp_info = $emp_data[$member['edu_id']];

     }
     dd($emp_data);
   }

   public function array_insert($array, $position, $insert)
   {
        if (is_int($position)) {
            array_splice($array, $position, 0, $insert);
        } else {
            $pos   = array_search($position, array_keys($array));
            $array = array_merge(
                array_slice($array, 0, $pos),
                $insert,
                array_slice($array, $pos)
            );
        }
    }

    public function disputes(Request $request){

        $edu_id     = $request->input('edu_id');
        $class_id   = $request->input('class_id');
        $response = [
            'status' => FALSE,
            'data' => [],
        ];

        $class_info = $this->employee_model->get_class_info($class_id);
        $class_info_array = Helper::OBJECT_TO_ARRAY($class_info);
        $class_info_id_key = Helper::ASSOCIATIVE('id',$class_info_array);

        if ($class_info == null){
            $response['msg'] = 'no class data.';
            return response()->json($response);
        }

        $talktime = $class_info[0]->talktime;
        $el_data = $this->employee_model->get_el_records($edu_id,$talktime);

        $update_values = [];
        $current_rc = 0;
        $total_valid_class = 0;

        foreach ($el_data as $key => $el) {
            if ($current_rc == 0 && in_array($el->class_id,$class_id)) {
                $current_rc = $el->running_class_count + 1;
                $total_valid_class = $current_rc;
                $update_values[$el->id] = [
                    'class_date' => $el->class_date,
                    'running_class_count' => $current_rc,
                    'total_valid_class' => $total_valid_class,
                    'class_status' => 'Valid',
                    'class_id' => $el->class_id,
                ]; 
            }else{

                if ($el->class_status == 'Valid') {

                    $current_rc++;
                    $total_valid_class++;
                    $update_values[$el->id] = [
                        'class_date' => $el->class_date,
                        'running_class_count' => $current_rc,
                        'total_valid_class' => $total_valid_class,
                        'class_status' => $el->class_status,
                        'class_id' => $el->class_id,
                    ]; 
                }else{
                    
                   
                    if (in_array($el->class_id,$class_id)) {
                      
                       $current_rc++;
                       $total_valid_class++;
                        $update_values[$el->id] = [
                            'class_date' => $el->class_date,
                            'running_class_count' => $current_rc,
                            'total_valid_class' => $total_valid_class,
                            'class_status' => 'Valid',
                            'class_id' => $el->class_id,
                        ]; 
                    }else{

                        $update_values[$el->id] = [
                            'class_date' => $el->class_date,
                            'running_class_count' => $current_rc,
                            'total_valid_class' => $total_valid_class,
                            'class_status' => $el->class_status,
                            'class_id' => $el->class_id,
                        ]; 
                    }
                    
                }

                
            }
        }
        //save adjustments
       // dd($update_values);
    }

}
 ?>
