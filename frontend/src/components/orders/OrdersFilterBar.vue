<template>
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
            @complete="$emit('complete-city', $event)"
            placeholder="Buscar destino..."
            class="w-full"
            :minLength="0"
            :inputProps="{ onFocus: () => $emit('focus-destination') }"
          />
        </div>

        <div class="field filter-button">
          <Button
            label="Filtrar"
            icon="pi pi-filter"
            class="filter-button-inner"
            @click="$emit('filter')"
          />
        </div>
      </div>
    </template>
  </Card>
</template>

<script setup>
import { toRefs } from 'vue'
import Card from 'primevue/card'
import InputText from 'primevue/inputtext'
import Dropdown from 'primevue/dropdown'
import Calendar from 'primevue/calendar'
import AutoComplete from 'primevue/autocomplete'
import Button from 'primevue/button'

const props = defineProps({
  filters: {
    type: Object,
    required: true
  },
  statusOptions: {
    type: Array,
    required: true
  },
  citySuggestions: {
    type: Array,
    required: true
  }
})

const emit = defineEmits(['filter', 'complete-city', 'focus-destination'])

// toRefs garante reatividade dos props dentro do componente
const { filters, statusOptions, citySuggestions } = toRefs(props)
</script>


<style scoped>
.filters-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr)); /* 4 colunas iguais */
  column-gap: 1rem;
  row-gap: 1rem;
  margin-bottom: 1.75rem;
  align-items: end;
}

/* linha 2 – Destino grande + botão à direita */
.field-destination {
  grid-column: 1 / span 3; /* ocupa 3 colunas (75%) */
}

.filter-button {
  grid-column: 4 / span 1;
  display: flex;
  justify-content: flex-end !important;
  align-items: center;
  width: 100% !important;
}

.filter-button-inner {
  width: auto;
  min-width: 150px;
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

/* Campo de destino ocupa toda a largura da célula */
.field-destination :deep(.p-autocomplete) {
  width: 100%;
}

.field-destination :deep(.p-autocomplete-input),
.field-destination :deep(.p-inputtext) {
  width: 100%;
  box-sizing: border-box;
}

/* Field genérico (mantido pra consistência visual) */
.field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
</style>
