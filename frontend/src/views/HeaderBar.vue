<template>
  <header class="header">
    <div class="left">

      <img
        :src="PlaneLogo"
        alt="Onfly Travel Orders"
        class="logo-icon"
      />

      <div class="titles">
        <span class="system-name">Onfly Travel Orders</span>
        <small class="system-subtitle">Painel de viagens corporativas</small>
      </div>
    </div>

    <div class="right">
        <span class="greeting">Olá, {{ userName }}</span>


          <!-- Botão de troca de tema -->
          <Button
            class="theme-toggle-button"
            :icon="isDarkTheme ? 'pi pi-moon' : 'pi pi-sun'"
            text
            rounded
            @click="toggleTheme"
            v-tooltip="isDarkTheme ? 'Tema escuro' : 'Tema claro'"
          />
          
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
                v-for="n in notifications/*.filter(n => !n.read_at)*/"
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
import PlaneLogo from '../assets/logo-plane.svg';
import { useTheme } from '../composables/useTheme';

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

const { isDarkTheme, toggleTheme } = useTheme();

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
  background-color: var(--header-bg-color);
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--header-border-color);
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
  color: var(--header-text-color); /* tudo que for texto herda daqui */
}

.left .app-title,
.left .app-subtitle,
.right .greeting {
  color: var(--header-text-color);
}

.header .p-button.p-button-text,
.header .p-button.p-button-text .p-button-icon,
.header .p-button.p-button-icon-only,
.header .p-button.p-button-icon-only .p-button-icon {
  color: var(--header-icon-color);
}

/* se quiser um leve realce no hover, bem discreto */
.header .p-button.p-button-text:hover {
  background-color: rgba(255, 255, 255, 0.12);
}

html:not(.app-dark) .header .p-button.p-button-text:hover {
  background-color: rgba(15, 23, 42, 0.06);
}


.left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.logo-icon {
  font-size: 1.7rem;
  color: var(--logo-icon-color);
}

/* Tema claro — ícone azul coerente com o header */
html:not(.app-dark) .logo-icon {
  color: #7aa2c7; /* como combinamos */
}

/* Tema escuro — Slate Azul */
html.app-dark .logo-icon {
  color: rgba(26, 108, 165, 0.85); /* azul profissional */
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
  background: var(--dropdown-bg);
  border: 1px solid var(--dropdown-border);
  border-radius: 0.5rem;
  box-shadow: 0 10px 20px rgba(0,0,0,0.35);
  width: 280px;
  max-height: 300px;
  overflow-y: auto;
  z-index: 999;
}

.notifications-item {
  padding: 0.6rem 0.9rem;
  cursor: pointer;
  border-bottom: 1px solid var(--dropdown-border);
}

.notifications-item:last-child {
  border-bottom: none;
}

.notifications-item:hover {
  background: var(--dropdown-item-hover-bg);
}

.notification-title {
  font-size: 0.85rem;
  font-weight: 600;
  color: var(--dropdown-title-color);
}

.notification-subtitle {
  font-size: 0.8rem;
  color: var(--dropdown-subtitle-color);
}

.notification-meta {
  font-size: 0.75rem;
  color: var(--dropdown-meta-color);
  margin-top: 0.25rem;
}

.notifications-empty {
  padding: 0.7rem 0.9rem;
  font-size: 0.85rem;
  color: var(--dropdown-subtitle-color);
}


.theme-toggle-button {
  margin: 0 0.75rem;
}

/* Botões do header no TEMA ESCURO usando o mesmo fundo do header */
html.app-dark .header .theme-toggle-button,
html.app-dark .header .notifications-button,
html.app-dark .header .user-menu-button {
  background-color: var(--header-bg-color) !important;
  border-color: transparent !important;
  box-shadow: none !important;
}

/* Mantém o mesmo fundo também no hover */
html.app-dark .header .theme-toggle-button:hover,
html.app-dark .header .notifications-button:hover,
html.app-dark .header .user-menu-button:hover {
  background-color: var(--header-bg-color) !important;
}

/* Ícone branco no tema claro */
html:not(.app-dark) .logo-icon {
  height: 26px;
  width: auto;
  filter: brightness(0) invert(1); /* torna branco */
}

/* Ícone branco também no tema escuro */
html.app-dark .logo-icon {
  height: 26px;
  width: auto;
  filter: brightness(0) invert(1);
}

</style>
