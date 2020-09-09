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
    name: 'TransferredClass',
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
                // column_list: ["ID","Class Time","Teacher","Class Status","Cancel Status","Category", "Check In", "Comment"],//name of collumns in array
                column_list: ["ID","Class Time","Deduction","Transfer Time"],//name of collumns in array
                show_lists:[],
			},
        }
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        get_transferred_class(){
            let url = api+'my-transferred-class/view?month='+this.yearMonth+'&page=' + this.table.page;
            this.API_GET({url}).then((res) => {
                let final_list =
                {
                    page: res.current_page,
                    list: []
                };
                res.data.forEach(function(li){
                
                    (final_list.list).push({
                        class_id: li.class_id,
                        date_time: li.date_time,
                        // new_teacher: 'ID: '+li.newteacher_id +'</br> Name: '+ li.nickname,
                        // class_time: li.talktime,
                        deduction: li.deduction,
                        transfer_time: li.transfer_time
                    });
                
            });
            this.table.show_lists = final_list.list;
            this.table.pages = res.last_page
            // console.log(this.table.show_lists.length)
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
            this.get_transferred_class()
		},
    },
    mounted(){
        this.table.loading = true;
        this.get_transferred_class();
    }
}
</script>

