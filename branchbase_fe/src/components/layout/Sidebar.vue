<template>
    <div id="default_sidebar" style="height: 90vh" :class="toggle_status == 2 ? 'toggled' : ''">

        <!-- for wider screen -->
        <transition name="expanding">
            <div class="expanded_sidebar" style="height: 100% !important;">
                <!-- <p class="title_label" v-if="toggle_status==1"> General</p> -->
                <ul>
                    <li>
                        <router-link :to="{name:'profile'}" >
                            <div class="main_menu" :class="$route.name == 'profile' ? 'active': ''">
                                <fa class="mm_icon" icon="home" />
                                <span v-if="toggle_status==1">Home</span>
                            </div>
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name:'Earnings'}" >
                            <div class="main_menu" :class="$route.name == 'Earnings' ? 'active': ''">
                                <fa class="mm_icon" icon="money-bill-wave-alt" />
                                <span v-if="toggle_status==1">Earning Logs</span>
                            </div>
                        </router-link>
                    </li>
                    
                    <li>
                        <router-link :to="{name:'classes'}" >
                            <div class="main_menu" :class="$route.name == 'classes' ? 'active': ''">
                                <fa class="mm_icon" icon="book-reader" />
                                
                                <span v-if="toggle_status==1">Classes</span>
                            </div>
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name:'monthly'}" >
                            <div class="main_menu" :class="$route.name == 'monthly' ? 'active': ''">
                                <fa class="mm_icon" icon="calendar-day" />
                                <span v-if="toggle_status==1">Monthly Data</span>
                            </div>
                        </router-link>
                    </li>
                    <li v-if="false">
                        <router-link :to="{name:'Payslip'}" >
                            <div class="main_menu" :class="$route.name == 'Payslip' ? 'active': ''">
                                <fa class="mm_icon" icon="sticky-note" />
                                <span v-if="toggle_status==1">Payslip</span>
                            </div>
                        </router-link>
                    </li>
                    <li hidden>
                        <router-link :to="{name:'payroll'}" >
                            <div class="main_menu" :class="$route.name == 'payroll' ? 'active': ''">
                                <fa class="mm_icon" icon="money-bill-wave-alt" />
                                <span v-if="toggle_status==1">Payroll</span>
                            </div>
                        </router-link>
                    </li>
                </ul>
            </div>
        </transition>

        <!-- for mobile screen -->
        <transition name="mobile_fade">
            <div class="mobile_sidebar" v-if="togglem_status == 2">
                <p class="title_label"> General</p>
                <ul>
                    <li>
                        <div class="main_menu">
                            <fa class="mm_icon" icon="home" /> Home
                        </div>
                    </li>
                    <li>
                        <router-link :to="{name:'Earnings'}" >
                            <div @click="toggleButton" class="main_menu" :class="$route.name == 'Earnings' ? 'active': ''" >
                                <fa class="mm_icon" icon="money-bill-wave-alt" /> Earning Logs
                            </div>
                        </router-link>
                    </li>
                    
                    <li>
                        <router-link :to="{name:'classes'}" >
                            <div @click="toggleButton" class="main_menu" :class="$route.name == 'classes' ? 'active': ''">
                                <fa class="mm_icon" icon="book-reader" /> Classes
                            </div>
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name:'monthly'}" >
                            <div @click="toggleButton" class="main_menu" :class="$route.name == 'monthly' ? 'active': ''">
                                <fa class="mm_icon" icon="calendar-day" /> Monthly Data
                            </div>
                        </router-link>
                    </li>
                    <li>
                        <router-link :to="{name:'Payslip'}" >
                            <div @click="toggleButton" class="main_menu" :class="$route.name == 'Payslip' ? 'active': ''">
                                <fa class="mm_icon" icon="sticky-note" /> Payslip
                            </div>
                        </router-link>
                    </li>
                    <li hidden>
                        <router-link :to="{name:'Payroll'}" >
                            <div @click="toggleButton" class="main_menu" :class="$route.name == 'Payroll' ? 'active': ''">
                                <fa class="mm_icon" icon="sticky-note" /> Payroll
                            </div>
                        </router-link>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<script>
import { mapGetters } from 'vuex'
export default {
    name: 'SideBar',
    data(){
        return{
            sideheight: 0
        }
    },
    mounted(){
        // console.log(this.$route);
        var windh = window.innerHeight;
        var headh_arr = document.querySelectorAll('.navbar');
        var headh = 0;
        for (var i = 0; i < headh_arr.length; i++) {
            if(!this.is_hidden(headh_arr[i])){
                headh = headh_arr[i].clientHeight;
            }
        }
        this.sideheight = windh - (headh);
    },
    computed: {
        ...mapGetters({
            toggle_status: 'SIDEBAR_TOGGLE',
            togglem_status: 'SIDEBAR_TOGGLE_MOBILE'
        }),
    },
    methods:{
        toggleButton(){
            this.$store.commit('TOGGLE_SIDEBAR_MOBILE', this.togglem_status == 1 ? 2:1);
        },
        is_hidden(el) {
            var style = window.getComputedStyle(el);
            return ((style.display === 'none') || (style.visibility === 'hidden'))
        },
        // toggle_dropdown(evt){
            // var all_menus = document.querySelectorAll('li .main_menu');
            // for (var i = 0; i < all_menus.length; i++) {
            //     all_menus[i].classList.remove('active')
            // }

            // var parent_element = evt.target.closest('li');
            // parent_element.querySelector('.main_menu').classList.add('active')
        // },
        // removed since it conflicts with header
        // active_toggled(evt){
        //     var all_menus = document.querySelectorAll('.short_sidebar li');
        //     for (var i = 0; i < all_menus.length; i++) {
        //         all_menus[i].classList.remove('active')
        //     }
        //     var parent_element = evt.target.closest('li');
        //     parent_element.classList.add('active')
        // },

        // removed since it conflicts in header data
        // toggle_sidebar_mobile() {
        //     this.toggle_m = this.toggle_m == 1 ? 2 : 1
        //     this.$store.commit('TOGGLE_SIDEBAR_MOBILE', this.toggle_m);
        //     console.log(this.$store);
        // },
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="scss">
#default_sidebar{
    width: 240px;
    display: inline-block;
    transition: 0.2s;
    
    &.toggled{
        width: 70px;
    }

    .expanded_sidebar{
        width: 99.5%;
        overflow-x: hidden;
        overflow-y: auto;
        background: #fff;

        .title_label{
            font-size: 15px;
            padding: 1rem 0 1rem 2rem;
            font-weight: 700;
            color: #0D708F;
            text-transform: uppercase;
            margin: 0;
        }

        ul{
            padding: 0;
            list-style: none;
            font-size: 14px;
            color: #626a6d;

            li{
                width: 240px;
                a{
                    text-decoration: none;
                    color: #607d8b;
                }
                .main_menu{
                    width: 100%;
                    padding: 1rem 0 1rem 2rem;
                    position: relative;
                    cursor: pointer;

                    .mm_icon{
                        margin-right: 1rem;
                    }

                    .mm_caret{
                        position: absolute;
                        right: 5%;
                        top: 50%;
                        transform: rotate(0deg) translate(-50%,-50%);
                        color: #607d8b;
                    }

                    &:hover{
                        background: #cecece;
                        color: #0D708F;

                        &::after{
                            display:block;
                        }
                    }

                    &.active{
                        background: #fff;
                        color: #0D708F;
                        &:hover{
                            background: #cecece;
                            color: #0D708F;
                        }

                        &::after{
                            display:block;
                        }

                        .mm_caret{
                            transform: rotate(90deg) translate(-50%,-50%);
                            right: 10%;
                            top: 45%;
                        }
                    }

                    &::after{
                        content: '';
                        position: absolute;
                        left: 0;
                        top: 0;
                        height: 100%;
                        width: 5px;
                        background: #0D708F;
                        display: none;
                    }
                }

                .sub_menu{
                    li{
                        padding: .5rem 0 .5rem 4rem;
                        position: relative;
                        cursor: pointer;
                        color: #8a9093;

                        svg{
                            color: #8a9093!important;
                            font-size: 7px;
                            vertical-align: middle;
                        }

                        &:hover{
                            color: #0D708F;
                        }

                        .sm_icon{
                            margin-right: 1rem;
                        }
                    }
                }
            }
        }
    }

    .short_sidebar{
        width: 99.5%;
        overflow-x: hidden;
        overflow-y: auto;
        background: #fff;

        ul{
            padding: 0;
            list-style: none;
            color: #626a6d;

            li{
                width: 100%;
                position: relative;
                font-size: 1rem;
                height: 3rem;
                text-align: center;
                cursor: pointer;
                transition: all ease-in-out 0.3s;

                svg{
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%,-50%);
                }

                &.active{
                    color: #0D708F;
                    box-shadow: 0 0 5px #d3d3d3;
                    &::after{
                        content: '';
                        position: absolute;
                        right: 0;
                        top: 0;
                        background: #0D708F;
                        width: 5px;
                        height: 100%;
                    }
                }

                &:hover{
                    background: #0D708F;
                    color: #fff;
                    box-shadow: 0 0 5px #d3d3d3;
                }
            }
        }
    }

    .expanding-enter-active{
        animation: fadeIn ease 1s;
    }
}

@media (max-width: 991px){
    #default_sidebar{
        width: 0;

        .expanded_sidebar,.short_sidebar{
            display: none;
        }

        .mobile_sidebar{
            position: absolute;
            top: 0;
            left: 0;
            background: #fff;
            width: 100%;
            height: 100%;
            z-index: 5;
            .title_label{
                font-size: 15px;
                padding: 1rem 0 1rem 2rem;
                font-weight: 700;
                color: #0D708F;
                text-transform: uppercase;
                margin: 0;
            }

            ul{
                padding: 0;
                list-style: none;
                font-size: 14px;
                color: #626a6d;

                li{
                    a{
                        text-decoration: none;
                    }
                    .main_menu{
                        padding: 1rem 0 1rem 2rem;
                        position: relative;
                        cursor: pointer;

                        .mm_icon{
                            margin-right: 1rem;
                        }

                        .mm_caret{
                            position: absolute;
                            right: 5%;
                            top: 50%;
                            transform: rotate(0deg) translate(-50%,-50%);
                            color: #607d8b;
                        }

                        &:hover{
                            background: #cecece;
                            color: #0D708F;

                            &::after{
                                display:block;
                            }
                        }

                        &.active{
                            &:hover{
                                background: #cecece;
                                color: #0D708F;
                            }
                            background: #fff;
                            color: #0D708F;

                            &::after{
                                display:block;
                            }

                            .mm_caret{
                                transform: rotate(90deg) translate(-50%,-50%);
                                right: 10%;
                                top: 45%;
                            }
                        }

                        &::after{
                            content: '';
                            position: absolute;
                            left: 0;
                            top: 0;
                            height: 100%;
                            width: 5px;
                            background: #0D708F;
                            display: none;
                        }
                    }

                    .sub_menu{
                        li{
                            padding: .5rem 0 .5rem 4rem;
                            position: relative;
                            cursor: pointer;
                            color: #8a9093;

                            svg{
                                color: #8a9093!important;
                                font-size: 7px;
                                vertical-align: middle;
                            }

                            &:hover{
                                color: #0D708F;
                            }

                            .sm_icon{
                                margin-right: 1rem;
                            }
                        }
                    }
                }
            }
        }
    }

    #default_content .main_content_holder{
        width: 100%!important;
    }

    .mobile_fade-enter-active{
        animation: mobile_fade ease 0.3s;
    }
    .mobile_fade-leave-active{
        animation: mobile_fade ease 0.3s reverse;
    }
}
@keyframes fadeIn {
    0%{
        opacity: 0;
    }
    100%{
        opacity: 1;
    }
}

@keyframes mobile_fade{
    0%{
        width: 0%;
        opacity: 0;
        overflow: hidden;
    }
    100%{
        width: 100%;
        opacity: 1;
        overflow: hidden;
    }
}
</style>
