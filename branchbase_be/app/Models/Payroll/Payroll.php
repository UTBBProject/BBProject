<?php

namespace App\Models\Payroll;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Lumen\Auth\Authorizable;
use App\Libraries\Helpers as Helper;
class Payroll extends Model 
{

    protected $db_recruitment;
    protected $db_payroll;
    protected $db_edu;
    protected $sql_raw;
    protected $select;
    public $timestamps = false; //By default, Eloquent expects created_at and updated_at columns to exist on your >tables. Disabled by this.
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

    public function get_bb_teachers($from,$to){
        $data = $this->db_recruitment->table(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'employee') . ' AS e')
        ->select('e.user_id','e.entry_date','up.uid AS edu_id','e.phone','e.teacher_level','e.firstName','e.lastName','e.middleName','e.ding_emp_id')
        ->join(Helper::DB_TABLE('DB_DATABASE_UTALK', 'ims_users_profile') . ' AS up','e.phone','=','up.mobile')
        ->where('e.entry_date','!=','0000-00-00')
        ->where('e.country','=','Philippines')
        ->where(function($query) use ($from,$to){
            $query->where(function($q){
                $q->where('e.disabled','=',0);
                $q->where('e.dismissed','=',0);
            });
            $query->orWhereBetween('e.end_date',[$from,$to]);
        })
        ->where('e.position','LIKE','%Online English%')
        ->where('e.base','=',1)
        //->where('up.uid','=',2989)
        ->get();
    
        return $data;
    }

    public function get_bb_teams($month){
        $data = $this->db_payroll->table('team')
            ->where('team_name','=','Team Home-Based')
            ->where('my_created','=',$month)
            ->get();
        return $data;
    }

    public function get_bb_subteams($team_ids){
        $data = $this->db_payroll->table('team_sub')
            ->whereIn('team_id',$team_ids)
            ->get();
        return $data;
    }

    public function get_bb_team_members($team_ids){
        $data = $this->db_payroll->table('team_members')
            ->select('team_member AS user_id','team_member_tid AS edu_id','team_id')
            ->whereIn('team_id',$team_ids)
            ->get();
        return $data;
    }

    public function DUMP_QUERY($connection){
        $queries    = $connection->getQueryLog();
        $lastQuery = end($queries);
        dd($queries);
    }
}
