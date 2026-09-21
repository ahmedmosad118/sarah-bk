<template>
  <div class="space-y-6">
    <CrudIndex
      endpoint="/job-titles"
      :title="$t('jobTitles.title')"
      :custom-columns="columns"
    >
      <!-- Custom Name Column with Bilingual Support -->
      <template #col-name="{ item }">
        <div>
          <span class="block font-bold text-gray-900 dark:text-white">
            {{ $i18n.locale === 'ar' ? (item.name_ar || item.name) : (item.name_en || item.name) }}
          </span>
          <span class="block text-[11px] font-mono text-gray-400">
            {{ $i18n.locale === 'ar' ? item.name_en : item.name_ar }}
          </span>
        </div>
      </template>

      <!-- Custom Code Column -->
      <template #col-code="{ value }">
        <span class="inline-block rounded-md bg-gray-100 px-2 py-0.5 text-xs font-mono font-bold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
          {{ value || '—' }}
        </span>
      </template>

      <!-- Custom Is Default Column -->
      <template #col-is_default="{ value }">
        <span
          class="rounded-full px-2 py-0.5 text-[10px] font-bold"
          :class="value ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
        >
          {{ value ? $t('jobTitles.defaultBadge') : $t('jobTitles.customBadge') }}
        </span>
      </template>

      <!-- Custom Is Active Column -->
      <template #col-is_active="{ value }">
        <span
          class="inline-flex items-center gap-1.5 text-xs font-bold"
          :class="value ? 'text-emerald-600 dark:text-emerald-400' : 'text-gray-400'"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="value ? 'bg-emerald-500' : 'bg-gray-400'"></span>
          {{ value ? $t('jobTitles.available') : $t('common.inactive') }}
        </span>
      </template>
    </CrudIndex>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CrudIndex from '../../components/crud/CrudIndex.vue';

const { t } = useI18n();

const columns = computed(() => [
  { name: 'name', label: t('jobTitles.nameCol') },
  { name: 'code', label: t('jobTitles.codeCol') },
  { name: 'description', label: t('jobTitles.descCol') },
  { name: 'is_default', label: t('jobTitles.typeCol') },
  { name: 'is_active', label: t('jobTitles.statusCol') },
]);
</script>
