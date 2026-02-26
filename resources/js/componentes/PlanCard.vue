<script setup>
import { useRouter } from 'vue-router'
import { ref, reactive } from "vue";

const router = useRouter()
const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
const token = ref(localStorage.getItem('token'))

const props = defineProps({
    plan: Object
})

const mostrarModal = ref(false)

// Estado del formulario para la tabla negocios
const formulario = reactive({
    nombre: '',
    telefono: '',
    email: '',
    hora_inicio: '',
    hora_fin: ''
})

const errores = reactive({})

const eventPlanSeleccionado = () => {
    console.log(token)
    if(token.value != null){
        abrirModal()
    } else {
        //guardar el plan en localStorage
        localStorage.setItem('planSeleccionado', JSON.stringify(props.plan))
        //redireccionar al login
        router.push('/login')
    }
}

const abrirModal = () => {
    mostrarModal.value = true
}

const cerrarModal = () => {
    mostrarModal.value = false
    limpiarFormulario()
}

const limpiarFormulario = () => {
    formulario.nombre = ''
    formulario.telefono = ''
    formulario.email = ''
    formulario.hora_inicio = ''
    formulario.hora_fin = ''
    Object.keys(errores).forEach(key => delete errores[key])
}

const validarFormulario = () => {
    Object.keys(errores).forEach(key => delete errores[key])
    let esValido = true

    if (!formulario.nombre.trim()) {
        errores.nombre = 'El nombre del negocio es obligatorio.'
        esValido = false
    }

    if (!formulario.telefono.trim()) {
        errores.telefono = 'El teléfono es obligatorio.'
        esValido = false
    } else if (!/^\d{10}$/.test(formulario.telefono.replace(/\D/g, ''))) {
        errores.telefono = 'Debe contener 10 dígitos numéricos.'
        esValido = false
    }

    if (!formulario.email.trim()) {
        errores.email = 'El correo es obligatorio.'
        esValido = false
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulario.email)) {
        errores.email = 'Ingresa un correo electrónico válido.'
        esValido = false
    }

    if (!formulario.hora_inicio) {
        errores.hora_inicio = 'Selecciona la hora de apertura.'
        esValido = false
    }

    if (!formulario.hora_fin) {
        errores.hora_fin = 'Selecciona la hora de cierre.'
        esValido = false
    } else if (formulario.hora_inicio && formulario.hora_fin <= formulario.hora_inicio) {
        errores.hora_fin = 'La hora de cierre debe ser posterior a la de apertura.'
        esValido = false
    }

    return esValido
}

const registrarNegocio = () => {
    if (validarFormulario()) {
        // TODO: Aquí se agrega la petición real al backend
        console.log('Validación exitosa del front. Datos listos:', formulario)

        // Puedes llamar aquí a tu función axios/fetch y luego cerrar el modal
        // cerrarModal()
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

                <h2 class="text-primary fw-bold mb-4 display-6">
                    ${{ plan.precio }}
                    <span class="text-muted fs-6 fw-normal">/mes</span>
                </h2>

                <hr class="text-muted opacity-25 mb-4">

                <ul class="list-unstyled text-start mb-4 flex-grow-1">

                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">📅</span>
                        <span class="text-secondary fw-medium">Intervalo: {{ plan.intervalo_citas_minutos }} min</span>
                    </li>

                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">⏰</span>
                        <span class="text-secondary fw-medium">Recordatorio: {{ plan.recordatorio_minutos }} min antes</span>
                    </li>

                    <li class="mb-3 d-flex align-items-center">
                        <span class="fs-5 me-3">💬</span>
                        <span class="text-secondary fw-medium">WhatsApp: {{ plan.whatsapp_creditos_iniciales }} créditos</span>
                    </li>

                </ul>

            </div>

            <div class="card-footer bg-transparent border-0 p-4 pt-0">

                <button v-if="token && planGuardado" @click="abrirModal" class="btn btn-secondary w-100 py-3 fw-bold fs-6 rounded-3">
                    Registrar Negocio
                </button>

                <button v-else @click="eventPlanSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3">
                    Seleccionar plan
                </button>

            </div>

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
                            <h4 class="fw-bold mb-0 text-dark">${{ plan.precio }} <small class="text-muted fs-6 fw-normal">/mes</small></h4>
                        </div>
                    </div>

                    <form @submit.prevent="registrarNegocio">

                        <div class="mb-4">
                            <label class="form-label fw-semibold text-secondary">Nombre del Negocio</label>
                            <input
                                type="text"
                                v-model="formulario.nombre"
                                class="form-control form-control-lg bg-light border-0 shadow-sm"
                                :class="{ 'is-invalid': errores.nombre }"
                                placeholder="Ej. Optica Vista Boreal"
                            >
                            <div class="invalid-feedback fw-medium">{{ errores.nombre }}</div>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Teléfono</label>
                                <input
                                    type="tel"
                                    v-model="formulario.telefono"
                                    class="form-control form-control-lg bg-light border-0 shadow-sm"
                                    :class="{ 'is-invalid': errores.telefono }"
                                    placeholder="10 dígitos"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.telefono }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Correo Electrónico</label>
                                <input
                                    type="email"
                                    v-model="formulario.email"
                                    class="form-control form-control-lg bg-light border-0 shadow-sm"
                                    :class="{ 'is-invalid': errores.email }"
                                    placeholder="contacto@negocio.com"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.email }}</div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Hora de Apertura</label>
                                <input
                                    type="time"
                                    v-model="formulario.hora_inicio"
                                    class="form-control form-control-lg bg-light border-0 shadow-sm"
                                    :class="{ 'is-invalid': errores.hora_inicio }"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.hora_inicio }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-secondary">Hora de Cierre</label>
                                <input
                                    type="time"
                                    v-model="formulario.hora_fin"
                                    class="form-control form-control-lg bg-light border-0 shadow-sm"
                                    :class="{ 'is-invalid': errores.hora_fin }"
                                >
                                <div class="invalid-feedback fw-medium">{{ errores.hora_fin }}</div>
                            </div>
                        </div>

                    </form>

                </div>

                <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-light fw-semibold px-4 rounded-pill" @click="cerrarModal">
                        Cancelar
                    </button>
                    <button type="button" class="btn btn-primary fw-bold px-4 rounded-pill shadow-sm d-flex align-items-center" @click="registrarNegocio">
                        <span class="me-2">💾</span> Finalizar Registro
                    </button>
                </div>

            </div>
        </div>
    </div>

</template>
