<script setup>

const props = defineProps({
    plan: Object
})

</script>

<template>

    <div class="col-md-4 col-lg-3">

        <div class="card shadow-sm border-0 h-100">

            <div class="card-body text-center">

                <h4 class="card-title">
                    {{ plan.nombre }}
                </h4>

                <h2 class="text-primary fw-bold">
                    ${{ plan.precio }}
                    <small class="text-muted fs-6">/mes</small>
                </h2>

                <hr>

                <ul class="list-unstyled text-start">

                    <li class="mb-3">
                        📅 Intervalo: {{ plan.intervalo_citas_minutos }} min
                    </li>

                    <li class="mb-3">
                        ⏰ Recordatorio: {{ plan.recordatorio_minutos }} min antes
                    </li>

                    <li class="mb-3">
                        💬 WhatsApp: {{ plan.whatsapp_creditos_iniciales }}
                    </li>

                </ul>

            </div>

            <div class="card-footer bg-white border-0">

                <button class="btn btn-primary w-100">
                    Seleccionar plan
                </button>

            </div>

        </div>

    </div>

</template>
