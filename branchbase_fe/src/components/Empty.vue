<template>
    <div id="empty_page">
        <div class="white_container">

        </div>
    </div>
</template>

<script>

export default {
    name: 'Empty',
    data(){
        return{
            mcheight: 0
        }
    },
    methods:{

    }
}
</script>

<style lang="scss">
.white_container{
    background: #fff;
    /*border-radius: 10pt;*/
    box-shadow: 0 0 5px #d3d3d3;
    padding: 1rem;
}
</style>
