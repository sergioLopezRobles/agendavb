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

const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

// Variables Principales de Dueños
const duenos = ref([]);
const buscar = ref('');

// Variables del Nuevo Modal de Negocios
const mostrarModalNegocios = ref(false);
const duenoSeleccionado = ref(null);

// Variables de estado citas, servicios y stats (Originales)
const mostrarModalAgenda = ref(false);
const negocioSeleccionado = ref(null);
const citasNegocio = ref([]);
const mostrarModalDetalle = ref(false);
const citaDetalle = ref(null);
const mostrarModalServicios = ref(false);
const serviciosNegocio = ref([]);
const mostrarModalStats = ref(false);
const datosStats = ref(null);

// ------------------------------------------------------------------
// CONFIGURACIÓN CALENDARIO
// ------------------------------------------------------------------
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

// ------------------------------------------------------------------
// LÓGICA DE CARGA Y FILTROS
// ------------------------------------------------------------------
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

const cargarDirectorio = async () => {
    try {
        const response = await fetch('/api/admin/negocios', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            duenos.value = data.duenos; // Ahora recibimos dueños con negocios anidados
        }
    } catch (error) { window.$toast.show('Error al cargar directorio', 'danger', 4000); }
};

const duenosFiltrados = computed(() => {
    return duenos.value.filter(d => {
        const txt = buscar.value.toLowerCase();
        // Busca por dueño o si alguno de sus negocios coincide
        return d.dueno_nombre.toLowerCase().includes(txt) ||
            (d.dueno_email && d.dueno_email.toLowerCase().includes(txt)) ||
            d.negocios.some(neg => neg.nombre.toLowerCase().includes(txt));
    });
});

// Helpers de Imágenes
const obtenerAvatarDueno = (avatarUrl, nombre) => {
    if (avatarUrl) {
        return `http://localhost/uploads/documentos/profile_pictures/${avatarUrl}`;
    }
    return `https://ui-avatars.com/api/?name=${nombre}&background=0D6EFD&color=fff`;
};

const obtenerRutaLogo = (rutaCompleta) => {
    if (!rutaCompleta) return '';
    const partes = rutaCompleta.split('/');
    const nombreArchivo = partes[partes.length - 1];
    return `/api/ver-logo/${nombreArchivo}`;
};

// ------------------------------------------------------------------
// ACCIONES (Abrir Modales)
// ------------------------------------------------------------------
const abrirListaNegocios = (dueno) => {
    duenoSeleccionado.value = dueno;
    mostrarModalNegocios.value = true;
};

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

const abrirServicios = async (negocio) => {
    negocioSeleccionado.value = negocio;
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

// ChartJS Stats
const chartData = computed(() => {
    if (!datosStats.value || !datosStats.value.stats.length) return null;
    const labels = [];
    const counts = [];
    const backgroundColors = [];
    const coloresPorEstado = { 'Creada': '#3498db', 'En Proceso': '#f39c12', 'Terminada': '#2ecc71', 'Cancelada': '#e74c3c' };

    datosStats.value.stats.forEach(s => {
        labels.push(s.estado);
        counts.push(s.total);
        backgroundColors.push(coloresPorEstado[s.estado] || '#95a5a6');
    });

    return { labels: labels, datasets: [{ data: counts, backgroundColor: backgroundColors, borderWidth: 0 }] };
});

onMounted(() => {
    cargarDatosMenu();
    cargarDirectorio();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Directorio de Dueños</h4>
                <p class="text-muted small mb-0">Gestión de usuarios y sus sucursales operativas</p>
            </div>
            <div class="text-end">
                <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                    Total Dueños: {{ duenosFiltrados.length }}
                </span>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="input-group shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por dueño, correo o negocio...">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Dueño (Usuario)</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Contacto</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Plan y Registro</th>
                            <th class="py-3 px-4 text-center text-secondary fw-semibold border-bottom-0">Total Negocios</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="d in duenosFiltrados" :key="d.id">
                            <td class="px-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <img :src="obtenerAvatarDueno(d.dueno_avatar, d.dueno_nombre)"
                                             alt="Perfil"
                                             class="rounded-circle shadow-sm border bg-white"
                                             style="width: 45px; height: 45px; object-fit: cover;">
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6">{{ d.dueno_nombre }}</div>
                                        <span :class="['badge rounded-pill', d.id_rol === 1 ? 'bg-dark' : 'bg-info text-dark']" style="font-size: 0.65rem;">
                                            {{ d.rol_nombre }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-dark small mb-1">✉️ {{ d.dueno_email }}</div>
                                <div class="text-muted small">📞 {{ d.dueno_telefono || 'Sin teléfono' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="mb-1">
                                    <span :class="['badge rounded-pill shadow-sm', d.plan_nombre === 'SIN PLAN' ? 'bg-secondary' : 'bg-primary bg-gradient']">
                                        {{ d.plan_nombre }}
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    <span title="Fecha de registro en la plataforma">📅 Registro: {{ d.fecha_registro }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge bg-dark fs-6 rounded-circle p-2" style="width: 35px; height: 35px;">
                                    {{ d.negocios.length }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button @click="abrirListaNegocios(d)" class="btn btn-primary fw-bold rounded-3 px-3 shadow-sm d-inline-flex align-items-center" :disabled="d.negocios.length === 0">
                                     Ver Negocios
                                </button>
                            </td>
                        </tr>
                        <tr v-if="duenosFiltrados.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalNegocios" class="modal fade show d-block" style="background: rgba(0,0,0,0.5); z-index: 1040;">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 rounded-4 shadow-lg">
                    <div class="modal-header bg-light border-0 px-4 py-3">
                        <h5 class="fw-bold mb-0 d-flex align-items-center">
                            <img :src="obtenerAvatarDueno(duenoSeleccionado.dueno_avatar, duenoSeleccionado.dueno_nombre)" class="rounded-circle me-3 shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                            Sucursales de {{ duenoSeleccionado.dueno_nombre }}
                        </h5>
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalNegocios = false"></button>
                    </div>

                    <div class="modal-body p-0">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-white sticky-top shadow-sm">
                            <tr>
                                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Negocio</th>
                                <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Ubicación / Contacto</th>
                                <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Gestión Operativa</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="n in duenoSeleccionado.negocios" :key="n.id">
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <img v-if="n.logo" :src="obtenerRutaLogo(n.logo)" alt="Logo" class="rounded-circle shadow-sm border bg-white" style="width: 45px; height: 45px; object-fit: cover;">
                                            <div v-else class="rounded-circle shadow-sm d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold border border-primary border-opacity-25" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                                {{ n.nombre.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-dark fs-6">{{ n.nombre }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-muted small text-truncate" style="max-width: 300px;">📍 {{ n.direccion || 'Sin dirección' }}</div>
                                    <div class="text-muted small">✉️ {{ n.email || 'Sin correo de local' }}</div>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button @click="abrirServicios(n)" class="btn btn-sm btn-outline-primary fw-bold rounded-3 px-3 d-flex align-items-center">
                                            <span>✂️ Servicios</span>
                                        </button>
                                        <button @click="abrirAgenda(n)" class="btn btn-sm btn-outline-info fw-bold rounded-3 px-3 d-flex align-items-center">
                                            <span>📅 Citas</span>
                                        </button>
                                        <button @click="abrirStats(n)" class="btn btn-sm btn-outline-dark fw-bold rounded-3 px-3 d-flex align-items-center">
                                            <span>📊 Stats</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
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
                        <p class="text-muted small mb-0">Negocio: <span class="fw-bold text-primary">{{ negocioSeleccionado?.nombre }}</span></p>
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
                                            <div v-if="s.notas" class="text-muted mt-1" style="font-size: 0.75rem;">📝 {{ s.notas }}</div>
                                        </td>
                                        <td class="px-3 py-3 fw-bold text-success">${{ s.precio }}</td>
                                        <td class="px-3 py-3 fw-bold text-primary">
                                            <span v-if="s.anticipo && s.anticipo > 0">${{ s.anticipo }}</span>
                                            <span v-else>(Sin anticipo)</span>
                                        </td>
                                        <td class="px-3 py-3">
                                            <span v-if="s.tarjeta == '1'" class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">💳 Tarjeta</span>
                                            <span v-else class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">💵 Efectivo</span>
                                        </td>
                                        <td class="px-3 py-3 text-muted">{{ s.duracion_minutos }} min</td>
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
                        <h5 class="fw-bold text-dark">Análisis Operativo: {{ negocioSeleccionado?.nombre }}</h5>
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
