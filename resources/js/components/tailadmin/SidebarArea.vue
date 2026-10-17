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

      <!-- Commercial & CRM Group (Collapsible) -->
      <div>
        <button
          type="button"
          @click="toggleCrm"
          class="w-full flex items-center justify-between mb-2 px-3 py-1 rounded-lg text-[11px] font-black uppercase text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 tracking-wider transition-colors cursor-pointer group"
          :aria-expanded="crmOpen"
        >
          <span class="flex items-center gap-1.5">
            <span>{{ $t('nav.commercial') }}</span>
            <span
              v-if="isCrmRouteActive"
              class="w-1.5 h-1.5 rounded-full bg-[#00C896] animate-pulse"
              title="Active Group"
            ></span>
          </span>
          <ChevronDown
            class="h-3.5 w-3.5 transform transition-transform duration-200"
            :class="{ '-rotate-90 rtl:rotate-90': !crmOpen, 'rotate-0': crmOpen }"
          />
        </button>

        <transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <ul v-show="crmOpen" class="space-y-1">
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
            <li>
              <router-link
                to="/opportunities"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <Target class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.opportunities') }}</span>
              </router-link>
            </li>
          </ul>
        </transition>
      </div>

      <!-- Technical Operations & Costing Group (Collapsible) -->
      <div>
        <button
          type="button"
          @click="toggleTech"
          class="w-full flex items-center justify-between mb-2 px-3 py-1 rounded-lg text-[11px] font-black uppercase text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 tracking-wider transition-colors cursor-pointer group"
          :aria-expanded="techOpen"
        >
          <span class="flex items-center gap-1.5">
            <span>{{ $t('nav.technicalOperations') }}</span>
            <span
              v-if="isTechRouteActive"
              class="w-1.5 h-1.5 rounded-full bg-[#00C896] animate-pulse"
              title="Active Group"
            ></span>
          </span>
          <ChevronDown
            class="h-3.5 w-3.5 transform transition-transform duration-200"
            :class="{ '-rotate-90 rtl:rotate-90': !techOpen, 'rotate-0': techOpen }"
          />
        </button>

        <transition
          enter-active-class="transition-all duration-200 ease-out"
          enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0"
          leave-active-class="transition-all duration-150 ease-in"
          leave-from-class="opacity-100 translate-y-0"
          leave-to-class="opacity-0 -translate-y-1"
        >
          <ul v-show="techOpen" class="space-y-1">
            <li>
              <router-link
                to="/site-visits"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <MapPin class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.siteVisits') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/measurements"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <Ruler class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.measurements') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/scopes"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <ClipboardList class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.scopes') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/boqs"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <TableProperties class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.boqs') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/estimations"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <Calculator class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.estimations') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/quotations"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <Receipt class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.quotations') }}</span>
              </router-link>
            </li>
            <li>
              <router-link
                to="/contracts"
                active-class="bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/15 dark:text-[#00C896] font-bold shadow-xs"
                class="group relative flex items-center gap-3 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800/60 transition-colors"
              >
                <FileSignature class="h-4 w-4 shrink-0 text-[#00C896]" />
                <span>{{ $t('nav.contracts') }}</span>
              </router-link>
            </li>
          </ul>
        </transition>
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
        {{ isRtl ? 'صَرْح ERP' : 'SARH ERP' }} • v1.0
      </p>
    </div>
  </aside>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useRoute } from 'vue-router';
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
  Target,
  MapPin,
  Ruler,
  ClipboardList,
  TableProperties,
  Calculator,
  Receipt,
  FileSignature,
  X,
  ChevronDown,
} from 'lucide-vue-next';

defineProps({
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close-sidebar']);

const route = useRoute();
const { locale } = useI18n();
const isRtl = computed(() => locale.value === 'ar');

const CRM_ROUTES = ['/customers', '/leads', '/opportunities'];
const TECH_ROUTES = ['/site-visits', '/measurements', '/scopes', '/boqs', '/estimations', '/quotations', '/contracts'];

const isRouteInList = (path, list) => list.some((prefix) => path === prefix || path.startsWith(prefix + '/'));

const isCrmRouteActive = computed(() => isRouteInList(route.path, CRM_ROUTES));
const isTechRouteActive = computed(() => isRouteInList(route.path, TECH_ROUTES));

const savedCrm = localStorage.getItem('sarh_sidebar_crm_open');
const savedTech = localStorage.getItem('sarh_sidebar_tech_open');

// Initialize states: prioritize active route; fallback to saved preference; defaults to true for CRM, false for Tech
const crmOpen = ref(
  isCrmRouteActive.value
    ? true
    : savedCrm !== null
      ? savedCrm === 'true'
      : true
);

const techOpen = ref(
  isTechRouteActive.value
    ? true
    : savedTech !== null
      ? savedTech === 'true'
      : false
);

const toggleCrm = () => {
  crmOpen.value = !crmOpen.value;
  localStorage.setItem('sarh_sidebar_crm_open', String(crmOpen.value));
};

const toggleTech = () => {
  techOpen.value = !techOpen.value;
  localStorage.setItem('sarh_sidebar_tech_open', String(techOpen.value));
};

// Auto-open active section upon navigation
watch(
  () => route.path,
  (newPath) => {
    if (isRouteInList(newPath, CRM_ROUTES)) {
      crmOpen.value = true;
      localStorage.setItem('sarh_sidebar_crm_open', 'true');
    } else if (isRouteInList(newPath, TECH_ROUTES)) {
      techOpen.value = true;
      localStorage.setItem('sarh_sidebar_tech_open', 'true');
    }
  },
  { immediate: true }
);
</script>
