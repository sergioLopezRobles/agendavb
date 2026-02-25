<script setup>

import { useRouter } from 'vue-router'

const router = useRouter()

const logout = async () => {
    const token = localStorage.getItem('token')

    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        })
    } catch (error) {
        console.log(error)
    }

    // borrar token del navegador
    localStorage.removeItem('token')

    // borrar plan seleccionado si quieres
    localStorage.removeItem('planSeleccionado')

    // redirigir al inicio
    router.push('/')

}

const plan = JSON.parse(localStorage.getItem('planSeleccionado'))
console.log('nombreplan: ' + plan.nombre )
console.log('precioplan: ' + plan.precio)
</script>

<template>
    <div class="bg-light min-vh-100">

        <nav class="navbar navbar-expand-lg bg-primary shadow-sm px-4 py-3">
            <div class="container-fluid d-flex justify-content-between align-items-center">

                <span class="navbar-brand text-white fw-bold fs-4 mb-0">
                    🚀 Dashboard
                </span>

                <button
                    @click="logout"
                    class="btn btn-danger fw-semibold px-4 rounded-pill"
                >
                    Cerrar sesión
                </button>

            </div>
        </nav>


        <div class="container mt-5 pt-4">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">

                    <div class="card shadow-lg border-0 rounded-4 text-center mb-4">
                        <div class="card-body p-5">

                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex justify-content-center align-items-center mb-4" style="width: 80px; height: 80px;">
                                <span class="fs-1">👋</span>
                            </div>

                            <h2 class="text-primary fw-bold mb-3">Bienvenido al Dashboard</h2>

                            <p class="text-muted fs-5 mb-0">
                                Ya iniciaste sesión correctamente.
                            </p>

                        </div>
                    </div>

                    <div v-if="plan" class="card shadow-sm border-0 rounded-4">
                        <div class="card-body p-4">
                            <h5 class="text-muted fw-bold mb-3 d-flex align-items-center">
                                <span class="me-2">📦</span> Tu plan actual
                            </h5>

                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="fw-bold text-dark mb-1">{{ plan.nombre }}</h3>
                                    <p class="text-secondary mb-0">
                                        Intervalo: {{ plan.intervalo_citas_minutos }} min
                                    </p>
                                </div>
                                <div class="text-end">
                                    <h2 class="text-primary fw-bold mb-0">${{ plan.precio }}</h2>
                                    <small class="text-muted">/mes</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-else class="alert alert-warning border-0 shadow-sm rounded-4 mt-4" role="alert">
                        Aún no has seleccionado ningún plan.
                    </div>

                </div>
            </div>
        </div>

    </div>
</template>
