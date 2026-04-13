<script setup>
import { ref, watch } from "vue";
import { useRouter } from 'vue-router';

const router = useRouter();
const isSidebarExpanded = ref(true);

// recibir los datos de la vista que este usando layout
const props = defineProps({
    usuarioLoggeado: Object,
    planAdquirido: Object
});

// --> leemos la memoria local de forma instantanea para evitar el parpadeo
const tienePlan = ref(localStorage.getItem('userHasPlan') === 'true');

// --> vigilamos cuando la base de datos responda para guardar el dato en memoria
watch(() => props.planAdquirido, (nuevoValor) => {
    if (nuevoValor && nuevoValor.id) {
        tienePlan.value = true;
        localStorage.setItem('userHasPlan', 'true');
    }
}, { immediate: true });

const toggleSidebar = () => {
    isSidebarExpanded.value = !isSidebarExpanded.value;
};

const logout = async () => {
    const token = localStorage.getItem('token');
    try {
        await fetch('/api/logout', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }
        });
    } catch (error) {
        console.log(error);
    }
    // limpiamos toda la memoria al salir
    localStorage.removeItem('token');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('planSeleccionado');
    localStorage.removeItem('userHasPlan');
    router.push('/');
};
</script>

<template>
    <div class="d-flex bg-light min-vh-100">

        <div :class="['sidebar-wrapper bg-white shadow-sm d-flex flex-column sticky-top', { 'collapsed': !isSidebarExpanded }]">

            <div class="brand-header border-bottom d-flex align-items-center px-3">
                <div class="logo-container d-flex justify-content-center align-items-center flex-shrink-0">
                    <img src="../../images/logo-vb.jpg" alt="Vista Boreal" class="rounded shadow-sm brand-logo">
                </div>
                <h4 class="fw-bolder mb-0 text-dark menu-text ms-3 text-truncate" style="letter-spacing: -0.5px;">Vista Boreal</h4>
            </div>

            <ul class="nav flex-column mt-3 px-2 gap-3">
                <li class="nav-item">
                    <router-link to="/dashboard" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/dashboard' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-4 icon-menu me-3">📊</span>
                        <span class="menu-text fw-semibold">Dashboard</span>
                    </router-link>
                </li>

                <li v-if="tienePlan" class="nav-item mt-2 pt-2 border-top">
                    <small class="text-muted fw-bold ms-3 menu-text d-block mb-1" style="font-size: 0.75rem;">MÓDULOS</small>
                </li>

                <li v-if="tienePlan" class="nav-item">
                    <router-link to="/negocios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/negocios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-4 icon-menu me-3">🏪</span>
                        <span class="menu-text fw-medium">Mis Negocios</span>
                    </router-link>
                </li>

                <li v-if="tienePlan" class="nav-item">
                    <a href="/citas-negocios" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-4 icon-menu me-3">📆</span>
                        <span class="menu-text fw-medium">Agenda Digital</span>
                    </a>
                </li>

                <li v-if="tienePlan" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-4 icon-menu me-3">👥</span>
                        <span class="menu-text fw-medium">Usuarios y Roles</span>
                    </a>
                </li>

                <li v-if="tienePlan" class="nav-item">
                    <router-link to="/soporte" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/soporte' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-4 icon-menu me-3">🎧</span>
                        <span class="menu-text fw-medium">Soporte / Tickets</span>
                    </router-link>
                </li>
            </ul>
        </div>

        <div class="flex-grow-1 d-flex flex-column overflow-hidden w-100">
            <nav class="navbar navbar-expand bg-white shadow-sm px-4 py-3">
                <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button @click="toggleSidebar" class="btn btn-light border-0 me-4 px-3 py-2 rounded-3 transition-all hover-shadow">
                            <span class="fs-5">☰</span>
                        </button>
                        <div class="d-none d-md-block">
                            <h4 class="mb-1 fw-bold text-dark">Buenos días, <span class="text-primary">{{ usuarioLoggeado?.name || 'Administrador' }}</span></h4>
                            <p class="text-muted small mb-0">Gestión de tu sistema</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light border-0 rounded-circle position-relative p-2 hover-shadow">
                            <span class="fs-5">✉️</span>
                        </button>
                        <button class="btn btn-light border-0 rounded-circle position-relative p-2 hover-shadow">
                            <span class="fs-5">🔔</span>
                            <span class="position-absolute top-25 start-75 translate-middle p-1 bg-danger border border-light rounded-circle"></span>
                        </button>
                        <div class="dropdown ms-2">
                            <button class="btn border-0 p-0 rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img :src="`https://ui-avatars.com/api/?name=${usuarioLoggeado?.name || 'Admin'}&background=0D6EFD&color=fff`" alt="Perfil" class="rounded-circle shadow-sm" style="width: 45px; height: 45px; object-fit: cover;">
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 mt-2 p-2 rounded-4" style="min-width: 200px;">
                                <li class="px-3 py-2">
                                    <span class="fw-bold text-dark d-block">{{ usuarioLoggeado?.name || 'Administrador' }}</span>
                                    <small class="text-muted">{{ usuarioLoggeado?.email || '' }}</small>
                                </li>
                                <li><a class="dropdown-item rounded-3 py-2" href="#">👤 Mi Perfil</a></li>
                                <li><a class="dropdown-item rounded-3 py-2" href="#">✉️ Mensajes</a></li>
                                <li><hr class="dropdown-divider my-2"></li>
                                <li>
                                    <button @click="logout" class="dropdown-item rounded-3 py-2 text-danger fw-bold d-flex align-items-center">
                                        <span class="me-2">🚪</span> Cerrar sesión
                                    </button>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="p-4 p-lg-5 overflow-auto h-100">
                <slot></slot>
            </main>
        </div>
    </div>
</template>

<style scoped>
/* ── CONTENEDOR PRINCIPAL ── */
.sidebar-wrapper {
    width: 270px;
    height: 100vh;
    transition: width 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    overflow-x: hidden;
    z-index: 1000;
}

/* ── CABECERA Y LOGO ── */
.brand-header {
    height: 87px;
    white-space: nowrap;
}

.logo-container {
    width: 48px;
    height: 48px;
    transition: all 0.3s ease;
}

.brand-logo {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* ── ANIMACIÓN DE TEXTOS (Menú y Cabecera) ── */
.menu-text {
    transition: opacity 0.2s ease, transform 0.3s ease, width 0.3s ease;
    opacity: 1;
    white-space: nowrap;
}

.icon-menu {
    transition: margin 0.3s ease;
}

/* ── ESTADOS COLAPSADOS ── */
.sidebar-wrapper.collapsed {
    width: 90px;
}

.sidebar-wrapper.collapsed .menu-text {
    opacity: 0;
    width: 0;
    overflow: hidden;
    transform: translateX(-10px);
    margin-left: 0 !important;
}

/* Centrar logo al colapsar */
.sidebar-wrapper.collapsed .brand-header {
    padding-left: 0 !important;
    padding-right: 0 !important;
    justify-content: center;
}

.sidebar-wrapper.collapsed .logo-container {
    width: 42px; /* Un poco más pequeño al colapsar */
    height: 42px;
}

/* Centrar iconos del menú al colapsar */
.sidebar-wrapper.collapsed .nav-link {
    justify-content: center;
    padding-left: 0 !important;
    padding-right: 0 !important;
}

.sidebar-wrapper.collapsed .icon-menu {
    margin-right: 0 !important;
}

/* ── UTILIDADES ── */
.hover-bg-light:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}

.hover-shadow:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}
</style>
