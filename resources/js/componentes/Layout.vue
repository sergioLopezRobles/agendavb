<script setup>
import { ref } from "vue";
import { useRouter } from 'vue-router';

const router = useRouter();
const isSidebarExpanded = ref(true);

//RECIBIR LOS DATOS DE LA VISTA QUE ESTE USANDO LAYOUT
const props = defineProps({
    usuarioLoggeado: Object,
    planAdquirido: Object
});

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
    localStorage.removeItem('token');
    localStorage.removeItem('userEmail');
    localStorage.removeItem('planSeleccionado');
    router.push('/');
};
</script>

<template>
    <div class="d-flex bg-light min-vh-100">

        <div :class="['sidebar-wrapper bg-white shadow-sm d-flex flex-column sticky-top', { 'collapsed': !isSidebarExpanded }]">
            <div class="d-flex align-items-center p-3 border-bottom brand-header">
                <span class="fs-2 text-primary me-2">🏢</span>
                <h4 class="fw-bold mb-0 text-dark menu-text text-truncate">Vista Boreal</h4>
            </div>

            <ul class="nav flex-column mt-3 px-2 gap-1">
                <li class="nav-item">
                    <router-link to="/dashboard" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/dashboard' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-3 me-3">📊</span>
                        <span class="menu-text fw-semibold">Dashboard</span>
                    </router-link>
                </li>

                <li v-if="planAdquirido" class="nav-item mt-2 pt-2 border-top">
                    <small class="text-muted fw-bold ms-3 menu-text d-block mb-2">MÓDULOS</small>
                </li>

                <li v-if="planAdquirido" class="nav-item">
                    <router-link to="/negocios" class="nav-link rounded-3 d-flex align-items-center py-2 px-3" :class="[$route.path === '/negocios' ? 'bg-primary text-white' : 'text-dark hover-bg-light']">
                        <span class="fs-3 me-3">🏪</span>
                        <span class="menu-text fw-medium">Mis Negocios</span>
                    </router-link>
                </li>

                <li v-if="planAdquirido" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-3 me-3">📆</span>
                        <span class="menu-text fw-medium">Agenda Digital</span>
                    </a>
                </li>
                <li v-if="planAdquirido" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-3 me-3">👥</span>
                        <span class="menu-text fw-medium">Usuarios y Roles</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="flex-grow-1 d-flex flex-column overflow-hidden w-100">
            <nav class="navbar navbar-expand bg-white shadow-sm px-4 py-3">
                <div class="container-fluid p-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <button @click="toggleSidebar" class="btn btn-light border-0 me-4 px-3 py-2 rounded-3">
                            <span class="fs-5">☰</span>
                        </button>
                        <div class="d-none d-md-block">
                            <h4 class="mb-1 fw-bold text-dark">Buenos días, <span class="text-primary">{{ usuarioLoggeado?.name || 'Administrador' }}</span></h4>
                            <p class="text-muted small mb-0">Gestión de tu sistema</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-light border-0 rounded-circle position-relative p-2">
                            <span class="fs-5">✉️</span>
                        </button>
                        <button class="btn btn-light border-0 rounded-circle position-relative p-2">
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
.sidebar-wrapper {
    width: 260px;
    height: 100vh;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    overflow-x: hidden;
    z-index: 1000;
}
.sidebar-wrapper.collapsed {
    width: 80px;
}
.sidebar-wrapper.collapsed .menu-text {
    opacity: 0;
    pointer-events: none;
    display: none;
}
.brand-header {
    height: 87px;
}
.hover-bg-light:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}
</style>
