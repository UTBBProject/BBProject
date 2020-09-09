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
    name: 'Complaints',
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
				column_list: ["Class ID","Complaints ID","Complaint Date","Description"],//name of collumns in array
                show_lists:[],
			},
        }
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        get_data(){
            let url = api+'my-complaints?month='+this.yearMonth+'&page='+ this.table.page;
            this.API_GET({url}).then((res) => {
                let final_list =
                {
                    page: res.current_page,
                    list: []
                };
                res.data.forEach(function(li){
                
                    (final_list.list).push({
                        class_id: li.class_id,
                        complaints_id: li.id,
                        complaint_date: li.complaint_date,
                        description: li.description,
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
