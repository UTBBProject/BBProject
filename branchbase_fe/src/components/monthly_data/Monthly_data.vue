<template>
    <div id="Monthly_container">
            <div class="monthly_content_holder">
                <div class="utalk_table_search" >
                    <div id="search_filter_holder">
                        <b-card style="margin-bottom: 0px; padding-bottom: 0px;">
                            <b-form>
                                <b-row>
                                    <b-col sm="6">
                                        <b-form-group>
                                            <label>Select Month</label>
                                            <div>
                                                <date-picker v-model="year_month" type="month" placeholder="Select month" :lang="lang" :format="format" value-type="format"></date-picker>
                                                <b-button class="submit_search" @click="search_month">Search</b-button>
                                            </div>
                                        </b-form-group>
                                        
                                    </b-col>
                                </b-row>
                            </b-form>
                        </b-card> 
                    </div>  
                </div>  
                <div v-if="!grid_loading">
                    <div class="counter">
                        <div class="overviewcard grid" @click="change_index(1,$event)">
                            <div class="overviewcard_title">Attendance Violation Count</div>
                            <div class="overviewcard_num num1">{{monthly_report.attendance_count}}</div>
                        </div>
                        <div v-if="false" class="overviewcard grid" @click="change_index(2,$event)">
                            <div class="overviewcard_title">Complaints Count</div>
                            <div class="overviewcard_num num2">{{monthly_report.complaints_count}}</div>
                        </div>
                        <div class="overviewcard grid" @click="change_index(3,$event)">
                            <div class="overviewcard_title" >Total Transferred Classes</div>
                            <div class="overviewcard_num num3">{{monthly_report.transferred_count}}</div>
                        </div>
                    </div>
                    <div v-if="false" class="bonus">
                        <div class="bonuscard grid" @click="change_index(4,$event)">
                            <div class="bonus_info"><span class="value">PHP {{monthly_report.pb_amount}}</span></div>
                            <div class="bonus_name">Performance Bonus</div>
                        </div>
                        <div class="bonuscard grid" @click="change_index(5,$event)">
                            <div class="bonus_info"><span class="value">PHP {{monthly_report.pi_amount}}</span></div>
                            <div class="bonus_name">Performance Improvement</div>
                        </div>
                        <div class="bonuscard grid" @click="change_index(6,$event)">
                            <div class="bonus_info"><span class="value">PHP {{monthly_report.course_incentives}}</span></div>
                            <div class="bonus_name">Total Course Incentives</div>
                        </div>
                        <div class="bonuscard grid" @click="change_index(7,$event)">
                            <div class="bonus_info"><span class="value">PHP {{monthly_report.attendance_incentives}}</span></div>
                            <div class="bonus_name">Attendance Incentives</div>
                        </div>
                    </div>
                </div> 
                <div class="grid_container" v-else><center><loader /></center></div>
                <transition name="fade">
                    <AttendanceViolation :year-month = "year_month" v-if="tab_index == 1" />
                    <Complaints :year-month = "year_month" v-if="tab_index == 2" />
                    <TransferredClass :year-month = "year_month" v-if="tab_index == 3" />
                    <PerformanceBonus :year-month = "year_month" v-if="tab_index == 4" />
                    <PerformanceImprovement :year-month = "year_month" v-if="tab_index == 5" />
                </transition>
                
            </div>

    </div>
</template>


<script src="./js/monthly_data.js"></script>


<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="scss">
#Monthly_container{
    $border-gray: #d3d3d3;
    $hover-gray: #b5b5b5;
    $white: #ffffff;
    .fade-enter,
    .fade-leave-to {
        opacity: 0;
    }
    .fade-enter-active{
        transition: 1s;
    }
    .monthly_body{
        box-shadow: 0 0 5px $border-gray;
    }
    .monthly_content_holder{
        // overflow-x: hidden;
        // overflow-y: visible ;
        display: inline-block;
        transition: all ease-in-out .3s;
        width: 100%;
        padding:1rem 1rem 4rem 1rem;
        background-color: $white;
        text-align:left;
        box-shadow: 0 0 5px $border-gray;
            .d_search{
                position: relative; 
                width:20%; 
                display:inline-block
            }
            .btn_search{
                padding: 1rem;
                display:inline-block
            }
            .counter{
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
                grid-auto-rows: 94px;
                grid-gap: 20px;
                .overviewcard {
                    align-items: center;
                    display: flex;
                }
                .overviewcard:hover{
                    background-color: $hover-gray;
                    color: $white;
                    cursor: pointer;
                }
                .overviewcard_num{
                    padding: 0.8rem;
                    color: $white;
                    font-weight: 600;
                    font-size: 1.2rem;
                    border-radius: 50%;
                    width:55px;
                    height:55px;
                    text-align:center;
                    border:1px solid $white;
                }
                .num1{
                    background-color: #81c6f8
                }
                .num2{
                    background-color: #5ec595
                }
                .num3{
                    background-color: #ce92b7
                }
            }
            .bonus{
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
                grid-auto-rows: 94px;
                grid-gap: 20px;
                margin-top: 20px;
                .bonuscard{
                    display: block;
                    text-align: right;
                }
                .bonuscard:hover{
                    color:white;
                    background-color: $hover-gray;
                    cursor: pointer;
                }
                .bonus_info{
                    text-decoration: underline;
                    font-size:1.5rem; 
                    font-weight: 400;  
                }
                .bonus_name{
                    font-weight: 200; 
                }
                .information{
                    width:100%;
                    height:100%;
                    margin:auto;
                    text-align: center;
                    font-size:2rem;
                    margin-top:2rem;
                }
                
            }
            .grid{
                justify-content: space-between;
                padding: 20px;
                background-color: $white;
                border-radius: 2px;
                border: 1px solid $border-gray;
            }
            .active{
                background-color: $hover-gray;
                color: #ffffff;
            }
            .submit_search{
                background: transparent;
                border: solid 1px;
                /*border-radius: 0;*/
                color:  #0d708f;
                margin-left: .5rem;
                font-size: 12px;
                outline: none;
                cursor: pointer;
                box-shadow: none;

                &:hover{
                    background: #0d708f;
                    color: #fff;
                    border-color: #0d708f;
                }
            }
            .date_range_container{
                display: inline-flex;
                width: 100%;
            }
            .grid_container{
                color: $hover-gray;
                font-size: 18px;
                text-align:center;
            }
    }
    .slide-fade-enter-active {
        transition: all .3s ease;
    }
    .slide-fade-leave-active {
        transition: all .8s cubic-bezier(1.0, 0.5, 0.8, 1.0);
    }
}
</style>