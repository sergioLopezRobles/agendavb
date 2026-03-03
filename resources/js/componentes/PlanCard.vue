<script setup>
import { useRouter } from 'vue-router'
import { ref, reactive, onMounted } from "vue";

const router = useRouter()
const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
const token = ref(localStorage.getItem('token'))

const props = defineProps({
    plan: Object
})

const mostrarModal = ref(false)

// Variables para el flujo de verificación
const codigoEnviado = ref(true)
const numeroVerificado = ref(true)

// Estado del formulario
const formulario = reactive({
    nombre: '',
   // telefono: '',
    email: '', // Se llenará con el correo del usuario logueado
    hora_inicio: '',
    hora_fin: '',
   //codigo_verificacion: ''
})

const errores = reactive({})

// Intentamos cargar el email del usuario cuando se monta el componente
onMounted(() => {
    // Aquí es donde busca el correo en la memoria del navegador.
    // Una vez que arreglemos tu Login.vue, esto funcionará solo.
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
    mostrarModal.value = true
}

const cerrarModal = () => {
    mostrarModal.value = false
    limpiarFormulario()
}

const limpiarFormulario = () => {
    formulario.nombre = ''
    formulario.telefono = ''
    formulario.hora_inicio = ''
    formulario.hora_fin = ''
    formulario.codigo_verificacion = ''
    codigoEnviado.value = false
    numeroVerificado.value = false
    Object.keys(errores).forEach(key => delete errores[key])
}

// --- CONEXIÓN REAL CON TWILIO ---
const enviarCodigo = async () => {
    if (formulario.telefono.length === 10) {
        // Ponemos el botón en estado de carga (opcional, visual)
        window.$toast.show('Enviando SMS...', 'info', 2000)

        try {
            const response = await fetch('/api/enviar-codigo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}` // Mandamos el token si la ruta está protegida
                },
                body: JSON.stringify({ telefono: formulario.telefono })
            })

            const data = await response.json()

            if (response.ok && data.success) {
                codigoEnviado.value = true
                window.$toast.show('Código enviado con éxito', 'success', 3000)
            } else {
                window.$toast.show('Error: ' + data.message, 'danger', 4000)
            }
        } catch (error) {
            window.$toast.show('Error al conectar con el servidor', 'danger', 4000)
            console.error(error)
        }
    }
}

const confirmarCodigo = async () => {
    if (formulario.codigo_verificacion.length === 6) {
        try {
            const response = await fetch('/api/verificar-codigo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${token.value}`
                },
                body: JSON.stringify({
                    telefono: formulario.telefono,
                    codigo: formulario.codigo_verificacion
                })
            })

            const data = await response.json()

            if (response.ok && data.success) {
                numeroVerificado.value = true
                codigoEnviado.value = false
                errores.telefono = null
                window.$toast.show('Número verificado correctamente', 'success', 3000)
            } else {
                window.$toast.show('Código incorrecto, intenta de nuevo', 'warning', 4000)
            }
        } catch (error) {
            window.$toast.show('Error al conectar con el servidor', 'danger', 4000)
            console.error(error)
        }
    }
}
// ---------------------------------------

const validarFormulario = () => {
    Object.keys(errores).forEach(key => delete errores[key])
    let esValido = true

    if (!formulario.nombre.trim()) {
        errores.nombre = 'El nombre del negocio es obligatorio.'
        esValido = false
    }

   /* if (!formulario.telefono.trim() || formulario.telefono.length !== 10) {
        errores.telefono = 'Debe contener exactamente 10 dígitos numéricos.'
        esValido = false
    }*/

    /*if (!numeroVerificado.value) {
        errores.telefono = 'Debes verificar el número de teléfono para continuar.'
        esValido = false
    }*/

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

const formRegistrarPlanNegocio = async () => {
    if (!validarFormulario()) return

    try{
        const response = await fetch('/api/registrar-plan-negocio',{
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json',
                // Es vital enviar el token para que el middleware auth:sanctum te deje pasar
                'Authorization': `Bearer ${token.value}`
            },
            body: JSON.stringify({
                plan: props.plan.id,
                nombre: formulario.nombre,
                email: formulario.email,
                // telefono: formulario.telefono,
                hora_inicio: formulario.hora_inicio,
                hora_fin: formulario.hora_fin
            })
        })

        const data = await response.json()

        if(data.valid){
            //registro exitoso
            window.$toast.show(data.message, 'success', 5000)
            window.location.reload()
        }else{
            //registro no exitoso
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
                            <input
                                type="email"
                                v-model="formulario.email"
                                class="form-control form-control-lg text-muted shadow-none"
                                style="background-color: #e9ecef; border: 1px solid #dee2e6;"
                                disabled
                            >
                            <small class="text-muted mt-1 d-block">Este correo está vinculado a tu cuenta de usuario.</small>
                        </div>

                        <!--<div class="mb-3">
                            <label class="form-label fw-semibold text-secondary">Teléfono de Notificaciones (WhatsApp)</label>

                            <div class="input-group input-group-lg shadow-sm">
                                <input
                                    type="tel"
                                    v-model="formulario.telefono"
                                    class="form-control bg-light border-0"
                                    :class="{ 'is-invalid': errores.telefono }"
                                    placeholder="10 dígitos"
                                    maxlength="10"
                                    :disabled="numeroVerificado"
                                    @input="formulario.telefono = formulario.telefono.replace(/\D/g, '')"
                                >
                                <button
                                    class="btn fw-bold px-4"
                                    :class="numeroVerificado ? 'btn-success' : 'btn-outline-primary bg-white'"
                                    type="button"
                                    @click="enviarCodigo"
                                    :disabled="formulario.telefono.length !== 10 || numeroVerificado"
                                >
                                    {{ numeroVerificado ? '✅ Verificado' : 'Verificar' }}
                                </button>
                            </div>
                            <div class="text-danger small mt-1 fw-medium" v-if="errores.telefono">{{ errores.telefono }}</div>
                        </div>

                        <div v-if="codigoEnviado" class="mb-4 p-3 bg-primary bg-opacity-10 rounded-3 border border-primary border-opacity-25">
                            <label class="form-label fw-bold text-primary">Ingresa el código que enviamos por SMS</label>
                            <div class="input-group input-group-lg shadow-sm">
                                <input
                                    type="text"
                                    v-model="formulario.codigo_verificacion"
                                    class="form-control bg-white border-0 text-center fw-bold text-primary"
                                    placeholder="------"
                                    maxlength="6"
                                    @input="formulario.codigo_verificacion = formulario.codigo_verificacion.replace(/\D/g, '')"
                                >
                                <button
                                    class="btn btn-primary fw-bold px-4"
                                    type="button"
                                    @click="confirmarCodigo"
                                    :disabled="formulario.codigo_verificacion.length !== 6"
                                >
                                    Confirmar
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">Demo: Para probar, escribe 6 números cualesquiera.</small>
                        </div>-->

                        <div class="row g-3 mt-2">
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

                <div class="modal-footer border-top-0 px-4 pb-4 pt-0 d-flex justify-content-center">
                    <button
                        type="button"
                        class="btn btn-primary fw-bold px-5 py-2 rounded-pill shadow-sm d-flex align-items-center"
                        @click="formRegistrarPlanNegocio">
                        <!--:disabled="!numeroVerificado"-->
                        <span class="me-2">💾</span> Finalizar Registro
                    </button>
                </div>
            </div>
        </div>
    </div>

</template>
