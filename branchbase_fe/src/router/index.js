import Vue from 'vue'
import Router from 'vue-router'

import Login from '@/components/Login.vue'
// import Home from '@/components/Home.vue'
// import Empty from '@/components/Empty.vue'
// import Classes from '@/components/classes/Classes.vue'
// import Profile from '@/components/Profile.vue'
// import Earnings from '@/components/earnings/earnings'
// import Rankings from '@/components/rankings/Rankings.vue'
// import Monthly from '@/components/monthly_data/Monthly_data.vue'
// import Payslip from '@/components/payslip/payslip'
// import BrowserNotCompatible from '@/components/404/BrowserNotCompatible.vue'

Vue.use(Router)

import store from '../store/store.js'

const if_not_authenticated = (to, from, next) => {
    if (!store.getters.IS_AUTHENTICATED) {
        next()
        return
    }
    next('/')
}

const if_authenticated = (to, from, next) => {
    if (store.getters.IS_AUTHENTICATED) {
        next()
        return
    }
    next('/login')
}

const cannot_access = (to, from, next) => {
    // set condition that it cannot be accessed
    next('/')
}

export default new Router({
    mode: 'hash',
    base: '/',
    routes: [
        {
            path: '/Login',
            name: 'Login',
            component: Login,
            beforeEnter: if_not_authenticated
        },
        {
            path: '/404BrowserNotCompatible',
            name: 'BrowserNotCompatible',
            component: () => import('@/components/404/BrowserNotCompatible.vue'),
            beforeEnter: cannot_access,
        },
        {
            path: '/',
            component: () => import('@/components/Home.vue'),
            beforeEnter: if_authenticated,
            children: [
                {
                    path: '/',
                    name: 'profile',
                    component: () => import('@/components/Profile.vue'),
                },
                {
                    path: '/Classes',
                    name: 'classes',
                    component:() => import('@/components/classes/Classes.vue'),
                },
                {
                    path: '/earnings',
                    name: 'Earnings',
                    component: () => import('@/components/earnings/earnings'),
                },
                {
                    path: '/Rankings',
                    name: 'rankings',
                    component: () => import('@/components/rankings/Rankings.vue'),
                },
                {
                    path: '/Monthly',
                    name: 'monthly',
                    component: () => import('@/components/monthly_data/Monthly_data.vue')
                },
                {
                    path:'/payslip',
                    name: 'Payslip',
                    component: () => import('@/components/payslip/payslip')
                },
                {
                    path:'/payroll',
                    name: 'payroll',
                    component: () => import('@/components/admin/Payroll')
                },
                {
                    path:'/permission',
                    name: 'permission',
                    component: () => import('@/components/admin/Permission')
                }
            ]
        },
    ]
})
