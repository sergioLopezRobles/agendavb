import { createRouter, createWebHistory } from 'vue-router'

import Login from "./pages/Login.vue";
import Register from "./pages/Register.vue";
import Dashboard from "./pages/Dashboard.vue";
import Planes from "./componentes/Planes.vue";
import PlanCard from "./componentes/PlanCard.vue";
import Negocios from "./pages/Negocios.vue";
import TicketsSoporte from "./pages/TicketsSoporte.vue";
import CitasClientes from "./pages/CitasClientes.vue";
import CitasNegocios from "./pages/CitasNegocios.vue";

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
        path: '/soporte',
        name: 'Soporte',
        component: TicketsSoporte,
    },
    {
        path: '/:slug',
        component: CitasClientes,
        //NO SE LE AGREGA meta: { requiresAuth: true }, PARA QUE LA RUTA PUEDA SER PUBLICA
    },
    {
        path: '/citas-negocios',
        component: CitasNegocios,
        meta: { requiresAuth: true }
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token')
    const publicpages = ['/', '/login', '/register', '/citasclientes']
    const authrequired = to.meta.requiresAuth
    const ispublic = publicpages.includes(to.path)

    // NO autenticado → intenta entrar a área protegida
    if (authrequired && !token) {
        return next('/')
    }
    // Autenticado → intenta ir a login, register o home
    if (token && ispublic) {
        return next('/dashboard')
    }
    next()
    return true
})

export default router
