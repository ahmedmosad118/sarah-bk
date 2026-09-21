import { createI18n } from 'vue-i18n';
import ar from '../locales/ar';
import en from '../locales/en';

const defaultLocale = localStorage.getItem('sarh_locale') || 'ar';

// Set initial HTML direction and lang
if (typeof document !== 'undefined') {
  document.documentElement.lang = defaultLocale;
  document.documentElement.dir = defaultLocale === 'ar' ? 'rtl' : 'ltr';
}

const i18n = createI18n({
  legacy: false, // Use Composition API mode
  locale: defaultLocale,
  fallbackLocale: 'ar',
  messages: {
    ar,
    en,
  },
});

export const setLanguage = (locale) => {
  i18n.global.locale.value = locale;
  localStorage.setItem('sarh_locale', locale);
  if (typeof document !== 'undefined') {
    document.documentElement.lang = locale;
    document.documentElement.dir = locale === 'ar' ? 'rtl' : 'ltr';
  }
};

export default i18n;
