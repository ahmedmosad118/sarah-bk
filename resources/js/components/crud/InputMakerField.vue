<template>
  <div :class="[colClass, $i18n.locale === 'ar' ? 'text-right' : 'text-left']" class="mb-3.5">
    <div class="flex items-center justify-between mb-1.5">
      <label v-if="field.type !== 'boolean'" class="block text-xs font-bold text-gray-700 dark:text-gray-300">
        {{ formatLabel(field.label) }}
        <span v-if="field.required" class="text-rose-500 font-bold">*</span>
      </label>
      <button
        v-if="field.name === 'customer_id' || field.allow_create"
        type="button"
        @click="$emit('field-action', field.name)"
        class="text-[11px] font-bold text-[#00A87E] hover:text-[#00C896] hover:underline dark:text-[#00C896] flex items-center gap-1 cursor-pointer transition-colors"
      >
        <Plus class="h-3 w-3" />
        <span>{{ $t('leads.addNewCustomer') }}</span>
      </button>
    </div>

    <!-- 1. Text / Email / Tel / Password -->
    <div v-if="['text', 'email', 'tel', 'password'].includes(field.type)" class="relative">
      <input
        :type="field.type"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="formatLabel(field.placeholder) || formatLabel(field.label)"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden transition-all dark:text-white"
        :class="error ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white focus:ring-2 focus:ring-[#00C896]/20 dark:border-gray-700 dark:bg-gray-900/40 dark:focus:border-[#00C896]'"
      />
    </div>

    <!-- 2. Textarea -->
    <div v-else-if="field.type === 'textarea'" class="relative">
      <textarea
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :placeholder="formatLabel(field.placeholder) || formatLabel(field.label)"
        :rows="3"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden transition-all dark:text-white"
        :class="error ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500 focus:border-rose-500 focus:ring-2 focus:ring-rose-500/20' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white focus:ring-2 focus:ring-[#00C896]/20 dark:border-gray-700 dark:bg-gray-900/40 dark:focus:border-[#00C896]'"
      ></textarea>
    </div>

    <!-- 3. Number / Currency / Percentage -->
    <div v-else-if="['number', 'currency', 'percentage'].includes(field.type)" class="flex items-stretch rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40 focus-within:border-[#00C896] focus-within:bg-white focus-within:ring-2 focus-within:ring-[#00C896]/20 transition-all overflow-hidden">
      <input
        type="number"
        :step="field.step || (field.type === 'currency' ? '0.01' : 'any')"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value === '' ? null : Number($event.target.value))"
        :placeholder="formatLabel(field.placeholder) || '0.00'"
        :required="field.required"
        :disabled="field.readonly"
        class="w-full bg-transparent py-2.5 px-3.5 text-xs font-bold font-mono text-gray-900 dark:text-white outline-hidden [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none text-left"
        dir="ltr"
      />
      <span
        v-if="field.type === 'currency' || field.name === 'estimated_value'"
        class="inline-flex items-center px-3 bg-gray-100 dark:bg-gray-800 border-s border-gray-200 dark:border-gray-700 text-xs font-black text-[#00A87E] dark:text-[#00C896] select-none whitespace-nowrap"
      >
        {{ $t('opportunities.currencyEGP') || 'ج.م' }}
      </span>
      <span
        v-else-if="field.type === 'percentage'"
        class="inline-flex items-center px-3 bg-gray-100 dark:bg-gray-800 border-s border-gray-200 dark:border-gray-700 text-xs font-black text-[#00A87E] dark:text-[#00C896] select-none whitespace-nowrap"
      >
        %
      </span>
    </div>

    <!-- 4. Select / Relation Dropdown (Select2 Searchable Component) -->
    <div v-else-if="['select', 'relation', 'searchable_select', 'enum', 'dropdown'].includes(field.type)" class="relative">
      <SearchableSelect
        :model-value="modelValue"
        :options="computedOptions"
        :placeholder="formatLabel(field.placeholder) || formatLabel(field.label)"
        :search-placeholder="$t('common.search')"
        :disabled="field.readonly"
        :clearable="!field.required"
        :allow-empty="!field.required"
        :action-label="field.name === 'customer_id' ? 'leads.addNewCustomer' : (field.action_label || '')"
        @action="$emit('field-action', field.name)"
        @update:model-value="$emit('update:modelValue', $event)"
      />
    </div>

    <!-- 5. Boolean Toggle / Switch -->
    <div v-else-if="['boolean', 'switch'].includes(field.type)" class="flex items-center justify-between p-3 rounded-xl border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/40">
      <div>
        <label class="block text-xs font-bold text-gray-900 dark:text-white">{{ formatLabel(field.label) }}</label>
        <span v-if="field.help_text" class="text-[11px] text-gray-400">{{ formatLabel(field.help_text) }}</span>
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
        class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white focus:ring-2 focus:ring-[#00C896]/20 dark:border-gray-700 dark:bg-gray-900/40 dark:text-white dark:focus:border-[#00C896] transition-all"
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
import { useI18n } from 'vue-i18n';
import { Plus } from 'lucide-vue-next';
import SearchableSelect from '../common/SearchableSelect.vue';

const props = defineProps({
  field: {
    type: Object,
    required: true,
  },
  modelValue: {
    type: [String, Number, Boolean, Array, Object, null],
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

defineEmits(['update:modelValue', 'field-action']);

const { t, te } = useI18n();

const formatLabel = (key) => {
  if (!key) return '';
  return te(key) ? t(key) : key;
};

const colClass = computed(() => {
  const col = Number(props.field.col) || 12;
  const colSpanMap = {
    1: 'col-span-12 sm:col-span-1',
    2: 'col-span-12 sm:col-span-2',
    3: 'col-span-12 sm:col-span-3',
    4: 'col-span-12 sm:col-span-4',
    5: 'col-span-12 sm:col-span-5',
    6: 'col-span-12 sm:col-span-6',
    7: 'col-span-12 sm:col-span-7',
    8: 'col-span-12 sm:col-span-8',
    9: 'col-span-12 sm:col-span-9',
    10: 'col-span-12 sm:col-span-10',
    11: 'col-span-12 sm:col-span-11',
    12: 'col-span-12 sm:col-span-12',
  };
  return colSpanMap[col] || 'col-span-12 sm:col-span-12';
});

const computedOptions = computed(() => {
  if (props.optionsList && props.optionsList.length > 0) {
    return props.optionsList;
  }
  return props.field.options || [];
});
</script>
