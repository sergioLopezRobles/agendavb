<script setup>

import { ref, onMounted} from 'vue'

import PlanCard from './PlanCard.vue'

const planes = ref([])

onMounted(async () => {
    const response = await fetch('/api/planes')
    planes.value = await response.json()
})

</script>


<template>

    <div class="container py-5">

    <div class="text-center mb-5">

        <h1 class="fw-bold">
            Planes disponibles
        </h1>

        <p class="text-muted">
            Selecciona el plan ideal para tu negocio
        </p>

    </div>

    <div class="row g-4 justify-content-center">

        <PlanCard
            v-for="plan in planes"
            :key="plan.id"
            :plan="plan"
        />

    </div>

</div>

</template>
