<template>
  <div
    class="min-h-screen flex flex-col lg:flex-row bg-gray-50 dark:bg-gray-950 font-sans transition-colors duration-150"
    :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
  >
    <!-- Left / Brand Showcase Hero (Hidden on small screens, 50% on lg) -->
    <div
      class="hidden lg:flex lg:w-1/2 relative flex-col justify-between p-12 bg-gradient-to-br from-[#0C1315] via-[#121E21] to-[#070D0E] text-white overflow-hidden border-e border-white/10"
    >
      <!-- Subtle Background Glows -->
      <div class="absolute -top-24 -left-24 w-96 h-96 bg-[#00C896]/15 rounded-full blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-[#00C896]/10 rounded-full blur-3xl pointer-events-none"></div>

      <!-- Top Brand Header -->
      <div class="relative z-10">
        <BrandLogo size="lg" textColor="light" />
      </div>

      <!-- Middle Feature Highlights -->
      <div class="relative z-10 my-auto py-8">
        <h2 class="text-3xl font-black text-white leading-tight mb-4 max-w-lg">
          {{ $t('auth.brandHeading') }}
        </h2>
        <p class="text-sm text-gray-300 mb-8 max-w-md leading-relaxed">
          {{ $t('auth.brandDesc') }}
        </p>

        <!-- Feature List -->
        <div class="space-y-4 max-w-md">
          <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/20 text-[#00C896] shrink-0">
              <Database class="h-5 w-5" />
            </div>
            <span class="text-xs font-bold text-gray-200">{{ $t('auth.featMultiTenant') }}</span>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/20 text-[#00C896] shrink-0">
              <ShieldCheck class="h-5 w-5" />
            </div>
            <span class="text-xs font-bold text-gray-200">{{ $t('auth.featRoles') }}</span>
          </div>

          <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/5 border border-white/10 backdrop-blur-md">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/20 text-[#00C896] shrink-0">
              <Activity class="h-5 w-5" />
            </div>
            <span class="text-xs font-bold text-gray-200">{{ $t('auth.featAudit') }}</span>
          </div>
        </div>
      </div>

      <!-- Bottom Footer Info -->
      <div class="relative z-10 text-xs text-gray-400 flex items-center justify-between border-t border-white/10 pt-4">
        <span>&copy; {{ new Date().getFullYear() }} {{ $t('common.system') }}</span>
        <span class="text-[11px] text-[#00C896] font-bold font-mono">Enterprise Edition</span>
      </div>
    </div>

    <!-- Right / Form Area (100% mobile, 50% on lg) -->
    <div class="flex-1 flex flex-col justify-between p-6 sm:p-10 lg:p-12">
      <!-- Top Action Bar (Language Switcher & Dark Mode) -->
      <div class="flex items-center justify-between">
        <!-- Mobile Logo (Shown only on small screens) -->
        <div class="lg:hidden">
          <BrandLogo size="sm" />
        </div>

        <div class="flex items-center gap-2 ms-auto">
          <!-- Language Switcher Toggle -->
          <button
            @click="toggleLang"
            class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-xs font-bold text-gray-700 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors shadow-xs"
          >
            <Globe class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ $i18n.locale === 'ar' ? 'English' : 'العربية' }}</span>
          </button>

          <!-- Theme Toggle -->
          <button
            @click="toggleDarkMode"
            class="p-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors shadow-xs"
            :title="isDark ? $t('header.lightMode') : $t('header.darkMode')"
          >
            <Sun v-if="isDark" class="h-4 w-4 text-amber-500" />
            <Moon v-else class="h-4 w-4 text-slate-700" />
          </button>
        </div>
      </div>

      <!-- Auth Form Container (Render child route LoginView via router-view) -->
      <div class="my-auto mx-auto w-full max-w-md py-8">
        <router-view />
      </div>

      <!-- Bottom Mobile Footer -->
      <div class="text-center text-xs text-gray-400 pt-4 border-t border-gray-100 dark:border-gray-800/80">
        &copy; {{ new Date().getFullYear() }} {{ $t('common.system') }}. {{ $t('auth.allRightsReserved') }}
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandLogo from '../components/common/BrandLogo.vue';
import { Globe, Sun, Moon, Database, ShieldCheck, Activity, Sparkles } from 'lucide-vue-next';
import { setLanguage } from '../i18n';

const { locale } = useI18n();
const isDark = ref(false);

const toggleLang = () => {
  const target = locale.value === 'ar' ? 'en' : 'ar';
  setLanguage(target);
};

const toggleDarkMode = () => {
  isDark.value = !isDark.value;
  if (isDark.value) {
    document.documentElement.classList.add('dark');
    localStorage.setItem('sarh_theme', 'dark');
  } else {
    document.documentElement.classList.remove('dark');
    localStorage.setItem('sarh_theme', 'light');
  }
};

onMounted(() => {
  const savedTheme = localStorage.getItem('sarh_theme');
  if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    isDark.value = true;
    document.documentElement.classList.add('dark');
  }
});
</script>
