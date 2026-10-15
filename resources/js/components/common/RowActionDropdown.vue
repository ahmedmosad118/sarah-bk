<template>
  <div class="relative inline-block text-start" ref="dropdownRef">
    <button
      type="button"
      @click.stop="toggle"
      :class="[
        isOpen
          ? 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-white'
          : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white',
      ]"
      class="p-1.5 rounded-lg transition-colors cursor-pointer inline-flex items-center justify-center focus:outline-hidden"
      :aria-expanded="isOpen"
      :title="$t('common.actions') || 'Actions'"
    >
      <slot name="trigger">
        <MoreVertical class="h-4 w-4" />
      </slot>
    </button>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute end-0 mt-1 min-w-44 rounded-xl border border-gray-100 bg-white p-1 shadow-lg dark:border-gray-800 dark:bg-gray-900 z-40 text-xs"
        @click.stop
      >
        <slot :close="close" />
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { MoreVertical } from 'lucide-vue-next';

const isOpen = ref(false);
const dropdownRef = ref(null);

const toggle = () => {
  isOpen.value = !isOpen.value;
};

const close = () => {
  isOpen.value = false;
};

const handleOutsideClick = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    close();
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    close();
  }
};

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  document.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
  document.removeEventListener('click', handleOutsideClick);
  document.removeEventListener('keydown', handleKeyDown);
});

defineExpose({ close });
</script>
