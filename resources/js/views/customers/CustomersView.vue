<template>
  <div class="space-y-6">
    <CrudIndex
      ref="crudRef"
      endpoint="/customers"
      :title="$t('customers.title')"
      :custom-columns="columns"
      :extra-params="filterParams"
      @loaded="onDataLoaded"
    >
      <!-- Top Stats Overview Cards -->
      <template #top-stats>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-4">
          <!-- Total Customers -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-[#00C896]/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('customers.totalCustomers') }}</p>
                <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.total || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00C896] dark:bg-[#00C896]/20">
                <Users class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Active Customers -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-emerald-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('customers.activeCustomers') }}</p>
                <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ stats.active || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <UserCheck class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Individuals -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('customers.individualCount') }}</p>
                <h3 class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ stats.individuals || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <User class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Corporate / Companies -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('customers.companyCount') }}</p>
                <h3 class="mt-1 text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ stats.companies || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <Building2 class="h-5 w-5" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Filter Toolbar Injection (Segmented Controls) -->
      <template #filters>
        <div class="flex flex-wrap items-center gap-2">
          <!-- Segmented Customer Type Filter Pills -->
          <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
            <button
              type="button"
              @click="setTypeFilter('')"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="!filterParams.customer_type ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <Users class="h-3.5 w-3.5" />
              <span>{{ $t('customers.filterAll') }}</span>
            </button>
            <button
              type="button"
              @click="setTypeFilter('individual')"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.customer_type === 'individual' ? 'bg-white text-blue-600 shadow-xs dark:bg-gray-800 dark:text-blue-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <User class="h-3.5 w-3.5" />
              <span>{{ $t('customers.filterIndividuals') }}</span>
            </button>
            <button
              type="button"
              @click="setTypeFilter('company')"
              class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.customer_type === 'company' ? 'bg-white text-purple-600 shadow-xs dark:bg-gray-800 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <Building2 class="h-3.5 w-3.5" />
              <span>{{ $t('customers.filterCompanies') }}</span>
            </button>
          </div>

          <!-- Status Filter Pills -->
          <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
            <button
              type="button"
              @click="setStatusFilter('')"
              class="flex items-center gap-1 px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="!filterParams.status ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('common.all') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('active')"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'active' ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-800 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
              <span>{{ $t('common.active') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('inactive')"
              class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'inactive' ? 'bg-white text-rose-600 shadow-xs dark:bg-gray-800 dark:text-rose-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
              <span>{{ $t('common.inactive') }}</span>
            </button>
          </div>

          <!-- Reset Filter Button -->
          <button
            v-if="filterParams.customer_type || filterParams.status"
            type="button"
            @click="resetFilters"
            class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-rose-400 text-xs font-bold transition-all cursor-pointer"
            title="Reset Filters"
          >
            <RotateCcw class="h-3 w-3" />
            <span>{{ $t('common.cancel') }}</span>
          </button>
        </div>
      </template>

      <!-- Custom Customer Name & Enterprise Column -->
      <template #col-name="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold"
            :class="item.customer_type === 'company' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]'"
          >
            <Building2 v-if="item.customer_type === 'company'" class="h-4 w-4" />
            <User v-else class="h-4 w-4" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900 dark:text-white">{{ item.name }}</span>
            </div>
            <p v-if="item.customer_type === 'company' && item.company_name" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400">
              {{ item.company_name }}
            </p>
            <p v-else-if="item.email" class="text-[11px] font-mono text-gray-400">
              {{ item.email }}
            </p>
          </div>
        </div>
      </template>

      <!-- Custom Customer Type Badge -->
      <template #col-customer_type="{ value }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-bold"
          :class="value === 'company' ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
        >
          <Building2 v-if="value === 'company'" class="h-3 w-3" />
          <User v-else class="h-3 w-3" />
          {{ value === 'company' ? $t('customers.company') : $t('customers.individual') }}
        </span>
      </template>

      <!-- Custom Contact & Phone Column -->
      <template #col-phone="{ item }">
        <div class="flex items-center gap-2">
          <span v-if="item.phone" class="font-mono text-xs font-semibold text-gray-700 dark:text-gray-300" dir="ltr">
            {{ item.phone }}
          </span>
          <span v-else class="text-xs text-gray-400">—</span>

          <!-- Direct WhatsApp Link -->
          <a
            v-if="item.whatsapp || item.phone"
            :href="`https://wa.me/${(item.whatsapp || item.phone).replace(/[^0-9]/g, '')}`"
            target="_blank"
            rel="noopener noreferrer"
            class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400"
            :title="$t('customers.whatsappChat')"
          >
            <MessageSquare class="h-3.5 w-3.5" />
          </a>

          <!-- Direct Phone Link -->
          <a
            v-if="item.phone"
            :href="`tel:${item.phone}`"
            class="flex h-6 w-6 items-center justify-center rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
            :title="$t('customers.callNow')"
          >
            <Phone class="h-3.5 w-3.5" />
          </a>
        </div>
      </template>

      <!-- Custom Status Column -->
      <template #col-status="{ value }">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold"
          :class="value === 'active' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="value === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
          {{ value === 'active' ? $t('common.active') : $t('common.inactive') }}
        </span>
      </template>
    </CrudIndex>
  </div>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import CrudIndex from '../../components/crud/CrudIndex.vue';
import {
  Users,
  UserCheck,
  User,
  Building2,
  Phone,
  MessageSquare,
  RotateCcw,
} from 'lucide-vue-next';

const { t } = useI18n();
const crudRef = ref(null);
const stats = ref({
  total: 0,
  active: 0,
  inactive: 0,
  individuals: 0,
  companies: 0,
});

const filterParams = reactive({
  customer_type: '',
  status: '',
});

const columns = computed(() => [
  { name: 'name', label: t('customers.name') },
  { name: 'customer_type', label: t('customers.customerType') },
  { name: 'phone', label: t('customers.phone') },
  { name: 'address', label: t('customers.address') },
  { name: 'status', label: t('customers.status') },
]);

const onDataLoaded = (payload) => {
  if (payload && payload.stats) {
    stats.value = payload.stats;
  }
};

const setTypeFilter = (type) => {
  filterParams.customer_type = type;
  crudRef.value?.loadData(1);
};

const setStatusFilter = (status) => {
  filterParams.status = status;
  crudRef.value?.loadData(1);
};

const resetFilters = () => {
  filterParams.customer_type = '';
  filterParams.status = '';
  crudRef.value?.loadData(1);
};
</script>
