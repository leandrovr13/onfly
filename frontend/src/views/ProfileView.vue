<template>
  <div class="auth-page">
    <Toast />

    <Card class="auth-card">
      <template #title>
        <div class="auth-header">
          <h2>Meus Dados</h2>
        </div>
      </template>

      <template #subtitle>
        <span>Atualize suas informações de acesso.</span>
      </template>

      <template #content>

        <!-- PREVIEW DO AVATAR -->
        <Avatar
          :image="avatarPreviewUrl"
          :label="!avatarPreviewUrl ? initials : null"
          class="profile-avatar"
          shape="circle"
          size="xlarge"
        />

        <!-- FORMULÁRIO -->
        <form class="auth-form" @submit.prevent="handleUpdate">

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
              class="w-full"
              disabled
            />
            <small class="text-muted">
              O e-mail não pode ser alterado neste ambiente de teste.
            </small>
          </div>

          <div class="field">
            <label>Telefone</label>
            <InputText
                v-model="form.phone"
                placeholder="(31) 99999-9999"
                class="w-full"
                @keypress="allowOnlyNumbers"
                @input="maskPhone"
            />
            <small v-if="fieldErrors.phone" class="error-text">
              {{ fieldErrors.phone }}
            </small>
          </div>

          <div class="field">
            <label>Foto (avatar)</label>
            <input type="file" accept="image/*" @change="onPhotoChange" />
          </div>

          <div class="field">
            <label>Nova senha</label>
            <Password
              v-model="form.password"
              :feedback="false"
              toggleMask
              class="w-full"
            />
            <small class="text-muted">
              Deixe em branco se não quiser alterar a senha.
            </small>
            <small v-if="fieldErrors.password" class="error-text">
              {{ fieldErrors.password }}
            </small>
          </div>

          <div class="field">
            <label>Confirmar nova senha</label>
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

          <Button
            type="submit"
            label="Salvar alterações"
            icon="pi pi-check"
            class="w-full"
            :loading="loading"
          />

          <div class="auth-switch">
            <Button
              label="Voltar ao painel"
              link
              @click="goToDashboard"
            />
          </div>

        </form>

      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'

import Card from 'primevue/card'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import Avatar from 'primevue/avatar'
import { useToast } from 'primevue/usetoast'

const router = useRouter()
const toast = useToast()

const loading = ref(false)
const avatarPreviewUrl = ref(null)
const photoFile = ref(null)

const form = ref({
  name: '',
  email: '',
  phone: '',
  password: '',
  password_confirmation: '',
})

const fieldErrors = ref({
  phone: '',
  password: '',
  password_confirmation: ''
})

onMounted(() => {
  const storedUser = localStorage.getItem('user')
  if (!storedUser) {
    router.push('/login')
    return
  }

  const user = JSON.parse(storedUser)

  form.value.name = user.name
  form.value.email = user.email
  form.value.phone = user.phone
  avatarPreviewUrl.value = user.avatar_url || null
})

const initials = computed(() => {
  if (!form.value.name) return '?'
  return form.value.name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
})

function onPhotoChange(event) {
  const file = event.target.files?.[0]
  if (!file) return

  photoFile.value = file
  avatarPreviewUrl.value = URL.createObjectURL(file)
}

function clearFieldErrors() {
  fieldErrors.value.phone = ''
  fieldErrors.value.password = ''
  fieldErrors.value.password_confirmation = ''
}

async function handleUpdate() {
  clearFieldErrors()

  const hasPassword = !!form.value.password
  const hasConfirmation = !!form.value.password_confirmation

   // Telefone opcional, mas se preenchido precisa ser válido
  if (form.value.phone) {
    const numeric = form.value.phone.replace(/\D/g, '')

    // Aceita 10 dígitos (fixo) ou 11 (celular)
    if (numeric.length !== 10 && numeric.length !== 11) {
      fieldErrors.value.phone = 'Informe um telefone válido com DDD (10 ou 11 dígitos).'
      return
    }
  }

  // Só um dos campos preenchido
  if ((hasPassword && !hasConfirmation) || (!hasPassword && hasConfirmation)) {
    fieldErrors.value.password = 'Preencha os dois campos para alterar a senha.'
    fieldErrors.value.password_confirmation = 'Preencha os dois campos para alterar a senha.'
    return
  }

  // Ambos preenchidos, mas diferentes
  if (hasPassword && hasConfirmation && form.value.password !== form.value.password_confirmation) {
    fieldErrors.value.password_confirmation = 'A confirmação de senha não confere.'
    return
  }

  // Tamanho mínimo alinhado com o backend
  if (hasPassword && form.value.password.length < 8) {
    fieldErrors.value.password = 'A nova senha deve ter pelo menos 8 caracteres.'
    return
  }

  try {
    loading.value = true

    const data = new FormData()
    data.append('name', form.value.name)
    data.append('phone', form.value.phone)
    data.append('email', form.value.email)

    if (hasPassword) {
      data.append('password', form.value.password)
      data.append('password_confirmation', form.value.password_confirmation)
    }

    if (photoFile.value) {
      data.append('photo', photoFile.value)
    }

    const response = await api.post('/auth/profile', data, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    localStorage.setItem('user', JSON.stringify(response.data.user))
    avatarPreviewUrl.value = response.data.user.avatar_url

    toast.add({
      severity: 'success',
      summary: 'Perfil atualizado',
      detail: 'Seus dados foram salvos com sucesso.',
      life: 3000
    })
  } catch (error) {
    console.error(error)

    // Erros de validação vindos do backend
    if (error.response && error.response.status === 422 && error.response.data?.errors) {
      const errors = error.response.data.errors

      if (errors.password?.length) {
        fieldErrors.value.password = errors.password[0]
      }
      if (errors.password_confirmation?.length) {
        fieldErrors.value.password_confirmation = errors.password_confirmation[0]
      }

      toast.add({
        severity: 'error',
        summary: 'Erro de validação',
        detail: 'Verifique os campos destacados.',
        life: 4000
      })
    } else {
      toast.add({
        severity: 'error',
        summary: 'Erro ao atualizar',
        detail: 'Não foi possível atualizar o perfil. Tente novamente mais tarde.',
        life: 4000
      })
    }
  } finally {
    loading.value = false
  }
}

function maskPhone(e) {
  let v = e.target.value

  // remove não numéricos
  v = v.replace(/\D/g, '')

  // máximo 11 dígitos
  v = v.substring(0, 11)

  if (v.length <= 10) {
    // fixo: (XX) XXXX-XXXX
    if (v.length >= 1) v = v.replace(/^(\d{0,2})/, '($1')
    if (v.length >= 3) v = v.replace(/^(\(\d{2})(\d)/, '$1) $2')
    if (v.length >= 7) v = v.replace(/(\d{4})(\d)/, '$1-$2')
  } else {
    // celular: (XX) XXXXX-XXXX
    if (v.length >= 1) v = v.replace(/^(\d{0,2})/, '($1')
    if (v.length >= 3) v = v.replace(/^(\(\d{2})(\d)/, '$1) $2')
    if (v.length >= 8) v = v.replace(/(\d{5})(\d)/, '$1-$2')
  }

  form.value.phone = v
}


function allowOnlyNumbers(e) {
  const char = String.fromCharCode(e.which)

  if (!/[0-9]/.test(char)) {
    e.preventDefault()
    return
  }

  const numeric = form.value.phone.replace(/\D/g, '')

  if (numeric.length >= 11) {
    e.preventDefault()
  }
}

function goToDashboard() {
  router.push('/dashboard')
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
  text-align: center;
}

.text-muted {
  font-size: 0.8rem;
  opacity: 0.7;
}

.error-text {
  font-size: 0.8rem;
  color: #f44336; /* vermelho Prime-like */
}

/* Avatar */

.profile-avatar {
  margin: 0 auto 1.5rem auto;
  display: flex;
  align-items: center;
  justify-content: center;

  width: 80px;
  height: 80px;
  border-radius: 50% !important;

  border: 3px solid #444;
  overflow: hidden;
}

:deep(.profile-avatar img) {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: center !important;
}

:deep(.profile-avatar .p-avatar-text) {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.7rem;
  font-weight: 600;
}
</style>
