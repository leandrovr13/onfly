<template>
  <div class="container">
    <h1>Painel</h1>

    <div class="topbar">
      <span>Usuário autenticado.</span>
      <button @click="logout">Sair</button>
    </div>

    <hr />

    <h2>Pedidos de Viagem</h2>

    <button @click="showCreateModal = true" class="new-btn">
        Novo Pedido
    </button>
    <div v-if="showCreateModal" class="modal-backdrop">
        <div class="modal">
            <h3>Novo Pedido de Viagem</h3>

            <label>
            Destino:
            <input v-model="form.destination" type="text" />
            </label>

            <label>
            Data de ida:
            <input v-model="form.departure_date" type="date" />
            </label>

            <label>
            Data de volta:
            <input v-model="form.return_date" type="date" />
            </label>

            <div class="modal-actions">
            <button @click="createOrder">Criar</button>
            <button @click="closeModal">Cancelar</button>
            </div>
        </div>
    </div>




    <div class="filters">
      <label>
        Status:
        <select v-model="filters.status" @change="applyFilters">
          <option value="">Todos</option>
          <option value="solicitado">Solicitado</option>
          <option value="aprovado">Aprovado</option>
          <option value="cancelado">Cancelado</option>
        </select>
      </label>

      <label>
        Destino:
        <input v-model="filters.destination" @keyup.enter="applyFilters" placeholder="Buscar destino..." />
      </label>
    </div>

    <div v-if="loading">Carregando...</div>

    <table v-if="!loading">
      <thead>
        <tr>
          <th>ID</th>
          <th>Destino</th>
          <th>Data Ida</th>
          <th>Data Volta</th>
          <th>Status</th>
          <th>Solicitado por</th>
          <th v-if="isAdmin">Ações</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="order in orders" :key="order.id">
          <td>{{ order.id }}</td>
          <td>{{ order.destination }}</td>
          <td>{{ order.departure_date }}</td>
          <td>{{ order.return_date }}</td>
          <td>{{ order.status }}</td>
          <td>{{ order.user.name }}</td>
            <td v-if="isAdmin">
                <!-- Só permite alterar se estiver "solicitado" -->
                <button
                    v-if="order.status === 'solicitado'"
                    @click="updateStatus(order, 'aprovado')"
                >
                    Aprovar
                </button>

                <button
                    v-if="order.status === 'solicitado'"
                    @click="updateStatus(order, 'cancelado')"
                >
                    Cancelar
                </button>

                <span v-if="order.status !== 'solicitado'">
                    (sem ações)
                </span>
            </td>

        </tr>
      </tbody>
    </table>
  </div>
</template>


<script setup>
import { ref, onMounted } from 'vue'
import api from '../services/api'
import { useRouter } from 'vue-router'

// router para logout
const router = useRouter()

// estados reativos
const loading = ref(false)
const orders = ref([])
const showCreateModal = ref(false)

const filters = ref({
  status: '',
  destination: ''
})

const form = ref({
  destination: '',
  departure_date: '',
  return_date: ''
})

function closeModal() {
  showCreateModal.value = false
  form.value = {
    destination: '',
    departure_date: '',
    return_date: ''
  }
}


// descobre se o usuário logado é admin
const isAdmin = ref(false)
const storedUser = localStorage.getItem('user')

if (storedUser) {
  try {
    const user = JSON.parse(storedUser)
    isAdmin.value = user.role === 'admin'
  } catch (e) {
    console.error('Erro ao ler usuário do storage', e)
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

async function createOrder() {
  try {
    await api.post('/travel-orders', {
      destination: form.value.destination,
      departure_date: form.value.departure_date,
      return_date: form.value.return_date
    })

    closeModal()
    await loadOrders()

  } catch (error) {
    console.error('Erro ao criar pedido:', error)
  }
}

// carrega os pedidos da API
async function loadOrders() {
  try {
    loading.value = true

    const response = await api.get('/travel-orders', {
      params: {
        status: filters.value.status,
        destination: filters.value.destination
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

// chamado quando o usuário muda os filtros
async function applyFilters() {
  await loadOrders()
}

// logout
function logout() {
  localStorage.removeItem('token')
  router.push('/login')
}

// ao abrir a página, carrega os pedidos
onMounted(() => {
  loadOrders()
})
</script>


<style scoped>
.page {
  max-width: 960px;
  margin: 0 auto;
  padding: 2rem;
  color: #f5f5f5;
}
.topbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1.5rem;
}
.filters {
  display: flex;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}
.error {
  margin-top: 1rem;
  color: #ff6b6b;
}
table {
  width: 100%;
  border-collapse: collapse;
}
th,
td {
  padding: 0.5rem 0.75rem;
  border-bottom: 1px solid #444;
}

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.7);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal {
  background: #222;
  padding: 20px;
  border-radius: 10px;
  width: 350px;
}

.modal label {
  display: block;
  margin-bottom: 10px;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
}

.new-btn {
  margin-bottom: 20px;
  padding: 8px 14px;
}

</style>
