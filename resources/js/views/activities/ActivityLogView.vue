<template>
  <div class="space-y-6">
    <BreadcrumbDefault :pageTitle="$t('activities.title')" />

    <!-- Data Table -->
    <TailAdminDataTable
      :columns="columns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :allow-create="false"
      :search-placeholder="$t('activities.searchPlaceholder')"
      @search="onSearch"
      @page-change="loadLogs"
    >
      <template #filters>
        <select
          v-model="filterLogName"
          @change="loadLogs(1)"
          class="rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
        >
          <option value="">{{ $t('activities.allModules') }}</option>
          <option value="auth">{{ $t('activities.authModule') }}</option>
          <option value="users">{{ $t('activities.usersModule') }}</option>
          <option value="roles">{{ $t('activities.rolesModule') }}</option>
          <option value="settings">{{ $t('activities.settingsModule') }}</option>
          <option value="tenant">{{ $t('activities.tenantModule') }}</option>
        </select>
      </template>

      <!-- Custom Description Column -->
      <template #col-description="{ item }">
        <div :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
          <span class="block font-bold text-gray-900 dark:text-white">{{ item.description }}</span>
          <span v-if="item.log_name" class="inline-block mt-0.5 rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-mono text-gray-500 dark:bg-gray-700 dark:text-gray-300">
            [{{ item.log_name }}]
          </span>
        </div>
      </template>

      <!-- Custom Causer Column -->
      <template #col-causer="{ item }">
        <div v-if="item.causer" class="flex items-center gap-2">
          <span class="font-bold text-gray-800 dark:text-gray-200">{{ item.causer.name }}</span>
        </div>
        <span v-else class="text-gray-400 font-medium">{{ $t('activities.systemCauser') }}</span>
      </template>

      <!-- Custom Event Column -->
      <template #col-event="{ item }">
        <span
          class="rounded-full px-2 py-0.5 text-[10px] font-bold font-mono"
          :class="{
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400': item.event === 'created' || item.event === 'login',
            'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': item.event === 'updated',
            'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400': item.event === 'deleted' || item.event === 'logout',
            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': !item.event,
          }"
        >
          {{ item.event || 'action' }}
        </span>
      </template>

      <!-- Custom Date Column -->
      <template #col-created_at="{ item }">
        <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 font-mono" dir="ltr">
          {{ formatDate(item.created_at) }}
        </span>
      </template>

      <!-- Hide Action Buttons in Audit Log -->
      <template #actions>
        <span class="text-gray-400 text-xs">—</span>
      </template>
    </TailAdminDataTable>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Activity } from 'lucide-vue-next';
import api from '../../services/api';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';
import TailAdminDataTable from '../../components/tailadmin/TailAdminDataTable.vue';

const { t, locale } = useI18n();

const columns = computed(() => [
  { name: 'description', label: t('activities.descCol') },
  { name: 'causer', label: t('activities.causerCol') },
  { name: 'event', label: t('activities.eventCol') },
  { name: 'created_at', label: t('activities.timeCol') },
]);

const items = ref([]);
const meta = ref(null);
const loading = ref(false);
const filterLogName = ref('');
const searchQuery = ref('');

const loadLogs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/activity-logs', {
      params: {
        page,
        log_name: filterLogName.value,
        search: searchQuery.value,
      },
    });
    items.value = res.data.data;
    meta.value = res.data.meta;
  } catch (err) {
    // Ignore
  } finally {
    loading.value = false;
  }
};

const onSearch = (q) => {
  searchQuery.value = q;
  loadLogs(1);
};

const formatDate = (dateStr) => {
  if (!dateStr) return '—';
  const d = new Date(dateStr);
  return d.toLocaleString(locale.value === 'ar' ? 'ar-EG' : 'en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
};

onMounted(() => {
  loadLogs();
});
</script>
