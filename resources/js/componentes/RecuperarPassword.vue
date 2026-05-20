<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const telefono = ref('')
const codigo = ref('')
const password = ref('')
const confirmPassword = ref('')

const enviandoCodigo = ref(false)
const codigoEnviado = ref(false)
const recuperando = ref(false)

const enviarCodigoSMS = async () => {
    if (!telefono.value || telefono.value.length !== 10) {
        window.$toast.show('Ingresa un celular válido de 10 dígitos.', 'warning', 3000)
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
            window.$toast.show('Te enviamos un código por SMS.', 'info', 4000)
            codigoEnviado.value = true
        } else {
            window.$toast.show(data.message || 'Error al enviar SMS', 'danger', 4000)
        }
    } catch (error) {
        window.$toast.show('Error de conexión', 'danger', 4000)
    } finally {
        enviandoCodigo.value = false
    }
}

const actualizarContrasena = async () => {
    if (!codigo.value || !password.value || password.value !== confirmPassword.value) {
        window.$toast.show('Verifica que todos los campos estén correctos y las contraseñas coincidan.', 'warning', 4000)
        return
    }

    recuperando.value = true
    try {
        const response = await fetch('/api/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                telefono: telefono.value,
                codigo: codigo.value,
                password: password.value
            })
        })
        const data = await response.json()

        if (data.valid) {
            window.$toast.show('¡Contraseña recuperada exitosamente!', 'success', 5000)
            router.push('/login')
        } else {
            window.$toast.show(data.message, 'danger', 4000)
        }
    } catch (error) {
        window.$toast.show('Error en el servidor', 'danger', 4000)
    } finally {
        recuperando.value = false
    }
}
</script>

<template>
    <div class="container min-vh-100 d-flex justify-content-center align-items-center py-5">
        <div class="col-md-8 col-lg-5 w-50">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-5">

                    <div class="text-center mb-4">
                        <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 80px; height: 80px;">
                            <span class="fs-1">🔐</span>
                        </div>
                    </div>

                    <h3 class="text-center text-dark fw-bold mb-2">Recuperar Acceso</h3>
                    <p class="text-center text-muted mb-4">Ingresa tu número registrado para restaurar tu contraseña mediante SMS.</p>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold">Tu Teléfono Celular</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0">🇲🇽 +52</span>
                                <input v-model="telefono" type="tel" class="form-control form-control-lg bg-light border-0" placeholder="10 dígitos" maxlength="10" @input="telefono = telefono.replace(/\D/g, '')" :disabled="codigoEnviado">
                            </div>
                        </div>

                        <div class="col-12 mt-4" v-if="!codigoEnviado">
                            <button @click="enviarCodigoSMS" class="btn btn-dark w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm" :disabled="enviandoCodigo">
                                {{ enviandoCodigo ? 'Enviando SMS...' : 'Enviar Código de Recuperación' }}
                            </button>
                        </div>

                        <div v-if="codigoEnviado" class="mt-4 p-4 bg-light rounded-4 border">
                            <div class="mb-3">
                                <label class="form-label fw-semibold text-primary">Código SMS de 6 dígitos</label>
                                <input v-model="codigo" type="text" class="form-control form-control-lg border-primary" placeholder="Ej. 123456" maxlength="6">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Nueva Contraseña</label>
                                <input v-model="password" type="password" class="form-control form-control-lg border-0 shadow-sm" placeholder="Mínimo 6 caracteres">
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Confirmar Contraseña</label>
                                <input v-model="confirmPassword" type="password" class="form-control form-control-lg border-0 shadow-sm" placeholder="Repite la contraseña">
                            </div>

                            <button @click="actualizarContrasena" class="btn btn-primary w-100 py-3 fw-bold fs-5 rounded-3 shadow-sm" :disabled="recuperando">
                                {{ recuperando ? 'Actualizando...' : 'Guardar Nueva Contraseña' }}
                            </button>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <router-link to="/login" class="text-decoration-none fw-bold text-muted">← Volver al Login</router-link>
                    </div>

                </div>
            </div>
        </div>
    </div>
</template>
