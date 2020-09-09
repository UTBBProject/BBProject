import {mapActions} from 'vuex'
import { mapGetters } from 'vuex'
import {api} from '@/constants'
import AttendanceViolation from '@/components/monthly_data/Attendance_violation.vue'
import Complaints from '@/components/monthly_data/Complaints.vue'
import TransferredClass from '@/components/monthly_data/Transferred_class.vue'
import PerformanceBonus from '@/components/monthly_data/Performance_bonus.vue'
import PerformanceImprovement from '@/components/monthly_data/Performance_improvement.vue'
import loader from '@/components/layout/spinner/spinner';
export default {
    components:{
        loader,
        AttendanceViolation,
        Complaints,
        TransferredClass,
        PerformanceBonus,
        PerformanceImprovement
        
    },
    name: 'Monthly',
    data(){
        return{
            mcheight: 0,
            tab_index: 0,
            sdate: new Date(),
            year_month:'',
            lang: 'en',
            format: 'YYYY-MM',
            monthly_report:{
                attendance_count: 0,
                complaints_count: 0,
                transferred_count: 0,
                pb_amount: 0.0,
                pi_amount: 0.0,
                course_incentives: 0.0,
                attendance_incentives: 0.0
            },
            transferred:{
            },
            grid_loading:false,
            toClassRoute:{ 
                name: 'classes', 
                params: { 
                    data: null,
                    current_page: 1 
                }
            },
        }
    },
    computed: {
        ...mapGetters({
            toggle_status: 'SIDEBAR_TOGGLE',
        }),
    },
    methods:{
        ...mapActions({
            API_GET: 'API_GET'
        }),
        getPageData(){
            this.grid_loading = true
            this.get_monthly_report()
		},
        change_index(index, element){
            this.tab_index = index
            var all_menus = document.querySelectorAll('.grid ');
            for (var i = 0; i < all_menus.length; i++) {
                all_menus[i].classList.remove('active')
            }
            var parent_element = element.target.closest('.grid');
            parent_element.classList.add('active')
            if(index == 6){
                var res = this.year_month.split("-");
                var selected_month = parseInt(res[1]);
                var selected_year = parseInt(res[0]);
                var lastDay = new Date(selected_year, selected_month, 0).getDate();
                var callData = {}
                callData.page = 'monthly_data'
                callData.date_from = this.year_month + '-01'
                callData.date_to = this.year_month + '-' + lastDay
                this.toClassRoute.params.data = callData;
                 // route to classes
                this.$router.push(this.toClassRoute);
            }
        },
        get_monthly_report(){
            let url = api+'my-monthly-reports?month='+this.year_month;
            this.API_GET({url}).then((res) => {
                this.monthly_report = res
                this.grid_loading = false
            }).catch((err) => {
                /* eslint-disable */
                // console.log("There is An Error: "+ err);
                this.grid_loading = false
            })
        },
        search_month(){
            this.tab_index = 0;
            var all_menus = document.querySelectorAll('.grid ');
            for (var i = 0; i < all_menus.length; i++) {
                all_menus[i].classList.remove('active')
            }
            if(!this.year_month){
                /* eslint-disable */
                console.log('Month cannot be empty')
            }
            this.getPageData();
        },
    },
    mounted() {
        // when first open the page, it will load this
        this.year_month = this.sdate.getFullYear() + '-' + (parseInt(this.sdate.getMonth()) + 1)
        this.getPageData();
    }
}