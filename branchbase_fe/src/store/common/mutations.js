export default {
	AUTH_REQUEST: (state) => {
		state.status = 'loading'
	},
	AUTH_SUCCESS: (state, token) => {
		state.status = 'success'
		state.token = token
	},
	AUTH_ERROR: (state) => {
		state.status = 'error'
	},
	AUTH_LOGOUT: (state) => {
		state.token = ''
	},

	TOGGLE_SIDEBAR : (state, toggle) => {
		state.sidebar_toggle = toggle
		state.sidebar_toggle_mobile = 1
	},
	TOGGLE_SIDEBAR_MOBILE : (state,toggle) => {
		state.sidebar_toggle_mobile = toggle
		state.sidebar_toggle = 1
	}
}
