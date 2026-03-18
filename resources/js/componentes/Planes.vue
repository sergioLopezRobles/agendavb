<script setup>
import { ref, onMounted} from 'vue'
import PlanCard from './PlanCard.vue'
import Navbar from './Navbar.vue'

const planes = ref([])

onMounted(async () => {
    const response = await fetch('/api/planes')
    planes.value = await response.json()
})
</script>

<template>
    <navbar/>

    <div class="bg-light min-vh-100 pb-5">

        <div class="py-5" style="background: linear-gradient(180deg, #f8f9fa 0%, #e9ecef 100%);">
            <div class="container py-5 mt-4 text-center">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold mb-3 tracking-wide text-uppercase">
                    Tu negocio de una manera simple
                </span>
                <h1 class="text-dark fw-bolder display-4 mb-3 tracking-tight">
                    Impulsa tu negocio al siguiente nivel
                </h1>
                <p class="text-secondary fs-5 mx-auto" style="max-width: 600px;">
                    Comienza a automatizar tus reservas hoy mismo. Sin contratos forzosos. Cambia de plan o cancela cuando quieras.
                </p>

                <div class="d-flex justify-content-center align-items-center mt-4 gap-3">
                    <span class="fw-medium text-muted">Mensual</span>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input shadow-none cursor-pointer" type="checkbox" role="switch">
                    </div>
                    <span class="fw-bold text-dark d-flex align-items-center">
                        Anual
                        <span class="badge bg-success bg-opacity-10 text-success ms-2 rounded-pill border border-success border-opacity-25 shadow-sm" style="font-size: 0.7em;">
                            Ahorra 20%
                        </span>
                    </span>
                </div>
            </div>
        </div>

        <div class="container mt-5">
            <div class="row g-4 justify-content-center">
                <PlanCard
                    v-for="plan in planes"
                    :key="plan.id"
                    :plan="plan"
                />
            </div>
        </div>

        <div class="container mt-5 pt-5 text-center">
            <h4 class="fw-bold text-dark mb-4">¿Por qué elegir nuestra plataforma?</h4>
            <div class="row justify-content-center g-4 mt-2">
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-0 transition-hover">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3">🔒</span>
                        </div>
                        <h6 class="fw-bold text-dark">Pagos 100% Seguros</h6>
                        <p class="text-muted small mb-0">Tus transacciones están encriptadas y procesadas por Stripe.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-0 transition-hover">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3">🎧</span>
                        </div>
                        <h6 class="fw-bold text-dark">Soporte Dedicado</h6>
                        <p class="text-muted small mb-0">Mesa de ayuda integrada para resolver tus dudas rápidamente.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="p-4 bg-white rounded-4 shadow-sm h-100 border-0 transition-hover">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                            <span class="fs-3">⚡</span>
                        </div>
                        <h6 class="fw-bold text-dark">Activación Inmediata</h6>
                        <p class="text-muted small mb-0">Tu sistema de reservas y enlace público listos al instante.</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
.tracking-tight {
    letter-spacing: -0.03em;
}
.tracking-wide {
    letter-spacing: 0.05em;
}
.form-switch .form-check-input:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
    opacity: 0.7; /* Para el switch deshabilitado */
}
.transition-hover {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
}
</style>
