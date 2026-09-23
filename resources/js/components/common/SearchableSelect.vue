<template>
  <div class="relative w-full" ref="containerRef">
    <!-- Trigger Button -->
    <button
      type="button"
      :disabled="disabled"
      @click="toggleDropdown"
      class="w-full flex items-center justify-between rounded-xl border bg-gray-50 py-2.5 px-3.5 text-xs font-medium transition-all outline-hidden text-left"
      :class="[
        $i18n.locale === 'ar' ? 'text-right' : 'text-left',
        isOpen
          ? 'border-[#00C896] bg-white ring-2 ring-[#00C896]/20 dark:border-[#00C896] dark:bg-gray-900'
          : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900/50 dark:hover:border-gray-600',
        disabled ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer',
      ]"
    >
      <span
        v-if="selectedOption"
        class="truncate font-semibold text-gray-900 dark:text-white"
      >
        {{ formatLabel(selectedOption.label) }}
      </span>
      <span
        v-else
        class="truncate text-gray-400 dark:text-gray-500"
      >
        {{ formatLabel(placeholder) || $t('common.searchPlaceholder') }}
      </span>

      <div class="flex items-center gap-1.5 ml-2">
        <!-- Clear Selection Button -->
        <span
          v-if="clearable && selectedOption && !disabled"
          @click.stop="clearSelection"
          class="p-0.5 rounded-full text-gray-400 hover:text-rose-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          title="Clear"
        >
          <X class="h-3.5 w-3.5" />
        </span>

        <!-- Chevron -->
        <ChevronDown
          class="h-4 w-4 text-gray-400 transition-transform duration-200"
          :class="{ 'rotate-180 text-[#00C896]': isOpen }"
        />
      </div>
    </button>

    <!-- Dropdown Menu -->
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="transform scale-95 opacity-0 -translate-y-1"
      enter-to-class="transform scale-100 opacity-100 translate-y-0"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="transform scale-100 opacity-100 translate-y-0"
      leave-to-class="transform scale-95 opacity-0 -translate-y-1"
    >
      <div
        v-if="isOpen"
        class="absolute left-0 right-0 z-50 mt-1.5 max-h-72 w-full rounded-2xl border border-gray-100 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-800 flex flex-col"
        :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
      >
        <!-- Search Box inside Dropdown -->
        <div v-if="options.length > 5 || showSearchAlways" class="p-1 mb-1 border-b border-gray-100 dark:border-gray-700">
          <div class="relative">
            <input
              ref="searchInputRef"
              type="text"
              v-model="searchQuery"
              :placeholder="formatLabel(searchPlaceholder) || $t('common.search')"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
              :class="$i18n.locale === 'ar' ? 'pr-8 pl-3 text-right' : 'pl-8 pr-3 text-left'"
              @click.stop
            />
            <Search
              class="absolute top-2 h-3.5 w-3.5 text-gray-400 pointer-events-none"
              :class="$i18n.locale === 'ar' ? 'right-2.5' : 'left-2.5'"
            />
          </div>
        </div>

        <!-- Quick Action / Add New Option inside Select -->
        <div v-if="actionLabel" class="p-1 mb-1 border-b border-gray-100 dark:border-gray-700">
          <button
            type="button"
            @click.stop="triggerAction"
            class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold rounded-xl bg-[#00C896]/10 text-[#00A87E] hover:bg-[#00C896]/20 dark:bg-[#00C896]/20 dark:text-[#00C896] transition-all cursor-pointer group"
          >
            <span class="flex items-center gap-1.5">
              <Plus class="h-3.5 w-3.5 transition-transform group-hover:scale-110" />
              <span>{{ formatLabel(actionLabel) }}</span>
            </span>
            <span class="text-[10px] font-medium opacity-75 px-1.5 py-0.5 rounded-md bg-white/70 dark:bg-gray-800/80">{{ $t('common.create') }}</span>
          </button>
        </div>

        <!-- Options List -->
        <div class="overflow-y-auto max-h-52 space-y-0.5 custom-scrollbar py-0.5">
          <!-- Empty option (Reset / All) -->
          <button
            v-if="allowEmpty"
            type="button"
            @click="selectOption('')"
            class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium rounded-xl text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700/50 transition-colors"
            :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
          >
            <span>— {{ formatLabel(placeholder) || $t('common.all') }} —</span>
          </button>

          <!-- List Items -->
          <button
            v-for="opt in filteredOptions"
            :key="opt.value"
            type="button"
            @click="selectOption(opt.value)"
            class="w-full flex items-center justify-between px-3 py-2 text-xs font-medium rounded-xl transition-colors"
            :class="[
              $i18n.locale === 'ar' ? 'text-right' : 'text-left',
              isSelected(opt.value)
                ? 'bg-emerald-50 text-emerald-800 font-bold dark:bg-emerald-950/40 dark:text-emerald-300'
                : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/60',
            ]"
          >
            <span class="truncate">{{ formatLabel(opt.label) }}</span>
            <Check
              v-if="isSelected(opt.value)"
              class="h-4 w-4 text-[#00C896] shrink-0 ml-2"
            />
          </button>

          <!-- Empty State -->
          <div
            v-if="filteredOptions.length === 0"
            class="py-6 text-center text-xs text-gray-400 dark:text-gray-500"
          >
            {{ $t('common.noData') }}
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { useI18n } from 'vue-i18n';
import { ChevronDown, Search, Check, X, Plus } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: [String, Number, Boolean, null],
    default: null,
  },
  options: {
    type: Array,
    default: () => [],
  },
  placeholder: {
    type: String,
    default: '',
  },
  searchPlaceholder: {
    type: String,
    default: '',
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  clearable: {
    type: Boolean,
    default: true,
  },
  allowEmpty: {
    type: Boolean,
    default: false,
  },
  showSearchAlways: {
    type: Boolean,
    default: true,
  },
  actionLabel: {
    type: String,
    default: '',
  },
});

const emit = defineEmits(['update:modelValue', 'change', 'action']);

const { t, te } = useI18n();
const containerRef = ref(null);
const searchInputRef = ref(null);
const isOpen = ref(false);
const searchQuery = ref('');

const formatLabel = (key) => {
  if (!key) return '';
  return te(key) ? t(key) : key;
};

const selectedOption = computed(() => {
  if (props.modelValue === null || props.modelValue === undefined || props.modelValue === '') {
    return null;
  }
  return props.options.find((opt) => String(opt.value) === String(props.modelValue)) || null;
});

const isSelected = (val) => {
  if (props.modelValue === null || props.modelValue === undefined) return false;
  return String(val) === String(props.modelValue);
};

const filteredOptions = computed(() => {
  if (!searchQuery.value.trim()) {
    return props.options;
  }
  const q = searchQuery.value.toLowerCase().trim();
  return props.options.filter((opt) => {
    const label = formatLabel(opt.label).toLowerCase();
    const val = String(opt.value).toLowerCase();
    return label.includes(q) || val.includes(q);
  });
});

const toggleDropdown = () => {
  if (props.disabled) return;
  isOpen.value = !isOpen.value;
  if (isOpen.value) {
    searchQuery.value = '';
    nextTick(() => {
      searchInputRef.value?.focus();
    });
  }
};

const selectOption = (val) => {
  emit('update:modelValue', val);
  emit('change', val);
  isOpen.value = false;
  searchQuery.value = '';
};

const clearSelection = () => {
  emit('update:modelValue', null);
  emit('change', null);
};

const triggerAction = () => {
  isOpen.value = false;
  searchQuery.value = '';
  emit('action');
};

const handleClickOutside = (e) => {
  if (containerRef.value && !containerRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

const handleKeyDown = (e) => {
  if (e.key === 'Escape') {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  document.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside);
  document.removeEventListener('keydown', handleKeyDown);
});
</script>
