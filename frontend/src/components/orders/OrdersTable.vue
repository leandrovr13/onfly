<template>
  <DataTable
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
            class="btn-approve"
            @click="$emit('approve', data)"
          />

          <Button
            label="Cancelar"
            icon="pi pi-times"
            size="small"
            class="btn-cancel"
            @click="$emit('cancel', data)"
          />
        </div>

        <Button
            v-else
            label="Detalhes"
            icon="pi pi-search"
            size="small"
            class="btn-details"
            @click="$emit('details', data)"
        />

      </template>
    </Column>
  </DataTable>
</template>

<script setup>
import { toRefs } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Tag from 'primevue/tag'
import Button from 'primevue/button'

const props = defineProps({
  orders: {
    type: Array,
    required: true
  },
  isAdmin: {
    type: Boolean,
    default: false
  },
  statusLabel: {
    type: Function,
    required: true
  },
  statusSeverity: {
    type: Function,
    required: true
  },
  formatDate: {
    type: Function,
    required: true
  }
})

defineEmits(['approve', 'cancel', 'details'])

// toRefs mantém reatividade dos props dentro do template
const { orders, isAdmin, statusLabel, statusSeverity, formatDate } = toRefs(props)
</script>

<style scoped>
.actions-cell {
  display: flex;
  gap: 0.5rem;
}

/* -------------------------------
   BOTÃO APROVAR
-------------------------------- */
.btn-approve {
  background: #dcfce7 !important;
  border: 2px solid #16a34a !important;
  color: #166534 !important;
  font-weight: 600;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.btn-approve:hover {
  background: #bbf7d0 !important;
  border-color: #15803d !important;
}

/* -------------------------------
   BOTÃO CANCELAR
-------------------------------- */
.btn-cancel {
  background: #fee2e2 !important;
  border: 2px solid #dc2626 !important;
  color: #991b1b !important;
  font-weight: 600;
  border-radius: 6px;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #fecaca !important;
  border-color: #b91c1c !important;
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

html.app-dark .btn-details {
  background-color: #1c2a3a !important;  /* azul bem escuro */
  border: 1px solid #4f81c7 !important;
  color: #bcd8ff !important;  /* azul claro */
}

html.app-dark .btn-details:hover {
  background-color: #223549 !important; /* hover */
  border-color: #6da1ff !important;
  color: #d6e6ff !important;
}


/* Botão DETALHES — Slate Azulada */
.btn-details {
  background-color: #e8f1ff !important;
  border: 1px solid #4f81c7 !important;
  color: #2a3f66 !important;
  font-weight: 500;
  padding: 0.45rem 0.85rem;
  border-radius: 6px;
  transition: 0.2s ease;
}

.btn-details:hover {
  background-color: #d9e9ff !important;
  border-color: #3b6fb0 !important;
  color: #1f3352 !important;
}


</style>
