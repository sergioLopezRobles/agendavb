import { createRouter, createWebHistory } from 'vue-router'

import Login from "./pages/Login.vue";
import Register from "./pages/Register.vue";
import Dashboard from "./pages/Dashboard.vue";
import Planes from "./componentes/Planes.vue";
import PlanCard from "./componentes/PlanCard.vue";
import Negocios from "./pages/Negocios.vue";
import TicketsSoporte from "./pages/TicketsSoporte.vue";
import TicketSoporteAdmin from "./pages/TicketSoporteAdmin.vue";
import CitasClientes from "./pages/CitasClientes.vue";
import CitasNegocios from "./pages/CitasNegocios.vue";
import UsuariosAdmin from "./componentes/UsuariosAdmin.vue";
import AuditoriaAdmin from "./pages/AuditoriaAdmin.vue";
import NegociosAdmin from "./pages/NegociosAdmin.vue";

const routes = [
    {
        path: '/',
        component: Planes
    },
    {
        path: '/login',
        component: Login
    },
    {
        path: '/register',
        component: Register
    },
    {
        path: '/dashboard',
        component: Dashboard,
        meta: { requiresAuth: true }
    },
    {
        path: '/negocios',
        component: Negocios,
        meta: { requiresAuth: true }
    },
    {
        path: '/registrar-plan-negocio',
        component: PlanCard,
        meta: { requiresAuth: true }
    },
    {
        path: '/citas-negocios',
        component: CitasNegocios,
        meta: { requiresAuth: true }
    },
    {
        path: '/actualizar-estado-cita',
        component: CitasNegocios,
        meta: { requiresAuth: true }
    },
    {
        path: '/soporte',
        name: 'Soporte',
        component: TicketsSoporte,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin-soporte',
        name: 'AdminSoporte',
        component: TicketSoporteAdmin,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin-usuarios',
        name: 'AdminUsuarios',
        component: UsuariosAdmin,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin-auditoria',
        name: 'AdminAuditoria',
        component: AuditoriaAdmin,
        meta: { requiresAuth: true }
    },
    {
        path: '/admin-negocios',
        name: 'AdminNegocios',
        component: NegociosAdmin,
        meta: { requiresAuth: true }
    },
    {
        path: '/:slug',
        component: CitasClientes,
    },
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const publicpages = ['/', '/login', '/register', '/citasclientes'] // el comodín no va en este arreglo
    const authrequired = to.meta.requiresAuth

    // Validamos si la ruta a la que va no requiere auth (es pública)
    const ispublic = !authrequired;

    if (authrequired && !token) {
        return next('/')
    }
    if (token && publicpages.includes(to.path)) {
        return next('/dashboard')
    }
    next()
})

export default router
