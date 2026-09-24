<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      class="flex items-center gap-1.5 p-1 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800/80 transition-colors cursor-pointer group"
      :title="authStore.user?.name || $t('header.user')"
    >
      <div class="relative">
        <img
          :src="currentAvatarUrl"
          @error="handleAvatarError"
          alt="User"
          class="h-9 w-9 rounded-xl object-cover ring-2 ring-[#00C896]/40 group-hover:ring-[#00C896] bg-emerald-50 dark:bg-slate-800 transition-all shadow-xs"
        />
        <span class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-[#00C896] ring-2 ring-white dark:ring-gray-900"></span>
      </div>
      <ChevronDown class="h-3.5 w-3.5 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-200 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
    </button>

    <!-- Dropdown Menu -->
    <div
      v-if="isOpen"
      class="absolute mt-3 w-64 rounded-2xl border border-gray-100 bg-white p-2 shadow-xl dark:border-gray-800 dark:bg-gray-900 z-50 animate-in fade-in zoom-in-95 duration-150"
      :class="$i18n.locale === 'ar' ? 'left-0' : 'right-0'"
    >
      <div class="px-3 py-2.5 border-b border-gray-100 dark:border-gray-800 mb-1" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
        <p class="text-xs font-bold text-gray-900 dark:text-white">{{ authStore.user?.name }}</p>
        <p class="text-[11px] text-gray-400 truncate font-mono">{{ authStore.user?.email }}</p>
        <div class="mt-1.5 flex flex-wrap gap-1">
          <span
            v-for="role in authStore.roles"
            :key="role"
            class="rounded-md bg-[#00C896]/10 px-1.5 py-0.5 text-[10px] font-bold text-[#00A87E] dark:text-[#00C896]"
          >
            {{ role }}
          </span>
        </div>
      </div>

      <!-- Profile & Settings Actions -->
      <router-link
        to="/profile"
        @click="isOpen = false"
        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
        :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
      >
        <User class="h-4 w-4 text-[#00C896]" />
        <span>{{ $t('header.profile') }}</span>
      </router-link>

      <router-link
        to="/settings"
        @click="isOpen = false"
        class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
      >
        <Settings class="h-4 w-4 text-slate-400" />
        <span>{{ $t('header.settings') }}</span>
      </router-link>

      <div class="my-1 border-t border-gray-100 dark:border-gray-800"></div>

      <button
        @click="handleLogout"
        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30 transition-colors"
        :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
      >
        <LogOut class="h-4 w-4 text-rose-500" />
        <span>{{ $t('header.logout') }}</span>
      </button>
    </div>

    <!-- User Profile Modal -->
    <UserProfileModal
      :show="showProfileModal"
      :initial-tab="profileModalTab"
      @close="showProfileModal = false"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import { ChevronDown, User, Palette, Settings, LogOut } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import UserProfileModal from './UserProfileModal.vue';

const authStore = useAuthStore();
const router = useRouter();
const isOpen = ref(false);
const dropdownRef = ref(null);

const showProfileModal = ref(false);
const profileModalTab = ref('info');

const generateSvgFallback = (name) => {
  const initial = (name || 'U').trim().charAt(0);
  return `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128"><rect width="128" height="128" rx="36" fill="%230C1315"/><rect x="4" y="4" width="120" height="120" rx="32" fill="none" stroke="%2300C896" stroke-width="3" stroke-opacity="0.5"/><text x="50%" y="54%" font-family="Cairo, Outfit, sans-serif" font-weight="900" font-size="54" fill="%2300C896" dominant-baseline="middle" text-anchor="middle">${encodeURIComponent(initial)}</text></svg>`;
};

const currentAvatarUrl = computed(() => {
  if (authStore.user?.avatar_url && !authStore.user.avatar_url.includes('ui-avatars.com')) {
    return authStore.user.avatar_url;
  }
  return generateSvgFallback(authStore.user?.name);
});

const handleAvatarError = (e) => {
  e.target.src = generateSvgFallback(authStore.user?.name);
};

const openProfileModal = (tab = 'info') => {
  profileModalTab.value = tab;
  showProfileModal.value = true;
  isOpen.value = false;
};

const handleLogout = async () => {
  isOpen.value = false;
  await authStore.logout();
  router.push('/login');
};

const handleClickOutside = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => document.addEventListener('click', handleClickOutside));
onUnmounted(() => document.removeEventListener('click', handleClickOutside));
</script>
