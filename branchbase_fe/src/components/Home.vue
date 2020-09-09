<template>
    <div id="default_content">
        <DHeader />
        <div class="body_container" style="height: 100% !important">
            <DSidebar />
            <div class="main_content_holder" :class="toggle_status == 2 ? 'toggled' : '' " style="height: 90vh !important; width: 100%;">
                <router-view></router-view>
            </div>
        </div>

    </div>
</template>

<script>
import DHeader from '@/components/layout/Header.vue'
import DSidebar from '@/components/layout/Sidebar.vue'
import { mapGetters } from 'vuex'
export default {
    name: 'HomePage',
    data(){
        return{
            mcheight: 0
        }
    },
    components:{
        DHeader,
        DSidebar
    },
    mounted(){
        this.set_height()
    },
    computed: {
        ...mapGetters({
            toggle_status: 'SIDEBAR_TOGGLE',
        }),
    },
    methods:{
        set_height(){
            var checker = setInterval(() =>{
                if(document.querySelector('.navbar')){
                    clearInterval(checker);
                    var windh = window.innerHeight;
                    var headh_arr = document.querySelectorAll('.navbar');
                    var headh = 0;
                    for (var i = 0; i < headh_arr.length; i++) {
                        if(!this.is_hidden(headh_arr[i])){
                            headh = headh_arr[i].clientHeight;
                        }
                    }
                    this.mcheight = windh - (headh);
                }
            },10)
        },
        is_hidden(el) {
            var style = window.getComputedStyle(el);
            return ((style.display === 'none') || (style.visibility === 'hidden'))
        },
    }
}
</script>

<!-- Add "scoped" attribute to limit CSS to this component only -->
<style lang="scss">
::-webkit-scrollbar {
  width: 5px;
}
::-webkit-scrollbar-track {
  background: #f1f1f1;
}
::-webkit-scrollbar-thumb {
  background: #aeaeae;
}
::-webkit-scrollbar-thumb:hover {
  background: #888;
}

#default_content{
    .body_container{
        display: inline-flex;
        width: 100%;
        overflow: hidden;
        position: relative;
    }
    .main_content_holder{
        overflow-x: hidden;
        overflow-y: auto;
        width: 80%;
        display: inline-block;
        // transition: all ease-in-out .3s;
        padding: 1rem;

        &.toggled{
            width: 95%;
        }
    }

    .white_container{
        background: #fff;
        /*border-radius: 10pt;*/
        box-shadow: 0 0 5px #d3d3d3;
        padding: 1rem;
    }

    .utalk_table_layout{
        width: 100%;
        table-layout: fixed;

        thead{
            /*border-top: solid 1px #f5f5f5;*/
            border-bottom: solid 2px #0D708F;
            font-size: 13px;
            th{
                padding: .8rem 0;
            }
        }

        tbody{
            tr{
                td{
                    padding: .8rem 0;
                    font-size: 13px;
                }

                &:nth-child(even){
                    background: #f5f5f5;
                }
            }
        }
    }

    .utalk_table_search{
        margin-bottom: 1rem;

        .search_toggle{
            text-align: right;

            button{
                background: transparent;
                outline: none;
                box-shadow: none;
                border-radius: 5px;
                border:solid 1px #d3d3d3;
                color: #0d708f;
                font-size: 12px;
                transition: all ease-in-out 0.3s;

                &:hover{
                    background: #0d708f;
                    color: #fff;
                    border-color: #0d708f;
                }

                &.active{
                    background: #0d708f;
                    color: #fff;
                    border-color: #0d708f;

                    svg{
                        transform: rotate(180deg);
                    }
                }
                &.disabled:hover{
                    background: white !important;
                }
            }
        }

        #search_filter_holder{
            .card{
                border: none;
                padding-bottom: 2rem;

                .card-body{
                    padding: 0;
                }
            }

            label{
                font-size: 12px;
                margin-bottom: 0;
                /*font-weight: 700;
                color: #0d708f;*/
            }

            input,select{
                outline: none;
                box-shadow: none;
                border: none;
                border-bottom: solid 1px #d3d3d3;
                border-radius: 0;
                font-size: 12px;
                padding: 0.375rem 0;
                transition: all ease-in-out 0.3s;

                &:focus,&:active{
                    border-color: #0d708f;
                }
            }
        }
    }
}
#search_filter_holder button[disabled]:hover,
#search_filter_holder button:disabled,
button:disabled,
button[disabled]{
    background:#bdbdbd !important;
    color: #585858 !important;
    border:1px solid grey !important;
    cursor: not-allowed !important;
}
</style>
