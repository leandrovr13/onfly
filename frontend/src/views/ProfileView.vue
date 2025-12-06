<template>
  <div class="auth-page">
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
          </div>

          <div class="field">
            <label>Confirmar nova senha</label>
            <Password
              v-model="form.password_confirmation"
              :feedback="false"
              toggleMask
              class="w-full"
            />
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

const router = useRouter()

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

  // preview local
  avatarPreviewUrl.value = URL.createObjectURL(file)
}

async function handleUpdate() {
  try {
    loading.value = true

    const data = new FormData()
    data.append('name', form.value.name)
    data.append('phone', form.value.phone)
    data.append('email', form.value.email)

    if (form.value.password) {
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

    alert("Dados atualizados com sucesso!")
  } catch (error) {
    console.error(error)
    alert("Erro ao atualizar perfil.")
  } finally {
    loading.value = false
  }
}

function maskPhone(e) {
  let v = e.target.value;

  // remove tudo que não for número
  v = v.replace(/\D/g, '');

  // garante máximo de 11 dígitos
  v = v.substring(0, 11);

  // formatação "(XX) XXXXX-XXXX"
  if (v.length >= 1) v = v.replace(/^(\d{0,2})/, '($1');
  if (v.length >= 3) v = v.replace(/^(\(\d{2})(\d)/, '$1) $2');
  if (v.length >= 8) v = v.replace(/(\d{5})(\d)/, '$1-$2');

  form.value.phone = v;
}

function allowOnlyNumbers(e) {
  const char = String.fromCharCode(e.which);

  // Bloqueia tudo que não for número
  if (!/[0-9]/.test(char)) {
    e.preventDefault();
    return;
  }

  // Captura somente os números já digitados
  const numeric = form.value.phone.replace(/\D/g, '');

  // Se já tiver 11 dígitos → não deixa digitar mais nada
  if (numeric.length >= 11) {
    e.preventDefault();
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

.profile-avatar {
  margin: 0 auto 1.5rem auto;
  border: 3px solid #444;
  display: block;
}

.text-muted {
  font-size: 0.8rem;
  opacity: 0.7;
}

.profile-avatar img {
  object-fit: cover !important;
  object-position: center !important;
  width: 100% !important;
  height: 100% !important;
}
::v-deep(.profile-avatar img) {
  object-fit: cover !important;
  object-position: center !important;
  width: 100% !important;
  height: 100% !important;
}

</style>
