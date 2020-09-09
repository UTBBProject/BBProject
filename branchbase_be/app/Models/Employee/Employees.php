<?php

namespace App\Models\Employee;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Lumen\Auth\Authorizable;
use App\Libraries\Helpers as Helper;
class Employees extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;
    protected $db_recruitment;
    protected $db_payroll;
    protected $db_edu;
    protected $sql_raw;
    protected $select;
    public $timestamps = false; //By default, Eloquent expects created_at and updated_at columns to exist on your >tables. Disabled by this. recommit recommit recommit
    public function __construct() 
    {
        $this->db_recruitment   = DB::connection('db_recruitment');
        $this->db_payroll       = DB::connection('db_payroll');
        $this->db_edu           = DB::connection('db_edu');
        //for debugging
        //enable query log
        app('db')->connection('db_recruitment')->enableQueryLog();
        app('db')->connection('db_payroll')->enableQueryLog();
        app('db')->connection('db_edu')->enableQueryLog();
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function get_employees(){
        $this->sql_stmt = "SELECT * FROM utalk2016";
        $results = $this->db_recruitment->table('employee')->select(['user_id','lastName','firstName'])->paginate(10);
        return $results;
    }

    public function get_rankup(){

    }

    public function get_teachers($search = [],$paginate = TRUE){
        $this->select = [
            "e.user_id",
            DB::raw("CONCAT(e.lastName,', ',e.firstName,' ',e.middleName) AS teacher"),
            "up.mobile","up.uid AS edu_id",
            "e.teacher_level AS starting_level",
            "e.entry_date",
            "e.ding_emp_id"
        ];

        $search_query = FALSE;
        if (!empty($search)) {
            $search_query = "(e.user_id = '{$search}' OR up.uid = '{$search}' OR e.lastName LIKE '%{$search}%' OR e.firstName LIKE '%{$search}%')";
        }

        $data = $this->db_recruitment->table('employee AS e')
                ->join(Helper::DB_TABLE('DB_DATABASE_UTALK', 'ims_users_profile') . ' AS up','e.phone','=','up.mobile')
                ->select($this->select)
                ->where('e.base','=',1)
                ->where('e.dismissed','=',0)
                ->where('e.disabled','=',0)
                ->when($search_query,function($query) use($search_query){
                    return $query->whereRaw($search_query);
                })
                ->orderBy('e.lastName')
                ->paginate(10);
        return $data;
    }

    public function get_bb_employees($for_cron = FALSE,$date = '',$request = null){
        $this->select = [
            "e.user_id",
            DB::raw("CONCAT(e.lastName,', ',e.firstName,' ',e.middleName) AS teacher"),
            "up.mobile","up.uid AS edu_id",
            "e.teacher_level AS starting_level",
            "e.entry_date",
            "e.ding_emp_id",
            "e.id as row_id"
        ];
        $data = $this->db_recruitment->table('employee AS e')
                ->join(Helper::DB_TABLE('DB_DATABASE_UTALK', 'ims_users_profile') . ' AS up','e.phone','=','up.mobile')
                ->select($this->select)
                ->where('e.base','=',1)
                ->where('e.dismissed','=',0)
                ->where('e.disabled','=',0)
                ->where(function($query) use ($request){
                    if ($request != null) {
                        if ($request->has('edu_id')) {
                            $query->where('up.uid','=',$request->input('edu_id'));
                        }
                    }
                })
                ->when($for_cron,function($query) use ($date){
                    return $query->where('e.entry_date','<=',$date );
                })
                //->where('e.user_id','=',1837)
                ->orderBy('e.lastName')
                ->get();
        return $data;
    }

    public function get_all_class_count($edu_id,$entry_date){
        $entry_date = date('Y-m-d 00:00:00',strtotime($entry_date));
        $count = DB::table('ims_talk_lession')
                ->where('teacherid','=',$edu_id)
                ->where('talktime','>',$entry_date)
                ->count();
        return $count;
    }

    public function get_running_countv1($edu_id){
        //update
        $data = $this->db_payroll->table('bb_earnings_log')
            ->select('running_class_count','total_valid_class','current_level')
            ->where('edu_id','=',$edu_id)
            ->orderBy('id','DESC')
            ->first();
        return $data;
    }

    public function get_all_class($edu_ids,$d_from,$d_to){
        if (!empty($edu_ids)) {
            $data = $this->db_edu->table('ims_talk_lession')->select('id','teacherid','studentid','talktime','teachersignin','commentstatus','cancelstatus','category')
            ->where('talktime','>=',$d_from)
            ->where('talktime','<=',$d_to)
            ->whereIn('teacherid',$edu_ids)
            ->orderBy('talktime')
            ->get();
            return $data;
        }
        return [];
    }

    public function get_all_transferred_class($edu_ids,$d_from,$d_to){
         if (!empty($edu_ids)) {
            $data = DB::table('ims_sc_talk_lession_transfer AS t')->select('l.id','t.oldteacher_id AS teacherid','l.studentid','l.talktime','l.teachersignin','l.commentstatus','l.cancelstatus','l.category','t.status_log')
            ->leftJoin('ims_talk_lession AS l','t.lession_id','=','l.id')
            ->where('l.talktime','>=',$d_from)
            ->where('l.talktime','<=',$d_to)
            ->where('t.is_recovered','=',0)
            ->whereIn('t.oldteacher_id',$edu_ids)
            ->orderBy('l.talktime')
            ->get();

            return $data;
        }
        return [];
    }

    public function get_teachers_rate($level){
        $data = $this->db_payroll->table('hb_class_rate')
            ->where('level','=',trim($level))
            ->first();
            
        return $data;
    }

    public function get_class_rate(){
        $data = $this->db_payroll->table('hb_class_rate')
            ->get();
        return $data;
    }

    public function get_complaints($edu_id,$class_id){
        $data = $this->db_payroll->table('hb_complaints')
            ->where('teacher_id','=',$edu_id)
            ->where('class_id','=',$class_id)
            ->first();
        return $data;
    }

    public function get_observations($edu_id,$month){
        $data = $this->db_payroll->table('hb_observation')
            ->where('teacher_id','=',$edu_id)
            ->where('period','=',$month)
            ->first();
        return $data;
    }

    public function get_referral($edu_id,$month){
        $data = $this->db_payroll->table('hb_referral')
            ->where('referral_teacher_id','=',$edu_id)
            ->where('cutoff','=',$month)
            ->first();
        return $data;
    }

    public function transaction($data = [],$command = 1){
        if ($command == 1) {

            $lesson_ids = array_column($data, 'class_id');
           // $this->db_payroll->table('bb_earnings_log')->whereIn('class_id',$lesson_ids)->delete();
             $this->db_payroll->transaction(function() use ($data) {
                ini_set('memory_limit', '128M'); //300 seconds = 5 minutes
                ini_set('max_execution_time', 0); //300 seconds = 5 minutes
                $this->db_payroll->table('bb_earnings_log')->insert($data);
            });
        }elseif($command == 2){
            $this->db_payroll->transaction(function() use ($data) {
                ini_set('memory_limit', '128M'); //300 seconds = 5 minutes
                ini_set('max_execution_time', 0); //300 seconds = 5 minutes
                foreach ($data as $key => $value) {
                    $this->db_payroll->table('bb_level_history')->insert($value);
                }

            });
        }elseif($command == 3){
             $this->db_payroll->transaction(function() use ($data) {
                ini_set('memory_limit', '128M'); //300 seconds = 5 minutes
                ini_set('max_execution_time', 0); //300 seconds = 5 minutes
                foreach ($data as $key => $value) {
                    $this->db_payroll->table('bb_earnings_deduction_logs')->insert($value);
                }

            });
        }

    }

    public function er_data($edu_id,$request){
        $search = [];
        $data = $this->db_payroll->table('bb_earnings_log AS a')
            ->select('a.*', 'b.dispute_status as dis_status', 'b.dispute_create_time as dispute_date', 'b.details as dispute_description', 'b.dispute_result')
            ->leftJoin('bb_dispute_logs AS b', function($join){
                $join->on('a.class_id', '=', 'b.class_id');
                $join->on('a.edu_id', '=', 'b.edu_id');
            })
            ->where('a.edu_id','=',$edu_id)
            ->where(function($query) use ($request){
                if ($request->has('class_id')){
                    $query->orWhere('class_id','=',$request->input('class_id'));
                }

                if ($request->has('date_from') && $request->has('date_to')){
                    $dFrom = strtotime($request->input('date_from'));
                    $dTo   = strtotime($request->input('date_to'));
                    $query->whereBetween('class_date_unix',[$dFrom,$dTo]);
                }


                if ($request->has('cancelstatus')){
                    $query->where('class_cancel_status','=',$request->input('cancelstatus'));
                }

                if ($request->has('status') || $request->has('p') ){
                    $class_status = '-';


                    switch ((int)$request->input('status')) {
                        case 0:
                            $class_status = 'Valid';
                            break;
                        case 1:
                            $class_status = 'Transferred';
                            break;
                        case 2:
                            $class_status = 'Invalid';
                            break;
                        case 3:
                            $class_status = 'Complaints';
                            break;
                        case 4:
                            $class_status = 'Cancelled';
                            break;
                    }
                    // var_dump($class_status); die();

                    // if ($request->input('p') == 'profile'){
                    //     $class_status = 'Valid';
                    // }

                    if($request->has('status')){
                        $query->where('class_status','=',$class_status);
                    }

                    if($request->has('clc')){
                        $query->where('running_class_count','=',$request->input('clc'));
                    }
                }

                if ($request->has('comment'))
                    $query->where('class_comment','=',$request->input('comment'));

                if ($request->has('checkedin'))
                    $query->where('teachersignin','=',$request->input('checkedin'));

                if ($request->has('category'))
                    $query->where('class_category','=',$request->input('category'));
            })
            ->orderBy('class_date_unix','DESC')
            ->orderBy('id','DESC')
            ->paginate(10);
        return $data;
    }

    public function get_bb_checkin_sched($date){
        $data = DB::table('ims_dingtalk_sched AS a')
            ->select('a.user_id',
              'a.tid',
              'a.name',
              'b.check_time',
              'b.type' ,
              'a.on_leave',
              'a.leave_id',
              'a.leave_from',
              'a.leave_to',
              'a.absent_type',
              'a.base')
            ->leftJoin('ims_dingtalk_check_in_out AS b','b.sched_id','=','a.id')
            ->where('a.date','=',$date)
            ->where('a.base','=',1)
            ->get();

        return $data;
    }

    public function get_pb($edu_id,$month){
        $data = $this->db_payroll->table('hb_pb')
            ->where('teacher_id','=',$edu_id)
            ->where('cutoff_from','LIKE',"%{$month}%")//DB::raw("cutoff_from LIKE '%{$month}%'")
            ->first();
        $data2 = $this->db_payroll->table('hb_performance_bonus')
            ->where('teacher_tid','=',$edu_id)
            ->where('pb_month','=',$month)
            ->first();
        $return = [
            'pb_scores' => $data,
            'pb_rank'   => $data2
        ];

        return $return;
    }

    public function get_total_transferred_class($edu_id,$request){
        // kunin yong data from earnings logs
        $data = $this->get_earnings_logs_by_edu_id($edu_id,$request);

        //GETDATA earnings log
        $data = Helper::OBJECT_TO_ARRAY($data);
        $class_ids = array_column($data, 'class_id');

        $d = [];
        foreach($data as $key => $v){
            $d[$v['class_id']] = $v;
        }

        //get lessions
        $lession = $this->get_transferred_class_v2($edu_id,$class_ids,true);
        return $lession;

    }

    public function  get_earnings_logs_by_edu_id($edu_id,$request){

        $month  = $request->input('month');

        if(!$month){
            return [];
        }

        $dFrom  = date('Y-m-01 00:00:00',strtotime($month));
        $dTo    = date('Y-m-t 23:59:59',strtotime($month));

        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->where('class_date', '>=',$dFrom)
            ->where('class_date', '<=',$dTo)
            ->where('class_status','=','Transferred')
            ->get();
        return $data->toArray();
    }

    public function get_transferred_class_v2($edu_id,$class_ids,$count = false){

         $this->select = [
            'tl.id',
            'tr.newteacher_id',
            'up.nickname',
            DB::raw("FROM_UNIXTIME(tl.talktime,'%Y-%m-%d %H:%i') AS talktime"),
            DB::raw("case
                when tl.cancelstatus = 0 then 'Normal'
                when tl.cancelstatus = 1 then 'Cancelled'
                when tl.cancelstatus = 2 then 'Cancelled - For Make Up'
                when tl.cancelstatus = 3 then 'Cancelled in 24 hours'
                when tl.cancelstatus = 4 then 'Cancelled - Sc Make Up'
                when tl.cancelstatus = 5 then 'Cancelled - Sc Make Up, Done'
                else '-'
            end as cancelstatus"),
            DB::raw("case
                when tl.category = 1 then 'Connected'
                when tl.category = 2 then 'Abnormal'
                when tl.category = 3 then 'Absent'
                when tl.category = 4 then 'Late'
                else '-'
            end as category"),
            DB::raw("if(tl.teachersignin = 1, 'Yes', 'No') as check_in"),
            DB::raw("if(tl.commentstatus = 1, 'Done', 'Undone') as comment"),
            DB::raw("IF(
                tr.newteacher_id IS NULL OR tr.newteacher_id = tl.teacherid,
                IF(
                  tl.teachersignin = 1 
                  AND tl.commentstatus = 1 
                  AND tl.cancelstatus IN (0, 4, 5) 
                  AND tl.category != 0,
                  'Valid',
                    IF(
                        tl.cancelstatus NOT IN (0, 4, 5),
                        'Cancelled',
                        'Invalid'
                    )
                ),
                'Transferred'
              ) AS class_status"),
              "tr.create_time"
        ];


        $data = [];
        if($count){
            $data = DB::table('ims_sc_talk_lession_transfer AS tr')
            ->select($this->select)
            ->leftJoin('ims_talk_lession AS tl',function($join){
                $join->on('tl.id','=','tr.lession_id');
                $join->on('tr.is_recovered','=',DB::raw('0'));
            })
            ->leftJoin('ims_users_profile as up', 'tr.newteacher_id', '=', 'up.uid')
            ->where('tr.oldteacher_id','=',$edu_id)
            ->whereIn('tr.lession_id',$class_ids);
        }else{
            $data = DB::table('ims_sc_talk_lession_transfer AS tr')
            ->select($this->select)
            ->leftJoin('ims_talk_lession AS tl',function($join){
                $join->on('tl.id','=','tr.lession_id');
                $join->on('tr.is_recovered','=',DB::raw('0'));
            })
            ->leftJoin('ims_users_profile as up', 'tr.newteacher_id', '=', 'up.uid')
            ->where('tr.oldteacher_id','=',$edu_id)
            ->whereIn('tr.lession_id',$class_ids)
            ->orderBy('tr.id','DESC')
            ->paginate(10);
        }
            
        return $count ? $data->count() : $data->toArray();
    }

    public function get_transferred_class($edu_id,$request){

        $dd = get_earnings_logs_by_edu_id($edu_id,$request);

        $data1 = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->where(function($query) use ($request){
                $query->whereBetween('class_date',$sql_raw);
            })
            ->where('class_status','=','Transferred')
            ->get();
        
        $class_ids = Helper::OBJECT_TO_ARRAY($data1);
        if (empty($class_ids)){
            return [];
        }
        $class_ids = array_column($class_ids, 'class_id');

         $this->select = [
            'tl.id',
            'tr.newteacher_id',
            'up.nickname',
            DB::raw("FROM_UNIXTIME(tl.talktime,'%Y-%m-%d %H:%i') AS talktime"),
            DB::raw("case
                when tl.cancelstatus = 0 then 'Normal'
                when tl.cancelstatus = 1 then 'Cancelled'
                when tl.cancelstatus = 2 then 'Cancelled - For Make Up'
                when tl.cancelstatus = 3 then 'Cancelled in 24 hours'
                when tl.cancelstatus = 4 then 'Cancelled - Sc Make Up'
                when tl.cancelstatus = 5 then 'Cancelled - Sc Make Up, Done'
                else '-'
            end as cancelstatus"),
            DB::raw("case
                when tl.category = 1 then 'Connected'
                when tl.category = 2 then 'Abnormal'
                when tl.category = 3 then 'Absent'
                when tl.category = 4 then 'Late'
                else '-'
            end as category"),
            DB::raw("if(tl.teachersignin = 1, 'Yes', 'No') as check_in"),
            DB::raw("if(tl.commentstatus = 1, 'Done', 'Undone') as comment"),
            DB::raw("IF(
                tr.newteacher_id IS NULL OR tr.newteacher_id = tl.teacherid,
                IF(
                  tl.teachersignin = 1 
                  AND tl.commentstatus = 1 
                  AND tl.cancelstatus IN (0, 4, 5) 
                  AND tl.category != 0,
                  'Valid',
                    IF(
                        tl.cancelstatus NOT IN (0, 4, 5),
                        'Cancelled',
                        'Invalid'
                    )
                ),
                'Transferred'
              ) AS class_status")
        ];

        $data = DB::table('ims_sc_talk_lession_transfer AS tr')
            ->select($this->select)
            ->leftJoin('ims_talk_lession AS tl',function($join){
                $join->on('tl.id','=','tr.lession_id');
                $join->on('tr.is_recovered','=',DB::raw('0'));
            })
            ->leftJoin('ims_users_profile as up', 'tr.newteacher_id', '=', 'up.uid')
            /*->when($sql_raw,function($query) use($sql_raw){
                    return $query->whereBetween('tl.talktime',$sql_raw);
                })*/
            ->where('oldteacher_id','=',$edu_id)
            ->whereIn('lession_id',$class_ids)
            ->orderBy('tr.id','DESC')
            ->paginate(10);
        return $data;
    }


    public function get_total_complaints($edu_id,$month){
        $d_from = date('Y-m-01',strtotime($month));
        $d_to = date('Y-m-t',strtotime($month));
        $data = $this->db_payroll->table('hb_complaints')
            ->where('teacher_id','=',$edu_id)
            ->whereBetween('complaint_date',[$d_from,$d_to])
            ->orderBy('id','DESC')
            ->count();
        return $data;
    }

    public function get_bbComplaints($edu_id,$month){
        $d_from = date('Y-m-01',strtotime($month));
        $d_to = date('Y-m-t',strtotime($month));
        $data = $this->db_payroll->table('hb_complaints')
            ->where('teacher_id','=',$edu_id)
            ->whereBetween('cutoff_from',[$d_from,$d_to])
            ->orderBy('id','DESC')
            ->paginate();
        return $data;
    }

    public function get_class_list($edu_id,$request,$class_id = ''){
        $this->select = [
            'tl.id',
            'tl.lessiontype',
            'tl.book_type',
            'tl.teacherid',
            'tr.statustype',
            'up.nickname AS newteacher_name',
            DB::raw("FROM_UNIXTIME(tl.talktime,'%Y-%m-%d %H:%i') AS talktime"),
            DB::raw("case
                when tl.cancelstatus = 0 then 'Normal'
                when tl.cancelstatus = 1 then 'Cancelled'
                when tl.cancelstatus = 2 then 'Cancelled - For Make Up'
                when tl.cancelstatus = 3 then 'Cancelled in 24 hours'
                when tl.cancelstatus = 4 then 'Cancelled - Sc Make Up'
                when tl.cancelstatus = 5 then 'Cancelled - Sc Make Up, Done'
                when tl.cancelstatus = 8 then 'Cancelled - Task System Cancel'
                else '-'
            end as cancelstatus"),
            DB::raw("case
                when tl.category = 1 then 'Connected'
                when tl.category = 2 then 'Abnormal'
                when tl.category = 3 then 'Absent'
                when tl.category = 4 then 'Late'
                else '-'
            end as category"),
            DB::raw("if(tl.teachersignin = 1, 'Yes', 'No') as check_in"),
            DB::raw("if(tl.commentstatus = 1, 'Done', 'Undone') as comment"),
            DB::raw("case
                when (tr.statustype = 11 AND tr.oldteacherid = " .$edu_id.") then 'Transferred'
                else
                IF(
                  tl.teachersignin = 1 
                  AND tl.commentstatus = 1 
                  AND tl.cancelstatus IN (0, 4, 5) 
                  AND tl.category != 0,
                  'Valid',
                    IF(
                        tl.cancelstatus NOT IN (0, 4, 5),
                        'Cancelled',
                        'Invalid'
                    )
                )
                END
               AS class_status"),
            DB::raw("'--' AS videoDuration"),
            'gradeid',
            'talknote',
            'tl.studentid'
        ];


        $data = DB::table('ims_talk_lession AS tl')
            ->leftJoin('ims_lession_status as tr', 'tl.id','=','tr.lessionid')
            ->leftJoin('ims_users_profile as up', 'tl.teacherid', '=', 'up.uid')
            ->select($this->select)
            ->when($class_id,function($query) USE ($class_id){
                return $query->where('tl.id','=',$class_id);
            })
             ->where(function($query) use ($request, $edu_id){
                if ($request->has('class_id') && $request->input('class_id') != '')
                    $query->where('tl.id','=',$request->input('class_id'));

                if ($request->has('date_from') && $request->has('date_to') && ($request->input('checked') == 'true')){
                    $dFrom = strtotime($request->input('date_from'));
                    $dTo   = strtotime($request->input('date_to'));
                    $query->whereBetween('tl.talktime',[$dFrom,$dTo]);
                }

                if ($request->has('cancelstatus'))
                    $query->where('tl.cancelstatus','=',$request->input('cancelstatus'));

                if ($request->has('status')){
                    if ($request->input('status') == 0){
                        $valid_ids = $this->get_valid_ids($edu_id);
                        $ids = $valid_ids ? array_column(json_decode(json_encode($valid_ids), true),'id') : [];
                        $query->whereIn('tl.id', $ids);
                    }
                    elseif($request->input('status') == 1){
                        $query->where('tr.statustype','=',11);
                        $query->where('tr.oldteacherid','=',$edu_id);
                    }
                    elseif ($request->input('status') == 2){
                        $valid_ids = $this->get_valid_ids($edu_id);
                        $ids = $valid_ids ? array_column(json_decode(json_encode($valid_ids), true),'id') : [];
                        $query->whereNotIn('tl.id', $ids)->where(function($q){
                            $q->whereNull('tr.statustype');
                            $q->orWhere('tr.statustype', '!=', 11);
                        });
                    }
                    elseif($request->input('status') == 3){
                        $complaint_ids = $this->get_complaint_ids($edu_id);
                        $ids = $complaint_ids ? array_column(json_decode(json_encode($complaint_ids), true),'id') : [];
                        $query->whereIn('tl.id', $ids);
                    }
                    elseif($request->input('status') == 4){
                        $complaint_ids = $this->get_complaint_ids($edu_id);
                        $ids = $complaint_ids ? array_column(json_decode(json_encode($complaint_ids), true),'id') : [];
                        $query->whereNotIn('tl.cancelstatus', [0,4,5]);
                    }
                }
                if ($request->has('comment'))
                    $query->where('tl.commentstatus','=',$request->input('comment'));

                if ($request->has('checkedin'))
                    $query->where('tl.teachersignin','=',$request->input('checkedin'));

                if ($request->has('category'))
                    $query->where('tl.category','=',$request->input('category'));
            })
            ->where(function($query) use ($edu_id){
                $query->orWhere('tl.teacherid','=',$edu_id);
                $query->orWhere('tr.oldteacherid','=',$edu_id);
            })
            ->orderBy('tl.talktime')
            ->groupBy('tl.id')
            ->paginate(10);
            // $this->DUMP_QUERY($this->db_edu);

        return $data;
    }

    public function get_class_listV2($edu_id,$request,$class_id = ''){
        $this->select = [
            'tl.id',
            DB::raw("FROM_UNIXTIME(tl.talktime,'%Y-%m-%d %H:%i') AS talktime"),
            DB::raw("case
                when tl.cancelstatus = 0 then 'Normal'
                when tl.cancelstatus = 1 then 'Cancelled'
                when tl.cancelstatus = 2 then 'Cancelled - For Make Up'
                when tl.cancelstatus = 3 then 'Cancelled in 24 hours'
                when tl.cancelstatus = 4 then 'Cancelled - Sc Make Up'
                when tl.cancelstatus = 5 then 'Cancelled - Sc Make Up, Done'
                else '-'
            end as cancelstatus"),
            DB::raw("case
                when tl.category = 1 then 'Connected'
                when tl.category = 2 then 'Abnormal'
                when tl.category = 3 then 'Absent'
                when tl.category = 4 then 'Late'
                else '-'
            end as category"),
            DB::raw("if(tl.teachersignin = 1, 'Yes', 'No') as check_in"),
            DB::raw("if(tl.commentstatus = 1, 'Done', 'Undone') as comment"),
            DB::raw("IF(
                tr.newteacher_id IS NULL OR tr.newteacher_id = tl.teacherid,
                IF(
                  tl.teachersignin = 1 
                  AND tl.commentstatus = 1 
                  AND tl.cancelstatus IN (0, 4, 5) 
                  AND tl.category != 0,
                  'Valid',
                  IF(
                      tl.cancelstatus NOT IN (0, 4, 5),
                      'Cancelled',
                      'Invalid'
                  )
                ),
                'Transferred'
              ) AS class_status"),
            DB::raw("'--' AS videoDuration"),
        ];
        $data = DB::table('ims_talk_lession AS tl')
           /* ->leftJoin('ims_sc_talk_lession_transfer AS tr',function($join){
                $join->on('tl.id','=','tr.lession_id');
                $join->on('tr.is_recovered','=',DB::raw('0'));
            })*/
            ->select($this->select)
            ->when($class_id,function($query) USE ($class_id){
                return $query->where('tl.id','=',$class_id);
            })
             ->where(function($query) use ($request){
                if ($request->has('class_id'))
                    $query->orWhere('tl.id','=',$request->input('class_id'));

                if ($request->has('date_from') && $request->has('date_to')){
                    $dFrom = strtotime($request->input('date_from'));
                    $dTo   = strtotime($request->input('date_to'));
                    $query->orWhereBetween('tl.talktime',[$dFrom,$dTo]);
                }


                if ($request->has('cancelstatus'))
                    $query->orWhere('tl.cancelstatus','=',$request->input('cancelstatus'));

                if ($request->has('comment'))
                    $query->orWhere('tl.commentstatus','=',$request->input('comment'));

                if ($request->has('checkedin'))
                    $query->orWhere('tl.teachersignin','=',$request->input('checkedin'));

                if ($request->has('category'))
                    $query->orWhere('tl.category','=',$request->input('category'));
            })
            ->where(function($query) use ($edu_id){
                $query->orWhere('tl.teacherid','=',$edu_id);
                $query->orWhere('tr.oldteacherid','=',$edu_id);
            })
            ->orderBy('tl.id','DESC')
            ->paginate(10);
           // $this->DUMP_QUERY($this->db_edu);
        return $data;
    }

    public function get_classes_deducted($edu_id,$request){
        $filter = FALSE;
        if ($request->has('deducted')) {
            $filter = $request->input('deducted');
        }elseif($request->has('month')){
            $month = $request->input('month');
            $filter = DB::raw("class_date ");
        }

        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->where('class_count','<',0)
            ->orderBy('id','DESC')
            ->paginate(10);
        return $data;
    }

    public function get_class_curriculum($grade_id,$lesson_id){
        $this->select = [
            "tm.name AS material_name",
            "tm.materialinfo",
            "tm.materialnote",
            "m.id AS material_id",
        ];
         $data = $this->db_edu->table('ims_talk_teachingmaterial AS tm')
        ->join(Helper::DB_TABLE('DB_DATABASE_UTALK', 'ims_materials') . ' AS m','tm.materialpath','=','m.teachers_note')
        ->select($this->select)
        ->where('tm.gradeid','=',$grade_id)
        ->where('tm.lesson','=',$lesson_id)
        ->first();
     return $data;
    }

    public function get_level($level_id){
        $data = $this->db_edu->table('ims_talk_leveling')
            ->select('title','description')
            ->where('id','=',$level_id)
            ->first();
        return $data;
    }

    public function get_grade($gradeid){
        $data = $this->db_edu->table('ims_talk_grade')
            ->select('gradename','joy_name','level')
            ->where('id','=',$gradeid)
            ->first();
        return $data;
    }

    public function get_pi($edu_id,$month){
        $data = $this->db_payroll->table('hb_performance_improvement')
            ->where('teacher_id','=',$edu_id)
            ->where('period','=',$month)
            ->first();
        return $data;
    }

    public function get_attendance_violations_count($edu_id,$month){
        $d_from = date('Y-m-01',strtotime($month));
        $d_to = date('Y-m-t',strtotime($month));
        $data = DB::table('ims_auto_transfer')
            ->where('teacher_id','=',$edu_id)
            ->whereBetween('from_readable',[$d_from,$d_to])
            ->whereIn('auto_transfer_type',[1,2,3,4,5,6,7,8,9,11])
            ->get();
        $new_data = [];
        foreach($data as $key => $value){
            $new_data[] = date("Y-m-d", strtotime($value->date_created));
        };
        return count(array_unique($new_data)) ;
    }

    public function get_attendance_violations($edu_id,$month){
        $d_from = date('Y-m-01',strtotime($month));
        $d_to = date('Y-m-t',strtotime($month));
        $data = DB::table('ims_auto_transfer')
            ->where('teacher_id','=',$edu_id)
            ->whereBetween('from_readable',[$d_from,$d_to])
            ->whereIn('auto_transfer_type',[1,2,3,4,5,6,7,8,9,11])
            ->paginate(10);
        return $data->toArray();
    }
    public function getDingtalkSchedule($emp_id, $date ,$from_readable, $to_readable){
        $data = DB::table('ims_dingtalk_sched')
            ->select('ims_dingtalk_sched.id','ims_dingtalk_check_in_out.check_time','ims_dingtalk_check_in_out.type')
            ->join('ims_dingtalk_check_in_out','ims_dingtalk_check_in_out.sched_id','=','ims_dingtalk_sched.id')
            ->where('ims_dingtalk_check_in_out.base','=',0)
            ->where('ims_dingtalk_sched.user_id','=',$emp_id)
            ->where('ims_dingtalk_sched.date','=',$date)
            ->get();

        return $data;
    }

    // kunun ang mga schedule sa in a certain date
    public function get_ding_talk_schedules($emp_id, $date){
        $data = DB::table('ims_dingtalk_sched as sched')
            ->select('sched.id as sched_id','sched.date','sched.user_id','in_out.check_time','in_out.type')
            ->where('sched.date','=',$date)
            ->where('sched.user_id','=',$emp_id)
            ->where('in_out.disabled','=',0)
            ->leftJoin('ims_dingtalk_check_in_out as in_out','in_out.sched_id','=','sched.id')
            ->get();
        return $data->toArray();
    }

    public function get_time_in_out_in_sched($sched_id){
        $data = DB::table('ims_dingtalk_check_in_out')
            ->where('sched_id', $sched_id)
            ->where('disabled','=',0)
            ->get();
        // $queries    = DB::getQueryLog();
        // $lastQuery = end($queries);
        
        // return $lastQuery;
        return $data;
    }

    public function get_violation_status($edu_id, $time_in, $time_out){
        $data = DB::table('ims_auto_transfer')
            ->whereBetween('from_readable',[$time_in,$time_out])
            ->whereBetween('to_readable',[$time_in,$time_out])
            ->where('teacher_id','=',$edu_id)
            ->first();

        return $data;
    }


    public function get_monthly_reports($edu_id,$month,$request){
        //$edu_id = 2737;
        $user_details = $this->get_bb_details($edu_id);
        if(!isset($user_details->starting_level)){
            return  [
                'attendance_count' => 0,
                'complaints_count' => 0,
                'transferred_count' => 0,
                'pb_amount'         => 0.0,
                'pi_amount'         => 0.0,
                'course_incentives' => 0,
                'attendance_incentives' => 0,
                'monthly_bp'        => 0.0,
                'monthly_net_pay'   => 0.0,
                'monthly_deductions'  => 0.0,
                'subclass'          => 0,
            ];;
        }
        $teacher_level = $user_details->starting_level;
        $teacher_rate = $this->get_teachers_rate($teacher_level);
        $class_rate = $teacher_rate->class_rate;

        $user_id = $user_details->user_id;

        //Attendance Count
        $att_count = $this->get_attendance_violations_count($edu_id,$month);
        //Complaints count
        $com_count = $this->get_total_complaints($edu_id,$month);
        //Total Transferred Class
        $trans_class = $this->get_total_transferred_class($edu_id,$request);
        //PB
        $pb = $this->get_pb($edu_id,$month);
        //pi
        $pi = $this->get_pi($user_id,$month);
        //subclass
        $sc = $this->get_subclass_count($edu_id,$request);
        //course incentives
        $total_valid_class = $this->get_total_valid_class($edu_id,$month);
        //pi
        //$pi = $this->get_bb_pi();
        $course_incentives = 0.0;
        $basic_salary = ( $total_valid_class * $class_rate) + ($com_count * -200);
        if ($total_valid_class > 0 ) {

            if ($total_valid_class < 240) {
                $course_incentives = 0.0;
            }elseif ($total_valid_class >= 240) {
                $course_incentives = $basic_salary * .10;
            }elseif ($total_valid_class >= 320) {
                $course_incentives = $basic_salary * .20;
            }
        }

        //attendance incentives
        $attendance_incentives = 0.0;
        if ( $total_valid_class > 160) {
            if ($att_count == 0) {
                $attendance_incentives = $basic_salary * .10;
            }
        }

        $bb_payroll_data = $this->get_bb_payroll($edu_id,$month);
        $monthly_payroll = [];

        $monthly_bp = 0.0;
        $monthly_net_pay    = 0.0;
        $monthly_deductions = 0.0;

        if (count($bb_payroll_data) > 0) {
            $bb_payroll_data = Helper::OBJECT_TO_ARRAY($bb_payroll_data);
            //$monthly_bp = array_sum(array_column($bb_payroll_data, 'service_fee'));
            $monthly_net_pay = array_sum(array_column($bb_payroll_data, 'net_pay'));
            $monthly_deductions = array_sum(array_column($bb_payroll_data, 'total_deductions'));
        }

        $data = [
            'attendance_count' => $att_count,
            'complaints_count' => $com_count,
            'transferred_count' => $trans_class,
            'pb_amount'         => (isset($pb['pb_rank']) && !empty(isset($pb['pb_rank']))) ? $pb['pb_rank']->pb_bonus : 0.0,
            'pi_amount'         => (!empty($pi) OR $pi != NULL) ? number_format( $pi->total_pi,2,'.',' ')  : 0.0,
            'course_incentives' => number_format( $course_incentives,2,'.',' '),
            'attendance_incentives' => number_format( $attendance_incentives,2,'.',','),
            'monthly_bp'        => $basic_salary,
            'monthly_net_pay'   => $monthly_net_pay,
            'monthly_deductions'  => $monthly_deductions,
            'subclass'          => $sc,
        ];

        //dd($data);
        return $data;
    }

    public function get_total_valid_class($edu_id,$month){
        $d_from = strtotime(date('Y-m-01 00:00:00',strtotime($month)));
        $d_to = strtotime(date('Y-m-t 23:59:59',strtotime($month)));

        $data = DB::table('ims_talk_lession')
            ->where('teacherid','=',$edu_id)
            ->where('teachersignin','=', 1)
            ->where('commentstatus','=', 1)
            ->where('category','!=',0)
            ->whereIn('cancelstatus', [0,4,5])
            ->whereBetween('talktime',[$d_from,$d_to])
            ->count();
            return $data;
    }

    public function update_level($user_id,$level){
        $data = $this->db_recruitment->table('employee')
            ->where('user_id','=',$user_id)
            ->update(['teacher_level' => $level]);
    }

    public function update_history_log($data){
        $this->db_payroll->from('bb_level_history')->insert($data);
    }

    public function DUMP_QUERY($connection){
        $queries    = $connection->getQueryLog();
        $lastQuery = end($queries);
        dd($queries);
    }

    public function er_data_count($edu_id,$request){
        $search = [];
        $data = $this->db_payroll->table('bb_earnings_log')
            ->select('class_status')
            ->where('edu_id','=',$edu_id)
            ->where(function($query) use ($request){
                if ($request->has('class_id'))
                    $query->orWhere('class_id','=',$request->input('class_id'));

                if ($request->has('date_from') && $request->has('date_to')){
                    $dFrom = strtotime($request->input('date_from'));
                    $dTo   = strtotime($request->input('date_to'));
                    $query->whereBetween('class_date_unix',[$dFrom,$dTo]);
                }


                if ($request->has('cancelstatus'))
                    $query->where('class_cancel_status','=',$request->input('cancelstatus'));

                if ($request->has('status') || $request->has('p') ){
                    $class_status = '-';

                    // var_dump((int)$request->input('status')); die();
                    switch ((int)$request->input('status')) {
                        case 0:
                            $class_status = 'Valid';
                            break;
                        case 1:
                            $class_status = 'Transferred';
                            break;
                        case 2:
                            $class_status = 'Invalid';
                            break;
                        case 3:
                            $class_status = 'Complaints';
                            break;
                        case 4:
                            $class_status = 'Cancelled';
                            break;
                    }

                    // if ($request->input('p') == 'profile'){
                    //     $class_status = 'Valid';
                    // }

                    if($request->has('status')){
                        $query->where('class_status','=',$class_status);
                    }

                    if($request->has('clc')){
                        $query->where('running_class_count','=',$request->input('clc'));
                    }
                }

                if ($request->has('comment'))
                    $query->where('class_comment','=',$request->input('comment'));

                if ($request->has('checkedin'))
                    $query->where('teachersignin','=',$request->input('checkedin'));

                if ($request->has('category'))
                    $query->where('class_category','=',$request->input('category'));
            })
            ->get();
        return $data;
    }

    public function check_username($username) {
        $current_timestamp = time();
        return $this::from('ims_users as u')
           ->select('us.email', 'u.uid')
           ->leftJoin('ims_users_profile as up', 'u.uid', '=', 'up.uid')
           ->leftJoin(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'employee') . ' as e', 'up.mobile', '=', 'e.phone')
           ->leftJoin(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'users') . ' as us', 'e.user_id', '=', 'us.id')
           ->where('u.username', $username)
           ->where('u.groupid', 7)
           ->whereRaw('not (u.leavestatus=1 and u.leavestime <"'.$current_timestamp.'")')
           ->where('u.base', 1)
           ->where('up.mobile', '!=', '')
           ->first();
    }

    public function add_otp($data){
        if(!empty($data)){
            return $this::from('ims_users_otp')
                    ->insert($data);

        }else{
            return false;
        }

    }

    public function check_existing_otp($uid){
           return $this::from('ims_users_otp')
           ->select('expired_time')
           ->where('status', 0)
           ->where('expired_time', '>', time())
           ->first();
    }

    public function validate_otp($username, $otp, $forChangePw){
        $uid = $this->check_username($username)->uid;

        $validate = $this::from('ims_users_otp')
           ->select('id')
           ->where('status', 0)
           ->where('expired_time', '>', time())
           ->where('otp', $otp)
           ->where('uid', $uid)
           ->first();

        if($forChangePw && !empty($validate)){
            return ["otp_id" => $validate->id, 'uid' => $uid];
        }else{
            return !empty($validate) ? true : false;
        }
    }

    public function change_pw($username, $otp, $password, $salt){
        $otp = $this->validate_otp($username, $otp, true);
        if(!empty($otp['otp_id'])){
            //change status to 1 (used) pw was changed
            $data =  $this::from('ims_users_otp')
            ->where('id','=',$otp['otp_id'])
            ->update(['status' => 1]);

            $results = $this::from('ims_users')
            ->where('uid', $otp['uid'])
            ->update(['password' => $password,'salt'=> $salt]);

            return $results;
            //INSERT code for password
            return true;
        }else{
            return false;
        }
    }

    public function get_bb_level_history($edu_id){
        $data = $this->db_payroll->table('bb_level_history')
            ->where('edu_id','=',$edu_id)
            ->orderBy('date_created','ASC')
            ->first();
        return $data;
    }

     public function test_count($uid, $start_date)
    {
        return $this->db_edu->table('ims_talk_lession as tl')
            ->where('tl.teachersignin','=',1)
            ->where('tl.commentstatus','=',1)
            ->where('tl.category','!=',0)
            ->whereIn('tl.cancelstatus',[0,4,5])
            ->where('tl.talktime', '>=', strtotime($start_date))
            ->where('tl.talktime', '<=', strtotime('-1 days'))
            ->where('tl.teacherid', '=', $uid)
            ->count();
    }

    public function test_countv2($uid, $start_date,$d1)
    {
        return $this->db_edu->table('ims_talk_lession as tl')
            ->where('tl.teachersignin','=',1)
            ->where('tl.commentstatus','=',1)
            ->where('tl.category','!=',0)
            ->whereIn('tl.cancelstatus',[0,4,5])
            ->where('tl.talktime', '>=', strtotime($start_date))
            ->where('tl.talktime', '<=', $d1)
            ->where('tl.teacherid', '=', $uid)
            ->count();
    }

    public function get_acc($uid, $start_date)
    {
        return $this->db_edu->table('ims_talk_lession as tl')
            ->where('tl.teachersignin','=',1)
            ->where('tl.commentstatus','=',1)
            ->where('tl.category','!=',0)
            ->whereIn('tl.cancelstatus',[0,4,5])
            ->where('tl.talktime', '>=', strtotime($start_date))
            ->where('tl.talktime', '<=', strtotime('-1 days'))
            ->where('tl.teacherid', '=', $uid)
            ->count();
    }

    public function insert_level_history($data){
        if (!empty($data)) {

            //check
            $check = $this->db_payroll->table('bb_level_history')
                ->where('current_level','=',$data['current_level'])
                ->where('edu_id','=',$data['edu_id'])
                ->first();
            if ($check == null) {
                return $this->db_payroll->table('bb_level_history')->insert($data);
            }else{
                return FALSE;
            }

            
        }
        return FALSE;
    }

    public function get_all_complaints($edu_id){
        $data = $this->db_payroll->table('hb_complaints')
            ->where('teacher_id','=',$edu_id)
            ->count();
        return $data;
    }

    public function get_monthly_earnigs_log($edu_id,$month){
        $from   = date('Y-m-01 00:00:00',strtotime($month));
        $to     = date('Y-m-t 23:59:59',strtotime($month));

        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->whereBetween('class_date',[$from,$to])
            ->get();
        return $data;
    }


    public function get_max_talktime($edu_id){
        $this->select = [
            DB::raw("MAX(talktime) AS max_talktime"),
        ];
        $data = $this->db_edu->table('ims_talk_lession')
            ->select($this->select)
            ->where('teacherid','=',$edu_id)
            ->where('prestatus','=',0)
            ->where('restatus','=',0)
            ->where('changestatus','=',0)
            ->get();
        return $data;
    }

    public function get_bb_payroll($teacher_id,$month){
        $data = $this->db_payroll->table('hb_payroll')
            ->where('teacher_id','=',$teacher_id)
            ->where('cutoff_from','LIKE',"%$month%")
            ->get();
        return $data;
    }

    public function get_bb_pi($user_id,$month){
        $data = $this->db_payroll->table('hb_pi')
            ->where('emp_id','=',$user_id)
            ->where('cutoff_from','LIKE',"%$month%")
            ->get();
            return $data;
    }

    public function get_bb_trainings($edu_id,$month){
        /*$data = $this->db_payroll->table('hb_trainings')
            ->where()
            ->where()
            ->get();
            return $data;*/
    }

    public function get_bb_referrals($edu_id,$month){
      /*  $data = $this->db_payroll->table('hb_referral')
            ->where()
            ->where()
            ->get();
            return $data;*/
    }

    public function get_bb_details($edu_id){
        $this->select = [
            "e.user_id",
            DB::raw("CONCAT(e.lastName,', ',e.firstName,' ',e.middleName) AS teacher"),
            "up.mobile","up.uid AS edu_id",
            "e.teacher_level AS starting_level",
            "e.entry_date",
            "e.ding_emp_id",
            "e.id as row_id"
        ];
        $data = $this->db_recruitment->table('employee AS e')
                ->join(Helper::DB_TABLE('DB_DATABASE_UTALK', 'ims_users_profile') . ' AS up','e.phone','=','up.mobile')
                ->select($this->select)
                ->where('e.base','=',1)
                ->where('e.dismissed','=',0)
                ->where('e.disabled','=',0)
                ->where('up.uid','=',$edu_id)
                ->orderBy('e.lastName')
                ->first();
        return $data;
    }

    public function get_transferred_class_details($class_id){
        $data = DB::table('ims_talk_lession AS tl')
            ->select('tl.id',
                'tl.teacherid',
                't.transfer_reason_en',
                't.newteacher_id',
                'up.nickname',
                'tl.talktime'
            )
            ->leftJoin('ims_sc_talk_lession_transfer AS t','t.lession_id','=','tl.id')
            ->leftJoin('ims_users_profile AS up','t.newteacher_id','=','up.uid')
            ->where('tl.id','=',$class_id)
            ->first();
            return $data;
    }

    public function get_earnings_logs($class_id){
        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('class_id','=',$class_id)
            ->first();
        return $data;
    }

    public function get_valid_ids($edu_id){
        $data = DB::table('ims_talk_lession as tl')
                    ->leftJoin('ims_lession_status as tr', 'tl.id','=','tr.lessionid')
                    ->leftJoin(Helper::DB_TABLE('DB_DATABASE_PAYROLL', 'bb_earnings_log') . ' AS bel','tl.id','=','bel.class_id')
                    ->select('tl.id')
                    ->where('tl.teacherid','=',$edu_id)
                    ->where('tl.teachersignin','=',1)
                    ->where('tl.commentstatus','=',1)
                    ->whereIn('tl.cancelstatus', [0,4,5])
                    ->where('tl.category','!=', 0)
                    ->where(function($query){
                        $query->where('bel.video_duration','!=', '--');
                        $query->Where('bel.video_duration','!=', '');
                        $query->Where('bel.video_duration','>', '25');
                    })
                    ->get();
        return $data;
    }

    public function get_complaint_ids($edu_id){
        $data = $this->db_payroll->table('hb_complaints')
                ->select('class_id')
                ->where('teacher_id','=',$edu_id)
                ->get();
    }

    public function get_subclass_count($edu_id,$request){
         $sql_raw = '';

        if ($request->has('month')) {
            $month  = $request->input('month');
            $dFrom  = date('Y-m-01 00:00:00',strtotime($month));
            $dTo    = date('Y-m-t 23:59:59',strtotime($month));
            $sql_raw = [$dFrom,$dTo];
        }

        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->when($sql_raw,function($query) use($sql_raw){
                    return $query->whereBetween('class_date',$sql_raw);
                })
            ->where('class_type','=','Subclass')
            ->count();
        return $data;
    }

    public function check_if_class_is_tranferred($class_id){
        $data = $this->db_edu->table('ims_sc_talk_lession_transfer')
            ->where('lession_id','=',$class_id)
            ->orderBy('create_time','DESC')
            ->first();
            return $data;
    }

    public function talk_holiday($cron_date){

        $data = $this->db_edu->table('ims_talk_holiday AS h')
            ->whereRaw("'{$cron_date}' >= h.date_from  AND '{$cron_date}' <= h.date_to")
            ->first();
        return $data;
    }

    public function get_current_class_rate($edu_id){
        $cl_data = $this->db_payroll->table('bb_earnings_log')
        ->select('current_level')
        ->where('edu_id','=',$edu_id)
        ->orderBy('date_created','DESC')
        ->first();

        if ($cl_data != null) {
            $cl = $cl_data->current_level;
            $cr_data = $this->get_teachers_rate($cl);
            $data = $cr_data->class_rate;
        }
        return $data;
    }

    public function delete_earnings_log($date){
        $this->db_payroll->table('bb_earnings_log')
            ->whereRaw("DATE_FORMAT(class_date,'%Y-%m-%d') >= '{$date}'")
            ->delete();

    }
    //again
    public function delete_earnings_log_single($date,$request){
        $this->db_payroll->table('bb_earnings_log')
            ->whereRaw("DATE_FORMAT(class_date,'%Y-%m-%d') >= '{$date}'")
            ->where(function($query) use ($request){
                if ($request != null) {
                    if ($request->has('edu_id')) {
                        $query->where('edu_id','=',$request->input('edu_id'));
                    }
                }
            })
            ->delete();

    }

    public function getNoteType($class_id, $edu_id){
        $data = $this->db_payroll->table('bb_earnings_deduction_logs')
            ->where('class_id','=', $class_id)
            ->where('edu_id','=', $edu_id)
            ->orderBy('id', 'DESC')->first();
        return $data ? $data->t_type : false;
    }

    public function getNoteTypev2($class_ids = [], $edu_ids = []){
        $data = $this->db_payroll->table('bb_earnings_deduction_logs')
            ->whereIn('class_id', $class_ids)
            ->whereIn('edu_id', $edu_ids)
            ->orderBy('id', 'DESC')->get();
        return $data ? $data : false;
    }

    public function get_auto_transfer($edu_id,$talktime){
        $data = $this->db_edu->table('ims_auto_transfer')
            ->whereRaw("{$talktime} >= `from` AND {$talktime} <= `to`")
            ->where('teacher_id','=',$edu_id)
            ->first();
            return $data;
    }

    public function get_tvc_history($edu_id){
        $total = $this->db_payroll->table('bb_level_history')
            ->where('edu_id','=',$edu_id)
            ->sum('level_up_count');
        return $total;
    }

    public function get_class_info($class_id){
        if (is_array($class_id) && !empty($class_id)) {
            $data = $this->db_edu->table('ims_talk_lession')
            ->select('id','talktime','teachersignin','cancelstatus','commentstatus','category',DB::raw("FROM_UNIXTIME(talktime,'%Y-%m-%d %H:%i:%s') as readable_talktime"))
            ->whereIn('id',$class_id)
            ->orderBy('talktime', 'ASC')
            ->get();
        }else{
             $data = $this->db_edu->table('ims_talk_lession')
            ->where('id','=',$class_id)
            ->first();
        }
       
        return $data;
    }

    public function get_el_records($edu_id,$class_date){
        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->where('class_date_unix','>=',$class_date)
            ->get();
        return $data;
    }


    public function get_reason_lession_transfer($class_ids){
        if(!$class_ids){
            return false;
        }
        $data = $this->db_edu->table('ims_sc_talk_lession_transfer')
        ->whereIn('lession_id',$class_ids)
        ->orderBy('id', 'DESC')
        ->get();
        
        return !empty($data) ? $data : false;
    }


}
