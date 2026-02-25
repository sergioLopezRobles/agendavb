<script setup>
</script>
<template>

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">

        <div class="container">

            <a class="navbar-brand fw-bold">
                AgendaVB
            </a>

            <div class="ms-auto">

                <button class="btn btn-light me-2">
                    <router-link to="/login" class="text-decoration-none fw-bold text-primary">
                        Iniciar Sesión
                    </router-link>

                </button>

                <button class="btn btn-light">
                    <router-link to="/register" class="text-decoration-none fw-bold text-primary">
                       Registrarse
                    </router-link>
                </button>

            </div>

        </div>

    </nav>

</template>
