<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-gray-950/75 backdrop-blur-xs transition-opacity"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <div
        class="relative w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-800 dark:border dark:border-gray-700 max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-200"
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
          <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <span class="inline-block w-2 h-4 bg-[#00C896] rounded-full"></span>
            {{ title }}
          </h3>
          <button
            @click="$emit('close')"
            class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 transition-colors"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto py-4 flex-1">
          <slot />
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
          <button
            type="button"
            @click="$emit('close')"
            class="rounded-xl border border-gray-200 px-4 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
          >
            {{ $t('common.cancel') }}
          </button>

          <button
            type="button"
            :disabled="loading"
            @click="$emit('save')"
            class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-5 py-2.5 text-xs font-black text-[#0C1315] disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-[#0C1315] border-t-transparent"></span>
            <span>{{ $t('common.save') }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { X } from 'lucide-vue-next';

defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['close', 'save']);
</script>
