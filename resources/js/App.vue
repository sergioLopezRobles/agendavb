<template>

    <router-view/>

    <Toast ref="toast"/>

</template>

<script>

import Toast from "./componentes/Toast.vue";
export default {

    components:{
        Toast
    },
    mounted() {
        window.$toast = this.$refs.toast
    }

}

</script>
