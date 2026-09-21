<template>
  <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
      <span class="inline-block w-2 h-5 bg-blue-600 rounded-full"></span>
      {{ pageTitle }}
    </h2>

    <nav>
      <ol class="flex items-center gap-2 text-xs font-semibold text-gray-500 dark:text-gray-400">
        <li>
          <router-link to="/dashboard" class="hover:text-blue-600 transition-colors">{{ $t('common.home') }}</router-link>
        </li>
        <li>
          <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5 text-gray-400" />
          <ChevronRight v-else class="h-3.5 w-3.5 text-gray-400" />
        </li>
        <li class="text-blue-600 font-bold dark:text-blue-400">{{ pageTitle }}</li>
      </ol>
    </nav>
  </div>
</template>

<script setup>
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

defineProps({
  pageTitle: {
    type: String,
    required: true,
  },
});
</script>
