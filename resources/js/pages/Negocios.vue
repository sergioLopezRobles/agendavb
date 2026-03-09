<script setup>
import { ref, onMounted } from 'vue';
import Layout from '../componentes/Layout.vue';

const token = localStorage.getItem('token');
const negocios = ref([]);
const limiteNegocios = ref(1); // Por defecto 1
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

// Variables para el Modal de Edición
const mostrarModalEdicion = ref(false);
// 👇 Agregamos 'slug' a la edición
const negocioEditando = ref({ id: '', nombre: '', telefono: '', email: '', slug: ''});

// Variables para el Modal de Creación
const mostrarModalCreacion = ref(false);
// 👇 Agregamos 'slug' a la creación
const nuevoNegocio = ref({ nombre: '', email: '', telefono: '', hora_inicio: '', hora_fin: '', slug: '' });

onMounted(() => {
    cargarNegocios();
});

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

// --- LÓGICA DE EDICIÓN ---
const abrirModalEdicion = (negocio) => {
    negocioEditando.value = {
        id: negocio.id,
        nombre: negocio.nombre,
        telefono: negocio.telefono,
        email: negocio.email,
        slug: negocio.slug // 👇 Pasamos el slug actual para que se vea
    };
    mostrarModalEdicion.value = true;
};

const guardarEdicion = async () => {
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
                email: negocioEditando.value.email
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

// --- LÓGICA DE CREACIÓN Y LÍMITES DEL PLAN ---
const clickAgregarNegocio = () => {
    const creados = Number(negocios.value.length);
    const permitidos = Number(limiteNegocios.value);

    if (creados >= permitidos) {
        window.$toast.show(`Tu plan solo te permite tener ${permitidos} negocio(s).`, 'warning', 5000);
        return;
    }

    nuevoNegocio.value = {
        nombre: '',
        email: usuarioLoggeado.value?.email || '',
        telefono: '',
        hora_inicio: '',
        hora_fin: '',
        slug: '' // Limpiamos el slug al abrir
    };
    mostrarModalCreacion.value = true;
};

const guardarNuevoNegocio = async () => {
    if (!nuevoNegocio.value.nombre || !nuevoNegocio.value.hora_inicio || !nuevoNegocio.value.hora_fin) {
        window.$toast.show('Por favor llena los campos obligatorios', 'warning', 3000);
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
                hora_inicio: nuevoNegocio.value.hora_inicio,
                hora_fin: nuevoNegocio.value.hora_fin,
                slug: nuevoNegocio.value.slug // 👇 Enviamos el slug al backend
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
                class="btn rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center"
                :class="negocios.length >= limiteNegocios ? 'btn-secondary' : 'btn-primary'"
                :disabled="negocios.length >= limiteNegocios"
            >
                <span class="fs-5 me-2" v-if="negocios.length < limiteNegocios">+</span>
                <span class="fs-5 me-2" v-else>🔒</span>
                {{ negocios.length >= limiteNegocios ? 'Límite Alcanzado' : 'Añadir Negocio' }}
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
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Horario</th>
                        <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Acciones</th>
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
                            {{ negocio.hora_inicio }} - {{ negocio.hora_fin }}
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button @click="abrirModalEdicion(negocio)" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-2" title="Editar Información">
                                ✏️
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
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">✏️ Editar Negocio</h5>
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalEdicion = false"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre del Negocio</label>
                            <input type="text" v-model="negocioEditando.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm">
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
                            <input type="text" v-model="negocioEditando.telefono" class="form-control form-control-lg bg-light border-0 shadow-sm" maxlength="10">
                            <small class="text-muted mt-1">Recuerda ingresar 10 dígitos.</small>
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
                            <input type="text" v-model="nuevoNegocio.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm" placeholder="Ej. Sucursal Centro">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Correo Electrónico</label>
                            <input type="email" v-model="nuevoNegocio.email" class="form-control form-control-lg text-muted shadow-none" style="background-color: #e9ecef; border: 1px solid #dee2e6;" disabled>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Teléfono de Notificaciones (WhatsApp)</label>
                            <input type="text" v-model="nuevoNegocio.telefono" class="form-control form-control-lg bg-light border-0 shadow-sm" maxlength="10" placeholder="10 dígitos">
                        </div>

                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Hora de Apertura</label>
                                <input type="time" v-model="nuevoNegocio.hora_inicio" class="form-control form-control-lg bg-light border-0 shadow-sm">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Hora de Cierre</label>
                                <input type="time" v-model="nuevoNegocio.hora_fin" class="form-control form-control-lg bg-light border-0 shadow-sm">
                            </div>
                        </div>

                        <div v-if="planAdquirido?.id == 3" class="col-md-12 mt-4">
                            <label class="form-label fw-semibold text-secondary">URL DEL NEGOCIO</label>
                            <div class="input-group has-validation shadow-sm">
                                <span class="input-group-text bg-white border-end-0">www.agendavb/</span>
                                <input type="text" v-model="nuevoNegocio.slug" class="form-control form-control-lg bg-light border-start-0" placeholder="mi-nueva-sucursal">
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
    </Layout>
</template>
