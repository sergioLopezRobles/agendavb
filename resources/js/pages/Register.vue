<script setup>

import { ref } from 'vue'
import { useRouter} from 'vue-router'

const router = useRouter()

const name = ref('')
const email = ref('')
const password = ref('')

const register = async () => {
    const response = await fetch('/api/register',{
        method: 'POST',
        headers: {
            'Content-Type' : 'application/json'
        },
        body: JSON.stringify({
            name: name.value,
            email: email.value,
            password: password.value
        })
    })

    const data = await response.json()

    localStorage.setItem('token', data.token)
    router.push('/dashboard')

}

</script>


<template>

    <div class="container mt-5">

        <h2>Register</h2>

        <input v-model="name" class="form-control mb-2" placeholder="Nombre">

        <input v-model="email" class="form-control mb-2" placeholder="Email">

        <input v-model="password" type="password" class="form-control mb-2" placeholder="Password">

        <button @click="register" class="btn btn-success">
            Crear cuenta
        </button>

    </div>

</template>
