<template>
    <div class="utalk-payslip" :style="height==0 ? '': 'height: '+height+'px; overflow-y: scroll;'">
        <center v-if="NoData">
            <NoData title="Payslip Not Yet Generated.<br>Select period." style="height:400px; padding-top:100px;"/>
        </center>
        
        <center v-else-if="loading">
            <div class="payslip-loader">
                <Spinner style="height:300px; padding-top:20px;" />
            </div>
        </center>
        
        <table v-else>
            <tbody>
                <tr>
                    <td class="title-header line-left line-right" colspan="2">UTALK TUTORIAL SERVICES </td>
                    <td class="title-header line-right" colspan="2">BONUS</td>
                </tr>

                <tr>
                    <td width="25%"  class="line-left table-text">Employee Name</td>
                    <!-- Employees name -->
                    <td width="25%" class="line-right table-text">{{ employeeName=="" ? 'N/a': employeeName}}</td>
                    <td  width="25%" class="table-text">Attendance Incentives</td>
                    <!-- attendance -->
                    <td width="25%" class="line-right table-text">{{ attendanceIncentives=="" ? '0.00': attendanceIncentives.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="line-left table-text">Position</td>
                    <!-- position -->
                    <td class="line-right table-text">{{ position=="" ? 'N/a': position }}</td>
                    <td class="table-text">Performance Bonus</td>
                    <!-- performance bonus -->
                    <td class="line-right table-text">{{ performanceBonus=="" ||  performanceBonus==0 || performanceBonus == null? '0.00': performanceBonus.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="line-left table-text">Date Hired</td>
                    <!-- date hired -->
                    <td class="line-right table-text">{{ dateHired=="" ? 'N/a': dateHired }}</td>
                    <td class="table-text">Partial Referral Bonus</td>
                    <!-- partial bonus -->
                    <td class="line-right table-text">{{ partialReferralBonus=="" ? '0.00': partialReferralBonus.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="line-left table-text">Teacher Level</td>
                    <!-- teacher level -->
                    <td class="line-right table-text">{{ teacherLevel=="" ? 'N/a': teacherLevel }}</td>
                    <td class="table-text">Total Course Incentive</td>
                    <!-- class incentives -->
                    <td class="table-text line-right">{{ totalCourseIncentives=="" || totalCourseIncentives==0? '0.00': totalCourseIncentives.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}}</td>
                </tr>

                <tr>
                    <td class="line-left table-text">Class Rate</td>
                    <!-- class rete -->
                    <td class="line-right table-text">{{ classRate=="" ? 'N/a': classRate }}</td>
                    <td class="table-text">Performance Improvement</td>
                    <!-- performance improvement -->
                    <td class="table-text line-right">{{ perfomanceImprovement=="" || perfomanceImprovement==0? '0.00': perfomanceImprovement.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="line-left table-text">Employee Type</td>
                    <!-- employee type -->
                    <td class="line-right table-text">{{ employeeType=="" ? 'N/a': employeeType }}</td>
                    <td colspan="2" class="table-text line-right"></td>
                </tr>

                <tr>
                    <td class="line-left table-text">Payroll Register</td>
                    <td class="line-right table-text">{{ payrollRegister=="" ? 'N/a': payrollRegister }}</td>
                    <td colspan="2" class="table-text line-right"></td>
                </tr>

                <tr>
                    <td class="line-left table-text">Pay Period</td>
                    <!-- pay period -->
                    <td class="line-right table-text">{{ payPeriod=="" ? 'N/a': payPeriod }}</td>

                    <td colspan="2" class="line-right table-text table-text-bold"></td>
                </tr>

                <tr>
                    <td class="line-left table-text">Pay Date</td>
                    <!-- pay date -->
                    <td class="line-right table-text">{{ payDate=="" ? 'N/a': payDate }}</td>

                    <td class="title-header line-right" colspan="2">DEDUCTIONS</td>
                </tr>

                <tr>
                    <td colspan="2" class="line-left line-right table-text"></td>
                    
                    <td colspan="2" class="line-right table-text table-text-bold">Transferred Class</td>
                </tr>

                <tr>
                    <td colspan="2" class="line-left line-right table-text"></td>
                    <!-- <td class="line-left with-border table-text">PHIC: <span class="center">{{ PHIC=="" ? 'N/a': PHIC }}</span></td>
                    <td class="line-right with-border table-text">HDMF: <span class="center">{{ HDMF=="" ? 'N/a': HDMF }}</span></td> -->
                    {{ /* eslint-disable */ }}
                    <td class="table-text">
                        <span class="text-tab-left">Call-in </span><br>
                        <span class="text-tab-left">>=3hrs</span><br>
                        <span class="text-tab-left"><3hrs</span>
                    </td>
                    <td class="table-text line-right">
                        <!-- {{ callin=="" ? '0.00': callin.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}<br> --><br>
                        {{ g_30_transferred != '' || g_30_transferred != 0 ? g_30_transferred : '0.00' }}<br>
                        {{ l_30_transferred != '' || l_30_transferred != 0 ? l_30_transferred : '0.00' }}
                    </td>
                
                </tr>

                <tr>
                    <td colspan="2" class="line-left line-right table-text"></td>
                    <!-- <td class="line-left with-border table-text">TIN: <span class="center">{{ TIN=="" ? 'N/a': TIN }}</span></td>
                    <td class="line-right with-border table-text">SSS: <span class="center">{{ SSS=="" ? 'N/a': SSS }}</span></td> -->
                    <td class="table-text">
                        <span class="text-tab-left">Awol</span>
                    </td>
                    <td class="table-text line-right">{{ awol=="" ? '0.00': awol.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <!-- <tr>
                    <td colspan="2" class="row-blank table-text"></td>
                    <td class="table-text"></td>
                    <td class="line-right table-text"></td>
                    
                </tr> -->

                <tr>
                    <td colspan="2" class="line-left line-right title-header">INCOME</td>
                    <td colspan="2" class="line-left line-right"></td>
                </tr>

                <tr>
                    <td class="table-text line-left">Service Fee<br> (Class Rate * Total Valid Classess)</td>
                    <td class="table-text line-right"> {{ serviceFee=="" || serviceFee==0 ? '0.00': serviceFee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }} </td>
                    <td colspan="2" class="line-right table-text table-text-bold">Tardiness</td>
                </tr>

                <tr>
                    <td class="table-text line-left">Training Fee (9 Hrs)</td>
                    <td class="table-text line-right">{{trainingFee=="" ? '0.00': trainingFee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                    <td class="table-text table-text">
                        <span class="text-tab-left">Late for Shift</span><br>
                        <!-- <span class="text-tab-left">(10mins before class)</span> -->
                    </td>
                    <td class="table-text line-right">{{ less25MinutesDeduction=="" || less25MinutesDeduction==0 ? '0.00': less25MinutesDeduction.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td> 
                </tr>

                <tr>
                    <td class="line-left table-text">Observation Fee(6 Hrs)</td>
                    <td class="line-right table-text">{{ observationFee=="" ? '0.00': observationFee.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                    
                    <td class="table-text table-text">
                        <span class="text-tab-left">Late for Class</span><br>
                        <!-- <span class="text-tab-left">(Base on Duration)</span> -->
                        <span class="text-tab-sub">20mins=<duration<25mins</span><br>
                        <span class="text-tab-sub">10mins=<duration<20mins</span><br>
                        <span class="text-tab-sub">duration<10mins</span>
                    </td>
                    <td class="table-text line-right">
                        <br>
                        {{ less25Minutes=="" || less25Minutes==0? '0.00': less25Minutes.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}<br>
                        {{ late_20=="" || late_20 == 0 ? '0.00': late_20.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}<br>
                        {{ late_10=="" || late_10 == 0 ? '0.00': late_10.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}
                    </td>
                    
                </tr>
                <tr>
                    <td class="line-left line-right table-text">Week-end Rate</td>
                    <td class="line-left line-right table-text">{{ week_end_rate }}</td>
                    <td  class="line-right table-text table-text-bold"></td>
                    <td class="line-right table-text">
                        <!-- <span class="text-tab-sub"></span><br>
                        {{ video_duration_deduction != '' ? video_duration_deduction : '0.00' }} -->
                    </td>
                </tr>
                <tr>
                    <td class="line-left line-right table-text">Sub-class Rate</td>
                    <td class="line-left line-right table-text">{{ sub_class_rate }}</td>
                    <td class="line-right table-text">
                        <!-- <span class="text-tab-left">Video Duration Deduction</span><br> -->
                    </td>
                    <!-- <td class="line-right table-text">{{ video_duration_deduction != '' ? video_duration_deduction : '0.00' }}</td> -->
                    <td class="line-right table-text with-border-bottom">{{ totalDeduction=="" || totalDeduction ==0 ? '0.00': totalDeduction.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>
                <!-- <tr>
                    <td colspan="2" class="line-left line-right table-text row-blank"></td>
                    
                    <td class="line-right table-text"></td>
                    
                </tr> -->

                <tr>
                    <td colspan="2" class="line-left line-right table-text table-text-bold">Salary Adjustment (If there are any)</td>
                    <td colspan="2" class="line-right table-text table-text-bold"></td>
                </tr>

                <tr>
                    <td colspan="2" rowspan="6" class="line-left line-right table-text"></td>
                    <td class="table-text">
                        <span class="text-tab-left"></span>
                    </td>
                    <td class="table-text line-right "></td>
                </tr>

                <!-- <tr>
                    <td colspan="2" class="line-right table-text row-blank"></td>
                </tr> -->

                <tr>
                    <td colspan="2" class="table-text line-right row-blank"></td>
                </tr>

                <tr>
                    <td class="title-header title-header-ex">Gross Income</td>
                    <td class="title-header title-header-ex line-right">{{ grossIncome=="" ||grossIncome==0  ? '0.00': grossIncome.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="title-header title-header-ex">Total Deduction</td>
                    <td Class="title-header title-header-ex with-border-bottom line-right">{{ totalDeduction=="" || totalDeduction ==0 ? '0.00': totalDeduction.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") }}</td>
                </tr>

                <tr>
                    <td class="title-header title-header-ex">NET PAY</td>
                    <td Class="title-header title-header-ex line-right">{{ NETPAY=="" || NETPAY==0 ?  "0.00" : NETPAY.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}}</td>
                </tr>

                <tr>
                    <td colspan="5" class="row-blank"></td>
                </tr>

                <tr>
                    <td colspan="4" class="table-text line-left line-right with-border text-italic">For inquiries/disputes/clarification</td>
                </tr>
                <tr>
                    <td colspan="2" class="line-left line-right table-text table-text-bold">Payment Information</td>
                    <td colspan="2" class="line-right"></td>
                </tr>
                <tr>
                    <td class="table-text table-text-bold line-left">
                        <span class="text-tab-left">Account Number:</span>
                    </td>
                    <td class="line-right table-text">{{ accountNumber=="" ? 'N/a': accountNumber }}</td>
                    <td class="table-text table-text-bold">Employee's Signature:</td>
                    <td class="table-text line-right" v-html="employeeSignature=='' ? '': employeeSignature"></td>
                </tr>
                <tr>
                    <td class="table-text table-text-bold line-left with-border-bottom-bold">
                        <span class="text-tab-left">Name of Bank/Account:</span>
                    </td>
                    <td class="line-right table-text with-border-bottom-bold">{{ bankName=="" ? 'N/a': bankName }}</td>
                    <td class="table-text table-text-bold with-border-bottom-bold">Prepared By:</td>
                    <td class="table-text line-right with-border-bottom-bold">{{ preparedBy=="" ? '': preparedBy }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script src="./js/payslip.js"></script>
<style src="./css/payslip.css"></style>