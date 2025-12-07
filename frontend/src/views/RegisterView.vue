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
            <small v-if="fieldErrors.name" class="error-text">
              {{ fieldErrors.name }}
            </small>
          </div>

          <div class="field">
            <label>E-mail</label>
            <InputText
              v-model="form.email"
              type="email"
              required
              class="w-full"
            />
            <small v-if="fieldErrors.email" class="error-text">
              {{ fieldErrors.email }}
            </small>
          </div>

          <div class="field">
            <label>Telefone</label>
            <InputText
              v-model="form.phone"
              placeholder="(31) 99999-9999"
              class="w-full"
              @input="form.phone = formatPhone(form.phone)"
              @keypress="allowOnlyNumbers"
            />
            <small v-if="fieldErrors.phone" class="error-text">
              {{ fieldErrors.phone }}
            </small>
          </div>


          <div class="field">
            <label>Foto (avatar)</label>
            <input
              type="file"
              accept="image/*"
              @change="onPhotoChange"
            />
            <small v-if="fieldErrors.photo" class="error-text">
              {{ fieldErrors.photo }}
            </small>
          </div>

          <div class="field">
            <label>Senha</label>
            <Password
              v-model="form.password"
              :feedback="false"
              toggleMask
              class="w-full"
            />
            <small v-if="fieldErrors.password" class="error-text">
              {{ fieldErrors.password }}
            </small>
          </div>

          <div class="field">
            <label>Confirmar senha</label>
            <Password
              v-model="form.password_confirmation"
              :feedback="false"
              toggleMask
              class="w-full"
            />
            <small v-if="fieldErrors.password_confirmation" class="error-text">
              {{ fieldErrors.password_confirmation }}
            </small>
          </div>

          <div v-if="generalError" class="form-error">
            {{ generalError }}
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

const fieldErrors = ref({})
const generalError = ref('')
const errorTranslations = {
  "The name field is required.": "O campo nome é obrigatório.",
  "The email field is required.": "O campo e-mail é obrigatório.",
  "The phone field is required.": "O campo telefone é obrigatório.",
  "The password field is required.": "O campo senha é obrigatório.",
  "The password confirmation does not match.": "A confirmação de senha não confere.",
  "The email has already been taken.": "Este e-mail já está em uso.",
  "The phone has already been taken.": "Este telefone já está em uso.",
  "The password must be at least 8 characters.": "A senha deve ter pelo menos 8 caracteres.",
  "The password must contain at least one uppercase letter.": "A senha deve conter pelo menos uma letra maiúscula.",
  "The password must contain at least one number.": "A senha deve conter pelo menos um número.",
  "The email must be a valid email address.": "Informe um e-mail válido.",
  "The password field confirmation does not match.": "A senha digitada na confirmação não confere.",
};

function translateError(message) {
  return errorTranslations[message] || message; // fallback: mantém original caso não mapeado
}


function onPhotoChange(event) {
  const files = event.target.files
  photoFile.value = files && files[0] ? files[0] : null
}

function formatPhone(value) {
  if (!value) return ''
  return value
    .replace(/\D/g, '')
    .replace(/^(\d{2})(\d)/, '($1) $2')
    .replace(/(\d{5})(\d)/, '$1-$2')
    .slice(0, 15)
}

function allowOnlyNumbers(e) {
  // Permite atalhos como Ctrl+V / Cmd+V (colar), Ctrl+C etc.
  if (e.ctrlKey || e.metaKey) {
    return;
  }

  const allowedKeys = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Enter'];

  // permite teclas de controle
  if (allowedKeys.includes(e.key)) {
    return;
  }

  // bloqueia tudo que não for número
  if (!/^[0-9]$/.test(e.key)) {
    e.preventDefault();
    return;
  }

  // impede que passe de 11 dígitos numéricos
  const numeric = form.value.phone.replace(/\D/g, '');
  if (numeric.length >= 11) {
    e.preventDefault();
  }
}

async function handleRegister() {
  try {
    loading.value = true
    fieldErrors.value = {}
    generalError.value = ''

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

    if (error.response && error.response.status === 422) {
      const errors = error.response.data.errors || {}
      const mapped = {}

      Object.keys(errors).forEach((field) => {
        const original = errors[field][0]
        mapped[field] = translateError(original)
      })
      

      fieldErrors.value = mapped
    } else {
      generalError.value =
        'Não foi possível concluir o cadastro. Verifique os dados e tente novamente.'
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

.error-text {
  color: #e55b5b;
  font-size: 0.8rem;
}

.form-error {
  margin-top: 0.5rem;
  margin-bottom: 0.25rem;
  color: #e55b5b;
  font-size: 0.85rem;
}
</style>
