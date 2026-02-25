<script setup>

import { useRouter } from 'vue-router'
import {onMounted, ref} from "vue";
import PlanCard from "../componentes/PlanCard.vue";

const router = useRouter()

const planes = ref([])

onMounted(async () => {
    const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
    if(planGuardado){
        console.log(planGuardado)
        planes.value = [planGuardado]
    }else {
        const response = await fetch('/api/planes')
        planes.value = await response.json()
        console.log(planes)
    }
})

const logout = async () => {
    const token = localStorage.getItem('token')

    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
    } catch (error) {
        console.log(error)
    }

    // borrar token del navegador
    localStorage.removeItem('token')

    // borrar plan seleccionado si quieres
    localStorage.removeItem('planSeleccionado')

    // redirigir al inicio
    router.push('/')

}

</script>

<template>
    <div class="bg-light min-vh-100">
        <nav class="navbar navbar-expand-lg bg-primary shadow-sm px-4 py-3">
            <div class="container-fluid d-flex justify-content-between align-items-center">

                <span class="navbar-brand text-white fw-bold fs-4 mb-0">
                    🚀 Dashboard
                </span>

                <button
                    @click="logout"
                    class="btn btn-danger fw-semibold px-4 rounded-pill"
                >
                    Cerrar sesión
                </button>

            </div>
        </nav>


        <div class="container mt-5 pt-4">
            <div class="row justify-content-center">
                <div>

                    <div class="card shadow-lg border-0 rounded-4 text-center mb-4">
                        <div class="card-body p-5">

                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center mb-4" style="width: 80px; height: 80px;">
                                <span class="fs-1">👋</span>
                            </div>

                            <h2 class="text-primary fw-bold mb-3">Bienvenido al Dashboard</h2>

                            <p class="text-muted fs-5 mb-0">
                                Ya iniciaste sesión correctamente.
                            </p>

                        </div>
                    </div>

                    <div v-if="plan" class="card shadow-sm border-0 rounded-4">
                        <div class="container py-4">

                            <div class="row g-4 justify-content-center">

                                <PlanCard
                                    v-for="plan in planes"
                                    :key="plan.id"
                                    :plan="plan"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <div class="container py-4">

                            <div class="row g-4 justify-content-center">

                                <PlanCard
                                    v-for="plan in planes"
                                    :key="plan.id"
                                    :plan="plan"
                                />

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
