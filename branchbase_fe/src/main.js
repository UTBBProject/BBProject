import Vue from 'vue'
import App from './App.vue'
//vuex
import store from './store/store.js'
import 'es6-promise/auto'
//vue router
import router from './router'
// bootstrap plugin
import BootstrapVue from 'bootstrap-vue'
import 'bootstrap/dist/css/bootstrap.css'
import 'bootstrap-vue/dist/bootstrap-vue.css'
import '@/assets/css/common.css'

//vue axios
import axios from 'axios'
import VueAxios from 'vue-axios'

//datetimepicker
import DatePicker from 'vue2-datepicker';

// FONTAWESOME ICONS
import { library } from '@fortawesome/fontawesome-svg-core'
import { faHome,faLock, faEnvelope, faUser, faBookReader, faCalendarDay, faMoneyBillWaveAlt, faBars, faSignOutAlt, faEllipsisV, faAngleDown, faStickyNote, faTimes } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
library.add(faHome , faLock, faEnvelope, faUser, faBookReader, faCalendarDay, faMoneyBillWaveAlt, faBars, faSignOutAlt, faEllipsisV, faAngleDown, faStickyNote,faTimes)
Vue.component('fa', FontAwesomeIcon)

Vue.use(BootstrapVue)
Vue.use(VueAxios, axios)
Vue.use(DatePicker)

Vue.config.productionTip = false

new Vue({
	el: '#app',
	router,
	store,
	render: h => h(App),
}).$mount('#app')

const token = localStorage.getItem('token')
if (token) {
	axios.defaults.headers.common['Authorization'] = 'bearer ' + token
}

axios.defaults.withCredentials = true
