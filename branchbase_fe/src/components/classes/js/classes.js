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
                id: null,
                date_from: null,
                date_to: null,
                cancelstatus: null,
                status: null,
                comment: null,
                checkedin: null,
                category: null,
                checked: true
            },
            mcheight: 0,
            tab_index: 0,
            month_year:'',
            lang: 'en',
            format: 'YYYY-MM-DD HH:mm',
            table:{
                currentPage: 0,
                storeData: false,
				loading: false,
				rows: 10,
				page: 1,
				available_page: 0,
                pages: 0,
                collumn_lists: ["Class ID","Student Info",{name: "Curriculum", width: "200px"},"Date Time","Cancel Status","Status","Video Duration","Comment", "Check In", "Category"],
                show_lists:[],
                class_list:[],
                transferred_details: []
            },
            toggle_search: 0,
            cs_option: [
                {value: null,text: 'All'},
                {value: 0, text : 'Normal'},
                {value: 1, text : 'Cancelled'},
                {value: 2, text : 'Cancelled - For Make Up'},
                {value: 3, text : 'Cancelled in 24 hrs'},
                {value: 4, text : 'Cancelled - SC Make Up'},
                {value: 5, text : 'Cancelled - SC Make Up, Done'},
                {value: 8, text : 'Cancelled - Task System Cancel'},
            ],
            stats_option: [
                {value: null,text: 'All'},
                {value: 0, text : 'Valid'},
                {value: 1, text : 'Transferred'},
                {value: 2, text : 'Invalid'},
                {value: 3, text : 'Complaints'},
                {value: 4, text : 'Cancelled'},
            ],
            checked_option: [
                {value: null,text: 'All'},
                {value: 1, text : 'Yes'},
                {value: 0, text : 'No'},
                {value: 2, text : 'Not Applicable'},
            ],
            category_option: [
                {value: null,text: 'All'},
                {value: 1, text : 'Connected'},
                {value: 2, text : 'Abnormal'},
                {value: 0, text : 'Not Applicable'},
            ],
            comment_option: [
                {value: null,text: 'Done/Undone'},
                {value: 1, text : 'Done'},
                {value: 0, text : 'Undone'},
            ],
            modal: {
                class_id: '',
                class_datetime: '',
                new_teacher_id: '',
                nickname: '',
            },
        }
    },
    watch:{
        'search.class_id': function(val){
            if(val < 0){
                this.search.class_id = 0
            }
        }
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        getPageNum(number){
            this.table.loading = true;
            this.table.currentPage = number;
            if(this.isPageExist(number)){
                this.table.loading = true;
                this.table.class_list.forEach(list =>{
                    if(list.page == number){
                        this.table.show_lists = list.list;
                    }
                });
                this.table.loading = false;
            }else{
                this.table.loading = true;
                this.getData(number,'');
            }
        },
		isPageExist(pageNum){
            let re = false;
            if(!this.storeData){
                return false;
            }
            this.table.class_list.forEach(list => {
                if(list.page == pageNum){
                    re = true;
                    return true;
                }
            });
            return re;
        },

        buttonClicked(id,key){
            if(key=="transferred"){
                this.table.transferred_details.forEach((li) => {
                    if(li.details.class_id==id){
                        this.modal = li.details;
                    }
                });
                this.$refs['transferred-modal'].show();
            }
        },
        closeModal(){
            this.$refs['transferred-modal'].hide();
        },

        checkboxChecked(){
            if (this.search.checked === true){
                this.search.checked = false
            }else{
                this.search.checked = true
            }
        },

        search_table(){
            this.table.loading = true;
            this.search.onLoad = true;
            this.table.currentPage = 1;
            this.getData(1,this.search);
        },

        getData(page,search=""){
            let url = '';
            let param = this.search;
            var that = this;
            if(typeof this.$route.params.data == 'number' && this.$route.params.page == 'earnings' && this.buttonBack.to.name !=''){
                url = api+'my-class-list/view/'+search+'?page='+page;
            }else if((typeof this.$route.params.data == 'string') && this.$route.params.data == 'deducted' && this.buttonBack.to.name !=''){
                url = api+'my-class-list/'+search+'?page='+page;
            }else if(typeof this.$route.params.data!='undefined' && this.$route.params.data.page == 'monthly_data' && this.buttonBack.to.name !=''){
                url = api+'my-class-list/?page='+page+'&date_from='+this.$route.params.data.date_from+'&date_to='+this.$route.params.data.date_to;
            }else{
                url = api+'my-class-list?page='+page;
            }
            
            this.API_GET({url, param}).then((res) => {
                let final_list =
                    {
                        page: res.current_page,
                        list: []
                    };
                let data = res.data;
                that.table.pages = res.last_page;
                data.forEach(function(li){
                    (that.table.transferred_details).push({
                        details: {
                            class_id: li.id,
                            class_datetime: li.talktime,
                            new_teacher_id: typeof li.teacherid == 'undefined' || li.teacherid == null ? '--':li.teacherid,
                            nickname: typeof li.newteacher_name == 'undefined' || li.newteacher_name == null ? '--':li.newteacher_name
                        }
                    });


                    
                    (final_list.list).push({
                        id: li.id,
                        student_info: (li.studentid == null || typeof li.studentid == 'undefined' || typeof li.student_nickname == 'undefined' || typeof li.student_mobile == 'undefined')?'--': ("UID: " + li.studentid + "<br/>" + "Name: " + li.student_nickname + "<br/>"  + "Mobile: " + li.student_mobile),
                        curriculum: (typeof li.curriculum_info == 'undefined' || li.curriculum_info == null) ? '--': (li.curriculum_info.joyname == '--' ? li.curriculum_info.gradename + "_DAY" + li.talknote + "_" + li.curriculum_info.materialnote : "<div class='curriculum'><label>Utalk:</label> " + li.curriculum_info.gradename + "_DAY" + li.talknote + "_" + li.curriculum_info.materialnote + "<br/>" + "<label>Joy:</label> " + li.curriculum_info.joyname + "_DAY" + li.talknote + "_" + li.curriculum_info.materialnote + "</div>"),
                        date_time: li.talktime,
                        cancelstatus: li.cancelstatus,
                        status: li.class_status == "Transferred" ? [
                            {
                                btnClass: "act-info",
                                btnKey: 'transferred',
                                btnName: li.class_status,
                                btnCallData: li.id,
                                btnStyle: 'width: 100px;'
                            }
                        ] : li.class_status,

                        

                        videoDuration: li.videoDuration.split(".").length >= 3 ?  li.videoDuration.split(".")[0] + '.' + li.videoDuration.split(".")[1] : li.videoDuration,
                        comment: li.comment,
                        checkedin: li.check_in,
                        category: li.category,
                    });
                });
                that.table.show_lists = final_list.list;
                if(that.table.storeData){
                    that.table.class_list.push(final_list);
                }
                this.table.loading = false;
                this.search.onLoad = false;
                if(that.toggle_search==1){
                    document.getElementById('toggle-open-search').click();
                }
            }).catch(() => {
                this.table.loading = false;
                this.search.onLoad = false;
            })
        },
        checkData(){
                this.search.cancelstatus = document.getElementById('cancel-status-field').value!=''?document.getElementById('cancel-status-field').value: null ;
                this.search.status = document.getElementById('status-field').value!=''?document.getElementById('status-field').value: null;
                this.search.comment = document.getElementById('comment-field').value!=''?document.getElementById('comment-field').value: null;
                this.search.checkedin = document.getElementById('checkedin-field').value!=''?document.getElementById('checkedin-field').value: null;
                this.search.category = document.getElementById('category-field').value!=''?document.getElementById('category-field').value: null;
        },
        reset_search(){
            this.search = {
                onLoad: false,
                id: null,
                date_from: null,
                date_to: null,
                cancelstatus: null,
                status: null,
                comment: null,
                checkedin: null,
                category: null,
            };
        },

        loadData(){
            let url = api+'my-class-list/maxtalktime'
            this.API_GET({url}).then((res) => {
                this.table.loading = true;
                this.search.onLoad = true;
                var max = res[0].max_talktime
                var new_date = new Date(max * 1000)
                var date_now = new Date();
                var date_before = new Date(date_now.setTime(date_now.getTime() - (30*60*1000)))
                this.search.date_from = date_before.getFullYear() + '-' + (date_before.getMonth() + 1) +'-'+date_before.getDate()+ ' ' + date_before.getHours() + ":"+date_before.getMinutes()
                this.search.date_to =  new_date.getFullYear() + '-' + (new_date.getMonth() + 1) +'-'+new_date.getDate()+ ' ' + new_date.getHours() + ":"+new_date.getMinutes()
                this.getData(1,"");
            }).catch(() => {});
        },

        refreshRoute(){
            this.buttonBack = {
                show: false,
                to: {
                    name:"",
                    params:{
                        current_page: 1,
                        search: null
                    }
                }
            }
            this.table.loading = true;
            this.loadData();
        },
        numberOnly(e){
            const key = e.key;

            // If is '.' key, stop it
            if (key === '.' || key === '-' || key === '+' || key === 'e'){
                return e.preventDefault();
            }
        },
    },
    mounted() {
        this.buttonBack.to.params.current_page = this.$route.params.current_page;
        this.table.loading = true;
        let cpn = this.$route.params.current_page==null? 1 : this.$route.params.current_page;
        this.table.currentPage = cpn;
        if (this.$route.params.data === 'deducted'){
            this.buttonBack.show = true;
            this.buttonBack.to.name = "profile";
            this.getData(1,this.$route.params.data);
        }else if((typeof this.$route.params.data == 'number') && this.$route.params.page == 'earnings'){
            this.buttonBack.show = true;
            this.buttonBack.to.params.search = this.$route.params.search;
            this.buttonBack.to.name = "Earnings";
            this.getData(1,this.$route.params.data);
        }else if(typeof this.$route.params.data!='undefined' && this.$route.params.data.page == 'monthly_data'){
            this.buttonBack.show = true;
            this.buttonBack.to.name = "monthly";
            this.getData(1,this.$route.params.data);
        }else{
            this.loadData();
        }
    }
}