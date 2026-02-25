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

    <div class="bg-light min-vh-100 py-5">
        <div class="container py-4">

            <div class="text-center mb-5">

                <div class="bg-white shadow-sm rounded-circle d-inline-flex justify-content-center align-items-center mb-4" style="width: 80px; height: 80px;">
                    <span class="fs-1">💎</span>
                </div>

                <h1 class="text-primary fw-bold display-5 mb-3">
                    Planes disponibles
                </h1>

                <p class="text-muted fs-5">
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
    </div>

</template>
