<script setup>

import {useRouter} from 'vue-router'
const router = useRouter()

const props = defineProps({
    plan: Object
})

const planSeleccionado = () => {
    //guardar el plan en localStorage
    localStorage.setItem('planSeleccionado', JSON.stringify(props.plan))

    //redireccionar al login
    router.push('/login')
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

                <button @click="planSeleccionado" class="btn btn-primary w-100 py-3 fw-bold fs-6 rounded-3">
                    Seleccionar plan
                </button>

            </div>

        </div>

    </div>

</template>
