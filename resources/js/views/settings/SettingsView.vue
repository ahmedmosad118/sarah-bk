<template>
  <div class="space-y-6">
    <BreadcrumbDefault :pageTitle="$t('settings.title')" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Tenant Environment Info Card -->
      <div
        class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800 h-fit"
        :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
      >
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Building2 class="h-4 w-4 text-blue-600" />
          <span>{{ $t('settings.environmentData') }}</span>
        </h3>

        <div class="space-y-3 text-xs">
          <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40">
            <span class="text-gray-400 block text-[11px]">{{ $t('settings.companyRegisteredName') }}</span>
            <span class="font-bold text-gray-900 dark:text-white">{{ tenant?.name }}</span>
          </div>

          <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40">
            <span class="text-gray-400 block text-[11px]">{{ $t('settings.companyCode') }}</span>
            <span class="font-mono font-bold text-blue-600 dark:text-blue-400">{{ tenant?.company_code }}</span>
          </div>

          <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40">
            <span class="text-gray-400 block text-[11px]">{{ $t('settings.tenantSlug') }}</span>
            <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ tenant?.slug }}</span>
          </div>

          <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-900/40">
            <span class="text-gray-400 block text-[11px]">{{ $t('settings.isolatedDb') }}</span>
            <span class="font-mono font-bold text-purple-600 dark:text-purple-400">{{ tenant?.database_name || 'sarh_tenant_' + tenant?.slug }}</span>
          </div>
        </div>
      </div>

      <!-- Settings Form -->
      <div
        class="lg:col-span-2 rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800"
        :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
      >
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
          <Settings class="h-4 w-4 text-slate-600" />
          <span>{{ $t('settings.generalConfig') }}</span>
        </h3>

        <form @submit.prevent="saveSettings" class="space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.legalName') }}
              </label>
              <input
                type="text"
                v-model="form.company_legal_name"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.currency') }}
              </label>
              <input
                type="text"
                v-model="form.currency"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.currencySymbol') }}
              </label>
              <input
                type="text"
                v-model="form.currency_symbol"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.timezone') }}
              </label>
              <input
                type="text"
                v-model="form.timezone"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900 text-left"
                dir="ltr"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.vatRate') }}
              </label>
              <input
                type="number"
                step="0.1"
                v-model="form.vat_rate"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900 text-left"
                dir="ltr"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('settings.workDays') }}
              </label>
              <input
                type="number"
                v-model="form.work_days_per_week"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-medium dark:border-gray-700 dark:bg-gray-900 text-left"
                dir="ltr"
              />
            </div>
          </div>

          <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="flex items-center gap-2 rounded-xl bg-blue-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-blue-700 disabled:opacity-50 transition-colors shadow-sm"
            >
              <span v-if="saving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
              <span>{{ $t('settings.saveSettings') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { Building2, Settings } from 'lucide-vue-next';
import api from '../../services/api';
import { useNotificationStore } from '../../stores/notification';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';

const { t } = useI18n();
const notificationStore = useNotificationStore();
const tenant = ref(null);
const saving = ref(false);

const form = ref({
  company_legal_name: 'شركة صرح للمقاولات العامة',
  currency: 'EGP',
  currency_symbol: 'ج.م',
  timezone: 'Africa/Cairo',
  vat_rate: '14',
  work_days_per_week: '6',
});

const loadSettings = async () => {
  try {
    const res = await api.get('/settings');
    tenant.value = res.data.tenant;
    const settingsGrouped = res.data.data;

    Object.values(settingsGrouped).flat().forEach((s) => {
      if (form.value[s.key] !== undefined) {
        form.value[s.key] = s.value;
      }
    });
  } catch (err) {
    // Ignore
  }
};

const saveSettings = async () => {
  saving.value = true;
  try {
    const settingsArray = Object.entries(form.value).map(([key, value]) => ({
      key,
      value,
    }));

    await api.post('/settings', { settings: settingsArray });
    notificationStore.success(t('settings.savedSuccess'));
  } catch (err) {
    notificationStore.error(err.response?.data?.message || 'Error saving settings.');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  loadSettings();
});
</script>
