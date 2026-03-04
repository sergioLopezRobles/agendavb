<script setup>
import { useRouter } from 'vue-router'
import { onMounted, ref } from "vue";
import PlanCard from "../componentes/PlanCard.vue";

const router = useRouter()
const token = ref(localStorage.getItem('token'))
const planes = ref([])
const isSidebarExpanded = ref(true)

const planAdquirido = ref(null)
const usuarioLoggeado = ref(null)

const mostrarTodosPlanes = ref(false)

const toggleSidebar = () => {
    isSidebarExpanded.value = !isSidebarExpanded.value
}

onMounted(async () => {
    cargarDashboard()
})

const cargarDashboard = async()  => {
    const planGuardado = JSON.parse(localStorage.getItem('planSeleccionado'))
    if(planGuardado){
        console.log(planGuardado)
        planes.value = [planGuardado]
        mostrarTodosPlanes.value = false
    }else {
        const response = await fetch('/api/planes')
        planes.value = await response.json()
        console.log(planes)
        mostrarTodosPlanes.value = true
    }

    try{
        const response = await fetch('/api/dashboard',{
            method: 'GET',
            headers: {
                'Content-Type' : 'application/json',
                // Es vital enviar el token para que el middleware auth:sanctum te deje pasar
                'Authorization': `Bearer ${token.value}`
            }
        })

        const data = await response.json()

        if(data.valid){
            usuarioLoggeado.value = data.usuarioLoggeado
            console.log(usuarioLoggeado)
            planAdquirido.value = data.planAdquirido
        }

    }catch (error) {
        console.error("Error en la petición:", error)
        window.$toast.show('Error al conectar con el servidor', 'danger', 5000)
    }
}

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
    localStorage.removeItem('userEmail')
    localStorage.removeItem('planSeleccionado')

    // redirigir al inicio
    router.push('/')
}

const eventMostrarTodosPlanes = async () => {
    const response = await fetch('/api/planes')
    planes.value = await response.json()
    mostrarTodosPlanes.value = true
}
</script>

<template>
    <div class="d-flex bg-light min-vh-100">

        <div :class="['sidebar-wrapper bg-white shadow-sm d-flex flex-column sticky-top', { 'collapsed': !isSidebarExpanded }]">

            <div class="d-flex align-items-center p-3 border-bottom brand-header">
                <span class="fs-3 text-primary me-2">🏢</span>
                <h4 class="fw-bold mb-0 text-dark menu-text text-truncate">Vista Boreal</h4>
            </div>

            <ul class="nav flex-column mt-3 px-2 gap-1">
                <li class="nav-item">
                    <a href="#" class="nav-link text-white bg-primary rounded-3 d-flex align-items-center py-2 px-3">
                        <span class="fs-5 me-3">📊</span>
                        <span class="menu-text fw-semibold">Dashboard</span>
                    </a>
                </li>

                <li v-if="planAdquirido" class="nav-item mt-2 pt-2 border-top">
                    <small class="text-muted fw-bold ms-3 menu-text d-block mb-2">MODULOS</small>
                </li>

                <li v-if="planAdquirido" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-5 me-3">📆</span>
                        <span class="menu-text fw-medium">Agenda Digital</span>
                    </a>
                </li>
                <li v-if="planAdquirido" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-5 me-3">👥</span>
                        <span class="menu-text fw-medium">Usuarios y Roles</span>
                    </a>
                </li>
                <li v-if="planAdquirido" class="nav-item">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-5 me-3">💬</span>
                        <span class="menu-text fw-medium">Notificaciones WA</span>
                    </a>
                </li>

                <li class="nav-item" :class="{ 'mt-2 pt-2 border-top': !planAdquirido }">
                    <a href="#" class="nav-link text-dark rounded-3 d-flex align-items-center py-2 px-3 hover-bg-light">
                        <span class="fs-5 me-3">💳</span>
                        <span class="menu-text fw-medium">Membresías</span>
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
                            <p class="text-muted small mb-0">Tu resumen de rendimiento del sistema</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center gap-3">

                        <div class="input-group d-none d-lg-flex bg-light rounded-pill px-3 py-1 me-2" style="width: 250px;">
                            <span class="input-group-text border-0 bg-transparent p-0 me-2">🔍</span>
                            <input type="text" class="form-control border-0 bg-transparent shadow-none" placeholder="Buscar...">
                        </div>

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
                                <li><hr class="dropdown-divider my-2"></li>
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

                <div v-if="planAdquirido">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-dark">Vista General</h5>
                        <button class="btn btn-outline-primary rounded-pill px-4 shadow-sm fw-semibold ">
                            <span class="me-1">+</span> Nueva Cita
                        </button>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-6 col-lg-3">
                            <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-primary">
                                <div class="card-body">
                                    <h6 class="text-muted fw-bold mb-2">Citas Hoy</h6>
                                    <h3 class="fw-bold mb-0">12</h3>
                                    <small class="text-primary fw-semibold d-flex align-items-center mt-2">
                                        <span class="me-1">📈</span> +2% desde ayer
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-warning">
                                <div class="card-body">
                                    <h6 class="text-muted fw-bold mb-2">Usuarios Activos</h6>
                                    <h3 class="fw-bold mb-0">5</h3>
                                    <small class="text-muted fw-semibold d-flex align-items-center mt-2">
                                        <span class="me-1">👥</span> Personal en turno
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-success">
                                <div class="card-body">
                                    <h6 class="text-muted fw-bold mb-2">Créditos WhatsApp</h6>
                                    <h3 class="fw-bold mb-0">{{ planAdquirido?.whatsapp_creditos_iniciales || '0' }}</h3>
                                    <small class="text-success fw-semibold d-flex align-items-center mt-2">
                                        <span class="me-1">💬</span> Activo durante 7 días
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-3">
                            <div class="card shadow-sm border-0 rounded-4 h-100 border-start border-4 border-info">
                                <div class="card-body">
                                    <h6 class="text-muted fw-bold mb-2">Plan Adquirido</h6>
                                    <h3 class="fw-bold mb-0 text-dark">{{ planAdquirido?.nombre || 'Cargando...' }}</h3>
                                    <small class="text-info fw-semibold d-flex align-items-center mt-2">
                                        <span class="me-1">💳</span> Próx. cobro 24 Jun
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="!planAdquirido">
                    <div class="row mb-5">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark">
                                Tu Configuración de Plan
                            </h5>

                            <button
                                v-if="!mostrarTodosPlanes" @click="eventMostrarTodosPlanes" class="btn btn-link fw-bold fs-6 p-0">
                                Ver todos los planes
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="card shadow-sm border-0 rounded-4 bg-transparent">
                                <div class="row g-4">
                                    <PlanCard
                                        v-for="plan in planes"
                                        :key="plan.id"
                                        :plan="plan"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


            </main>
        </div>
    </div>
</template>

<style scoped>
/* Transición y tamaño del Sidebar */
.sidebar-wrapper {
    width: 260px;
    height: 100vh;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
    overflow-x: hidden;
    z-index: 1000;
}

/* Estado colapsado del Sidebar */
.sidebar-wrapper.collapsed {
    width: 80px;
}

/* Ocultar los textos cuando está colapsado para que solo queden los iconos */
.sidebar-wrapper.collapsed .menu-text {
    opacity: 0;
    pointer-events: none;
    display: none;
}

/* Asegurar altura del encabezado del logo */
.brand-header {
    height: 87px;
}

/* Efecto hover suave en los botones del menú */
.hover-bg-light:hover {
    background-color: #f8f9fa;
    transition: background-color 0.2s ease;
}
</style>
