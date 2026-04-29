<script setup>
import { ref, onMounted, computed } from 'vue';
import Layout from '../componentes/Layout.vue';

const token = localStorage.getItem('token');
const usuarioLoggeado = ref(null);
const planAdquirido = ref(null);

const negocios = ref([]);
const buscar = ref('');

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
                                    <button class="btn btn-sm btn-outline-primary fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Servicios">
                                        <span>✂️ Servicios</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-info fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Citas">
                                        <span>📅 Citas</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-dark fw-bold rounded-3 px-3 d-flex align-items-center" title="Ver Estadísticas">
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
    </Layout>
</template>
