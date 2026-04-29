<script setup>
import { ref, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import { nextTick } from 'vue';
import { Pie } from 'vue-chartjs';
import { Chart as ChartJS, Title, Tooltip, Legend, ArcElement, CategoryScale } from 'chart.js';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale);

const mostrarModalStats = ref(false);
const datosStats = ref(null);

const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);
const negocios = ref([]);
const buscar = ref('');

// 2. variables de estado citas calendario negocios
const mostrarModalAgenda = ref(false);
const negocioSeleccionado = ref(null);
const citasNegocio = ref([]);
const mostrarModalDetalle = ref(false);
const citaDetalle = ref(null);

// Variables para el Modal de Servicios
const mostrarModalServicios = ref(false);
const serviciosNegocio = ref([]);

// 3. Configuración del Calendario (Igual a la del Dueño)
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

// 4. Función para abrir la agenda
const abrirAgenda = async (negocio) => {
    negocioSeleccionado.value = negocio;
    try {
        const response = await fetch(`/api/admin/negocios/${negocio.id}/citas`, {
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

const cargarDatosMenu = async () => {
    try {
        const response = await fetch('/api/dashboard', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            usuarioLoggeado.value = data.usuarioLoggeado;
            planAdquirido.value = data.planAdquirido;
        }
    } catch (error) { console.error("Error menu", error); }
};

const cargarNegocios = async () => {
    try {
        const response = await fetch('/api/admin/negocios', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            negocios.value = data.negocios;
        }
    } catch (error) { window.$toast.show('Error al cargar los negocios', 'danger', 4000); }
};

const negociosFiltrados = computed(() => {
    return negocios.value.filter(n => {
        const textoBusqueda = buscar.value.toLowerCase();
        return n.negocio_nombre.toLowerCase().includes(textoBusqueda) ||
            n.dueno_nombre.toLowerCase().includes(textoBusqueda) ||
            (n.negocio_email && n.negocio_email.toLowerCase().includes(textoBusqueda));
    });
});

const obtenerRutaLogo = (rutaCompleta) => {
    if (!rutaCompleta) return '';
    const partes = rutaCompleta.split('/');
    const nombreArchivo = partes[partes.length - 1]; // Toma lo que está después de la última '/'
    return `/api/ver-logo/${nombreArchivo}`;
};

// Función para cargar y abrir servicios
const abrirServicios = async (negocio) => {
    negocioSeleccionado.value = negocio; // Reutilizamos esta variable
    try {
        const response = await fetch(`/api/admin/negocios/${negocio.id}/servicios`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) {
            serviciosNegocio.value = data.servicios;
            mostrarModalServicios.value = true;
        }
    } catch (error) { window.$toast.show('Error al cargar servicios', 'danger', 4000); }
};

const cerrarModalServicios = () => {
    mostrarModalServicios.value = false;
    serviciosNegocio.value = [];
};

const chartData = computed(() => {
    if (!datosStats.value || !datosStats.value.stats.length) return null;

    const labels = [];
    const counts = [];
    const backgroundColors = [];

    // Diccionario para que siempre tengan un color fijo según el texto
    const coloresPorEstado = {
        'Creada': '#3498db',      // Azul
        'En Proceso': '#f39c12',  // Naranja
        'Terminada': '#2ecc71',   // Verde
        'Cancelada': '#e74c3c'    // Rojo
    };

    datosStats.value.stats.forEach(s => {
        labels.push(s.estado);
        counts.push(s.total);
        // Si por alguna razón agregan un estado nuevo en la BD, se pone gris por defecto
        backgroundColors.push(coloresPorEstado[s.estado] || '#95a5a6');
    });

    return {
        labels: labels,
        datasets: [{
            data: counts,
            backgroundColor: backgroundColors,
            borderWidth: 0
        }]
    };
});

const abrirStats = async (negocio) => {
    negocioSeleccionado.value = negocio;
    try {
        const response = await fetch(`/api/admin/negocios/${negocio.id}/stats`, {
            headers: { 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if(data.valid) {
            datosStats.value = data;
            mostrarModalStats.value = true;
        }
    } catch (error) { window.$toast.show('Error al cargar estadísticas', 'danger', 4000); }
};

onMounted(() => {
    cargarDatosMenu();
    cargarNegocios();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Directorio de Negocios</h4>
                <p class="text-muted small mb-0">Supervisión operativa de todos los comercios del SaaS</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                    Total Registrados: {{ negocios.length }}
                </span>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por negocio, dueño o correo...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Negocio</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Responsable (Dueño)</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Detalles Operativos</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Registro</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Gestión</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="n in negociosFiltrados" :key="n.id">
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <img v-if="n.logo"
                                             :src="obtenerRutaLogo(n.logo)"
                                             alt="Logo"
                                             class="rounded-circle shadow-sm border bg-white"
                                             style="width: 45px; height: 45px; object-fit: cover;">

                                        <div v-else
                                             class="rounded-circle shadow-sm d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold border border-primary border-opacity-25"
                                             style="width: 45px; height: 45px; font-size: 1.2rem;">
                                            {{ n.negocio_nombre.charAt(0).toUpperCase() }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="fw-bold text-dark fs-6">{{ n.negocio_nombre }}</div>
                                        <div class="text-muted small">✉️ {{ n.negocio_email || 'Sin correo de local' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ n.dueno_nombre }}</div>
                                <div class="text-muted small">📞 {{ n.dueno_telefono || 'Sin teléfono' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="mb-1">
                                    <span class="badge bg-light text-dark border rounded-pill me-1">Plan {{ n.plan_nombre || 'No asignado' }}</span>
                                </div>
                                <div class="text-muted small text-truncate" style="max-width: 250px;">
                                    📍 {{ n.direccion || 'Sin dirección registrada' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-muted small">
                                {{ n.fecha_creacion }}
                            </td>
                            <td class="px-4 py-3 text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button @click="abrirServicios(n)" class="btn btn-sm btn-outline-primary fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Servicios">
                                        <span>✂️ Servicios</span>
                                    </button>
                                    <button @click="abrirAgenda(n)" class="btn btn-sm btn-outline-info fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Citas">
                                        <span>📅 Citas</span>
                                    </button>
                                    <button @click="abrirStats(n)" class="btn btn-sm btn-outline-dark fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Estadísticas">
                                        <span>📊 Stats</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="negociosFiltrados.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">No se encontraron negocios en la plataforma.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalAgenda" class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-0 px-4 pt-4">
                        <h5 class="fw-bold">Agenda de {{ negocioSeleccionado?.negocio_nombre }}</h5>
                        <button type="button" class="btn-close" @click="cerrarModalAgenda"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="calendar-container shadow-sm border p-3 bg-white rounded-3">
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
                            <p class="fw-bold mb-3">📱 {{ citaDetalle.cliente_telefono }}</p>
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

        <div v-if="mostrarModalServicios" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="fw-bold text-dark mb-0">Gestión de Servicios</h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModalServicios"></button>
                    </div>

                    <div class="px-4 mt-1 mb-4">
                        <p class="text-muted small mb-0">Negocio: <span class="fw-bold text-primary">{{ negocioSeleccionado?.negocio_nombre }}</span></p>
                    </div>

                    <div class="modal-body px-4 pb-4 pt-0">
                        <div v-if="serviciosNegocio.length === 0" class="text-center py-5 bg-light rounded-3 border">
                            <span class="fs-1 d-block mb-2">📭</span>
                            <p class="text-muted mb-0">Este negocio aún no tiene servicios registrados.</p>
                        </div>

                        <div v-else class="card shadow-sm border-0 rounded-4 overflow-hidden">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                    <tr>
                                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Nombre</th>
                                        <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Precio Total</th>
                                        <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Anticipo Fijo</th>
                                        <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Método</th>
                                        <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Duración</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr v-for="s in serviciosNegocio" :key="s.id">
                                        <td class="px-4 py-3">
                                            <div class="fw-bold text-dark">{{ s.nombre }}</div>
                                            <div v-if="s.notas" class="text-muted mt-1" style="font-size: 0.75rem;">
                                                📝 {{ s.notas }}
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 fw-bold text-success">
                                            ${{ s.precio }}
                                        </td>
                                        <td class="px-3 py-3 fw-bold text-primary">
                                            <span v-if="s.anticipo && s.anticipo > 0">${{ s.anticipo }}</span>
                                            <span v-else>(Sin anticipo)</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span v-if="s.tarjeta == '1'" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                                                💳 Tarjeta
                                            </span>
                                            <span v-else class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                                💵 Efectivo
                                            </span>
                                        </td>
                                        <td class="px-3 py-3 text-muted">
                                            {{ s.duracion_minutos }} min
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalStats" class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header border-0 px-4 pt-4">
                        <h5 class="fw-bold text-dark">Análisis Operativo: {{ negocioSeleccionado?.negocio_nombre }}</h5>
                        <button type="button" class="btn-close" @click="mostrarModalStats = false"></button>
                    </div>

                    <div class="modal-body p-4">
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-4 text-center">
                                    <small class="text-primary fw-bold d-block mb-1">CITAS ESTE MES</small>
                                    <h3 class="fw-bold mb-0 text-primary">{{ datosStats?.totalMes }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-success bg-opacity-10 border border-success border-opacity-25 rounded-4 text-center">
                                    <small class="text-success fw-bold d-block mb-1">INGRESOS (TERMINADAS)</small>
                                    <h3 class="fw-bold mb-0 text-success">${{ datosStats?.ingresos }}</h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-dark bg-opacity-10 border border-dark border-opacity-25 rounded-4 text-center">
                                    <small class="text-dark fw-bold d-block mb-1">CRÉDITOS WSP</small>
                                    <h3 class="fw-bold mb-0 text-dark">{{ negocioSeleccionado?.whatsapp_creditos }}</h3>
                                </div>
                            </div>
                        </div>

                        <div class="row align-items-center">
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold text-center mb-3">Distribución de Citas</h6>
                                <div style="max-height: 250px;">
                                    <Pie v-if="chartData" :data="chartData" :options="{ responsive: true, maintainAspectRatio: false }" />
                                </div>
                            </div>

                            <div class="col-md-6 ps-4">
                                <h6 class="fw-bold mb-3">🏆 Servicios más solicitados</h6>
                                <div v-for="(ser, index) in datosStats?.topServicios" :key="index" class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-3">
                                    <span class="small fw-bold text-dark">{{ ser.nombre }}</span>
                                    <span class="badge bg-dark rounded-pill">{{ ser.total }} citas</span>
                                </div>
                                <div v-if="datosStats?.topServicios.length === 0" class="text-muted small text-center py-4">
                                    No hay datos suficientes aún.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
