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

// 1. Importamos el componente de recuperación
import RecuperarPassword from "./componentes/RecuperarPassword.vue";

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
    // 2. Registramos la nueva ruta
    {
        path: '/recuperar-password',
        component: RecuperarPassword
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
    // Ojo: Esta ruta con el comodín (slug) siempre debe ir al final
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

    // 3. Agregamos la ruta aquí para que un usuario logueado no pueda ver la pantalla de recuperar
    const publicpages = ['/', '/login', '/register', '/citasclientes', '/recuperar-password']
    const authrequired = to.meta.requiresAuth

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
