
<template>
    <div class="no-data" v-html="title">
    </div>
</template>

<script>
export default {
    props:{
        title: { tpye: String, default: "No Data Found" }
    }
}
</script>

<style scoped>
    .no-data{
        font-size: 14px;
        font-style: italic;
    }
</style>