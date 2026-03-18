<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';
import PlanCard from '../componentes/PlanCard.vue';

const mostrarModalUpgrade = ref(false);
const planesDisponibles = ref([]);

const token = localStorage.getItem('token');
const negocios = ref([]);
const limiteNegocios = ref(1);
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const mostrarModalEdicion = ref(false);
const negocioEditando = ref({ id: '', nombre: '', telefono: '', email: '', slug: ''});

const mostrarModalCreacion = ref(false);
const nuevoNegocio = ref({ nombre: '', email: '', telefono: '', slug: '' });

const errores = reactive({});

const horariosDisponibles = [
    "00:00 - 01:00", "01:00 - 02:00", "02:00 - 03:00", "03:00 - 04:00",
    "04:00 - 05:00", "05:00 - 06:00", "06:00 - 07:00", "07:00 - 08:00",
    "08:00 - 09:00", "09:00 - 10:00", "10:00 - 11:00", "11:00 - 12:00",
    "12:00 - 13:00", "13:00 - 14:00", "14:00 - 15:00", "15:00 - 16:00",
    "16:00 - 17:00", "17:00 - 18:00", "18:00 - 19:00", "19:00 - 20:00",
    "20:00 - 21:00", "21:00 - 22:00", "22:00 - 23:00", "23:00 - 24:00"
];

const horarios = ref([]);
const horariosEdicion = ref([]);

const minutosDisponibles = ref([]);

// NUEVAS VARIABLES PARA EL MODAL DE SERVICIOS
const mostrarModalServicios = ref(false);
const mostrarModalFormServicio = ref(false);
const negocioActualServicios = ref(null);
const servicios = ref([]);
const busquedaServicio = ref('');
const esEditarServicio = ref(false);
const formularioServicio = reactive({ id: '', nombre: '', precio: '', duracion_minutos: '' });
const erroresServicio = reactive({});

// FILTRO DE BUSQUEDA EN TIEMPO REAL
const serviciosFiltrados = computed(() => {
    if (!busquedaServicio.value) return servicios.value;
    return servicios.value.filter(s => s.nombre.toLowerCase().includes(busquedaServicio.value.toLowerCase()));
});

function toggleHorario(hora) {
    const index = horarios.value.indexOf(hora);
    if (index === -1) {
        horarios.value.push(hora);
        horarios.value.sort();
    } else {
        horarios.value.splice(index, 1);
    }

    if(errores.horarios) delete errores.horarios;
}

function toggleHorarioEdicion(hora) {
    const index = horariosEdicion.value.indexOf(hora);
    if (index === -1) {
        horariosEdicion.value.push(hora);
        horariosEdicion.value.sort();
    } else {
        horariosEdicion.value.splice(index, 1);
    }

    if(errores.edicion_horarios) delete errores.edicion_horarios;
}

onMounted(() => {
    cargarNegocios();
    cargarPlanes();
});

const cargarPlanes = async () => {
    try {
        const response = await fetch('/api/planes');
        planesDisponibles.value = await response.json();
    } catch (error) {
        console.error("Error al cargar los planes", error);
    }
};

const intentarAccesoPremium = (nivelRequerido) => {
    if (planAdquirido.value.id < nivelRequerido) {
        mostrarModalUpgrade.value = true;
        return false;
    }
    return true;
};

const cargarNegocios = async () => {
    try {
        const response = await fetch('/api/mis-negocios', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();

        if(data.valid) {
            negocios.value = data.negocios;
            usuarioLoggeado.value = data.usuarioLoggeado;
            planAdquirido.value = data.planAdquirido;

            const limitesPorPlan = { 1: 1, 2: 3, 3: 6 };
            limiteNegocios.value = limitesPorPlan[data.planAdquirido.id] || 1;
        }
    } catch (error) {
        window.$toast.show('Error al cargar la tabla de negocios', 'danger', 4000);
    }
};

const abrirModalEdicion = (negocio) => {
    Object.keys(errores).forEach(key => delete errores[key]);
    negocioEditando.value = {
        id: negocio.id,
        nombre: negocio.nombre,
        telefono: negocio.telefono,
        email: negocio.email,
        slug: negocio.slug
    };
    horariosEdicion.value = negocio.horarios ? [...negocio.horarios] : [];
    mostrarModalEdicion.value = true;
};

const guardarEdicion = async () => {
    Object.keys(errores).forEach(key => delete errores[key]);
    let esValido = true;

    if (!negocioEditando.value.nombre.trim()) {
        errores.edicion_nombre = 'El nombre es obligatorio.';
        esValido = false;
    }
    if (!negocioEditando.value.telefono || negocioEditando.value.telefono.length !== 10) {
        errores.edicion_telefono = 'Debe contener exactamente 10 dígitos.';
        esValido = false;
    }
    if (horariosEdicion.value.length === 0) {
        errores.edicion_horarios = 'Debes seleccionar al menos un horario.';
        esValido = false;
    }

    if (!esValido) {
        window.$toast.show('Por favor revisa los campos en rojo', 'warning', 3000);
        return;
    }

    try {
        const response = await fetch(`/api/negocios/${negocioEditando.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                nombre: negocioEditando.value.nombre,
                telefono: negocioEditando.value.telefono,
                email: negocioEditando.value.email,
                horarios: horariosEdicion.value
            })
        });
        const data = await response.json();

        if(data.valid) {
            window.$toast.show(data.message, 'success', 4000);
            mostrarModalEdicion.value = false;
            cargarNegocios();
        }
    } catch (error) {
        window.$toast.show('Error al guardar los cambios', 'danger', 4000);
    }
};

const clickAgregarNegocio = () => {
    const creados = Number(negocios.value.length);
    const permitidos = Number(limiteNegocios.value);

    if (creados >= permitidos) {
        mostrarModalUpgrade.value = true;
        return;
    }

    nuevoNegocio.value = {
        nombre: '',
        email: usuarioLoggeado.value?.email || '',
        telefono: '',
        slug: ''
    };
    horarios.value = [];
    Object.keys(errores).forEach(key => delete errores[key]);

    mostrarModalCreacion.value = true;
};

const guardarNuevoNegocio = async () => {
    Object.keys(errores).forEach(key => delete errores[key]);
    let esValido = true;

    if (!nuevoNegocio.value.nombre.trim()) {
        errores.nombre = 'El nombre es obligatorio.';
        esValido = false;
    }

    if (!nuevoNegocio.value.telefono || nuevoNegocio.value.telefono.length !== 10) {
        errores.telefono = 'Debe contener exactamente 10 dígitos.';
        esValido = false;
    }

    if (horarios.value.length === 0) {
        errores.horarios = 'Debes seleccionar al menos un horario.';
        esValido = false;
    }

    if (planAdquirido.value.id == 3 && !nuevoNegocio.value.slug.trim()) {
        errores.slug = 'La URL personalizada es obligatoria.';
        esValido = false;
    }

    if (!esValido) {
        window.$toast.show('Por favor revisa los campos en rojo', 'warning', 3000);
        return;
    }

    try {
        const response = await fetch('/api/registrar-plan-negocio', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                plan: planAdquirido.value.id,
                nombre: nuevoNegocio.value.nombre,
                email: nuevoNegocio.value.email,
                telefono: nuevoNegocio.value.telefono,
                slug: nuevoNegocio.value.slug,
                horarios: horarios.value
            })
        });

        const data = await response.json();

        if (data.valid) {
            window.$toast.show(data.message, 'success', 4000);
            mostrarModalCreacion.value = false;
            cargarNegocios();
        } else {
            window.$toast.show(data.message, 'warning', 4000);
        }
    } catch (error) {
        window.$toast.show('Error al registrar el negocio', 'danger', 4000);
    }
};

// LOGICA COMPLETA DEL CRUD DE SERVICIOS
const abrirServicios = (negocio) => {
    negocioActualServicios.value = negocio;
    busquedaServicio.value = '';
    cargarServiciosNegocio();
    mostrarModalServicios.value = true;
};

const cargarServiciosNegocio = async () => {
    try {
        const response = await fetch(`/api/negocios/${negocioActualServicios.value.id}/servicios`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });
        const data = await response.json();
        if(data.valid) {
            servicios.value = data.servicios;
            minutosDisponibles.value = data.minutos_permitidos; // guardamos los minutos aquí
        }
    } catch (error) {
        console.error("Error al cargar servicios");
    }
};

const abrirFormularioServicio = (servicio = null) => {
    Object.keys(erroresServicio).forEach(key => delete erroresServicio[key]);

    if (servicio) {
        esEditarServicio.value = true;
        formularioServicio.id = servicio.id;
        formularioServicio.nombre = servicio.nombre;
        formularioServicio.precio = servicio.precio;
        formularioServicio.duracion_minutos = servicio.duracion_minutos;
    } else {
        esEditarServicio.value = false;
        formularioServicio.id = '';
        formularioServicio.nombre = '';
        formularioServicio.precio = '';
        formularioServicio.duracion_minutos = '';
    }

    // OCULTA EL PRIMER MODAL MIENTRAS SE MUESTRA EL SEGUNDO PARA EVITAR BUGS DE Z-INDEX
    mostrarModalServicios.value = false;
    mostrarModalFormServicio.value = true;
};

const cerrarFormularioServicio = () => {
    mostrarModalFormServicio.value = false;
    mostrarModalServicios.value = true;
};

const guardarServicio = async () => {
    Object.keys(erroresServicio).forEach(key => delete erroresServicio[key]);
    let esValido = true;

    if (!formularioServicio.nombre.trim()) {
        erroresServicio.nombre = 'El nombre es obligatorio.';
        esValido = false;
    }
    if (!formularioServicio.precio || formularioServicio.precio <= 0) {
        erroresServicio.precio = 'Ingresa un precio válido.';
        esValido = false;
    }
    if (!formularioServicio.duracion_minutos || formularioServicio.duracion_minutos <= 0) {
        erroresServicio.duracion = 'Ingresa una duración en minutos válida.';
        esValido = false;
    }

    if (!esValido) return;

    try {
        const endpoint = esEditarServicio.value
            ? `/api/servicios/${formularioServicio.id}`
            : `/api/servicios`;

        const method = esEditarServicio.value ? 'PUT' : 'POST';

        const response = await fetch(endpoint, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                id_negocio: negocioActualServicios.value.id,
                nombre: formularioServicio.nombre,
                precio: formularioServicio.precio,
                duracion_minutos: formularioServicio.duracion_minutos
            })
        });

        const data = await response.json();

        if(data.valid) {
            window.$toast.show(data.message, 'success', 3000);
            cerrarFormularioServicio();
            cargarServiciosNegocio();
        } else {
            window.$toast.show(data.message, 'warning', 3000);
        }
    } catch (error) {
        window.$toast.show('Error al guardar el servicio', 'danger', 3000);
    }
};

const eliminarServicio = async (id) => {
    if(!confirm("¿Estás seguro de que deseas eliminar este servicio?")) return;

    try {
        const response = await fetch(`/api/servicios/${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        });

        const data = await response.json();

        if(data.valid) {
            window.$toast.show('Servicio eliminado', 'success', 3000);
            cargarServiciosNegocio();
        }
    } catch (error) {
        window.$toast.show('Error al eliminar el servicio', 'danger', 3000);
    }
};

</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Gestión de Negocios</h4>
                <p class="text-muted small mb-0">
                    Plan actual: <span class="fw-bold text-primary">{{ planAdquirido?.nombre }}</span>
                    (Usando {{ negocios.length }} de {{ limiteNegocios }})
                </p>
            </div>

            <button
                @click="clickAgregarNegocio"
                class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center"
            >
                <span class="fs-5 me-2">+</span>
                Añadir Negocio
            </button>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Nombre</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Enlace (Slug)</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Correo</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Teléfono</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Horarios</th>
                        <th class="py-3 px-5 text-end text-secondary fw-semibold border-bottom-0">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="negocio in negocios" :key="negocio.id">
                        <td class="px-4 py-3 fw-bold text-dark">
                            <span class="fs-5 me-2">🏪</span>{{ negocio.nombre }}
                        </td>
                        <td class="px-4 py-3">
                            <a :href="`https://${negocio.slug}`" target="_blank" class="text-decoration-none text-primary">
                                {{ negocio.slug }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-muted">{{ negocio.email }}</td>
                        <td class="px-4 py-3 text-muted">{{ negocio.telefono }}</td>
                        <td class="px-4 py-3 text-muted">
                            <span class="badge bg-light text-secondary border">Múltiples turnos</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button @click="abrirModalEdicion(negocio)" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-2" title="Editar Información">
                                ✏️
                            </button>
                            <button @click="intentarAccesoPremium(1) ? abrirServicios(negocio) : null" class="btn btn-sm btn-outline-success rounded-circle p-2 me-2" title="Agregar Servicios">
                                📋
                            </button>
                        </td>
                    </tr>
                    <tr v-if="negocios.length === 0">
                        <td colspan="6" class="text-center py-5 text-muted">
                            Aún no tienes negocios registrados.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModalEdicion" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">✏️ Editar Negocio</h5>
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalEdicion = false"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre del Negocio</label>
                            <input type="text" v-model="negocioEditando.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores.edicion_nombre }">
                            <div class="invalid-feedback fw-medium">{{ errores.edicion_nombre }}</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">URL del Negocio</label>
                            <input type="text" v-model="negocioEditando.slug" class="form-control form-control-lg text-muted shadow-none" style="background-color: #e9ecef; border: 1px solid #dee2e6;" disabled>
                            <small class="text-muted mt-1 d-block">La URL pública no se puede modificar.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Correo Electrónico</label>
                            <input type="email" v-model="negocioEditando.email" class="form-control form-control-lg text-muted shadow-none" style="background-color: #e9ecef; border: 1px solid #dee2e6;" disabled>
                            <small class="text-muted mt-1 d-block">El correo vinculado no se puede cambiar.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Teléfono</label>
                            <input type="text" v-model="negocioEditando.telefono" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores.edicion_telefono }" maxlength="10" @input="negocioEditando.telefono = negocioEditando.telefono.replace(/\D/g, '')">
                            <div class="invalid-feedback fw-medium">{{ errores.edicion_telefono }}</div>
                        </div>

                        <div class="mb-3 mt-4 pt-3 border-top">
                            <label class="form-label fw-semibold text-secondary mb-2">
                                Horarios de Atención
                                <span class="text-muted small fw-normal ms-2">(Haz clic para agregar o quitar)</span>
                            </label>

                            <div class="border rounded-3 p-3 bg-light shadow-sm" :class="{'border-danger': errores.edicion_horarios}">
                                <div class="d-flex flex-wrap gap-2" style="max-height: 160px; overflow-y: auto;">
                                    <button
                                        v-for="hora in horariosDisponibles"
                                        :key="hora"
                                        type="button"
                                        class="btn btn-sm rounded-pill fw-medium transition-all"
                                        :class="horariosEdicion.includes(hora) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                        @click="toggleHorarioEdicion(hora)"
                                    >
                                        <span v-if="horariosEdicion.includes(hora)" class="me-1">✓</span>
                                        <span v-else class="me-1">🕒</span>
                                        {{ hora }}
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small fw-medium mt-2" v-if="errores.edicion_horarios">{{ errores.edicion_horarios }}</div>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="guardarEdicion">💾 Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalCreacion" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h4 class="modal-title fw-bold text-dark d-flex align-items-center">
                            <span class="fs-3 me-2">🏢</span> Registrar Nuevo Negocio
                        </h4>
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalCreacion = false"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre del Negocio</label>
                            <input type="text" v-model="nuevoNegocio.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores.nombre }" placeholder="Ej. Sucursal Centro">
                            <div class="invalid-feedback fw-medium">{{ errores.nombre }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Correo Electrónico</label>
                            <input type="email" v-model="nuevoNegocio.email" class="form-control form-control-lg text-muted shadow-none" style="background-color: #e9ecef; border: 1px solid #dee2e6;" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Teléfono de Notificaciones (WhatsApp)</label>
                            <input type="text" v-model="nuevoNegocio.telefono" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores.telefono }" maxlength="10" placeholder="10 dígitos" @input="nuevoNegocio.telefono = nuevoNegocio.telefono.replace(/\D/g, '')">
                            <div class="invalid-feedback fw-medium">{{ errores.telefono }}</div>
                        </div>

                        <div class="mb-3 mt-4 pt-3 border-top">
                            <label class="form-label fw-semibold text-secondary mb-2">
                                Horarios de Atención
                                <span class="text-muted small fw-normal ms-2">(Haz clic para agregar o quitar)</span>
                            </label>

                            <div class="border rounded-3 p-3 bg-light shadow-sm" :class="{'border-danger': errores.horarios}">
                                <div class="d-flex flex-wrap gap-2" style="max-height: 160px; overflow-y: auto;">
                                    <button
                                        v-for="hora in horariosDisponibles"
                                        :key="hora"
                                        type="button"
                                        class="btn btn-sm rounded-pill fw-medium transition-all"
                                        :class="horarios.includes(hora) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                        @click="toggleHorario(hora)"
                                    >
                                        <span v-if="horarios.includes(hora)" class="me-1">✓</span>
                                        <span v-else class="me-1">🕒</span>
                                        {{ hora }}
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small fw-medium mt-2" v-if="errores.horarios">{{ errores.horarios }}</div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <label class="form-label fw-semibold text-secondary d-flex align-items-center">
                                URL DEL NEGOCIO
                                <span v-if="planAdquirido?.id < 3" class="badge bg-warning text-dark ms-2 shadow-sm">
                                    ⭐ Plan Avanzado
                                </span>
                            </label>
                            <div class="input-group has-validation shadow-sm" @click="planAdquirido?.id < 3 ? intentarAccesoPremium(3) : null">
                                <span class="input-group-text bg-white border-end-0" :class="{'text-muted': planAdquirido?.id < 3}">
                                    www.agendavb/
                                </span>
                                <input
                                    type="text"
                                    v-model="nuevoNegocio.slug"
                                    class="form-control form-control-lg border-start-0"
                                    :class="{ 'is-invalid': errores.slug, 'bg-light text-muted': planAdquirido?.id < 3 }"
                                    placeholder="mi-nueva-sucursal"
                                    :readonly="planAdquirido?.id < 3"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.slug }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-center">
                        <button type="button" class="btn btn-primary fw-bold px-5 py-2 rounded-pill shadow-sm" @click="guardarNuevoNegocio">
                            💾 Guardar Negocio!
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalUpgrade" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.85); backdrop-filter: blur(5px);">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content border-0 shadow-lg rounded-4 bg-light">
                    <div class="modal-header border-bottom-0 pb-0 px-5 pt-5 text-center d-block position-relative">
                        <button type="button" class="btn-close position-absolute top-0 end-0 m-4 shadow-none" @click="mostrarModalUpgrade = false"></button>
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 80px; height: 80px;">
                            <span class="fs-1">🚀</span>
                        </div>
                        <h2 class="fw-bold text-dark mb-2">Lleva tu negocio al siguiente nivel</h2>
                        <p class="text-muted fs-5 mb-0">
                            Tu plan actual es <span class="fw-bold text-primary">{{ planAdquirido?.nombre }}</span>.
                            Mejora tu suscripción para desbloquear esta y más herramientas.
                        </p>
                    </div>
                    <div class="modal-body px-5 py-5">
                        <div class="row g-4 justify-content-center">
                            <PlanCard
                                v-for="plan in planesDisponibles"
                                :key="plan.id"
                                :plan="plan"
                                :current-plan-id="planAdquirido ? planAdquirido.id : null"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalServicios" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <div>
                            <h4 class="modal-title fw-bold text-dark d-flex align-items-center">
                                Gestión de Servicios
                            </h4>
                            <p class="text-muted small mb-0">Negocio: <span class="fw-bold text-primary">{{ negocioActualServicios?.nombre }}</span></p>
                        </div>
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalServicios = false"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <div class="input-group shadow-sm rounded-3">
                                    <span class="input-group-text bg-white border-end-0 text-muted">Buscador</span>
                                    <input type="text" v-model="busquedaServicio" class="form-control border-start-0 bg-white" placeholder="Filtrar servicios por nombre...">
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button class="btn btn-primary w-100 fw-bold shadow-sm" @click="abrirFormularioServicio()">
                                    + Añadir Servicio
                                </button>
                            </div>
                        </div>

                        <div class="table-responsive border rounded-3 shadow-sm mt-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Nombre</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Precio</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Duración</th>
                                    <th class="py-3 px-3 text-end text-secondary fw-semibold border-bottom-0">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="servicio in serviciosFiltrados" :key="servicio.id">
                                    <td class="px-3 py-3 fw-bold text-dark">{{ servicio.nombre }}</td>
                                    <td class="px-3 py-3 text-success fw-bold">${{ servicio.precio }}</td>
                                    <td class="px-3 py-3 text-muted">{{ servicio.duracion_minutos }} min</td>
                                    <td class="px-3 py-3 text-end">
                                        <button @click="abrirFormularioServicio(servicio)" class="btn btn-sm btn-outline-primary p-2 me-2">
                                            Editar
                                        </button>
                                        <button @click="eliminarServicio(servicio.id)" class="btn btn-sm btn-outline-danger p-2">
                                            Borrar
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="serviciosFiltrados.length === 0">
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        No se encontraron servicios.
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalFormServicio" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">
                            {{ esEditarServicio ? 'Editar Servicio' : 'Nuevo Servicio' }}
                        </h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarFormularioServicio()"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre del Servicio</label>
                            <input type="text" v-model="formularioServicio.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.nombre }">
                            <div class="invalid-feedback fw-medium">{{ erroresServicio.nombre }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Precio</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" step="0.01" v-model="formularioServicio.precio" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.precio }">
                                    <div class="invalid-feedback fw-medium">{{ erroresServicio.precio }}</div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Duración</label>
                                <div class="input-group">
                                    <select v-model="formularioServicio.duracion_minutos" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.duracion }">
                                        <option value="" disabled>Selecciona...</option>
                                        <option v-for="minuto in minutosDisponibles" :key="minuto" :value="minuto">
                                            {{ minuto }} minutos
                                        </option>
                                    </select>
                                    <div class="invalid-feedback fw-medium">{{ erroresServicio.duracion }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3 shadow-sm" @click="guardarServicio()">
                                {{ esEditarServicio ? 'Guardar Cambios' : 'Añadir Servicio' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
