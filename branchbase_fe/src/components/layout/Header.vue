<template>
    <div id="default_header">
        <b-navbar class="big_screen_header" toggleable="sm">
            <b-navbar-brand href="#" style="width: 240px;">
                <img class="header_logo" :src="require('@/assets/image/logo_white.png')">
            </b-navbar-brand>
            <b-navbar-toggle @click="toggle_sidebar" class="desktop_toggle" target="default_sidebar">
                <fa icon="bars"/>
            </b-navbar-toggle>
            <b-navbar-toggle target="default_sidebar" class="tablet_toggle" @click="toggle_sidebar_mobile">
                <fa icon="bars"/>
            </b-navbar-toggle>

            <b-collapse id="nav-collapse" is-nav>
                <b-navbar-nav class="ml-auto">
                    <b-nav-item-dropdown right class="profile_btn">
                        <template v-slot:button-content>
                            <img :src="profile_image ? profile_image : require('@/assets/image/avatar1.jpg')">
                            {{username}}
                            <fa icon="angle-down"/>
                        </template>
                        <b-dropdown-item :to="{name: 'profile'}" class="profile_icons">
                            <fa icon="user"/>
                            Profile
                        </b-dropdown-item>
                        <b-dropdown-item href="#" @click="logout">
                            <fa icon="sign-out-alt"/>
                            Logout
                        </b-dropdown-item>
                    </b-nav-item-dropdown>
                </b-navbar-nav>
            </b-collapse>
        </b-navbar>

        <b-navbar class="mobile_screen_header">
            <b-navbar-toggle target="default_sidebar" @click="toggle_sidebar_mobile">
                <fa icon="bars"/>
            </b-navbar-toggle>
            <b-navbar-brand href="#"><img class="header_logo" :src="require('@/assets/image/logo_white.png')">
            </b-navbar-brand>
            <div class="setting_icon" @click="toggle_settings">
                <fa icon="ellipsis-v"/>
            </div>
        </b-navbar>

        <transition name="fade_from_top">
            <div class="setting_container" v-if="show_setttings">
                <ul>
                    <li>
                        <img class="setting_items" :src="require('@/assets/image/avatar1.jpg')">
                    </li>
                    <li>
                        <fa icon="sign-out-alt"/>
                    </li>
                </ul>
            </div>
        </transition>
    </div>
</template>

<script>
    import {mapActions,mapGetters} from 'vuex';
    import {api} from '@/constants'

    export default {
        name: 'Header',
        data() {
            return {
                toggle: 1,
                toggle_m: 1,
                show_setttings: false,
                profile_image: null,
                username: null
            }
        },
        computed: {
            ...mapGetters({
                toggle_status: 'SIDEBAR_TOGGLE',
                togglem_status: 'SIDEBAR_TOGGLE_MOBILE'
            }),
        },
        methods: {
            ...mapActions({
                AUTH_LOGOUT: 'AUTH_LOGOUT',
                API_GET: 'API_GET'
            }),
            
            logout() {
                this.AUTH_LOGOUT().then(() => {
                    this.$router.push('/login')
                })
            },
            toggle_sidebar() {
                this.toggle = this.toggle == 1 ? 2 : 1
                this.$store.commit('TOGGLE_SIDEBAR', this.toggle);
            },
            toggle_sidebar_mobile() {
                this.$store.commit('TOGGLE_SIDEBAR_MOBILE', this.togglem_status == 1 ? 2:1);
            },
            toggle_settings() {
                this.show_setttings = this.show_setttings ? false : true
            },
            fetchProfileImage() {
                let url = api + "header-data";
                var that = this;
                this.API_GET({url}).then((res) => {
                    that.profile_image = res.avatar
                    that.username = res.teacher_name
                }).catch(() => {
                    // console.log("There is an error: "+ err);
                })
            }
        },
        mounted() {
            this.fetchProfileImage();
        }
    }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="scss">
    #default_header {
        // z-index:10 !important;
        border-bottom: solid 1px #e8e9ed;

        .header_logo {
            width: 50%;
            /*margin-left: 1.5rem;*/
            margin: auto;
            display: block;
        }

        .navbar {
            background: #fff;
            color: #0D708F;
            padding: 0;

            .navbar-brand {
                width: 20%;
                padding: .5rem;
                /*border-right: 1px solid #e8e9ed;*/
                height: 49.61px;
                margin: 0;
                display: inline-flex;
            }

            .navbar-toggler {
                display: inline-flex;
                border: none;
                color: #0D708F;
                outline: none;
                padding: 1rem;
                border-right: 1px solid #e8e9ed;
                border-left: 1px solid #e8e9ed;
                transition: all ease-in-out .3s;
                border-radius: 0;

                &:hover {
                    color: #169cc6;
                }
            }

            .profile_btn {
                margin-right: .5rem;
                font-size: 12px;
                font-weight: 700;

                .navbar-light .navbar-nav .nav-link {
                    color: #000;
                }

                svg {
                    font-size: 10px;
                    margin-left: .5rem;
                }

                .dropdown-toggle::after {
                    display: none !important;
                }

                img {
                    width: 1.9rem;
                    border-radius: 50px;
                    box-shadow: 0 3px 5px #e0e0e0;
                    border: solid 2px #fff;
                    object-fit: contain;
                    margin-right: .5rem
                }

                .dropdown-item {
                    color: #626a6d;

                    svg {
                        font-size: 15px;
                        margin-right: .5rem;
                    }
                }
            }
        }

        .mobile_screen_header {
            display: none;
        }

        .tablet_toggle {
            display: none !important;
        }
    }

    @media (min-width: 768px) and (max-width: 991px) {
        #default_header {
            .header_logo {
                width: 70%;
            }

            .tablet_toggle {
                display: inline-flex !important;
            }

            .desktop_toggle {
                display: none !important;
            }
        }
    }

    @media (max-width: 767px) {
        #default_header {
            .big_screen_header {
                display: none;
            }

            .mobile_screen_header {
                display: block;
                position: relative;
                box-shadow: 0 0.46875rem 2.1875rem rgba(4, 9, 20, 0.03), 0 0.9375rem 1.40625rem rgba(4, 9, 20, 0.03), 0 0.25rem 0.53125rem rgba(4, 9, 20, 0.05), 0 0.125rem 0.1875rem rgba(4, 9, 20, 0.03);
                z-index: 1;

                .navbar-toggler {
                    border-radius: 0;
                }

                .navbar-brand {
                    width: 30%;

                    .header_logo {
                        width: 7rem;
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                    }
                }

                .setting_icon {
                    position: absolute;
                    right: 3.5%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                    cursor: pointer;
                    font-size: 1.3rem;
                }
            }

            .setting_container {
                position: absolute;
                top: 70px;
                right: 2%;
                background: #fff;
                padding: .5rem;
                border-radius: 50px;
                box-shadow: 0 0.46875rem 2.1875rem rgba(4, 9, 20, 0.03), 0 0.9375rem 1.40625rem rgba(4, 9, 20, 0.03), 0 0.25rem 0.53125rem rgba(4, 9, 20, 0.05), 0 0.125rem 0.1875rem rgba(4, 9, 20, 0.03);
                z-index: 2;

                ul {
                    padding: 0;
                    list-style: none;
                    /*display: inline-block;*/
                    margin: 0;

                    li {
                        /*display: inline-block;*/
                        position: relative;
                        height: 40px;
                        width: 40px;
                        border-radius: 50px;
                        overflow: hidden;
                        margin: .5rem 0;
                        background: #f5f5f5;
                        color: #0D708F;

                        img {
                            object-fit: contain;
                            height: 40px;
                        }

                        svg {
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                        }
                    }
                }
            }
        }
    }

    .fade_from_top-enter-active {
        animation: fade_from_top ease .3s;
    }

    .fade_from_top-leave-active {
        animation: fade_from_top ease .3s reverse;
    }

    @keyframes fade_from_top {
        0% {
            opacity: 0;
            top: 50px;
        }
        100% {
            opacity: 1;
            top: 70px;
        }
    }

</style>
