<template>
    <div id="empty_page">
        <div class="mm_container" >
            <div v-if="!pb_loading">
                <b-card no-body header-class="card_header" header-text-variant="white" header="Basic Info">
                    <table class="table table-sm borderless basic_table utalk_table_layout" v-if="pb.pb_rank">
                        <tbody>
                            <tr>
                                <td class="tbd-label">Team ID</td>
                                <td class="tbd-label">Team Name</td>
                                <td class="tbd-label">Team Rank</td>                                
                                <td class="tbd-label">Subteam ID</td>                                
                                <td class="tbd-label">Subteam Name</td>                                
                                <td class="tbd-label">Subteam Coe</td>
                                <td class="tbd-label">Subteam Rank</td>
                                
                            </tr>
                            <tr>
                                <td class="tbd">{{pb.pb_rank.team_id}}</td>
                                <td class="tbd">{{pb.pb_rank.team}}</td> <!-- team name-->
                                <td class="tbd">{{pb.pb_rank.team_rank}}</td>
                                <td class="tbd">{{pb.pb_rank.subteam_id}}</td> <!-- subteam_id-->
                                <td class="tbd">{{pb.pb_scores.subteam}}</td>
                                <td class="tbd">{{pb.pb_rank.subteam_coe}}</td>
                                <td class="tbd">{{pb.pb_rank.subteam_rank}}</td>
                            </tr>
                            <tr>
                                <td colspan="7">
                                    <table class="basic2" style="width:100%">
                                        <tr>
                                            <td class="tbd-label">Individual Rank</td>
                                            <td class="tbd-label">EIC Rank</td>
                                            <td class="tbd-label">Score</td>
                                            <td class="tbd-label">Gross Incentive</td>
                                            <td class="tbd-label">Amount</td>
                                            <td class="tbd-label">Cutoff</td>
                                        </tr>
                                        <tr>
                                            <td class="tbd">{{pb.pb_rank.overall_rank}}</td>
                                            <td class="tbd">{{pb.pb_rank.member_eic}}</td>
                                            <td class="tbd">{{pb.pb_rank.score}}</td>
                                            <td class="tbd">{{pb.pb_rank.basic_salary}}</td>
                                            <td class="tbd">{{pb.pb_rank.pb_bonus}}</td>
                                            <td class="tbd">{{pb.pb_rank.pb_month}}</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div v-else class="loading-table"><Nodata /></div>
                </b-card>
                <b-card no-body header-class="card_header" header-text-variant="white" header="Raw score" class="raw_score_container"  v-if="pb.pb_scores">
                    <table class="table table-sm mt-2 p-3 borderless raw_score_table">
                        <tbody>
                            <tr>
                                <td colspan="3" class="tbd-label">Team</td>
                                <td colspan="3" class="tbd-label">Status</td>
                                <td colspan="3" class="tbd-label">Level</td>
                                
                            </tr>
                            <tr>
                                <td colspan="3" class="tbd">{{pb.pb_scores.subteam}}</td>
                                <td colspan="3" class="tbd">{{pb.pb_scores.status}}</td>
                                <td colspan="3" class="tbd">{{pb.pb_scores.level}}</td>
                                
                            </tr>
                        </tbody>
                    </table>
                    <div class="table_overflow">
                        <table class="table table-sm mt-4 p-4 borderless raw_score_table" v-if="pb.pb_scores">
                            <tbody>
                                <tr>
                                    <td class="tbd-label">Demo TL</td>
                                    <td class="tbd-label">Demo ATL</td>
                                    <td class="tbd-label">Demo QA
                                    <td class="tbd-label">Sample TL</td>
                                    <td class="tbd-label">Sample ATL</td>
                                    <td class="tbd-label">Sample QA</td>
                                    <td class="tbd-label">Behavior TL</td>
                                    <td class="tbd-label">Behavior ATL</td>
                                    <td class="tbd-label">Total Course</td>
                                    <td class="tbd-label">Student Feedback</td>
                                </tr>
                                <tr>
                                    <td class="tbd">{{pb.pb_scores.demo_tl}}</td>
                                    <td class="tbd">{{pb.pb_scores.demo_atl}}</td>
                                    <td class="tbd">{{pb.pb_scores.demo_qa}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_tl}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_atl}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_qa}}</td>
                                    <td class="tbd">{{pb.pb_scores.behavior_tl}}</td>
                                    <td class="tbd">{{pb.pb_scores.behavior_atl}}</td>
                                    <td class="tbd">{{pb.pb_scores.total_course}}</td>
                                    <td class="tbd">{{pb.pb_scores.student_feedback}}</td>
                                </tr>
                                <tr>
                                    <td class="tbd-label">Demo TL Score </td>
                                    <td class="tbd-label">Demo ATL Score</td>
                                    <td class="tbd-label">Demo QA Score</td>
                                    <td class="tbd-label">Sample TL Score</td>
                                    <td class="tbd-label">Sample ATL Score</td>
                                    <td class="tbd-label">Sample QA Score</td>
                                    <td class="tbd-label">Behavior TL Score</td>
                                    <td class="tbd-label">Behavior ATL Score</td>
                                    <td class="tbd-label">Total Course Score</td>
                                    <td class="tbd-label">Student Feedback Score</td>
                                </tr>
                                <tr>
                                    <td class="tbd">{{pb.pb_scores.demo_tl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.demo_atl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.demo_qa_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_tl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_atl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.sample_qa_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.behavior_tl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.behavior_atl_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.total_course_score}}</td>
                                    <td class="tbd">{{pb.pb_scores.student_feedback_score}}</td>
                                    
                                </tr>
                            </tbody>
                        </table>
                    </div>    
                    <div class="total_num" v-if="pb.pb_scores">
                        <span class="total_name">Total Score: </span> <span class="total_val">{{pb.pb_scores.total_score}}</span>
                        <span class="total_name">Total Amount:</span> <span class="total_val">{{pb.pb_scores.total_amount}}</span>                
                    </div>
                </b-card>
            </div>    
            <div class="loading" v-else><center><loader /></center></div>
        </div>
        
    </div>
</template>

<script>
import {mapActions} from 'vuex'
import {api} from '@/constants'
import Nodata from '@/components/layout/Nodata';
import loader from '@/components/layout/spinner/spinner';
export default {
    components:{
        Nodata,
		loader
    },
    
    name: 'PerformanceBonus',
    props: ['yearMonth'],
    data(){
        return{
            mcheight: 0,
            pb_loading:false,
            pb:{
                pb_rank:{},
                pb_scores:{},
            },
        }
    },
    mounted() {
        this.get_pb();
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        get_pb(){
            this.pb_loading = true
            let url = api+'my-pb?month='+this.yearMonth;
            this.API_GET({url}).then((res) => {
                this.pb.pb_rank = res.pb_rank
                this.pb.pb_scores = res.pb_scores
                this.pb_loading = false
            }).catch(err => {
                this.pb_loading = false
                this.pb.pb_rank = ''
                this.pb.pb_scores = ''
                /* eslint-disable */
                console.log(err);
            })
        },
    }
}
</script>

<style lang="scss">
.mm_container{
    animation: fadeInRight 0.3s;
    margin-top:1rem;
    background: #fff;
    border: 1px solid #d3d3d3;
    padding: 1rem;

    .basic_info{
        margin-top:2rem;
    }
    .basic_table{
        float:center;
        text-align:center;
        font-size:0.9rem;
        td{
           border: none; 
        }
        .tbd-label{
            font-weight:700;
            color:#474747
        }
        .tbd{
            color:#6b6b6b;
        }
        .basic2{
            width:100%;
            .tbd{
                width: 16.6%;
            }
            .tbd-label{
                width:16.6%
            }
        }
    }
    .raw_score_container{
        margin-top:1rem;
        .raw_score_table{
            overflow:scroll;
            float:center;
            margin:auto;
            padding:2rem;
            font-size:13px;
            text-align:center;
            td{
            border: none; 
            padding:0.6rem;
            }
            .tbd-label{
                margin-top:1rem;
                font-weight:700;
                color:#474747
            }
            .tbd{
                color:#6b6b6b;
            }
        }
    }
    .card_header{
        background-color:#0d708f;
        font-size:13px;
        padding:0.5rem;
        font-weight:500;
    }
    .total_num{
        text-align:right;
        padding: 1rem;
        .total_name{
            margin-left:1rem;
            font-size:0.9rem;
            font-weight:700;
            color: #0d708f;
        }
        .total_val{
            font-size:0.9rem;
            font-weight:700;
            color: #474747;
        }
    }
    .table_overflow{
        overflow:auto;
    }
}
.loading{
    color: #b5b5b5;
    font-size: 18px;
    text-align:center;
    width:100%;
}
.loading-table{
	color: #666666;
	text-align:center;
	padding: 2rem 0rem;
	font-size: 30px;
}
</style>
