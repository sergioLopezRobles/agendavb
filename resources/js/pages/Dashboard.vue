<script setup>
import { onMounted, ref } from "vue";
import { useRouter } from "vue-router"; // <-- IMPORTAMOS EL ROUTER
import PlanCard from "../componentes/PlanCard.vue";
import Layout from "../componentes/Layout.vue";

const router = useRouter(); // <-- INICIALIZAMOS EL ROUTER
const token = ref(localStorage.getItem('token'))
const planes = ref([])
const planAdquirido = ref(null)
const usuarioLoggeado = ref(null)
const mostrarTodosPlanes = ref(false)
const cantidadNegocios = ref(0)
const citasHoy = ref(0)

const userRol = ref(parseInt(localStorage.getItem('userRol')) || 2)

// Variables para Admin
const statsAdmin = ref({
    total_usuarios: 0,
    total_negocios: 0,
    suscripciones_activas: 0,
    tickets_pendientes: 0,
    ingresos_mrr: 0,
    total_citas: 0,
    logs: []
})

onMounted(async () => {
    cargarDashboard()
})

const cargarDashboard = async()  => {
    if (userRol.value === 2) {
        const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
        if(planGuardado){
            planes.value = [planGuardado]
            mostrarTodosPlanes.value = false
        } else {
            const response = await fetch('/api/planes')
            planes.value = await response.json()
            mostrarTodosPlanes.value = true
        }
    }

    try {
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

            if (data.usuarioLoggeado.id_rol === 1) {
                statsAdmin.value = data.statsAdmin
            } else {
                planAdquirido.value = data.planAdquirido
                cantidadNegocios.value = data.cantidadNegocios
                citasHoy.value = data.citasHoy || 0
            }
        }
    } catch (error) {
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    }
}

const eventMostrarTodosPlanes = async () => {
    const response = await fetch('/api/planes')
    planes.value = await response.json()
    mostrarTodosPlanes.value = true
}

// Formateador de moneda para el MRR
const formatoMoneda = (cantidad) => {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(cantidad);
}
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <div v-if="userRol === 1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-dark fs-3">Métricas Financieras y de Uso</h5>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-success bg-success bg-opacity-10">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Ingresos (MRR)</h6>
                            <h4 class="fw-bold mb-0 text-success">{{ formatoMoneda(statsAdmin.ingresos_mrr) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-primary">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Citas Totales</h6>
                            <h4 class="fw-bold mb-0">{{ statsAdmin.total_citas }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-info">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Usuarios registrados</h6>
                            <h4 class="fw-bold mb-0">{{ statsAdmin.total_usuarios }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-secondary">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Suscripciones</h6>
                            <h4 class="fw-bold mb-0">{{ statsAdmin.suscripciones_activas }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-dark">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Negocios</h6>
                            <h4 class="fw-bold mb-0">{{ statsAdmin.total_negocios }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 col-lg-2">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-warning">
                        <div class="card-body p-3 text-center">
                            <h6 class="text-muted fw-bold mb-1" style="font-size: 0.8rem;">Tickets Abiertos</h6>
                            <h4 class="fw-bold mb-0 text-warning">{{ statsAdmin.tickets_pendientes }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4 overflow-hidden mb-5">
                <div class="card-header bg-white border-bottom pt-4 pb-3 px-4">
                    <h6 class="fw-bold mb-0 text-dark">📋 Auditorías Reciente</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                            <tr>
                                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Fecha y Hora</th>
                                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Usuario</th>
                                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Acción Realizada</th>
                                <th class="py-3 px-4 text-center text-secondary fw-semibold border-bottom-0">Tipo</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="log in statsAdmin.logs" :key="log.id">
                                <td class="px-4 py-3 text-muted small">{{ log.fecha }}</td>
                                <td class="px-4 py-3 fw-bold text-dark">{{ log.usuario_nombre }}</td>
                                <td class="px-4 py-3">{{ log.cambios?.mensaje || 'Acción desconocida' }}</td>
                                <td class="px-4 py-3 text-center">
                                        <span class="badge rounded-pill"
                                              :class="{
                                                'bg-success': log.tipo_mensaje === 1,
                                                'bg-warning text-dark': log.tipo_mensaje === 2,
                                                'bg-danger': log.tipo_mensaje === 3,
                                                'bg-secondary': log.tipo_mensaje === 0
                                            }">
                                            {{ log.tipo_mensaje === 1 ? 'Creación' : (log.tipo_mensaje === 2 ? 'Edición' : (log.tipo_mensaje === 3 ? 'Eliminación' : 'Sistema')) }}
                                        </span>
                                </td>
                            </tr>
                            <tr v-if="statsAdmin.logs.length === 0">
                                <td colspan="4" class="text-center py-5 text-muted">No hay movimientos recientes registrados.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="userRol === 2 && planAdquirido">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0 text-dark fs-3">Vista General</h5>
            </div>

            <div class="row g-4 mb-5">

                <div class="col-md-6 col-lg-3">
                    <div @click="router.push('/citas-negocios')" class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-primary interactive-card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Citas Hoy</h6>
                                <h3 class="fw-bold mb-0 text-primary">{{ citasHoy }}</h3>
                            </div>
                            <span class="fs-4 text-primary opacity-50">📆</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div @click="router.push('/negocios')" class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-warning interactive-card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Mis Negocios</h6>
                                <h3 class="fw-bold mb-0 text-warning">{{ cantidadNegocios }}</h3>
                            </div>
                            <span class="fs-4 text-warning opacity-50">🏪</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-info interactive-card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Créditos WhatsApp</h6>
                                <h3 class="fw-bold mb-0 text-success">{{ planAdquirido?.whatsapp_creditos_iniciales || '0' }}</h3>
                            </div>
                            <span class="fs-4 text-success opacity-50">💬</span>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div @click="router.push('/negocios')" class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-info interactive-card">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted fw-bold mb-2">Mi Plan Actual</h6>
                                <h4 class="fw-bold mb-0 text-info text-truncate" style="max-width: 150px;">{{ planAdquirido?.nombre || 'Cargando...' }}</h4>
                            </div>
                            <span class="fs-4 text-info opacity-50">⭐</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div v-if="userRol === 2 && !planAdquirido">
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

<style scoped>
/* Transición suave para las tarjetas interactivas */
.interactive-card {
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.interactive-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}

/* Tarjeta estática para diferenciar que no tiene clic */
.static-card {
    cursor: default;
}
</style>
