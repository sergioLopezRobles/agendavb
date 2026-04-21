<script setup>
import { useRouter } from 'vue-router'
import { ref, reactive, onMounted, computed } from "vue";
import { loadStripe } from '@stripe/stripe-js';

// ── EMITS & ROUTER ────────────────────────────────────────────────────────────
const emit = defineEmits(['planRegistrado']);
const router = useRouter()

// ── STORAGE ───────────────────────────────────────────────────────────────────
const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
const token = ref(localStorage.getItem('token'))

// ── PROPS ─────────────────────────────────────────────────────────────────────
const props = defineProps({
    plan: Object,
    currentPlanId: [Number, String, null]
})

// ── ESTADO DE MODALES ─────────────────────────────────────────────────────────
const mostrarModalRegistro = ref(false)
const mostrarModalConfirmacionUpgrade = ref(false)

// ── ESTADO DE CARGA ───────────────────────────────────────────────────────────
const procesandoPago = ref(false)
const textoCarga = ref('Iniciando conexión segura...')

// ── FORMULARIO DE REGISTRO ────────────────────────────────────────────────────
const formulario = reactive({
    nombre: '', email: '', slug: '', direccion: '',
    logo: null, logoPreview: '', // <-- AGREGADO
    telefonos: [{ id_tipo: 1, numero: '' }]
})

const errores = reactive({})

// ── STRIPE ────────────────────────────────────────────────────────────────────
const stripePromise = loadStripe('pk_test_51T8vO4CPQ2Qy65AdX1JGvoLFng7dtqBIWCaWAbVENn8JNGyQbYmC6hfFjdStUT2AAdRUOyLz6E35IqdlkWfglDQ5009NWtv54j');
let stripe = null;
let cardElement = null;
const errorTarjeta = ref('');

// ── HORARIOS ──────────────────────────────────────────────────────────────────
const horariosDisponibles = [
    "00:00 - 01:00", "01:00 - 02:00", "02:00 - 03:00", "03:00 - 04:00",
    "04:00 - 05:00", "05:00 - 06:00", "06:00 - 07:00", "07:00 - 08:00",
    "08:00 - 09:00", "09:00 - 10:00", "10:00 - 11:00", "11:00 - 12:00",
    "12:00 - 13:00", "13:00 - 14:00", "14:00 - 15:00", "15:00 - 16:00",
    "16:00 - 17:00", "17:00 - 18:00", "18:00 - 19:00", "19:00 - 20:00",
    "20:00 - 21:00", "21:00 - 22:00", "22:00 - 23:00", "23:00 - 24:00"
]
const horarios = ref([])

// ── COMPUTED ──────────────────────────────────────────────────────────────────
const limiteTelefonos = computed(() => {
    const limite = props.plan?.caracteristicas?.limite_telefonos_negocios;
    return limite ? parseInt(limite) : 3;
});

// ── MULTI-TELÉFONO Y LOGO ─────────────────────────────────────────────────────
const agregarTelefono = () => {
    if (formulario.telefonos.length < limiteTelefonos.value) {
        formulario.telefonos.push({ id_tipo: 1, numero: '' });
    } else {
        window.$toast.show(`Tu plan permite un máximo de ${limiteTelefonos.value} teléfonos.`, 'warning', 3000);
    }
};

const quitarTelefono = (index) => {
    if (formulario.telefonos.length > 1) {
        formulario.telefonos.splice(index, 1);
    }
};

const manejarLogoNuevo = (event) => {
    const file = event.target.files[0];
    if (file) {
        formulario.logo = file;
        formulario.logoPreview = URL.createObjectURL(file);
    } else {
        formulario.logo = null;
        formulario.logoPreview = '';
    }
};

// ── HORARIOS ──────────────────────────────────────────────────────────────────
function toggleHorario(hora) {
    const index = horarios.value.indexOf(hora)
    if (index === -1) {
        horarios.value.push(hora)
        horarios.value.sort()
    } else {
        horarios.value.splice(index, 1)
    }
    if(errores.horarios) delete errores.horarios
}

// ── LIFECYCLE ─────────────────────────────────────────────────────────────────
onMounted(() => {
    const userEmail = localStorage.getItem('userEmail')
    formulario.email = userEmail ? userEmail : ''
})

// ── LÓGICA DEL BOTÓN PRINCIPAL ────────────────────────────────────────────────
const eventPlanSeleccionado = () => {
    if(token.value != null){
        abrirModalRegistro()
    } else {
        localStorage.setItem('planSeleccionado', JSON.stringify(props.plan))
        router.push('/login')
    }
}

// ── MODAL: REGISTRO INICIAL ───────────────────────────────────────────────────
const abrirModalRegistro = () => {
    mostrarModalRegistro.value = true;
    errorTarjeta.value = '';
    procesandoPago.value = false;

    setTimeout(async () => {
        stripe = await stripePromise;
        const elements = stripe.elements();
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    '::placeholder': { color: '#aab7c4' }
                },
                invalid: { color: '#fa755a', iconColor: '#fa755a' }
            }
        });
        cardElement.mount('#card-element');
        cardElement.on('change', (event) => {
            errorTarjeta.value = event.error ? event.error.message : '';
        });
    }, 200);
}

// ── HELPERS DE FORMULARIO ─────────────────────────────────────────────────────
const cerrarModal = () => {
    mostrarModalRegistro.value = false
    mostrarModalConfirmacionUpgrade.value = false
    limpiarFormulario()
}

const limpiarFormulario = () => {
    formulario.nombre = ''
    formulario.slug = ''
    formulario.direccion = ''
    formulario.logo = null
    formulario.logoPreview = ''
    formulario.telefonos = [{ id_tipo: 1, numero: '' }]
    horarios.value = []
    Object.keys(errores).forEach(key => delete errores[key])
}

const validarFormulario = () => {
    Object.keys(errores).forEach(key => delete errores[key])
    let esValido = true

    if (!formulario.nombre.trim()) {
        errores.nombre = 'El nombre del negocio es obligatorio.'
        esValido = false
    }

    formulario.telefonos.forEach((tel, index) => {
        if (!tel.numero.trim() || tel.numero.length < 10) {
            errores[`telefono_${index}`] = 'El número debe tener 10 dígitos.';
            esValido = false;
        }
    });

    if (horarios.value.length === 0) {
        errores.horarios = 'Debes agregar al menos un horario de atención.'
        esValido = false
    }

    return esValido
}

// ── API: REGISTRO INICIAL + COBRO CON STRIPE ──────────────────────────────────
const formRegistrarPlanNegocio = async () => {
    if (!validarFormulario()) return

    let paymentId = '';

    // Lógica real de Stripe reactivada
    const { paymentMethod, error } = await stripe.createPaymentMethod({
        type: 'card',
        card: cardElement,
        billing_details: { name: formulario.nombre, email: formulario.email }
    });

    if (error) {
        errorTarjeta.value = error.message;
        return;
    }
    paymentId = paymentMethod.id;

    procesandoPago.value = true

    let step = 0
    const mensajesEfecto = [
        'Validando método de pago con Stripe...',
        'Construyendo tu panel de administración...',
        'Configurando tu agenda...',
        'Preparando módulos de servicios...',
        '¡Casi listo, afinando últimos detalles!'
    ]

    const intervaloCarga = setInterval(() => {
        step = (step + 1) % mensajesEfecto.length;
        textoCarga.value = mensajesEfecto[step];
    }, 1800);

    try {
        const formData = new FormData();
        formData.append('plan', props.plan.id);
        formData.append('nombre', formulario.nombre);
        formData.append('email', formulario.email);
        formData.append('slug', formulario.slug);
        formData.append('payment_method_id', paymentId);

        if (formulario.direccion) formData.append('direccion', formulario.direccion);
        if (formulario.logo) formData.append('logo', formulario.logo);

        formulario.telefonos.forEach((tel, index) => {
            formData.append(`telefonos[${index}][id_tipo]`, tel.id_tipo);
            formData.append(`telefonos[${index}][numero]`, tel.numero);
        });

        horarios.value.forEach((hora, index) => {
            formData.append(`horarios[${index}]`, hora);
        });

        const response = await fetch('/api/registrar-plan-negocio', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token.value}`
                // Fetch asigna automáticamente multipart/form-data con FormData
            },
            body: formData
        })

        const data = await response.json()

        if(data.valid){
            window.$toast.show('¡Suscripción exitosa y negocio registrado!', 'success', 5000)
            localStorage.setItem('userHasPlan', 'true')
            window.location.href = '/dashboard'
        } else {
            procesandoPago.value = false
            window.$toast.show(data.message, 'warning', 5000)
        }
    } catch (error) {
        procesandoPago.value = false
        console.error("Error en la petición:", error)
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    } finally {
        clearInterval(intervaloCarga)
    }
}

// ── API: UPGRADE DE PLAN (usuario ya tiene plan activo) ───────────────────────
const confirmarUpgrade = async () => {
    procesandoPago.value = true;
    let step = 0;
    const mensajesEfecto = [
        'Calculando cambio...',
        'Actualizando suscripción en Stripe...',
        'Desbloqueando funciones...',
        '¡Afinando detalles!'
    ];

    const intervaloCarga = setInterval(() => {
        step = (step + 1) % mensajesEfecto.length;
        textoCarga.value = mensajesEfecto[step];
    }, 1500);

    try {
        const response = await fetch('/api/upgrade-plan', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token.value}`
            },
            body: JSON.stringify({ plan: props.plan.id })
        })

        const data = await response.json()

        if(data.valid){
            window.$toast.show(data.message, 'success', 5000)
            setTimeout(() => { window.location.reload(); }, 1500);
        } else {
            procesandoPago.value = false
            window.$toast.show(data.message, 'warning', 5000)
        }
    } catch (error) {
        procesandoPago.value = false
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    } finally {
        clearInterval(intervaloCarga)
    }
}
</script>

<template>
    <div class="col-md-6 col-lg-4 mb-4">

        <div class="card saas-card h-100 border-0 shadow-sm rounded-3 position-relative bg-white overflow-hidden">
            <div class="position-absolute top-0 start-0 w-100 bg-primary" style="height: 4px;"></div>

            <div class="card-body p-4 p-md-5 d-flex flex-column">

                <div class="mb-4">
                    <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold text-uppercase mb-3 tracking-wide" style="letter-spacing: 0.5px;">
                        {{ plan.nombre }}
                    </span>
                    <h2 class="fw-bolder text-dark mb-2" style="font-size: 2.8rem;">
                        ${{ plan.caracteristicas?.precio }}
                        <span class="text-muted fs-5 fw-normal">/mes</span>
                    </h2>
                    <p class="text-secondary mb-0" style="font-size: 0.95rem;">{{ plan.caracteristicas?.descripcion }}</p>
                </div>

                <hr class="text-muted opacity-10 mb-4">

                <ul class="list-unstyled mb-4 flex-grow-1">
                    <li class="mb-3 d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">✓</div>
                        <div>
                            <span class="d-block text-dark fw-medium">Intervalo de citas</span>
                            <span class="text-muted small">Cada {{ plan.caracteristicas?.intervalo_citas_minutos }} min</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">✓</div>
                        <div>
                            <span class="d-block text-dark fw-medium">Recordatorios automáticos</span>
                            <span class="text-muted small">{{ plan.caracteristicas?.recordatorio_minutos }} min antes de la cita</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">✓</div>
                        <div>
                            <span class="d-block text-dark fw-medium">Notificaciones WhatsApp</span>
                            <span class="text-muted small">{{ plan.caracteristicas?.whatsapp_creditos_iniciales }} créditos mensuales</span>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="card-footer bg-transparent border-0 p-4 pt-0">
                <button v-if="currentPlanId == plan.id" class="btn btn-light text-muted w-100 py-3 fw-bold fs-6 rounded-pill border" disabled>Plan actual activado</button>
                <button v-else-if="currentPlanId != null && plan.id < currentPlanId" class="btn btn-light text-muted w-100 py-3 fw-bold fs-6 rounded-pill border" disabled title="No puedes bajar a un plan inferior directamente">Plan inferior</button>
                <button v-else-if="currentPlanId != null && plan.id > currentPlanId" @click="mostrarModalConfirmacionUpgrade = true" class="btn btn-outline-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn">Mejorar a este plan</button>
                <button v-else-if="token && planGuardado" @click="abrirModalRegistro" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn shadow-sm">Registrar Negocio</button>
                <button v-else @click="eventPlanSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn shadow-sm">Comenzar ahora</button>
            </div>
        </div>

        <div v-if="mostrarModalRegistro" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4 px-md-5 pt-md-5">
                        <div v-if="!procesandoPago">
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2">Paso final</span>
                            <h3 class="modal-title fw-bolder text-dark">Configura tu negocio</h3>
                        </div>
                        <button v-if="!procesandoPago" type="button" class="btn-close shadow-none" @click="cerrarModal"></button>
                    </div>

                    <div class="modal-body px-4 py-4 px-md-5">

                        <div v-if="procesandoPago" class="d-flex flex-column align-items-center justify-content-center py-5 my-4 text-center">
                            <div class="position-relative mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center pulse-animation" style="width: 100px; height: 100px;">
                                    <span class="fs-1">🚀</span>
                                </div>
                                <div class="spinner-border text-primary position-absolute top-0 start-0" style="width: 100px; height: 100px; border-width: 0.25rem;" role="status"></div>
                            </div>
                            <h4 class="fw-bolder text-dark mb-2 text-fade-in" :key="textoCarga">{{ textoCarga }}</h4>
                        </div>

                        <div v-else>
                            <div class="d-flex justify-content-between align-items-center p-4 rounded-4 mb-4 border" style="background-color: #f8f9fa;">
                                <div>
                                    <p class="text-muted small fw-bold mb-1 text-uppercase">Resumen de compra</p>
                                    <h5 class="fw-bold text-dark mb-0">Plan {{ plan.nombre }}</h5>
                                </div>
                                <div class="text-end">
                                    <h4 class="fw-bold mb-0 text-primary">${{ plan.caracteristicas?.precio }} <span class="text-muted fs-6 fw-normal">/mes</span></h4>
                                </div>
                            </div>

                            <form @submit.prevent="formRegistrarPlanNegocio">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Nombre de tu negocio</label>
                                        <input type="text" v-model="formulario.nombre" class="form-control form-control-lg bg-light border-0 px-4" style="border-radius: 0.75rem;" :class="{ 'is-invalid': errores.nombre }" placeholder="Ej. Clínica Dental Vista Boreal">
                                        <div class="invalid-feedback fw-medium px-2">{{ errores.nombre }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Correo del titular</label>
                                        <input type="email" v-model="formulario.email" class="form-control form-control-lg text-muted px-4 border-0" style="background-color: #e9ecef; border-radius: 0.75rem;" disabled>
                                    </div>
                                </div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Dirección (Opcional)</label>
                                        <input type="text" v-model="formulario.direccion" class="form-control form-control-lg bg-light border-0 px-4" style="border-radius: 0.75rem;" placeholder="Ej. Av. Principal #123">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Logo (Opcional)</label>
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="flex-shrink-0">
                                                <img v-if="formulario.logoPreview" :src="formulario.logoPreview" alt="Preview" class="rounded-circle object-fit-cover shadow-sm border border-primary border-2" style="width: 48px; height: 48px;">
                                                <div v-else class="rounded-circle d-flex align-items-center justify-content-center bg-light text-muted border shadow-sm" style="width: 48px; height: 48px; font-size: 0.8rem;">Sin foto</div>
                                            </div>
                                            <div class="flex-grow-1">
                                                <input type="file" @change="manejarLogoNuevo" class="form-control bg-light border-0 shadow-sm" accept="image/jpeg, image/png, image/jpg" style="border-radius: 0.75rem;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4 pt-2">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <label class="form-label fw-bold text-dark small text-uppercase tracking-wide mb-0">Teléfonos de Contacto (Máx. {{ limiteTelefonos }})</label>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill fw-bold" @click="agregarTelefono" v-if="formulario.telefonos.length < limiteTelefonos">+ Agregar otro</button>
                                    </div>
                                    <div v-for="(tel, index) in formulario.telefonos" :key="index" class="d-flex gap-2 mb-3 align-items-start">
                                        <select v-model="tel.id_tipo" class="form-select bg-light border-0" style="width: 140px; border-radius: 0.75rem;">
                                            <option :value="1">WhatsApp</option>
                                            <option :value="2">Fijo</option>
                                            <option :value="3">Telegram</option>
                                        </select>
                                        <div class="flex-grow-1">
                                            <input type="tel" v-model="tel.numero" class="form-control bg-light border-0 px-3" :class="{ 'is-invalid': errores['telefono_' + index] }" placeholder="10 dígitos" maxlength="10" @input="tel.numero = tel.numero.replace(/\D/g, '')" style="border-radius: 0.75rem;">
                                            <div class="invalid-feedback fw-medium px-2" v-if="errores['telefono_' + index]">{{ errores['telefono_' + index] }}</div>
                                        </div>
                                        <button type="button" class="btn btn-light text-danger border-0 rounded-circle" @click="quitarTelefono(index)" v-if="formulario.telefonos.length > 1">❌</button>
                                    </div>
                                </div>

                                <div class="mb-4 pt-2">
                                    <label class="form-label fw-bold text-dark small text-uppercase tracking-wide mb-3">Disponibilidad de horarios</label>
                                    <div class="border rounded-4 p-4 bg-white shadow-sm" :class="{'border-danger': errores.horarios}">
                                        <div class="d-flex flex-wrap gap-2" style="max-height: 180px; overflow-y: auto;">
                                            <button v-for="hora in horariosDisponibles" :key="hora" type="button" class="btn btn-sm rounded-pill fw-medium transition-all px-3 py-2 border" :class="horarios.includes(hora) ? 'btn-primary border-primary shadow-sm text-white' : 'btn-light border-light text-secondary'" @click="toggleHorario(hora)">
                                                <span v-if="horarios.includes(hora)" class="me-1">✓</span>{{ hora }}
                                            </button>
                                        </div>
                                    </div>
                                    <div class="text-danger small fw-medium mt-2 px-2" v-if="errores.horarios">{{ errores.horarios }}</div>
                                </div>

                                <div v-if="plan.id == 3" class="mb-4 pt-2">
                                    <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Enlace personalizado</label>
                                    <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                                        <span class="input-group-text bg-light border-0 text-muted px-4">www.agendavb/</span>
                                        <input type="text" v-model="formulario.slug" @input="formulario.slug = formulario.slug.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '')" class="form-control border-0 bg-light" placeholder="tu-marca-aqui" required>
                                    </div>
                                </div>

                                <div class="mb-4 pt-2 border-top">
                                    <label class="form-label fw-bold text-dark small text-uppercase tracking-wide mb-3">
                                        💳 Detalles de pago
                                    </label>
                                    <div class="p-3 bg-light border" style="border-radius: 0.75rem;">
                                        <div id="card-element" class="w-100"></div>
                                    </div>
                                    <div class="text-danger small fw-medium mt-2 px-2" v-if="errorTarjeta">
                                        {{ errorTarjeta }}
                                    </div>
                                </div>

                                <div class="mt-5 d-flex justify-content-end gap-2">
                                    <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-pill shadow-sm saas-btn">
                                        Confirmar y suscribirme
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="mostrarModalConfirmacionUpgrade" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h4 class="modal-title fw-bolder text-dark" v-if="!procesandoPago">Confirmar Cambio de Plan</h4>
                        <button v-if="!procesandoPago" type="button" class="btn-close shadow-none" @click="cerrarModal"></button>
                    </div>
                    <div class="modal-body px-4 py-4 text-center">
                        <div v-if="procesandoPago" class="d-flex flex-column align-items-center justify-content-center py-4">
                            <div class="position-relative mb-4">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center pulse-animation" style="width: 80px; height: 80px;"><span class="fs-2">🚀</span></div>
                                <div class="spinner-border text-primary position-absolute top-0 start-0" style="width: 80px; height: 80px; border-width: 0.25rem;" role="status"></div>
                            </div>
                            <h5 class="fw-bolder text-dark mb-2 text-fade-in" :key="textoCarga">{{ textoCarga }}</h5>
                            <p class="text-muted small">No cierres esta ventana.</p>
                        </div>
                        <div v-else>
                            <div class="bg-primary bg-opacity-10 p-4 rounded-4 mb-4">
                                <p class="text-muted small fw-bold text-uppercase mb-1">Estás a punto de adquirir el</p>
                                <h3 class="fw-bolder text-primary mb-0">Plan {{ plan.nombre }}</h3>
                                <div class="fs-5 text-dark fw-bold mt-2">${{ plan.caracteristicas?.precio }}/mes</div>
                            </div>
                            <p class="text-secondary small mb-4">Se te calculará automáticamente el tiempo que no usaste de tu plan actual y te cobrará únicamente la diferencia exacta hasta el día de hoy.</p>
                            <div class="d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-light fw-bold px-4 py-2 rounded-pill" @click="cerrarModal">Cancelar</button>
                                <button type="button" class="btn btn-primary fw-bold px-4 py-2 rounded-pill saas-btn shadow-sm" @click="confirmarUpgrade">Aceptar y Cambiar Plan</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.saas-card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
.saas-card:hover { transform: translateY(-6px); box-shadow: 0 1rem 3rem rgba(0,0,0,0.1) !important; }
.saas-btn { transition: all 0.2s ease-in-out; }
.saas-btn:hover { transform: scale(1.02); }
.tracking-wide { letter-spacing: 0.05em; }
.pulse-animation { animation: pulse-ring 2s infinite cubic-bezier(0.215, 0.61, 0.355, 1); }
@keyframes pulse-ring {
    0%   { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7); }
    70%  { transform: scale(1);    box-shadow: 0 0 0 15px rgba(13, 110, 253, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(13, 110, 253, 0); }
}
.text-fade-in { animation: fadeIn 0.5s ease-in-out; }
@keyframes fadeIn {
    0%   { opacity: 0; transform: translateY(5px); }
    100% { opacity: 1; transform: translateY(0); }
}
::-webkit-scrollbar { width: 6px; }
::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
</style>
