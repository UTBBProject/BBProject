<?php

namespace App\Models\Edu;
use App\Libraries\Helpers as Helper;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Lumen\Auth\Authorizable;
class Edu_class extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;
    protected $db_recruitment;
    protected $db_payroll;
    protected $db_edu;
    protected $sql_raw;
    protected $select;
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
                ->join('utalk2016.ims_users_profile AS up','e.phone','=','up.mobile')
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

    public function get_bb_employees(){
        $this->select = [
            "e.user_id",
            DB::raw("CONCAT(e.lastName,', ',e.firstName,' ',e.middleName) AS teacher"),
            "up.mobile","up.uid AS edu_id",
            "e.teacher_level AS starting_level",
            "e.entry_date",
            "e.ding_emp_id"
        ];
        $data = $this->db_recruitment->table('employee AS e')
                ->join('utalk2016.ims_users_profile AS up','e.phone','=','up.mobile')
                ->select($this->select)
                ->where('e.base','=',1)
                ->where('e.dismissed','=',0)
                ->where('e.disabled','=',0)
                //->where('up.uid','=',2801)
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

    public function get_running_count($edu_id){
        $data = $this->db_payroll->table('bb_earnings_log')
            ->select('running_class_count','current_level')
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
            $data = $this->db_edu->table('ims_sc_talk_lession_transfer AS t')->select('l.id','t.oldteacher_id AS teacherid','l.studentid','l.talktime','l.teachersignin','l.commentstatus','l.cancelstatus','l.category')
            ->leftJoin('ims_talk_lession AS l','t.oldteacher_id','=','l.teacherid')
            ->where('l.talktime','>=',$d_from)
            ->where('l.talktime','<=',$d_to)
            ->where('t.is_recovered','=',0)
            ->whereIn('t.oldteacher_id',$edu_ids)
            ->orderBy('l.talktime')
            ->groupBy('l.id')
            ->get();
            return $data;
        }
        return [];
    }

    public function get_teachers_rate($level){
        $data = $this->db_payroll->table('hb_class_rate')
            ->where('level','=',$level)
            ->first();
        return $data;
    }

    public function get_complaints($edu_id,$class_id){
        $data = $this->db_payroll->table('hb_complaints')
            ->where('teacher_id','=',$edu_id)
            ->where('class_id','=',$class_id)
            ->first();
        return $data;
    }

    public function transaction($data = [],$command = 1){
        if ($command == 1) {
             $this->db_payroll->transaction(function() use ($data) {
                foreach($data as $d) {
                    $this->db_payroll->table('bb_earnings_log')->insert($d);
                }
            });
        }

    }

    public function er_data($edu_id){
        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
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

    public function get_profile($uid)
    {
        return $this->db_edu->table('ims_users as u')
            ->select('up.avatar', 'e.id as row_id','u.uid as teacher_id', 'e.user_id', 'up.realname as teacher_name', 'up.mobile', 'e.entry_date', 'e.teacher_level')
            ->leftJoin('ims_users_profile as up', 'u.uid', '=', 'up.uid')
            ->leftJoin(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'employee').' as e', 'up.mobile', '=', 'e.phone')
            ->orderBy('e.user_id', 'DESC')
            ->where('u.uid', '=', $uid)
            ->first();
    }

    public function get_accumulated_course_count($uid, $start_date)
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

    public function get_deduction_count_since_last_rank_up($uid)
    {
        return $this->db_payroll->table('bb_earnings_log as bel')
            ->where('bel.class_count', '<', '0')
            ->where('bel.edu_id', '=', $uid)
            ->count();
    }

    public function get_total_class_since_rank_up($uid, $current_rank)
    {
        return $this->db_payroll->table('bb_earnings_log as bel')
            ->where('bel.edu_id', '=', $uid)
            ->where('bel.current_level', '=', $current_rank)
            ->sum('bel.class_count');
    }

    public function get_starting_level($order_id, $uid,$edu_id)
    {
        $data = $this->db_recruitment->table('level_log')
        ->select('level')
        ->where('emp_id','=',$order_id)
        ->orderBy('created_at')
        ->first();

        $level = $this->db_payroll->table('bb_earnings_log')
        ->select('current_level as level')
        ->where('user_id','=',$uid)
        ->orderBy('date_created')
        ->first();

        if ($edu_id) {
            $level_history =  $this->db_payroll->table('bb_level_history')
            ->select('old_level as level','level_up_count')
            ->where('edu_id','=',$edu_id)
            ->orderBy('date_created','ASC')
            ->first();
        }
        
        if ($data != null) {
            return $data;
        }elseif($level_history != null){
            return $level_history;
        }elseif ($level != null) {
            return $level;
        }

        // return $data ? $data : $level;
    }

    public function get_previous_level($uid)
    {
        $level = '--';
        if ($uid) {
            $level =  $this->db_payroll->table('bb_level_history')
            ->select('old_level as level','level_up_count')
            ->where('user_id','=',$uid)
            ->orderBy('date_created','DESC')
            ->first();
        }
        return $level;
    }

    public function get_student_profile($student_ids){
        $data = DB::table('ims_mc_members')
        ->select('uid','realname','nickname','qq','uniacid','groupid','mobile')
        ->whereIn('uid',$student_ids)->get();
        return $data;
    }

    public function DUMP_QUERY($connection){
        $queries    = $connection->getQueryLog();
        $lastQuery = end($queries);
        dd($queries);
    }

    public function get_accumulated_earnings_count($edu_id){
        // $date = date('Y-m-d',strtotime('-1 day'));
        $data = $this->db_payroll->table('bb_earnings_log')
            ->where('edu_id','=',$edu_id)
            ->where(function($query){
                $query->where('class_count','=',1);
                $query->orWhere('total_valid_class','>',0);
            })
            // ->where('class_date','<=',$date)
            ->orderBy('id','desc')
            ->first();
        return $data;
    }

    public function check_number_from_employee($ims_user_profile_number){
        $data = $this->db_recruitment->table('employee')
            ->where('phone','=',$ims_user_profile_number)
            ->first();


        return empty($data) ? false : true;
    }
}
