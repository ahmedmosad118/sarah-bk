<template>
  <div :class="[colClass, $i18n.locale === 'ar' ? 'text-right' : 'text-left']" class="mb-4">
    <label v-if="field.type !== 'boolean'" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
      {{ field.label }}
      <span v-if="field.required" class="text-rose-500 font-bold">*</span>
    </label>

    <!-- 1. Text / Email / Tel / Password -->
    <div v-if="['text', 'email', 'tel', 'password'].includes(field.type)" class="relative">
      <input
        :type="field.type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="field.placeholder || field.label"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-4 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-colors"
      />
    </div>

    <!-- 2. Textarea -->
    <div v-else-if="field.type === 'textarea'" class="relative">
      <textarea
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="field.placeholder || field.label"
        :rows="3"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-4 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-colors"
      ></textarea>
    </div>

    <!-- 3. Number / Currency / Percentage -->
    <div v-else-if="['number', 'currency', 'percentage'].includes(field.type)" class="relative">
      <input
        type="number"
        :step="field.step || (field.type === 'currency' ? '0.01' : 'any')"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value === '' ? null : Number($event.target.value))"
        :placeholder="field.placeholder || '0'"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-4 text-xs font-bold text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
        dir="ltr"
      />
      <span
        v-if="field.type === 'currency'"
        class="absolute top-2.5 text-xs font-bold text-[#00C896]"
        :class="$i18n.locale === 'ar' ? 'left-3.5' : 'right-3.5'"
      >
        {{ $t('settings.currencySymbol') }}
      </span>
      <span
        v-if="field.type === 'percentage'"
        class="absolute top-2.5 text-xs font-bold text-[#00C896]"
        :class="$i18n.locale === 'ar' ? 'left-3.5' : 'right-3.5'"
      >
        %
      </span>
    </div>

    <!-- 4. Select / Relation Dropdown -->
    <div v-else-if="['select', 'relation'].includes(field.type)" class="relative">
      <select
        :value="modelValue"
        @change="$emit('update:modelValue', $event.target.value)"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-4 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-colors appearance-none cursor-pointer"
      >
        <option value="">{{ field.placeholder || '— ' + field.label + ' —' }}</option>
        <option
          v-for="opt in computedOptions"
          :key="opt.value"
          :value="opt.value"
        >
          {{ opt.label }}
        </option>
      </select>
      <div
        class="pointer-events-none absolute top-3 text-gray-400"
        :class="$i18n.locale === 'ar' ? 'left-3.5' : 'right-3.5'"
      >
        <ChevronDown class="h-4 w-4" />
      </div>
    </div>

    <!-- 5. Boolean Toggle / Switch -->
    <div v-else-if="['boolean', 'switch'].includes(field.type)" class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
      <div>
        <label class="block text-xs font-bold text-gray-900 dark:text-white">{{ field.label }}</label>
        <span v-if="field.help_text" class="text-[11px] text-gray-400">{{ field.help_text }}</span>
      </div>
      <label class="relative inline-flex items-center cursor-pointer">
        <input
          type="checkbox"
          :checked="!!modelValue"
          @change="$emit('update:modelValue', $event.target.checked)"
          class="sr-only peer"
        />
        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#00C896]"></div>
      </label>
    </div>

    <!-- 6. Date / Datetime -->
    <div v-else-if="['date', 'datetime'].includes(field.type)" class="relative">
      <input
        :type="field.type === 'datetime' ? 'datetime-local' : 'date'"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-4 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-colors"
      />
    </div>

    <!-- Validation Error Feedback -->
    <p v-if="error" class="mt-1 text-[11px] font-bold text-rose-500">
      {{ error }}
    </p>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object],
    default: null,
  },
  error: {
    type: String,
    default: null,
  },
  optionsList: {
    type: Array,
    default: () => [],
  },
});

defineEmits(['update:modelValue']);

const colClass = computed(() => {
  const col = props.field.col || 12;
  return `col-span-12 sm:col-span-${col}`;
});

const computedOptions = computed(() => {
  if (props.optionsList && props.optionsList.length > 0) {
    return props.optionsList;
  }
  return props.field.options || [];
});
</script>
