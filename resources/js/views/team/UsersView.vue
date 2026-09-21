<template>
  <div class="space-y-6">
    <CrudIndex
      ref="crudRef"
      endpoint="/users"
      :title="$t('users.title')"
      :custom-columns="columns"
      :related-options="relatedOptions"
      @loaded="onDataLoaded"
      @open-modal="onOpenModal"
    >
      <!-- Top Stats Slot -->
      <template #top-stats>
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
          <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-800">
            <span class="text-xs font-bold text-gray-500">{{ $t('users.totalUsers') }}</span>
            <p class="text-xl font-black text-gray-900 dark:text-white mt-1 font-mono">{{ stats.total }}</p>
          </div>
          <div class="rounded-2xl border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/30 dark:bg-emerald-950/20">
            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400">{{ $t('users.activeUsers') }}</span>
            <p class="text-xl font-black text-emerald-700 dark:text-emerald-400 mt-1 font-mono">{{ stats.active }}</p>
          </div>
          <div class="rounded-2xl border border-rose-100 bg-rose-50/50 p-4 dark:border-rose-900/30 dark:bg-rose-950/20">
            <span class="text-xs font-bold text-rose-700 dark:text-rose-400">{{ $t('users.inactiveUsers') }}</span>
            <p class="text-xl font-black text-rose-700 dark:text-rose-400 mt-1 font-mono">{{ stats.inactive }}</p>
          </div>
          <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/20">
            <span class="text-xs font-bold text-blue-700 dark:text-blue-400">{{ $t('users.owners') }}</span>
            <p class="text-xl font-black text-blue-700 dark:text-blue-400 mt-1 font-mono">{{ stats.owners }}</p>
          </div>
        </div>
      </template>

      <!-- Custom Avatar / Name Column -->
      <template #col-name="{ item }">
        <div class="flex items-center gap-3">
          <img
            :src="item.avatar_url || 'https://ui-avatars.com/api/?name=' + encodeURIComponent(item.name)"
            :alt="item.name"
            class="h-9 w-9 rounded-xl object-cover ring-2 ring-gray-100 dark:ring-gray-700"
          />
          <div>
            <span class="block font-bold text-gray-900 dark:text-white">{{ item.name }}</span>
            <span class="block text-[11px] font-mono text-gray-400">{{ item.email }}</span>
          </div>
        </div>
      </template>

      <!-- Custom Job Title Column -->
      <template #col-job_title="{ item }">
        <span class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-2.5 py-1 text-xs font-bold text-gray-800 dark:bg-gray-700 dark:text-gray-200">
          <Tag class="h-3.5 w-3.5 text-amber-500" />
          <span>{{ item.job_title?.name || item.job_title_id || '—' }}</span>
        </span>
      </template>

      <!-- Custom Roles Column -->
      <template #col-roles="{ item }">
        <div class="flex flex-wrap gap-1">
          <span
            v-for="role in item.roles_list || []"
            :key="role"
            class="rounded-md px-2 py-0.5 text-[10px] font-bold"
            :class="{
              'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300': role === 'Owner',
              'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300': role !== 'Owner',
            }"
          >
            {{ role }}
          </span>
        </div>
      </template>

      <!-- Custom Status Column -->
      <template #col-status="{ item }">
        <button
          @click="toggleStatus(item)"
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold transition-all hover:opacity-80"
          :class="item.status === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400'"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="item.status === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
          <span>{{ item.status === 'active' ? $t('common.active') : $t('common.inactive') }}</span>
        </button>
      </template>

      <!-- Extra Modal Fields: Multiple Roles Selector -->
      <template #modal-extra-fields="{ formData }">
        <div class="col-span-12">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
            {{ $t('users.multipleRolesLabel') }} <span class="text-rose-500 font-bold">*</span>
          </label>
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-3 rounded-2xl border border-gray-200 bg-gray-50/70 dark:border-gray-700 dark:bg-gray-900/40">
            <label
              v-for="r in availableRoles"
              :key="r.name"
              class="flex items-center gap-2 p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer text-xs font-semibold text-gray-800 dark:text-gray-200 hover:border-blue-500 transition-colors"
            >
              <input
                type="checkbox"
                :value="r.name"
                v-model="selectedRoles"
                @change="updateRoles(formData)"
                class="rounded-md border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="truncate">{{ r.display_name || r.name }}</span>
            </label>
          </div>
        </div>
      </template>
    </CrudIndex>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { Tag } from 'lucide-vue-next';
import CrudIndex from '../../components/crud/CrudIndex.vue';
import api from '../../services/api';
import { useNotificationStore } from '../../stores/notification';

const { t } = useI18n();
const crudRef = ref(null);
const notificationStore = useNotificationStore();

const columns = computed(() => [
  { name: 'name', label: t('users.userCol') },
  { name: 'phone', label: t('users.phoneCol') },
  { name: 'job_title', label: t('users.jobTitleCol') },
  { name: 'roles', label: t('users.rolesCol') },
  { name: 'status', label: t('users.statusCol') },
  { name: 'joining_date', label: t('users.joiningDateCol') },
]);

const stats = ref({ total: 0, active: 0, inactive: 0, owners: 0 });
const availableRoles = ref([]);
const availableJobTitles = ref([]);
const selectedRoles = ref([]);
const relatedOptions = ref({});

const onDataLoaded = (res) => {
  if (res.stats) stats.value = res.stats;
  if (res.roles) availableRoles.value = res.roles;
  if (res.job_titles) {
    availableJobTitles.value = res.job_titles;
    relatedOptions.value['job_title_id'] = res.job_titles.map((j) => ({
      value: j.id,
      label: `${j.name} (${j.code || '—'})`,
    }));
  }
};

const onOpenModal = ({ mode, data, item }) => {
  if (mode === 'edit' && item?.roles_list) {
    selectedRoles.value = [...item.roles_list];
    data.roles = selectedRoles.value;
  } else {
    selectedRoles.value = ['Project Team'];
    data.roles = selectedRoles.value;
  }
};

const updateRoles = (formData) => {
  formData.roles = selectedRoles.value;
};

const toggleStatus = async (user) => {
  try {
    const res = await api.post(`/users/${user.id}/toggle-status`);
    notificationStore.success(res.data.message || t('users.toggleStatusSuccess'));
    crudRef.value?.loadData();
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error updating user status.');
  }
};
</script>
