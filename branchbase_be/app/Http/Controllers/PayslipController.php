<?php
namespace App\Http\Controllers;
use App\Models\Payslip\Payslip as Payslip;
use App\Libraries\Helpers as Helper;
use App\Models\Employee\Employees as Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination;

class PayslipController extends Controller
{
    protected $payslip_model;
    public function __construct()
    {
        $this->payslip_model = new Payslip;
    }

    public function getPayslip(Request $request){
        $date = $request->input('date');
        $cutoff = $request->input('cutoff');

        // transfered deductions
        $trans_deduction = [];
        $total_callin_deduction = 0;
        $total_awol_deduction = 0;
        $total_lateShift_deduction = 0;
        $total_lateClass_deduction = 0;
        $total_late_20 = 0;
        $total_late_10 = 0;

        $get_td = $this->payslip_model->get_transferred_deductions(Auth::user()->uid,$date,$cutoff);
        if(!$get_td){
            $trans_deduction = [
                "callin"=>$total_callin_deduction,
                "awol"=>$total_awol_deduction,
                "lateShift"=>$total_lateShift_deduction,
                "lateClass"=>$total_lateClass_deduction,
                "late_20"=>$total_late_20,
                "late_10"=>$total_late_10
            ]; 
        }else{
            
            foreach ($get_td as $key => $value) {
                $total_callin_deduction += $value->t_type == 0 ? $value->deduction_amount : 0;
                $total_awol_deduction += $value->t_type == 1 ? $value->deduction_amount : 0;
                $total_lateShift_deduction += $value->t_type == 2 ? $value->deduction_amount : 0;
                $total_lateClass_deduction += $value->t_type == 3 ? $value->deduction_amount : 0;
                $total_late_20 += $value->t_type == 4 ? $value->deduction_amount : 0;
                $total_late_10 += $value->t_type == 5 ? $value->deduction_amount : 0;
            }
            $trans_deduction = [
                "callin"=>$total_callin_deduction,
                "awol"=>$total_awol_deduction,
                "lateShift"=>$total_lateShift_deduction,
                "lateClass"=>$total_lateClass_deduction,
                "late_20"=>$total_late_20,
                "late_10"=>$total_late_10
            ];
        }


        $from = 0;
        $to = 0;

        if($cutoff == 1){
            $from = date('Y-m-01', strtotime($date));
            $to = date('Y-m-15', strtotime($date));
        }else{
            $from = date('Y-m-16', strtotime($date));
            $to = date('Y-m-t', strtotime($date));
        }

        // pay date
        $pay_date = "";
        $pay_date_dash = "";
        if(date('d',strtotime($from)) < 15){
            $pay_date = date('F 25, Y', strtotime($date));
            $pay_date_dash = date('Y-m-25', strtotime($date));
        }else{
            $pay_date = date('F 10, Y', strtotime($date.' +1 month'));
            $pay_date_dash = date('Y-m-10', strtotime($date.' +1 month'));
        }

        // check if paydate
        $date_now = date("Y-m-d");
        if($pay_date_dash > $date_now ){
            return response()->json(["hasdata"=>false, "message"=>"Payslip Not Generated Yet"]); die();
        }

        $info = $this->payslip_model->get_employee_info(Auth::user()->uid,$date,$cutoff);
        if(!$info){
            // return this if no info has no data
            return response()->json(["hasdata"=>false, "message"=>"Payslip Not Generated Yet"]); die();
        }
        $payroll = $this->payslip_model->get_hb_payroll(Auth::user()->uid,$date,$cutoff);
        if(!$payroll){
            // return this if no info has no data
            return response()->json(["hasdata"=>false, "message"=>"Payslip Not Generated Yet"]); die();
        }
        


        $per_bonus = $this->payslip_model->get_performance_bonus(Auth::user()->uid, $date);
        
        


        $response = [
            "hasdata"=>true,
            "item_filter"=>[
                "date"=>$date,
                "cutoff"=>$cutoff,
            ],
            "info"=>$info[0],
            // "date_pay"=>date("M",strtotime($date)).' '.($cutoff==1 ? 10:25).' '.date("Y",strtotime($date)),
            // "date_pay"=>date("Y-m-",strtotime($date)).''.($cutoff==1 ? 10:25),
            "date_pay"=> $pay_date,
            "class_rate"=>$this->payslip_model->get_class_rate($info[0]->level)[0],
            // "tax_account"=>$this->payslip_model->get_tax_accounts($this->payslip_model->get_phone(Auth::user()->uid))[0],
            "payroll"=> $payroll[0],
            "deductions"=> $trans_deduction,
            "performance_bonus" => !$per_bonus->isEmpty() ? $per_bonus[0] : "",
            "performance_improvement" => $this->payslip_model->get_performance_improvement(Auth::user()->uid, $date),
        ];

        return response()->json($response);
    }


    public function getPayslipV2(Request $request){
        
        $date = $request->input('date');
        $cutoff = $request->input('cutoff');

        $get_bb_payroll = $this->payslip_model->get_bb_payroll(Auth::user()->uid,$date,$cutoff);

        $response = [
            "hasdata"=>isset($get_bb_payroll[0]) ? true : false,
            "item_filter"=>[
                "date"=>$date,
                "cutoff"=>$cutoff,
            ],
            "bb_payroll"=>isset($get_bb_payroll[0]) ? $get_bb_payroll[0] : ''
        ];

        return response()->json($response);
    
    }
}
