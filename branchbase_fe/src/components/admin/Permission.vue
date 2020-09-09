<template>
    <div id="permission" style="padding: 0px; background-color: white;">
        <div class="user_information">
            <div class="user_item">
                Employee ID / Teacher ID <br/>
                <span>112233</span>
            </div>
            <div class="user_item">
                Name <br/>
                <span>John Mico Bautista</span>
            </div>
            <!-- <div class="user_item">
                Position <br/>
                <span>System Application Developer </span>
            </div> -->
        </div>

        <!-- <div class="note" style="padding:15px 15px 0;color:#666;font-size:.8rem;">Note: Enable / Disable Menu for this User.</div> -->

        <div class="permission_lists">
            <div class="permission_item" v-for="(item,key) in permission_list" :key="key">
                <b-form-checkbox name="check-button" v-model="item.active" switch size="default">
                    {{item.name}}
                </b-form-checkbox>
            </div>

            <div class="permission_controls">
                <div class="mid_circle"></div>
                <b-button size="sm" class="save_perm">Save</b-button>
                <b-button size="sm" class="reset_perm" @click="reset_lists">Reset</b-button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    data() {
        return {
            permission_list:[
                {
                    id: 1,
                    name: 'Home',
                    active: false
                },
                {
                    id: 2,
                    name: 'Earning / Logs',
                    active: false
                },
                {
                    id: 3,
                    name: 'Classes',
                    active: false
                },
                {
                    id: 4,
                    name: 'Monthly Data',
                    active: false
                },
                {
                    id: 5,
                    name: 'Payslip',
                    active: false
                },
                {
                    id: 6,
                    name: 'Payroll',
                    active: false
                },
            ]
            }
    },
    methods:{
        reset_lists(){
            this.permission_list.forEach(e => {
                e.active = false
            });
        }
    }
}
</script>

<style lang="scss">
    #permission{
        .user_information{
            padding: 15px;
            display: inline-flex;
            width: 100%;
            border-bottom: solid 2px #0D708F;

            .user_item{
                width: 100%;
                font-weight: 400;
                color: #333;
                font-size: .8rem;

                span{
                    font-weight: 700;
                    font-size: 1rem;
                    color: #444;
                    margin-top: 5px;
                    display: inline-block;
                }
            }
        }

        .permission_lists{
            padding: 25px 15px;
            width: 100%;
            display: inline-table;

            .permission_item{
                width: 33.33%;
                display: inline-table;
                margin: 5px 0;
                box-sizing: border-box; 

                label{
                    cursor: pointer;
                }
            }

            .permission_controls{
                margin-top: 20px;
                display: inline-block;
                float: right;
                position: relative;
            }

            .save_perm{
                width: 80px;
                background: #0d708f;
                color: #fff;
                border-radius: 0;
                border: solid 1px;
                margin-right: .5px;
                cursor: pointer;
                font-size: 12px;

                &:hover{
                    background: #0d708f;
                    color: #fff;
                    border-color: #0d708f;
                }
            }

            .reset_perm{
                width: 80px;
                background: #ae3939;
                color: #fff;
                border-radius: 0;
                border: solid 1px;
                margin-left: .5px;
                cursor: pointer;
                font-size: 12px;

                &:hover{
                    background: #ae3939;
                    color: #fff;
                    border-color: #ae3939;
                }
            }

            .mid_circle{
                position: absolute;
                padding: 7px;
                background: #fff;
                border-radius: 50px;
                top: 50%;
                left: 50%;
                transform: translate(-50%,-50%);
            }
        }
    }
</style>