<template>
    <div id="empty_page">
        <div class="pi_container">
            <div v-if="!pi_loading">
                <b-card no-body header-class="card_header" header-text-variant="white" header="Performance Improvement">
                    <div  v-if="pi.total_pi">
                        <table class="table table-sm borderless basic_table utalk_table_layout">
                            <tbody>
                                <tr>
                                    <td class="tbd-label">Rate</td>
                                    <td class="tbd-label">Hourly Rate</td>
                                    <td class="tbd-label">Demo Count</td>
                                    <td class="tbd-label">Training Count</td>
                                    <td class="tbd-label">Hours Rendered</td>
                                    <td class="tbd-label">Period</td>
                                </tr>
                                <tr>
                                    <td class="tbd">{{pi.rate}}</td>
                                    <td class="tbd">{{pi.hourly_rate}}</td>
                                    <td class="tbd">{{pi.demo_count}}</td>
                                    <td class="tbd">{{pi.training_count}}</td>
                                    <td class="tbd">{{pi.hours_rendered}}</td>
                                    <td class="tbd">{{pi.period}}</td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="total_num">
                            <span class="total_name">Total Amount:</span> <span class="total_val">{{pi.total_pi}}</span>                
                        </div>
                    </div>
                    <div v-else class="loading-table"><Nodata /></div>
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
    
    name: 'PerformanceImprovement',
    props: ['yearMonth'],
    data(){
        return{
            mcheight: 0,
            pi_loading:false,
            pi:{},
        }
    },
    
    mounted() {
        this.get_pi();
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        get_pi(){
            this.pb_loading = true
            let url = api+'my-pi?month='+this.yearMonth;
            this.API_GET({url}).then((res) => {
                this.pi= res
                this.pi_loading = false
            }).catch(err => {
                /* eslint-disable */
                console.log("Their is An Error: "+ err);
                this.pi_loading = false
            })
        },
    }
}
</script>

<style lang="scss">
.pi_container{
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
}
.loading{
    margin:auto;
    color: #b5b5b5;
    font-size: 18px;
    text-align:center;
    width:100%;
}
</style>

