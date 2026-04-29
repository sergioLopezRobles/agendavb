<script setup>
import { ref, onMounted, computed, nextTick } from 'vue';
import Layout from '../componentes/Layout.vue';
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);
const negocios = ref([]);
const citasNegocio = ref([]);
const mostrarModalAgenda = ref(false);
const negocioSeleccionado = ref(null);
const mostrarModalDetalle = ref(false);
const citaDetalle = ref(null);
const descargandoExcel = ref(false);

onMounted(() => {
    cargarDatosMenu();
    cargarDatosCitas();
});

const cargarDatosMenu = async () => {
    try {
        const response = await fetch('/api/dashboard', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) {
            usuarioLoggeado.value = data.usuarioLoggeado;
            planAdquirido.value = data.planAdquirido;
        }
    } catch (error) { console.error("Error Menu", error); }
};

const cargarDatosCitas = async () => {
    try {
        const response = await fetch('/api/citas-negocios', {
            method: 'GET',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) negocios.value = data.negocios;
    } catch (error) { window.$toast.show('Error al cargar negocios', 'danger', 4000); }
};

const eventosCalendario = computed(() => {
    return citasNegocio.value.map(cita => ({
        id: cita.id,
        title: cita.cliente_nombre,
        start: cita.fecha + 'T' + cita.hora,
        extendedProps: { ...cita },
        color: '#0d6efd'
    }))
});

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    locale: 'es',
    events: eventosCalendario,
    eventClick: (info) => {
        citaDetalle.value = info.event.extendedProps;
        mostrarModalDetalle.value = true;
    },
    headerToolbar: { left: 'prev,next today', center: 'title', right: '' }
});

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
            await nextTick();
        }
    } catch (error) { window.$toast.show('Error al cargar agenda', 'danger', 4000); }
};

const cerrarModalAgenda = () => {
    mostrarModalAgenda.value = false;
    citasNegocio.value = [];
};

// --- FUNCIÓN PARA DESCARGAR EL EXCEL ---
const descargarExcel = async () => {
    descargandoExcel.value = true;
    try {
        const response = await fetch('/api/citas-negocios/descargar-excel', {
            method: 'GET',
            headers: { 'Authorization': `Bearer ${token}` }
        });

        // Si el backend nos rebota (por ej. Error 403 por seguridad), leemos el mensaje de Laravel
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Error en la descarga');
        }

        // Convertimos la respuesta en un archivo Blob (binario)
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);

        // Creamos un enlace invisible para forzar la descarga en el navegador
        const a = document.createElement('a');
        a.href = url;
        a.download = `Agenda_VB_${new Date().toISOString().split('T')[0]}.xlsx`;
        document.body.appendChild(a);
        a.click();

        // Limpiamos
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        window.$toast.show('Excel descargado correctamente', 'success', 3000);
    } catch (error) {
        // Mostramos el error exacto que nos mandó Laravel
        window.$toast.show(error.message, 'danger', 5000);
    } finally {
        descargandoExcel.value = false;
    }
};

</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold text-dark mb-0">Gestión de Agendas</h4>

            <div class="text-end">
                <button
                    @click="descargarExcel"
                    :disabled="descargandoExcel || !planAdquirido?.puede_descargar_hoy"
                    class="btn btn-success rounded-pill px-4 fw-bold shadow-sm d-flex align-items-center">
                    <span v-if="descargandoExcel" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    <span v-else class="fs-5 me-2">📊</span>
                    {{ descargandoExcel ? 'Generando...' : 'Descargar Excel de 30 Días' }}
                </button>

                <small v-if="planAdquirido && !planAdquirido.puede_descargar_hoy" class="text-danger mt-1 fw-semibold d-block">
                    Descarga manual no disponible con tu plan.
                </small>
            </div>
        </div>

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
                        <div class="calendar-container shadow-sm border">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalDetalle" class="modal fade show d-block" style="background: rgba(0,0,0,0.4); z-index: 1060;">
            <div class="modal-dialog modal-sm modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0 pb-2">
                        <h6 class="fw-bold mb-0">Detalles de la Cita</h6>
                        <button type="button" class="btn-close" @click="mostrarModalDetalle = false"></button>
                    </div>
                    <div class="modal-body pt-0">
                        <div class="p-3 bg-light rounded-3 border">
                            <p class="mb-1 small text-muted fw-semibold">Servicio:</p>
                            <p class="fw-bold text-primary mb-3">{{ citaDetalle.servicio_nombre || 'No especificado' }}</p>

                            <p class="mb-1 small text-muted">Cliente:</p>
                            <p class="fw-bold mb-2">{{ citaDetalle.cliente_nombre }}</p>

                            <p class="mb-1 small text-muted">Teléfono:</p>
                            <p class="mb-3"><a :href="'https://wa.me/52' + citaDetalle.cliente_telefono" target="_blank" class="text-decoration-none text-dark fw-medium">📱 {{ citaDetalle.cliente_telefono }}</a></p>

                            <div class="row g-2 border-top pt-2 mt-2">
                                <div class="col-6">
                                    <p class="mb-0 small text-muted">Hora:</p>
                                    <p class="fw-bold text-dark mb-0">{{ citaDetalle.hora }}</p>
                                </div>
                                <div class="col-6">
                                    <p class="mb-0 small text-muted">Fecha:</p>
                                    <p class="fw-bold text-dark mb-0">{{ citaDetalle.fecha }}</p>
                                </div>
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
    padding: 15px;
    border-radius: 12px;
}
:deep(.fc) { max-height: 70vh; }
:deep(.fc-event) { cursor: pointer; border: none; padding: 2px; }
</style>
