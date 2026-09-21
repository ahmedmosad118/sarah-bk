<template>
  <div class="inline-flex items-center gap-3">
    <!-- Monogram Icon Badge -->
    <div
      :class="[
        sizeClasses.badge,
        'flex items-center justify-center shrink-0 rounded-2xl bg-[#0C1315] shadow-lg shadow-black/20 border border-white/10 transition-transform hover:scale-105 select-none'
      ]"
    >
      <svg
        viewBox="0 0 128 128"
        :class="sizeClasses.svg"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <!-- Top-Left Accent Dot -->
        <rect x="23" y="23" width="11" height="11" rx="3.5" fill="#00C896" />

        <!-- Bottom-Right Accent Dot -->
        <rect x="94" y="94" width="11" height="11" rx="3.5" fill="#00C896" />

        <!-- Top S-Section (Pure White) -->
        <path
          d="M 35 68
             L 35 44
             C 35 36.268 41.268 30 49 30
             L 84 30
             C 89.523 30 94 34.477 94 40
             L 94 46
             L 51 46
             C 48.791 46 47 47.791 47 50
             L 47 68
             Z"
          fill="#FFFFFF"
        />

        <!-- Middle Pill (Brand Green #00C896) -->
        <rect x="41" y="55" width="46" height="18" rx="9" fill="#00C896" />

        <!-- Bottom S-Section (Sage / Platinum) -->
        <path
          d="M 93 60
             L 93 84
             C 93 91.732 86.732 98 79 98
             L 44 98
             C 38.477 98 34 93.523 34 88
             L 34 82
             L 77 82
             C 79.209 82 81 80.209 81 78
             L 81 60
             Z"
          fill="#D0DBD5"
        />
      </svg>
    </div>

    <!-- Text Typography -->
    <div v-if="showText" class="flex flex-col">
      <div class="flex items-center gap-1.5">
        <span
          :class="[
            sizeClasses.title,
            textColor === 'light' ? 'text-white' : 'text-gray-900 dark:text-white',
            'font-black tracking-tight leading-none'
          ]"
        >
          صَرْح
        </span>
        <span
          v-if="showTag"
          class="rounded-md bg-[#00C896]/15 px-1.5 py-0.5 text-[9px] font-black font-mono tracking-wider text-[#00C896]"
        >
          ERP
        </span>
      </div>
      <span
        :class="[
          sizeClasses.subtitle,
          textColor === 'light' ? 'text-gray-300' : 'text-gray-400 dark:text-gray-400',
          'font-bold font-mono tracking-wider'
        ]"
      >
        SARH • SaaS
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  size: {
    type: String,
    default: 'md', // 'sm', 'md', 'lg', 'xl'
  },
  showText: {
    type: Boolean,
    default: true,
  },
  showTag: {
    type: Boolean,
    default: true,
  },
  textColor: {
    type: String,
    default: 'auto', // 'auto', 'light'
  },
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return {
        badge: 'h-8 w-8 text-sm',
        svg: 'h-6 w-6',
        title: 'text-base',
        subtitle: 'text-[9px]',
      };
    case 'lg':
      return {
        badge: 'h-12 w-12 text-2xl',
        svg: 'h-9 w-9',
        title: 'text-2xl',
        subtitle: 'text-xs',
      };
    case 'xl':
      return {
        badge: 'h-14 w-14 text-3xl',
        svg: 'h-11 w-11',
        title: 'text-3xl',
        subtitle: 'text-xs',
      };
    case 'md':
    default:
      return {
        badge: 'h-10 w-10 text-xl',
        svg: 'h-7 w-7',
        title: 'text-lg',
        subtitle: 'text-[10px]',
      };
  }
});
</script>
