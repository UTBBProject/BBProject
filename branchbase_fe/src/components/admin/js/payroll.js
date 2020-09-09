import {mapActions} from 'vuex'
import {api} from '@/constants'
import tablelayout from '@/components/layout/table/table';
export default {
    components: {
        tablelayout
    },
    data() {
        return {
            buttonBack:{
                show: false,
                to: {
                    name:"",
                    params:{
                        current_page: 1,
                        search: null
                    }
                }
            },
            search:{
                onLoad: false,
                tid: null,
                date_from: null,
                date_to: null,
                tname: null,
                checked: true,
                empid: null,
                page: 1
            },
            mcheight: 0,
            tab_index: 0,
            month_year:'',
            lang: 'en',
            format: 'YYYY-MM-DD',
            table:{
                currentPage: 0,
                storeData: false,
				loading: false,
				rows: 10,
				page: 1,
				available_page: 0,
                pages: 0,
                collumn_lists: [
                    'id','Teacher ID',
                    'Employee ID',
                    { 
                        name: 'Teacher Name', 
                        style: "text-align:left;" 
                    },
                    'Cut-off Date',
                    'Gross Pay',
                    'Deduction',
                    'Net Pay'
                ],
                show_lists:[],
                class_list:[],
                transferred_details: []
            },
            toggle_search: 0,
        }
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),

        reset_search(){
            this.search={
                onLoad: false,
                tid: null,
                date_from: null,
                date_to: null,
                tname: null,
                checked: true,
                empid: null,
                page: 1,
            };
            this.initialize();
        },
        buttonSearch(){
            this.search.page = 1;
            this.initialize();
        },
        getPageNum(page){
            this.search.page = page;
            this.initialize();
        },
        initialize(){
            let url = api + 'get-payroll-list';
            let that = this;
            let param = that.search;
            that.table.loading = true;
            this.API_GET({url,param}).then(response =>{
                that.table.show_lists = [];
                (response.data).forEach(item => {
                    that.table.show_lists.push([
                        item.id,
                        item.edu_id,
                        item.user_id,
                        // item.teacher,
                        {
                            text: item.teacher,
                            style: "text-align:left;"
                        },
                        item.pay_date,
                        item.gross_income,
                        item.total_deduction,
                        item.total_netpay
                    ]);
                });
                that.table.loading = false;
                that.table.pages = response.last_page;
                that.table.currentPage = response.current_page;
            });
        }
    },
    mounted(){
        this.initialize();
    }
}