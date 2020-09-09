<template>
    <div style="background-color: white; padding: 15px;">
        <div>
            <div class="utalk_table_search payslip-filter"  style="margin-bottom: 0px; ">
                <div id="search_filter_holder" class="mt-2">
                    <b-card style="margin-bottom: 0px; padding-bottom: 0px;">
                        <b-form>
                            <b-row>
                                <b-col sm="10">
                                    <b-form-group>
                                        <label>Select Period</label>
                                        <div class="">
                                            <date-picker class="m-top-5" :clearable="false" v-model="search.date" :editable="false" type="month" format="YYYY-MM" :lang="'en'" value-type="format" style="margin-right:20px;" placeholder="Select Period"></date-picker>
                                            <!--  -->
                                            <b-form-select class="m-top-5" style="width:140px; margin-right:20px;" v-model="search.cutoff" :options="option.cutoff" />
                                            <b-button size="sm" class="submit_search m-top-5" style="width:70px" :disabled="search.onLoad" @click="searchPeriod">
                                                <b-spinner v-if="search.onLoad" small  variant="primary" label="Spinning"></b-spinner>
                                                <span v-else>Show</span>
                                            </b-button>
                                            <b-button size="sm" class="submit_search m-top-5" style="width:70px" :disabled="exporting"
                                                @click="exportPayslip"
                                            >
                                                <span>Export</span>
                                            </b-button>
                                        </div>
                                    </b-form-group>
                                </b-col>
                            </b-row>
                        </b-form>
                    </b-card>
                </div>
            </div>
            <!-- payslip -->
            <div >
                 <ChildPayslip class="pay-slip-box" :payslip="payslip" />
            </div>
        </div>
    </div>
</template>
<script src="./js/payslip.js"></script>
<style>
    .payslip-filter *{
        /* removes outline in filter */
        outline: none;
    }
    .m-top-5{
        margin-top: 5px;
    }

    .utalk_table_search, #search_filter_holder{
        -webkit-user-select: none; /* Chrome/Safari */        
        -moz-user-select: none; /* Firefox */
        -ms-user-select: none; /* IE10+ */

        /* Rules below not implemented in browsers yet */
        -o-user-select: none;
        user-select: none;
        outline: none;
    }
    @media screen and (max-width:743px){
        .pay-slip-box {
            overflow-x: scroll !important;
            width: 680px;
        }
    }
    
</style>