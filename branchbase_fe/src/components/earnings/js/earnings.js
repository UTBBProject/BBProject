import tablelayout from '@/components/layout/table/table';
import datetime from 'vuejs-datetimepicker';
import { mapActions } from 'vuex';
import { api } from '@/constants';
import NoData from '@/components/layout/Nodata.vue';
export default {
    components: {
        tablelayout,
        datetime,
        NoData
    },
    data() {
        return {
            from_route: false,
            modal: {
                class_rate: '',
                week_pay: '',
                sub_class: '',

            },
            scheme: '',
            badge_count: [],
            search: {
                cancelstatus: null,
                status: null,
                checkedin: null,
                comment: null,
                category: null,
                date_from: null,
                date_to: null,
                class_id: null,
                onLoad: false
            },
            toggle_search: 0,
            cs_option: [
                { value: null, text: 'All' },
                { value: 0, text: 'Normal' },
                { value: 1, text: 'Cancelled' },
                { value: 2, text: 'Cancelled - For Make Up' },
                { value: 3, text: 'Cancelled in 24 hrs' },
                { value: 4, text: 'Cancelled - SC Make Up' },
                { value: 5, text: 'Cancelled - SC Make Up, Done' },
            ],
            stats_option: [
                { value: null, text: 'All' },
                { value: 0, text: 'Valid' },
                { value: 1, text: 'Transferred' },
                { value: 2, text: 'Invalid' },
                { value: 3, text: 'Complaints' },
                { value: 4, text: 'Cancelled' },
            ],
            checked_option: [
                { value: null, text: 'All' },
                { value: 0, text: 'Yes' },
                { value: 1, text: 'No' },
                { value: 2, text: 'Not Applicable' },
            ],
            category_option: [
                { value: null, text: 'All' },
                { value: 0, text: 'Connected' },
                { value: 1, text: 'Abnormal' },
                { value: 2, text: 'Not Applicable' },
            ],
            comment_option: [
                { value: null, text: 'Done/Undone' },
                { value: 1, text: 'Done' },
                { value: 0, text: 'Undone' },
            ],
            toClassRoute: {
                name: 'classes',
                params: {
                    data: null,
                    current_page: 1,
                    search: null,
                    page: 'earnings'
                }
            },
            table: {
                currentPage: 0,
                doStoreList: false,
                loading: false,
                rows: 10,
                page: 1,
                pages: 0,
                collumn_lists: [
                    { name: "Class Id", width: "auto" },
                    "Date Time",
                    "Status",
                    "Amount",
                    { name: "Addition to Total Class", width: "auto" },
                    "Current Lesson Count",
                    "Current Level<br><small>(Based on class count only)</small>"],
                show_lists: [],
                data_lists: [],
                actions: [
                    {
                        btnClass: "act-primary",
                        btnName: "<span>Edit</span>",
                        btnKey: "Edit"
                    },
                    {
                        btnClass: "act-info",
                        btnName: "<span>View</span>",
                        btnKey: "View"
                    },
                    {
                        btnClass: "act-danger",
                        btnName: "Delete",
                        btnKey: "Delete"
                    }
                ],
                amount_detail: [],
                profile: false,
                clickable: [],
                disputeDetails: {}
            },
            current_cr: null,
            amount_show_details: {}
        }
    },
    methods: {
        /**
         * /START/ TABLE METHODS
         */
        ...mapActions({
            API_GET: 'API_GET'
        }),
        setBody(list) {
            let that = this;
            return Object.values(list).filter((obj, index) => !that.table.disableColl.includes(index + 1));
        },
        getPageNum(number) {
            this.table.loading = true;
            this.table.currentPage = number;
            this.toClassRoute.params.current_page = number;
            if (this.isPageExist(number)) {
                this.table.loading = true;
                this.table.data_lists.forEach(list => {
                    if (list.page == number) {
                        this.table.show_lists = list.list;
                    }
                });
                this.table.loading = false;
            } else {
                this.table.loading = true;
                this.getData(number, '');
            }
        },
        numberOnly(e) {
            const key = e.key;
            if (key === '.' || key === '-' || key === '+' || key === 'e') {
                return e.preventDefault();
            }
        },
        buttonClicked(key, callData = '', return_something = false) {
            
            if (key == 'status') {
                this.toClassRoute.params.current_page = this.table.currentPage;
                this.toClassRoute.params.data = callData;
                this.toClassRoute.params.search = this.search;
                this.$router.push(this.toClassRoute);
            }
            if (key == "amount") {
                this.modal = return_something;
                this.$refs['amount-modal'].show();
            }
            if (key == 'dispute' && return_something) {
                
                let disShow = return_something.dispute_status != null && return_something.dispute_description != null ?
                {
                    status: return_something.dispute_status,
                    date: return_something.dispute_date,
                    discription: JSON.parse(return_something.dispute_description),
                    dispute_result: return_something.dispute_result == 'refuse' ? 'Rejected' : return_something.dispute_result
                } : false ;
                if (disShow) {
                    this.table.disputeDetails = disShow;
                    this.$refs['dispute-modal'].show();
                }
            }
        },
        checkIfHasDispute(id) {
            let that = this;
            let res = false;
            that.table.clickable.forEach(item => {
                if (id == item.id) {
                    res = true;
                }
            });
            return res;
        },
        isObjectEmpty(obj) {
            for (var key in obj) {
                if (obj.hasOwnProperty(key))
                    return false;
            }
            return true;
        },
        closeModal(ref_name) {
            this.$refs[ref_name].hide();
        },
        searchButton() {
            this.from_route = false
            this.table.amount_detail = [];
            if (this.search.class_id == "") {
                this.search.class_id = null;
            }

            // fix search
            if (this.search.date_from == '') {
                this.search.date_from = null;
            }
            if (this.search.date_to == '') {
                this.search.date_to = null;
            }

            this.search.onLoad = true;
            this.table.currentPage = 1;
            this.getData(1, this.search);
            this.getDataCount();
        },
        resetSearch() {
            this.from_route = false
            this.search = {
                cancelstatus: null,
                status: null,
                checkedin: null,
                comment: null,
                category: null,
                date_from: null,
                date_to: null,
                class_id: null,
                onLoad: false
            };
        },
        isPageExist(pageNum) {
            let re = false;
            if (!this.table.doStoreList) {
                return false;
            }
            this.table.data_lists.forEach(list => {
                if (list.page == pageNum) {
                    re = true;
                    return true;
                }
            });
            return re;
        },
        getData(page, search = null) {
            let url = '';
            if (this.$route.params.data == 'accumulated_classes' && this.from_route == true) {
                this.profile = true
                url = api + 'my-earnings-log?p=profile&page=' + page;
            } else {
                url = api + 'my-earnings-log?page=' + page;
            }

            let param = this.search;
            if (typeof search != 'undefined' && search != '') {
                param = search;
                this.search = search;
            }
            var that = this;

            this.API_GET({ url, param }).then((res) => {
                that.toClassRoute.params.current_page == res.current_page;
                that.table.show_lists = res.data;
                that.table.loading = false;
                that.table.pages = res.last_page;
                that.search.onLoad = false;
            }).catch(() => {
                this.table.loading = false;
                this.search.onLoad = false;
            })
        },
        /**
        * /END/ TABLE METHODS
        */
        getDataCount(search = '') {
            let url = '';
            if (this.$route.params.data == 'accumulated_classes' && this.from_route == true) {
                // url = api+'my-earnings-count?p=profile&clc='+this.$route.params.clc;
                url = api + 'my-earnings-count?p=profile';
            } else {
                url = api + 'my-earnings-count';
            }
            // this function would overide if the browser is IE
            let param = this.search;
            if (search != "") {
                param = search;
            }
            let that = this;
            that.badge_count = [];

            this.API_GET({ url, param }).then((res) => {
                for (const i in res) {
                    that.badge_count.push({
                        title: i,
                        count: res[i].toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","), // automatically add commas
                        class: i == "Valid" ? 'primary' : (i == "Transferred" ? 'info' : (i == "Invalid" ? "danger" : "warning"))
                    });
                }
            }).catch(() => {
            });


        },
        checkData() {
            this.search.cancelstatus = document.getElementById('cancel-status-field').value != '' ? document.getElementById('cancel-status-field').value : null;
            this.search.status = document.getElementById('status-field').value != '' ? document.getElementById('status-field').value : null;
            this.search.comment = document.getElementById('comment-field').value != '' ? document.getElementById('comment-field').value : null;
            this.search.checkedin = document.getElementById('checkedin-field').value != '' ? document.getElementById('checkedin-field').value : null;
            this.search.category = document.getElementById('category-field').value != '' ? document.getElementById('category-field').value : null;
        },
        check_scheme(amount, class_rate, type) {
            let url = ''
            url = api + 'current-classrate'
            let $this = this
            this.API_GET({ url }).then((res) => {
                $this.current_cr = res
            }).catch(() => {
            });

            if (type == 'transferred') {
                if (amount != null) {
                    amount = amount * -1;
                    if ((class_rate * .80) == amount) {
                        this.scheme = "Scheme: > 3 hours."
                    } else if ((class_rate * 1) == amount) {
                        this.scheme = "Scheme: < 3 hours."
                    }
                }
            } else if (type == 'video') {

                amount = amount * -1;
                this.scheme = ''
                if ((this.current_cr * .50) == amount) {
                    this.scheme = "Scheme: < 25 minutes"
                } else if ((this.current_cr * 1) == amount) {
                    this.scheme = "Scheme: < 20 minutes"
                } else if ((this.current_cr * 1.5) == amount) {
                    this.scheme = "Scheme: < 10 minutes"
                }

            }

        }
    },


    mounted() {
        if (this.$route.params.data == 'accumulated_classes') {
            this.from_route = true;
        }
        this.table.loading = true;
        let c_p = this.$route.params.current_page == null ? 1 : this.$route.params.current_page;
        this.table.currentPage = c_p;
        this.getData(c_p, typeof this.$route.params.search != 'undefined' ? this.$route.params.search : '');
        this.getDataCount(typeof this.$route.params.search != 'undefined' ? this.$route.params.search : '');
    }
}