import { ref } from 'vue';

const COLOR_SCHEME_KEY = 'color-scheme';

const savedScheme =
  localStorage.getItem(COLOR_SCHEME_KEY) === 'dark' ? 'dark' : 'light';

const isDarkTheme = ref(savedScheme === 'dark');

function applyTheme(isDark) {
  if (isDark) {
    document.documentElement.classList.add('app-dark');
    localStorage.setItem(COLOR_SCHEME_KEY, 'dark');
  } else {
    document.documentElement.classList.remove('app-dark');
    localStorage.setItem(COLOR_SCHEME_KEY, 'light');
  }
}

export function useTheme() {
  const toggleTheme = () => {
    isDarkTheme.value = !isDarkTheme.value;
    applyTheme(isDarkTheme.value);
  };

  const setTheme = (scheme) => {
    isDarkTheme.value = scheme === 'dark';
    applyTheme(isDarkTheme.value);
  };

  return {
    isDarkTheme,
    toggleTheme,
    setTheme,
  };
}
