<template>
  <div class="login-page">
    <Card class="login-card">
      <template #title>
        <div class="login-title">
          <span>Onfly – Acesso</span>
        </div>
      </template>

      <template #content>
        <form @submit.prevent="login" class="login-form">
          <div class="field">
            <label for="email">E-mail</label>
            <InputText
              id="email"
              v-model="email"
              type="email"
              placeholder="admin@onfly.test"
              autocomplete="email"
              class="w-full"
            />
          </div>

          <div class="field">
            <label for="password">Senha</label>
            <Password
              id="password"
              v-model="password"
              :feedback="false"
              toggleMask
              inputClass="w-full"
              autocomplete="current-password"
            />
          </div>

          <div v-if="error" class="error-message">
            {{ error }}
          </div>

          <div class="actions">
            <Button
              type="submit"
              label="Entrar"
              icon="pi pi-sign-in"
            />
          </div>
        </form>

        <div class="login-footer">
          <span>Ainda não tem conta?</span>
          <Button label="Cadastre-se" link @click="goToRegister" />
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../services/api';

const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');

function goToRegister() {
  router.push('/register')
}

async function login() {
  error.value = '';

  try {
    const response = await api.post('/auth/login', {
      email: email.value,
      password: password.value,
    });

    localStorage.setItem('token', response.data.token);
    localStorage.setItem('user', JSON.stringify(response.data.user));

    router.push('/dashboard');
  } catch (e) {
    console.error(e);
    error.value = 'Credenciais inválidas. Verifique e tente novamente.';
  }
}
</script>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: #f3f4f6; /* fundo claro padrão */
}

/* Quando o tema dark estiver ativo (classe app-dark no <html>) */
:global(html.app-dark) .login-page {
  background: #111; /* fundo escuro só no tema dark */
}

.login-card {
  width: 100%;
  max-width: 400px;
}

.login-title {
  text-align: center;
  font-size: 1.3rem;
  font-weight: 600;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.field label {
  font-size: 0.9rem;
}

.w-full {
  width: 100%;
}

.actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 0.75rem;
}

.error-message {
  margin-top: 0.25rem;
  color: #ff6b6b;
  font-size: 0.85rem;
}

/* Footer "Ainda não tem conta? Cadastre-se" bem alinhado */
.login-footer {
  margin-top: 1.25rem;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  font-size: 0.9rem;
}

/* Ajuste fino no botão link do PrimeVue dentro do footer */
:deep(.login-footer .p-button.p-button-link) {
  padding: 0;
  height: auto;
  line-height: 1;
}


/* Transformar o botão 'Cadastre-se' em link real, sem fundo */
:deep(.login-footer .p-button.p-button-link) {
  background: none !important;
  color: #1a6ca5 !important; /* azul institucional da Onfly */
  padding: 0 !important;
  box-shadow: none !important;
  border: none !important;
  font-weight: 600;
  text-decoration: underline;
  cursor: pointer;
}

/* No tema escuro, deixe o link em azul claro para boa leitura */
:global(html.app-dark) .login-footer .p-button.p-button-link {
  color: #7dc1ff !important;
}

</style>
