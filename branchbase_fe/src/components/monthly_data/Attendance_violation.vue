<template>
    <div id="empty_page">
        <div class="monthly_table">
            <tablelayout
			:header-data="table.column_list"
            headerStyle
			:data-list="table.show_lists"
			:page-count="table.pages"
            :pagination-show="true"
			:table-style="'height: 420px;'"
			:action-pass="false"
			:onLoad="table.loading"
            :getPageNum="getPageNum"
            :actionClick="getPageNum"
            trStyle
            style="font-size: 13px;"
		/>
        </div>
    </div>
</template>

<script>
import tablelayout from '@/components/layout/table/table';
import {mapActions} from 'vuex'
import {api} from '@/constants'

export default {
    name: 'AttendanceViolation',
    components: {
        tablelayout
    },
    props:['transferred','yearMonth'],
    data(){
        return{
            mcheight: 0,
            table:{
                // This will disable storing data to data_list
                doStoreList: false,
				loading: false,
				rows: 10,
				page: 1,
				available_page: 0,
                pages: 0,
                // this is the collimn header
				column_list: ["ID","Date","Shift","Violation"],//name of collumns in array
                show_lists:[],
			},
        }
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        get_data(){
            let url = api+'my-attendance?month='+this.yearMonth+'&page='+ this.table.page;
            this.API_GET({url}).then((res) => {
                let final_list =
                {
                    page: res.current_page,
                    list: []
                };
                res.data.forEach(function(li){
                    (final_list.list).push({
                        class_id: li.id,
                        date_time: li.date_created.substring(0,10),
                        schedule: li.dingtalk_sched,
                        status: li.violation+(li.absent_time_range != "" ? "<br>"+li.absent_time_range : ''),

                    });
                
            });
            this.table.show_lists = final_list.list;
            this.table.pages = res.last_page
            }).catch(err => {
                /* eslint-disable */
                console.log("Their is An Error: "+ err);
            }).finally(() => {
                this.table.loading = false;
            })
        },
        getPageNum(number){
            this.table.loading = true;
            this.table.page = number
            this.get_data()
		},
    },
    mounted(){
        this.table.loading = true;
        this.get_data();
    }
}
</script>

<style lang="scss">
.monthly_table{
    margin-top:1rem;
    background: #fff;
    padding: 1rem;
}
</style>
