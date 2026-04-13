<script setup>
</script>

<template>

    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-3">

        <div class="container d-flex justify-content-between align-items-center">

            <a class="navbar-brand fw-bold text-black fs-4 d-flex align-items-center text-decoration-none" href="#">
                <img src="../../images/logo-vb.jpg" alt="Vista Boreal" class="me-2 rounded shadow-sm" style="width: 40px; height: 40px; object-fit: cover;">
                <h4 class="fw-bold mb-0 text-dark menu-text text-truncate">Vista Boreal</h4>
            </a>

            <div class="ms-auto d-flex align-items-center gap-3">

                <router-link to="/login" class="btn btn-outline-primary fw-semibold px-4 rounded-pill">
                    Iniciar Sesión
                </router-link>

                <router-link to="/register" class="btn btn-primary fw-semibold px-4 rounded-pill shadow-sm">
                    Registrarse
                </router-link>

            </div>

        </div>

    </nav>

</template>
