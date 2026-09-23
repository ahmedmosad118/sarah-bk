<template>
  <aside
    :class="[
      sidebarOpen
        ? 'translate-x-0'
        : isRtl
          ? 'translate-x-full lg:translate-x-0'
          : '-translate-x-full lg:translate-x-0',
      isRtl ? 'right-0 border-l' : 'left-0 border-r',
    ]"
    class="fixed top-0 z-50 flex h-screen w-72 flex-col overflow-y-hidden bg-white border-gray-100 duration-300 ease-linear dark:bg-gray-900 dark:border-gray-800 lg:static transition-all"
  >
    <!-- Sidebar Header -->
    <div class="flex items-center justify-between gap-2 px-6 py-5 border-b border-gray-100 dark:border-gray-800/80">
      <router-link to="/dashboard">
        <BrandLogo size="md" />
      </router-link>

      <button
        @click="$emit('close-sidebar')"
        class="block lg:hidden p-2 rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-800"
      >
        <X class="h-5 w-5" />
      </button>
    </div>

    <!-- Navigation Links -->
    <div class="no-scrollbar flex flex-col overflow-y-auto duration-300 ease-linear p-4 space-y-6">
      <!-- Main Group -->
      <div>
        <h3 class="mb-2.5 px-3 text-[11px] font-black uppercase text-gray-400 tracking-wider">
          {{ $t('nav.main') }}
        </h3>
        <ul class="space-y-1">
          <li>
            <router-link
              to="/dashboard"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <LayoutDashboard class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.dashboard') }}</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Commercial & CRM Group -->
      <div>
        <h3 class="mb-2.5 px-3 text-[11px] font-black uppercase text-gray-400 tracking-wider">
          {{ $t('nav.commercial') }}
        </h3>
        <ul class="space-y-1">
          <li>
            <router-link
              to="/customers"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <UserCheck class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.customers') }}</span>
            </router-link>
          </li>
          <li>
            <router-link
              to="/leads"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <Sparkles class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.leads') }}</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- Team & Foundation Group -->
      <div>
        <h3 class="mb-2.5 px-3 text-[11px] font-black uppercase text-gray-400 tracking-wider">
          {{ $t('nav.userManagement') }}
        </h3>
        <ul class="space-y-1">
          <li>
            <router-link
              to="/team/users"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <Users class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.users') }}</span>
            </router-link>
          </li>
          <li>
            <router-link
              to="/team/job-titles"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <Tag class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.jobTitles') }}</span>
            </router-link>
          </li>
          <li>
            <router-link
              to="/team/roles"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <ShieldCheck class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.roles') }}</span>
            </router-link>
          </li>
        </ul>
      </div>

      <!-- System & Audit Group -->
      <div>
        <h3 class="mb-2.5 px-3 text-[11px] font-black uppercase text-gray-400 tracking-wider">
          {{ $t('nav.systemMonitoring') }}
        </h3>
        <ul class="space-y-1">
          <li>
            <router-link
              to="/activities"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <Activity class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.activityLogs') }}</span>
            </router-link>
          </li>
          <li>
            <router-link
              to="/settings"
              active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
              class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
            >
              <Settings class="h-4 w-4 shrink-0 text-[#00C896]" />
              <span>{{ $t('nav.settings') }}</span>
            </router-link>
          </li>
        </ul>
      </div>
    </div>

    <!-- Sidebar Footer -->
    <div class="mt-auto p-4 border-t border-gray-100 dark:border-gray-800 text-center">
      <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">
        صَرْح ERP • v1.0
      </p>
    </div>
  </aside>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import BrandLogo from '../common/BrandLogo.vue';
import {
  LayoutDashboard,
  UserCheck,
  Sparkles,
  Users,
  Tag,
  ShieldCheck,
  Activity,
  Settings,
  X,
} from 'lucide-vue-next';

defineProps({
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close-sidebar']);

const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');
</script>
