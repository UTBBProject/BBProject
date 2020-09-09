import getters from './getters'
import mutations from './mutations'
import actions from './actions'

const state = {
    token: localStorage.getItem('token') || '',
    status: '',

    sidebar_toggle: 1,
    sidebar_toggle_mobile: 1
}

export default {
    state: state,
    getters: getters,
    mutations: mutations,
    actions: actions
}
