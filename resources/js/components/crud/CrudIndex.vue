<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <BreadcrumbDefault :pageTitle="title || schema?.title || $t('common.dashboard')" />

    <!-- Extra Top Slots (Stats, Filters) -->
    <slot name="top-stats" />

    <!-- TailAdmin Data Table -->
    <TailAdminDataTable
      :columns="tableColumns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :allow-create="allowCreate"
      :create-button-text="createButtonTextComputed"
      :search-placeholder="searchPlaceholderComputed"
      @search="onSearch"
      @page-change="onPageChange"
      @create="openCreateModal"
      @edit="openEditModal"
      @delete="confirmDelete"
    >
      <template #filters>
        <slot name="filters" />
      </template>

      <!-- Forward column slots -->
      <template
        v-for="col in tableColumns"
        :key="col.name"
        #[`col-${col.name}`]="slotProps"
      >
        <slot :name="`col-${col.name}`" v-bind="slotProps" />
      </template>

      <!-- Forward actions slot -->
      <template #actions="slotProps">
        <slot name="actions" v-bind="slotProps" />
      </template>
    </TailAdminDataTable>

    <!-- Create / Edit Modal -->
    <CrudModal
      :show="showModal"
      :title="modalTitleComputed"
      :loading="modalLoading"
      @close="closeModal"
      @save="saveRecord"
    >
      <CrudForm
        v-if="schema"
        :schema="schema"
        :form-data="formData"
        :errors="formErrors"
        :related-options="relatedOptions"
        @field-action="$emit('field-action', $event)"
      >
        <template #extra-fields>
          <slot name="modal-extra-fields" :form-data="formData" :modal-mode="modalMode" />
        </template>
      </CrudForm>
      <div v-else class="flex flex-col items-center justify-center py-12 text-center text-gray-400">
        <div class="h-8 w-8 animate-spin rounded-full border-3 border-[#00C896] border-t-transparent"></div>
        <p class="mt-3 text-xs font-semibold">{{ $t('common.loading') }}</p>
      </div>
    </CrudModal>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import BreadcrumbDefault from '../tailadmin/BreadcrumbDefault.vue';
import TailAdminDataTable from '../tailadmin/TailAdminDataTable.vue';
import CrudModal from './CrudModal.vue';
import CrudForm from './CrudForm.vue';

const props = defineProps({
  endpoint: {
    type: String,
    required: true,
  },
  title: String,
  allowCreate: {
    type: Boolean,
    default: true,
  },
  customColumns: {
    type: Array,
    default: null,
  },
  relatedOptions: {
    type: Object,
    default: () => ({}),
  },
  extraParams: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['loaded', 'record-saved', 'record-deleted', 'open-modal', 'field-action']);

const { t, te, locale } = useI18n();
const notificationStore = useNotificationStore();
const schema = ref(null);
const items = ref([]);
const meta = ref(null);
const loading = ref(false);
const showModal = ref(false);
const modalMode = ref('create'); // 'create' or 'edit'
const modalLoading = ref(false);
const formData = ref({});
const formErrors = ref({});
const activeRecordId = ref(null);
const currentSearch = ref('');
const currentPage = ref(1);

const formatKey = (key) => {
  if (!key) return '';
  return te(key) ? t(key) : key;
};

const createButtonTextComputed = computed(() => {
  const singular = formatKey(schema.value?.singular_title);
  return singular ? `${t('common.create')} ${singular}` : t('common.createRecord');
});

const searchPlaceholderComputed = computed(() => {
  const title = formatKey(schema.value?.title || props.title);
  return title ? `${t('common.search')} ${title}...` : t('common.searchPlaceholder');
});

const modalTitleComputed = computed(() => {
  const singular = formatKey(schema.value?.singular_title || '');
  if (modalMode.value === 'create') {
    return `${t('common.create')} ${singular}`;
  }
  return `${t('common.edit')} ${singular}`;
});

const tableColumns = computed(() => {
  if (props.customColumns) {
    return props.customColumns.map((col) => ({
      ...col,
      label: formatKey(col.label),
    }));
  }
  if (!schema.value?.fields) {
    return [{ name: 'id', label: '#' }, { name: 'name', label: t('common.name') }];
  }
  return schema.value.fields
    .filter((f) => !f.hidden_in_table && f.type !== 'hidden')
    .map((f) => ({
      name: f.name,
      label: formatKey(f.label),
    }));
});

const loadData = async (page = 1, search = '') => {
  loading.value = true;
  currentPage.value = page;
  currentSearch.value = search;

  try {
    const res = await api.get(props.endpoint, {
      params: { page, search, ...props.extraParams },
    });
    items.value = res.data.data;
    meta.value = res.data.meta;
    if (res.data.schema && !schema.value) {
      schema.value = res.data.schema;
    }
    emit('loaded', res.data);
  } catch (err) {
    notificationStore.error(t('common.loading'));
  } finally {
    loading.value = false;
  }
};

const onSearch = (q) => {
  loadData(1, q);
};

const onPageChange = (p) => {
  loadData(p, currentSearch.value);
};

const openCreateModal = () => {
  modalMode.value = 'create';
  activeRecordId.value = null;
  formErrors.value = {};

  const initial = {};
  schema.value?.fields?.forEach((f) => {
    initial[f.name] = f.default !== undefined ? f.default : null;
  });
  formData.value = initial;
  showModal.value = true;
  emit('open-modal', { mode: 'create', data: formData.value });
};

const openEditModal = (item) => {
  modalMode.value = 'edit';
  activeRecordId.value = item.id;
  formErrors.value = {};
  formData.value = { ...item };
  showModal.value = true;
  emit('open-modal', { mode: 'edit', data: formData.value, item });
};

const closeModal = () => {
  showModal.value = false;
  formErrors.value = {};
};

const saveRecord = async () => {
  modalLoading.value = true;
  formErrors.value = {};

  try {
    let res;
    if (modalMode.value === 'create') {
      res = await api.post(props.endpoint, formData.value);
    } else {
      res = await api.put(`${props.endpoint}/${activeRecordId.value}`, formData.value);
    }

    notificationStore.success(res.data.message || t('common.successSave'));
    closeModal();
    loadData(currentPage.value, currentSearch.value);
    emit('record-saved', res.data);
  } catch (err) {
    if (err.response?.status === 422 && err.response?.data?.errors) {
      formErrors.value = err.response.data.errors;
      notificationStore.error(t('common.validationError'));
    } else {
      notificationStore.error(err.response?.data?.message || t('common.validationError'));
    }
  } finally {
    modalLoading.value = false;
  }
};

const confirmDelete = async (id) => {
  const confirmed = await alertService.confirmDelete({
    title: locale.value === 'ar' ? 'تأكيد الحذف النهائي' : 'Confirm Deletion',
    text: t('common.confirmDelete'),
    confirmButtonText: locale.value === 'ar' ? 'نعم، احذف السجل' : 'Yes, Delete',
    cancelButtonText: locale.value === 'ar' ? 'إلغاء الأمر' : 'Cancel',
  });

  if (!confirmed) {
    return;
  }

  try {
    const res = await api.delete(`${props.endpoint}/${id}`);
    notificationStore.success(res.data.message || t('common.successDelete'));
    loadData(currentPage.value, currentSearch.value);
    emit('record-deleted', id);
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error deleting record.');
  }
};

onMounted(() => {
  loadData();
});

defineExpose({
  loadData,
  fetchData: loadData,
  openCreateModal,
  openEditModal,
});
</script>
