<template>
  <div class="space-y-6">
    <CrudIndex
      ref="crudRef"
      endpoint="/leads"
      :title="$t('leads.title')"
      :custom-columns="columns"
      :extra-params="filterParams"
      :related-options="relatedOptions"
      @field-action="onFieldAction"
      @loaded="onDataLoaded"
    >
      <!-- Top Stats Overview Cards -->
      <template #top-stats>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-5 sm:gap-4">
          <!-- Total Leads -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-[#00C896]/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('leads.totalLeads') }}</p>
                <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.total || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00C896] dark:bg-[#00C896]/20">
                <Sparkles class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- New Leads -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('leads.newLeads') }}</p>
                <h3 class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ stats.new || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <Clock class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Contacted -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-amber-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('leads.contactedLeads') }}</p>
                <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ stats.contacted || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <PhoneCall class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Qualified -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('leads.qualifiedLeads') }}</p>
                <h3 class="mt-1 text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ stats.qualified || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <CheckCircle2 class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Converted -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-emerald-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('leads.convertedLeads') }}</p>
                <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ stats.converted || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <Award class="h-5 w-5" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Custom Segmented Filter Toolbars -->
      <template #filters>
        <div class="flex flex-wrap items-center gap-2">
          <!-- Status Filters -->
          <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
            <button
              type="button"
              @click="setStatusFilter('')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="!filterParams.status ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('common.all') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('New')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'New' ? 'bg-white text-blue-600 shadow-xs dark:bg-gray-800 dark:text-blue-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('leads.filterNew') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('Contacted')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Contacted' ? 'bg-white text-amber-600 shadow-xs dark:bg-gray-800 dark:text-amber-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('leads.filterContacted') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('Qualified')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Qualified' ? 'bg-white text-purple-600 shadow-xs dark:bg-gray-800 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('leads.filterQualified') }}</span>
            </button>
            <button
              type="button"
              @click="setStatusFilter('Converted')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Converted' ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-800 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('leads.filterConverted') }}</span>
            </button>
          </div>

          <!-- Reset Filter Button -->
          <button
            v-if="filterParams.status || filterParams.source || filterParams.customer_id"
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

      <!-- Custom Lead Title & Customer Column -->
      <template #col-title="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]"
          >
            <Building2 v-if="item.customer?.customer_type === 'company'" class="h-4 w-4 text-purple-600 dark:text-purple-400" />
            <User v-else class="h-4 w-4" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900 dark:text-white hover:text-[#00C896] cursor-pointer" @click="openLeadDetails(item)">
                {{ item.title }}
              </span>
            </div>
            <p v-if="item.customer" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1">
              <span class="text-gray-700 dark:text-gray-300 font-bold">{{ item.customer.name }}</span>
              <span v-if="item.customer.company_name" class="text-gray-400">({{ item.customer.company_name }})</span>
            </p>
          </div>
        </div>
      </template>

      <!-- Custom Source Column -->
      <template #col-source="{ value }">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-bold"
          :class="getSourceClass(value)"
        >
          {{ getSourceLabel(value) }}
        </span>
      </template>

      <!-- Custom Status Column -->
      <template #col-status="{ value }">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold"
          :class="getStatusClass(value)"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="getStatusDotClass(value)"></span>
          {{ getStatusLabel(value) }}
        </span>
      </template>

      <!-- Custom Estimated Value Column -->
      <template #col-estimated_value="{ value }">
        <span v-if="value" class="font-mono text-xs font-bold text-gray-900 dark:text-white">
          {{ Number(value).toLocaleString() }} <span class="text-[10px] text-[#00C896] font-bold">{{ $t('leads.currencyEGP') }}</span>
        </span>
        <span v-else class="text-xs text-gray-400">—</span>
      </template>

      <!-- Custom Assigned User Column -->
      <template #col-assigned_to="{ item }">
        <div v-if="item.assigned_user" class="flex items-center gap-1.5">
          <div class="h-6 w-6 rounded-full bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-700 dark:text-gray-300">
            {{ item.assigned_user.name.charAt(0) }}
          </div>
          <span class="text-xs font-semibold text-gray-700 dark:text-gray-300">{{ item.assigned_user.name }}</span>
        </div>
        <span v-else class="text-xs text-gray-400 italic">—</span>
      </template>

      <!-- Custom Actions Column -->
      <template #actions="{ item }">
        <div class="flex items-center justify-center gap-1">
          <!-- View Details -->
          <button
            type="button"
            @click="openLeadDetails(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-[#00C896] hover:bg-[#00C896]/10 dark:hover:bg-[#00C896]/20 transition-colors cursor-pointer"
            :title="$t('leads.leadDetails')"
          >
            <Eye class="h-3.5 w-3.5" />
          </button>

          <!-- Convert to Client / Converted Action -->
          <button
            v-if="item.status !== 'Converted'"
            type="button"
            @click="convertLead(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400 transition-colors cursor-pointer"
            :title="$t('leads.convertAction')"
          >
            <Award class="h-3.5 w-3.5" />
          </button>

          <!-- Edit -->
          <button
            type="button"
            @click="crudRef?.openEditModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors cursor-pointer"
            :title="$t('common.edit')"
          >
            <Pencil class="h-3.5 w-3.5" />
          </button>

          <!-- Delete -->
          <button
            type="button"
            @click="crudRef?.confirmDelete(item.id)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-900/30 dark:hover:text-rose-400 transition-colors cursor-pointer"
            :title="$t('common.delete')"
          >
            <Trash2 class="h-3.5 w-3.5" />
          </button>
        </div>
      </template>
    </CrudIndex>

    <!-- 1. Quick Customer Creation Modal -->
    <CrudModal
      :show="showQuickCustomerModal"
      :title="$t('leads.quickAddCustomerTitle')"
      :loading="quickCustomerLoading"
      @close="closeQuickCustomerModal"
      @save="saveQuickCustomer"
    >
      <div class="space-y-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ $t('leads.quickAddCustomerDesc') }}
        </p>

        <!-- Customer Type Radio/Pills -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('customers.customerType') }} <span class="text-rose-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="quickCustomerForm.customer_type = 'individual'"
              class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
              :class="quickCustomerForm.customer_type === 'individual' ? 'border-[#00C896] bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
            >
              <User class="h-4 w-4" />
              <span>{{ $t('customers.individual') }}</span>
            </button>
            <button
              type="button"
              @click="quickCustomerForm.customer_type = 'company'"
              class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
              :class="quickCustomerForm.customer_type === 'company' ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
            >
              <Building2 class="h-4 w-4" />
              <span>{{ $t('customers.company') }}</span>
            </button>
          </div>
        </div>

        <!-- Name & Company Name -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.name') }} <span class="text-rose-500">*</span>
            </label>
            <input
              type="text"
              v-model="quickCustomerForm.name"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
            <p v-if="quickCustomerErrors.name" class="mt-1 text-[11px] text-rose-500 font-bold">
              {{ quickCustomerErrors.name[0] }}
            </p>
          </div>

          <div v-if="quickCustomerForm.customer_type === 'company'">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.companyName') }}
            </label>
            <input
              type="text"
              v-model="quickCustomerForm.company_name"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
          </div>
        </div>

        <!-- Phone & WhatsApp -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.phone') }}
            </label>
            <input
              type="tel"
              v-model="quickCustomerForm.phone"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
              dir="ltr"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.whatsapp') }}
            </label>
            <input
              type="tel"
              v-model="quickCustomerForm.whatsapp"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
              dir="ltr"
            />
          </div>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('customers.email') }}
          </label>
          <input
            type="email"
            v-model="quickCustomerForm.email"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            dir="ltr"
          />
        </div>
      </div>
    </CrudModal>

    <!-- 2. Lead View / Details & Progressive Qualification Modal -->
    <CrudModal
      :show="showDetailsModal"
      :title="$t('leads.leadDetails')"
      :show-save-button="false"
      @close="showDetailsModal = false"
    >
      <div v-if="selectedLead" class="space-y-6">
        <!-- Lead Header & Pipeline Progression -->
        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h3 class="text-base font-black text-gray-900 dark:text-white">{{ selectedLead.title }}</h3>
              <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $t('leads.source') }}: <span class="font-bold text-gray-700 dark:text-gray-200">{{ getSourceLabel(selectedLead.source) }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                :class="getStatusClass(selectedLead.status)"
              >
                <span class="h-2 w-2 rounded-full" :class="getStatusDotClass(selectedLead.status)"></span>
                {{ getStatusLabel(selectedLead.status) }}
              </span>
            </div>
          </div>

          <!-- Interactive Pipeline Stage Stepper -->
          <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
            <p class="text-[11px] font-bold text-gray-400 mb-1.5">{{ $t('leads.pipelineProgress') }}</p>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5 text-xs font-bold">
              <button
                v-for="st in ['New', 'Contacted', 'Qualified', 'Converted', 'Unqualified', 'Lost']"
                :key="st"
                type="button"
                @click="qualificationForm.status = st"
                class="py-1.5 px-2 rounded-xl text-center text-[11px] transition-all cursor-pointer border"
                :class="qualificationForm.status === st ? 'border-[#00C896] bg-[#00C896] text-white shadow-xs' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
              >
                {{ getStatusLabel(st) }}
              </button>
            </div>
          </div>
        </div>

        <!-- Client Profile Card -->
        <div class="p-4 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-3 bg-white dark:bg-gray-900">
          <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-1.5">
            <User class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ $t('leads.clientInfo') }}</span>
          </h4>

          <div v-if="selectedLead.customer" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('customers.name') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedLead.customer.name }}</p>
            </div>
            <div v-if="selectedLead.customer.company_name">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.companyName') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedLead.customer.company_name }}</p>
            </div>
            <div v-if="selectedLead.customer.phone" class="flex items-center gap-2">
              <div>
                <p class="text-gray-400 text-[11px]">{{ $t('customers.phone') }}</p>
                <p class="font-mono font-bold text-gray-900 dark:text-white" dir="ltr">{{ selectedLead.customer.phone }}</p>
              </div>
              <a
                :href="`tel:${selectedLead.customer.phone}`"
                class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
                title="اتصال"
              >
                <Phone class="h-3.5 w-3.5" />
              </a>
              <a
                v-if="selectedLead.customer.whatsapp || selectedLead.customer.phone"
                :href="`https://wa.me/${(selectedLead.customer.whatsapp || selectedLead.customer.phone).replace(/[^0-9]/g, '')}`"
                target="_blank"
                class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400"
                title="واتساب"
              >
                <MessageSquare class="h-3.5 w-3.5" />
              </a>
            </div>
            <div v-if="selectedLead.customer.email">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.email') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">{{ selectedLead.customer.email }}</p>
            </div>
          </div>
        </div>

        <!-- Description (تفاصيل الطلب) -->
        <div v-if="selectedLead.description" class="space-y-1">
          <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('leads.description') }}</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 whitespace-pre-line">
            {{ selectedLead.description }}
          </p>
        </div>

        <!-- Progressive Commercial Qualification Card -->
        <div class="p-4 rounded-2xl border border-[#00C896]/30 bg-[#00C896]/5 dark:bg-[#00C896]/10 space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <Sparkles class="h-4 w-4 text-[#00A87E] dark:text-[#00C896]" />
              <h4 class="text-xs font-black text-gray-900 dark:text-white">{{ $t('leads.qualificationSection') }}</h4>
            </div>
            <span class="text-[10px] font-bold text-gray-400 hidden sm:inline">{{ $t('leads.qualificationDesc') }}</span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <!-- Estimated Value -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('leads.estimatedValue') }}
              </label>
              <div class="relative">
                <input
                  type="number"
                  step="0.01"
                  v-model="qualificationForm.estimated_value"
                  placeholder="0.00"
                  class="w-full rounded-xl border border-gray-200 bg-white py-2 px-3 text-xs font-bold font-mono text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                  dir="ltr"
                />
                <span class="absolute top-2 left-3 text-[10px] font-bold text-[#00C896]">{{ $t('leads.currencyEGP') }}</span>
              </div>
            </div>

            <!-- Expected Start Date -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('leads.expectedStartDate') }}
              </label>
              <input
                type="date"
                v-model="qualificationForm.expected_start_date"
                class="w-full rounded-xl border border-gray-200 bg-white py-2 px-3 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              />
            </div>

            <!-- Assigned Specialist -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('leads.assignedTo') }}
              </label>
              <SearchableSelect
                :model-value="qualificationForm.assigned_to"
                :options="userOptions"
                :placeholder="$t('leads.selectAssignee')"
                :clearable="true"
                @update:model-value="qualificationForm.assigned_to = $event"
              />
            </div>
          </div>

          <!-- Internal Notes & Qualification Notes -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('leads.notes') }}
            </label>
            <textarea
              v-model="qualificationForm.notes"
              rows="2"
              class="w-full rounded-xl border border-gray-200 bg-white py-2 px-3 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              placeholder="سجل ملاحظات مكالمة التأهيل، تفاصيل المقايسة المبدئية، متطلبات التشطيب..."
            ></textarea>
          </div>

          <!-- Save Qualification Button -->
          <div class="flex justify-end">
            <button
              type="button"
              @click="saveQualification"
              :disabled="qualificationLoading"
              class="flex items-center gap-1.5 px-4 py-2 rounded-xl bg-[#00C896] text-white hover:bg-[#00A87E] text-xs font-black shadow-xs transition-all cursor-pointer disabled:opacity-50"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              <span>{{ qualificationLoading ? $t('common.loading') : $t('leads.saveQualification') }}</span>
            </button>
          </div>
        </div>
      </div>
    </CrudModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import CrudIndex from '../../components/crud/CrudIndex.vue';
import CrudModal from '../../components/crud/CrudModal.vue';
import SearchableSelect from '../../components/common/SearchableSelect.vue';
import {
  Sparkles,
  Clock,
  PhoneCall,
  CheckCircle2,
  Award,
  RotateCcw,
  Building2,
  User,
  Users,
  UserPlus,
  Eye,
  Pencil,
  Trash2,
  Phone,
  MessageSquare,
} from 'lucide-vue-next';

const { t } = useI18n();
const notify = useNotificationStore();
const crudRef = ref(null);

const stats = ref({
  total: 0,
  new: 0,
  contacted: 0,
  qualified: 0,
  converted: 0,
  lost: 0,
  total_estimated_value: 0,
});

const filterParams = reactive({
  status: '',
  source: '',
  customer_id: '',
  assigned_to: '',
});

const customerOptions = ref([]);
const userOptions = ref([]);

const relatedOptions = computed(() => ({
  customer_id: customerOptions.value,
  assigned_to: userOptions.value,
}));

const columns = computed(() => [
  { name: 'title', label: t('leads.leadTitle') },
  { name: 'source', label: t('leads.source') },
  { name: 'status', label: t('common.status') },
  { name: 'estimated_value', label: t('leads.estimatedValue') },
  { name: 'assigned_to', label: t('leads.assignedTo') },
]);

// Lead View Details & Qualification Modal State
const showDetailsModal = ref(false);
const selectedLead = ref(null);
const qualificationLoading = ref(false);
const qualificationForm = reactive({
  status: 'New',
  estimated_value: null,
  expected_start_date: null,
  assigned_to: null,
  notes: '',
});

// Quick Customer Creation Modal State
const showQuickCustomerModal = ref(false);
const quickCustomerLoading = ref(false);
const quickCustomerErrors = ref({});
const quickCustomerForm = reactive({
  customer_type: 'individual',
  name: '',
  company_name: '',
  phone: '',
  whatsapp: '',
  email: '',
  status: 'active',
});

const loadDropdownOptions = async () => {
  try {
    const [customersRes, usersRes] = await Promise.all([
      api.get('/customers/all'),
      api.get('/users?per_page=100'),
    ]);

    if (customersRes.data?.data) {
      customerOptions.value = customersRes.data.data.map((c) => ({
        value: c.id,
        label: c.company_name ? `${c.name} (${c.company_name})` : c.name,
      }));
    }

    if (usersRes.data?.data) {
      userOptions.value = usersRes.data.data.map((u) => ({
        value: u.id,
        label: u.name,
      }));
    }
  } catch (error) {
    console.error('Failed to load related select options:', error);
  }
};

onMounted(() => {
  loadDropdownOptions();
});

const onDataLoaded = (payload) => {
  if (payload && payload.stats) {
    stats.value = payload.stats;
  }
};

const setStatusFilter = (status) => {
  filterParams.status = status;
  crudRef.value?.loadData(1);
};

const resetFilters = () => {
  filterParams.status = '';
  filterParams.source = '';
  filterParams.customer_id = '';
  filterParams.assigned_to = '';
  crudRef.value?.loadData(1);
};

const openLeadDetails = (lead) => {
  selectedLead.value = lead;
  qualificationForm.status = lead.status || 'New';
  qualificationForm.estimated_value = lead.estimated_value;
  qualificationForm.expected_start_date = lead.expected_start_date;
  qualificationForm.assigned_to = lead.assigned_to;
  qualificationForm.notes = lead.notes || '';
  showDetailsModal.value = true;
};

const saveQualification = async () => {
  if (!selectedLead.value) return;
  qualificationLoading.value = true;
  try {
    const res = await api.post(`/leads/${selectedLead.value.id}/qualify`, qualificationForm);
    if (res.data?.success) {
      notify.success(t('leads.qualificationSuccess'));
      selectedLead.value = res.data.data;
      crudRef.value?.loadData();
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'Error updating qualification');
  } finally {
    qualificationLoading.value = false;
  }
};

const convertLead = async (lead) => {
  const confirmed = await alertService.confirmAction({
    title: t('leads.convertAction'),
    text: t('leads.convertConfirm'),
    confirmButtonText: t('leads.convertAction'),
    cancelButtonText: t('common.cancel'),
    icon: 'question',
  });

  if (!confirmed) {
    return;
  }

  try {
    const res = await api.post(`/leads/${lead.id}/convert`);
    if (res.data?.success) {
      notify.success(t('leads.convertSuccess'));
      crudRef.value?.loadData();
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'Failed to convert lead');
  }
};

const onFieldAction = (fieldName) => {
  if (fieldName === 'customer_id') {
    openQuickCustomerModal();
  }
};

// Quick Customer Modal Handlers
const openQuickCustomerModal = () => {
  quickCustomerForm.customer_type = 'individual';
  quickCustomerForm.name = '';
  quickCustomerForm.company_name = '';
  quickCustomerForm.phone = '';
  quickCustomerForm.whatsapp = '';
  quickCustomerForm.email = '';
  quickCustomerErrors.value = {};
  showQuickCustomerModal.value = true;
};

const closeQuickCustomerModal = () => {
  showQuickCustomerModal.value = false;
};

const saveQuickCustomer = async () => {
  quickCustomerLoading.value = true;
  quickCustomerErrors.value = {};

  try {
    const res = await api.post('/customers', quickCustomerForm);
    if (res.data?.success && res.data?.data) {
      const newCustomer = res.data.data;
      notify.success(t('customers.savedSuccessfully'));

      // Add to options list
      const optionLabel = newCustomer.company_name
        ? `${newCustomer.name} (${newCustomer.company_name})`
        : newCustomer.name;

      customerOptions.value.unshift({
        value: newCustomer.id,
        label: optionLabel,
      });

      // Auto-select in current Lead form
      if (crudRef.value?.formData) {
        crudRef.value.formData.customer_id = newCustomer.id;
      }

      showQuickCustomerModal.value = false;
    }
  } catch (err) {
    if (err.response?.status === 422 && err.response.data?.errors) {
      quickCustomerErrors.value = err.response.data.errors;
    } else {
      notify.error(err.response?.data?.message || 'Error saving customer');
    }
  } finally {
    quickCustomerLoading.value = false;
  }
};

// Source & Status UI Helpers
const getSourceLabel = (src) => {
  const map = {
    Facebook: t('leads.sourceFacebook'),
    Instagram: t('leads.sourceInstagram'),
    Google: t('leads.sourceGoogle'),
    Website: t('leads.sourceWebsite'),
    WhatsApp: t('leads.sourceWhatsApp'),
    Referral: t('leads.sourceReferral'),
    Phone: t('leads.sourcePhone'),
    'Walk-in': t('leads.sourceWalkIn'),
    Other: t('leads.sourceOther'),
  };
  return map[src] || src;
};

const getSourceClass = (src) => {
  const map = {
    Facebook: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    Instagram: 'bg-pink-50 text-pink-700 dark:bg-pink-900/30 dark:text-pink-300',
    Google: 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300',
    Website: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    WhatsApp: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    Referral: 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    Phone: 'bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
    'Walk-in': 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    Other: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
  };
  return map[src] || map.Other;
};

const getStatusLabel = (status) => {
  const map = {
    New: t('leads.statusNew'),
    Contacted: t('leads.statusContacted'),
    Qualified: t('leads.statusQualified'),
    Unqualified: t('leads.statusUnqualified'),
    Converted: t('leads.statusConverted'),
    Lost: t('leads.statusLost'),
  };
  return map[status] || status;
};

const getStatusClass = (status) => {
  const map = {
    New: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    Contacted: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    Qualified: 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    Unqualified: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    Converted: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    Lost: 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
  };
  return map[status] || map.New;
};

const getStatusDotClass = (status) => {
  const map = {
    New: 'bg-blue-500',
    Contacted: 'bg-amber-500',
    Qualified: 'bg-purple-500',
    Unqualified: 'bg-gray-400',
    Converted: 'bg-emerald-500',
    Lost: 'bg-rose-500',
  };
  return map[status] || map.New;
};
</script>
