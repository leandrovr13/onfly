<template>
  <!-- Só renderiza se tiver notificação -->
  <div v-if="notification" class="modal-backdrop">
    <div class="modal-window">
      <!-- Cabeçalho -->
      <div class="modal-header">
        <span>Detalhes da Notificação</span>
        <button class="modal-close-btn" @click="emitClose">
          ✕
        </button>
      </div>

      <!-- Corpo -->
      <div class="modal-body">
        <div class="notification-card" :class="notificationVariantClass">
          <div class="notification-card-header">
            <div class="notification-card-icon-wrapper">
              <i
                class="notification-card-icon pi"
                :class="isApprovedNotification ? 'pi-check' : 'pi-times'"
              ></i>
            </div>

            <div class="notification-card-header-texts">
              <div class="notification-card-title">
                {{ notificationTitle }}
              </div>
              <div class="notification-card-subtitle">
                Pedido #{{ travelOrderId }}
              </div>
            </div>
          </div>

          <div class="notification-card-body">
            <p>{{ notificationMessageLine1 }}</p>
            <p>{{ notificationMessageLine2 }}</p>
          </div>
        </div>

        <div class="notification-meta-grid">
          <span>Solicitado em: {{ notificationRequestedAt }}</span>
          <span>Notificado em: {{ notificationNotifiedAt }}</span>
        </div>
      </div>

      <!-- Rodapé -->
      <div class="modal-footer">
        <Button label="Fechar" @click="emitClose" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import Button from 'primevue/button'

const props = defineProps({
  notification: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['close'])

const emitClose = () => {
  emit('close')
}

const dataPayload = computed(() => props.notification?.data || {})

const isApprovedNotification = computed(() => {
  const status = (dataPayload.value.new_status || '').toLowerCase()
  return status === 'aprovado'
})

const notificationVariantClass = computed(() => {
  const status = (dataPayload.value.new_status || '').toLowerCase()

  if (status === 'aprovado') return 'notification-card--success'
  if (status === 'cancelado') return 'notification-card--danger'
  return ''
})

const notificationTitle = computed(() => {
  if (!props.notification) return ''
  return isApprovedNotification.value ? 'Boa notícia! ✈️' : 'Aviso importante.'
})

const travelOrderId = computed(() => {
  return dataPayload.value.travel_order_id ?? '—'
})

const destination = computed(() => {
  return dataPayload.value.destination || 'destino não informado'
})

const notificationMessageLine1 = computed(() => {
  if (!props.notification) return ''

  if (isApprovedNotification.value) {
    return `O pedido #${travelOrderId.value}, com destino a ${destination.value}, foi aprovado.`
  }

  return `O pedido #${travelOrderId.value}, para ${destination.value}, foi cancelado.`
})

const notificationMessageLine2 = computed(() => {
  if (!props.notification) return ''

  if (isApprovedNotification.value) {
    return 'Agora é só se preparar, pois sua viagem está oficialmente em andamento.'
  }

  return 'Se quiser ajustar alguma informação ou criar um novo pedido, pode contar com a gente.'
})

const notificationRequestedAt = computed(() => {
  const raw = dataPayload.value.requested_at
  if (!raw) return '-'
  return new Date(raw).toLocaleString('pt-BR')
})

const notificationNotifiedAt = computed(() => {
  const raw = props.notification?.created_at
  if (!raw) return '-'
  return new Date(raw).toLocaleString('pt-BR')
})
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  backdrop-filter: blur(3px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-window {
  background: var(--modal-bg);
  color: var(--modal-text);
  width: 600px;
  max-width: 92vw;
  border-radius: 14px;
  overflow: hidden;
  box-shadow:
    0px 14px 28px rgba(0, 0, 0, 0.35),
    0px 10px 10px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}

.modal-header {
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid var(--modal-border);
  font-size: 1.15rem;
  font-weight: 600;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.modal-footer {
  padding: 1rem 1.5rem 1.25rem;
  display: flex;
  justify-content: flex-end;
}

.modal-close-btn {
  background: transparent;
  border: none;
  cursor: pointer;
  color: var(--modal-text);
  font-size: 1.3rem;
  opacity: 0.8;
}
.modal-close-btn:hover {
  opacity: 1;
}

/* ------ Card de notificação ------ */

.notification-card {
  background: var(--modal-card-bg);
  border: 1px solid var(--modal-border);
  padding: 1.25rem 1.5rem 1.1rem;
  border-radius: 10px;
  box-shadow: 0 8px 18px rgba(15, 23, 42, 0.12);
  --notification-accent: #16a34a;
  --notification-accent-soft: rgba(22, 163, 74, 0.14);
  border-top-width: 4px;
  border-top-color: var(--notification-accent);
}

.notification-card--danger {
  --notification-accent: #dc2626;
  --notification-accent-soft: rgba(220, 38, 38, 0.14);
}

.notification-card-header {
  display: flex;
  gap: 0.9rem;
  align-items: center;
  margin-bottom: 0.75rem;
}

.notification-card-icon-wrapper {
  width: 40px;
  height: 40px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--notification-accent-soft);
}

.notification-card-icon {
  font-size: 1.1rem;
  color: var(--notification-accent);
}

.notification-card-header-texts {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.notification-card-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--modal-text);
}

.notification-card-subtitle {
  font-size: 0.86rem;
  color: var(--modal-subtle-text);
}

.notification-card-body p {
  margin: 0 0 0.15rem;
  font-size: 0.92rem;
  color: var(--modal-subtle-text);
  line-height: 1.45;
}

/* Meta */
.notification-meta-grid {
  display: flex;
  justify-content: space-between;
  padding-top: 1rem;
  border-top: 1px solid var(--modal-border);
  font-size: 0.85rem;
  color: var(--modal-subtle-text);
}
</style>
