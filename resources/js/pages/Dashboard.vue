<script setup>
import { onMounted, ref } from "vue";
import PlanCard from "../componentes/PlanCard.vue";
import Layout from "../componentes/Layout.vue"; // Importamos el molde visual

const token = ref(localStorage.getItem('token'))
const planes = ref([])
const planAdquirido = ref(null)
const usuarioLoggeado = ref(null)
const mostrarTodosPlanes = ref(false)

const cantidadNegocios = ref(0)

onMounted(async () => {
    cargarDashboard()
})

const cargarDashboard = async()  => {
    const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
    if(planGuardado){
        planes.value = [planGuardado]
        mostrarTodosPlanes.value = false
    }else {
        const response = await fetch('/api/planes')
        planes.value = await response.json()
        mostrarTodosPlanes.value = true
    }

    try{
        const response = await fetch('/api/dashboard',{
            method: 'GET',
            headers: {
                'Content-Type' : 'application/json',
                'Authorization': `Bearer ${token.value}`
            }
        })
        const data = await response.json()
        if(data.valid){
            usuarioLoggeado.value = data.usuarioLoggeado
            planAdquirido.value = data.planAdquirido
            // Recibimos la cantidad desde Laravel
            cantidadNegocios.value = data.cantidadNegocios
        }
    }catch (error) {
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    }
}

const eventMostrarTodosPlanes = async () => {
    const response = await fetch('/api/planes')
    planes.value = await response.json()
    mostrarTodosPlanes.value = true
}
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <div v-if="planAdquirido">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-dark fs-3">Vista General</h5>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-primary">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold mb-2">Citas Hoy</h6>
                            <h3 class="fw-bold mb-0">...</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-warning">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold mb-2">Negocios Registrados</h6>
                            <h3 class="fw-bold mb-0">{{ cantidadNegocios }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-success">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold mb-2">Créditos WhatsApp</h6>
                            <h3 class="fw-bold mb-0">{{ planAdquirido?.whatsapp_creditos_iniciales || '0' }}</h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-info">
                        <div class="card-body">
                            <h6 class="text-muted fw-bold mb-2">Plan Adquirido</h6>
                            <h3 class="fw-bold mb-0 text-dark">{{ planAdquirido?.nombre || 'Cargando...' }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="!planAdquirido">
            <div class="row mb-5">
                <div class="col-12 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Tu Configuración de Plan</h5>
                    <button v-if="!mostrarTodosPlanes" @click="eventMostrarTodosPlanes" class="btn btn-link fw-bold fs-6 p-0">Ver todos los planes</button>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-4 bg-transparent">
                        <div class="row g-4">
                            <PlanCard
                                v-for="plan in planes"
                                :key="plan.id"
                                :plan="plan"
                                @planRegistrado="cargarDashboard"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </Layout>
</template>
