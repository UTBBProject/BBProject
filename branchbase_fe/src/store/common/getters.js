export default {
	IS_AUTHENTICATED: state => !!state.token,
	AUTH_STATUS: state => state.status,

	SIDEBAR_TOGGLE: state => state.sidebar_toggle,
	SIDEBAR_TOGGLE_MOBILE: state => state.sidebar_toggle_mobile,
}
