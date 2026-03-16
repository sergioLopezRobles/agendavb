<script setup>
import { useRouter } from 'vue-router'
import { ref, reactive, onMounted } from "vue";
import { loadStripe } from '@stripe/stripe-js';

const emit = defineEmits(['planRegistrado']);

const router = useRouter()
const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
const token = ref(localStorage.getItem('token'))

const props = defineProps({
    plan: Object,
    currentPlanId: [Number, String, null]
})

const mostrarModal = ref(false)

const codigoEnviado = ref(true)
const numeroVerificado = ref(true)

const formulario = reactive({
    nombre: '',
    telefono: '',
    email: '',
    slug: ''
})

const errores = reactive({})

const stripePromise = loadStripe('pk_test_51T8vO4CPQ2Qy65AdX1JGvoLFng7dtqBIWCaWAbVENn8JNGyQbYmC6hfFjdStUT2AAdRUOyLz6E35IqdlkWfglDQ5009NWtv54j');
let stripe = null;
let cardElement = null;
const errorTarjeta = ref('');

const horariosDisponibles = [
    "00:00 - 01:00", "01:00 - 02:00", "02:00 - 03:00", "03:00 - 04:00",
    "04:00 - 05:00", "05:00 - 06:00", "06:00 - 07:00", "07:00 - 08:00",
    "08:00 - 09:00", "09:00 - 10:00", "10:00 - 11:00", "11:00 - 12:00",
    "12:00 - 13:00", "13:00 - 14:00", "14:00 - 15:00", "15:00 - 16:00",
    "16:00 - 17:00", "17:00 - 18:00", "18:00 - 19:00", "19:00 - 20:00",
    "20:00 - 21:00", "21:00 - 22:00", "22:00 - 23:00", "23:00 - 24:00"
]

const horarios = ref([])

// NUEVA FUNCIÓN: Agrega o quita el horario al darle clic (Toggle)
function toggleHorario(hora) {
    const index = horarios.value.indexOf(hora)
    if (index === -1) {
        horarios.value.push(hora)
        horarios.value.sort() // Mantiene las horas ordenadas
    } else {
        horarios.value.splice(index, 1)
    }

    if(errores.horarios) delete errores.horarios
}

onMounted(() => {
    const userEmail = localStorage.getItem('userEmail')
    formulario.email = userEmail ? userEmail : ''
})

const eventPlanSeleccionado = () => {
    if(token.value != null){
        abrirModal()
    } else {
        localStorage.setItem('planSeleccionado', JSON.stringify(props.plan))
        router.push('/login')
    }
}

const abrirModal = () => {
    mostrarModal.value = true;
    errorTarjeta.value = '';

    setTimeout(async () => {
        stripe = await stripePromise;
        const elements = stripe.elements();

        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif','::placeholder': { color: '#aab7c4' },
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

const cerrarModal = () => {
    mostrarModal.value = false
    limpiarFormulario()
}

const limpiarFormulario = () => {
    formulario.nombre = ''
    formulario.telefono = ''
    formulario.slug = ''
    horarios.value = []
    codigoEnviado.value = false
    numeroVerificado.value = false
    Object.keys(errores).forEach(key => delete errores[key])
}

const validarFormulario = () => {
    Object.keys(errores).forEach(key => delete errores[key])
    let esValido = true

    if (!formulario.nombre.trim()) {
        errores.nombre = 'El nombre del negocio es obligatorio.'
        esValido = false
    }

    if (!formulario.telefono.trim() || formulario.telefono.length !== 10) {
        errores.telefono = 'Debe contener exactamente 10 dígitos numéricos.'
        esValido = false
    }

    if (horarios.value.length === 0) {
        errores.horarios = 'Debes agregar al menos un horario de atención.'
        esValido = false
    }

    return esValido
}

const formRegistrarPlanNegocio = async () => {
    if (!validarFormulario()) return

    try{
        const response = await fetch('/api/registrar-plan-negocio',{
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json',
                'Authorization': `Bearer ${token.value}`
            },
            body: JSON.stringify({
                plan: props.plan.id,
                nombre: formulario.nombre,
                email: formulario.email,
                telefono: formulario.telefono,
                slug: formulario.slug,
                horarios: horarios.value,
            })
        })

        const data = await response.json()

        if(data.valid){
            window.$toast.show(data.message, 'success', 5000)
            cerrarModal()
            window.location.reload()
        }else{
            window.$toast.show(data.message, 'warning', 5000)
        }
    }catch (error) {
        console.error("Error en la petición:", error)
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    }
}
</script>

<template>
    <div class="col-md-6 col-lg-4">

        <div class="card shadow-lg border-0 rounded-4 h-100">

            <div class="card-body p-4 p-md-5 d-flex flex-column text-center">

                <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center mx-auto mb-4" style="width: 60px; height: 60px;">
                    <span class="fs-2 text-primary">🏷️</span>
                </div>

                <h4 class="card-title fw-bold mb-2">
                    {{ plan.nombre }}
                </h4>

                <h2 class="text-primary fw-bold mb-2 display-6">
                    ${{ plan.caracteristicas?.precio }}
                    <span class="text-muted fs-6 fw-normal">/mes</span>
                </h2>

                <p class="text-secondary mb-4">{{ plan.caracteristicas?.descripcion }}</p>

                <hr class="text-muted opacity-25 mb-4">

                <ul class="list-unstyled text-start mb-4 flex-grow-1">
                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">📅</span>
                        <span class="text-secondary fw-medium">Intervalo: {{ plan.caracteristicas?.intervalo_citas_minutos }} min</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">⏰</span>
                        <span class="text-secondary fw-medium">Recordatorio: {{ plan.caracteristicas?.recordatorio_minutos }} min antes</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">💬</span>
                        <span class="text-secondary fw-medium">WhatsApp: {{ plan.caracteristicas?.whatsapp_creditos_iniciales }} créditos</span>
                    </li>
                </ul>

            </div>

            <div class="card-footer bg-transparent border-0 p-4 pt-0">
                <button v-if="currentPlanId == plan.id" class="btn btn-secondary w-100 py-3 fw-bold fs-6 rounded-3" disabled>
                    Este es tu plan actual
                </button>

                <button v-else-if="currentPlanId != null" @click="eventPlanSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3">
                    Seleccionar plan
                </button>

                <button v-else-if="token && planGuardado" @click="abrirModal" class="btn btn-secondary w-100 py-3 fw-bold fs-6 rounded-3">
                    Registrar Negocio
                </button>

                <button v-else @click="eventPlanSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3">
                    Seleccionar plan
                </button>
            </div>

        </div>

        <div v-if="mostrarModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                        <h4 class="modal-title fw-bold text-dark d-flex align-items-center">
                            <span class="fs-3 me-2">🏢</span> Registrar Negocio
                        </h4>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModal"></button>
                    </div>

                    <div class="modal-body px-4 py-4">

                        <div class="d-flex justify-content-between align-items-center bg-primary bg-opacity-10 p-3 rounded-3 mb-4 border border-primary border-opacity-25">
                            <div>
                                <span class="badge bg-primary mb-1">Plan Seleccionado</span>
                                <h5 class="fw-bold text-primary mb-0">{{ plan.nombre }}</h5>
                            </div>
                            <div class="text-end">
                                <h4 class="fw-bold mb-0 text-dark">${{ plan.caracteristicas?.precio }} <small class="text-muted fs-6 fw-normal">/mes</small></h4>
                            </div>
                        </div>

                        <form @submit.prevent="formRegistrarPlanNegocio">

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Nombre del Negocio</label>
                                <input
                                    type="text"
                                    v-model="formulario.nombre"
                                    class="form-control form-control-lg bg-light border-0 shadow-sm"
                                    :class="{ 'is-invalid': errores.nombre }"
                                    placeholder="Ej. Clínica Dental Vista Boreal"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.nombre }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-semibold text-secondary">Correo Electrónico (Titular)</label>
                                <input type="email" v-model="formulario.email" class="form-control form-control-lg text-muted shadow-none" style="background-color: #e9ecef; border: 1px solid #dee2e6;" disabled>
                                <small class="text-muted mt-1 d-block">Este correo está vinculado a tu cuenta de usuario.</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-secondary">Teléfono de Notificaciones (WhatsApp)</label>
                                <div class="input-group input-group-lg shadow-sm">
                                    <input
                                        type="tel"
                                        v-model="formulario.telefono"
                                        class="form-control bg-light border-0"
                                        :class="{ 'is-invalid': errores.telefono }"
                                        placeholder="10 dígitos"
                                        maxlength="10"
                                        @input="formulario.telefono = formulario.telefono.replace(/\D/g, '')"
                                    >
                                </div>
                                <div class="text-danger small mt-1 fw-medium" v-if="errores.telefono">{{ errores.telefono }}</div>
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

                            <div v-if="plan.id == 3" class="col-md-12 mt-4">
                                <label class="form-label fw-semibold text-secondary">URL DEL NEGOCIO</label>
                                <div class="input-group has-validation shadow-sm">
                                    <span class="input-group-text bg-white border-end-0 text-muted">www.agendavb/</span>
                                    <input type="text" v-model="formulario.slug" class="form-control form-control-lg border-start-0" placeholder="barberia-lopez" required>
                                </div>
                            </div>

                            <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary fw-bold px-4">Guardar Negocio</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
