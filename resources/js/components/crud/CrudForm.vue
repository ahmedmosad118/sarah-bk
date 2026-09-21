<template>
  <form @submit.prevent="$emit('submit')" class="space-y-4">
    <div class="grid grid-cols-12 gap-4">
      <InputMakerField
        v-for="field in visibleFields"
        :key="field.name"
        :field="field"
        :model-value="formData[field.name]"
        :error="errors[field.name]?.[0]"
        :options-list="relatedOptions[field.name]"
        @update:model-value="updateField(field.name, $event)"
      />
    </div>

    <slot name="extra-fields" />
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
