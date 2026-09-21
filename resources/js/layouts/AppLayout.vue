<template>
  <div
    class="flex h-screen overflow-hidden bg-gray-50 dark:bg-gray-950 font-sans transition-colors duration-150"
    :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
  >
    <!-- Sidebar -->
    <SidebarArea :sidebar-open="sidebarOpen" @close-sidebar="sidebarOpen = false" />

    <!-- Main Content Column -->
    <div class="relative flex flex-1 flex-col overflow-y-auto overflow-x-hidden">
      <!-- Header -->
      <HeaderArea @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <!-- Page Content -->
      <main class="grow p-4 sm:p-6 md:p-8">
        <router-view />
      </main>
    </div>

    <!-- Toast Notifications Center -->
    <div
      class="fixed bottom-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
      :class="$i18n.locale === 'ar' ? 'left-5' : 'right-5'"
    >
      <div
        v-for="toast in notificationStore.toasts"
        :key="toast.id"
        class="pointer-events-auto rounded-2xl p-4 shadow-xl border flex items-start gap-3 animate-in slide-in-from-bottom-5 duration-200"
        :class="{
          'bg-white border-emerald-200 text-emerald-950 dark:bg-gray-800 dark:border-emerald-800 dark:text-emerald-300': toast.type === 'success',
          'bg-white border-rose-200 text-rose-950 dark:bg-gray-800 dark:border-rose-800 dark:text-rose-300': toast.type === 'error',
          'bg-white border-blue-200 text-blue-950 dark:bg-gray-800 dark:border-blue-800 dark:text-blue-300': toast.type === 'info',
        }"
      >
        <div class="shrink-0 mt-0.5">
          <CheckCircle2 v-if="toast.type === 'success'" class="h-5 w-5 text-emerald-500" />
          <AlertCircle v-else-if="toast.type === 'error'" class="h-5 w-5 text-rose-500" />
          <Info v-else class="h-5 w-5 text-blue-500" />
        </div>

        <div class="flex-1" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
          <p class="text-xs font-bold">{{ toast.title }}</p>
          <p class="text-[11px] font-medium text-gray-500 dark:text-gray-400 mt-0.5">{{ toast.message }}</p>
        </div>

        <button
          @click="notificationStore.removeToast(toast.id)"
          class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 p-1 rounded-lg transition-colors"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useNotificationStore } from '../stores/notification';
import { CheckCircle2, AlertCircle, Info, X } from 'lucide-vue-next';
import SidebarArea from '../components/tailadmin/SidebarArea.vue';
import HeaderArea from '../components/tailadmin/HeaderArea.vue';

const sidebarOpen = ref(false);
const notificationStore = useNotificationStore();
</script>
