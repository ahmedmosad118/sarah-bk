import { defineStore } from 'pinia';

export const useThemeStore = defineStore('theme', {
  state: () => {
    const savedTheme = typeof localStorage !== 'undefined' ? localStorage.getItem('sarh_theme') : null;
    const prefersDark = typeof window !== 'undefined' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    const isDark = savedTheme === 'dark' || (!savedTheme && prefersDark);

    return {
      isDark,
    };
  },

  actions: {
    initTheme() {
      if (typeof document === 'undefined') return;
      if (this.isDark) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    },

    setDark(value) {
      this.isDark = !!value;
      if (typeof document !== 'undefined') {
        if (this.isDark) {
          document.documentElement.classList.add('dark');
          localStorage.setItem('sarh_theme', 'dark');
        } else {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('sarh_theme', 'light');
        }
      }
    },

    toggleTheme() {
      this.setDark(!this.isDark);
    },
  },
});
