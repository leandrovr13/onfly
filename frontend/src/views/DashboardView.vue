<template>
  <div class="dashboard-page">
    <!-- HEADER GLOBAL -->
    <HeaderBar
      :user-name="userName"
      :avatar-url="userAvatarUrl"
      :notifications="notifications"
      :notifications-loading="notificationsLoading"
      @logout="logout"
      @profile="goToProfile"
      @open-notification="handleOpenNotification"
    />

    <!-- CONTEÚDO DO DASHBOARD -->
    <div class="dashboard-content p-4">
      <div class="flex justify-content-center">
        <div class="w-full md:w-10 lg:w-8">
          <!-- Card principal -->
          <Card class="dashboard-card">
            <template #title>
              <div class="card-header">
                <span>Pedidos de Viagem</span>
                <Button
                  label="Novo Pedido"
                  icon="pi pi-plus"
                  size="small"
                  @click="showCreateModal = true"
                />
              </div>
            </template>

            <template #content>
              <!-- Bloco de Filtros -->
              <OrdersFilterBar
                :filters="filters"
                :status-options="statusOptions"
                :city-suggestions="citySuggestions"
                @filter="loadOrders"
                @complete-city="onCityComplete"
                @focus-destination="openFilterDestinationSuggestions"
              />

             <!-- Loading -->
              <div v-if="loading" class="loading-area">
                <ProgressBar mode="indeterminate" style="height: 4px" />
                <span class="loading-text">Carregando pedidos...</span>
              </div>

              <!-- Tabela -->
              <OrdersTable
                v-else
                :orders="orders"
                :is-admin="isAdmin"
                :status-label="statusLabel"
                :status-severity="statusSeverity"
                :format-date="formatDate"
                @details="openOrderDetails"
                @approve="order => openStatusConfirm(order, 'aprovado')"
                @cancel="order => openStatusConfirm(order, 'cancelado')"
              />


            </template>
          </Card>

          <!-- Modal de novo pedido -->
          <Dialog
            v-model:visible="showCreateModal"
            header="Novo Pedido de Viagem"
            :modal="true"
            :style="{ width: '450px' }"
            :breakpoints="{ '768px': '95vw' }"
          >

            <Message
              v-if="dateError"
              severity="error"
              class="mb-3"
            >
              {{ dateError }}
            </Message>

            <div class="field">
              <label>Destino</label>
              <AutoComplete
                ref="newOrderDestinationAC"
                v-model="form.destination"
                :suggestions="citySuggestionsNewOrder"
                optionLabel="label"
                @complete="onCityCompleteNewOrder"
                placeholder="Destino"
                class="w-full"
                :minLength="0"
                :inputProps="{ onFocus: openNewOrderDestinationSuggestions }"
              />

            </div>

            <div class="field">
              <label>Data de ida</label>
              <Calendar
                v-model="form.departure_date"
                dateFormat="dd/mm/yy"
                showIcon
                class="w-full"
                :minDate="today"
                @update:modelValue="onDepartureChange"
              />
            </div>

            <div class="field">
              <label>Data de volta</label>
              <Calendar
                v-model="form.return_date"
                dateFormat="dd/mm/yy"
                showIcon
                class="w-full"
                :minDate="minReturnDate"
                @update:modelValue="onAnyDateChange"
              />
            </div>


            <template #footer>
              <Button
                label="Cancelar"
                severity="secondary"
                @click="closeModal"
              />
              <Button
                label="Criar"
                icon="pi pi-check"
                @click="createOrder"
              />
            </template>
          </Dialog>

          <!-- Modal de sucesso ao criar pedido -->
          <Dialog
            v-model:visible="showSuccessDialog"
            modal
            :closable="false"
            :style="{ width: '28rem' }"
          >
            <template #header>
              <div class="success-header">
                <span>Pedido criado com sucesso</span>
              </div>
            </template>

            <div class="success-card">
              <div class="success-icon-wrapper">
                <i class="pi pi-check success-icon"></i>
              </div>

              <div class="success-texts">
                <p class="success-title">Tudo certo por aqui!</p>
                <p class="success-message">
                  Seu pedido de viagem foi registrado e já está na fila para
                  análise. Você será notificado assim que ele for aprovado ou
                  cancelado.
                </p>

                <div
                  v-if="lastCreatedOrder"
                  class="success-summary"
                >
                  <div class="success-summary-row">
                    <span class="label">Destino:</span>
                    <span class="value">
                      {{ lastCreatedOrder.destination || '—' }}
                    </span>
                  </div>
                  <div class="success-summary-row">
                    <span class="label">Período:</span>
                    <span class="value">
                      {{ lastCreatedOrder.departure.toLocaleDateString('pt-BR') }}
                      &nbsp;até&nbsp;
                      {{ lastCreatedOrder.return.toLocaleDateString('pt-BR') }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <template #footer>
              <Button
                label="Ok"
                @click="closeSuccessDialog"
              />
            </template>
          </Dialog>


          <!-- Modal de detalhes do pedido -->
          <Dialog
            v-model:visible="showOrderDetailsDialog"
            modal
            header="Detalhes do Pedido"
            :style="{ width: '32rem' }"
          >
            <div v-if="orderDetails">
              <div class="order-details-header">
                <span class="order-id">
                  Pedido #{{ orderDetails.id }}
                </span>

                <Tag
                  :value="statusLabel(orderDetails.status)"
                  :severity="statusSeverity(orderDetails.status)"
                />
              </div>

              <div class="order-details-grid">
                <div class="order-details-row">
                  <span class="label">Destino</span>
                  <span class="value">
                    {{ orderDetails.destination || '—' }}
                  </span>
                </div>

                <div class="order-details-row">
                  <span class="label">Período</span>
                  <span class="value">
                    {{ formatDate(orderDetails.departure_date) }}
                    &nbsp;até&nbsp;
                    {{ formatDate(orderDetails.return_date) }}
                  </span>
                </div>

                <div class="order-details-row">
                  <span class="label">Solicitado por</span>
                  <span class="value">
                    {{ orderDetails.user?.name ?? '-' }}
                  </span>
                </div>

                <div class="order-details-row">
                  <span class="label">Status</span>
                  <span class="value">
                    {{ statusLabel(orderDetails.status) }}
                  </span>
                </div>
              </div>
            </div>

            <template #footer>
              <Button
                label="Fechar"
                @click="showOrderDetailsDialog = false"
              />
            </template>
          </Dialog>

          <!-- Modal de confirmação de mudança de status -->
          <Dialog
            v-model:visible="showStatusConfirmDialog"
            :modal="true"
            :style="{ width: '26rem' }"
          >
            <template #header>
              <span>
                {{ isConfirmingApproval ? 'Confirmar aprovação' : 'Confirmar cancelamento' }}
              </span>
            </template>

            <div class="status-confirm-body">
              <p class="status-confirm-main">
                {{ isConfirmingApproval
                  ? 'Quer mesmo aprovar este pedido de viagem?'
                  : 'Quer mesmo cancelar este pedido de viagem?' }}
              </p>

              <p v-if="statusConfirmOrder" class="status-confirm-details">
                Pedido #{{ statusConfirmOrder.id }}
                <span v-if="statusConfirmOrder.destination">
                  – {{ statusConfirmOrder.destination }}
                </span>
              </p>

              <p class="status-confirm-warning">
                * Essa operação é irreversível.
              </p>
            </div>

            <template #footer>
              <Button
                label="Voltar"
                severity="secondary"
                @click="cancelStatusConfirmation"
              />
              <Button
                :label="isConfirmingApproval ? 'Aprovar' : 'Cancelar Pedido'"
                :severity="isConfirmingApproval ? 'success' : 'danger'"
                @click="confirmStatusChange"
              />
            </template>
          </Dialog>


          <!-- Modal de notificações -->
          <NotificationModal
            :notification="selectedNotification"
            @close="selectedNotification = null"
          />

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { formatDate } from '../utils/date'
import HeaderBar from './HeaderBar.vue'
import NotificationModal from '../components/notifications/NotificationModal.vue'
import OrdersFilterBar from '../components/orders/OrdersFilterBar.vue'
import airportCities from '../data/airportCitiesBr.json';
import OrdersTable from '../components/orders/OrdersTable.vue'


// PrimeVue components
import Button from 'primevue/button'
import Card from 'primevue/card'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Calendar from 'primevue/calendar'
import ProgressBar from 'primevue/progressbar'

// router para logout
const router = useRouter()

//Auto complete de cidades
const citySuggestions = ref([]);

// Auto complete de cidades no modal "Novo Pedido"
const citySuggestionsNewOrder = ref([]);

//Modal de sucesso após criar pedido
const showSuccessDialog = ref(false);
const lastCreatedOrder = ref(null);

const showOrderDetailsDialog = ref(false)
const orderDetails = ref(null)

//Modal de confirmação de status
const showStatusConfirmDialog = ref(false)
const statusToConfirm = ref(null)      // 'aprovado' ou 'cancelado'
const statusConfirmOrder = ref(null)   // pedido selecionado
const isConfirmingApproval = computed(() => statusToConfirm.value === 'aprovado')



// ---------- USUÁRIO LOGADO / AVATAR ----------
const currentUser = ref(null)
const isAdmin = ref(false)

const storedUser = localStorage.getItem('user')

function openOrderDetails(order) {
  orderDetails.value = order
  showOrderDetailsDialog.value = true
}

if (storedUser) {
  try {
    const user = JSON.parse(storedUser)
    currentUser.value = user
    isAdmin.value = user.role === 'admin'
  } catch (e) {
    console.error('Erro ao ler usuário do storage', e)
  }
}

// nome e avatar que aparece no HeaderBar
const userName = computed(() => currentUser.value?.name || 'Usuário')
const userAvatarUrl = computed(() => currentUser.value?.avatar_url || null)

// estados reativos
const loading = ref(false)
const orders = ref([])
const showCreateModal = ref(false)

const notifications = ref([])
const notificationsLoading = ref(false)

const selectedNotification = ref(null)

const filters = ref({
  id: null,
  status: null,
  destination: null,
  start_date: null,
  end_date: null
})

const form = ref({
  destination: '',
  departure_date: null,
  return_date: null
})

const today = new Date();
today.setHours(0, 0, 0, 0);

const dateError = ref('');

const minReturnDate = computed(() => {
  if (form.value.departure_date) {
    const d = new Date(form.value.departure_date);
    d.setHours(0, 0, 0, 0);
    return d;
  }
  return today;
});

function onDepartureChange() {
  dateError.value = '';
  if (
    form.value.return_date &&
    new Date(form.value.return_date) < new Date(form.value.departure_date)
  ) {
    form.value.return_date = null;
  }
}

function onAnyDateChange() {
  dateError.value = '';
}


const newOrderDestinationAC = ref(null);

function openFilterDestinationSuggestions() {
  // ao focar, carregamos a lista inicial (sem termo)
  onCityComplete({ query: '' });
}

function openNewOrderDestinationSuggestions() {
  onCityCompleteNewOrder({ query: '' });

  nextTick(() => {
    if (newOrderDestinationAC.value && newOrderDestinationAC.value.show) {
      newOrderDestinationAC.value.show();
    }
  });
}



const onCityComplete = (event) => {
  const term = (event.query || '').toLowerCase().trim();

  let list = airportCities;

  // se tiver termo, filtra; se não tiver, mostra os primeiros
  if (term) {
    list = list.filter((item) => {
      const text = `${item.city} ${item.state}`.toLowerCase();
      return text.includes(term);
    });
  }

  citySuggestions.value = list
    .slice(0, 15)
    .map((item) => ({
      label: `${item.city} - ${item.state}`,
      value: item.city,
    }));
};


const onCityCompleteNewOrder = (event) => {
  const term = (event.query || '').toLowerCase().trim();

  let list = airportCities;

  if (term) {
    list = list.filter((item) => {
      const text = `${item.city} ${item.state}`.toLowerCase();
      return text.includes(term);
    });
  }

  citySuggestionsNewOrder.value = list
    .slice(0, 15)
    .map((item) => ({
      label: `${item.city} - ${item.state}`,
      value: item.city,
    }));
};



// opções de status (para o filtro)
const statusOptions = [
  { label: 'Solicitado', value: 'solicitado' },
  { label: 'Aprovado', value: 'aprovado' },
  { label: 'Cancelado', value: 'cancelado' }
]


// helpers de status para Tag
function statusLabel(status) {
  if (status === 'solicitado') return 'Solicitado'
  if (status === 'aprovado') return 'Aprovado'
  if (status === 'cancelado') return 'Cancelado'
  return status
}

function statusSeverity(status) {
  switch (status) {
    case 'aprovado':
      return 'success'
    case 'cancelado':
      return 'danger'
    default:
      return 'info'
  }
}

// converte Date -> 'YYYY-MM-DD' para a API
function toApiDate(date) {
  if (!date) return undefined
  const d = new Date(date)
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

async function handleOpenNotification(notification) {
  selectedNotification.value = notification

  if (!notification.read_at) {
    await markNotificationAsRead(notification)
  }
}


async function updateStatus(order, newStatus) {
  try {
    await api.patch(`/travel-orders/${order.id}/status`, {
      status: newStatus
    })

    await loadOrders()
  } catch (error) {
    console.error('Erro ao atualizar status', error)
  }
}

function openStatusConfirm(order, newStatus) {
  statusConfirmOrder.value = order
  statusToConfirm.value = newStatus
  showStatusConfirmDialog.value = true
}

function cancelStatusConfirmation() {
  showStatusConfirmDialog.value = false
  statusConfirmOrder.value = null
  statusToConfirm.value = null
}

async function confirmStatusChange() {
  if (!statusConfirmOrder.value || !statusToConfirm.value) {
    cancelStatusConfirmation()
    return
  }

  try {
    await updateStatus(statusConfirmOrder.value, statusToConfirm.value)
  } finally {
    // seja sucesso ou erro, fechamos o modal
    cancelStatusConfirmation()
  }
}



async function createOrder() {
  dateError.value = '';

  const missing = [];

  // -------- DESTINO OBRIGATÓRIO E VÁLIDO --------
  const dest = form.value.destination;

  const isValidDestination =
    dest &&
    typeof dest === 'object' &&
    'label' in dest &&
    'value' in dest &&
    dest.value;

  if (!isValidDestination) {
    // pode ser só vazio ou algo digitado que não veio do autocomplete
    missing.push('um destino válido');
  }

  // -------- DATAS OBRIGATÓRIAS --------
  const dep = form.value.departure_date;
  const ret = form.value.return_date;

  if (!dep) missing.push('a data de ida');
  if (!ret) missing.push('a data de volta');

  // Se tiver campos faltando → monta a mensagem dinâmica
  if (missing.length) {
    if (missing.length === 1) {
      dateError.value = `Preencha ${missing[0]} antes de criar o pedido.`;
    } else if (missing.length === 2) {
      dateError.value = `Preencha ${missing[0]} e ${missing[1]} antes de criar o pedido.`;
    } else {
      dateError.value = `Preencha ${missing.join(', ')} antes de criar o pedido.`;
    }
    return;
  }

  // ===============================
  // VALIDAR DATAS
  // ===============================

  const depDate = new Date(dep);
  const retDate = new Date(ret);

  const todayStart = new Date();
  todayStart.setHours(0, 0, 0, 0);

  depDate.setHours(0, 0, 0, 0);
  retDate.setHours(0, 0, 0, 0);

  if (depDate < todayStart) {
    dateError.value = 'A data de ida não pode ser anterior ao dia de hoje.';
    return;
  }

  if (retDate < depDate) {
    dateError.value = 'A data de volta deve ser igual ou posterior à data de ida.';
    return;
  }

  // ===============================
  // CRIAR O PEDIDO + MODAL DE SUCESSO
  // ===============================

  try {
    const destinationLabel = dest.label;
    const destinationValue = dest.value;

    lastCreatedOrder.value = {
      destination: destinationLabel,
      departure: depDate,
      return: retDate,
    };

    await api.post('/travel-orders', {
      destination: destinationValue,
      departure_date: toApiDate(dep),
      return_date: toApiDate(ret),
    });

    closeModal();
    await loadOrders();

    showSuccessDialog.value = true;
  } catch (error) {
    console.error('Erro ao criar pedido:', error);
  }
}

function closeSuccessDialog() {
  showSuccessDialog.value = false;
  lastCreatedOrder.value = null;
}

async function loadNotifications() {
  notificationsLoading.value = true
  try {
    const { data } = await api.get('/notifications')
    notifications.value = data
  } catch (error) {
    console.error('Erro ao carregar notificações', error)
  } finally {
    notificationsLoading.value = false
  }
}

async function markNotificationAsRead(notification) {
  try {
    await api.post(`/notifications/${notification.id}/read`)
    notification.read_at = new Date().toISOString()
  } catch (error) {
    console.error('Erro ao marcar notificação como lida', error)
  }
}


function closeModal() {
  showCreateModal.value = false
  form.value = {
    destination: '',
    departure_date: null,
    return_date: null
  }
}

// carrega os pedidos da API
async function loadOrders() {
  try {
    loading.value = true

    const response = await api.get('/travel-orders', {
      params: {
        id: filters.value.id || undefined,
        status: filters.value.status || undefined,
        destination: filters.value.destination
                  ? filters.value.destination.value
                  : undefined,
        start_date: toApiDate(filters.value.start_date),
        end_date: toApiDate(filters.value.end_date)
      }
    })

    // travel-orders retorna um paginator, pegamos só o "data"
    orders.value = response.data.data
  } catch (error) {
    console.error('Erro ao carregar pedidos', error)
  } finally {
    loading.value = false
  }
}

// logout
function logout() {
  localStorage.removeItem('token')
  router.push('/login')
}

// Meus Dados
function goToProfile() {
  router.push('/profile')
}

// ao abrir a página, carrega os pedidos
onMounted(() => {
  loadOrders()
  loadNotifications()
})

</script>

<style scoped>
.dashboard-page {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.dashboard-content {
  flex: 1;
}

.dashboard-card {
  margin-top: 0.5rem;
}

.card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.invisible-label {
  opacity: 0;
  height: 0;
}

.loading-area {
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
}

.loading-text {
  display: block;
  margin-top: 0.5rem;
  font-size: 0.85rem;
  opacity: 0.8;
}

.actions-cell {
  display: flex;
  gap: 0.5rem;
}

.text-muted {
  opacity: 0.6;
  font-size: 0.85rem;
}

.w-full {
  width: 100%;
}

.meta-label {
  display: block;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #6b7280;
}

.meta-value {
  display: block;
  margin-top: 0.15rem;
}

.notification-card-footer {
  margin-top: 0.5rem;
  text-align: right;
}

/* Campo 'Destino' no modal de novo pedido ocupa toda a largura */
.p-dialog .field {
  width: 100%;
}

/* Força o AutoComplete a usar toda a largura */
.p-dialog .field :deep(.p-autocomplete),
.p-dialog .field :deep(.p-inputtext) {
  width: 100%;
  box-sizing: border-box;
}

.success-header {
  font-size: 1.05rem;
  font-weight: 600;
}

.success-card {
  display: flex;
  gap: 1rem;
  align-items: flex-start;
}

.success-icon-wrapper {
  width: 42px;
  height: 42px;
  border-radius: 999px;
  background: rgba(34, 197, 94, 0.12);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.success-icon {
  font-size: 1.2rem;
  color: #16a34a;
}

.success-texts {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.success-title {
  margin: 0;
  font-weight: 600;
  font-size: 0.98rem;
}

.success-message {
  margin: 0;
  font-size: 0.9rem;
  color: #4b5563;
}

.success-summary {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid #e5e7eb;
  font-size: 0.88rem;
}

.success-summary-row {
  display: flex;
  gap: 0.3rem;
}

.success-summary-row .label {
  font-weight: 600;
  color: #374151;
}

.success-summary-row .value {
  color: #111827;
}


/* Versão dark do modal de sucesso */
html.app-dark .p-dialog .success-card {
  color: #e5e7eb;
}

html.app-dark .p-dialog .success-message {
  color: #d1d5db;
}

html.app-dark .p-dialog .success-summary {
  border-top-color: #374151;
}

html.app-dark .p-dialog .success-summary-row .label {
  color: #9ca3af;
}

html.app-dark .p-dialog .success-summary-row .value {
  color: #f9fafb;
}


/* -------------------------------
   TEMA DARK — melhor contraste
-------------------------------- */
html.app-dark .btn-approve {
  background: rgba(34, 197, 94, 0.18) !important;
  border-color: #22c55e !important;
  color: #86efac !important;
}

html.app-dark .btn-approve:hover {
  background: rgba(34, 197, 94, 0.26) !important;
}

html.app-dark .btn-cancel {
  background: rgba(239, 68, 68, 0.18) !important;
  border-color: #ef4444 !important;
  color: #fca5a5 !important;
}

html.app-dark .btn-cancel:hover {
  background: rgba(239, 68, 68, 0.26) !important;
}

/* Cabeçalho do modal de detalhes */
.order-details-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.order-id {
  font-weight: 600;
  font-size: 0.98rem;
}

/* Grid de informações */
.order-details-grid {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.order-details-row {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.order-details-row .label {
  font-size: 0.78rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  opacity: 0.7;
}

.order-details-row .value {
  font-size: 0.9rem;
}

/* Ajuste para tema dark */
html.app-dark .order-details-row .label {
  color: #9ca3af;
}

html.app-dark .order-details-row .value {
  color: #e5e7eb;
}

.status-confirm-body {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  font-size: 0.9rem;
}

.status-confirm-main {
  margin: 0;
  font-weight: 600;
}

.status-confirm-details {
  margin: 0;
  opacity: 0.85;
}

.status-confirm-warning {
  margin: 0.3rem 0 0;
  font-size: 0.85rem;
  color: #b45309; /* tom de alerta suave */
}

/* Versão dark */
html.app-dark .status-confirm-warning {
  color: #fbbf24;
}



</style>
