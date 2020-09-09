<template>
    <div class="white_container">
        <b-row>
            <b-col md="12" class="fourOfour"><strong>404</strong></b-col>
            <b-col md="12" class="bnc"><p>This site is not compatible with this browser.</p></b-col>
        </b-row>
    </div>
</template>
<script>

export default {
    name: 'BrowserNotCompatible',
    data(){
        return{

        }
	},
    methods:{

    },
    mounted() {

	}
}
</script>
<style lang="scss">
.white_container {
    height: 100%;
    .fourOfour {
        padding-top: 10%;
        text-align: center;
        color: #acdeee;
        font-size: 80px;
        letter-spacing: 10px;
        -webkit-text-stroke-width: 3px;
        -webkit-text-stroke-color: #000;
    }
    .bnc {
        text-align: center;
        font-size: 20px;
        p{
            background-color: #ccc;
            padding: 5px;
            padding-bottom: 5px;
        }
    }
}
</style>