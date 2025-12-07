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
              <Card class="filters-card">
                <template #title>
                  <span class="filters-title">Filtros</span>
                </template>
                <template #content>
                  <div class="filters-grid">
                        <!-- LINHA 1 -->
                        <div class="field field-id">
                          <label>ID do Pedido</label>
                          <InputText
                            v-model="filters.id"
                            class="w-full"
                            type="number"
                            min="1"
                            placeholder="Ex: 5"
                          />
                        </div>

                        <div class="field field-status">
                          <label>Status</label>
                          <Dropdown
                            v-model="filters.status"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Todos"
                            showClear
                            class="w-full"
                          />
                        </div>

                        <div class="field field-start-date">
                          <label>Data inicial</label>
                          <Calendar
                            v-model="filters.start_date"
                            dateFormat="dd/mm/yy"
                            showIcon
                            class="w-full"
                          />
                        </div>

                        <div class="field field-end-date">
                          <label>Data final</label>
                          <Calendar
                            v-model="filters.end_date"
                            dateFormat="dd/mm/yy"
                            showIcon
                            class="w-full"
                          />
                        </div>

                        <!-- LINHA 2 -->
                        <div class="field field-destination">
                          <label>Destino</label>
                          <AutoComplete
                            v-model="filters.destination"
                            :suggestions="citySuggestions"
                            optionLabel="label"
                            @complete="onCityComplete"
                            placeholder="Buscar destino..."
                            class="w-full"
                            :minLength="2"
                          />
                        </div>

                        <div class="field filter-button">
                          <Button
                            label="Filtrar"
                            icon="pi pi-filter"
                            class="filter-button-inner"
                            @click="loadOrders"
                          />
                        </div>
                  </div>
                </template>
              </Card>

              <!-- Loading -->
              <div v-if="loading" class="loading-area">
                <ProgressBar mode="indeterminate" style="height: 4px" />
                <span class="loading-text">Carregando pedidos...</span>
              </div>


              <!-- Tabela -->
              <DataTable
                v-else
                :value="orders"
                dataKey="id"
                responsiveLayout="scroll"
                class="mt-3"
              >
                <Column field="id" header="ID" style="width: 80px" />

                <Column field="destination" header="Destino" />

                <Column header="Data Ida">
                  <template #body="{ data }">
                    {{ formatDate(data.departure_date) }}
                  </template>
                </Column>

                <Column header="Data Volta">
                  <template #body="{ data }">
                    {{ formatDate(data.return_date) }}
                  </template>
                </Column>

                <Column header="Status">
                  <template #body="{ data }">
                    <Tag
                      :value="statusLabel(data.status)"
                      :severity="statusSeverity(data.status)"
                    />
                  </template>
                </Column>

                <Column header="Solicitado por">
                  <template #body="{ data }">
                    {{ data.user?.name ?? '-' }}
                  </template>
                </Column>

                <Column
                  v-if="isAdmin"
                  header="Ações"
                  style="width: 230px"
                >
                  <template #body="{ data }">
                    <div v-if="data.status === 'solicitado'" class="actions-cell">
                      <Button
                        label="Aprovar"
                        icon="pi pi-check"
                        size="small"
                        @click="updateStatus(data, 'aprovado')"
                      />
                      <Button
                        label="Cancelar"
                        icon="pi pi-times"
                        size="small"
                        severity="danger"
                        outlined
                        @click="updateStatus(data, 'cancelado')"
                      />
                    </div>

                    <span v-else class="text-muted">
                      (sem ações)
                    </span>
                  </template>
                </Column>
              </DataTable>
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
            <div class="field">
              <label>Destino</label>
              <AutoComplete
                v-model="form.destination"
                :suggestions="citySuggestionsNewOrder"
                optionLabel="label"
                @complete="onCityCompleteNewOrder"
                placeholder="Destino"
                class="w-full"
                :minLength="2"
              />
            </div>


            <div class="field">
              <label>Data de ida</label>
              <Calendar
                v-model="form.departure_date"
                dateFormat="dd/mm/yy"
                showIcon
                class="w-full"
              />
            </div>

            <div class="field">
              <label>Data de volta</label>
              <Calendar
                v-model="form.return_date"
                dateFormat="dd/mm/yy"
                showIcon
                class="w-full"
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

          <Dialog
            v-model:visible="showNotificationDialog"
            modal
            header="Detalhes da Notificação"
            style="width: 32rem"
          >
            <div
              v-if="selectedNotification"
              :class="['notification-card', notificationVariantClass]"
            >
              <div class="notification-card-header">
                <div class="notification-card-icon">
                  <i
                    v-if="selectedNotification.data.new_status === 'aprovado'"
                    class="pi pi-check"
                  />
                  <i
                    v-else-if="selectedNotification.data.new_status === 'cancelado'"
                    class="pi pi-times"
                  />
                  <i
                    v-else
                    class="pi pi-info-circle"
                  />
                </div>

                <div class="notification-card-title">
                  <p class="notification-card-title-main">
                    <span v-if="selectedNotification.data.new_status === 'aprovado'">
                      Boa notícia!
                    </span>
                    <span v-else-if="selectedNotification.data.new_status === 'cancelado'">
                      Aviso importante.
                    </span>
                    <span v-else>
                      Atualização do pedido.
                    </span>
                  </p>
                  <p class="notification-card-title-sub">
                    Pedido #{{ selectedNotification.data.travel_order_id }}
                  </p>
                </div>
              </div>

              <div class="notification-card-body">
                <p>
                  O pedido
                  <strong>#{{ selectedNotification.data.travel_order_id }}</strong>,
                  com destino a
                  <strong>{{ selectedNotification.data.destination }}</strong>,
                  foi
                  <strong>{{ selectedNotification.data.new_status }}</strong>.
                </p>

                <p
                  v-if="selectedNotification.data.new_status === 'aprovado'"
                  class="notification-card-message"
                >
                  Agora é só se preparar — sua viagem está oficialmente em andamento.
                </p>

                <p
                  v-else-if="selectedNotification.data.new_status === 'cancelado'"
                  class="notification-card-message"
                >
                  Se quiser ajustar alguma informação ou criar um novo pedido,
                  pode contar com a gente.
                </p>
              </div>

              <div class="notification-card-meta">
                <div>
                  <span class="meta-label">Solicitado em</span>
                  <span class="meta-value">
                    {{ selectedNotification.data.requested_at }}
                  </span>
                </div>
                <div>
                  <span class="meta-label">Notificado em</span>
                  <span class="meta-value">
                    {{ new Date(selectedNotification.created_at).toLocaleString('pt-BR') }}
                  </span>
                </div>
              </div>

              <div class="notification-card-footer">
                <Button
                  label="Fechar"
                  severity="secondary"
                  @click="closeNotificationDialog"
                />
              </div>
            </div>
          </Dialog>



        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../services/api'
import { formatDate } from '../utils/date'
import HeaderBar from './HeaderBar.vue'
import airportCities from '../data/airportCitiesBr.json';


// PrimeVue components
import Button from 'primevue/button'
import Card from 'primevue/card'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Dialog from 'primevue/dialog'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import InputText from 'primevue/inputtext'
import ProgressBar from 'primevue/progressbar'

// router para logout
const router = useRouter()

//Auto complete de cidades
const citySuggestions = ref([]);

// Auto complete de cidades no modal "Novo Pedido"
const citySuggestionsNewOrder = ref([]);

// ---------- USUÁRIO LOGADO / AVATAR ----------
const currentUser = ref(null)
const isAdmin = ref(false)

const storedUser = localStorage.getItem('user')

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
const showNotificationDialog = ref(false)

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

const notificationVariantClass = computed(() => {
  if (!selectedNotification.value) return ''

  const status = selectedNotification.value.data.new_status

  if (status === 'aprovado') {
    return 'notification-card--success'
  }

  if (status === 'cancelado') {
    return 'notification-card--danger'
  }

  return ''
})

const onCityComplete = (event) => {
  const term = (event.query || '').toLowerCase().trim();

  if (!term || term.length < 2) {
    citySuggestions.value = [];
    return;
  }

  citySuggestions.value = airportCities
    .filter((item) => {
      const text = `${item.city} ${item.state}`.toLowerCase();
      return text.includes(term);
    })
    .slice(0, 15)
    .map((item) => ({
      label: `${item.city} - ${item.state}`,
      value: item.city
    }));
};

const onCityCompleteNewOrder = (event) => {
  const term = (event.query || '').toLowerCase().trim();

  if (!term || term.length < 2) {
    citySuggestionsNewOrder.value = [];
    return;
  }

  citySuggestionsNewOrder.value = airportCities
    .filter((item) => {
      const text = `${item.city} ${item.state}`.toLowerCase();
      return text.includes(term);
    })
    .slice(0, 15)
    .map((item) => ({
      label: `${item.city} - ${item.state}`,
      value: item.city
    }));
};


// opções de status (para o filtro)
const statusOptions = [
  { label: 'Solicitado', value: 'solicitado' },
  { label: 'Aprovado', value: 'aprovado' },
  { label: 'Cancelado', value: 'cancelado' }
]

if (storedUser) {
  try {
    const user = JSON.parse(storedUser)
    isAdmin.value = user.role === 'admin'
  } catch (e) {
    console.error('Erro ao ler usuário do storage', e)
  }
}

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
  showNotificationDialog.value = true

  if (!notification.read_at) {
    await markNotificationAsRead(notification)
  }
}


function closeNotificationDialog() {
  showNotificationDialog.value = false
  selectedNotification.value = null
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

async function createOrder() {
  try {
    await api.post('/travel-orders', {
      destination: form.value.destination
        ? form.value.destination.value
        : '',
      departure_date: toApiDate(form.value.departure_date),
      return_date: toApiDate(form.value.return_date)
    })

    closeModal()
    await loadOrders()
  } catch (error) {
    console.error('Erro ao criar pedido:', error)
  }
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

.filters-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr)); /* 4 colunas iguais */
  column-gap: 1rem;
  row-gap: 1rem;
  margin-bottom: 1.75rem;
  align-items: end;
}

/* linha 1 – 4 colunas normais (ID, Status, Data inicial, Data final) */
/* linha 2 – Destino grande + botão à direita */
.field-destination {
  grid-column: 1 / span 3;  /* ocupa 3 colunas (75%) */
}

.filter-button {
  grid-column: 4 / span 1;
  display: flex;
  justify-content: flex-end !important;
  align-items: center;
  width: 100% !important;
}

.filter-button-inner {
  width: auto;      /* deixa o botão com tamanho natural */
  min-width: 150px; /* opcional – dá mais presença visual */
}


/* TELAS MÉDIAS (2 colunas) */
@media (max-width: 992px) {
  .filters-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .field-destination {
    grid-column: 1 / span 2;
  }

  .filter-button {
    grid-column: 1 / span 2;
    justify-content: flex-start;
  }

  .filter-button-inner {
    max-width: none;
  }
}

/* TELAS PEQUENAS (1 coluna) */
@media (max-width: 640px) {
  .filters-grid {
    grid-template-columns: 1fr;
  }

  .field-destination,
  .filter-button {
    grid-column: auto;
  }

  .filter-button-inner {
    width: 100%;
  }
}

/* Forçar o AutoComplete do PrimeVue a ocupar toda a largura da célula */
.field-destination :deep(.p-autocomplete) {
  width: 100%;
}

/* E o input interno também */
.field-destination :deep(.p-autocomplete-input),
.field-destination :deep(.p-inputtext) {
  width: 100%;
  box-sizing: border-box;
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

.notification-card {
  background: #111827;
  border-radius: 0.75rem;
  border: 1px solid #374151;
  padding: 1.2rem 1.4rem 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  box-shadow: 0 18px 40px rgba(0, 0, 0, 0.45);
}

/* faixa superior de cor, de acordo com o status */
.notification-card--success {
  border-top: 4px solid #16a34a;
}

.notification-card--danger {
  border-top: 4px solid #ef4444;
}

.notification-card-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.notification-card-icon {
  width: 2.4rem;
  height: 2.4rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(55, 65, 81, 0.7);
  flex-shrink: 0;
}

.notification-card--success .notification-card-icon {
  background: rgba(22, 163, 74, 0.15);
  color: #4ade80;
}

.notification-card--danger .notification-card-icon {
  background: rgba(239, 68, 68, 0.15);
  color: #fca5a5;
}

.notification-card-title-main {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #e5e7eb;
}

.notification-card-title-sub {
  margin: 0;
  font-size: 0.85rem;
  color: #9ca3af;
}

.notification-card-body {
  font-size: 0.9rem;
  color: #e5e7eb;
}

.notification-card-message {
  margin-top: 0.5rem;
  color: #d1d5db;
}

.notification-card-meta {
  display: flex;
  justify-content: space-between;
  gap: 1rem;
  font-size: 0.8rem;
  color: #9ca3af;
  border-top: 1px solid #1f2933;
  padding-top: 0.75rem;
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


</style>
