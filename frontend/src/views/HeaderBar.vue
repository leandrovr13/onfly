<template>
  <header class="header">
    <div class="left">
      <i class="pi pi-briefcase logo-icon"></i>
      <div class="titles">
        <span class="system-name">Onfly Travel Orders</span>
        <small class="system-subtitle">Painel de viagens corporativas</small>
      </div>
    </div>

    <div class="right">
        <span class="greeting">Olá, {{ userName }}</span>

        <!-- Sino de notificações -->
        <div class="notifications-wrapper">
          <Button
            class="notifications-button"
            text
            aria-haspopup="true"
            @click="toggleNotifications"
          >
            <i class="pi pi-bell notifications-icon" />
          </Button>

          <span
            v-if="!notificationsLoading && unreadCount"
            class="notifications-badge"
          >
            {{ unreadCount }}
          </span>


          <div
            v-if="isNotificationsOpen"
            class="notifications-dropdown"
          >
            <div v-if="notificationsLoading" class="notifications-empty">
              Carregando notificações...
            </div>

            <div v-else-if="!notifications.length" class="notifications-empty">
              Nenhuma notificação.
            </div>

            <ul v-else class="notifications-list">
              <li
                v-for="n in notifications.filter(n => !n.read_at)"
                :key="n.id"
                class="notifications-item"
                @click="handleNotificationClick(n)"
              >
                <div class="notification-title">
                  Pedido #{{ n.data.travel_order_id }} {{ n.data.new_status }}
                </div>
                <div class="notification-subtitle">
                  {{ n.data.destination || 'Sem destino' }}
                </div>
                <div class="notification-meta">
                  {{ new Date(n.created_at).toLocaleString('pt-BR') }}
                </div>
              </li>
            </ul>
          </div>


        </div>


        <!-- Menu popup com avatar -->
        <Menu ref="userMenu" :model="userMenuItems" :popup="true" />

        <Button
          class="user-menu-button"
          @click="toggleUserMenu($event)"
          aria-haspopup="true"
        >
          <Avatar
            :image="props.avatarUrl || undefined"
            :label="!props.avatarUrl ? initials : undefined"
            class="avatar"
            shape="circle"
            size="large"
          />

          <i class="pi pi-angle-down caret-icon" />
        </Button>
      </div>

  </header>
</template>

<script setup>
import { computed, ref } from "vue";
import Avatar from "primevue/avatar";
import Button from "primevue/button";
import Menu from "primevue/menu";

const props = defineProps({
  userName: {
    type: String,
    default: "Usuário"
  },
  avatarUrl: {
    type: String,
    default: null
  },
  notifications: {
    type: Array,
    default: () => []
  },
  notificationsLoading: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(["logout", "profile", "open-notification"]);

const isNotificationsOpen = ref(false)

const unreadCount = computed(() => 
  props.notifications.filter(n => !n.read_at).length
)


function toggleNotifications() {
  isNotificationsOpen.value = !isNotificationsOpen.value
}

function handleNotificationClick(notification) {
  emit("open-notification", notification)
  isNotificationsOpen.value = false
}


const initials = computed(() => {
  return props.userName
    .split(" ")
    .map(p => p[0])
    .join("")
    .toUpperCase();
});

// ----- MENU DROPDOWN -----
const userMenu = ref();

const userMenuItems = [
  {
    label: "Meus Dados",
    icon: "pi pi-user",
    command: () => emit("profile")
  },
  {
    separator: true
  },
  {
    label: "Sair",
    icon: "pi pi-sign-out",
    command: () => emit("logout")
  }
];

const toggleUserMenu = (event) => {
  if (userMenu.value) {
    userMenu.value.toggle(event);
  }
};
</script>

<style scoped>
.header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: #1f1f1f;
  padding: 1rem 1.5rem;
  border-bottom: 1px solid #2c2c2c;
  box-shadow: 0 2px 6px rgba(0,0,0,0.25);
}

.left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-icon {
  font-size: 1.7rem;
  color: #4ade80;
}

.titles {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.system-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #f0f0f0;
}

.system-subtitle {
  font-size: 0.8rem;
  color: #9ca3af;
}

.right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.greeting {
  color: #dddddd;
  font-size: 0.95rem;
  opacity: 0.85;
}

.avatar {
  background: #4ade80;
  color: #1f1f1f;
  border: 2px solid #333;
}

.user-menu-button {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: transparent;
  border: none;
  padding: 0;
}

.user-menu-button :deep(.p-avatar) {
  cursor: pointer;
}

.caret-icon {
  font-size: 0.8rem;
  color: #e5e7eb;
}

/* Ajusta o avatar do header para não distorcer */
:deep(.avatar img) {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  object-position: center !important;
}


.notifications-wrapper {
  position: relative;
  display: inline-flex;
  align-items: center;
}

.notifications-button {
  position: relative;
  padding: 0.4rem;
  overflow: visible;
}


.notifications-icon {
  font-size: 1.2rem;
  color: #e5e7eb;
}

.notifications-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  background: #ef4444;
  color: #ffffff;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0 6px;
  height: 18px;
  min-width: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  line-height: 1;
  z-index: 10;
}


.notifications-dropdown {
  position: absolute;
  top: 120%;
  right: 0;
  margin-top: 0;
  background: #111827;
  border: 1px solid #374151;
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0,0,0,0.35);
  width: 280px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 999;
}


.notifications-list {
  list-style: none;
  margin: 0;
  padding: 0.25rem 0;
}

.notifications-item {
  padding: 0.6rem 0.9rem;
  cursor: pointer;
  border-bottom: 1px solid #1f2933;
}

.notifications-item:last-child {
  border-bottom: none;
}

.notifications-item:hover {
  background: #1f2937;
}

.notification-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: #e5e7eb;
}

.notification-subtitle {
  font-size: 0.8rem;
  color: #9ca3af;
}

.notification-meta {
  font-size: 0.75rem;
  color: #6b7280;
  margin-top: 0.25rem;
}

.notifications-empty {
  padding: 0.7rem 0.9rem;
  font-size: 0.85rem;
  color: #9ca3af;
}


</style>
