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

// --> funcion para agregar o quitar horario
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
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">
                            <span style="font-size: 12px; font-weight: bold;">✓</span>
                        </div>
                        <div>
                            <span class="d-block text-dark fw-medium">Intervalo de citas</span>
                            <span class="text-muted small">Cada {{ plan.caracteristicas?.intervalo_citas_minutos }} min</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">
                            <span style="font-size: 12px; font-weight: bold;">✓</span>
                        </div>
                        <div>
                            <span class="d-block text-dark fw-medium">Recordatorios automáticos</span>
                            <span class="text-muted small">{{ plan.caracteristicas?.recordatorio_minutos }} min antes de la cita</span>
                        </div>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center me-3 mt-1 flex-shrink-0" style="width: 24px; height: 24px;">
                            <span style="font-size: 12px; font-weight: bold;">✓</span>
                        </div>
                        <div>
                            <span class="d-block text-dark fw-medium">Notificaciones WhatsApp</span>
                            <span class="text-muted small">{{ plan.caracteristicas?.whatsapp_creditos_iniciales }} créditos mensuales</span>
                        </div>
                    </li>
                </ul>

            </div>

            <div class="card-footer bg-transparent border-0 p-4 pt-0">
                <button v-if="currentPlanId == plan.id" class="btn btn-light text-muted w-100 py-3 fw-bold fs-6 rounded-pill border" disabled>
                    Plan actual activado
                </button>

                <button v-else-if="currentPlanId != null" @click="eventPlanSeleccionado" class="btn btn-outline-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn">
                    Cambiar a este plan
                </button>

                <button v-else-if="token && planGuardado" @click="abrirModal" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn shadow-sm">
                    Registrar Negocio
                </button>

                <button v-else @click="eventPlanSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-pill saas-btn shadow-sm">
                    Comenzar ahora
                </button>
            </div>

        </div>

        <div v-if="mostrarModal" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6); backdrop-filter: blur(4px);">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                    <div class="modal-header border-bottom-0 pb-0 px-4 pt-4 px-md-5 pt-md-5">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-2">Paso final</span>
                            <h3 class="modal-title fw-bolder text-dark">Configura tu negocio</h3>
                        </div>
                        <button type="button" class="btn-close shadow-none" @click="cerrarModal"></button>
                    </div>

                    <div class="modal-body px-4 py-4 px-md-5">

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

                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Nombre de tu negocio</label>
                                <input
                                    type="text"
                                    v-model="formulario.nombre"
                                    class="form-control form-control-lg bg-light border-0 px-4"
                                    style="border-radius: 0.75rem;"
                                    :class="{ 'is-invalid': errores.nombre }"
                                    placeholder="Ej. Clínica Dental Vista Boreal"
                                >
                                <div class="invalid-feedback fw-medium px-2">{{ errores.nombre }}</div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Correo del titular</label>
                                    <input type="email" v-model="formulario.email" class="form-control form-control-lg text-muted px-4 border-0" style="background-color: #e9ecef; border-radius: 0.75rem;" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">WhatsApp de contacto</label>
                                    <input
                                        type="tel"
                                        v-model="formulario.telefono"
                                        class="form-control form-control-lg bg-light border-0 px-4"
                                        style="border-radius: 0.75rem;"
                                        :class="{ 'is-invalid': errores.telefono }"
                                        placeholder="10 dígitos"
                                        maxlength="10"
                                        @input="formulario.telefono = formulario.telefono.replace(/\D/g, '')"
                                    >
                                    <div class="invalid-feedback fw-medium px-2" v-if="errores.telefono">{{ errores.telefono }}</div>
                                </div>
                            </div>

                            <div class="mb-4 pt-2">
                                <label class="form-label fw-bold text-dark small text-uppercase tracking-wide mb-3">
                                    Disponibilidad de horarios
                                    <span class="text-muted fw-normal text-capitalize ms-2">(Selecciona los bloques de atención)</span>
                                </label>

                                <div class="border rounded-4 p-4 bg-white shadow-sm" :class="{'border-danger': errores.horarios}">
                                    <div class="d-flex flex-wrap gap-2" style="max-height: 180px; overflow-y: auto;">
                                        <button
                                            v-for="hora in horariosDisponibles"
                                            :key="hora"
                                            type="button"
                                            class="btn btn-sm rounded-pill fw-medium transition-all px-3 py-2 border"
                                            :class="horarios.includes(hora) ? 'btn-primary border-primary shadow-sm text-white' : 'btn-light border-light text-secondary'"
                                            @click="toggleHorario(hora)"
                                        >
                                            <span v-if="horarios.includes(hora)" class="me-1">✓</span>
                                            {{ hora }}
                                        </button>
                                    </div>
                                </div>
                                <div class="text-danger small fw-medium mt-2 px-2" v-if="errores.horarios">{{ errores.horarios }}</div>
                            </div>

                            <div v-if="plan.id == 3" class="mb-4 pt-2">
                                <label class="form-label fw-bold text-dark small text-uppercase tracking-wide">Enlace personalizado</label>
                                <div class="input-group input-group-lg shadow-sm rounded-4 overflow-hidden">
                                    <span class="input-group-text bg-light border-0 text-muted px-4">www.agendavb/</span>
                                    <input type="text" v-model="formulario.slug" class="form-control border-0 bg-light" placeholder="tu-marca-aqui" required>
                                </div>
                                <small class="text-muted d-block mt-2 px-2">Este será el link público para tus clientes.</small>
                            </div>

                            <div class="mt-5 d-flex justify-content-end gap-2">
                                <button type="submit" class="btn btn-primary px-5 py-3 fw-bold rounded-pill shadow-sm saas-btn">Confirmar y guardar</button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* --> animaciones y sombras limpias saas */
.saas-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.saas-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,0.1) !important;
}
.saas-btn {
    transition: all 0.2s ease-in-out;
}
.saas-btn:hover {
    transform: scale(1.02);
}
.tracking-wide {
    letter-spacing: 0.05em;
}
/* --> personalizar la barra de scroll para los horarios */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}
::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
