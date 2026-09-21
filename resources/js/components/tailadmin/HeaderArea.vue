<template>
  <header class="sticky top-0 z-40 flex w-full bg-white/85 backdrop-blur-md border-b border-gray-100 dark:bg-gray-900/85 dark:border-gray-800 transition-colors">
    <div class="flex grow items-center justify-between px-4 py-3 sm:px-6 md:px-8">
      <!-- Left (or Right in RTL): Mobile Toggle & Tenant Identifier -->
      <div class="flex items-center gap-3">
        <button
          @click="$emit('toggle-sidebar')"
          class="lg:hidden p-2 rounded-xl text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
        >
          <Menu class="h-5 w-5" />
        </button>

        <div class="flex items-center gap-2.5">
          <BrandLogo size="sm" :show-text="false" />
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-sm font-black text-gray-900 dark:text-white">
                {{ authStore.tenant?.name || $t('common.system') }}
              </h1>
              <span class="rounded-full bg-[#00C896]/15 px-2 py-0.5 text-[10px] font-bold text-[#00A87E] dark:text-[#00C896]">
                {{ $t('common.isolatedDb') }}
              </span>
            </div>
            <p class="text-[11px] font-mono font-medium text-gray-400">
              {{ authStore.tenant?.company_code || 'SARH-SYS' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Actions: Language Switcher, Dark Mode & User Profile -->
      <div class="flex items-center gap-2 sm:gap-3">
        <!-- Language Switcher Dropdown / Toggle -->
        <div class="relative" ref="langMenuRef">
          <button
            @click="langOpen = !langOpen"
            class="flex items-center gap-1.5 px-3 py-2 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-xs font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700/60 transition-colors"
          >
            <Globe class="h-4 w-4 text-[#00C896]" />
            <span class="hidden sm:inline">{{ currentLocaleLabel }}</span>
            <span class="sm:hidden">{{ locale.toUpperCase() }}</span>
          </button>

          <!-- Language Dropdown -->
          <div
            v-if="langOpen"
            class="absolute mt-2 w-36 rounded-2xl border border-gray-100 bg-white p-1.5 shadow-xl dark:border-gray-800 dark:bg-gray-900 z-50 animate-in fade-in zoom-in-95 duration-150"
            :class="locale === 'ar' ? 'left-0' : 'right-0'"
          >
            <button
              @click="switchLocale('ar')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
              :class="{ 'text-[#00A87E] dark:text-[#00C896] bg-[#00C896]/10': locale === 'ar' }"
            >
              <span>العربية</span>
              <Check v-if="locale === 'ar'" class="h-3.5 w-3.5 text-[#00C896]" />
            </button>
            <button
              @click="switchLocale('en')"
              class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
              :class="{ 'text-[#00A87E] dark:text-[#00C896] bg-[#00C896]/10': locale === 'en' }"
            >
              <span>English</span>
              <Check v-if="locale === 'en'" class="h-3.5 w-3.5 text-[#00C896]" />
            </button>
          </div>
        </div>

        <!-- Dark Mode Toggle -->
        <button
          @click="themeStore.toggleTheme"
          class="p-2.5 rounded-xl border border-gray-200 bg-white hover:bg-gray-50 text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700/60 transition-colors"
          :title="themeStore.isDark ? $t('header.lightMode') : $t('header.darkMode')"
        >
          <Sun v-if="themeStore.isDark" class="h-4 w-4 text-amber-400 animate-in spin-in-180 duration-200" />
          <Moon v-else class="h-4 w-4 text-slate-700 animate-in spin-in-180 duration-200" />
        </button>

        <!-- User Dropdown -->
        <DropdownUser />
      </div>
    </div>
  </header>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandLogo from '../common/BrandLogo.vue';
import { Menu, Globe, Sun, Moon, Check } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useThemeStore } from '../../stores/theme';
import { setLanguage } from '../../i18n';
import DropdownUser from './DropdownUser.vue';

defineEmits(['toggle-sidebar']);

const authStore = useAuthStore();
const themeStore = useThemeStore();
const { locale } = useI18n();

const langOpen = ref(false);
const langMenuRef = ref(null);

const currentLocaleLabel = computed(() => {
  return locale.value === 'ar' ? 'العربية' : 'English';
});

const switchLocale = (target) => {
  setLanguage(target);
  langOpen.value = false;
};

const handleClickOutside = (e) => {
  if (langMenuRef.value && !langMenuRef.value.contains(e.target)) {
    langOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  themeStore.initTheme();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});
</script>
