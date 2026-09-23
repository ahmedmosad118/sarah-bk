<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[999] flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-gray-950/80 backdrop-blur-sm transition-opacity"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <div
        class="relative w-full rounded-3xl bg-white p-5 sm:p-7 shadow-2xl dark:bg-gray-850 dark:border dark:border-gray-700 max-h-[92vh] flex flex-col animate-in fade-in zoom-in-95 duration-200"
        :class="modalSizeClass"
      >
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700/80 shrink-0">
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
        <div
          v-if="showFooter"
          class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-700 shrink-0"
        >
          <button
            v-if="showCancelButton"
            type="button"
            @click="$emit('close')"
            class="rounded-xl border border-gray-200 px-5 py-2.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors cursor-pointer"
          >
            {{ cancelText || (!showSaveButton ? $t('common.close') : $t('common.cancel')) }}
          </button>

          <button
            v-if="showSaveButton"
            type="button"
            :disabled="loading"
            @click="$emit('save')"
            class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-6 py-2.5 text-xs font-black text-gray-950 disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20 cursor-pointer"
          >
            <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-gray-950 border-t-transparent"></span>
            <span>{{ saveText || $t('common.save') }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

const props = defineProps({
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
  size: {
    type: String,
    default: '5xl',
  },
  showSaveButton: {
    type: Boolean,
    default: true,
  },
  showCancelButton: {
    type: Boolean,
    default: true,
  },
  showFooter: {
    type: Boolean,
    default: true,
  },
  saveText: {
    type: String,
    default: '',
  },
  cancelText: {
    type: String,
    default: '',
  },
});

defineEmits(['close', 'save']);

const modalSizeClass = computed(() => {
  const sizeMap = {
    sm: 'max-w-md',
    md: 'max-w-lg',
    lg: 'max-w-2xl',
    xl: 'max-w-3xl',
    '2xl': 'max-w-4xl',
    '3xl': 'max-w-4xl',
    '4xl': 'max-w-5xl',
    '5xl': 'max-w-6xl',
    '6xl': 'max-w-7xl',
    full: 'max-w-[95vw]',
  };
  return sizeMap[props.size] || 'max-w-5xl';
});
</script>
