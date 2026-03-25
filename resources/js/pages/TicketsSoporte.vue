<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';

// VARIABLES GLOBALES
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

// DATOS TRAIDOS DE LA BASE DE DATOS
const tickets = ref([]);
const prioridadesTicket = ref([]);
const estadosTicket = ref([]);
const misNegocios = ref([]);
const preguntasFrecuentes = ref([]); // --> NUEVO ARREGLO PARA EL SELECT

// VARIABLES DE FILTROS
const buscar = ref('');
const filtroEstado = ref('');
const filtroPrioridad = ref('');

// VARIABLES DEL MODAL Y FORMULARIO
const mostrarModalTicket = ref(false);
const errores = reactive({});

const formularioTicket = reactive({
    id_negocio: '',
    id_pregunta: '', // --> NUEVO CAMPO
    asunto: ''
});

// AL INICIAR LA PANTALLA
onMounted(() => {
    cargarDatosMenu();
    cargarDatosTickets();
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
const cargarDatosTickets = async () => {
    try {
        const response = await fetch('/api/tickets', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();

        if(data.valid) {
            tickets.value = data.tickets;
            prioridadesTicket.value = data.prioridades;
            estadosTicket.value = data.estados;
            misNegocios.value = data.negocios;
            preguntasFrecuentes.value = data.preguntas; // --> GUARDAMOS LAS PREGUNTAS
        }
    } catch (error) {
        window.$toast.show('Error al cargar la información de tickets', 'danger', 4000);
    }
};

// COMPUTED PARA FILTRAR EN TIEMPO REAL
const ticketsFiltrados = computed(() => {
    return tickets.value.filter(ticket => {
        const coincideTexto = ticket.id.toLowerCase().includes(buscar.value.toLowerCase()) ||
            ticket.asunto.toLowerCase().includes(buscar.value.toLowerCase()) ||
            ticket.negocio_nombre.toLowerCase().includes(buscar.value.toLowerCase()) ||
            (ticket.pregunta_nombre && ticket.pregunta_nombre.toLowerCase().includes(buscar.value.toLowerCase()));

        const coincideEstado = filtroEstado.value === '' || (ticket.id_estado && ticket.id_estado.toString() === filtroEstado.value);
        const coincidePrioridad = filtroPrioridad.value === '' || (ticket.id_prioridad && ticket.id_prioridad.toString() === filtroPrioridad.value);

        return coincideTexto && coincideEstado && coincidePrioridad;
    });
});

// METODOS DEL MODAL
const abrirModalTicket = () => {
    Object.keys(errores).forEach(key => delete errores[key]);
    formularioTicket.id_negocio = '';
    formularioTicket.id_pregunta = '';
    formularioTicket.asunto = '';
    mostrarModalTicket.value = true;
};

const cerrarModalTicket = () => {
    mostrarModalTicket.value = false;
};

// GUARDAR EL NUEVO TICKET
const guardarTicket = async () => {
    Object.keys(errores).forEach(key => delete errores[key]);
    let esValido = true;

    if (!formularioTicket.id_negocio) {
        errores.id_negocio = 'Debes seleccionar un negocio.';
        esValido = false;
    }

    if (!formularioTicket.id_pregunta) {
        errores.id_pregunta = 'Debes seleccionar un tipo de problema.';
        esValido = false;
    }

    // SOLO VALIDA EL TEXTBOX SI SELECCIONÓ "OTRO" (ID 0)
    if (formularioTicket.id_pregunta == 0 && !formularioTicket.asunto.trim()) {
        errores.asunto = 'Por favor, describe tu problema detalladamente.';
        esValido = false;
    }

    if (!esValido) return;

    try {
        const response = await fetch('/api/tickets', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify(formularioTicket)
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
        window.$toast.show('Error al crear el ticket', 'danger', 4000);
    }
};

const verTicket = (ticket) => {
    ticketRevisar.id = ticket.id;
    // Si eligió una pregunta del catálogo, muestra eso en la revisión. Si eligió "Otro", muestra su texto manual.
    ticketRevisar.asunto = ticket.id_pregunta == 0 ? ticket.asunto : ticket.pregunta_nombre;
    ticketRevisar.negocio_nombre = ticket.negocio_nombre;
    ticketRevisar.id_prioridad = ticket.id_prioridad || '';
    ticketRevisar.id_estado = ticket.id_estado;

    mostrarModalRevisar.value = true;
};

const cerrarModalRevisar = () => {
    mostrarModalRevisar.value = false;
};

const mostrarModalRevisar = ref(false);
const ticketRevisar = reactive({
    id: '',
    asunto: '',
    negocio_nombre: '',
    id_prioridad: '',
    id_estado: ''
});

const actualizarTicket = async () => {
    if (!ticketRevisar.id_prioridad || !ticketRevisar.id_estado) {
        window.$toast.show('Debes seleccionar prioridad y estado', 'warning', 3000);
        return;
    }

    try {
        const response = await fetch(`/api/tickets/${ticketRevisar.id}`, {
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

        if(data.valid) {
            window.$toast.show(data.message, 'success', 4000);
            cerrarModalRevisar();
            cargarDatosTickets();
        } else {
            window.$toast.show(data.message, 'warning', 4000);
        }
    } catch (error) {
        window.$toast.show('Error al actualizar el ticket', 'danger', 4000);
    }
};
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Centro de Soporte</h4>
                <p class="text-muted small mb-0">Gestión y resolución de tickets de clientes</p>
            </div>
            <button @click="abrirModalTicket" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center">
                <span class="fs-5 me-2">+</span> Nuevo Ticket
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Total Tickets</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ tickets.length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Abiertos</h6>
                        <h3 class="fw-bold mb-0 text-danger">{{ tickets.filter(t => t.id_estado == '1').length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">En Progreso</h6>
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
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por ID, asunto, cliente o negocio...">
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
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Asunto / Negocio</th>
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
                                <div class="fw-bold text-dark text-truncate" style="max-width: 300px;">
                                    {{ ticket.id_pregunta == 0 ? ticket.asunto : ticket.pregunta_nombre }}
                                </div>
                                <div class="text-muted small">🏢 {{ ticket.negocio_nombre }}</div>
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
                                <button class="btn btn-sm btn-light fw-bold text-primary rounded-3 px-3">
                                    Revisar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="ticketsFiltrados.length === 0">
                            <td colspan="6" class="text-center py-5 text-muted">No se encontraron tickets.</td>
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
                        <button type="button" class="btn-close shadow-none" @click="cerrarModalTicket"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">¿Para qué negocio es?</label>
                            <select v-model="formularioTicket.id_negocio" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{'is-invalid': errores.id_negocio}">
                                <option value="" disabled>Selecciona tu negocio...</option>
                                <option v-for="negocio in misNegocios" :key="negocio.id" :value="negocio.id">
                                    {{ negocio.nombre }}
                                </option>
                            </select>
                            <div class="invalid-feedback fw-medium">{{ errores.id_negocio }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">¿Cuál es el problema?</label>
                            <select v-model="formularioTicket.id_pregunta" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{'is-invalid': errores.id_pregunta}">
                                <option value="" disabled>Selecciona una opción...</option>
                                <option v-for="pregunta in preguntasFrecuentes" :key="pregunta.id" :value="pregunta.id">
                                    {{ pregunta.pregunta }}
                                </option>
                            </select>
                            <div class="invalid-feedback fw-medium">{{ errores.id_pregunta }}</div>
                        </div>

                        <div class="mb-3" v-if="formularioTicket.id_pregunta == 0">
                            <label class="form-label fw-semibold text-secondary">Describe tu problema detalladamente</label>
                            <textarea v-model="formularioTicket.asunto" rows="3" class="form-control form-control-lg bg-light border-0 shadow-sm" placeholder="Explícanos qué sucede..." :class="{'is-invalid': errores.asunto}"></textarea>
                            <div class="invalid-feedback fw-medium">{{ errores.asunto }}</div>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="guardarTicket">Levantar Ticket</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalRevisar" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">Revisar Ticket {{ ticketRevisar.id }}</h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModalRevisar"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <p class="text-muted small mb-1">Negocio: <span class="fw-bold text-dark">{{ ticketRevisar.negocio_nombre }}</span></p>
                            <p class="text-muted small mb-0">Asunto: <span class="fw-bold text-dark">{{ ticketRevisar.asunto }}</span></p>
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

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Actualizar Estado</label>
                            <select v-model="ticketRevisar.id_estado" class="form-select form-select-lg bg-light border-0 shadow-sm">
                                <option v-for="estado in estadosTicket" :key="estado.id" :value="estado.id">
                                    {{ estado.descripcion }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="actualizarTicket">Actualizar Ticket</button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
