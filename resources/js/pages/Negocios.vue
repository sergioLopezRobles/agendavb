<script setup>
/**
 * Componente de página para la gestión de Negocios.
 *
 * Permite a los usuarios ver, crear, editar y gestionar sus negocios,
 * así como los servicios asociados a cada uno. También incluye
 * funcionalidades para generar códigos QR, copiar enlaces públicos y
 * gestionar planes de suscripción.
 */
import { onMounted, ref } from 'vue';
import QrcodeVue from 'qrcode.vue';

// Componentes
import Layout   from '../componentes/Layout.vue';
import PlanCard from '../componentes/PlanCard.vue';

// Composables
import { useNegocios  } from '../composables/useNegocios.js';
import { useServicios } from '../composables/useServicios.js';

// ── NEGOCIOS ──────────────────────────────────────────────────────────────────
const {
    token, negocios, limiteNegocios, usuarioLoggeado, planAdquirido,
    planesDisponibles, mostrarModalUpgrade, mostrarModalEdicion,
    mostrarModalCreacion, negocioEditando, nuevoNegocio, errores,
    horariosDisponibles, horarios, horariosEdicion, limiteTelefonos,
    cargarPlanes, cargarNegocios, intentarAccesoPremium,
    clickAgregarNegocio, guardarNuevoNegocio,
    abrirModalEdicion, guardarEdicion,
    toggleHorario, toggleHorarioEdicion,
    agregarTelefonoNuevo, quitarTelefonoNuevo,
    agregarTelefonoEdicion, quitarTelefonoEdicion
} = useNegocios();

// ── SERVICIOS ─────────────────────────────────────────────────────────────────
const {
    mostrarModalServicios, mostrarModalFormServicio, negocioActualServicios,
    servicios, busquedaServicio, esEditarServicio, minutosDisponibles,
    limiteServicios, totalServicios, formularioServicio, erroresServicio,
    serviciosFiltrados, minimoAnticipoPlan, anticipoMinimo, setAnticipoMinimo,
    abrirServicios, abrirFormularioServicio, cerrarFormularioServicio,
    guardarServicio, eliminarServicio
} = useServicios(token);

// ── LÓGICA DE COPIAR PORTAPAPELES Y CÓDIGO QR ─────────────────────────────────
const mostrarModalQR = ref(false);
const negocioActualQR = ref(null);

const copiarSlug = async (slug) => {
    const url = `https://${slug}`;

    // Si tiene HTTPS (Producción)
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(url);
            window.$toast.show('¡Enlace copiado al portapapeles! 📋', 'success', 3000);
        } catch (err) {
            window.$toast.show('Error al copiar el enlace', 'danger', 3000);
        }
    } else {
        // Método de respaldo para HTTP (Laragon local)
        try {
            const textArea = document.createElement("textarea");
            textArea.value = url;
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            document.execCommand('copy');
            document.body.removeChild(textArea);

            window.$toast.show('¡Enlace copiado al portapapeles! 📋', 'success', 3000);
        } catch (err) {
            window.$toast.show('Error al copiar el enlace', 'danger', 3000);
        }
    }
};

const abrirModalQR = (negocio) => {
    negocioActualQR.value = negocio;
    mostrarModalQR.value = true;
};

const descargarQR = () => {
    // Buscamos el canvas que genera la librería qrcode.vue
    const canvas = document.querySelector('.qr-container canvas');
    if (canvas) {
        const url = canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.href = url;
        link.download = `QR_${negocioActualQR.value.slug}.png`;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.$toast.show('¡Código QR descargado! ⬇️', 'success', 3000);
    }
};

// ── INIT ──────────────────────────────────────────────────────────────────────
onMounted(() => {
    cargarNegocios();
    cargarPlanes();
});
</script>

<template>
    <Layout :usuarioLoggeado="usuarioLoggeado" :planAdquirido="planAdquirido">

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                        HEADER DE LA PÁGINA                        -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-0 text-dark">Gestión de Negocios</h4>
                <p class="text-muted small mb-0">
                    Plan actual: <span class="fw-bold text-primary">{{ planAdquirido?.nombre }}</span>
                    (Usando {{ negocios.length }} de {{ limiteNegocios }})
                </p>
            </div>
            <button @click="clickAgregarNegocio" class="btn btn-primary rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center">
                <span class="fs-5 me-2">+</span> Añadir Negocio
            </button>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                        TABLA DE NEGOCIOS                          -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                    <tr>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Nombre</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Enlace (Público)</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">QR</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Correo</th>
                        <th class="py-3 px-4 text-secondary fw-semibold border-bottom-0">Teléfonos</th>
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
                            <div @click="copiarSlug(negocio.slug)" class="d-inline-flex align-items-center bg-primary bg-opacity-10 px-3 py-1 rounded-pill text-primary fw-medium copy-pill" title="Clic para copiar enlace">
                                <span class="me-2 fs-6">🔗</span> {{ negocio.slug }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <button @click="abrirModalQR(negocio)" class="btn btn-sm btn-outline-dark rounded-circle p-2" title="Generar código QR">📱</button>
                        </td>

                        <td class="px-4 py-3 text-muted">{{ negocio.email }}</td>
                        <td class="px-4 py-3 text-muted">
                            <span class="badge bg-info text-dark rounded-pill shadow-sm">{{ negocio.telefonos?.length || 0 }} Números</span>
                        </td>
                        <td class="px-4 py-3 text-muted">
                            <span class="badge bg-light text-secondary border">Múltiples turnos</span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <button @click="abrirModalEdicion(negocio)" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-2" title="Editar Información">✏️</button>
                            <button @click="intentarAccesoPremium(1) ? abrirServicios(negocio) : null" class="btn btn-sm btn-outline-success rounded-circle p-2 me-2" title="Agregar Servicios">📋</button>
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

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                           MODAL: CÓDIGO QR                        -->
        <!--      Se muestra al hacer clic en el botón de QR de un negocio     -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-if="mostrarModalQR" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content border-0 shadow-lg rounded-4 text-center p-4">
                    <div class="d-flex justify-content-end mb-2">
                        <button type="button" class="btn-close shadow-none" @click="mostrarModalQR = false"></button>
                    </div>

                    <div class="mb-3">
                        <h5 class="fw-bolder text-dark mb-1">Tu Código QR</h5>
                        <p class="text-muted small mb-0">Escanea para ir a tu agenda</p>
                        <p class="text-primary fw-bold small mt-1">{{ negocioActualQR?.nombre }}</p>
                    </div>

                    <div class="qr-container bg-white p-3 rounded-4 mx-auto shadow-sm mb-4 d-inline-block border">
                        <qrcode-vue :value="`https://${negocioActualQR?.slug}`" :size="200" level="H" foreground="#000000" />
                    </div>

                    <button @click="descargarQR" class="btn btn-primary w-100 py-2 fw-bold rounded-pill shadow-sm d-flex justify-content-center align-items-center">
                        <span class="fs-5 me-2">⬇️</span> Descargar PNG
                    </button>
                </div>
            </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                      MODAL: EDICIÓN DE NEGOCIO                    -->
        <!--   Se muestra al hacer clic en el botón de editar de un negocio    -->
        <!-- ───────────────────────────────────────────────────────────────── -->
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
                        </div>

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                                <label class="form-label fw-semibold text-secondary mb-0">Teléfonos de Contacto (Máx. {{ limiteTelefonos }})</label>
                                <button v-if="negocioEditando.telefonos.length < limiteTelefonos" type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" @click="agregarTelefonoEdicion">
                                    + Agregar otro
                                </button>
                            </div>
                            <div v-for="(tel, index) in negocioEditando.telefonos" :key="index" class="d-flex gap-2 mb-3 align-items-start">
                                <select v-model="tel.id_tipo" class="form-select bg-light border-0 shadow-sm" style="width: 140px; border-radius: 0.75rem;">
                                    <option :value="1">WhatsApp</option>
                                    <option :value="2">Fijo</option>
                                    <option :value="3">Telegram</option>
                                </select>
                                <div class="flex-grow-1">
                                    <input type="tel" v-model="tel.numero" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores['edicion_telefono_' + index] }" placeholder="10 dígitos" maxlength="10" @input="tel.numero = tel.numero.replace(/\D/g, '')" style="border-radius: 0.75rem;">
                                    <div class="invalid-feedback fw-medium px-2" v-if="errores['edicion_telefono_' + index]">{{ errores['edicion_telefono_' + index] }}</div>
                                </div>
                                <button v-if="negocioEditando.telefonos.length > 1" type="button" class="btn btn-light text-danger border-0 rounded-circle mt-1" @click="quitarTelefonoEdicion(index)">❌</button>
                            </div>
                        </div>

                        <div class="mb-3 mt-4 pt-3 border-top">
                            <label class="form-label fw-semibold text-secondary mb-2">Horarios de Atención</label>
                            <div class="border rounded-3 p-3 bg-light shadow-sm" :class="{'border-danger': errores.edicion_horarios}">
                                <div class="d-flex flex-wrap gap-2" style="max-height: 160px; overflow-y: auto;">
                                    <button v-for="hora in horariosDisponibles" :key="hora" type="button"
                                            class="btn btn-sm rounded-pill fw-medium"
                                            :class="horariosEdicion.includes(hora) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                            @click="toggleHorarioEdicion(hora)">
                                        <span class="me-1">{{ horariosEdicion.includes(hora) ? '✓' : '🕒' }}</span>{{ hora }}
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small fw-medium mt-2" v-if="errores.edicion_horarios">{{ errores.edicion_horarios }}</div>
                        </div>
                    </div>
                    <div class="modal-footer border-top-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3" @click="guardarEdicion">💾 Guardar Cambios</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                     MODAL: CREACIÓN DE NEGOCIO                    -->
        <!--      Se muestra al hacer clic en el botón "Añadir Negocio"        -->
        <!-- ───────────────────────────────────────────────────────────────── -->
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

                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 mt-4">
                                <label class="form-label fw-semibold text-secondary mb-0">Teléfonos de Contacto (Máx. {{ limiteTelefonos }})</label>
                                <button v-if="nuevoNegocio.telefonos.length < limiteTelefonos" type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" @click="agregarTelefonoNuevo">
                                    + Agregar otro
                                </button>
                            </div>
                            <div v-for="(tel, index) in nuevoNegocio.telefonos" :key="index" class="d-flex gap-2 mb-3 align-items-start">
                                <select v-model="tel.id_tipo" class="form-select bg-light border-0 shadow-sm" style="width: 140px; border-radius: 0.75rem;">
                                    <option :value="1">WhatsApp</option>
                                    <option :value="2">Fijo</option>
                                    <option :value="3">Telegram</option>
                                </select>
                                <div class="flex-grow-1">
                                    <input type="tel" v-model="tel.numero" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': errores['telefono_' + index] }" placeholder="10 dígitos" maxlength="10" @input="tel.numero = tel.numero.replace(/\D/g, '')" style="border-radius: 0.75rem;">
                                    <div class="invalid-feedback fw-medium px-2" v-if="errores['telefono_' + index]">{{ errores['telefono_' + index] }}</div>
                                </div>
                                <button v-if="nuevoNegocio.telefonos.length > 1" type="button" class="btn btn-light text-danger border-0 rounded-circle mt-1" @click="quitarTelefonoNuevo(index)">❌</button>
                            </div>
                        </div>

                        <div class="mb-3 mt-4 pt-3 border-top">
                            <label class="form-label fw-semibold text-secondary mb-2">Horarios de Atención</label>
                            <div class="border rounded-3 p-3 bg-light shadow-sm" :class="{'border-danger': errores.horarios}">
                                <div class="d-flex flex-wrap gap-2" style="max-height: 160px; overflow-y: auto;">
                                    <button v-for="hora in horariosDisponibles" :key="hora" type="button"
                                            class="btn btn-sm rounded-pill fw-medium"
                                            :class="horarios.includes(hora) ? 'btn-primary shadow-sm' : 'btn-outline-secondary bg-white text-dark'"
                                            @click="toggleHorario(hora)">
                                        <span class="me-1">{{ horarios.includes(hora) ? '✓' : '🕒' }}</span>{{ hora }}
                                    </button>
                                </div>
                            </div>
                            <div class="text-danger small fw-medium mt-2" v-if="errores.horarios">{{ errores.horarios }}</div>
                        </div>

                        <div class="col-md-12 mt-4">
                            <label class="form-label fw-semibold text-secondary d-flex align-items-center">
                                URL DEL NEGOCIO
                                <span v-if="planAdquirido?.id < 3" class="badge bg-warning text-dark ms-2 shadow-sm">⭐ Plan Avanzado</span>
                            </label>
                            <div class="input-group has-validation shadow-sm" @click="planAdquirido?.id < 3 ? intentarAccesoPremium(3) : null">
                                <span class="input-group-text bg-white border-end-0" :class="{'text-muted': planAdquirido?.id < 3}">www.agendavb/</span>
                                <input
                                    type="text"
                                    v-model="nuevoNegocio.slug"
                                    @input="nuevoNegocio.slug = nuevoNegocio.slug.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')"
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

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                   MODAL: UPGRADE DE PLAN                          -->
        <!-- Se muestra cuando el usuario intenta acceder a una función premium -->
        <!-- ───────────────────────────────────────────────────────────────── -->
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
                            <PlanCard v-for="plan in planesDisponibles" :key="plan.id" :plan="plan" :current-plan-id="planAdquirido ? planAdquirido.id : null" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--                   MODAL: GESTIÓN DE SERVICIOS                     -->
        <!-- Se muestra al hacer clic en el botón de servicios de un negocio   -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-if="mostrarModalServicios" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <div>
                            <h4 class="modal-title fw-bold text-dark">Gestión de Servicios</h4>
                            <p class="text-muted small mb-0">
                                Negocio: <span class="fw-bold text-primary">{{ negocioActualServicios?.nombre }}</span>
                                &nbsp;·&nbsp;
                                <span :class="limiteServicios !== null && totalServicios >= Number(limiteServicios) ? 'text-danger fw-bold' : 'text-muted'">
                                    {{ totalServicios }} de {{ limiteServicios !== null ? limiteServicios : '∞' }} servicios usados
                                </span>
                            </p>
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
                                <button class="btn btn-primary w-100 fw-bold shadow-sm" @click="abrirFormularioServicio()">+ Añadir Servicio</button>
                            </div>
                        </div>
                        <div class="table-responsive border rounded-3 shadow-sm mt-3">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                <tr>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Nombre</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Precio Total</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Anticipo Fijo</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Método</th>
                                    <th class="py-3 px-3 text-secondary fw-semibold border-bottom-0">Duración</th>
                                    <th class="py-3 px-3 text-end text-secondary fw-semibold border-bottom-0">Acciones</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="servicio in serviciosFiltrados" :key="servicio.id">
                                    <td class="px-3 py-3 fw-bold text-dark">{{ servicio.nombre }}</td>
                                    <td class="px-3 py-3 text-success fw-bold">${{ servicio.precio }}</td>
                                    <td class="px-3 py-3 text-primary fw-bold">
                                        {{ servicio.anticipo ? '$' + servicio.anticipo : '(Sin anticipo)' }}
                                    </td>
                                    <td class="px-3 py-3">
                                        <span v-if="servicio.tarjeta == '1'" class="badge bg-primary bg-opacity-10 text-primary rounded-pill">💳 Tarjeta</span>
                                        <span v-else class="badge bg-success bg-opacity-10 text-success rounded-pill">💵 Efectivo</span>
                                    </td>
                                    <td class="px-3 py-3 text-muted">{{ servicio.duracion_minutos }} min</td>
                                    <td class="px-3 py-3 text-end">
                                        <button @click="abrirFormularioServicio(servicio)" class="btn btn-sm btn-outline-primary p-2 me-2">Editar</button>
                                        <button @click="eliminarServicio(servicio.id)" class="btn btn-sm btn-outline-danger p-2">Borrar</button>
                                    </td>
                                </tr>
                                <tr v-if="serviciosFiltrados.length === 0">
                                    <td colspan="6" class="text-center py-4 text-muted">No se encontraron servicios.</td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!--               MODAL: FORMULARIO DE CREAR/EDITAR SERVICIO          -->
        <!-- Se muestra desde el modal de "Gestión de Servicios" al añadir o   -->
        <!-- editar un servicio.                                               -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div v-if="mostrarModalFormServicio" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h5 class="modal-title fw-bold text-dark">{{ esEditarServicio ? 'Editar Servicio' : 'Nuevo Servicio' }}</h5>
                        <button type="button" class="btn-close shadow-none" @click="cerrarFormularioServicio()"></button>
                    </div>
                    <div class="modal-body px-4 py-4">
                        <div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Nombre del Servicio</label>
                            <input type="text" v-model="formularioServicio.nombre" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.nombre }">
                            <div class="invalid-feedback fw-medium">{{ erroresServicio.nombre }}</div>
                        </div>

                        <div class="mb-4 bg-light p-3 rounded-3 border d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 fw-bold text-dark">Método de Pago Permitido</h6>
                                <small class="text-muted">Elige cómo cobrarás este servicio.</small>
                            </div>
                            <div class="form-check form-switch fs-4 mb-0">
                                <input class="form-check-input cursor-pointer" type="checkbox" role="switch" id="switchTarjeta"
                                       :checked="formularioServicio.tarjeta === '1'"
                                       @change="formularioServicio.tarjeta = $event.target.checked ? '1' : '0'">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary">Precio Total</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" step="0.01" v-model="formularioServicio.precio" class="form-control form-control-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.precio }">
                                    <div class="invalid-feedback fw-medium">{{ erroresServicio.precio }}</div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold text-secondary d-flex justify-content-between">
                                    <span>Pago de Anticipo</span>
                                    <span v-if="formularioServicio.precio && formularioServicio.tarjeta === '1'" class="badge bg-primary text-white cursor-pointer" @click="setAnticipoMinimo" style="cursor: pointer;" title="Autocompletar mínimo">Mín. ${{ anticipoMinimo.toFixed(2) }}</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">$</span>
                                    <input type="number" step="0.01" v-model="formularioServicio.anticipo"
                                           class="form-control form-control-lg bg-light border-0 shadow-sm"
                                           :class="{ 'is-invalid': erroresServicio.anticipo }"
                                           placeholder="Dejar en 0 si no requiere"
                                           :disabled="formularioServicio.tarjeta === '0'">
                                    <div class="invalid-feedback fw-medium">{{ erroresServicio.anticipo }}</div>
                                </div>
                                <small class="text-muted d-block mt-1">Opcional. Se guardará como sin anticipo si es 0.</small>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-semibold text-secondary">Duración de la cita</label>
                                <div class="input-group">
                                    <select v-model="formularioServicio.duracion_minutos" class="form-select form-select-lg bg-light border-0 shadow-sm" :class="{ 'is-invalid': erroresServicio.duracion }">
                                        <option value="" disabled>Selecciona...</option>
                                        <option v-for="minuto in minutosDisponibles" :key="minuto" :value="minuto">{{ minuto }} minutos</option>
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

<style scoped>
/*
 * Estilos para el efecto hover en el "pill" del enlace copiable.
 */
.copy-pill {
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
.copy-pill:hover {
    background-color: rgba(13, 110, 253, 0.2) !important;
    transform: scale(1.02);
}
</style>
