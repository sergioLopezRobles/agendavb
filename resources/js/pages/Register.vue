<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')
const telefono = ref('')
const codigo = ref('')

const nameError = ref('')
const emailError = ref('')
const passwordError = ref('')
const confirmPasswordError = ref('')
const telefonoError = ref('')
const codigoError = ref('')

// Variables para el flujo de Twilio
const enviandoCodigo = ref(false)
const codigoEnviado = ref(false)
const verificandoCodigo = ref(false)
const numeroVerificado = ref(false)

const enviarCodigoSMS = async () => {
    telefonoError.value = ''
    if (!telefono.value || telefono.value.length !== 10) {
        telefonoError.value = 'Ingresa un número válido de 10 dígitos.'
        return
    }

    enviandoCodigo.value = true
    try {
        const response = await fetch('/api/enviar-codigo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefono: telefono.value })
        })
        const data = await response.json()

        if (data.success || data.valid) {
            window.$toast.show('Código enviado por SMS', 'info', 4000)
            codigoEnviado.value = true
        } else {
            telefonoError.value = data.message || 'Error al enviar código'
        }
    } catch (error) {
        window.$toast.show('Error de conexión con el servidor', 'danger', 4000)
    } finally {
        enviandoCodigo.value = false
    }
}

const verificarCodigoSMS = async () => {
    codigoError.value = ''
    if (!codigo.value) {
        codigoError.value = 'Ingresa el código que recibiste.'
        return
    }

    verificandoCodigo.value = true
    try {
        const response = await fetch('/api/verificar-codigo', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ telefono: telefono.value, codigo: codigo.value })
        })
        const data = await response.json()

        if (data.success || data.valid) {
            window.$toast.show('¡Número verificado correctamente!', 'success', 4000)
            numeroVerificado.value = true
        } else {
            codigoError.value = data.message || 'Código incorrecto'
        }
    } catch (error) {
        window.$toast.show('Error de conexión con el servidor', 'danger', 4000)
    } finally {
        verificandoCodigo.value = false
    }
}

const validar = () => {
    let valido = true
    nameError.value = ''
    emailError.value = ''
    passwordError.value = ''
    confirmPasswordError.value = ''
    telefonoError.value = ''

    if (!name.value) {
        nameError.value = 'El nombre es obligatorio.'
        valido = false
    }

    if (!email.value) {
        emailError.value = 'El correo electrónico es obligatorio.'
        valido = false
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
        emailError.value = 'Ingresa un formato de correo válido.'
        valido = false
    }

    if (!telefono.value || telefono.value.length !== 10) {
        telefonoError.value = 'Ingresa tu número de 10 dígitos.'
        valido = false
    }

    //verificación
    else if (!numeroVerificado.value) {
        telefonoError.value = 'Debes verificar tu número para continuar.'
        window.$toast.show('Verifica tu número de teléfono primero', 'warning', 3000)
        valido = false
    }

    if (!password.value) {
        passwordError.value = 'La contraseña es obligatoria.'
        valido = false
    } else if (password.value.length < 6) {
        passwordError.value = 'La contraseña debe tener al menos 6 caracteres.'
        valido = false
    }

    if (!confirmPassword.value) {
        confirmPasswordError.value = 'Debes confirmar tu contraseña.'
        valido = false
    } else if (password.value !== confirmPassword.value) {
        confirmPasswordError.value = 'Las contraseñas no coinciden.'
        valido = false
    }

    return valido
}

const register = async () => {
    if (!validar()) return

    const response = await fetch('/api/register',{
        method: 'POST',
        headers: {
            'Content-Type' : 'application/json'
        },
        body: JSON.stringify({
            name: name.value,
            email: email.value,
            password: password.value,
            telefono: telefono.value
        })
    })

    const data = await response.json()

    if(data.valid){
        window.$toast.show(data.message, 'success', 5000)
        localStorage.setItem('token', data.token)
        localStorage.setItem('userEmail', email.value)
        router.push('/dashboard')
    }else{
        window.$toast.show(data.message, 'warning', 5000)
    }
}
</script>

<template>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center py-5">
        <div class="col-md-8 col-lg-6 w-50">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                            <span class="fs-1">📝</span>
                        </div>
                    </div>

                    <h2 class="text-center text-primary fw-bold mb-2">Crear Cuenta</h2>
                    <p class="text-center text-muted mb-4">Regístrate para comenzar</p>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Nombre Completo</label>
                            <input v-model="name" type="text" class="form-control form-control-lg bg-light border-0" :class="{ 'is-invalid': nameError }" placeholder="Tu nombre">
                            <div class="invalid-feedback">{{ nameError }}</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Correo Electrónico</label>
                            <input v-model="email" type="email" class="form-control form-control-lg bg-light border-0" :class="{ 'is-invalid': emailError }" placeholder="ejemplo@correo.com">
                            <div class="invalid-feedback">{{ emailError }}</div>
                        </div>

                        <div class="col-12 mt-3 mb-2">
                            <label class="form-label fw-semibold">Teléfono Celular</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">🇲🇽 +52</span>
                                <input v-model="telefono" type="tel" class="form-control form-control-lg bg-light border-0" :class="{ 'is-invalid': telefonoError, 'is-valid': numeroVerificado }" placeholder="10 dígitos" maxlength="10" @input="telefono = telefono.replace(/\D/g, '')" :disabled="numeroVerificado">

                                <button v-if="!numeroVerificado" @click="enviarCodigoSMS" class="btn btn-outline-primary fw-bold" type="button" :disabled="enviandoCodigo || telefono.length !== 10">
                                    {{ enviandoCodigo ? 'Enviando...' : 'Validar numero' }}
                                </button>
                                <span v-else class="input-group-text bg-success text-white border-0 fw-bold">✅</span>
                            </div>
                            <div class="text-danger small fw-medium mt-1" v-if="telefonoError">{{ telefonoError }}</div>
                        </div>

                        <div v-if="codigoEnviado && !numeroVerificado" class="col-12 bg-primary bg-opacity-10 p-3 rounded-3 mt-2">
                            <label class="form-label fw-bold text-primary small">Ingresa el código de 6 dígitos</label>
                            <div class="input-group">
                                <input v-model="codigo" type="text" class="form-control bg-white border-0" placeholder="Ej. 123456" maxlength="6">
                                <button @click="verificarCodigoSMS" class="btn btn-primary fw-bold" type="button" :disabled="verificandoCodigo">
                                    {{ verificandoCodigo ? 'Verificando...' : 'Validar' }}
                                </button>
                            </div>
                            <div class="text-danger small fw-medium mt-1" v-if="codigoError">{{ codigoError }}</div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <input v-model="password" type="password" class="form-control form-control-lg bg-light border-0" :class="{ 'is-invalid': passwordError }" placeholder="Min. 6 caracteres">
                            <div class="invalid-feedback">{{ passwordError }}</div>
                        </div>

                        <div class="col-md-6 mt-3">
                            <label class="form-label fw-semibold">Confirmar</label>
                            <input v-model="confirmPassword" type="password" class="form-control form-control-lg bg-light border-0" :class="{ 'is-invalid': confirmPasswordError }" placeholder="Repite contraseña">
                            <div class="invalid-feedback">{{ confirmPasswordError }}</div>
                        </div>
                    </div>

                    <button @click="register" class="btn btn-primary w-100 py-3 fw-bold fs-5 mb-3 mt-4 rounded-3 shadow-sm" :disabled="!numeroVerificado">
                        Crear cuenta segura
                    </button>

                    <div class="text-center">
                        <p class="mb-0 text-muted">¿Ya tienes cuenta? <router-link to="/login" class="text-decoration-none fw-bold text-primary">Iniciar Sesión</router-link></p>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
