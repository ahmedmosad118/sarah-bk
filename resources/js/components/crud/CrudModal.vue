<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[999] flex items-center justify-center p-4 sm:p-6 overflow-y-auto bg-gray-950/75 backdrop-blur-xs transition-opacity"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <div
        class="relative w-full max-w-4xl rounded-3xl bg-white p-6 sm:p-7 shadow-2xl dark:bg-gray-850 dark:border dark:border-gray-700 max-h-[90vh] flex flex-col animate-in fade-in zoom-in-95 duration-200"
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700/80">
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
              <span class="h-2.5 w-2.5 rounded-full bg-[#00C896] animate-pulse"></span>
            </span>
            <div>
              <h3 class="text-sm sm:text-base font-black text-gray-900 dark:text-white">
                {{ title }}
              </h3>
            </div>
          </div>
          <button
            type="button"
            @click="$emit('close')"
            class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700/70 dark:hover:text-gray-200 transition-colors cursor-pointer"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body (Scrollable) -->
        <div class="overflow-y-auto py-5 px-1 flex-1 custom-scrollbar">
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
            class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-5 py-2.5 text-xs font-black text-gray-950 disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-gray-950 border-t-transparent"></span>
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
