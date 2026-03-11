<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../componentes/Layout.vue';

// 1. Variables para el menú lateral
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

// 2. Datos de prueba (Dummy Data) para la tabla
const tickets = ref([
    {
        id: 'TKT-1042',
        asunto: 'Error al sincronizar mi calendario con el plan avanzado',
        negocio: 'Clínica Dental Vista Boreal',
        prioridad: 'Alta',
        estado: 'Abierto',
        fecha: 'Hace 2 horas'
    },
    {
        id: 'TKT-1041',
        asunto: 'Duda sobre el límite de WhatsApp en el plan Medio',
        negocio: 'Barbería López',
        prioridad: 'Media',
        estado: 'En Progreso',
        fecha: 'Hace 5 horas'
    },
    {
        id: 'TKT-1040',
        asunto: 'No puedo agregar un tercer negocio al sistema',
        negocio: 'Spa Relax',
        prioridad: 'Baja',
        estado: 'Resuelto',
        fecha: 'Ayer'
    },
    {
        id: 'TKT-1039',
        asunto: 'Solicitud de cambio de correo del titular',
        negocio: 'Consultorio Dr. Martínez',
        prioridad: 'Alta',
        estado: 'Abierto',
        fecha: 'Hace 1 día'
    }
]);

const buscar = ref('');

// 3. Al montar la vista, traemos los datos del usuario para "despertar" el menú
onMounted(() => {
    cargarDatosMenu();
});

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
        console.error("Error al cargar los datos para el menú", error);
    }
};

const verTicket = (id) => {
    console.log("Abriendo detalles del ticket:", id);
};
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Centro de Soporte</h4>
                <p class="text-muted small mb-0">Gestión y resolución de tickets de clientes</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center">
                <span class="fs-5 me-2">+</span> Nuevo Ticket
            </button>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Total Tickets</h6>
                        <h3 class="fw-bold mb-0 text-dark">124</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Abiertos</h6>
                        <h3 class="fw-bold mb-0 text-danger">12</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">En Progreso</h6>
                        <h3 class="fw-bold mb-0 text-warning">5</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-success h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Resueltos (Hoy)</h6>
                        <h3 class="fw-bold mb-0 text-success">8</h3>
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
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por ID, asunto o cliente...">
                        </div>
                    </div>
                    <div class="col-md-8 text-end">
                        <select class="form-select w-auto d-inline-block shadow-sm border-0 bg-light me-2">
                            <option value="">Cualquier Estado</option>
                            <option value="Abierto">Abiertos</option>
                            <option value="En Progreso">En Progreso</option>
                            <option value="Resuelto">Resueltos</option>
                        </select>
                        <select class="form-select w-auto d-inline-block shadow-sm border-0 bg-light">
                            <option value="">Cualquier Prioridad</option>
                            <option value="Alta">Alta</option>
                            <option value="Media">Media</option>
                            <option value="Baja">Baja</option>
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
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Actualización</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="ticket in tickets" :key="ticket.id" style="cursor: pointer;" @click="verTicket(ticket.id)">
                            <td class="px-4 py-3 fw-bold text-primary">{{ ticket.id }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark text-truncate" style="max-width: 300px;">{{ ticket.asunto }}</div>
                                <div class="text-muted small">🏢 {{ ticket.negocio }}</div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                    <span class="badge rounded-pill"
                                          :class="{
                                            'bg-danger bg-opacity-10 text-danger': ticket.prioridad === 'Alta',
                                            'bg-warning bg-opacity-10 text-dark': ticket.prioridad === 'Media',
                                            'bg-secondary bg-opacity-10 text-secondary': ticket.prioridad === 'Baja'
                                        }">
                                        {{ ticket.prioridad }}
                                    </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                    <span class="badge rounded-pill"
                                          :class="{
                                            'bg-primary': ticket.estado === 'Abierto',
                                            'bg-warning text-dark': ticket.estado === 'En Progreso',
                                            'bg-success': ticket.estado === 'Resuelto'
                                        }">
                                        {{ ticket.estado }}
                                    </span>
                            </td>
                            <td class="px-4 py-3 text-muted small">{{ ticket.fecha }}</td>
                            <td class="px-4 py-3 text-end">
                                <button class="btn btn-sm btn-light fw-bold text-primary rounded-3 px-3">
                                    Revisar
                                </button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </Layout>
</template>
