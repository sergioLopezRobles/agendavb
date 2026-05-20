<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';

// ── State ──────────────────────────────────────────────────────────────────────
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const tickets = ref([]);
const prioridadesTicket = ref([]);
const estadosTicket = ref([]);

const buscar = ref('');
const filtroEstado = ref('');
const filtroPrioridad = ref('');

const mostrarModalRevisar = ref(false);
const ticketRevisar = reactive({
    id: '',
    asunto: '',
    negocio_nombre: '',
    dueno_nombre: '',
    dueno_email: '',
    dueno_telefono: '',
    id_prioridad: '',
    id_estado: ''
});

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

const cargarDatosTicketsGlobales = async () => {
    try {
        const response = await fetch('/api/admin/tickets', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            tickets.value = data.tickets;
            prioridadesTicket.value = data.prioridades;
            estadosTicket.value = data.estados;
        }
    } catch (error) { window.$toast.show('Error al cargar tickets globales', 'danger', 4000); }
};

// ── Computed ───────────────────────────────────────────────────────────────────
const ticketsFiltrados = computed(() => {
    return tickets.value.filter(ticket => {
        const busqueda = buscar.value.toLowerCase();

        // Búsqueda ampliada: ID, Nombre del dueño, Email del dueño, Asunto o Negocio
        const coincideTexto =
            ticket.id.toLowerCase().includes(busqueda) ||
            (ticket.dueno_nombre && ticket.dueno_nombre.toLowerCase().includes(busqueda)) ||
            (ticket.dueno_email && ticket.dueno_email.toLowerCase().includes(busqueda)) ||
            (ticket.asunto && ticket.asunto.toLowerCase().includes(busqueda)) ||
            (ticket.negocio_nombre && ticket.negocio_nombre.toLowerCase().includes(busqueda));

        const coincideEstado = filtroEstado.value === '' || (ticket.id_estado && ticket.id_estado.toString() === filtroEstado.value);
        const coincidePrioridad = filtroPrioridad.value === '' || (ticket.id_prioridad && ticket.id_prioridad.toString() === filtroPrioridad.value);

        return coincideTexto && coincideEstado && coincidePrioridad;
    });
});

// ── Modal & Logic (Review Ticket) ─────────────────────────────────────────────
const verTicket = (ticket) => {
    ticketRevisar.id = ticket.id;
    ticketRevisar.asunto = ticket.asunto;
    ticketRevisar.negocio_nombre = ticket.negocio_nombre;
    ticketRevisar.dueno_nombre = ticket.dueno_nombre;
    ticketRevisar.dueno_email = ticket.dueno_email;
    ticketRevisar.dueno_telefono = ticket.dueno_telefono;
    ticketRevisar.id_prioridad = ticket.id_prioridad || '';
    ticketRevisar.id_estado = ticket.id_estado;

    mostrarModalRevisar.value = true;
};

const cerrarModalRevisar = () => {
    mostrarModalRevisar.value = false;
};

const actualizarTicket = async () => {
    if (!ticketRevisar.id_prioridad || !ticketRevisar.id_estado) {
        window.$toast.show('Debes seleccionar prioridad y estado', 'warning', 3000);
        return;
    }

    try {
        const response = await fetch(`/api/admin/tickets/${ticketRevisar.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                id_prioridad: ticketRevisar.id_prioridad,
                id_estado: ticketRevisar.id_estado
            })
        });

        const data = await response.json();
        if (data.valid) {
            window.$toast.show('Estado del ticket actualizado exitosamente', 'success', 4000);
            cerrarModalRevisar();
            cargarDatosTicketsGlobales();
        } else {
            window.$toast.show(data.message, 'warning', 4000);
        }
    } catch (error) {
        window.$toast.show('Error al actualizar el ticket', 'danger', 4000);
    }
};

onMounted(() => {
    cargarDatosMenu();
    cargarDatosTicketsGlobales();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Bandeja de Tickets</h4>
                <p class="text-muted small mb-0">Gestión central de soporte</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Total en el sistema</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ tickets.length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Pendientes</h6>
                        <h3 class="fw-bold mb-0 text-danger">{{ tickets.filter(t => t.id_estado == '1').length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">En Proceso</h6>
                        <h3 class="fw-bold mb-0 text-warning">{{ tickets.filter(t => t.id_estado == '2').length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-success h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Resueltos</h6>
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
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por dueño, correo o ID...">
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <select v-model="filtroPrioridad" class="form-select w-auto d-inline-block shadow-sm border-2 bg-light me-3">
                            <option value="">Cualquier Prioridad</option>
                            <option v-for="prioridad in prioridadesTicket" :key="prioridad.id" :value="prioridad.id.toString()">
                                {{ prioridad.descripcion }}
                            </option>
                        </select>
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
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">ID Ticket</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Usuario (Dueño)</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Prioridad</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Estado</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Fecha</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="ticket in ticketsFiltrados" :key="ticket.id" style="cursor: pointer;" @click="verTicket(ticket)">
                            <td class="px-4 py-3 fw-bold text-primary">{{ ticket.id }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 250px;">
                                    👤 {{ ticket.dueno_nombre }}
                                </div>
                                <div class="text-muted small">✉️ {{ ticket.dueno_email }}</div>
                                <div class="text-muted small">📞 {{ ticket.dueno_telefono || 'Sin teléfono' }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                    <span class="badge rounded-pill"
                                          :class="{
                                            'bg-danger bg-opacity-10 text-danger': ticket.prioridad_nombre === 'Alta',
                                            'bg-warning bg-opacity-10 text-dark': ticket.prioridad_nombre === 'Media',
                                            'bg-secondary bg-opacity-10 text-secondary': ticket.prioridad_nombre === 'Baja',
                                            'bg-light text-dark border': !ticket.prioridad_nombre
                                        }">
                                        {{ ticket.prioridad_nombre || 'Por definir' }}
                                    </span>
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
                                <button class="btn btn-sm btn-dark fw-bold text-white rounded-3 px-3">
                                    Gestionar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="ticketsFiltrados.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">No se encontraron tickets en la plataforma.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalRevisar" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header bg-light border-0 pb-3 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">Gestionar Ticket {{ ticketRevisar.id }}</h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModalRevisar"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-4 p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3">
                            <h6 class="fw-bold text-primary mb-2">Contacto del Dueño</h6>
                            <p class="mb-1 small text-dark"><strong>👤 Nombre:</strong> {{ ticketRevisar.dueno_nombre }}</p>
                            <p class="mb-1 small text-dark"><strong>✉️ Correo:</strong> {{ ticketRevisar.dueno_email }}</p>
                            <p class="mb-0 small text-dark"><strong>📞 Teléfono:</strong> {{ ticketRevisar.dueno_telefono || 'No registrado' }}</p>
                        </div>

                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <p class="text-muted small mb-1">Negocio Afectado: <span class="fw-bold text-dark">{{ ticketRevisar.negocio_nombre }}</span></p>
                            <hr class="my-2">
                            <p class="text-muted small mb-0">Asunto del Ticket:</p>
                            <p class="fw-bold text-dark mb-0" style="word-wrap: break-word;">{{ ticketRevisar.asunto }}</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Asignar Prioridad</label>
                            <select v-model="ticketRevisar.id_prioridad" class="form-select form-select-lg bg-light border-0 shadow-sm">
                                <option value="" disabled>Selecciona la prioridad...</option>
                                <option v-for="prioridad in prioridadesTicket" :key="prioridad.id" :value="prioridad.id">
                                    {{ prioridad.descripcion }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Actualizar Estado</label>
                            <select v-model="ticketRevisar.id_estado" class="form-select form-select-lg bg-light border-0 shadow-sm">
                                <option v-for="estado in estadosTicket" :key="estado.id" :value="estado.id">
                                    {{ estado.descripcion }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-dark w-100 py-3 fw-bold fs-6 rounded-3 shadow-sm hover-shadow" @click="actualizarTicket">
                            Aplicar Cambios
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
