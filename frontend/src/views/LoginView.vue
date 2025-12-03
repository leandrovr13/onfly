<template>
  <div>
    <h1>Login</h1>
    <form @submit.prevent="login">
      <input v-model="email" placeholder="Email" type="email" />
      <input v-model="password" placeholder="Password" type="password" />
      <button type="submit">Entrar</button>
    </form>

    <p v-if="error" style="color:red">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

const router = useRouter()
const email = ref('')
const password = ref('')
const error = ref('')

async function login() {
  error.value = ''

  try {
    const response = await api.post('/auth/login', {
      email: email.value,
      password: password.value
    })

    // token + usuário no localStorage
    localStorage.setItem('token', response.data.token)    
    localStorage.setItem('user', JSON.stringify(response.data.user))

    router.push('/dashboard')
  } catch (e) {
    error.value = 'Credenciais inválidas'
  }
}
</script>

