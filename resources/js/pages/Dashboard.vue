<script setup>

import { useRouter } from 'vue-router'

const router = useRouter()

const logout = async () =>
{
    const token = localStorage.getItem('token')

    try
    {
        await fetch('/api/logout', {

            method: 'POST',

            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            }

        })

    }
    catch (error)
    {
        console.log(error)
    }

    // borrar token del navegador
    localStorage.removeItem('token')

    // borrar plan seleccionado si quieres
    localStorage.removeItem('planSeleccionado')

    // redirigir al inicio
    router.push('/')
}

const plan = JSON.parse(localStorage.getItem('planSeleccionado'))
console.log('nombreplan: ' + plan.nombre )
console.log('precioplan: ' + plan.precio )

</script>

<template>

    <div>

        <!-- Navbar -->
        <nav class="navbar navbar-dark bg-dark px-3">

            <span class="navbar-brand">
                Dashboard
            </span>

            <button
                @click="logout"
                class="btn btn-outline-light btn-sm"
            >
                Cerrar sesión
            </button>

        </nav>


        <!-- Contenido -->
        <div class="container mt-5">

            <h2>Bienvenido al Dashboard</h2>

            <p>
                Ya iniciaste sesión correctamente.
            </p>

        </div>

    </div>

</template>
