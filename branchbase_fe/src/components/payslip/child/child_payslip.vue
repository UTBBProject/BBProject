<template>
   <PayslipLayout
        id="convert-pdf"
        :NoData="payslip.NoData"
        :loading="payslip.loading"
        :employeeName="payslip.employeeName"
        :position="payslip.position"
        :dateHired="payslip.dateHired"
        :teacherLevel="payslip.teacherLevel"
        :classRate="payslip.classRate"
        :employeeType="payslip.employeeType"
        :payrollRegister="payslip.payrollRegister"
        :payPeriod="payslip.payPeriod"
        :payDate="payslip.payDate"
        :serviceFee="payslip.serviceFee"
        :trainingFee="payslip.trainingFee"
        :observationFee="payslip.observationFee"
        :salaryAdjustment="payslip.salaryAdjustment"
        :accountNumber="payslip.accountNumber"
        :bankName="payslip.bankName"
        :attendanceIncentives="payslip.attendanceIncentives"
        :performanceBonus="payslip.performanceBonus"
        :partialReferralBonus="payslip.partialReferralBonus"
        :classIncentive="payslip.classIncentive"
        :perfomanceImprovement="payslip.perfomanceImprovement"
        :transferredClass="payslip.transferredClass"
        :less25Minutes="payslip.less25Minutes"
        :deductionsClass="payslip.deductionsClass"
        :callin="payslip.callin"
        :awol="payslip.awol"
        :less25MinutesDeduction="payslip.less25MinutesDeduction"
        :grossIncome="payslip.grossIncome"
        :totalDeduction="payslip.totalDeduction"
        :NETPAY="payslip.NETPAY"
        :employeeSignature="payslip.employeeSignature"
        :preparedBy ="payslip.preparedBy"
        :totalCourseIncentives="payslip.totalCourseIncentives"
        :late_20="payslip.late_20"
        :late_10="payslip.late_10"
        :video_duration_deduction="payslip.video_duration_deduction"
        :g_30_transferred="payslip.g_30_transferred"
        :l_30_transferred="payslip.l_30_transferred"
        :sub_class_rate="payslip.sub_class_rate"
        :week_end_rate="payslip.week_end_rate"
    >
   </PayslipLayout>
</template>


<script>
import PayslipLayout from '@/components/layout/payslip_layout/payslip';
export default {
    props:{
        payslip: { type: Object }
    },
    components:{
        PayslipLayout
    }
}
</script>