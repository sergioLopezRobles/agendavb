<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import Layout from './Layout.vue';

// ── State ──────────────────────────────────────────────────────────────────────
const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const usuarios = ref([]);
const roles = ref([]);
const buscar = ref('');
const filtroRol = ref('');
const filtroEstatus = ref('');

const mostrarModalEditar = ref(false);
const guardando = ref(false);

const usuarioEditar = reactive({
    id: '',
    name: '',
    email: '',
    id_rol: '',
    estatus: ''
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

const cargarUsuarios = async () => {
    try {
        const response = await fetch('/api/admin/usuarios', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            usuarios.value = data.usuarios;
            roles.value = data.roles;
        }
    } catch (error) { window.$toast.show('Error al cargar usuarios', 'danger', 4000); }
};

// ── Computed ───────────────────────────────────────────────────────────────────
const usuariosFiltrados = computed(() => {
    return usuarios.value.filter(u => {
        const coincideTexto = u.name.toLowerCase().includes(buscar.value.toLowerCase()) ||
            u.email.toLowerCase().includes(buscar.value.toLowerCase()) ||
            (u.telefono && u.telefono.includes(buscar.value));

        const coincideRol = filtroRol.value === '' || (u.id_rol && u.id_rol.toString() === filtroRol.value);
        const coincideEstatus = filtroEstatus.value === '' || u.estatus.toString() === filtroEstatus.value;

        return coincideTexto && coincideRol && coincideEstatus;
    });
});

// ── Logic Modal & Actualizar ───────────────────────────────────────────────────
const abrirModalEditar = (usuario) => {
    usuarioEditar.id = usuario.id;
    usuarioEditar.name = usuario.name;
    usuarioEditar.email = usuario.email;
    usuarioEditar.id_rol = usuario.id_rol || 2; // Default Dueño si no tiene
    usuarioEditar.estatus = usuario.estatus;
    mostrarModalEditar.value = true;
};

const cerrarModalEditar = () => {
    if (!guardando.value) mostrarModalEditar.value = false;
};

const actualizarUsuario = async () => {
    if (!usuarioEditar.id_rol || usuarioEditar.estatus === '') {
        window.$toast.show('Debes seleccionar un rol y estatus.', 'warning', 3000);
        return;
    }

    guardando.value = true;

    try {
        const response = await fetch(`/api/admin/usuarios/${usuarioEditar.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            },
            body: JSON.stringify({
                id_rol: usuarioEditar.id_rol,
                estatus: usuarioEditar.estatus
            })
        });

        const data = await response.json();
        if (data.valid) {
            window.$toast.show(data.message, 'success', 4000);
            cerrarModalEditar();
            cargarUsuarios();
        } else {
            window.$toast.show(data.message, 'warning', 4000);
        }
    } catch (error) {
        window.$toast.show('Error al actualizar el usuario.', 'danger', 4000);
    } finally {
        guardando.value = false;
    }
};

onMounted(() => {
    cargarDatosMenu();
    cargarUsuarios();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Gestión de Usuarios y Roles</h4>
                <p class="text-muted small mb-0">Control total de accesos y cuentas del SaaS</p>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Total Usuarios</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ usuarios.length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-success h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Cuentas Activas</h6>
                        <h3 class="fw-bold mb-0 text-success">{{ usuarios.filter(u => u.estatus == 1).length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-danger h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Suspendidos</h6>
                        <h3 class="fw-bold mb-0 text-danger">{{ usuarios.filter(u => u.estatus == 0).length }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 rounded-4 border-start border-4 border-dark h-100">
                    <div class="card-body">
                        <h6 class="text-muted fw-bold mb-1">Administradores</h6>
                        <h3 class="fw-bold mb-0 text-dark">{{ usuarios.filter(u => u.id_rol == 1).length }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <div class="row g-3">
                    <div class="col-md-5">
                        <div class="input-group shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar por nombre, email o teléfono...">
                        </div>
                    </div>
                    <div class="col-md-7 text-end">
                        <select v-model="filtroRol" class="form-select w-auto d-inline-block shadow-sm border-2 bg-light me-3">
                            <option value="">Cualquier Rol</option>
                            <option v-for="rol in roles" :key="rol.id" :value="rol.id.toString()">
                                {{ rol.titulo }}
                            </option>
                        </select>
                        <select v-model="filtroEstatus" class="form-select w-auto d-inline-block shadow-sm border-2 bg-light">
                            <option value="">Cualquier Estatus</option>
                            <option value="1">Activos</option>
                            <option value="0">Suspendidos</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">ID</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Usuario</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Contacto</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Plan Actual</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Rol</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0 text-center">Estatus</th>
                            <th class="py-3 px-4 text-end text-secondary fw-semibold border-bottom-0">Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="u in usuariosFiltrados" :key="u.id" style="cursor: pointer;" @click="abrirModalEditar(u)">
                            <td class="px-4 py-3 fw-bold text-primary">#{{ u.id }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ u.name }}</div>
                                <div class="text-muted small">Registrado: {{ u.fecha_registro }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-dark small">✉️ {{ u.email }}</div>
                                <div class="text-muted small">📞 {{ u.telefono || 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge bg-light text-dark border">{{ u.plan_nombre || 'Sin Plan' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge" :class="u.id_rol == 1 ? 'bg-dark' : 'bg-primary'">
                                    {{ u.rol_nombre || 'Dueño' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge rounded-pill" :class="u.estatus == 1 ? 'bg-success' : 'bg-danger'">
                                    {{ u.estatus == 1 ? 'Activo' : 'Suspendido' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end">
                                <button class="btn btn-sm btn-light fw-bold text-primary rounded-3 px-3">
                                    Editar
                                </button>
                            </td>
                        </tr>
                        <tr v-if="usuariosFiltrados.length === 0">
                            <td colspan="7" class="text-center py-5 text-muted">No se encontraron usuarios.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalEditar" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">Editar Privilegios</h5>
                        <button type="button" class="btn-close shadow-none" :disabled="guardando" @click="cerrarModalEditar"></button>
                    </div>

                    <div class="modal-body px-4 py-4">
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <p class="text-muted small mb-1">Nombre: <span class="fw-bold text-dark">{{ usuarioEditar.name }}</span></p>
                            <p class="text-muted small mb-0">Email: <span class="fw-bold text-dark">{{ usuarioEditar.email }}</span></p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nivel de Acceso (Rol)</label>
                            <select v-model="usuarioEditar.id_rol" :disabled="guardando" class="form-select form-select-lg bg-light border-0 shadow-sm">
                                <option v-for="rol in roles" :key="rol.id" :value="rol.id">
                                    {{ rol.titulo }}
                                </option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Estado de la Cuenta</label>
                            <select v-model="usuarioEditar.estatus" :disabled="guardando" class="form-select form-select-lg bg-light border-0 shadow-sm">
                                <option :value="1">🟢 Activo (Puede iniciar sesión)</option>
                                <option :value="0">🔴 Suspendido (Acceso bloqueado)</option>
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-end">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="actualizarUsuario" :disabled="guardando">
                            <span v-if="guardando" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                            {{ guardando ? 'Guardando...' : 'Aplicar Cambios' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Layout>
</template>
