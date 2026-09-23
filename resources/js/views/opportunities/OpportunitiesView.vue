<template>
  <div class="space-y-6">
    <CrudIndex
      ref="crudRef"
      endpoint="/opportunities"
      :title="$t('opportunities.title')"
      :custom-columns="columns"
      :extra-params="filterParams"
      :related-options="relatedOptions"
      @field-action="onFieldAction"
      @loaded="onDataLoaded"
    >
      <!-- Top Stats Overview Cards -->
      <template #top-stats>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-5 sm:gap-4">
          <!-- Total Opportunities -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-[#00C896]/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('opportunities.totalOpportunities') }}</p>
                <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.total || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00C896] dark:bg-[#00C896]/20">
                <Target class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- New & Qualified -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('opportunities.newOpportunities') }}</p>
                <h3 class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ (stats.new || 0) + (stats.qualified || 0) }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <Clock class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Proposal & Negotiation -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-amber-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('opportunities.proposalOpportunities') }}</p>
                <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ (stats.proposal || 0) + (stats.negotiation || 0) }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <FileSpreadsheet class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Won Deals -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-emerald-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('opportunities.wonOpportunities') }}</p>
                <h3 class="mt-1 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ stats.won || 0 }}</h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <Award class="h-5 w-5" />
              </div>
            </div>
          </div>

          <!-- Pipeline Value -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/30">
            <div class="flex items-center justify-between">
              <div>
                <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('opportunities.totalEstimatedValue') }}</p>
                <h3 class="mt-1 text-lg font-black text-purple-600 dark:text-purple-400 font-mono">
                  {{ formatMoney(stats.total_estimated_value) }} <span class="text-[10px] font-bold text-gray-400">{{ $t('opportunities.currencyEGP') }}</span>
                </h3>
              </div>
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <TrendingUp class="h-5 w-5" />
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Segmented Stage Filter Toolbar -->
      <template #filters>
        <div class="flex flex-wrap items-center gap-2">
          <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
            <button
              type="button"
              @click="setStageFilter('')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="!filterParams.stage ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('common.all') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('New')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'New' ? 'bg-white text-blue-600 shadow-xs dark:bg-gray-800 dark:text-blue-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterNew') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('Qualified')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'Qualified' ? 'bg-white text-purple-600 shadow-xs dark:bg-gray-800 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterQualified') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('Proposal')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'Proposal' ? 'bg-white text-amber-600 shadow-xs dark:bg-gray-800 dark:text-amber-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterProposal') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('Negotiation')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'Negotiation' ? 'bg-white text-orange-600 shadow-xs dark:bg-gray-800 dark:text-orange-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterNegotiation') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('Won')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'Won' ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-800 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterWon') }}</span>
            </button>
            <button
              type="button"
              @click="setStageFilter('Lost')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.stage === 'Lost' ? 'bg-white text-rose-600 shadow-xs dark:bg-gray-800 dark:text-rose-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('opportunities.filterLost') }}</span>
            </button>
          </div>

          <!-- Reset Filters -->
          <button
            v-if="filterParams.stage || filterParams.customer_id || filterParams.assigned_to || filterParams.lead_id"
            type="button"
            @click="resetFilters"
            class="flex items-center gap-1 px-2.5 py-1.5 rounded-xl border border-gray-200 bg-white text-gray-500 hover:text-rose-600 hover:bg-rose-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-rose-400 text-xs font-bold transition-all cursor-pointer"
          >
            <RotateCcw class="h-3 w-3" />
            <span>{{ $t('common.cancel') }}</span>
          </button>
        </div>
      </template>

      <!-- Custom Opportunity Title & Customer Column -->
      <template #col-title="{ item }">
        <div class="flex items-center gap-3">
          <div
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl font-bold bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]"
          >
            <Target class="h-4 w-4" />
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-gray-900 dark:text-white hover:text-[#00C896] cursor-pointer" @click="openOpportunityDetails(item)">
                {{ item.title }}
              </span>
              <span v-if="item.lead" class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-300 text-[10px] font-bold" title="محولة من عميل محتمل">
                <Sparkles class="h-2.5 w-2.5" />
                <span>{{ $t('opportunities.lead') }}</span>
              </span>
            </div>
            <p v-if="item.customer" class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 flex items-center gap-1 mt-0.5">
              <span class="text-gray-700 dark:text-gray-300 font-bold">{{ item.customer.name }}</span>
              <span v-if="item.customer.company_name" class="text-gray-400">({{ item.customer.company_name }})</span>
            </p>
          </div>
        </div>
      </template>

      <!-- Custom Stage Column -->
      <template #col-stage="{ value }">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold"
          :class="getStageClass(value)"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="getStageDotClass(value)"></span>
          {{ getStageLabel(value) }}
        </span>
      </template>

      <!-- Custom Estimated Value Column -->
      <template #col-estimated_value="{ value }">
        <span v-if="value" class="font-mono text-xs font-bold text-gray-900 dark:text-white">
          {{ Number(value).toLocaleString() }} <span class="text-[10px] text-[#00C896] font-bold">{{ $t('opportunities.currencyEGP') }}</span>
        </span>
        <span v-else class="text-xs text-gray-400">—</span>
      </template>

      <!-- Custom Expected Close Date Column -->
      <template #col-expected_close_date="{ value }">
        <span v-if="value" class="text-xs font-medium text-gray-700 dark:text-gray-300 font-mono">
          {{ formatDate(value) }}
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
            @click="openOpportunityDetails(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-[#00C896] hover:bg-[#00C896]/10 dark:hover:bg-[#00C896]/20 transition-colors cursor-pointer"
            :title="$t('opportunities.opportunityDetails')"
          >
            <Eye class="h-3.5 w-3.5" />
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
      @close="showQuickCustomerModal = false"
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

    <!-- 2. Opportunity Details & Stage Progression Modal -->
    <CrudModal
      :show="showDetailsModal"
      :title="$t('opportunities.opportunityDetails')"
      :show-save-button="false"
      @close="showDetailsModal = false"
    >
      <div v-if="selectedOpportunity" class="space-y-6">
        <!-- Opportunity Header & Stage Progression -->
        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <h3 class="text-base font-black text-gray-900 dark:text-white">{{ selectedOpportunity.title }}</h3>
              <p v-if="selectedOpportunity.customer" class="text-xs font-semibold text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $t('opportunities.customer') }}: <span class="font-bold text-gray-700 dark:text-gray-200">{{ selectedOpportunity.customer.name }}</span>
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                :class="getStageClass(selectedOpportunity.stage)"
              >
                <span class="h-2 w-2 rounded-full" :class="getStageDotClass(selectedOpportunity.stage)"></span>
                {{ getStageLabel(selectedOpportunity.stage) }}
              </span>
            </div>
          </div>

          <!-- Interactive Stage Stepper -->
          <div class="pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
            <p class="text-[11px] font-bold text-gray-400 mb-1.5">{{ $t('opportunities.pipelineProgress') }}</p>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-1.5 text-xs font-bold">
              <button
                v-for="st in ['New', 'Qualified', 'Proposal', 'Negotiation', 'Won', 'Lost']"
                :key="st"
                type="button"
                @click="updateOpportunityStage(st)"
                class="py-1.5 px-2 rounded-xl text-center text-[11px] transition-all cursor-pointer border"
                :class="selectedOpportunity.stage === st ? 'border-[#00C896] bg-[#00C896] text-white shadow-xs' : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
              >
                {{ getStageLabel(st) }}
              </button>
            </div>
          </div>
        </div>

        <!-- Client Profile Card -->
        <div class="p-4 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-3 bg-white dark:bg-gray-900">
          <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-1.5">
            <User class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ $t('opportunities.clientInfo') }}</span>
          </h4>

          <div v-if="selectedOpportunity.customer" class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('customers.name') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedOpportunity.customer.name }}</p>
            </div>
            <div v-if="selectedOpportunity.customer.company_name">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.companyName') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedOpportunity.customer.company_name }}</p>
            </div>
            <div v-if="selectedOpportunity.customer.phone" class="flex items-center gap-2">
              <div>
                <p class="text-gray-400 text-[11px]">{{ $t('customers.phone') }}</p>
                <p class="font-mono font-bold text-gray-900 dark:text-white" dir="ltr">{{ selectedOpportunity.customer.phone }}</p>
              </div>
              <a
                :href="`tel:${selectedOpportunity.customer.phone}`"
                class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
                title="اتصال"
              >
                <Phone class="h-3.5 w-3.5" />
              </a>
              <a
                v-if="selectedOpportunity.customer.whatsapp || selectedOpportunity.customer.phone"
                :href="`https://wa.me/${(selectedOpportunity.customer.whatsapp || selectedOpportunity.customer.phone).replace(/[^0-9]/g, '')}`"
                target="_blank"
                class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400"
                title="واتساب"
              >
                <MessageSquare class="h-3.5 w-3.5" />
              </a>
            </div>
            <div v-if="selectedOpportunity.customer.email">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.email') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">{{ selectedOpportunity.customer.email }}</p>
            </div>
          </div>
        </div>

        <!-- Next Logical Action: Site Visit -->
        <div class="p-4 rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/50 dark:bg-blue-950/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-blue-600 text-white shadow-xs">
              <MapPin class="h-5 w-5" />
            </div>
            <div>
              <span class="text-[10px] font-black uppercase tracking-wider text-blue-600 dark:text-blue-400 block">الخطوة التالية في دورة العمل</span>
              <h4 class="text-xs font-black text-gray-900 dark:text-white">إضافة وتحديد موعد المعاينة الميدانية (Site Visit)</h4>
              <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">رفع المقاسات الميدانية ومعاينة الموقع لتحديد نطاق الأعمال وجدول الكميات.</p>
            </div>
          </div>
          <div class="shrink-0">
            <router-link
              :to="`/site-visits?opportunity_id=${selectedOpportunity.id}`"
              class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
            >
              <MapPin class="h-3.5 w-3.5" />
              <span>حجز / عرض المعاينات</span>
            </router-link>
          </div>
        </div>

        <!-- Originating Lead Card -->
        <div v-if="selectedOpportunity.lead" class="p-4 rounded-2xl border border-purple-200/70 dark:border-purple-900/40 bg-purple-50/40 dark:bg-purple-950/20 space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black text-purple-900 dark:text-purple-300 flex items-center gap-1.5">
              <Sparkles class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
              <span>{{ $t('opportunities.leadOriginInfo') }}</span>
            </h4>
            <span class="text-[11px] font-bold text-purple-600 dark:text-purple-400">
              #{{ selectedOpportunity.lead.id }}
            </span>
          </div>
          <div class="text-xs text-gray-700 dark:text-gray-300 font-semibold">
            {{ selectedOpportunity.lead.title }}
          </div>
          <p v-if="selectedOpportunity.lead.description" class="text-[11px] text-gray-500 dark:text-gray-400">
            {{ selectedOpportunity.lead.description }}
          </p>
        </div>

        <!-- Commercial Specifications Card -->
        <div class="p-4 rounded-2xl border border-[#00C896]/30 bg-[#00C896]/5 dark:bg-[#00C896]/10 space-y-3">
          <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
            <TrendingUp class="h-3.5 w-3.5 text-[#00A87E] dark:text-[#00C896]" />
            <span>{{ $t('opportunities.opportunityInfo') }}</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('opportunities.estimatedValue') }}</p>
              <p class="font-mono font-black text-sm text-gray-900 dark:text-white">
                {{ selectedOpportunity.estimated_value ? Number(selectedOpportunity.estimated_value).toLocaleString() : '—' }}
                <span class="text-[10px] text-[#00C896] font-bold">{{ $t('opportunities.currencyEGP') }}</span>
              </p>
            </div>

            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('opportunities.expectedStartDate') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">
                {{ formatDate(selectedOpportunity.expected_start_date) || '—' }}
              </p>
            </div>

            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('opportunities.expectedCloseDate') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">
                {{ formatDate(selectedOpportunity.expected_close_date) || '—' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Loss Analysis Card (When opportunity is marked as Lost) -->
        <div v-if="selectedOpportunity.stage === 'Lost' && selectedOpportunity.loss_reason" class="p-4 rounded-2xl bg-rose-50/70 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-900/40 space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black text-rose-800 dark:text-rose-300 flex items-center gap-1.5">
              <AlertCircle class="h-4 w-4 text-rose-600 dark:text-rose-400" />
              <span>{{ $t('opportunities.lossInfoTitle') }}</span>
            </h4>
            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-100 dark:bg-rose-900/40 text-[11px] font-bold text-rose-700 dark:text-rose-300">
              {{ getLossReasonLabel(selectedOpportunity.loss_reason) }}
            </span>
          </div>
          <div v-if="selectedOpportunity.competitor_name" class="text-xs text-rose-900 dark:text-rose-200">
            <span class="font-bold">{{ $t('opportunities.competitorName') }}:</span> {{ selectedOpportunity.competitor_name }}
          </div>
          <p v-if="selectedOpportunity.loss_notes" class="text-xs text-gray-700 dark:text-gray-300 bg-white/70 dark:bg-gray-900/40 p-2.5 rounded-xl border border-rose-100 dark:border-rose-900/30 whitespace-pre-line">
            {{ selectedOpportunity.loss_notes }}
          </p>
        </div>

        <!-- Description & Notes -->
        <div v-if="selectedOpportunity.description" class="space-y-1">
          <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('opportunities.description') }}</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 whitespace-pre-line">
            {{ selectedOpportunity.description }}
          </p>
        </div>

        <div v-if="selectedOpportunity.notes" class="space-y-1">
          <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('opportunities.notes') }}</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 whitespace-pre-line">
            {{ selectedOpportunity.notes }}
          </p>
        </div>
      </div>
    </CrudModal>

    <!-- 3. Record Loss Reason Modal for Opportunity -->
    <CrudModal
      :show="showLossModal"
      :title="$t('opportunities.lossInfoTitle')"
      :loading="lossFormLoading"
      @close="showLossModal = false"
      @save="submitLossReason"
    >
      <div class="space-y-4">
        <div class="p-3 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 text-xs text-rose-800 dark:text-rose-300 flex items-center gap-2">
          <AlertCircle class="h-4 w-4 shrink-0 text-rose-600 dark:text-rose-400" />
          <span>يرجى تسجيل سبب خسارة الفرصة البيعية لتحليل مؤشرات الأداء والتقارير التنفيذية.</span>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('opportunities.lossReason') }} <span class="text-rose-500">*</span>
          </label>
          <select
            v-model="lossForm.loss_reason"
            class="w-full rounded-xl border border-rose-200 bg-white py-2.5 px-3.5 text-xs font-semibold text-gray-900 outline-hidden focus:border-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          >
            <option value="">{{ $t('opportunities.lossReasonSelect') }}</option>
            <option value="price_high">{{ $t('opportunities.lossReasonPrice') }}</option>
            <option value="competitor_won">{{ $t('opportunities.lossReasonCompetitor') }}</option>
            <option value="client_postponed">{{ $t('opportunities.lossReasonPostponed') }}</option>
            <option value="client_unresponsive">{{ $t('opportunities.lossReasonUnresponsive') }}</option>
            <option value="scope_mismatch">{{ $t('opportunities.lossReasonOutOfScope') }}</option>
            <option value="budget_insufficient">{{ $t('opportunities.lossReasonBudget') }}</option>
            <option value="other">{{ $t('opportunities.lossReasonOther') }}</option>
          </select>
        </div>

        <div v-if="lossForm.loss_reason === 'competitor_won'">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('opportunities.competitorName') }}
          </label>
          <input
            type="text"
            v-model="lossForm.competitor_name"
            :placeholder="$t('opportunities.competitorNamePlaceholder')"
            class="w-full rounded-xl border border-rose-200 bg-white py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('opportunities.lossNotes') }}
          </label>
          <textarea
            v-model="lossForm.loss_notes"
            rows="3"
            class="w-full rounded-xl border border-rose-200 bg-white py-2.5 px-3.5 text-xs text-gray-900 outline-hidden focus:border-rose-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            :placeholder="$t('opportunities.lossNotesPlaceholder')"
          ></textarea>
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
import { formatDate } from '../../utils/date';
import CrudIndex from '../../components/crud/CrudIndex.vue';
import CrudModal from '../../components/crud/CrudModal.vue';
import {
  Target,
  Clock,
  FileSpreadsheet,
  Award,
  TrendingUp,
  RotateCcw,
  Building2,
  User,
  Eye,
  Pencil,
  Trash2,
  Phone,
  MessageSquare,
  Sparkles,
  AlertCircle,
} from 'lucide-vue-next';

const { t } = useI18n();
const notify = useNotificationStore();
const crudRef = ref(null);

const stats = ref({
  total: 0,
  new: 0,
  qualified: 0,
  proposal: 0,
  negotiation: 0,
  won: 0,
  lost: 0,
  total_estimated_value: 0,
  won_estimated_value: 0,
});

const filterParams = reactive({
  stage: '',
  customer_id: '',
  assigned_to: '',
  lead_id: '',
});

const customerOptions = ref([]);
const userOptions = ref([]);
const leadOptions = ref([]);

const relatedOptions = computed(() => ({
  customer_id: customerOptions.value,
  assigned_to: userOptions.value,
  lead_id: leadOptions.value,
}));

const columns = computed(() => [
  { name: 'title', label: t('opportunities.opportunityTitle') },
  { name: 'stage', label: t('opportunities.stage') },
  { name: 'estimated_value', label: t('opportunities.estimatedValue') },
  { name: 'expected_close_date', label: t('opportunities.expectedCloseDate') },
  { name: 'assigned_to', label: t('opportunities.assignedTo') },
]);

// Details Modal State
const showDetailsModal = ref(false);
const selectedOpportunity = ref(null);

// Loss Reason Modal State
const showLossModal = ref(false);
const lossFormLoading = ref(false);
const lossForm = reactive({
  loss_reason: '',
  competitor_name: '',
  loss_notes: '',
});

// Quick Customer Modal State
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
    const [customersRes, usersRes, leadsRes] = await Promise.all([
      api.get('/customers/all'),
      api.get('/users?per_page=100'),
      api.get('/leads?per_page=100'),
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

    if (leadsRes.data?.data) {
      leadOptions.value = leadsRes.data.data.map((l) => ({
        value: l.id,
        label: `#${l.id} - ${l.title}`,
      }));
    }
  } catch (error) {
    console.error('Failed to load related select options for opportunities:', error);
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

const setStageFilter = (stage) => {
  filterParams.stage = stage;
  crudRef.value?.loadData(1);
};

const resetFilters = () => {
  filterParams.stage = '';
  filterParams.customer_id = '';
  filterParams.assigned_to = '';
  filterParams.lead_id = '';
  crudRef.value?.loadData(1);
};

const openOpportunityDetails = (opp) => {
  selectedOpportunity.value = opp;
  showDetailsModal.value = true;
};

const updateOpportunityStage = (newStage) => {
  if (!selectedOpportunity.value) return;
  if (newStage === 'Lost') {
    lossForm.loss_reason = selectedOpportunity.value.loss_reason || '';
    lossForm.competitor_name = selectedOpportunity.value.competitor_name || '';
    lossForm.loss_notes = selectedOpportunity.value.loss_notes || '';
    showLossModal.value = true;
    return;
  }
  executeStageChange(newStage, {});
};

const submitLossReason = async () => {
  if (!selectedOpportunity.value) return;
  lossFormLoading.value = true;
  await executeStageChange('Lost', {
    loss_reason: lossForm.loss_reason || null,
    competitor_name: lossForm.loss_reason === 'competitor_won' ? lossForm.competitor_name : null,
    loss_notes: lossForm.loss_notes || null,
  });
  lossFormLoading.value = false;
  showLossModal.value = false;
};

const executeStageChange = async (newStage, extraData = {}) => {
  try {
    const res = await api.post(`/opportunities/${selectedOpportunity.value.id}/stage`, {
      stage: newStage,
      ...extraData,
    });
    if (res.data?.success) {
      notify.success(t('opportunities.stageSuccess') || 'تم تحديث مرحلة الفرصة بنجاح');
      selectedOpportunity.value = res.data.data;
      crudRef.value?.loadData();
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'Error updating stage');
  }
};

const onFieldAction = (fieldName) => {
  if (fieldName === 'customer_id') {
    openQuickCustomerModal();
  }
};

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

const saveQuickCustomer = async () => {
  quickCustomerLoading.value = true;
  quickCustomerErrors.value = {};

  try {
    const res = await api.post('/customers', quickCustomerForm);
    if (res.data?.success && res.data?.data) {
      const newCustomer = res.data.data;
      notify.success(t('customers.savedSuccessfully'));

      const optionLabel = newCustomer.company_name
        ? `${newCustomer.name} (${newCustomer.company_name})`
        : newCustomer.name;

      customerOptions.value.unshift({
        value: newCustomer.id,
        label: optionLabel,
      });

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

const formatMoney = (val) => {
  if (!val) return '0.00';
  return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const getStageLabel = (stage) => {
  const map = {
    New: t('opportunities.stageNew'),
    Qualified: t('opportunities.stageQualified'),
    Proposal: t('opportunities.stageProposal'),
    Negotiation: t('opportunities.stageNegotiation'),
    Won: t('opportunities.stageWon'),
    Lost: t('opportunities.stageLost'),
  };
  return map[stage] || stage;
};

const getStageClass = (stage) => {
  const map = {
    New: 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    Qualified: 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    Proposal: 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    Negotiation: 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
    Won: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    Lost: 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
  };
  return map[stage] || 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
};

const getStageDotClass = (stage) => {
  const map = {
    New: 'bg-blue-500',
    Qualified: 'bg-purple-500',
    Proposal: 'bg-amber-500',
    Negotiation: 'bg-orange-500',
    Won: 'bg-emerald-500',
    Lost: 'bg-rose-500',
  };
  return map[stage] || 'bg-gray-400';
};

const getLossReasonLabel = (reason) => {
  const map = {
    price_high: t('opportunities.lossReasonPrice'),
    competitor_won: t('opportunities.lossReasonCompetitor'),
    client_postponed: t('opportunities.lossReasonPostponed'),
    client_unresponsive: t('opportunities.lossReasonUnresponsive'),
    scope_mismatch: t('opportunities.lossReasonOutOfScope'),
    budget_insufficient: t('opportunities.lossReasonBudget'),
    other: t('opportunities.lossReasonOther'),
  };
  return map[reason] || reason;
};
</script>
