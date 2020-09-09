<?php

namespace App\Models\Payslip;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Laravel\Lumen\Auth\Authorizable;

class Payslip extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
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

    public function get_employee_info($edu_id, $date, $cutoff){
        $data = $this->db_payroll
            ->table('hb_basic_info')
            ->where('edu_id','=',$edu_id)
            ->where('cutoff_from','>=', $cutoff==1 ? $date.'-1': $date.'-16')
            ->where('cutoff_from','<=', $cutoff==1 ? $date.'-15': $date.'-30')
            // ->where(function($query) use ($cutoff, $date){
            //     if($cutoff == 1){
            //         // frsit cut off
            //         $query->where('cutoff_from','>=',$date.'-1')
            //             ->where('cutoff_to','<=',$date.'-15');
            //     }else{
            //         // second cutoff
            //         $query->where('cutoff_from','>=',$date.'-16')
            //             ->where('cutoff_to','<=',$date.'-30');
            //     }
            // })
            ->get();
        return $data->all();
    }
    

    public function get_class_rate($level){
        $data = $this->db_payroll
            ->table("hb_class_rate")
            ->where('level','=',$level)
            ->pluck('class_rate');
        
        return $data;
    }

    public function get_phone($user_id){
        $results = $this->db_edu->table('ims_users_profile')
            ->where('uid','=',$user_id)
            ->pluck('mobile');
        return $results;
    }

    // public function get_tax_accounts($phone){
    //     $data = $this->db_recruitment
    //         ->table('employee')
    //         ->select('sss','philhealth','tin','pagibig','currency','bank_card_number','deposit_bank')
    //         ->where('phone','=',$phone)
    //         ->get();

    //     return $data;
    // }

    public function get_transferred_deductions($edu_id,$month,$cutoff){
        $data = $this->db_payroll
            ->table('bb_earnings_deduction_logs')
            ->select('class_id','class_date','t_type', 'deduction_amount')
            ->where('class_date','>=',$month.($cutoff == 1 ? '-1':'-16'))
            ->where('class_date','<=',$month.($cutoff == 1 ? '-15':'-31'))
            ->where('edu_id','=',$edu_id)
            ->get();

        return !$data->isEmpty() ? $data : '';
    }

    public function get_performance_bonus($edu_id, $month){
        $data = $this->db_payroll
            ->table('hb_performance_bonus')
            ->select('pb_bonus','score','member_eic','basic_salary')
            ->where('pb_month','=',$month)
            ->where('teacher_tid','=',$edu_id)
            ->get();

        return $data;
    }

    public function get_performance_improvement($uid, $month){
        $data = $this->db_payroll
            ->table('hb_performance_improvement')
            ->select('rate','hourly_rate','demo_count','hours_rendered')
            ->where('period','=',$month)
            ->where('teacher_id','=',$uid)
            ->get();

        return !$data->isEmpty() ? $data : '';
    }

    public function get_hb_payroll($uid, $date, $cutoff){
        $data = $this->db_payroll
            
            ->table('hb_payroll')
            ->select("teacher_name","referral_bonus" ,"absence_deduction" ,"late_deduction" ,"transferred_deduction","less_25_deduction","attendance_bonus" ,"complaint_deduction" ,"callin_deduction","o_bonus" ,"t_bonus" ,"pb_bonus","pi_bonus" ,"ci_bonus","amount" ,"remark","service_fee","monthly_service_fee","total_income","total_deductions","net_pay","cutoff_from","cutoff_to", "cutoff")
            ->where('teacher_id','=',$uid)
            ->where(function($query) use ($date,$cutoff){
                if($cutoff==2){
                    $query->where('cutoff_from','>=', $cutoff==1 ? $date.'-1': $date.'-16')
                    ->where('cutoff_from','<=', $cutoff==1 ? $date.'-15': $date.'-30');
                }else{
                    $query->where('cutoff_to','>=', $cutoff==1 ? $date.'-1': $date.'-16')
                    ->where('cutoff_to','<=', $cutoff==1 ? $date.'-15': $date.'-30');
                }
            })
            
            ->get();
        
        
        return !$data->isEmpty() ? $data : '';
    }

    public function get_bb_payroll($user_id, $date, $cutoff){
        $data = $this->db_payroll
            
        ->table('bb_payroll')
        ->select("*")
        ->where('edu_id','=',$user_id)
        // this will find exacly what period
        ->where('pay_period','=',$date."-".($cutoff == 1 ? "01/15" : "16/".date("t", strtotime($date))))
        ->get();
    
        return !$data->isEmpty() ? $data : '';
    }

}