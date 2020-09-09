<template>
	<div id="profile_page">
		<div class="white_container">
			<div v-if="!onLoad">
				<b-row>
					<b-col cols="12" md="5" class="mt-3"> 
						<div class="text-center">
							<img :src="profile_image ? profile_image : require('@/assets/image/avatar1.jpg')" class="profile_image">
						</div>
						<div class="ml-md-4 mt-3">
							<b-row>
								<b-col cols="6"><strong>Teacher Name:</strong></b-col> <b-col cols="6"><p>{{user_data.teacher_name}}</p></b-col>
								<b-col cols="6"><strong>Teacher ID:</strong></b-col> <b-col cols="6"><p>{{user_data.teacher_id}}</p></b-col>
								<b-col cols="6"><strong>Employee ID:</strong></b-col> <b-col cols="6"><p>{{user_data.user_id}}</p></b-col>
								<b-col cols="6"><strong>Mobile Number:</strong></b-col> <b-col cols="6"><p v-html="user_data.mobile"></p></b-col>
								<b-col cols="6"><strong>Entry Date:</strong></b-col><b-col cols="6"><p>{{user_data.entry_date}}</p></b-col>							
							</b-row>
						</div>
					</b-col>
					<b-col cols="12" md="7">
						<div class="class-level">
							<hr>
							<h4>Class and Level Information</h4>
							<hr>
							<b-row>
								<b-col cols="6"><strong>All Accumulated Classes:</strong></b-col><b-col cols="6"><p><router-link :to="{name:'Earnings', params:{data:'accumulated_classes',clc:user_data.acc}}">{{user_data.acc}}</router-link></p></b-col>
								<b-col cols="6"><strong>Classes Deducted:</strong></b-col><b-col cols="6"><p><router-link :to="{name:'classes',params:{data:'deducted'} }">{{user_data.cd}}</router-link></p></b-col>
								<b-col cols="6"><strong>Starting Level:</strong></b-col><b-col cols="6"><p>{{user_data.sl}}</p></b-col>
								<!-- <b-col cols="6"><strong>Current Level:</strong></b-col><b-col cols="6"><p>{{user_data.cl}}</p></b-col>
								<b-col cols="6"><strong>Current Total Class since {{user_data.cl}}:</strong></b-col><b-col cols="6"><p>{{user_data.ctcs}}</p></b-col>
								<b-col cols="6"><strong>Next Level:</strong></b-col><b-col cols="6"><p>{{user_data.nl}}</p></b-col>
								<b-col cols="6"><strong>Classes needs to complete:</strong></b-col><b-col cols="6"><p>{{user_data.cntc}}</p></b-col> -->
							</b-row>
						</div>
						</b-col>
				</b-row>
			</div>
			<div v-else><center><loader/></center></div>				
        </div>
    </div>
</template>

<script>
import {mapActions} from 'vuex'
import {api} from '@/constants'
import loader from '@/components/layout/spinner/spinner';

export default {
    name: 'Profile',
    data(){
        return{
			user_data: [],
			profile_image: null,
			onLoad: false
        }
	},
	components: {
		loader
	},
    methods:{
		...mapActions({
			API_GET: 'API_GET'
		}),

		fetchProfileData(){
			this.onLoad = true;
			let url = api+'profile';
            var that = this;
            this.API_GET({url}).then((res) => {
				that.onLoad = false;
				that.user_data = res
				that.profile_image = res.avatar

            }).catch(() => {
                // console.log("There is an error: "+ err);
            })
		},
    },
    mounted() {
		this.fetchProfileData();
	}
}
</script>

<style lang="scss">
.profile_image {
	width: 150px;
	height: 150px;
	border-radius: 50%;
	box-shadow: 0 4px 5px 6px #e0e0e0;
}
.class-level {
	a {
		text-decoration: underline dashed;
	}
}
</style>
