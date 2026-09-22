<template>
  <form @submit.prevent="$emit('submit')">
    <div class="grid grid-cols-12 gap-x-4 gap-y-1">
      <InputMakerField
        v-for="field in visibleFields"
        :key="field.name"
        :field="field"
        :model-value="formData[field.name]"
        :error="errors[field.name]?.[0]"
        :options-list="relatedOptions[field.name]"
        @update:model-value="updateField(field.name, $event)"
      />

      <div class="col-span-12">
        <slot name="extra-fields" />
      </div>
    </div>
  </form>
</template>

<script setup>
import { computed } from 'vue';
import InputMakerField from './InputMakerField.vue';

const props = defineProps({
  schema: {
    type: Object,
    required: true,
  },
  formData: {
    type: Object,
    required: true,
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
  relatedOptions: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['submit', 'update:formData']);

const visibleFields = computed(() => {
  return (props.schema?.fields || []).filter((f) => !f.hidden_in_form && f.type !== 'hidden');
});

const updateField = (name, value) => {
  props.formData[name] = value;
  emit('update:formData', { ...props.formData });
};
</script>
