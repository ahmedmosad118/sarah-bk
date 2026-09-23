<template>
  <CrudModal
    :show="show"
    :title="$t('leads.quickAddCustomerTitle') || 'إضافة عميل جديد سريعاً'"
    :loading="loading"
    :save-text="'حفظ وتحديد العميل'"
    @close="handleClose"
    @save="submitCustomer"
  >
    <div class="space-y-4">
      <p class="text-xs text-gray-500 dark:text-gray-400">
        {{ $t('leads.quickAddCustomerDesc') || 'قم بإنشاء العميل وسيتم اختياره وتحديده تلقائياً للنموذج الحالي فور الحفظ.' }}
      </p>

      <!-- 1. Customer Type Selector -->
      <div>
        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
          {{ $t('customers.customerType') || 'نوع العميل' }} <span class="text-rose-500">*</span>
        </label>
        <div class="grid grid-cols-2 gap-2">
          <button
            type="button"
            @click="form.customer_type = 'individual'"
            class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
            :class="form.customer_type === 'individual' ? 'border-[#00C896] bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
          >
            <User class="h-4 w-4" />
            <span>{{ $t('customers.individual') || 'عميل فرد' }}</span>
          </button>
          <button
            type="button"
            @click="form.customer_type = 'company'"
            class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
            :class="form.customer_type === 'company' ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
          >
            <Building2 class="h-4 w-4" />
            <span>{{ $t('customers.company') || 'شركة / منشأة' }}</span>
          </button>
        </div>
      </div>

      <!-- 2. Customer Name & Company Name -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div :class="form.customer_type === 'company' ? '' : 'sm:col-span-2'">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ form.customer_type === 'company' ? 'اسم جهة الاتصال / المسؤول' : ($t('customers.name') || 'اسم العميل') }}
            <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input
              type="text"
              v-model="form.name"
              @input="onFieldInput('name')"
              @blur="validateField('name')"
              required
              placeholder="مثال: م. أحمد مصطفى"
              class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden transition-all dark:text-white"
              :class="errors.name ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40'"
            />
          </div>
          <p v-if="errors.name" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ errors.name }}
          </p>
        </div>

        <div v-if="form.customer_type === 'company'">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('customers.companyName') || 'اسم الشركة / المؤسسة' }} <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input
              type="text"
              v-model="form.company_name"
              @input="onFieldInput('company_name')"
              @blur="validateField('company_name')"
              placeholder="مثال: شركة التطوير والإنشاءات"
              class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden transition-all dark:text-white"
              :class="errors.company_name ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40'"
            />
          </div>
          <p v-if="errors.company_name" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ errors.company_name }}
          </p>
        </div>
      </div>

      <!-- 3. Phone & WhatsApp with Sync Button -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <!-- Phone -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
              {{ $t('customers.phone') || 'رقم الهاتف' }}
            </label>
            <span class="text-[10px] text-gray-400 font-medium">11 رقم أو دولي (+20)</span>
          </div>
          <div class="relative">
            <input
              type="tel"
              v-model="form.phone"
              @input="onFieldInput('phone')"
              @blur="validateField('phone')"
              placeholder="01012345678 أو +201012345678"
              class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-mono font-medium text-gray-900 outline-hidden transition-all dark:text-white"
              :class="errors.phone ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40'"
              dir="ltr"
            />
          </div>
          <p v-if="errors.phone" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ errors.phone }}
          </p>
        </div>

        <!-- WhatsApp -->
        <div>
          <div class="flex items-center justify-between mb-1.5">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
              {{ $t('customers.whatsapp') || 'رقم الواتساب' }}
            </label>
            <button
              v-if="form.phone && form.phone !== form.whatsapp"
              type="button"
              @click="copyPhoneToWhatsapp"
              class="text-[10px] font-bold text-[#00A87E] hover:underline dark:text-[#00C896] cursor-pointer"
            >
              نفس رقم الهاتف
            </button>
          </div>
          <div class="relative">
            <input
              type="tel"
              v-model="form.whatsapp"
              @input="onFieldInput('whatsapp')"
              @blur="validateField('whatsapp')"
              placeholder="01012345678 أو +201012345678"
              class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-mono font-medium text-gray-900 outline-hidden transition-all dark:text-white"
              :class="errors.whatsapp ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40'"
              dir="ltr"
            />
          </div>
          <p v-if="errors.whatsapp" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ errors.whatsapp }}
          </p>
        </div>
      </div>

      <!-- 4. Email -->
      <div>
        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
          {{ $t('customers.email') || 'البريد الإلكتروني' }}
        </label>
        <div class="relative">
          <input
            type="email"
            v-model="form.email"
            @input="onFieldInput('email')"
            @blur="validateField('email')"
            placeholder="client@example.com"
            class="w-full rounded-xl border py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden transition-all dark:text-white"
            :class="errors.email ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 bg-gray-50 focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40'"
            dir="ltr"
          />
        </div>
        <p v-if="errors.email" class="mt-1 text-[11px] text-rose-500 font-bold">
          {{ errors.email }}
        </p>
      </div>
    </div>
  </CrudModal>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { User, Building2 } from 'lucide-vue-next';
import api from '../../services/api';
import { useNotificationStore } from '../../stores/notification';
import CrudModal from '../crud/CrudModal.vue';
import { validatePhone, validateEmail, validateRequired } from '../../utils/validators';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  defaultType: {
    type: String,
    default: 'individual',
  },
});

const emit = defineEmits(['close', 'customer-created']);

const { t } = useI18n();
const notify = useNotificationStore();

const loading = ref(false);
const errors = reactive({
  name: '',
  company_name: '',
  phone: '',
  whatsapp: '',
  email: '',
});

const form = reactive({
  customer_type: 'individual',
  name: '',
  company_name: '',
  phone: '',
  whatsapp: '',
  email: '',
  status: 'active',
});

watch(
  () => props.show,
  (val) => {
    if (val) {
      resetForm();
    }
  }
);

const resetForm = () => {
  form.customer_type = props.defaultType || 'individual';
  form.name = '';
  form.company_name = '';
  form.phone = '';
  form.whatsapp = '';
  form.email = '';
  form.status = 'active';
  clearErrors();
};

const clearErrors = () => {
  errors.name = '';
  errors.company_name = '';
  errors.phone = '';
  errors.whatsapp = '';
  errors.email = '';
};

const copyPhoneToWhatsapp = () => {
  form.whatsapp = form.phone;
  validateField('whatsapp');
};

const onFieldInput = (fieldName) => {
  if (errors[fieldName]) {
    validateField(fieldName);
  }
};

const validateField = (fieldName) => {
  if (fieldName === 'name') {
    const res = validateRequired(form.name, { minLength: 2, label: 'اسم العميل' });
    errors.name = res.isValid ? '' : res.message;
  } else if (fieldName === 'company_name') {
    if (form.customer_type === 'company') {
      const res = validateRequired(form.company_name, { minLength: 2, label: 'اسم الشركة' });
      errors.company_name = res.isValid ? '' : res.message;
    } else {
      errors.company_name = '';
    }
  } else if (fieldName === 'phone') {
    const res = validatePhone(form.phone, { required: false, label: 'رقم الهاتف' });
    errors.phone = res.isValid ? '' : res.message;
  } else if (fieldName === 'whatsapp') {
    const res = validatePhone(form.whatsapp, { required: false, label: 'رقم الواتساب' });
    errors.whatsapp = res.isValid ? '' : res.message;
  } else if (fieldName === 'email') {
    const res = validateEmail(form.email, { required: false, label: 'البريد الإلكتروني' });
    errors.email = res.isValid ? '' : res.message;
  }
};

const validateAll = () => {
  validateField('name');
  validateField('company_name');
  validateField('phone');
  validateField('whatsapp');
  validateField('email');

  return !errors.name && !errors.company_name && !errors.phone && !errors.whatsapp && !errors.email;
};

const handleClose = () => {
  emit('close');
};

const submitCustomer = async () => {
  if (!validateAll()) {
    notify.error('يرجى تصحيح الأخطاء في النموذج قبل الحفظ.');
    return;
  }

  loading.value = true;
  try {
    const payload = {
      customer_type: form.customer_type,
      name: form.name.trim(),
      company_name: form.customer_type === 'company' ? (form.company_name?.trim() || null) : null,
      phone: form.phone?.toString().trim() || null,
      whatsapp: form.whatsapp?.toString().trim() || null,
      email: form.email?.toString().trim() || null,
      status: 'active',
    };

    const res = await api.post('/customers', payload);
    if (res.data?.success && res.data?.data) {
      const createdCustomer = res.data.data;
      notify.success(t('customers.savedSuccessfully') || 'تم حفظ وتحديد العميل بنجاح');
      emit('customer-created', createdCustomer);
      emit('close');
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      const apiErrors = err.response.data.errors;
      if (apiErrors.name) errors.name = apiErrors.name[0];
      if (apiErrors.company_name) errors.company_name = apiErrors.company_name[0];
      if (apiErrors.phone) errors.phone = apiErrors.phone[0];
      if (apiErrors.whatsapp) errors.whatsapp = apiErrors.whatsapp[0];
      if (apiErrors.email) errors.email = apiErrors.email[0];
      notify.error('يرجى التأكد من صحة البيانات المدخلة.');
    } else {
      notify.error(err.response?.data?.message || 'حدث خطأ أثناء حفظ العميل');
    }
  } finally {
    loading.value = false;
  }
};
</script>
