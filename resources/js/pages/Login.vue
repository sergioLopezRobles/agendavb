<script setup>

import { ref } from 'vue'
import { useRouter} from 'vue-router'

const router = useRouter()

const email = ref('')
const password = ref('')

const login = async () => {
    const response = await fetch('/api/login',{
        method: 'POST',
        headers: {
            'Content-Type' : 'application/json'
        },
        body: JSON.stringify({
            email: email.value,
            password: password.value
        })
    })

    const data = await response.json()

    if(data.token){
        localStorage.setItem('token', data.token)
        router.push('/dashboard')
    }

}
</script>

<template>

    <div class="container mt-5">

        <h2>Login</h2>

        <input v-model="email" class="form-control mb-2" placeholder="Email">

        <input v-model="password" type="password" class="form-control mb-2" placeholder="Password">

        <button @click="login" class="btn btn-primary">
            Login
        </button>

        <p class="mt-3">

            ¿No tienes cuenta?

            <router-link to="/register">
                Registrarse
            </router-link>

        </p>

    </div>

</template>
