<template>
    <div v-if="restrictedBrowser" id="bnc"><BrowserNotCompatible/></div>	
    <div v-else id="login_container">
        <div class="left_side">
            <img src="../assets/image/bg_new.png" alt="" style="border: 2px solid white white; height: 400px;">
        </div>
        <div class="right_side" style="background-color: white;">
            <img class="logo_icon" :src="require('@/assets/image/logo_white.png')">
            <!-- <p class="title">Branch Base</p> -->
            <div class="form_holder">
                <b-form @submit="onSubmit($event)" @reset="onReset">
                    <b-form-group>
                        <label for="uname">
                            <fa icon="user"></fa>
                            Username</label>
                        <b-form-input type="text" id="uname" class="general_input" v-model="login.username"
                            trim></b-form-input>
                    </b-form-group>
                    <b-form-group>
                        <label for="upass">
                            <fa icon="lock"></fa>
                            Password</label>
                        <b-form-input type="password" id="upass" autocomplete="autocomplete" class="general_input"
                            v-model="login.password" trim></b-form-input>
                    </b-form-group>
                    <p v-if="login.error != ''" for="" class="error-login">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ login.error }}
                    </p>
                    <b-button class="login_btn" type="submit" style="margin-top: 5px;" :disabled="login.onLoad">
                        <b-spinner v-if="login.onLoad" small  variant="primary" label="Spinning"></b-spinner>
                        <span v-else>Login</span>
                    </b-button>
                    <p class="forgot_pass" @click="OpenForgetPass">Forgot Password?</p>
                </b-form>
            </div>
        </div>

        <div class="mobile_login">
            <div class="upper_container">
                <img class="logo_icon" :src="require('@/assets/image/logo_white.png')">
            </div>
            <div class="lower_container form_holder">
                <b-form @submit="onSubmit($event)" @reset="onReset">
                    <b-form-group>
                        <label for="muname">
                            <fa icon="user"></fa>
                            Username</label>
                        <b-form-input type="text" id="muname" class="general_input" v-model="login.username"
                            trim></b-form-input>
                    </b-form-group>
                    <b-form-group>
                        <label for="mupass">
                            <fa icon="lock"></fa>
                            Password</label>
                        <b-form-input type="password" id="mupass" autocomplete="autocomplete" class="general_input"
                            v-model="login.password" trim></b-form-input>
                    </b-form-group>
                    <p v-if="login.error != ''" for="" class="error-login">
                        <i class="fas fa-exclamation-triangle"></i>
                        {{ login.error }}
                    </p>
                    <b-button class="login_btn" type="submit">
                        <b-spinner v-if="login.onLoad" small  variant="primary" label="Spinning" :disabled="login.onLoad"></b-spinner>
                        <span v-else>Login</span>
                    </b-button>
                    <p class="forgot_pass" @click="OpenForgetPass">Forgot Password?</p>
                </b-form>
            </div>
        </div>

        <b-modal id="modalForget" style="margin-top: 10rem!important" @hide="clear_interval_otp">
            <template>
                <div class="form_holder">
                    <b-form @submit="forgetSubmit($event)" @reset="onReset">
                        <b-form-group v-if="!forget.toPassKey && !forget.toNewPass">
                            <label for="uname">
                                <fa icon="user"></fa>
                                Username</label>
                            <b-form-input type="text" id="uname" class="general_input" v-model="forget.username"
                                          trim></b-form-input>
                        </b-form-group>
                        <b-form-group v-if="!forget.toPassKey && !forget.toNewPass">
                            <label for="email">
                                <fa icon="envelope"></fa>
                                E-mail</label>
                            <b-form-input type="text" id="email" autocomplete="autocomplete" class="general_input"
                                          v-model="forget.email" trim></b-form-input>
                            <p v-if="forget.errCredentials" for="" class="error-login" style="margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{forget.errCredentialsMsg}}
                            </p>
                        </b-form-group>
                        <b-form-group v-if="forget.toPassKey && !forget.toNewPass">
                            <label for="otp">
                                <fa icon="lock"></fa>
                                Confirmation Key</label>
                            <b-form-input type="number" id="otp" autocomplete="autocomplete" class="general_input"
                                            v-model="forget.otp" @keypress.native="validate_otp" trim></b-form-input>
                            <label style="font-size: 0.6rem;">*Please check your email for your Confirmation Key</label>
                            <div>
                                <label style="font-size: 0.6rem;">{{forget.otp_exp_label}}
                                    <span style="font-weight: bolder;font-size: 0.9rem">{{forget.otp_timer}}</span></label>
                            </div>
                            <p v-if="forget.existingOtp" for="" class="error-login" style="margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                Confirmation Key already sent to email
                            </p>
                            <p v-if="forget.matchOtp" for="" class="error-login" style="margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{forget.errorOtp}}
                            </p>
                        </b-form-group>
                        <b-form-group v-if="forget.toNewPass">
                            <label for="newPass">
                                <fa icon="lock"></fa>
                                New Password</label>
                            <b-form-input type="password" id="newPass" autocomplete="autocomplete" class="general_input"
                                          v-model="forget.newPass" trim></b-form-input>
                        </b-form-group>
                        <b-form-group v-if="forget.toNewPass">
                            <label for="confirmPass">
                                <fa icon="lock"></fa>
                                Confirmation Password</label>
                            <b-form-input type="password" id="confirmPass" autocomplete="autocomplete" class="general_input"
                                          v-model="forget.confirmPass" trim></b-form-input>
                            <div>
                                <label style="font-size: 0.6rem;">{{forget.otp_exp_label}}
                                    <span style="font-weight: bolder;font-size: 0.9rem">{{forget.otp_timer}}</span></label>
                            </div>
                            <p v-if="forget.matchPw" for="" class="error-login" style="margin-top:1rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                {{forget.pw_err_msg}}
                            </p>
                        </b-form-group>
                        <b-button class="login_btn" type="submit" style="margin-top: 5px;width:50%;" :disabled="forget.onLoad">
                            <b-spinner v-if="forget.onLoad" small  variant="primary" label="Spinning"></b-spinner>
                            <span v-else>Submit</span>
                        </b-button>
                        <p v-if="forget.expiredPw" for="" class="error-login" style="margin-top:1rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Request Expired. Please try Again.
                        </p>
                        <p class="forgot_pass" @click="OpenForgetPass" v-if="forget.requestOtp">Request Confirmation Key</p>
                    </b-form>
                </div>
            </template>

            <template v-slot:modal-footer>
                <div class="w-100" hidden>
                    <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalForget')">
                        Close
                    </b-button>
                </div>
            </template>
        </b-modal>

        <b-modal id="modalSuccess" class="modal_success">
            <template>
                <b-form-group>
                    <div class="lower_container form_holder" style="text-align: center;">
                        <label style="font-size: 1.5rem">Successfully Changed Password</label>
                    </div>
                </b-form-group>
            </template>

            <template v-slot:modal-footer hidden>
                <div class="w-100" hidden>
                    <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalSuccess')">
                        Close
                    </b-button>
                </div>
            </template>
        </b-modal>
    </div>
</template>

<script>
    import {mapActions} from 'vuex'
    import BrowserNotCompatible from '@/components/404/BrowserNotCompatible';
    import {api} from '@/constants';
    export default {
        name: 'LoginPage',
        data() {
            return {
                login: {
                    username: '',
                    password: '',
                    error: '',
                    onLoad: false
                },
                forget: {
                    username: '',
                    email: '',
                    onLoad: false,
                    toPassKey: false,
                    otp: '',
                    toNewPass: false,
                    newPass: '',
                    confirmPass: '',
                    matchPw: false,
                    matchOtp: false,
                    errCredentials: false,
                    errCredentialsMsg: '',
                    existingOtp: false,
                    pw_err_msg: '',
                    otp_timer: '',
                    exp_time: '',
                    intervalVar: '',
                    otp_exp_label: '',
                    expiredPw: false,
                    requestOtp: false,
                    errorOtp: ''
                },
                restrictedBrowser: [],
            }
        },
        components: {
            BrowserNotCompatible
        },
        methods: {
            ...mapActions({
                AUTH_REQUEST: 'AUTH_REQUEST',
                FORGET_PASS: 'FORGET_PASS',
                FORGET_PASS_OTP: 'FORGET_PASS_OTP',
                // CHANGE_PASS: 'CHANGE_PASS',
                API_GET: 'API_GET',
                API_PUT: 'API_PUT',
                API_PATCH: 'API_PATCH'
            }),
            onSubmit(evt) {
                this.login.onLoad = true;
                evt.preventDefault();
                let data = new FormData()
                let that = this;
                data.append('username', this.login.username)
                data.append('password', this.login.password)

                this.AUTH_REQUEST({ data }).then(() => {
                    this.$router.push('/');
                }).catch(err => {
                    if(that.login.username != "" && that.login.password != ""){
                        if(err.message != '' && err.message != null && err.message.length < 63){
                            this.login.error = err.message;
                        }else{
                            this.login.error = "No connection could be made, the target machine refused it.";
                        }
                    }else{
                        this.login.error = "Fields are empty.";
                    }
                    
                    this.login.onLoad = false;
                    /* eslint-disable */
                    console.log(err.data.message) //todo
                })
            },
            clear_interval_otp(){
                clearInterval(this.forget.intervalVar)
            },
            setIntervalOtp(){
                this.forget.intervalVar = setInterval(() => {
                    this.forget.otp_timer = ''
                    var date_computed = this.start_otp_count(new Date(parseInt(this.forget.exp_time) * 1000), new Date())
                    if(date_computed === true){
                        this.forget.otp_exp_label = 'Your Confirmation key has expired';
                        this.forget.otp_timer = ''
                        clearInterval(this.forget.intervalVar);
                    }
                }, 1000);
            }, 
            forgetSubmit(evt) {
                this.forget.onLoad = true;
                this.forget.errCredentials = false
                this.forget.errCredentials = ""
                this.forget.expiredPw = false
                evt.preventDefault();
                let data = new FormData()
                let that = this;
                if(!this.validateEmail(this.forget.email)){
                    this.forget.errCredentials = true
                    this.forget.errCredentialsMsg = 'Please enter valid email'
                    this.forget.onLoad = false;
                    return
                }
                if(this.forget.username == ''){
                    this.forget.errCredentials = true
                    this.forget.errCredentialsMsg = 'Please enter your username'
                    this.forget.onLoad = false;
                    return
                }
                if(that.forget.toPassKey ){
                    if(this.forget.otp.trim() == ''){
                        this.forget.matchOtp = true
                        this.forget.errorOtp = 'Please Fill in the confirmation key'
                        this.forget.onLoad = false;
                        return
                    }
                    this.forget.errorOtp = ''
                    data = {}
                    let url = api+'forget_pass_otp?username='+this.forget.username.trim()+'&otp='+this.forget.otp.trim();
					// this.FORGET_PASS_OTP({ data })
                    this.API_GET({url,data}).then(r => {
                        if(!r.valid_otp){
                            if(this.forget.exp_time < (Math.floor(new Date().getTime() / 1000))){
                                this.forget.requestOtp = true
                                this.forget.expiredPw = true
                                return
                            }
                            this.forget.matchOtp = true
                            this.forget.errorOtp = 'Wrong Confirmation Key'
                        }else{
                            this.forget.matchOtp = false
                            this.forget.toNewPass = true
                            this.forget.toPassKey = false
                        }
                        that.forget.onLoad = false
                    }).catch(() => {
                        
                        this.forget.onLoad = false;

                    })
                }else if(this.forget.toNewPass){
                    if(this.forget.newPass != this.forget.confirmPass){
                        this.forget.matchPw = true
                        this.forget.pw_err_msg = 'Passwords do not match.'
                        this.forget.onLoad = false;
                    }else if((this.forget.newPass == '') || (this.forget.confirmPass == '')){
                        this.forget.matchPw = true
                        this.forget.pw_err_msg = 'Please enter new password'
                        this.forget.onLoad = false;
                    }else{
                        clearInterval(this.forget.intervalVar)
                        this.forget.matchPw = false
                        that.forget.onLoad = true;
                        data.append('username', this.forget.username.trim())
                        data.append('otp', this.forget.otp.trim())
                        data.append('password', this.forget.newPass.trim())
                        // data.append('_method', 'PATCH')

                        let url = api+'change_pass';

                        this.API_PATCH({ url, data }).then(r => {
                            if(r.changed){
                                this.$bvModal.hide('modalForget')
                                this.$bvModal.show('modalSuccess')
                                setTimeout(function(){
                                    that.$bvModal.hide('modalSuccess')
                                }, 3000);
                            }
                            that.forget.onLoad = false;  
                        }).catch(err => {
                            // console.log(err)
                            if(!err.changed){
                                this.forget.expiredPw = true
                                this.forget.requestOtp = true
                            }
                            this.forget.onLoad = false;
                            
                        })
                    }
                }else{
                    data = {}
                    let url = api+'forget_pass?username='+this.forget.username.trim()+'&email='+this.forget.email.trim();
                    // this.FORGET_PASS({ data })
                    this.API_GET({url,data}).then(r => {
                        that.forget.onLoad = false; 
                        that.forget.toPassKey = true;
                        if(r.existing){
                            this.forget.existingOtp = true
                        }
                        this.forget.onLoad = false;
                        this.forget.exp_time = r.exp_time;
                        this.setIntervalOtp();

                    }).catch(err => {
                        if(err.status == 401){
                            this.forget.errCredentials = true
                            this.forget.errCredentialsMsg = err.data.message
                        }else{
                            this.forget.errCredentials = false
                            this.forget.errCredentials = ""
                        }
                        this.forget.onLoad = false;
                        
                    })
                }
            },
            start_otp_count(exp_date, this_time){
                var delta = ''
                var days = ''
                var hours = ''
                var minutes = ''
                var seconds = ''

                delta = Math.abs(exp_date - this_time) / 1000;

                // calculate (and subtract) whole days
                days = Math.floor(delta / 86400);
                delta -= days * 86400;

                // calculate (and subtract) whole hours
                hours = Math.floor(delta / 3600) % 24;
                delta -= hours * 3600;

                // calculate (and subtract) whole minutes
                minutes = Math.floor(delta / 60) % 60;
                delta -= minutes * 60;

                // what's left is seconds
                seconds = Math.floor(delta % 60);

                this.forget.otp_exp_label = 'Password request will expire in:\xa0\xa0';
                this.forget.otp_timer = minutes +" : "+ ('0'+seconds).slice(-2)

                if((minutes == 0) && (seconds == 0)){
                    return true
                }
                // return ((minutes == 0) && (seconds == 0)) ? true : false

            },
            validateEmail(email) {
                // var re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,24}))$/;
                var re = /\S+@\S+\.\S+/;
                return re.test(String(email).toLowerCase());
            },
            OpenForgetPass(){
                this.forget.username = ''
                this.forget.email = ''
                this.forget.onLoad = false
                this.forget.toPassKey = false
                this.forget.emailKey = ''
                this.forget.toNewPass = false
                this.forget.otp = ''
                this.forget.errCredentials = false
                this.forget.errCredentials = ""
                this.forget.existingOtp = false
                this.forget.matchOtp = false
                this.forget.newPass = ''
                this.forget.confirmPass = ''
                this.forget.pw_err_msg = ''
                this.forget.otp_timer = ''
                this.forget.exp_time = ''
                this.forget.otp_exp_label = ''
                this.forget.errorOtp = ''
                this.forget.expiredPw = false
                this.forget.requestOtp = false
                this.$bvModal.show('modalForget')
            },
            validate_otp(evt){
                evt = (evt) || window.event
                var charCode = (evt.which) ? evt.which : evt.keyCode

                if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                    evt.preventDefault()
                }

                if (this.forget.otp.length === 5) {
                    evt.preventDefault()
                }
            },
            onReset() {

            },
            checkCompatibleBrowsers(){
                let currentBrowser = navigator.userAgent;
                this.restrictedBrowser = currentBrowser.includes("Mobile") || currentBrowser.includes("Trident");
                if(this.restrictedBrowser){
                    window.location = "#/404BrowserNotCompatible";
                }
            },
        },
        mounted() {
            this.checkCompatibleBrowsers();
        }
    }
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="scss" scoped>
    #bnc{
        height: 100%;
    }
    #login_container, #modalForget, #modalSuccess {
        position: fixed;
        left: 50%;
        top: 50%;
        width: 50%;
        transform: translate(-50%, -50%);
        background: #fff;
        /*padding: 3rem 2rem 2rem;*/
        box-shadow: 0 0 7px #ccc;
        border-radius: 5pt;
        overflow: hidden;

        .left_side {
            position: absolute;
            width: 50%;
            display: inline-table;
            background: url(../assets/image/bg_new.png) left top;
            background-size: cover;
            height: 100%;
            /*opacity: .9;*/
        }

        .right_side {
            position: relative;
            width: 50%;
            display: inline-table;
            padding: 2rem 2rem 2rem;
            float: right;
        }

        .title {
            color: #0D708F;
            font-weight: 700;
            font-size: 1rem;
            text-align: center;
            padding-bottom: 1rem;
        }

        .logo_icon {
            margin: auto;
            display: block;
            width: 45%;
            padding-bottom: 2rem;
        }

        .form_holder {
            label {
                color: #0D708F;
                font-size: 12px;
            }

            .general_input {
                border: none;
                border-bottom: solid 2px #ececec;
                box-shadow: none !important;
                border-radius: 0;
                transition: all ease-in-out 0.3s;

                &:focus, &:active {
                    border-color: #0D708F;
                }
            }

            .forgot_pass {
                font-size: 12px;
                color: #0D708F;
                text-align: center;
                cursor: pointer;
            }

            .login_btn {
                width: 70%;
                padding: 10px;
                text-align: center;
                margin: 3rem auto .5rem;
                display: block;
                background: #fff;
                color: #0D708F;
                border-color: #0D708F;
                border-radius: 0;
                transition: all ease-in-out .3s;

                &:hover {
                    background: #0D708F;
                    color: #fff;
                }
            }
        }

        .mobile_login {
            display: none;
        }

        .error-login{
            text-align:center; 
            color: red; 
            margin-top: -10px;
            padding: 0; 
            font-size: 12px; 
            position: relative
        }
    }

    @media (min-width: 768px) and (max-width: 991px) {
        #login_container {
            width: 60%;

            .left_side {
                width: 30%;
            }

            .right_side {
                width: 70%;
            }

            .login_btn {
                padding: .3rem !important;
            }
        }
    }

    @media (max-width: 767px) {
        #login_container {
            width: 80%;
            min-height: 60%;

            .left_side {
                display: none;
            }

            .right_side {
                display: none;
            }

            .mobile_login {
                display: block;
                /*position: relative;*/

                label, .forgot_pass {
                    font-size: 1rem;
                }

                .logo_icon {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    padding: 0;
                }

                .upper_container {
                    background: url(../assets/image/bg_new.png) no-repeat center center;
                    background-size: cover;
                    width: 100%;
                    height: 20vh;
                    position: relative;
                }

                .lower_container {
                    width: 100%;
                    padding: 1rem;
                }

                .login_btn {
                    /*padding: .3rem!important;*/
                    margin: 2rem auto 1rem;
                }
            }
        }
    }
</style>

<style>
    div#modalForget .modal-dialog, div#modalSuccess .modal-dialog {
        margin-top: 9rem;
    }

    @media (min-width: 400px) {
        div#modalForget .modal-dialog, div#modalSuccess .modal-dialog {
            margin-top: 8rem;
        }
    }

    @media (min-width:768px) and (max-width:1024px){
        div#modalForget .modal-dialog, div#modalSuccess .modal-dialog {
            margin-top: 20rem;
        }
    }

    @media (min-width:320px) and (max-width:375px) {
        div#modalForget .modal-dialog, div#modalSuccess .modal-dialog {
            margin-top: 7rem;
        }
    }
    
</style>