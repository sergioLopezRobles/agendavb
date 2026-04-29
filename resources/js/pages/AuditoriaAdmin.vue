<script setup>
import { ref, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';

const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const logs = ref([]);
const buscar = ref('');
const filtroTipo = ref('');

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

const cargarAuditoria = async () => {
    try {
        const response = await fetch('/api/admin/auditoria', {
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` }
        });
        const data = await response.json();
        if (data.valid) {
            logs.value = data.logs;
        }
    } catch (error) { window.$toast.show('Error al cargar la auditoría', 'danger', 4000); }
};

const logsFiltrados = computed(() => {
    return logs.value.filter(log => {
        const textoBusqueda = buscar.value.toLowerCase();
        const coincideTexto =
            log.usuario_nombre.toLowerCase().includes(textoBusqueda) ||
            log.email.toLowerCase().includes(textoBusqueda) ||
            (log.cambios?.mensaje && log.cambios.mensaje.toLowerCase().includes(textoBusqueda)) ||
            (log.cambios?.tabla && log.cambios.tabla.toLowerCase().includes(textoBusqueda));

        const coincideTipo = filtroTipo.value === '' || log.tipo_mensaje.toString() === filtroTipo.value;

        return coincideTexto && coincideTipo;
    });
});

onMounted(() => {
    cargarDatosMenu();
    cargarAuditoria();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Auditoría Global (Logs)</h4>
                <p class="text-muted small mb-0">Registro histórico de todas las acciones en la plataforma</p>
            </div>
        </div>

        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom-0 pt-4 pb-0 px-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group shadow-sm rounded-3">
                            <span class="input-group-text bg-white border-end-0 text-muted">🔍</span>
                            <input type="text" v-model="buscar" class="form-control border-start-0 bg-white" placeholder="Buscar usuario, correo, tabla o acción...">
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <select v-model="filtroTipo" class="form-select w-auto d-inline-block shadow-sm border-2 bg-light">
                            <option value="">Cualquier Tipo de Acción</option>
                            <option value="1">🟢 Creaciones</option>
                            <option value="2">🟡 Ediciones</option>
                            <option value="3">🔴 Eliminaciones</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 mt-3">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                        <tr>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Fecha y Hora</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Usuario Responsable</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Módulo Afectado</th>
                            <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Acción Detallada</th>
                            <th class="py-3 px-4 text-center text-secondary fw-semibold border-bottom-0">Tipo</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="log in logsFiltrados" :key="log.id">
                            <td class="px-4 py-3 text-muted small">{{ log.fecha }}</td>
                            <td class="px-4 py-3">
                                <div class="fw-bold text-dark">{{ log.usuario_nombre }}</div>
                                <div class="text-muted small">{{ log.email }}</div>
                            </td>
                            <td class="px-4 py-3 text-uppercase small fw-bold text-secondary">
                                {{ log.cambios?.tabla || 'Sistema' }}
                            </td>
                            <td class="px-4 py-3 text-dark">
                                {{ log.cambios?.mensaje || 'Acción no especificada' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="badge rounded-pill px-3"
                                      :class="{
                                        'bg-success bg-opacity-10 text-success border border-success': log.tipo_mensaje === 1,
                                        'bg-warning bg-opacity-10 text-dark border border-warning': log.tipo_mensaje === 2,
                                        'bg-danger bg-opacity-10 text-danger border border-danger': log.tipo_mensaje === 3,
                                        'bg-secondary bg-opacity-10 text-secondary border': log.tipo_mensaje === 0
                                    }">
                                    {{ log.tipo_mensaje === 1 ? 'Crear' : (log.tipo_mensaje === 2 ? 'Editar' : (log.tipo_mensaje === 3 ? 'Eliminar' : 'Otro')) }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="logsFiltrados.length === 0">
                            <td colspan="5" class="text-center py-5 text-muted">No se encontraron registros de auditoría.</td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </Layout>
</template>
