<template>
    <div id="app">
        <router-view></router-view>
    </div>
</template>

<script>

    import {mapActions} from 'vuex'
    import axios from 'axios'

    export default {
        name: 'app',
        methods: {
            ...mapActions({
                AUTH_LOGOUT: 'AUTH_LOGOUT'
            })
        },
        created: function () {
            const token = localStorage.getItem('token')
            if (token) {
                axios.defaults.headers.common['Authorization'] = 'bearer ' + token
            }
            axios.interceptors.response.use(undefined, (err) => {
                return new Promise(() => {
                    let response = err.response
                    if (this.$router.currentRoute !== undefined && this.$router.currentRoute.name !== 'Login' && response.status === 401 && response.config && !response.config.__isRetryRequest) {
                        this.AUTH_LOGOUT().then(() => {
                            this.$router.push('/login')
                        })
                    }
                    throw response;
                });
            });
        }
    }
</script>

<style>
    body {
        font-family: Segoe UI, Lucida Grande, Helvetica, Arial, Microsoft YaHei, FreeSans, Arimo, Droid Sans, wenquanyi micro hei, Hiragino Sans GB, Hiragino Sans GB W3, FontAwesome, sans-serif;
    }

    #app {
        background: #e9ecf3 !important;
        height: 100vh;
    }
    
</style>
<style lang="scss">
    .utalk_table_search{
        #search_filter_holder{
            .submit_search{
                background: transparent;
                border: solid 1px;
                /*border-radius: 0;*/
                color:  #0d708f;
                margin-right: .5rem;
                font-size: 12px;
                outline: none;
                cursor: pointer;
                box-shadow: none;
            }
            .submit_search:hover{
                background: #0d708f;
                color: #fff;
                border-color: #0d708f;
            }
            .reset_search{
                background: transparent;
                border: solid 1px;
                /*border-radius: 0;*/
                color:  #8f0d0d;
                margin-right: 1rem;
                font-size: 12px;
                outline: none;
                cursor: pointer;
                box-shadow: none;
            }
            .reset_search:hover{
                background: #8f0d0d;
                color: #fff;
                border-color: #8f0d0d;
            }

            .date_range_container{
                width:100%;
                display: inline-flex;
                span {
                    padding-left:10px;
                    padding-right: 10px;
                }
            }

            #its{
                padding-top: 38px;
            }

            label {
                font-weight: bold;
            }
        }
    }
    .curriculum{
        label{
            font-weight: bold;
        }
    }

    .form-group div[role="group"]{
        outline: none!important;
    }
</style>
