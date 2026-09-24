<template>
  <div class="rounded-2xl border border-gray-200 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-800 transition-all">
    <!-- Top Action Bar -->
    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 dark:border-gray-800">
      <!-- Search & Filters -->
      <div class="flex flex-1 flex-wrap items-center gap-3">
        <div class="relative w-full sm:w-72">
          <input
            type="text"
            v-model="searchQuery"
            @input="handleSearch"
            :placeholder="searchPlaceholder || $t('common.searchPlaceholder')"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors"
            :class="$i18n.locale === 'ar' ? 'pr-10 pl-4 text-right' : 'pl-10 pr-4 text-left'"
          />
          <span
            class="absolute top-3 text-gray-400 pointer-events-none"
            :class="$i18n.locale === 'ar' ? 'right-3.5' : 'left-3.5'"
          >
            <Search class="h-4 w-4" />
          </span>
        </div>

        <slot name="filters" />
      </div>

      <!-- Action Button -->
      <div class="flex items-center gap-2">
        <button
          v-if="allowCreate"
          @click="$emit('create')"
          class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-4 py-2.5 text-xs font-black text-gray-950 transition-colors shadow-sm shadow-[#00C896]/20"
        >
          <Plus class="h-4 w-4" />
          <span>{{ createButtonText || $t('common.createRecord') }}</span>
        </button>
      </div>
    </div>

    <!-- Bulk Actions Floating/Highlight Bar -->
    <div
      v-if="selectable && selectedIds.length > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 mx-5 my-3 rounded-2xl bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 shadow-xs animate-in fade-in duration-200"
    >
      <div class="flex items-center gap-2.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-rose-600 text-white font-mono font-black text-xs shadow-2xs">
          {{ selectedIds.length }}
        </span>
        <div>
          <p class="text-xs font-black text-rose-950 dark:text-rose-200">
            تم تحديد {{ selectedIds.length }} عنصر من الجدول
          </p>
          <p class="text-[11px] text-rose-700/80 dark:text-rose-400">
            يمكنك تطبيق إجراء الحذف الجماعي على كافة السجلات المحددة.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="onBulkDeleteClick"
          class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-xs font-black text-white shadow-xs transition-colors cursor-pointer"
        >
          <Trash2 class="h-3.5 w-3.5" />
          <span>حذف المحدد (Delete Selected)</span>
        </button>

        <button
          type="button"
          @click="clearSelection"
          class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-white dark:bg-gray-800 dark:border-rose-900/50 px-3 py-2 text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100 transition-colors cursor-pointer"
        >
          <X class="h-3.5 w-3.5" />
          <span>إلغاء التحديد</span>
        </button>
      </div>
    </div>

    <!-- Table Container -->
    <div class="max-w-full overflow-x-auto">
      <table class="w-full table-auto" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
        <thead>
          <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-900/30 text-[11px] font-black uppercase text-gray-400 tracking-wider">
            <th v-if="selectable" class="py-3 px-4 w-10">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @change="toggleSelectAll"
                class="rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896]"
              />
            </th>
            <th
              v-for="col in columns"
              :key="col.name"
              class="py-3 px-4"
              :class="col.headerClass || ''"
            >
              {{ col.label }}
            </th>
            <th class="py-3 px-4 text-center w-28">{{ $t('common.actions') }}</th>
          </tr>
        </thead>

        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-xs font-medium text-gray-700 dark:text-gray-200">
          <!-- Loading State -->
          <tr v-if="loading">
            <td :colspan="columns.length + (selectable ? 2 : 1)" class="py-12 text-center text-gray-400">
              <div class="flex flex-col items-center justify-center gap-3">
                <div class="h-7 w-7 animate-spin rounded-full border-3 border-blue-600 border-t-transparent"></div>
                <span class="text-xs font-bold">{{ $t('common.loading') }}</span>
              </div>
            </td>
          </tr>

          <!-- Empty State -->
          <tr v-else-if="items.length === 0">
            <td :colspan="columns.length + (selectable ? 2 : 1)" class="py-12 text-center">
              <div class="flex flex-col items-center justify-center gap-2">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800 dark:text-gray-500">
                  <Inbox class="h-6 w-6" />
                </div>
                <p class="text-xs font-bold text-gray-600 dark:text-gray-300">{{ $t('common.noData') }}</p>
                <p class="text-[11px] text-gray-400">{{ $t('common.noDataDesc') }}</p>
              </div>
            </td>
          </tr>

          <!-- Data Rows -->
          <tr
            v-else
            v-for="(item, index) in items"
            :key="item.id || index"
            class="hover:bg-gray-50/80 dark:hover:bg-gray-700/30 transition-colors"
          >
            <td v-if="selectable" class="py-3 px-4 w-10">
              <input
                type="checkbox"
                :value="item.id"
                v-model="selectedIds"
                class="rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896] cursor-pointer"
              />
            </td>

            <td
              v-for="col in columns"
              :key="col.name"
              class="py-3 px-4"
              :class="col.cellClass || ''"
            >
              <slot :name="`col-${col.name}`" :item="item" :value="item[col.name]">
                {{ formatCellValue(item[col.name]) }}
              </slot>
            </td>

            <td class="py-3 px-4 text-center">
              <div class="flex items-center justify-center gap-1.5">
                <slot name="actions" :item="item">
                  <button
                    @click="$emit('edit', item)"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors"
                    :title="$t('common.edit')"
                  >
                    <Pencil class="h-3.5 w-3.5" />
                  </button>
                  <button
                    @click="$emit('delete', item.id)"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition-colors"
                    :title="$t('common.delete')"
                  >
                    <Trash2 class="h-3.5 w-3.5" />
                  </button>
                </slot>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination Footer -->
    <div
      v-if="meta && meta.total > 0"
      class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 dark:border-gray-800 text-xs font-semibold text-gray-500 dark:text-gray-400"
    >
      <div>
        {{ $t('common.totalRecords') }}
        <span class="font-bold text-gray-900 dark:text-white">{{ meta.total }}</span>
        ({{ $t('common.page') }} {{ meta.current_page }} {{ $t('common.of') }} {{ meta.last_page }})
      </div>

      <div class="flex items-center gap-2">
        <button
          :disabled="meta.current_page <= 1"
          @click="$emit('page-change', meta.current_page - 1)"
          class="flex items-center gap-1 rounded-xl border border-gray-200 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-40 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
        >
          <ChevronRight v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
          <ChevronLeft v-else class="h-3.5 w-3.5" />
          <span>{{ $t('common.previous') }}</span>
        </button>

        <button
          :disabled="meta.current_page >= meta.last_page"
          @click="$emit('page-change', meta.current_page + 1)"
          class="flex items-center gap-1 rounded-xl border border-gray-200 px-3 py-1.5 hover:bg-gray-50 disabled:opacity-40 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
        >
          <span>{{ $t('common.next') }}</span>
          <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
          <ChevronRight v-else class="h-3.5 w-3.5" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { Search, Plus, Pencil, Trash2, Inbox, ChevronLeft, ChevronRight, X, CheckSquare } from 'lucide-vue-next';
import { isIsoDateString, formatDate } from '../../utils/date';

const { locale } = useI18n();

const formatCellValue = (val) => {
  if (val === null || val === undefined || val === '') return '—';
  if (isIsoDateString(val)) {
    return formatDate(val, locale.value);
  }
  return val;
};

const props = defineProps({
  columns: {
    type: Array,
    required: true,
  },
  items: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  meta: {
    type: Object,
    default: null,
  },
  allowCreate: {
    type: Boolean,
    default: true,
  },
  createButtonText: {
    type: String,
    default: '',
  },
  searchPlaceholder: {
    type: String,
    default: '',
  },
  selectable: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits([
  'search',
  'page-change',
  'create',
  'edit',
  'delete',
  'bulk-delete',
]);

const searchQuery = ref('');
const selectedIds = ref([]);
let searchTimeout = null;

const handleSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    emit('search', searchQuery.value);
  }, 350);
};

const isAllSelected = computed(() => {
  return props.items.length > 0 && selectedIds.value.length === props.items.length;
});

const toggleSelectAll = () => {
  if (isAllSelected.value) {
    selectedIds.value = [];
  } else {
    selectedIds.value = props.items.map((i) => i.id);
  }
};

const onBulkDeleteClick = () => {
  emit('bulk-delete', [...selectedIds.value]);
};

const clearSelection = () => {
  selectedIds.value = [];
};

watch(() => props.items, () => {
  selectedIds.value = [];
});

defineExpose({
  clearSelection,
  selectedIds,
});
</script>
