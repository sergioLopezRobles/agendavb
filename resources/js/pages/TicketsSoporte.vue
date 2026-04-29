<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';

// ── State ──────────────────────────────────────────────────────────────────────
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const tickets = ref([]);
const estadosTicket = ref([]);
const misNegocios = ref([]);
const preguntasFrecuentes = ref([]);

const preguntasOrdenadas = computed(() => {
    return [...preguntasFrecuentes.value].sort((a, b) => {
        if (a.id == 0) return 1;
        if (b.id == 0) return -1;
        return a.id - b.id;
    });
});

const buscar = ref('');
const filtroEstado = ref('');

const mostrarModalTicket = ref(false);
const enviandoTicket = ref(false); // <-- NUEVA VARIABLE ANTI-DOBLE CLIC
const formularioTicket = reactive({ id_negocio: '', id_pregunta: '', asunto: '' });
const errores = reactive({});

const mostrarModalRevisar = ref(false);
const ticketRevisar = reactive({ id: '', asunto: '', negocio_nombre: '', estado_nombre: '', fecha: '' });

// ── API Calls ──────────────────────────────────────────────────────────────────
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

const cargarDatosTickets = async () => {
    try {
        const response = await fetch('/api/tickets', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            tickets.value = data.tickets;
            estadosTicket.value = data.estados;
            misNegocios.value = data.negocios;
            preguntasFrecuentes.value = data.preguntas;
        }
    } catch (error) { window.$toast.show('Error al cargar tickets', 'danger', 4000); }
};

// ── Computed ───────────────────────────────────────────────────────────────────
const ticketsFiltrados = computed(() => {
    return tickets.value.filter(ticket => {
        const coincideTexto = ticket.id.toLowerCase().includes(buscar.value.toLowerCase()) ||
            ticket.asunto.toLowerCase().includes(buscar.value.toLowerCase()) ||
            ticket.negocio_nombre.toLowerCase().includes(buscar.value.toLowerCase()) ||
            (ticket.pregunta_nombre && ticket.pregunta_nombre.toLowerCase().includes(buscar.value.toLowerCase()));

        const coincideEstado = filtroEstado.value === '' || (ticket.id_estado && ticket.id_estado.toString() === filtroEstado.value);

        return coincideTexto && coincideEstado;
    });
});

// ── Modal Crear Ticket ────────────────────────────────────────────────────────
const abrirModalTicket = () => {
    Object.keys(errores).forEach(key => delete errores[key]);
    formularioTicket.id_negocio = '';
    formularioTicket.id_pregunta = '';
    formularioTicket.asunto = '';
    mostrarModalTicket.value = true;
};

const cerrarModalTicket = () => {
    if(!enviandoTicket.value) mostrarModalTicket.value = false;
};

const guardarTicket = async () => {
    // Si ya se está enviando, ignoramos clics extra
    if (enviandoTicket.value) return;

    Object.keys(errores).forEach(key => delete errores[key]);
    let esValido = true;

    if (!formularioTicket.id_negocio) { errores.id_negocio = 'Selecciona un negocio.'; esValido = false; }
    if (formularioTicket.id_pregunta === '') { errores.id_pregunta = 'Selecciona un problema.'; esValido = false; }
    if (formularioTicket.id_pregunta == 0 && !formularioTicket.asunto.trim()) { errores.asunto = 'Describe tu problema.'; esValido = false; }

    if (!esValido) return;

    // Bloqueamos el botón
    enviandoTicket.value = true;

    let asuntoFinal = formularioTicket.asunto;
    if (formularioTicket.id_pregunta != 0) {
        const pSel = preguntasOrdenadas.value.find(p => p.id == formularioTicket.id_pregunta);
        asuntoFinal = pSel ? pSel.pregunta : 'Soporte General';
    }

    try {
        const response = await fetch('/api/tickets', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
            body: JSON.stringify({
                id_negocio: formularioTicket.id_negocio,
                id_pregunta: formularioTicket.id_pregunta,
                asunto: asuntoFinal
            })
        });
        const data = await response.json();
        if(data.valid) {
            window.$toast.show(data.message, 'success', 4000);
            cerrarModalTicket();
            cargarDatosTickets();
        } else {
            window.$toast.show(data.message, 'warning', 4000);
        }
    } catch (error) {
        window.$toast.show('Error al crear ticket', 'danger', 4000);
    } finally {
        // Liberamos el botón pase lo que pase
        enviandoTicket.value = false;
    }
};

// ── Modal Ver Detalles (Solo Lectura) ─────────────────────────────────────────
const verTicket = (ticket) => {
    ticketRevisar.id = ticket.id;
    ticketRevisar.asunto = (ticket.id_pregunta == 0) ? ticket.asunto : ticket.pregunta_nombre;
    ticketRevisar.negocio_nombre = ticket.negocio_nombre;
    ticketRevisar.estado_nombre = ticket.estado_nombre;
    ticketRevisar.fecha = ticket.fecha;
    mostrarModalRevisar.value = true;
};

const cerrarModalRevisar = () => mostrarModalRevisar.value = false;

onMounted(() => {
    cargarDatosMenu();
    cargarDatosTickets();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Mis Tickets de Soporte</h4>
                <p class="text-muted small mb-0">Comunícate con el administrador para resolver dudas</p>
            </div>
            <button @click="abrirModalTicket" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center">
                <span class="fs-5 me-2">+</span> Levantar Ticket
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Mis Tickets</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ tickets.length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">En espera</h6>
                        <h3 class="fw-bold mb-0 text-danger">{{ tickets.filter(t => t.id_estado == '1').length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Revisando</h6>
                        <h3 class="fw-bold mb-0 text-warning">{{ tickets.filter(t => t.id_estado == '2').length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-success h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Finalizados</h6>
                        <h3 class="fw-bold mb-0 text-success">{{ tickets.filter(t => t.id_estado == '3').length }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <div class="row">
                    <div class="col-md-4">
                        <div class="input-group shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar ticket...">
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <select v-model="filtroEstado" class="form-select w-auto d-inline-block shadow-sm border-2 bg-light">
                            <option value="">Cualquier Estado</option>
                            <option v-for="estado in estadosTicket" :key="estado.id" :value="estado.id.toString()">
                                {{ estado.descripcion }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Folio</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Problema</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Estado</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Fecha</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0"></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="ticket in ticketsFiltrados" :key="ticket.id" style="cursor: pointer;" @click="verTicket(ticket)">
                            <td class="px-4 py-3 fw-bold text-primary">{{ ticket.id }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 300px;">
                                    {{ (ticket.id_pregunta == 0) ? ticket.asunto : ticket.pregunta_nombre }}
                                </div>
                                <div class="text-muted small">🏢 {{ ticket.negocio_nombre }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                    <span class="badge rounded-pill"
                                          :class="{
                                            'bg-primary': ticket.estado_nombre === 'Pendiente',
                                            'bg-warning text-dark': ticket.estado_nombre === 'En proceso',
                                            'bg-success': ticket.estado_nombre === 'Resuelto'
                                        }">
                                        {{ ticket.estado_nombre }}
                                    </span>
                            </td>
                            <td class="px-4 py-3 text-muted small">{{ ticket.fecha }}</td>
                            <td class="px-4 py-3 text-end">
                                <button class="btn btn-sm btn-light fw-bold text-primary rounded-3 px-3">Ver</button>
                            </td>
                        </tr>
                        <tr v-if="ticketsFiltrados.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">No has levantado ningún ticket.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalTicket" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">🎧 Nuevo Ticket de Soporte</h5>
                        <button type="button" class="btn-close shadow-none" :disabled="enviandoTicket" @click="cerrarModalTicket"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">¿Para qué negocio es?</label>
                            <select v-model="formularioTicket.id_negocio" :disabled="enviandoTicket" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{'is-invalid': errores.id_negocio}">
                                <option value="" disabled>Selecciona tu negocio...</option>
                                <option v-for="negocio in misNegocios" :key="negocio.id" :value="negocio.id">{{ negocio.nombre }}</option>
                            </select>
                            <div class="invalid-feedback fw-medium">{{ errores.id_negocio }}</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">¿Cuál es el problema?</label>
                            <select v-model="formularioTicket.id_pregunta" :disabled="enviandoTicket" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{'is-invalid': errores.id_pregunta}">
                                <option value="" disabled>Selecciona una opción...</option>
                                <option v-for="pregunta in preguntasOrdenadas" :key="pregunta.id" :value="pregunta.id">{{ pregunta.pregunta }}</option>
                            </select>
                            <div class="invalid-feedback fw-medium">{{ errores.id_pregunta }}</div>
                        </div>
                        <div class="mb-3" v-if="formularioTicket.id_pregunta == 0 && formularioTicket.id_pregunta !== ''">
                            <label class="form-label fw-semibold text-secondary">Describe tu problema detalladamente</label>
                            <textarea v-model="formularioTicket.asunto" :disabled="enviandoTicket" rows="3" class="form-control form-control-lg bg-light border-0 shadow-sm" placeholder="Explícanos qué sucede..." :class="{'is-invalid': errores.asunto}"></textarea>
                            <div class="invalid-feedback fw-medium">{{ errores.asunto }}</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="guardarTicket" :disabled="enviandoTicket">
                            <span v-if="enviandoTicket" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ enviandoTicket ? 'Procesando...' : 'Levantar Ticket' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalRevisar" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">Detalles del Ticket {{ ticketRevisar.id }}</h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModalRevisar"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <p class="text-muted small mb-1">Negocio: <span class="fw-bold text-dark">{{ ticketRevisar.negocio_nombre }}</span></p>
                            <p class="text-muted small mb-1">Fecha de creación: <span class="fw-bold text-dark">{{ ticketRevisar.fecha }}</span></p>
                            <p class="text-muted small mb-0">Detalle: <span class="fw-bold text-dark">{{ ticketRevisar.asunto }}</span></p>
                        </div>
                        <div class="row justify-content-center">
                            <div class="col-8">
                                <div class="p-3 border rounded-3 text-center">
                                    <p class="text-muted small mb-1">Estado Actual</p>
                                    <h6 class="fw-bold mb-0 text-primary">{{ ticketRevisar.estado_nombre }}</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-center">
                        <button type="button" class="btn btn-secondary w-100 py-3 fw-bold fs-6 rounded-3" @click="cerrarModalRevisar">Cerrar Detalles</button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
