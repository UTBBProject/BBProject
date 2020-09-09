<?php
namespace App\Http\Controllers;
use App\Libraries\Helpers as Helper;
use App\Models\Employee\Employees as Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination;
use App\Models\Edu\Edu_class as Edu;
use App\Models\Payroll\Payroll as Pay;
class BBPayrollController extends Controller
{
	protected $employee_model;
    protected $payroll_model;
	protected $search;
	public function __construct()
	{
        $this->middleware('auth');
        $this->employee_model = new Employee;
        $this->edu_model = new Edu;
        $this->payroll_model = new Pay;
    }

    public function employees(){
        //$results = app('db')->connection('db_recruitment')->select("SELECT * FROM users");
        //$results = DB::connection('db_payroll')->select('select * from employee limit 100');
        //Helper::PRINT_DUMP($results);
        return response()->json($this->employee_model->get_employees());
    }

    public function get_teachers(Request $request,$search = FALSE){
        $teachers = $this->employee_model->get_teachers($search);
      //  $paginator = Paginator::make($teachers->toArray(), self::count());
        dd($teachers);
        if (!empty($teachers)) {

          /*  foreach ($teachers as $key => $t) {
                $accumulated_class_count = $this->employee_model->get_all_class_count($t->edu_id,$t->entry_date);
                $teachers[$key]->accumulated_class = $accumulated_class_count;
            }*/
        }
        return response()->json($teachers);
    }

    public function test(){
        dd(Auth::user());
    }

    public function earnings_log($date = FALSE){

        $d1 = '2019-04-01';
        $d2 = '2019-05-31';

        $dates = $this->date_range($d1,$d2);


        foreach ($dates as  $_d) {

            $d_from = strtotime(date('Y-m-d 00:00:00',strtotime($_d)));
            $d_to   = strtotime(date('Y-m-d 23:59:59',strtotime($_d)));
            $data = [];
            $class_logs = [];
            $e_logs = $this->employee_model->get_bb_employees(TRUE,date('Y-m-d',strtotime($_d)));
            if (empty((array)$e_logs))
                continue;
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

                $dt_callins =  $this->dingtalk_callins(-1,$ding_ids,$d_from);

            $dt_approvals = $this->dingtalk_valid_class($ding_ids,$d_from);//

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
                if ($t->starting_level == "")
                    continue;
                $teachers_rc = !empty((array)$teachers_rc) ? $teachers_rc->running_class_count : 0;
                $teachers_cr = $this->employee_model->get_teachers_rate($t->starting_level);
                $course_rank_up = $teachers_cr->course_rank_up;

                $old_rc      = 0;
                $teachers_class_rate = $teachers_cr->class_rate;
                $teacher_dt_callins = isset($dt_callins[$t->ding_emp_id]) ? $dt_callins[$t->ding_emp_id] : [];
                $sched_checkin_data = [];
                if (isset($checkin_sched_array) && isset($checkin_sched_array[$t->edu_id])) {
                    $sched_checkin_data = $checkin_sched_array[$t->edu_id];

                }
                $current_level = $teachers_cr->level;
                $teacher_dt_approvals = isset($dt_approvals[$t->ding_emp_id]) ? $dt_approvals[$t->ding_emp_id] : [];

                if (!empty((array)$talk_class)) {
                    foreach ($talk_class as $k => $c) {

                        if ((int)$c->teachersignin == 1 AND in_array((int)$c->cancelstatus, [0,4,5]) AND (int)$c->commentstatus == 1 AND (int)$c->category != 0) {
                            $valid_class_rate = $teachers_class_rate;
                            //check for complaints
                            $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                            $class_status = 'Valid';
                            if (!empty((array)$complaint_check)) {
                               if ($complaint_check->complaint_date) {
                                   $class_count = -200;
                                   $class_status = 'Complaints';
                               }
                           } else{
                            $class_count = 1;
                        }
                            //end check complaints


                        $class_type = 'Normal';
                        $ct_amount = 0.0;
                        if (!empty($sched_checkin_data)) {
                            $scheds = array_chunk($sched_checkin_data, 2);
                                //if (count($sched_checkin_data) > 1) {
                            foreach ($scheds as $l => $s) {
                                $_cFrom = strtotime($s[0]['check_time']);
                                $_cTo   = strtotime($s[1]['check_time']);

                                if ($c->talktime >= $_cFrom && $c->talktime <= $_cTo) {
                                    break;
                                }else{
                                    $class_type = 'Subclass';
                                    $ct_amount = 20.00;
                                    $valid_class_rate += 20.00;

                                }

                            }
                               // }

                        }
                        if ($class_count < 0) {
                                //for rank down

                        }
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
                            $this->employee_model->update_level($t->user_id,$current_level);
                        }

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
                            'amount'        => $valid_class_rate,
                            'running_class_count' => $teachers_rc,
                            'class_type'    => $class_type,
                            'ct_amount'     => $ct_amount,
                            'class_rate' => $teachers_class_rate,
                        ];

                        if ($teachers_rc > $course_rank_up) {
                            $teachers_class_rate = $valid_class_rate;
                        }

                    }else{
                            //special case check dingtalk approvals
                        $ct_amount = 0.0;
                        $class_status  = 'Invalid';
                        $class_count = 0;
                        $teachers_rc += $class_count;
                        $class_type = 'Normal';
                        if (!empty($teacher_dt_approvals)) {
                            $is_valid = FALSE;
                            foreach ($teacher_dt_approvals as $dta) {
                                if ($dta['class_id'] == $c->id) {
                                    if ($dta['dispute_approved']) {
                                        $is_valid = TRUE;
                                        $class_status = 'Valid';
                                        $class_count = 1;
                                        $teachers_rc += $class_count;
                                        break;
                                    }
                                }
                            }

                            if ($is_valid) {
                                $complaint_check = $this->employee_model->get_complaints($t->edu_id,$c->id);
                                $class_status = 'Valid';
                                if (!empty((array)$complaint_check)) {
                                 if ($complaint_check->complaint_date) {
                                     $class_count = -200;
                                     $class_status = 'Complaints';
                                     $valid_class_rate = $teachers_class_rate * 0;
                                 }
                             }else{
                                $class_count = 1;
                            }

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

                            }
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
                    ];
                }
            }
        }

                //check teachers transferred class
        if (!empty((array)$transferred_class)) {

            foreach ($transferred_class as $j => $tc) {
                        //Check for callins
                        //Amount +-
                $deduction = 0.0;
                if (!empty($teacher_dt_callins)) {
                    foreach ($teacher_dt_callins as $x => $c) {
                        $create_time = strtotime($c['create_time']);
                        if ($create_time >= $d_from && $create_time <= $d_to) {
                            $value_date_data  = json_decode($c['value']['value'],true);
                            $leave_from = $value_date_data[0];
                            $leave_to   = $value_date_data[1];
                            if ($tc['talktime'] >= strtotime($leave_from)  && $tc['talktime'] <= strtotime($leave_to)) {
                               $ct = strtotime($create_time);
                               $lf = strtotime($leave_from);
                               $diff = round(abs($ct - $lf) / 60,2);

                               if ($diff > 180) {
                                $deduction = ($teachers_class_rate * .80) * -1;
                            }elseif($diff < 180){
                                $deduction = $teachers_class_rate * -1;
                            }
                            break;
                        }

                    }
                }

            }

            $transferred_class_class_rate_amount = $teachers_class_rate;
            if ($deduction == 0.0) {
                            //$teachers_class_rate = -150;
                $transferred_class_class_rate_amount = $transferred_class_class_rate_amount * -3;
            }else{
                $transferred_class_class_rate_amount = $deduction;
            }

            $class_count = 0;
            $class_logs[] = [
                'user_id'   => $t->user_id,
                'edu_id'    => $t->edu_id,
                'teacher'   => $t->teacher,
                'current_level' => $teachers_cr->level,
                'class_date' => date('Y-m-d H:i:s',$tc['talktime']),
                'class_date_unix' => $tc['talktime'],
                'class_id'  => $tc['id'],
                'class_status'  => 'Transferred',
                'class_category' => 0,
                'class_cancel_status' => 0,
                'teachersignin' => 0,
                'class_comment' => 0,
                'class_count'   => $class_count,
                'amount'        => $transferred_class_class_rate_amount,
                'running_class_count' => $teachers_rc,
                'class_type'    => 'Normal',
                'ct_amount'     => 0.0,
                'class_rate' => $teachers_class_rate,
            ];
        }
    }
            }//end main loop
        }

        if (!empty($class_logs)) {

            $this->employee_model->transaction($class_logs,1);
        }
    }


    return response()->json(['status' => 'success']);
}


public function dingtalk_callins($id,$dingid,$from = '',$to = '',$class = [],$clss_rate = 0.0){
        //ini_set('max_execution_time', 240);
    $dingtalk_proc_list = "http://dingtalkmanager.helputalk.com/processinstance/processinstance/listids";
    $dingtalk_proc_instance = "http://dingtalkmanager.helputalk.com/processinstance/processinstance/get";
    $ding_id_chunks = array_chunk($dingid, 5);

    $all_proc_id = [];
    foreach ($ding_id_chunks as  $dic) {
        $params = [
            'process_code'  => 'PROC-F0858A37-3716-43AF-90B2-3A82331F200D',
            'userid_list[]' => '"'.implode(',', $dic).'"',
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
    $dingtalk_proc_list = "http://dingtalkmanager.helputalk.com/processinstance/processinstance/listids";
    $dingtalk_proc_instance = "http://dingtalkmanager.helputalk.com/processinstance/processinstance/get";
    $ding_id_chunks = array_chunk($ding_ids, 5);
    $all_proc_id = [];

    foreach ($ding_id_chunks as  $dic) {
        $dids = implode(',', $dic);
        $params = [
            'process_code'  => 'PROC-7F9A1C36-ED5D-4B8C-9BAB-D957E72E467E',
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
                    'dispute_approved' => $dispute_approval
                ];

            }

        }
    }

    return $all_bb_call_ins;
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

    public function get_class_list(Request $request){
        $data = $this->employee_model->get_class_list(Auth::id(),$request);
        $temp = Helper::OBJECT_TO_ARRAY($data);
        $student_ids = array_column($temp, 'studentid');
        $lesson_ids = array_column($temp, 'id');
        $lesson_ids = implode(',', $lesson_ids);
        $student_profile  = $this->edu_model->get_student_profile($student_ids);
        $student_profile = Helper::OBJECT_TO_ARRAY($student_profile);
        $student_profile = Helper::ASSOCIATIVE('uid',$student_profile);
        foreach ($data as $key => $value) {
            if (isset($student_profile[$value->studentid])) {
                $data[$key]->student_nickname =  $student_profile[$value->studentid]['nickname'];
                $data[$key]->student_realname =  $student_profile[$value->studentid]['realname'];
                $data[$key]->student_qq =  $student_profile[$value->studentid]['qq'];
                $data[$key]->student_uniacid =  $student_profile[$value->studentid]['uniacid'];
                $data[$key]->student_groupid =  $student_profile[$value->studentid]['groupid'];
                $data[$key]->student_mobile =  $student_profile[$value->studentid]['mobile'];
            }
            $curriculum = $this->employee_model->get_class_curriculum($data[$key]->gradeid, $data[$key]->talknote);
            if($curriculum){
                $data[$key]->curriculum_info = $curriculum;
                $grade_info = $this->employee_model->get_grade($data[$key]->gradeid);
                $data[$key]->curriculum_info->gradename = $grade_info && $grade_info->gradename ? $grade_info->gradename : '--';
                $data[$key]->curriculum_info->joyname = $grade_info &&  $grade_info->joy_name ? $grade_info->joy_name : '--';
                $level_info = $grade_info ? $this->employee_model->get_level($grade_info->level) : [];
                $data[$key]->curriculum_info->level = $level_info ? $level_info->title : '--';
            }
            
        }

    $vd = Helper::VIDEO_DURATION($lesson_ids);
    if ($request->has('debug')) {
        if ($request->input('debug') == 'vd') {
            Helper::PRINT_DUMP($params);
            dd($vd);
        }
    }

    if (!empty($vd)) {
        foreach ($data as $key => $c) {
            if (isset($vd[$c->id])){
                $data[$key]->videoDuration = $vd[$c->id];
            }
            if(($data[$key]->videoDuration < 25 || $data[$key]->videoDuration == '--' || $data[$key]->videoDuration == '') && $data[$key]->class_status == 'Valid'){
                $data[$key]->class_status = 'Invalid';
            }
        }
    }
    else{
        foreach ($data as $key => $c) {
            if($data[$key]->class_status == 'Valid'){
                $data[$key]->class_status = 'Invalid';
            }
        }
    }

   if ($request->has('class_id')) {
       $el = $this->employee_model->get_earnings_logs($request->input('class_id'));
       if (!empty((array)$el)) {
            foreach ($data as $key => $c) {
                if ($request->input('class_id') == $c->id) {
                    if($el->class_status == 'Invalid'){
                        $array = [0,4,5];
                        if(!in_array($el->class_cancel_status,$array)){
                            $data[$key]->class_status = 'Cancelled';
                        }
                        else{
                            $data[$key]->class_status = $el->class_status;
                        }
                    }
                    else{
                        $data[$key]->class_status = $el->class_status;
                    }
                }
            }
       }
   }
 return response()->json($data);
}

public function get_bb_earnings_log(Request $request){
    $data = $this->employee_model->er_data(Auth::id(),$request)->toArray();
    
    $response['last_page'] = $data['last_page'];
    $response['current_page'] = $data['current_page'];
    
    $note = [
        1 => '10 mins Prior to shift',
        2 => 'Late within the shift',
        3 => 'AWOL',
        4 => 'Under Time',
        5 => 'Under Time',
        6 => 'Half Day Absent',
        7 => 'Whole Day Absent',
        8 => 'Leave',
        9 => 'Payroll Leave',
        10 => '15 mins Prior to shift',
        11 => 'Homebase Absent Request'
    ];

    $edu_ids = array_column($data['data'],'edu_id');
    $class_ids = array_column($data['data'],'class_id');
    $get_note_type = $this->employee_model->getNoteTypev2($class_ids, $edu_ids)->keyBy('class_id')->toArray();

    $transferred_class_ids = array_keys(array_filter($get_note_type, function($v) use ($note){
        return $v->t_type >= count($note);
    }));

    $reason_for_transfer = $this->employee_model->get_reason_lession_transfer($transferred_class_ids);
    $reason_for_transfer = $reason_for_transfer ? $reason_for_transfer->keyBy('lession_id')->toArray() : false;

    foreach ($data['data'] as $value) {
        $t_type = 0;
        if(isset($get_note_type[$value->class_id]) && $get_note_type[$value->class_id]->edu_id == $value->edu_id && $get_note_type[$value->class_id]->t_type <= count($note)){
            $t_type = $get_note_type[$value->class_id]->t_type;
            $t_type = !empty($t_type) && $t_type >= 0 ? (isset($note[$t_type]) ? $note[$t_type] : '') : '';
        }else if($reason_for_transfer != false && isset($reason_for_transfer[$value->class_id]) && $reason_for_transfer[$value->class_id]->lession_id == $value->class_id){
            $t_type = $reason_for_transfer[$value->class_id]->transfer_reason_en;
        }

        $response['data'][] = [
            'id' => $value->id,
            'user_id' => $value->user_id,
            'edu_id' => $value->edu_id,
            'teacher' => $value->teacher,
            'current_level' => $value->current_level,
            'class_date' => $value->class_date,
            'class_date_unix' => $value->class_date_unix,
            'class_id' => $value->class_id,
            'class_type' => $value->class_type,
            'class_status' => $value->class_status,
            'class_category' => $value->class_category,
            'class_cancel_status' => $value->class_cancel_status,
            'teachersignin' => $value->teachersignin,
            'class_comment' => $value->class_comment,
            'class_count' => $value->class_count,
            'running_class_count' => $value->running_class_count,
            'total_valid_class' => $value->total_valid_class,
            'class_rate' => $value->class_rate,
            'amount' => $value->amount,
            'ct_amount' => $value->ct_amount,
            'weekend_pay' => $value->weekend_pay,
            'video_duration' => $value->video_duration,
            'video_deduction' => $value->video_deduction,
            'date_created' => $value->date_created,
            'note' => $t_type,
            'dispute_status' => isset($value->dispute_status) ? $value->dispute_status : null,
            'dispute_description' => isset($value->dispute_description) ? $value->dispute_description : null,
            'dispute_date' => isset($value->dispute_date) ? $value->dispute_date : null
        ];
    }
    return response()->json($response);
}

public function bb_attendance(Request $request){
    // return response()->json(Auth::id());
    $this->validate($request, [
        'month' => 'required',
    ]);
    $month = $request->input('month');
    // get all data within a month
    $data = $this->employee_model->get_attendance_violations(Auth::id(),$month);
    // return response()->json($data);

    $new_data = [];
    foreach($data['data'] as $key => $value){
        $affected_shifts = [];
        $affected_shifts_str = "";
        // kunin ang mga schedule niya in that day
        $scheds = $this->employee_model->get_ding_talk_schedules($value->emp_id, date('Y-m-d',strtotime($value->from_readable)));

        if($scheds){
            $scheds = array_chunk($scheds,2);
            foreach($scheds as $k => $v){
                if (
                    ($value->from >= strtotime($v[0]->check_time) && $value->from <= strtotime($v[1]->check_time))
                    || 
                    ($value->to >= strtotime($v[0]->check_time) && $value->to <= strtotime($v[1]->check_time))
                ){
                    $affected_shifts [] = date("H:i:s",strtotime($v[0]->check_time))."-".date("H:i:s",strtotime($v[1]->check_time));
                }
            }
        }
        if($affected_shifts) {
            $affected_shifts_str = implode(',', $affected_shifts);
        }

        $type_absent = [5,6,7,8,11];
        $auto_transfer_desc = [
            1  => '10 mins Prior to shift',
            2  => 'Late within the shift',
            3  => 'AWOL',
            4  => 'Early Out',
            9  => 'Payroll Leave',
        ];

        $violation = in_array($value->auto_transfer_type,$type_absent) ? "Absent" : $auto_transfer_desc[$value->auto_transfer_type];
        // set abest time if status absent
        $absent_time_range = date("H:i", strtotime($value->from_readable)).'-'.date("H:i", strtotime($value->to_readable));

        $value->absent_time_range = in_array($value->auto_transfer_type,$type_absent) ? $absent_time_range : '';
        $value->violation = $violation != "" ? $violation : "--";
        $value->dingtalk_sched = $affected_shifts_str != "" ? $affected_shifts_str : "--";
        $value->date_created = date("Y-m-d",$value->from);
        $new_data[] = $value;
    }
    $data['data'] = $new_data;

   return response()->json($data);
}

public function bb_complaints(Request $request){
    $data = $this->employee_model-> get_bbComplaints(Auth::id(),$request);
    return response()->json($data);
}

public function bb_total_transferred_class(Request $request){
    $data = $this->employee_model->get_total_transferred_class(Auth::id(),$request);
    return response()->json($data);
}

public function bb_transferred_class(Request $request){
    // dd(Auth::id());
    // return  response()->json(Auth::id());
    // kunin yong data from earnings logs
    $data = $this->employee_model->get_earnings_logs_by_edu_id(Auth::id(),$request);

    //GETDATA earnings log
    $data = Helper::OBJECT_TO_ARRAY($data);
    $class_ids = array_column($data, 'class_id');

    $d = [];
    foreach($data as $key => $v){
        $d[$v['class_id']] = $v;
    }

    //get lessions
    $lession = $this->employee_model->get_transferred_class_v2(Auth::id(),$class_ids);

    $r = [];
    $r['last_page'] = $lession['last_page'];
    $r['current_page'] = $lession['current_page'];
    $r['total'] = $lession['total'];
    $r['per_page'] = $lession['per_page'];
    $r['last_page'] = $lession['last_page'];
    $r['from'] = $lession['from'];
    $r['data'] = [];
    foreach($lession['data'] as $key => $value){
        $r['data'][] = [
            'class_id' => $value->id,
            'date_time' => $value->talktime,
            'deduction' => $d[$value->id]['amount'],
            'transfer_time' => $value->create_time
        ];
    }

    return  response()->json($r);
}

public function bb_performance_bonus(Request $request){
    $this->validate($request, [
        'month' => 'required',
    ]);
    $month = $request->input('month');
    $data = $this->employee_model->get_pb(Auth::id(),$month);
    return response()->json($data);
}

public function bb_performance_improvement(Request $request){
   $this->validate($request, [
    'month' => 'required',
]);
   $month = $request->input('month');
   $data = $this->employee_model->get_pi(Auth::id(),$month);
   return response()->json($data);
}

public function bb_total_course_incentive(Request $request){


}

public function bb_attendance_incentives(){

}

public function bb_class_count_deducted(Request $request){
    $data = $this->employee_model->get_classes_deducted(Auth::id(),$request);
    return response()->json($data);
}

public function bb_add_earnings_log(Request $request){

}

public function bb_update_earnings_log(Request $request){

    $this->validate($request, [
        'class_id' => 'required',
        'class_status' => 'required',
        'class_count' => 'required',
        'amount'    => 'required',
        'ct_amount' => 'required'
    ]);
}

public function bb_monthly_report(Request $request){
    $this->validate($request, [
        'month' => 'required',
    ]);
    $data = $this->employee_model->get_monthly_reports(Auth::id(),$request->input('month'),$request);
    return response()->json($data);
}

public function bb_view_class(Request $request,$class_id){
    $data = $this->employee_model->get_class_list(Auth::id(),$request,$class_id);
    $temp = Helper::OBJECT_TO_ARRAY($data);
    $student_ids = array_column($temp, 'studentid');
    $student_profile  = $this->edu_model->get_student_profile($student_ids);
    $student_profile = Helper::OBJECT_TO_ARRAY($student_profile);
    $student_profile = Helper::ASSOCIATIVE('uid',$student_profile);
    $vd = Helper::VIDEO_DURATION($class_id);
    $el = $this->employee_model->get_earnings_logs($class_id);
    if (!empty($vd)) {
        foreach ($data as $key => $value) {
            $data[$key]->videoDuration = number_format((float)$vd[$value->id], 2, '.', '');
            if (!empty((array)$el) && $class_id == $value->id ) {
                $data[$key]->class_status = $el->class_status;
            }
        }
    }else{
        foreach ($data as $key => $value) {
            if (!empty((array)$el) && $class_id == $value->id ) {
                $data[$key]->class_status = $el->class_status;
            }
        }
    }

    foreach ($data as $key => $value) {
        if (isset($student_profile[$value->studentid])) {
            $data[$key]->student_nickname =  $student_profile[$value->studentid]['nickname'];
            $data[$key]->student_realname =  $student_profile[$value->studentid]['realname'];
            $data[$key]->student_qq =  $student_profile[$value->studentid]['qq'];
            $data[$key]->student_uniacid =  $student_profile[$value->studentid]['uniacid'];
            $data[$key]->student_groupid =  $student_profile[$value->studentid]['groupid'];
            $data[$key]->student_mobile =  $student_profile[$value->studentid]['mobile'];
        }
        $curriculum = $this->employee_model->get_class_curriculum($data[$key]->gradeid, $data[$key]->talknote);
        if($curriculum){
            $data[$key]->curriculum_info = $curriculum;
            $grade_info = $this->employee_model->get_grade($data[$key]->gradeid);
            $data[$key]->curriculum_info->gradename = $grade_info && $grade_info->gradename ? $grade_info->gradename : '--';
            $data[$key]->curriculum_info->joyname = $grade_info &&  $grade_info->joy_name ? $grade_info->joy_name : '--';
            $level_info = $grade_info ? $this->employee_model->get_level($grade_info->level) : [];
            $data[$key]->curriculum_info->level = $level_info ? $level_info->title : '--';
        }
        
    }

    return response()->json($data);
}

public function get_bb_earnings_count(Request $request){
    $data = $this->employee_model->er_data_count(Auth::id(),$request);
    $d = Helper::OBJECT_TO_ARRAY($data);
    $r = array_count_values(array_column($d, 'class_status'));
    return response()->json($r);
}

public function monthly_earnings_log(Request $request){
    $month = date('Y-m');
    if ($request->has('month'))
        $month = $request->input('month');
    $data = $this->employee_model->get_monthly_earnigs_log(Auth::id(),$month);
    $temp_data =  Helper::OBJECT_TO_ARRAY($data);

    $total_earnings = array_sum(array_column($temp_data, 'amount'));
    $total_deductions_data = array_filter(array_column($temp_data, 'amount'), function ($v) {
      return $v < 0;
  });
    $total_deductions = array_sum($total_deductions_data);
    Helper::PRINT_DUMP($total_deductions);
    Helper::PRINT_DUMP($total_earnings);
}


public function get_bb_max_talktime(){
    $data = $this->employee_model->get_max_talktime(Auth::id());
    return response()->json($data);
}

public function get_transferred_class_details(Request $request){
   $this->validate($request, [
    'lessonid' => 'required',
]);
   $data = $this->employee_model->get_transferred_class_details($request->input('lessonid'));
   if (!empty((array)$data)) {
       $data->class_time = date('Y-m-d H:i',$data->talktime);
   }
   return response()->json($data);
}

public function bb_observation(Request $request){
    $this->validate($request, [
        'month' => 'required',
    ]);
    $month = $request->input('month');
    $data = $this->employee_model->get_observations(Auth::id(),$month);
    return response()->json($data);
}

public function bb_referral(Request $request){
    $this->validate($request, [
        'month' => 'required',
    ]);
    $month = $request->input('month');
    $data = $this->employee_model->get_referral(Auth::id(),$month);
    return response()->json($data);
}

public function get_bb_payroll(Request $request){
    dd(Auth::user());
    //$data = $this->payroll_model->get_bb_payroll($request);
}

public function get_current_classrate(){
    $data = $this->employee_model->get_current_class_rate(Auth::id());
    return response()->json($data);
}


}
?>
