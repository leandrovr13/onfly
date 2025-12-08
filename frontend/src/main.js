import { createApp } from 'vue';
import './assets/styles/global.css';
import App from './App.vue';
import router from './router';

import PrimeVue from 'primevue/config';
import Aura from '@primeuix/themes/aura';
import ToastService from 'primevue/toastservice'
import Toast from 'primevue/toast';  
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Dropdown from 'primevue/dropdown';
import Dialog from 'primevue/dialog';
import Calendar from 'primevue/calendar';
import Tag from 'primevue/tag';

import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Card from 'primevue/card';
import AutoComplete from 'primevue/autocomplete';
import Message from 'primevue/message';

import 'primeicons/primeicons.css';
import 'primeflex/primeflex.css';

// =======================
// Tema inicial (light/dark)
// =======================
const COLOR_SCHEME_KEY = 'color-scheme';
const savedScheme = localStorage.getItem(COLOR_SCHEME_KEY) === 'dark' ? 'dark' : 'light';

if (savedScheme === 'dark') {
  document.documentElement.classList.add('app-dark');
} else {
  document.documentElement.classList.remove('app-dark');
}


// =======================
// Tradução do Calendário
// =======================
const primeLocalePtBR = {
  firstDayOfWeek: 1,
  dayNames: [
    'Domingo', 'Segunda-feira', 'Terça-feira',
    'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'
  ],
  dayNamesShort: ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'],
  dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S'],
  monthNames: [
    'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
    'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
  ],
  monthNamesShort: [
    'jan', 'fev', 'mar', 'abr', 'mai', 'jun',
    'jul', 'ago', 'set', 'out', 'nov', 'dez'
  ],
  today: 'Hoje',
  clear: 'Limpar',
};


const app = createApp(App);

app.use(router);

app.use(PrimeVue, {
  theme: {
    preset: Aura,
    options: {
      darkModeSelector: '.app-dark',
    },
  },
  locale: primeLocalePtBR,
});

app.use(ToastService);

// Registro global dos componentes
app.component('Button', Button);
app.component('InputText', InputText);
app.component('Password', Password);
app.component('Card', Card);
app.component('AutoComplete', AutoComplete);
app.component('DataTable', DataTable);
app.component('Column', Column);
app.component('Dropdown', Dropdown);
app.component('Dialog', Dialog);
app.component('Calendar', Calendar);
app.component('Tag', Tag);
app.component('Message', Message);
app.component('Toast', Toast);

app.mount('#app');
