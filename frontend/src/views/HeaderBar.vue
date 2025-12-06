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
  }
});


const emit = defineEmits(["logout", "profile"]);

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

</style>
