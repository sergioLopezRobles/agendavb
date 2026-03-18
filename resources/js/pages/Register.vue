<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')
const confirmPassword = ref('')

const nameError = ref('')
const emailError = ref('')
const passwordError = ref('')
const confirmPasswordError = ref('')

const validar = () => {
    let valido = true
    nameError.value = ''
    emailError.value = ''
    passwordError.value = ''
    confirmPasswordError.value = ''

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
            password: password.value
        })
    })

    const data = await response.json()

    if(data.valid){
        //registro exitoso
        window.$toast.show(data.message, 'success', 5000)
        localStorage.setItem('token', data.token)

        localStorage.setItem('userEmail', email.value)

        router.push('/dashboard')
    }else{
        //registro no exitoso
        window.$toast.show(data.message, 'warning', 5000)
    }
}
</script>

<template>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">

        <div class="col-md-6 col-lg-5 w-40">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                            <span class="fs-1">📝</span>
                        </div>
                    </div>

                    <h2 class="text-center text-primary fw-bold mb-2">Crear Cuenta</h2>
                    <p class="text-center text-muted mb-3">Regístrate para comenzar</p>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Nombre Completo</label>
                        <input
                            v-model="name"
                            type="text"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': nameError }"
                            placeholder="Tu nombre"
                        >
                        <div class="invalid-feedback">
                            {{ nameError }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Correo Electrónico</label>
                        <input
                            v-model="email"
                            type="email"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': emailError }"
                            placeholder="ejemplo@correo.com"
                        >
                        <div class="invalid-feedback">
                            {{ emailError }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Contraseña</label>
                        <input
                            v-model="password"
                            type="password"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': passwordError }"
                            placeholder="Ingresa tu contraseña aquí"
                        >
                        <div class="invalid-feedback">
                            {{ passwordError }}
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Confirmar Contraseña</label>
                        <input
                            v-model="confirmPassword"
                            type="password"
                            class="form-control form-control-lg"
                            :class="{ 'is-invalid': confirmPasswordError }"
                            placeholder="Repite tu contraseña"
                        >
                        <div class="invalid-feedback">
                            {{ confirmPasswordError }}
                        </div>
                    </div>

                    <button @click="register" class="btn btn-primary w-100 py-3 fw-bold fs-5 mb-3 rounded-3">
                        Crear cuenta
                    </button>

                    <div class="text-center">
                        <p class="mb-0 text-muted">
                            ¿Ya tienes cuenta?
                            <router-link to="/login" class="text-decoration-none fw-bold text-primary">
                                Iniciar Sesión
                            </router-link>
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>
