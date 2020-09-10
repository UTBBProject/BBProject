import pagination from '@/components/layout/pagination/pagination';
import Nodata from '@/components/layout/Nodata';
import loader from '@/components/layout/spinner/spinner';

export default {
	data(){
		return {
			pageNumber: 1,
		};
	},
	mounted(){
	},
	components:{
		pagination,
		Nodata,
		loader
	},
	props: {
		// This will be the header thead
		headerData:{ type: Array, default: [] },
		// this array of objects is the data list that will be shown
		dataList:{ type: Array, default: null },
		// this set the page count
		pageCount:{ type: Number, default: 0 },
		// this function returns function(page Number)
		getPageNum: { type: Function },
		// this sets the style for the table
		tableStyle: { type: String, default: "" },
		// this disables or enables action collumn
		actionPass: { type: Boolean, default: false },
		// array of objects that defines the action button
		actionArray: { type: Array },
		// this basivally a function that returns (id,key,callback)
		actionClick: { type:Function},
		// set table to loading state
		onLoad: { type: Boolean, default: false },
		// set your action width
		actionCollumnWidth: { type: Number, defaukt: 300 },
		// style for your rows
		trStyle: { type: String, default: "" },
		// style for your header
		headerStyle: { type: String, default: "" },
		// enables or disables pagination
		paginationShow: { type: Boolean, default: true },
		// declare an array of numbers, doesnt show collumn in number 0(default) [1,2,3]
		disableColl: { type: [Number,Array], default: 0 },
		// current page
		currentPage: { type: Number },
		// if not data
		noData: {type: Boolean, default: false}
	},
	methods: {
		paginationClickHandler(num){
			this.getPageNum(num);
		},
		setBody(list){
			let that = this;
			return Object.values(list).filter( (obj, index) => !that.table.disableColl.includes(index+1));
		}
	},
	computed: {
		setTableHead: function() {
			let that = this;
			return this.headerData.filter(function(item, index) {
				if(Array.isArray(that.disableColl)){
					if(!that.disableColl.includes(index+1)){
						return item!="" ? item : "-";
					}
				}else if((index+1)!=that.disableColl){
					return item!="" ? item : "-";
				}
			})
		}
	}
}