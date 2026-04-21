<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import Layout from '../componentes/Layout.vue';

// IMPORTACIONES DE FULLCALENDAR
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

// VARIABLES GLOBALES
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

// ALMACENAR NEGOCIOS
const negocios = ref([]);
const estadosCita = ref([]);

// ... tus variables globales existentes ...
const citasNegocio = ref([]);
const mostrarModalAgenda = ref(false);
const negocioSeleccionado = ref(null);

// PARA EL DETALLE DE CITA
const mostrarModalDetalle = ref(false);
const citaDetalle = ref(null);

// AL INICIAR LA PANTALLA
onMounted(() => {
    cargarDatosMenu();
    cargarDatosCitas();
});

// MANTIENE EL LAYOUT FUNCIONANDO
const cargarDatosMenu = async () => {
    try {
        const response = await fetch('/api/dashboard', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();
        if(data.valid) {
            usuarioLoggeado.value = data.usuarioLoggeado;
            planAdquirido.value = data.planAdquirido;
        }
    } catch (error) {
        console.error("ERROR AL CARGAR DATOS DEL MENU", error);
    }
};

// TRAE LOS TICKETS, NEGOCIOS Y CATALOGOS DESDE LARAVEL
const cargarDatosCitas = async () => {
    try {
        const response = await fetch('/api/citas-negocios', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();

        if(data.valid) {
            negocios.value = data.negocios;
            estadosCita.value = data.estados;

            console.log(negocios);
        }
    } catch (error) {
        window.$toast.show('Error al cargar la información de negocios', 'danger', 4000);
    }
};

// MAPEO DE EVENTOS PARA FULLCALENDAR
const eventosCalendario = computed(() => {
    return citasNegocio.value.map(cita => ({
        id: cita.id,
        title: cita.cliente_nombre,
        start: cita.fecha + 'T' + cita.hora, // Formato ISO: YYYY-MM-DDTHH:mm:ss
        extendedProps: { ...cita }, // Guardamos toda la info para el detalle
        color: '#0d6efd'
    }))
});

// CONFIGURACIÓN DEL CALENDARIO
const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: 'es',
    // ESTO ES LO QUE FALTA PARA EL FORMATO
    eventTimeFormat: {
        hour: 'numeric',
        minute: '2-digit',
        meridiem: 'short', // Esto pone el 'am' o 'pm'
        hour12: true
    },
    events: eventosCalendario,
    eventClick: (info) => {
        // Al hacer clic en un evento (cita existente)
        citaDetalle.value = { ...info.event.extendedProps };
        mostrarModalDetalle.value = true;
    },
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: ''
    }
});

// FUNCIÓN PARA ABRIR LA AGENDA
/*
const abrirAgenda = async (negocio) => {
    negocioSeleccionado.value = negocio;
    try {
        const response = await fetch(`/api/citas-negocios/${negocio.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) {
            citasNegocio.value = data.citas;
            mostrarModalAgenda.value = true;
            // Forzamos el renderizado del calendario después de que el modal sea visible
            await nextTick();
        }
    } catch (error) {
        window.$toast.show('Error al cargar la agenda', 'danger', 4000);
    }
};*/
const abrirAgenda = async (negocio) => {
    negocioSeleccionado.value = negocio;
    try {
        const response = await fetch(`/api/citas-negocios/${negocio.id}`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) {
            citasNegocio.value = data.citas;
            estadosCita.value = data.estados; // 🔥 ESTA ES LA SOLUCIÓN AL SELECT VACÍO
            mostrarModalAgenda.value = true;
            // Forzamos el renderizado del calendario después de que el modal sea visible
            await nextTick();
        }
    } catch (error) {
        window.$toast.show('Error al cargar la agenda', 'danger', 4000);
    }
};

const cerrarModalAgenda = () => {
    mostrarModalAgenda.value = false;
    citasNegocio.value = [];
};

const guardarEstado = async () => {
    try {
        const response = await fetch('/api/actualizar-estado-cita', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                id_cita: citaDetalle.value.id,
                id_estado: citaDetalle.value.id_estado
            })
        });
        const data = await response.json();
        if(data.valid) {
            window.$toast.show('Estado actualizado correctamente', 'success', 3000);
            mostrarModalDetalle.value = false;
            // 🔥 CAMBIAMOS ESTO PARA QUE SE REFRESQUE EL CALENDARIO CORRECTAMENTE
            abrirAgenda(negocioSeleccionado.value);
        }
    } catch (error) {
        console.error("Error:", error);
        window.$toast.show('Error al actualizar el estado', 'danger', 3000);
    }
};
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <tbody>
                    <tr v-for="negocio in negocios" :key="negocio.id">
                        <td class="px-4 py-3 fw-bold text-dark">
                            <span class="fs-5 me-2">🏪</span>{{ negocio.nombre }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button @click="abrirAgenda(negocio)" class="btn btn-primary rounded-pill px-3 btn-sm fw-semibold">
                                📅 Ver Agenda
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModalAgenda" class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-0 px-4 pt-4">
                        <h5 class="fw-bold">Agenda de {{ negocioSeleccionado?.nombre }}</h5>
                        <button type="button" class="btn-close" @click="cerrarModalAgenda"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="calendar-container">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalDetalle" class="modal fade show d-block" style="background: rgba(0,0,0,0.4); z-index: 1060;">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0">
                        <h6 class="fw-bold mb-0">Detalles de la Cita</h6>
                        <button type="button" class="btn-close" @click="mostrarModalDetalle = false"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="p-3 bg-light rounded-3">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 small text-muted">Cliente:</p>
                                    <p class="fw-bold mb-2">{{ citaDetalle.cliente_nombre }}</p>
                                </div>
                                <div>
                                    <p class="mb-1 small text-muted">Teléfono:</p>
                                    <p class="fw-bold mb-2">{{ citaDetalle.cliente_telefono }}</p>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between">
                                <div>
                                    <p class="mb-1 small text-muted">Hora:</p>
                                    <p class="fw-bold">{{ citaDetalle.hora }}</p>
                                </div>
                                <div>
                                    <p class="mb-1 small text-muted">Fecha:</p>
                                    <p class="fw-bold">{{ citaDetalle.fecha }}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="small text-muted">Estado de la cita:</label>
                                <select class="form-select fw-bold" v-model="citaDetalle.id_estado">
                                    <option v-for="estado in estadosCita" :key="estado.id" :value="estado.id">
                                        {{ estado.titulo }}
                                    </option>
                                </select>
                            </div>
                            <div class="modal-footer mt-3 d-flex justify-content-between">
                                <!-- <button type="button" class="btn btn-secondary" @click="mostrarModalDetalle = false">Cerrar</button>-->
                                <button type="button" class="btn btn-primary" @click="guardarEstado">Actualizar Estado</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </Layout>
</template>

<style scoped>
.calendar-container {
    background: white;
    padding: 10px;
    border-radius: 12px;
}

/* Ajuste de altura para que el calendario no se desborde del modal */
:deep(.fc) {
    max-height: 70vh;
}
</style>
