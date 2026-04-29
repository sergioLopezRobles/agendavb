<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const email = ref('')
const password = ref('')

const emailError = ref('')
const passwordError = ref('')

const validar = () => {
    let valido = true
    emailError.value = ''
    passwordError.value = ''

    if (!email.value) {
        window.$toast.show('El correo electrónico es obligatorio ', 'warning', 5000)
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

    return valido
}

const login = async () => {
    if (!validar()) return

    try {
        const response = await fetch('/api/login',{
            method: 'POST',
            headers: {
                'Content-Type' : 'application/json'
            },
            body: JSON.stringify({
                email: email.value,
                password: password.value
            })
        })

        const data = await response.json()

        if(data.valid && data.token){
            localStorage.setItem('token', data.token)
            localStorage.setItem('userEmail', email.value)

            // 👇 SOLUCIÓN BUG 1: GUARDAMOS EL ROL Y EL PLAN INMEDIATAMENTE 👇
            localStorage.setItem('userRol', data.user.id_rol.toString())
            localStorage.setItem('userHasPlan', data.has_plan ? 'true' : 'false')
            // -------------------------------------------------------------

            window.$toast.show('Bienvenido '  + data.user.name, 'success', 5000)
            router.push('/dashboard')
        } else {
            // Mostramos el mensaje si está suspendido o se equivoca de contraseña
            window.$toast.show(data.message || 'Error al iniciar sesión', 'danger', 5000)
        }
    } catch (error) {
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    }
}
</script>

<template>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center">

        <div class="col-md-6 col-lg-5 w-40">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5"> <div class="text-center mb-4">
                    <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                        <span class="fs-1">🏢</span>
                    </div>
                </div>

                    <h2 class="text-center text-primary fw-bold mb-2">Bienvenido</h2>
                    <p class="text-center text-muted mb-5">Ingresa a tu cuenta para continuar</p>

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
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-semibold">Contraseña</label>
                            <a href="#" class="text-decoration-none small text-primary fw-semibold">¿Olvidaste tu contraseña?</a>
                        </div>
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

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="recordarme">
                        <label class="form-check-label text-muted" for="recordarme">
                            Recordarme en este dispositivo
                        </label>
                    </div>

                    <button @click="login" class="btn btn-primary w-100 py-3 fw-bold fs-5 mb-4 rounded-3">
                        Iniciar Sesión
                    </button>

                    <div class="text-center">
                        <p class="mb-0 text-muted">
                            ¿No tienes cuenta?
                            <router-link to="/register" class="text-decoration-none fw-bold text-primary">
                                Registrarse
                            </router-link>
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>

</template>
