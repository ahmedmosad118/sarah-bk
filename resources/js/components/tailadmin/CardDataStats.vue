<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs transition-all hover:shadow-md dark:border-gray-800 dark:bg-gray-800">
    <div class="flex items-center justify-between">
      <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
        <slot name="icon">
          <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </slot>
      </div>

      <span v-if="badge" :class="badgeClass" class="rounded-full px-2.5 py-0.5 text-xs font-bold">
        {{ badge }}
      </span>
    </div>

    <div class="mt-4 flex items-end justify-between">
      <div>
        <h4 class="text-2xl font-black text-gray-900 dark:text-white font-['Outfit',sans-serif]" dir="ltr">
          {{ total }}
        </h4>
        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 mt-1 block">
          {{ title }}
        </span>
      </div>

      <span v-if="subtitle" class="text-xs font-medium text-gray-400">
        {{ subtitle }}
      </span>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: String,
  total: [String, Number],
  badge: String,
  badgeClass: {
    type: String,
    default: 'bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
  },
  subtitle: String,
});
</script>
