<template>

    <router-view/>

    <Toast ref="toast"/>

    <Footer />

</template>

<script>

import Toast from "./componentes/Toast.vue";
import Footer from "./componentes/Footer.vue"; // 1. Se importa el archivo

export default {

    components:{
        Toast,
        Footer // 2. Se registra como componente válido
    },
    mounted() {
        window.$toast = this.$refs.toast
    }

}

</script>
