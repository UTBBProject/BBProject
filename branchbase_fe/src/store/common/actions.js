
import axios from 'axios'
import {api} from '@/constants'
export default {
    AUTH_REQUEST: ({commit}, {data}) => {
        return new Promise((resolve, reject) => {
            commit('AUTH_REQUEST')
            let url = api + 'login'

            axios.post(url, data)
                .then(response => {
                    let token = response.data.token

                    localStorage.setItem('token', token)
                    axios.defaults.headers.common['Authorization'] = 'bearer ' + token
                    commit('AUTH_SUCCESS', token)

                    resolve(response)
                })
                .catch(err => {
                    commit('AUTH_ERROR', err)
                    localStorage.removeItem('token')
                    reject(err.data)
                })
        })
    },
    AUTH_LOGOUT: ({commit}) => {
        return new Promise((resolve) => {
            commit('AUTH_LOGOUT')
            localStorage.removeItem('token')

            delete axios.defaults.headers.common['Authorization']
            resolve()
        })
    },

    PROFILE: ({ dispatch }) => {
        return dispatch('API_GET', {
            url: api + 'profile'
        })
    },
    EARNINGS: ({ dispatch }) => {
        return dispatch('API_GET', {
            url: api + 'my-earnings-log'
        })
    },

    CLASS_LIST: ({ dispatch }) => {
        return dispatch('API_GET', {
            url: api + 'my-class-list'
        })
    },

    HEADER_DATA: ({ dispatch }) => {
        return dispatch('API_GET', {
            url: api + 'header-data'
        })
    },

    //common function to access backend with token as header
    API_POST: ({dispatch}, {url, data, headers}) => {
        return new Promise((resolve, reject) => {
            data = data || {}
            headers = headers || {}

            axios.post(url, data, headers)
                .then(response => {
                    let payload = {response, resolve, reject}
                    dispatch('VALIDATE_API', payload)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    API_PUT: ({dispatch}, {url, data, headers}) => {
        return new Promise((resolve, reject) => {
            data = data || {}
            headers = headers || {}

            var json_data = Object.fromEntries(data);

            if( (typeof json_data) != 'object'){
                json_data = JSON.parse(json_data)
            }
            
            axios.put(url, json_data, headers)
                .then(response => {
                    let payload = {response, resolve, reject}
                    dispatch('VALIDATE_API', payload)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    API_PATCH: ({dispatch}, {url, data, headers}) => {
        return new Promise((resolve, reject) => {
            data = data || {}
            headers = headers || {}

            var json_data = Object.fromEntries(data);

            if( (typeof json_data) != 'object'){
                json_data = JSON.parse(json_data)
            }
            
            axios.patch(url, json_data, headers)
                .then(response => {
                    let payload = {response, resolve, reject}
                    dispatch('VALIDATE_API', payload)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    API_GET: ({dispatch}, {url, param}) => {
        return new Promise((resolve, reject) => {
            let apiGet = (param)
                ? () => axios.get(url, {params: param})
                : () => axios.get(url)

            apiGet()
                .then(response => {
                    let payload = {response, resolve, reject}
                    dispatch('VALIDATE_API', payload)
                })
                .catch(error => {
                    reject(error)
                })
        })
    },

    VALIDATE_API: (context, {response, resolve, reject}) => {
        if (response.status !== 200) return reject(response)
        return resolve(response.data)
    },

    // FORGET_PASS: ({commit}, {data}) => {
    FORGET_PASS: ({data}) => {
        return new Promise((resolve, reject) => {
            let url = api + 'forget_pass'

            axios.post(url, data)
                .then(response => {
                    resolve(response)
                })
                .catch(err => {
                    reject(err)
                })
        })
    },

    FORGET_PASS_OTP: ({data}) => {
    // FORGET_PASS_OTP: ({commit}, {data}) => {
        return new Promise((resolve, reject) => {
            let url = api + 'forget_pass_otp'

            axios.post(url, data)
                .then(response => {
                    resolve(response)
                })
                .catch(err => {
                    reject(err)
                })
        })
    },

    CHANGE_PASS: ({data}) => {
    // CHANGE_PASS: ({commit}, {data}) => {
        JSON.stringify(Object.fromEntries(data));
        // console.log(data)
        return new Promise((resolve, reject) => {
            let url = api + 'change_pass'

            axios.put(url, data)
                .then(response => {
                    resolve(response)
                })
                .catch(err => {
                    reject(err)
                })
        })
    },
}
