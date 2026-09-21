<template>
  <div class="space-y-6">
    <!-- Welcome Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-emerald-500/10 dark:from-slate-900 dark:via-[#112325] dark:to-slate-900 border border-emerald-200/80 dark:border-emerald-500/20 p-6 sm:p-8 text-gray-900 dark:text-white shadow-xs">
      <!-- Ambient Glow Accents -->
      <div class="absolute -top-24 -left-24 h-56 w-56 rounded-full bg-[#00C896]/15 dark:bg-[#00C896]/20 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 h-56 w-56 rounded-full bg-[#00C896]/10 dark:bg-[#00C896]/15 blur-3xl pointer-events-none"></div>

      <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full bg-[#00C896]/15 text-emerald-800 dark:text-[#00C896] border border-[#00C896]/30 px-3 py-1 text-xs font-bold backdrop-blur-md mb-3">
            <Building2 class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ authStore.tenant?.name || $t('common.system') }}</span>
            <span>•</span>
            <span class="font-mono">{{ authStore.tenant?.company_code }}</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
            {{ $t('dashboard.welcome', { name: authStore.user?.name || '' }) }}
          </h1>
          <p class="text-xs text-gray-500 dark:text-gray-300 mt-1 max-w-xl">
            {{ $t('dashboard.tenantSubtitle') }}
          </p>
        </div>

        <div class="flex items-center gap-3">
          <router-link
            to="/team/users"
            class="flex items-center gap-1.5 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-4 py-2.5 text-xs font-black text-gray-950 transition-colors shadow-md shadow-[#00C896]/20"
          >
            <Users class="h-4 w-4" />
            <span>{{ $t('dashboard.manageUsers') }}</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- Foundation Stats Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <CardDataStats
        :title="$t('dashboard.usersStat')"
        :total="stats.users"
        :badge="$t('common.active')"
        :subtitle="$t('dashboard.usersSubtitle')"
      >
        <template #icon>
          <Users class="h-6 w-6 text-[#00C896]" />
        </template>
      </CardDataStats>

      <CardDataStats
        :title="$t('dashboard.jobTitlesStat')"
        :total="stats.jobTitles"
        :subtitle="$t('dashboard.jobTitlesSubtitle')"
      >
        <template #icon>
          <Tag class="h-6 w-6 text-amber-600 dark:text-amber-400" />
        </template>
      </CardDataStats>

      <CardDataStats
        :title="$t('dashboard.rolesStat')"
        :total="stats.roles"
        :subtitle="$t('dashboard.rolesSubtitle')"
      >
        <template #icon>
          <ShieldCheck class="h-6 w-6 text-purple-600 dark:text-purple-400" />
        </template>
      </CardDataStats>

      <CardDataStats
        :title="$t('dashboard.logsStat')"
        :total="stats.logs"
        :subtitle="$t('dashboard.logsSubtitle')"
      >
        <template #icon>
          <Activity class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
        </template>
      </CardDataStats>
    </div>

    <!-- Quick Actions & Foundation Architecture Highlights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Architecture Status Card -->
      <div class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Layers class="h-4 w-4 text-blue-600" />
          <span>{{ $t('dashboard.architectureTitle') }}</span>
        </h3>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700/60 dark:bg-gray-900/30">
            <div class="flex items-center gap-2 mb-1.5">
              <Database class="h-4 w-4 text-blue-600" />
              <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $t('dashboard.isolatedDbTitle') }}</h4>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
              {{ $t('dashboard.isolatedDbDesc') }}
            </p>
          </div>

          <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700/60 dark:bg-gray-900/30">
            <div class="flex items-center gap-2 mb-1.5">
              <ShieldCheck class="h-4 w-4 text-purple-600" />
              <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $t('dashboard.spatieTitle') }}</h4>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
              {{ $t('dashboard.spatieDesc') }}
            </p>
          </div>

          <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700/60 dark:bg-gray-900/30">
            <div class="flex items-center gap-2 mb-1.5">
              <Cpu class="h-4 w-4 text-amber-600" />
              <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $t('dashboard.crudTitle') }}</h4>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
              {{ $t('dashboard.crudDesc') }}
            </p>
          </div>

          <div class="rounded-xl border border-gray-100 bg-gray-50/70 p-4 dark:border-gray-700/60 dark:bg-gray-900/30">
            <div class="flex items-center gap-2 mb-1.5">
              <FileText class="h-4 w-4 text-emerald-600" />
              <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $t('dashboard.mediaAuditTitle') }}</h4>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-gray-400">
              {{ $t('dashboard.mediaAuditDesc') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Quick Links Card -->
      <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Zap class="h-4 w-4 text-amber-500" />
          <span>{{ $t('dashboard.quickLinks') }}</span>
        </h3>

        <div class="space-y-2">
          <router-link
            to="/team/users"
            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#00C896]/30 hover:bg-[#00C896]/5 dark:border-gray-700 dark:hover:bg-gray-700/40 transition-colors text-xs font-semibold text-gray-800 dark:text-gray-200"
          >
            <span class="flex items-center gap-2">
              <Users class="h-4 w-4 text-[#00C896]" />
              {{ $t('nav.users') }}
            </span>
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-gray-400" />
            <ChevronRight v-else class="h-4 w-4 text-gray-400" />
          </router-link>

          <router-link
            to="/team/job-titles"
            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#00C896]/30 hover:bg-[#00C896]/5 dark:border-gray-700 dark:hover:bg-gray-700/40 transition-colors text-xs font-semibold text-gray-800 dark:text-gray-200"
          >
            <span class="flex items-center gap-2">
              <Tag class="h-4 w-4 text-amber-500" />
              {{ $t('nav.jobTitles') }}
            </span>
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-gray-400" />
            <ChevronRight v-else class="h-4 w-4 text-gray-400" />
          </router-link>

          <router-link
            to="/team/roles"
            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#00C896]/30 hover:bg-[#00C896]/5 dark:border-gray-700 dark:hover:bg-gray-700/40 transition-colors text-xs font-semibold text-gray-800 dark:text-gray-200"
          >
            <span class="flex items-center gap-2">
              <ShieldCheck class="h-4 w-4 text-purple-500" />
              {{ $t('nav.roles') }}
            </span>
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-gray-400" />
            <ChevronRight v-else class="h-4 w-4 text-gray-400" />
          </router-link>

          <router-link
            to="/activities"
            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#00C896]/30 hover:bg-[#00C896]/5 dark:border-gray-700 dark:hover:bg-gray-700/40 transition-colors text-xs font-semibold text-gray-800 dark:text-gray-200"
          >
            <span class="flex items-center gap-2">
              <Activity class="h-4 w-4 text-[#00C896]" />
              {{ $t('nav.activityLogs') }}
            </span>
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-gray-400" />
            <ChevronRight v-else class="h-4 w-4 text-gray-400" />
          </router-link>

          <router-link
            to="/settings"
            class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:border-[#00C896]/30 hover:bg-[#00C896]/5 dark:border-gray-700 dark:hover:bg-gray-700/40 transition-colors text-xs font-semibold text-gray-800 dark:text-gray-200"
          >
            <span class="flex items-center gap-2">
              <Settings class="h-4 w-4 text-slate-500" />
              {{ $t('nav.settings') }}
            </span>
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-gray-400" />
            <ChevronRight v-else class="h-4 w-4 text-gray-400" />
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import CardDataStats from '../../components/tailadmin/CardDataStats.vue';
import api from '../../services/api';
import {
  Building2,
  Users,
  Tag,
  ShieldCheck,
  Activity,
  Layers,
  Database,
  Cpu,
  FileText,
  Zap,
  Settings,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';

const authStore = useAuthStore();
const stats = ref({
  users: 1,
  jobTitles: 34,
  roles: 20,
  logs: 1,
});

onMounted(async () => {
  try {
    const [uRes, jRes, rRes, lRes] = await Promise.all([
      api.get('/users?per_page=1'),
      api.get('/job-titles?per_page=1'),
      api.get('/roles'),
      api.get('/activity-logs?per_page=1'),
    ]);

    if (uRes.data?.meta?.total) stats.value.users = uRes.data.meta.total;
    if (jRes.data?.meta?.total) stats.value.jobTitles = jRes.data.meta.total;
    if (rRes.data?.total_roles) stats.value.roles = rRes.data.total_roles;
    if (lRes.data?.meta?.total) stats.value.logs = lRes.data.meta.total;
  } catch (err) {
    // Ignore on initial load
  }
});
</script>
