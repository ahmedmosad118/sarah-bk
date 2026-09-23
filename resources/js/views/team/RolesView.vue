<template>
  <div class="space-y-6">
    <BreadcrumbDefault :pageTitle="$t('roles.title')" />

    <!-- Top Stats Overview Cards -->
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
      <!-- Total Roles -->
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-[#00C896]/30">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('roles.totalRoles') }}</p>
            <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.total }}</h3>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00C896] dark:bg-[#00C896]/20">
            <ShieldCheck class="h-5 w-5" />
          </div>
        </div>
      </div>

      <!-- System Roles -->
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/30">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('roles.systemRoles') }}</p>
            <h3 class="mt-1 text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ stats.system }}</h3>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
            <Lock class="h-5 w-5" />
          </div>
        </div>
      </div>

      <!-- Custom Roles -->
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/30">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('roles.customRoles') }}</p>
            <h3 class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ stats.custom }}</h3>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
            <Sparkles class="h-5 w-5" />
          </div>
        </div>
      </div>

      <!-- Total Permissions -->
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-amber-500/30">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('roles.totalPermissions') }}</p>
            <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ stats.permissions }}</h3>
          </div>
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
            <Key class="h-5 w-5" />
          </div>
        </div>
      </div>
    </div>

    <!-- Roles Data Table -->
    <TailAdminDataTable
      :columns="columns"
      :items="filteredRoles"
      :loading="loading"
      :allow-create="true"
      :create-label="$t('roles.createNewRole')"
      :search-placeholder="$t('roles.searchPlaceholder')"
      @search="onSearch"
      @create="openCreateRoleModal"
    >
      <!-- Filter Segmented Tabs -->
      <template #filters>
        <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
          <button
            type="button"
            @click="filterType = 'all'"
            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
            :class="filterType === 'all' ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
          >
            <span>{{ $t('roles.filterAll') }}</span>
          </button>
          <button
            type="button"
            @click="filterType = 'system'"
            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
            :class="filterType === 'system' ? 'bg-white text-purple-600 shadow-xs dark:bg-gray-800 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
          >
            <span>{{ $t('roles.filterSystem') }}</span>
          </button>
          <button
            type="button"
            @click="filterType = 'custom'"
            class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
            :class="filterType === 'custom' ? 'bg-white text-blue-600 shadow-xs dark:bg-gray-800 dark:text-blue-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
          >
            <span>{{ $t('roles.filterCustom') }}</span>
          </button>
        </div>
      </template>

      <!-- Custom Role & Code Column -->
      <template #col-role="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold"
            :class="item.name === 'Owner' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]'"
          >
            <ShieldCheck class="h-4 w-4" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900 dark:text-white hover:text-[#00C896] cursor-pointer" @click="openPermissionsModal(item)">
                {{ item.display_name || item.name }}
              </span>
              <span
                class="rounded-md px-1.5 py-0.5 text-[10px] font-mono font-bold"
                :class="item.name === 'Owner' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
              >
                {{ item.name }}
              </span>
            </div>
            <p v-if="item.description" class="text-[11px] text-gray-400 line-clamp-1 mt-0.5">
              {{ item.description }}
            </p>
          </div>
        </div>
      </template>

      <!-- Custom Assigned Staff Column -->
      <template #col-users_count="{ item }">
        <div class="flex items-center gap-1.5">
          <Users class="h-3.5 w-3.5 text-blue-500" />
          <span class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{ item.users_count || 0 }}</span>
          <span class="text-[11px] text-gray-400">{{ $t('roles.employees') }}</span>
        </div>
      </template>

      <!-- Custom Permissions Count Column -->
      <template #col-permissions_count="{ item }">
        <div class="flex items-center gap-1.5">
          <Key class="h-3.5 w-3.5 text-amber-500" />
          <span class="font-mono text-xs font-bold text-gray-900 dark:text-white">{{ item.permissions?.length || 0 }}</span>
          <span class="text-[11px] text-gray-400">{{ $t('roles.permissions') }}</span>
        </div>
      </template>

      <!-- Custom Type Column -->
      <template #col-type="{ item }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
          :class="isSystemRole(item) ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
        >
          <Lock v-if="isSystemRole(item)" class="h-3 w-3" />
          <Sparkles v-else class="h-3 w-3" />
          <span>{{ isSystemRole(item) ? $t('roles.systemRole') : $t('roles.customRole') }}</span>
        </span>
      </template>

      <!-- Custom Actions Column -->
      <template #actions="{ item }">
        <div class="flex items-center justify-center gap-1">
          <!-- Permissions Matrix Button -->
          <button
            type="button"
            @click="openPermissionsModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-[#00C896] hover:bg-[#00C896]/10 dark:hover:bg-[#00C896]/20 transition-colors cursor-pointer"
            :title="$t('roles.editPermissions')"
          >
            <SlidersHorizontal class="h-3.5 w-3.5" />
          </button>

          <!-- Edit Role Info Button -->
          <button
            type="button"
            @click="openEditRoleModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors cursor-pointer"
            :title="$t('roles.editRoleInfo')"
          >
            <Pencil class="h-3.5 w-3.5" />
          </button>

          <!-- Delete Role Button (Disabled/Hidden for system core roles) -->
          <button
            v-if="!isSystemRole(item)"
            type="button"
            @click="deleteRole(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition-colors cursor-pointer"
            :title="$t('roles.deleteCustomRole')"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </div>
      </template>
    </TailAdminDataTable>

    <!-- 1. Role Info Modal (Create / Edit Meta) -->
    <CrudModal
      :show="showRoleModal"
      :title="isEditing ? $t('roles.editRoleInfo') : $t('roles.createNewRole')"
      :loading="roleModalLoading"
      @close="showRoleModal = false"
      @save="saveRoleInfo"
    >
      <div class="space-y-4" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('roles.roleCodeName') }}
            </label>
            <input
              type="text"
              v-model="roleForm.name"
              :disabled="isEditing && activeRole?.name === 'Owner'"
              placeholder="e.g. Senior_Site_Engineer"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-mono font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white disabled:opacity-50"
              dir="ltr"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('roles.roleDisplayName') }}
            </label>
            <input
              type="text"
              v-model="roleForm.display_name"
              placeholder="مثال: كبير مهندسي الموقع"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('roles.roleDesc') }}
          </label>
          <textarea
            v-model="roleForm.description"
            rows="3"
            placeholder="وصف مختصر لمسؤوليات ونطاق عمل هذا الدور في المنظومة..."
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
          ></textarea>
        </div>
      </div>
    </CrudModal>

    <!-- 2. Permissions Matrix Modal -->
    <CrudModal
      :show="showPermissionsModal"
      :title="$t('roles.matrixTitle', { name: activeRole?.display_name || activeRole?.name || '' })"
      :loading="permissionsModalLoading"
      @close="showPermissionsModal = false"
      @save="saveRolePermissions"
    >
      <div class="space-y-5" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
        <!-- Search and Global Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-800">
          <div>
            <h4 class="text-xs font-black uppercase tracking-wider text-gray-400">
              {{ $t('roles.selectPermissionsForRole') }}
            </h4>
            <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
              {{ activeRole?.name === 'Owner' ? 'مالك المنظومة يمتلك تلقائياً كافة الصلاحيات بدون قيود' : 'حدد الصلاحيات الممنوحة لهذا الدور بدقة' }}
            </p>
          </div>

          <div v-if="activeRole?.name !== 'Owner'" class="flex items-center gap-2 text-xs">
            <button
              type="button"
              @click="selectAllPermissions"
              class="px-2.5 py-1 rounded-lg bg-[#00C896]/10 text-[#00A87E] hover:bg-[#00C896]/20 dark:bg-[#00C896]/20 dark:text-[#00C896] font-bold transition-colors cursor-pointer"
            >
              {{ $t('roles.selectAll') }}
            </button>
            <span class="text-gray-300 dark:text-gray-700">|</span>
            <button
              type="button"
              @click="clearAllPermissions"
              class="px-2.5 py-1 rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 font-bold transition-colors cursor-pointer"
            >
              {{ $t('roles.clearAll') }}
            </button>
          </div>
        </div>

        <!-- Permissions List Grouped by Module -->
        <div class="space-y-4 max-h-[55vh] overflow-y-auto custom-scrollbar pr-1">
          <div
            v-for="(perms, modName) in modules"
            :key="modName"
            class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/30"
          >
            <div class="flex items-center justify-between mb-3">
              <span class="text-xs font-black uppercase text-gray-900 dark:text-white flex items-center gap-2">
                <span class="h-2 w-2 rounded-full bg-[#00C896]"></span>
                <span>{{ modName }}</span>
              </span>

              <button
                v-if="activeRole?.name !== 'Owner'"
                type="button"
                @click="toggleModulePermissions(perms)"
                class="text-[11px] font-bold text-blue-600 hover:underline dark:text-blue-400 cursor-pointer"
              >
                {{ $t('roles.toggleModule') }}
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
              <label
                v-for="perm in perms"
                :key="perm.name"
                class="flex items-start gap-2.5 p-2.5 rounded-xl border bg-white dark:bg-gray-800/80 transition-all cursor-pointer"
                :class="selectedPermissions.includes(perm.name) ? 'border-[#00C896] bg-[#00C896]/5 dark:border-[#00C896]/60 dark:bg-[#00C896]/10' : 'border-gray-100 dark:border-gray-750 hover:border-gray-200'"
              >
                <input
                  type="checkbox"
                  :value="perm.name"
                  v-model="selectedPermissions"
                  :disabled="activeRole?.name === 'Owner'"
                  class="mt-0.5 rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896] cursor-pointer"
                />
                <div>
                  <span class="block text-xs font-bold text-gray-800 dark:text-gray-200">
                    {{ perm.display_name || perm.name }}
                  </span>
                  <span class="block text-[10px] font-mono text-gray-400" dir="ltr">
                    {{ perm.name }}
                  </span>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>
    </CrudModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';
import TailAdminDataTable from '../../components/tailadmin/TailAdminDataTable.vue';
import CrudModal from '../../components/crud/CrudModal.vue';
import {
  ShieldCheck,
  Lock,
  Sparkles,
  Key,
  Users,
  Pencil,
  Trash2,
  SlidersHorizontal,
} from 'lucide-vue-next';

const { t, locale } = useI18n();
const notificationStore = useNotificationStore();

const roles = ref([]);
const modules = ref({});
const loading = ref(false);
const searchQuery = ref('');
const filterType = ref('all'); // 'all', 'system', 'custom'

const stats = reactive({
  total: 0,
  system: 0,
  custom: 0,
  permissions: 0,
});

const columns = computed(() => [
  { name: 'role', label: t('roles.roleCol') },
  { name: 'users_count', label: t('roles.usersCountCol') },
  { name: 'permissions_count', label: t('roles.permissionsCountCol') },
  { name: 'type', label: t('roles.typeCol') },
]);

// 1. Role Info Modal State
const showRoleModal = ref(false);
const roleModalLoading = ref(false);
const isEditing = ref(false);
const activeRole = ref(null);
const roleForm = reactive({
  name: '',
  display_name: '',
  description: '',
});

// 2. Permissions Matrix Modal State
const showPermissionsModal = ref(false);
const permissionsModalLoading = ref(false);
const selectedPermissions = ref([]);

const isSystemRole = (role) => {
  if (!role) return false;
  return role.is_default || ['Owner', 'Super Admin', 'Administrator', 'Viewer'].includes(role.name);
};

const loadRoles = async () => {
  loading.value = true;
  try {
    const res = await api.get('/roles');
    roles.value = res.data.data || [];
    modules.value = res.data.modules || {};

    // Calculate Stats
    stats.total = roles.value.length;
    stats.system = roles.value.filter((r) => isSystemRole(r)).length;
    stats.custom = roles.value.filter((r) => !isSystemRole(r)).length;
    stats.permissions = res.data.total_permissions || 0;
  } catch (err) {
    notificationStore.error(t('common.loading'));
  } finally {
    loading.value = false;
  }
};

const filteredRoles = computed(() => {
  let list = roles.value;

  // Filter by Type
  if (filterType.value === 'system') {
    list = list.filter((r) => isSystemRole(r));
  } else if (filterType.value === 'custom') {
    list = list.filter((r) => !isSystemRole(r));
  }

  // Filter by Search Query
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim();
    list = list.filter((r) => {
      const name = (r.name || '').toLowerCase();
      const displayName = (r.display_name || '').toLowerCase();
      const desc = (r.description || '').toLowerCase();
      return name.includes(q) || displayName.includes(q) || desc.includes(q);
    });
  }

  return list;
});

const onSearch = (query) => {
  searchQuery.value = query;
};

// Create / Edit Role Handlers
const openCreateRoleModal = () => {
  isEditing.value = false;
  activeRole.value = null;
  roleForm.name = '';
  roleForm.display_name = '';
  roleForm.description = '';
  showRoleModal.value = true;
};

const openEditRoleModal = (role) => {
  isEditing.value = true;
  activeRole.value = role;
  roleForm.name = role.name;
  roleForm.display_name = role.display_name || role.name;
  roleForm.description = role.description || '';
  showRoleModal.value = true;
};

const saveRoleInfo = async () => {
  roleModalLoading.value = true;
  try {
    if (isEditing.value && activeRole.value) {
      await api.put(`/roles/${activeRole.value.id}`, {
        name: roleForm.name,
        display_name: roleForm.display_name,
        description: roleForm.description,
      });
      notificationStore.success(t('roles.savedSuccess'));
    } else {
      await api.post('/roles', {
        name: roleForm.name,
        display_name: roleForm.display_name,
        description: roleForm.description,
      });
      notificationStore.success(t('roles.createdSuccess'));
    }
    showRoleModal.value = false;
    loadRoles();
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error saving role.');
  } finally {
    roleModalLoading.value = false;
  }
};

// Permissions Matrix Handlers
const openPermissionsModal = (role) => {
  activeRole.value = role;
  selectedPermissions.value = (role.permissions || []).map((p) => p.name);
  showPermissionsModal.value = true;
};

const selectAllPermissions = () => {
  const all = [];
  Object.values(modules.value).forEach((perms) => {
    perms.forEach((p) => all.push(p.name));
  });
  selectedPermissions.value = all;
};

const clearAllPermissions = () => {
  selectedPermissions.value = [];
};

const toggleModulePermissions = (perms) => {
  const names = perms.map((p) => p.name);
  const allSelected = names.every((n) => selectedPermissions.value.includes(n));

  if (allSelected) {
    selectedPermissions.value = selectedPermissions.value.filter((n) => !names.includes(n));
  } else {
    const combined = new Set([...selectedPermissions.value, ...names]);
    selectedPermissions.value = Array.from(combined);
  }
};

const saveRolePermissions = async () => {
  if (!activeRole.value) return;

  permissionsModalLoading.value = true;
  try {
    await api.put(`/roles/${activeRole.value.id}`, {
      name: activeRole.value.name,
      display_name: activeRole.value.display_name,
      description: activeRole.value.description,
      permissions: selectedPermissions.value,
    });
    notificationStore.success(t('roles.savedSuccess'));
    showPermissionsModal.value = false;
    loadRoles();
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error saving role permissions.');
  } finally {
    permissionsModalLoading.value = false;
  }
};

// Delete Role Handler
const deleteRole = async (role) => {
  const roleName = role.display_name || role.name;
  const confirmed = await alertService.confirmDelete({
    title: locale.value === 'ar' ? 'تأكيد حذف الدور' : 'Confirm Role Deletion',
    text: t('roles.confirmDelete', { name: roleName }),
    confirmButtonText: locale.value === 'ar' ? 'نعم، احذف الدور' : 'Yes, Delete Role',
    cancelButtonText: locale.value === 'ar' ? 'إلغاء الأمر' : 'Cancel',
  });

  if (!confirmed) {
    return;
  }

  try {
    await api.delete(`/roles/${role.id}`);
    notificationStore.success(t('roles.deletedSuccess'));
    loadRoles();
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error deleting role.');
  }
};

onMounted(() => {
  loadRoles();
});
</script>
