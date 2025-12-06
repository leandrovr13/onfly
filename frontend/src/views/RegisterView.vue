<template>
  <div class="auth-page">
    <Card class="auth-card">
      <template #title>
        <div class="auth-header">
          <h2>Criar conta</h2>
        </div>
      </template>

      <template #subtitle>
        <span>Preencha os dados para acessar o sistema.</span>
      </template>

      <template #content>
        <form class="auth-form" @submit.prevent="handleRegister">
          <div class="field">
            <label>Nome</label>
            <InputText
              v-model="form.name"
              required
              class="w-full"
            />
          </div>

          <div class="field">
            <label>E-mail</label>
            <InputText
              v-model="form.email"
              type="email"
              required
              class="w-full"
            />
          </div>

          <div class="field">
            <label>Telefone</label>
            <InputText
              v-model="form.phone"
              placeholder="(31) 99999-9999"
              class="w-full"
            />
          </div>

          <div class="field">
            <label>Foto (avatar)</label>
            <input
              type="file"
              accept="image/*"
              @change="onPhotoChange"
            />
          </div>

          <div class="field">
            <label>Senha</label>
            <Password
              v-model="form.password"
              :feedback="false"
              toggleMask
              class="w-full"
            />
          </div>

          <div class="field">
            <label>Confirmar senha</label>
            <Password
              v-model="form.password_confirmation"
              :feedback="false"
              toggleMask
              class="w-full"
            />
          </div>

          <Button
            type="submit"
            label="Criar conta"
            icon="pi pi-user-plus"
            class="w-full"
            :loading="loading"
          />

          <div class="auth-switch">
            <span>Já possui conta?</span>
            <Button
              label="Entrar"
              link
              @click="goToLogin"
            />
          </div>
        </form>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'

const router = useRouter()
const loading = ref(false)
const photoFile = ref(null)

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

function onPhotoChange(event) {
  const files = event.target.files
  photoFile.value = files && files[0] ? files[0] : null
}

async function handleRegister() {
  try {
    loading.value = true

    const data = new FormData()
    data.append('name', form.value.name)
    data.append('email', form.value.email)
    data.append('phone', form.value.phone || '')
    data.append('password', form.value.password)
    data.append('password_confirmation', form.value.password_confirmation)

    if (photoFile.value) {
      data.append('photo', photoFile.value)
    }

    const response = await api.post('/auth/register', data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })

    localStorage.setItem('token', response.data.token)
    localStorage.setItem('user', JSON.stringify(response.data.user))

    router.push('/dashboard')
  } catch (error) {
    console.error('Erro ao registrar usuário', error)

    // se veio erro de validação do Laravel (422)
    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors || {}
      const firstField = Object.keys(errors)[0]
      const firstMessage = firstField ? errors[firstField][0] : 'Erro de validação.'

      alert('Erro ao registrar: ' + firstMessage)
    } else {
      alert('Erro ao registrar. Verifique os dados e tente novamente.')
    }
  } finally {
    loading.value = false
  }
}


function goToLogin() {
  router.push('/login')
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

.auth-card {
  width: 100%;
  max-width: 420px;
}

.auth-header h2 {
  margin: 0;
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.auth-switch {
  margin-top: 1rem;
  display: flex;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}
.w-full {
  width: 100%;
}
</style>
