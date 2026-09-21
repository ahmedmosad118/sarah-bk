<template>
  <div class="space-y-6">
    <BreadcrumbDefault :pageTitle="$t('roles.title')" />

    <!-- Top Action Bar -->
    <div class="flex items-center justify-between">
      <div></div>
      <button
        @click="openCreateRoleModal"
        class="flex items-center gap-1.5 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-4 py-2.5 text-xs font-black text-gray-950 transition-colors shadow-sm shadow-[#00C896]/20"
      >
        <Plus class="h-4 w-4" />
        <span>{{ $t('roles.createNewRole') }}</span>
      </button>
    </div>

    <!-- Roles Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="role in roles"
        :key="role.id"
        class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs hover:shadow-md dark:border-gray-800 dark:bg-gray-800 transition-all flex flex-col justify-between"
      >
        <div>
          <div class="flex items-start justify-between gap-2 mb-3">
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">
                  {{ role.display_name || role.name }}
                </h3>
                <span
                  class="rounded-md px-1.5 py-0.5 text-[10px] font-bold font-mono"
                  :class="role.name === 'Owner' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'"
                >
                  {{ role.name }}
                </span>
              </div>
              <p class="text-[11px] text-gray-400 mt-1 line-clamp-2">
                {{ role.description || $t('roles.roleDesc') }}
              </p>
            </div>
          </div>

          <div class="flex items-center gap-4 py-3 border-y border-gray-100 dark:border-gray-700/60 my-3 text-xs">
            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
              <Users class="h-3.5 w-3.5 text-blue-500" />
              <span class="font-bold font-mono">{{ role.users_count || 0 }}</span>
              <span>{{ $t('roles.employees') }}</span>
            </div>
            <div class="flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
              <Key class="h-3.5 w-3.5 text-amber-500" />
              <span class="font-bold font-mono">{{ role.permissions?.length || 0 }}</span>
              <span>{{ $t('roles.permissions') }}</span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-2">
          <button
            @click="openPermissionsModal(role)"
            class="rounded-xl border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-700 transition-colors flex items-center gap-1.5"
          >
            <Settings class="h-3.5 w-3.5 text-slate-500" />
            <span>{{ $t('roles.editPermissions') }}</span>
          </button>

          <button
            v-if="!role.is_default && !['Owner', 'Super Admin', 'Administrator', 'Viewer'].includes(role.name)"
            @click="deleteRole(role)"
            class="flex items-center gap-1 text-xs font-bold text-rose-500 hover:text-rose-700 p-1.5"
            :title="$t('roles.deleteCustomRole')"
          >
            <Trash2 class="h-3.5 w-3.5" />
            <span>{{ $t('roles.deleteCustomRole') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Permissions Matrix Modal -->
    <CrudModal
      :show="showModal"
      :title="$t('roles.matrixTitle', { name: activeRole?.display_name || activeRole?.name || '' })"
      :loading="modalLoading"
      @close="showModal = false"
      @save="saveRolePermissions"
    >
      <div class="space-y-6" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
        <div v-if="isCreating" class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('roles.roleCodeName') }}
            </label>
            <input
              type="text"
              v-model="roleForm.name"
              placeholder="e.g. Senior Site Engineer"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('roles.roleDisplayName') }}
            </label>
            <input
              type="text"
              v-model="roleForm.display_name"
              placeholder="مثال: كبير مهندسي الموقع"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
            />
          </div>
          <div class="col-span-2">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('roles.roleDesc') }}
            </label>
            <textarea
              v-model="roleForm.description"
              rows="2"
              placeholder="وصف مختصر لمسؤوليات هذا الدور"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
            ></textarea>
          </div>
        </div>

        <!-- Permissions List by Module -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black uppercase text-gray-400">
              {{ $t('roles.selectPermissionsForRole') }}
            </h4>
            <div class="flex items-center gap-2 text-xs">
              <button
                type="button"
                @click="selectAllPermissions"
                class="text-blue-600 font-bold hover:underline"
              >
                {{ $t('roles.selectAll') }}
              </button>
              <span class="text-gray-300">|</span>
              <button
                type="button"
                @click="clearAllPermissions"
                class="text-gray-500 font-bold hover:underline"
              >
                {{ $t('roles.clearAll') }}
              </button>
            </div>
          </div>

          <div
            v-for="(perms, moduleName) in modules"
            :key="moduleName"
            class="rounded-2xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-700/80 dark:bg-gray-900/30"
          >
            <div class="flex items-center justify-between mb-3 border-b border-gray-200/60 pb-2 dark:border-gray-700/60">
              <span class="text-xs font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="inline-block w-1.5 h-3 bg-[#00C896] rounded-full"></span>
                {{ moduleName }}
              </span>
              <button
                type="button"
                @click="toggleModule(perms)"
                class="text-[11px] font-semibold text-[#00A87E] dark:text-[#00C896] hover:underline"
              >
                {{ $t('roles.toggleModule') }}
              </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
              <label
                v-for="perm in perms"
                :key="perm.id"
                class="flex items-start gap-2.5 p-2 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-[#00C896]/40 transition-colors"
              >
                <input
                  type="checkbox"
                  :value="perm.name"
                  v-model="selectedPermissions"
                  :disabled="activeRole?.name === 'Owner'"
                  class="mt-0.5 rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896]"
                />
                <div>
                  <span class="block text-xs font-bold text-gray-800 dark:text-gray-200">
                    {{ perm.display_name || perm.name }}
                  </span>
                  <span class="block text-[10px] font-mono text-gray-400">
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
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import { useNotificationStore } from '../../stores/notification';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';
import CrudModal from '../../components/crud/CrudModal.vue';
import { ShieldCheck, Users, Key, Settings, Trash2, Plus } from 'lucide-vue-next';

const { t } = useI18n();
const notificationStore = useNotificationStore();
const roles = ref([]);
const modules = ref({});
const showModal = ref(false);
const modalLoading = ref(false);
const isCreating = ref(false);
const activeRole = ref(null);
const selectedPermissions = ref([]);

const roleForm = ref({
  name: '',
  display_name: '',
  description: '',
});

const loadRoles = async () => {
  try {
    const res = await api.get('/roles');
    roles.value = res.data.data;
    modules.value = res.data.modules;
  } catch (err) {
    notificationStore.error(t('common.loading'));
  }
};

const openCreateRoleModal = () => {
  isCreating.value = true;
  activeRole.value = null;
  roleForm.value = { name: '', display_name: '', description: '' };
  selectedPermissions.value = [];
  showModal.value = true;
};

const openPermissionsModal = (role) => {
  isCreating.value = false;
  activeRole.value = role;
  roleForm.value = {
    name: role.name,
    display_name: role.display_name,
    description: role.description,
  };
  selectedPermissions.value = role.permissions.map((p) => p.name);
  showModal.value = true;
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

const toggleModule = (perms) => {
  const modulePermNames = perms.map((p) => p.name);
  const allSelected = modulePermNames.every((n) => selectedPermissions.value.includes(n));

  if (allSelected) {
    selectedPermissions.value = selectedPermissions.value.filter((n) => !modulePermNames.includes(n));
  } else {
    selectedPermissions.value = Array.from(new Set([...selectedPermissions.value, ...modulePermNames]));
  }
};

const saveRolePermissions = async () => {
  modalLoading.value = true;
  try {
    if (isCreating.value) {
      await api.post('/roles', {
        ...roleForm.value,
        permissions: selectedPermissions.value,
      });
      notificationStore.success(t('roles.createdSuccess'));
    } else {
      await api.put(`/roles/${activeRole.value.id}`, {
        ...roleForm.value,
        permissions: selectedPermissions.value,
      });
      notificationStore.success(t('roles.savedSuccess'));
    }
    showModal.value = false;
    loadRoles();
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error saving role permissions.');
  } finally {
    modalLoading.value = false;
  }
};

const deleteRole = async (role) => {
  if (!confirm(t('roles.confirmDelete', { name: role.display_name || role.name }))) {
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
