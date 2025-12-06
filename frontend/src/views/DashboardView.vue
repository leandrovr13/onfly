<template>
  <div class="dashboard-page">
    <!-- HEADER GLOBAL -->
    <HeaderBar
      :user-name="userName"
      :avatar-url="userAvatarUrl"
      @logout="logout"
      @profile="goToProfile"
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
              <!-- Filtros -->
              <div class="filters-grid">
                <div class="field">
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

                <div class="field">
                  <label>Destino</label>
                  <InputText
                    v-model="filters.destination"
                    placeholder="Buscar destino..."
                    class="w-full"
                  />
                </div>

                <div class="field">
                  <label>Data inicial</label>
                  <Calendar
                    v-model="filters.start_date"
                    dateFormat="dd/mm/yy"
                    showIcon
                    class="w-full"
                  />
                </div>

                <div class="field">
                  <label>Data final</label>
                  <Calendar
                    v-model="filters.end_date"
                    dateFormat="dd/mm/yy"
                    showIcon
                    class="w-full"
                  />
                </div>

                <div class="field filter-button">
                  <label class="invisible-label">Filtrar</label>
                  <Button
                    label="Filtrar"
                    icon="pi pi-filter"
                    class="w-full"
                    @click="loadOrders"
                  />
                </div>
              </div>

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
              <InputText
                v-model="form.destination"
                class="w-full"
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

// Header novo
import HeaderBar from './HeaderBar.vue'

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

const filters = ref({
  status: '',
  destination: '',
  start_date: null,
  end_date: null
})

const form = ref({
  destination: '',
  departure_date: null,
  return_date: null
})

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
      destination: form.value.destination,
      departure_date: toApiDate(form.value.departure_date),
      return_date: toApiDate(form.value.return_date)
    })

    closeModal()
    await loadOrders()
  } catch (error) {
    console.error('Erro ao criar pedido:', error)
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
        status: filters.value.status || undefined,
        destination: filters.value.destination || undefined,
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
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-button {
  align-self: flex-end;
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
</style>
