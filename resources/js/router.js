import { createRouter, createWebHistory } from 'vue-router'

import Login from "./pages/Login.vue";
import Register from "./pages/Register.vue";
import Dashboard from "./pages/Dashboard.vue";
import Planes from "./componentes/Planes.vue";

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
    }

]

const router = createRouter({
    history: createWebHistory(),
    routes
})

router.beforeEach((to, from, next) => {

    const token = localStorage.getItem('token')

    const publicpages = ['/', '/login', '/register']
    const authrequired = to.meta.requiresAuth
    const ispublic = publicpages.includes(to.path)

    // ❌ NO autenticado → intenta entrar a dashboard
    if (authrequired && !token)
    {
        return next('/') // ← CAMBIO AQUI (antes era /login)
    }


    // ✅ Autenticado → intenta ir a login, register o home
    if (token && ispublic)
    {
        return next('/dashboard')
    }


    next()
})



export default router
