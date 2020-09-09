import ChildPayslip from '@/components/payslip/child/child_payslip';
// import ChildTable from '@/components/payslip/child/child_table';
import {api} from '@/constants';
import {mapActions} from 'vuex';
import * as html2pdf from 'html2pdf.js';
// const html2pdf = () => import('html2pdf.js');

export default {
    components: {
        ChildPayslip
    },
    data() {
        return {
            search_table:{
                cutoff_from: '',
                cutoff_to: ''
            },
            search:{
                onLoad: false,
                date: '2019-11',
                cutoff: 1
            },
            view: 2,
            option:{
                cutoff: [ 
                    {value: 1, text : '1st Cutoff'},
                    {value: 2, text : '2nd Cutoff'},
                ]
            },
            exporting: false,
            payslip:{
                NoData: false,
                height: '',
                loading: false,
                employeeName: '',
                position: '',
                dateHired: '',
                teacherLevel: '',
                classRate: '',
                employeeType: '',
                payrollRegister: '',
                payPeriod: '',
                payDate: '',
                PHIC: '',
                HDMF: '',
                TIN: '',
                SSS: '',
                serviceFee: '',
                trainingFee: '',
                observationFee: '',
                salaryAdjustment: '',
                accountNumber: '',
                bankName: '',
                attendanceIncentives: '',
                performanceBonus: '',
                partialReferralBonus: '',
                classIncentive: '',
                perfomanceImprovement: '',
                transferredClass: '',
                less25Minutes: '',
                deductionsClass: '',
                callin: '',
                awol: '',
                less25MinutesDeduction: '',
                grossIncome: '',
                totalDeduction: '',
                NETPAY: '',
                employeeSignature: '',
                preparedBy: '',
                totalCourseIncentives: '',
                late_20:"",
                late_10:"",
                video_duration_deduction: "",
                l_30_transferred: "",
                g_30_transferred: ""
            }
        }
    },
    
    computed: {
        
    },
    methods: {
        ...mapActions({
            API_GET: 'API_GET'
        }),
        exportPayslip(){
            this.exporting = true;
            let d = new Date();
            let add = d.getFullYear()+'_'+d.getDate()+'_'+(d.getMonth()+1);
            const options = {
                filename: 'payslip_'+add+'.pdf',
                image: {type: 'png'},
                html2canvas: { scale: 2 },
                jsPDF: {orientation: 'portrait'},
                margin: [ 5,5,5,5 ],
            };

            html2pdf()
            .from(document.getElementById('convert-pdf'))
            .set(options)
            .save();
            this.exporting = false;
        },
        searchPeriod(){
            this.payslip.NoData = false;
            this.search.onLoad = true;
            this.payslip.loading = true;
            this.exporting = true;
            this.getPayslipDetails();
        },
        getPayslipDetails_old(){
            let url = api+'get-payroll-v2';
            let param = this.search;
            var that = this;
        
            this.API_GET({url,param}).then((res) => {
                if(res.hasdata == false){
                    that.payslip.NoData = true;
                    that.search.onLoad = false;
                    that.payslip.loading = false;
                }
                this.payslip.NoData = false;
                that.search.onLoad = false;
                that.payslip.loading = false;
                this.exporting = false;
                
                // employee pay slip info
                that.payslip.employeeName = res.info.emp_name;
                that.payslip.position = res.info.position;
                
                that.payslip.dateHired = res.info.entry_date;
                that.payslip.teacherLevel = res.info.level;
                that.payslip.classRate = res.class_rate+"";
                that.payslip.employeeType = res.info.emp_type;
                that.payslip.payPeriod = res.info.cutoff_from+" to "+res.info.cutoff_to;
                that.payslip.payDate = res.date_pay;

                // tax accounts
                // that.payslip.PHIC = res.tax_account.philhealth;
                // that.payslip.TIN = res.tax_account.tin;
                // that.payslip.HDMF = res.tax_account.pagibig;
                // that.payslip.SSS = res.tax_account.sss;

                
                // bonus
                that.payslip.attendanceIncentives = res.payroll.referral_bonus.toFixed(2)+"";
                that.payslip.performanceBonus = res.payroll.pb_bonus==null ? 0.00 : '';
                that.payslip.partialReferralBonus = res.payroll.referral_bonus.toFixed(2)+"";
                that.payslip.classIncentive = '';
                
                that.payslip.perfomanceImprovement = res.payroll.pi_bonus;

                
                // income
                that.payslip.serviceFee = res.payroll.service_fee;
                that.payslip.trainingFee = '';
                that.payslip.observationFee = '';

                
                // deductions
                that.payslip.less25Minutes = res.deductions.lateClass; // late for class
                that.payslip.callin = res.deductions.callin;
                that.payslip.awol = res.deductions.awol;
                that.payslip.less25MinutesDeduction = res.deductions.lateShift;  // late for shift

                that.payslip.late_20 = res.deductions.late_20;
                that.payslip.late_10 = res.deductions.late_10;

                
                // total
                that.payslip.grossIncome = res.payroll.total_income;
                that.payslip.totalDeduction = res.payroll.total_deductions;
                that.payslip.NETPAY = res.payroll.net_pay;
                that.payslip.totalCourseIncentives = res.payroll.ci_bonus;

                
                // payment information
                // that.payslip.accountNumber = res.tax_account.bank_card_number;
                // that.payslip.bankName = res.tax_account.deposit_bank;
                // that.payslip.employeeSignature = "";
                // that.payslip.preparedBy = "";
                
                
            }).catch(() => {

            })
        },
        getPayslipDetails(){
            let url = api+'get-payroll-v2';
            let param = this.search;
            var that = this;
        
            this.API_GET({url,param}).then((res) => {
                if(res.hasdata == false || res.bb_payroll == false){
                    that.payslip.NoData = true;
                    that.search.onLoad = false;
                    that.payslip.loading = false;
                    return;
                }
                
                // employee pay slip info
                that.payslip.employeeName = res.bb_payroll.teacher;
                that.payslip.position = '';
                
                that.payslip.dateHired = res.bb_payroll.entry_date;
                that.payslip.teacherLevel = res.bb_payroll.teacher_level;
                that.payslip.classRate = res.bb_payroll.class_rate;
                that.payslip.employeeType = '';
                that.payslip.payPeriod = res.bb_payroll.pay_period;
                that.payslip.payDate = res.bb_payroll.pay_date;

                // tax accounts
                // that.payslip.PHIC = res.tax_account.philhealth;
                // that.payslip.TIN = res.tax_account.tin;
                // that.payslip.HDMF = res.tax_account.pagibig;
                // that.payslip.SSS = res.tax_account.sss;

                
                // bonus
                that.payslip.attendanceIncentives = res.bb_payroll.attendance_incentive;
                that.payslip.performanceBonus = res.bb_payroll.performance_bonus;
                that.payslip.partialReferralBonus = res.bb_payroll.partial_ref_bonus;
                that.payslip.classIncentive = '';
                
                that.payslip.perfomanceImprovement = res.bb_payroll.performance_improvement;

                
                // income
                that.payslip.serviceFee = res.bb_payroll.total_service_fee;
                that.payslip.trainingFee = res.bb_payroll.training_fee;
                that.payslip.observationFee = res.bb_payroll.observation_fee;

                
                // deductions
                that.payslip.less25Minutes = res.bb_payroll.late_25; // late for class
                that.payslip.callin = res.bb_payroll.transferred_deduction;
                that.payslip.awol = res.bb_payroll.awol_amount;
                that.payslip.less25MinutesDeduction = res.bb_payroll.late_for_shift;  // late for shift

                that.payslip.late_20 = res.bb_payroll.late_20;
                that.payslip.late_10 = res.bb_payroll.late_10;

                
                // total
                that.payslip.grossIncome = res.bb_payroll.gross_income;
                that.payslip.totalDeduction = res.bb_payroll.total_deduction;
                that.payslip.NETPAY = res.bb_payroll.total_netpay;
                that.payslip.totalCourseIncentives = res.bb_payroll.total_course_incentive;

                //
                that.payslip.video_duration_deduction = res.bb_payroll.video_duration_deduction;

                that.payslip.l_30_transferred = res.bb_payroll.l_30_transferred;
                that.payslip.g_30_transferred = res.bb_payroll.g_30_transferred;

                that.payslip.sub_class_rate = res.bb_payroll.sub_class_rate;
                that.payslip.week_end_rate = res.bb_payroll.week_end_rate;
                
                // payment information
                // that.payslip.accountNumber = res.tax_account.bank_card_number;
                // that.payslip.bankName = res.tax_account.deposit_bank;
                // that.payslip.employeeSignature = "";
                // that.payslip.preparedBy = "";

                this.payslip.NoData = false;
                that.search.onLoad = false;
                that.payslip.loading = false;
                this.exporting = false;
                
                
            }).catch(() => {

            })
        }
    },
    mounted(){
        let d = new Date();
        let month = d.getMonth()+1;
        let day = d.getDate();
        let year = d.getFullYear();

        if(day >= 25){
            // if date greater than 25 show 1rst cutoff for this month
            this.search.cutoff = 1;
            month = (d.getMonth()+1);
        }else{ // else if date is less than show 2nd cutoff for the last month
            this.search.cutoff = 2;
            // if less than 10, show last month cutoff
            if(day < 10){
                this.search.cutoff = 2;
            }
            // month will go backward 1 time
            month = d.getMonth();
            if(month == 0){
                // if month is zero
                year = d.getFullYear()-1;
                month = 12;
            }  
        }

        this.search.date = year+'-'+(month.toString().length==1 ? "0"+month : month);
        this.search.onLoad = true;
        this.payslip.loading = true;
        this.payslip.loading = true;
        this.exporting = true;
        this.getPayslipDetails();
    }
}