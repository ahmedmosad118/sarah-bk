<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
          <FileSignature class="h-6 w-6 text-[#00C896]" />
          <span>{{ $t('contract.title') }}</span>
        </h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ $t('contract.subtitle') }}
        </p>
      </div>

      <div class="flex items-center gap-2">
        <button
          v-if="canCreate"
          type="button"
          @click="openCreateModal(filterOpportunityId)"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs hover:shadow-md transition-all cursor-pointer"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('contract.createTitle') }}</span>
        </button>
      </div>
    </div>

    <!-- Opportunity Context Filter Banner (if present in query) -->
    <div
      v-if="filterOpportunityId"
      class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 p-3.5 rounded-2xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-200/60 dark:border-teal-800/40 text-xs"
    >
      <div class="flex items-center gap-2.5 text-teal-900 dark:text-teal-200 font-bold">
        <span class="w-2 h-2 rounded-full bg-[#00C896] animate-pulse"></span>
        <span>{{ $t('contract.filterByOpportunity') }}: #{{ filterOpportunityId }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          {{ $t('contract.clearOpportunityFilter') }}
        </button>
      </div>
    </div>

    <!-- Responsive KPI Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 lg:gap-4">
      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate">{{ $t('contract.totalContracts') }}</span>
          <Layers class="h-4 w-4 text-gray-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 truncate">{{ $t('contract.draftContracts') }}</span>
          <FileText class="h-4 w-4 text-slate-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-3.5 sm:p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300 truncate">{{ $t('contract.underReviewContracts') }}</span>
          <Clock class="h-4 w-4 text-amber-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-amber-800 dark:text-amber-200">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-3.5 sm:p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 truncate">{{ $t('contract.approvedContracts') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-emerald-800 dark:text-emerald-200">{{ stats.approved }}</p>
      </div>

      <div class="rounded-2xl border border-purple-100 bg-purple-50/40 p-3.5 sm:p-4 shadow-xs dark:border-purple-900/30 dark:bg-purple-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-purple-700 dark:text-purple-300 truncate">{{ $t('contract.signedContracts') }}</span>
          <ShieldCheck class="h-4 w-4 text-purple-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-purple-800 dark:text-purple-200">{{ stats.signed }}</p>
      </div>

      <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-3.5 sm:p-4 shadow-xs dark:border-blue-900/30 dark:bg-blue-950/20 col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-blue-700 dark:text-blue-300 truncate">{{ $t('contract.totalContractValue') }}</span>
          <Coins class="h-4 w-4 text-blue-500 shrink-0" />
        </div>
        <p class="mt-2 text-base sm:text-lg font-black text-blue-900 dark:text-blue-100 truncate">
          {{ formatCurrency(stats.total_contract_value) }}
        </p>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1">
          <Search class="absolute start-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
          <input
            v-model="filters.search"
            type="text"
            :placeholder="$t('contract.searchPlaceholder')"
            @input="debouncedFetch"
            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2.5 ps-10 pe-4 text-xs font-medium text-gray-900 outline-none transition-all placeholder:text-gray-400 focus:border-[#00C896] focus:bg-white dark:border-gray-800 dark:bg-gray-800/50 dark:text-white dark:focus:border-[#00C896]"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Workflow Status Filter -->
          <select
            v-model="filters.status"
            @change="fetchData"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 outline-none transition-all focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
          >
            <option value="">{{ $t('contract.allStatuses') }}</option>
            <option value="Draft">{{ $t('contract.statusDraft') }}</option>
            <option value="Under Review">{{ $t('contract.statusUnderReview') }}</option>
            <option value="Approved">{{ $t('contract.statusApproved') }}</option>
            <option value="Superseded">{{ $t('contract.statusSuperseded') }}</option>
          </select>

          <!-- Signature Status Filter -->
          <select
            v-model="filters.signature_status"
            @change="fetchData"
            class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 outline-none transition-all focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
          >
            <option value="">{{ $t('contract.allSignatureStatuses') }}</option>
            <option value="Not Sent">{{ $t('contract.sigStatusNotSent') }}</option>
            <option value="Sent for Signature">{{ $t('contract.sigStatusSent') }}</option>
            <option value="Signed">{{ $t('contract.sigStatusSigned') }}</option>
            <option value="Void">{{ $t('contract.sigStatusVoid') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Contracts Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
      <div class="overflow-x-auto">
        <table class="w-full text-start text-xs">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/40 text-gray-500 dark:text-gray-400 font-bold">
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colNumber') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colOpportunity') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colQuotation') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colValue') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colMilestones') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colDates') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colStatus') }}</th>
              <th class="px-4 py-3.5 text-start">{{ $t('contract.colSignatureStatus') }}</th>
              <th class="px-4 py-3.5 text-end">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="9" class="p-8 text-center text-xs text-gray-400">
                {{ $t('common.loading') }}
              </td>
            </tr>

            <tr v-else-if="contracts.length === 0">
              <td colspan="9" class="p-12 text-center">
                <FileSignature class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-2" />
                <p class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $t('contract.noContracts') }}</p>
                <p class="text-xs text-gray-400 mt-1">{{ $t('contract.noContractsDesc') }}</p>
              </td>
            </tr>

            <tr
              v-for="contract in contracts"
              :key="contract.id"
              class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition-colors"
            >
              <!-- Contract Number & Revision -->
              <td class="px-4 py-3 font-bold text-gray-900 dark:text-white whitespace-nowrap">
                <div class="flex items-center gap-2">
                  <span class="font-mono text-emerald-600 dark:text-emerald-400">{{ contract.contract_number }}</span>
                  <span class="px-1.5 py-0.5 rounded bg-gray-100 text-[10px] text-gray-600 font-bold dark:bg-gray-800 dark:text-gray-400">
                    V{{ contract.version }}
                  </span>
                </div>
              </td>

              <!-- Opportunity & Customer -->
              <td class="px-4 py-3">
                <p class="font-bold text-gray-900 dark:text-white line-clamp-1">
                  {{ contract.opportunity?.title || '—' }}
                </p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                  {{ contract.opportunity?.customer?.company_name || contract.opportunity?.customer?.name || '—' }}
                </p>
              </td>

              <!-- Source Quotation -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="flex items-center gap-1.5 font-mono text-[11px] text-teal-700 dark:text-teal-300">
                  <Receipt class="h-3.5 w-3.5 shrink-0 text-teal-500" />
                  <span>{{ contract.quotation?.quotation_number || '—' }}</span>
                </div>
              </td>

              <!-- Contract Final Value -->
              <td class="px-4 py-3 whitespace-nowrap">
                <div class="font-black text-gray-900 dark:text-white text-xs">
                  {{ formatCurrency(contract.contract_value) }}
                </div>
                <div v-if="contract.value_adjustment_reason" class="text-[10px] text-amber-600 dark:text-amber-400 flex items-center gap-1 mt-0.5">
                  <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                  <span class="truncate max-w-[120px]" :title="contract.value_adjustment_reason">{{ contract.value_adjustment_reason }}</span>
                </div>
              </td>

              <!-- Milestones -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg bg-slate-100 text-slate-700 text-[11px] font-bold dark:bg-slate-800 dark:text-slate-300">
                  <Coins class="h-3 w-3 text-slate-500" />
                  <span>{{ contract.milestones?.length || 0 }} {{ $t('contract.colMilestones') }}</span>
                </span>
              </td>

              <!-- Dates / Term -->
              <td class="px-4 py-3 whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-400">
                <div v-if="contract.start_date || contract.expected_completion_date">
                  <span>{{ contract.start_date || '—' }}</span>
                  <span class="mx-1 text-gray-400">→</span>
                  <span>{{ contract.expected_completion_date || '—' }}</span>
                </div>
                <div v-else>—</div>
              </td>

              <!-- Workflow Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                  :class="internalStatusBadgeClass(contract.status)"
                >
                  {{ contract.status }}
                </span>
              </td>

              <!-- Signature Status -->
              <td class="px-4 py-3 whitespace-nowrap">
                <span
                  class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold tracking-wider"
                  :class="signatureStatusBadgeClass(contract.signature_status)"
                >
                  {{ contract.signature_status }}
                </span>
              </td>

              <!-- Actions Dropdown -->
              <td class="px-4 py-3 text-end whitespace-nowrap">
                <RowActionDropdown :actions="getRowActions(contract)" />
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create Contract Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto"
    >
      <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-[#00C896]">
              <FileSignature class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('contract.createTitle') }}</h3>
              <p class="text-[11px] text-gray-500">{{ $t('contract.createSubtitle') }}</p>
            </div>
          </div>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitCreateContract" class="mt-4 space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Opportunity Select -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formOpportunity') }} <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="createForm.opportunity_id"
                @change="onOpportunityChanged"
                required
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              >
                <option value="" disabled>{{ $t('contract.selectOpportunity') }}</option>
                <option v-for="opp in opportunities" :key="opp.id" :value="opp.id">
                  #{{ opp.id }} - {{ opp.title }} ({{ opp.customer?.company_name || opp.customer?.name }})
                </option>
              </select>
            </div>

            <!-- Quotation Select -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formQuotation') }} <span class="text-rose-500">*</span>
              </label>
              <select
                v-model="createForm.quotation_id"
                @change="onQuotationChanged"
                :disabled="loadingQuotations || availableQuotations.length === 0"
                required
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] disabled:bg-gray-100 dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              >
                <option value="" disabled>{{ $t('contract.selectQuotation') }}</option>
                <option v-for="q in availableQuotations" :key="q.id" :value="q.id">
                  {{ q.quotation_number }} ({{ formatCurrency(q.grand_total) }}) - V{{ q.version }}
                </option>
              </select>
            </div>
          </div>

          <!-- Value and Adjustment Reason -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formValue') }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model.number="createForm.contract_value"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              />
            </div>

            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formAdjustmentReason') }}
                <span v-if="isValueAdjusted" class="text-rose-500 font-bold">*</span>
              </label>
              <input
                v-model="createForm.value_adjustment_reason"
                type="text"
                :required="isValueAdjusted"
                :placeholder="$t('contract.formAdjustmentReasonPlaceholder')"
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              />
            </div>
          </div>

          <!-- Dates -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formStartDate') }}
              </label>
              <input
                v-model="createForm.start_date"
                type="date"
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              />
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('contract.formExpectedCompletionDate') }}
              </label>
              <input
                v-model="createForm.expected_completion_date"
                type="date"
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
              />
            </div>
          </div>

          <!-- Terms and Notes -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('contract.formTermsAndConditions') }}
            </label>
            <textarea
              v-model="createForm.terms_and_conditions"
              rows="3"
              :placeholder="$t('contract.formTermsPlaceholder')"
              class="w-full rounded-xl border border-gray-200 bg-white p-3 text-xs font-medium text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-white"
            ></textarea>
          </div>

          <!-- Milestones Builder -->
          <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
            <div class="flex items-center justify-between mb-3">
              <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-2">
                <Coins class="h-4 w-4 text-[#00C896]" />
                <span>{{ $t('contract.milestonesTitle') }}</span>
              </h4>
              <div class="flex items-center gap-2">
                <select
                  v-model="createForm.milestone_type"
                  @change="onMilestoneTypeChanged"
                  class="rounded-lg border border-gray-200 bg-white px-2 py-1 text-[11px] font-bold text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                >
                  <option value="percentage">{{ $t('contract.milestoneTypePercentage') }}</option>
                  <option value="fixed">{{ $t('contract.milestoneTypeFixed') }}</option>
                </select>
                <button
                  type="button"
                  @click="addMilestone(createForm)"
                  class="px-2.5 py-1 rounded-lg bg-teal-50 text-[#00C896] hover:bg-teal-100 text-[11px] font-bold dark:bg-teal-950/40"
                >
                  {{ $t('contract.addMilestone') }}
                </button>
              </div>
            </div>

            <div class="space-y-2 max-h-48 overflow-y-auto pe-1">
              <div
                v-for="(m, idx) in createForm.milestones"
                :key="idx"
                class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-100 bg-gray-50/50 dark:border-gray-800 dark:bg-gray-800/40 text-xs"
              >
                <input
                  v-model="m.milestone_label"
                  type="text"
                  required
                  :placeholder="$t('contract.milestoneLabelPlaceholder')"
                  class="flex-1 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900"
                />

                <div v-if="createForm.milestone_type === 'percentage'" class="w-24">
                  <input
                    v-model.number="m.percentage"
                    type="number"
                    step="0.01"
                    min="0"
                    max="100"
                    required
                    :placeholder="$t('contract.milestonePercentage')"
                    class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs font-bold outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900"
                  />
                </div>

                <div v-else class="w-28">
                  <input
                    v-model.number="m.fixed_amount"
                    type="number"
                    step="0.01"
                    min="0"
                    required
                    :placeholder="$t('contract.milestoneFixedAmount')"
                    class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs font-bold outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900"
                  />
                </div>

                <button
                  type="button"
                  @click="removeMilestone(createForm, idx)"
                  class="text-gray-400 hover:text-rose-500 p-1"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>

            <!-- Milestones Sum Verification Pill -->
            <div class="mt-2 text-[11px] font-bold flex items-center justify-between text-gray-500">
              <span v-if="createForm.milestone_type === 'percentage'">
                {{ $t('contract.milestonePercentageSum', { sum: calculateMilestonesPercentageSum(createForm.milestones) }) }}
              </span>
              <span v-else>
                {{ $t('contract.milestoneFixedSum', { sum: formatCurrency(calculateMilestonesFixedSum(createForm.milestones)), expected: formatCurrency(createForm.contract_value) }) }}
              </span>
            </div>
          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving"
              class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs hover:shadow-md transition-all disabled:opacity-50"
            >
              {{ saving ? $t('common.loading') : $t('common.save') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Contract Details Modal -->
    <div
      v-if="showDetailsModal && activeContract"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-150 overflow-y-auto"
    >
      <div class="relative w-full max-w-3xl rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 my-8">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="p-2.5 rounded-xl bg-teal-50 dark:bg-teal-950/40 text-[#00C896]">
              <FileSignature class="h-6 w-6" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-base font-black text-gray-900 dark:text-white font-mono">{{ activeContract.contract_number }}</h3>
                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                  V{{ activeContract.version }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="internalStatusBadgeClass(activeContract.status)">
                  {{ activeContract.status }}
                </span>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold" :class="signatureStatusBadgeClass(activeContract.signature_status)">
                  {{ activeContract.signature_status }}
                </span>
              </div>
              <p class="text-xs text-gray-500 mt-0.5">{{ activeContract.opportunity?.title }} ({{ activeContract.opportunity?.customer?.company_name || activeContract.opportunity?.customer?.name }})</p>
            </div>
          </div>
          <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="mt-4 space-y-6 text-xs">
          <!-- Financial & Quotation Summary -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-xl bg-gray-50 dark:bg-gray-800/50">
            <div>
              <span class="text-gray-400 font-bold block">{{ $t('contract.colValue') }}</span>
              <span class="text-sm font-black text-gray-900 dark:text-white">{{ formatCurrency(activeContract.contract_value) }}</span>
            </div>
            <div>
              <span class="text-gray-400 font-bold block">{{ $t('contract.colQuotation') }}</span>
              <span class="font-mono text-teal-600 font-bold">{{ activeContract.quotation?.quotation_number }}</span>
            </div>
            <div>
              <span class="text-gray-400 font-bold block">{{ $t('contract.formStartDate') }}</span>
              <span class="font-bold text-gray-800 dark:text-gray-200">{{ activeContract.start_date || '—' }}</span>
            </div>
            <div>
              <span class="text-gray-400 font-bold block">{{ $t('contract.formExpectedCompletionDate') }}</span>
              <span class="font-bold text-gray-800 dark:text-gray-200">{{ activeContract.expected_completion_date || '—' }}</span>
            </div>
          </div>

          <div v-if="activeContract.value_adjustment_reason" class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/40 text-amber-900 dark:text-amber-200">
            <span class="font-bold block mb-1">{{ $t('contract.colAdjustmentReason') }}:</span>
            <p>{{ activeContract.value_adjustment_reason }}</p>
          </div>

          <!-- Milestones Schedule -->
          <div>
            <h4 class="font-black text-gray-900 dark:text-white mb-2 flex items-center gap-1.5">
              <Coins class="h-4 w-4 text-[#00C896]" />
              <span>{{ $t('contract.milestonesTitle') }}</span>
            </h4>
            <div class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
              <table class="w-full text-start">
                <thead class="bg-gray-50 dark:bg-gray-800/50 text-gray-500 font-bold">
                  <tr>
                    <th class="p-2.5 text-start">#</th>
                    <th class="p-2.5 text-start">{{ $t('contract.milestoneLabel') }}</th>
                    <th class="p-2.5 text-start">{{ $t('contract.milestonePercentage') }}</th>
                    <th class="p-2.5 text-start">{{ $t('contract.milestoneFixedAmount') }}</th>
                    <th class="p-2.5 text-start">{{ $t('contract.milestoneTrigger') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-for="(m, i) in activeContract.milestones" :key="m.id || i">
                    <td class="p-2.5 font-bold text-gray-400">{{ i + 1 }}</td>
                    <td class="p-2.5 font-bold text-gray-800 dark:text-gray-200">{{ m.milestone_label }}</td>
                    <td class="p-2.5 font-bold text-teal-600">{{ m.percentage ? `${m.percentage}%` : '—' }}</td>
                    <td class="p-2.5 font-bold text-gray-800 dark:text-gray-200">{{ m.fixed_amount ? formatCurrency(m.fixed_amount) : '—' }}</td>
                    <td class="p-2.5 text-gray-500">{{ m.trigger_description || '—' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Terms and Conditions -->
          <div v-if="activeContract.terms_and_conditions">
            <h4 class="font-black text-gray-900 dark:text-white mb-2">{{ $t('contract.formTermsAndConditions') }}</h4>
            <div class="p-3.5 rounded-xl border border-gray-200 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/40 text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
              {{ activeContract.terms_and_conditions }}
            </div>
          </div>

          <!-- Audit Sign-off Box -->
          <div class="border-t border-gray-100 dark:border-gray-800 pt-4">
            <h4 class="font-black text-gray-900 dark:text-white mb-2">{{ $t('contract.signAuditTitle') }}</h4>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-[11px]">
              <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40">
                <span class="text-gray-400 block">{{ $t('contract.preparedBy') }}</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ activeContract.prepared_user?.name || '—' }}</span>
              </div>
              <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40">
                <span class="text-gray-400 block">{{ $t('contract.reviewedBy') }}</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ activeContract.reviewed_user?.name || '—' }}</span>
              </div>
              <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40">
                <span class="text-gray-400 block">{{ $t('contract.approvedBy') }}</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ activeContract.approved_user?.name || '—' }}</span>
              </div>
              <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/40">
                <span class="text-gray-400 block">{{ $t('contract.signedBy') }}</span>
                <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ activeContract.signed_user?.name || $t('contract.notSignedYet') }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-800 mt-6">
          <button
            type="button"
            @click="printContract(activeContract)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300"
          >
            <Printer class="h-4 w-4" />
            <span>{{ $t('contract.printContract') }}</span>
          </button>
          <button
            type="button"
            @click="showDetailsModal = false"
            class="px-4 py-2 rounded-xl bg-gray-100 hover:bg-gray-200 text-xs font-bold text-gray-800 dark:bg-gray-800 dark:text-gray-200"
          >
            {{ $t('common.close') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Printable Legal Contract Document (Print-Only Area) -->
    <div v-if="printData" id="printable-contract" class="hidden print:block p-8 bg-white text-black font-sans">
      <div class="border-b-2 border-slate-900 pb-4 mb-6 flex justify-between items-end">
        <div>
          <h2 class="text-2xl font-black tracking-tight text-slate-900">SARH Contracting & Engineering</h2>
          <p class="text-xs text-slate-500 font-mono mt-1">LEGAL AGREEMENT & CONSTRUCTION CONTRACT</p>
        </div>
        <div class="text-right">
          <p class="font-mono text-sm font-black">{{ printData.contract_number }}</p>
          <p class="text-xs text-slate-500">Version {{ printData.version }} | {{ printData.start_date || printData.created_at?.slice(0, 10) }}</p>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-6 mb-6 text-xs">
        <div class="p-3 border border-slate-200 rounded-lg">
          <h4 class="font-black text-slate-900 mb-1">Contracting Party (First Party):</h4>
          <p class="font-bold">SARH Contracting & Finishing Co.</p>
          <p class="text-slate-600">Enterprise Commercial Registration No: SARH-8892</p>
        </div>
        <div class="p-3 border border-slate-200 rounded-lg">
          <h4 class="font-black text-slate-900 mb-1">Client (Second Party):</h4>
          <p class="font-bold">{{ printData.opportunity?.customer?.company_name || printData.opportunity?.customer?.name }}</p>
          <p class="text-slate-600">Project: {{ printData.opportunity?.title }}</p>
        </div>
      </div>

      <div class="mb-6 p-4 border border-emerald-200 bg-emerald-50/20 rounded-lg text-xs flex justify-between items-center">
        <div>
          <span class="text-slate-600 block">Total Agreed Contract Commitment:</span>
          <span class="text-lg font-black text-slate-900">{{ formatCurrency(printData.contract_value) }}</span>
        </div>
        <div class="text-right">
          <span class="text-slate-600 block">Execution Period:</span>
          <span class="font-bold text-slate-800">{{ printData.start_date || '—' }} {{ $t('contract.to') }} {{ printData.expected_completion_date || '—' }}</span>
        </div>
      </div>

      <!-- Payment Milestones -->
      <div class="mb-6">
        <h4 class="text-xs font-black text-slate-900 uppercase tracking-wider mb-2">Payment Milestones Schedule:</h4>
        <table class="w-full text-xs border border-slate-300 border-collapse">
          <thead>
            <tr class="bg-slate-100 font-bold border-b border-slate-300">
              <th class="p-2 border-r border-slate-300 text-start">#</th>
              <th class="p-2 border-r border-slate-300 text-start">Milestone Stage</th>
              <th class="p-2 border-r border-slate-300 text-start">Percentage</th>
              <th class="p-2 border-r border-slate-300 text-start">Amount</th>
              <th class="p-2 text-start">Deliverable Trigger</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(m, i) in printData.milestones" :key="m.id || i" class="border-b border-slate-200">
              <td class="p-2 border-r border-slate-300 font-bold">{{ i + 1 }}</td>
              <td class="p-2 border-r border-slate-300 font-bold">{{ m.milestone_label }}</td>
              <td class="p-2 border-r border-slate-300 font-bold">{{ m.percentage ? `${m.percentage}%` : '—' }}</td>
              <td class="p-2 border-r border-slate-300 font-bold">{{ m.fixed_amount ? formatCurrency(m.fixed_amount) : '—' }}</td>
              <td class="p-2 text-slate-600">{{ m.trigger_description || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Legal Terms -->
      <div v-if="printData.terms_and_conditions" class="mb-8 p-4 border border-slate-300 rounded-lg text-xs">
        <h4 class="font-black text-slate-900 uppercase tracking-wider mb-2">General & Special Contract Terms:</h4>
        <p class="leading-relaxed whitespace-pre-line text-slate-800">{{ printData.terms_and_conditions }}</p>
      </div>

      <!-- Signatures Box -->
      <div class="grid grid-cols-2 gap-8 pt-8 border-t-2 border-slate-400 text-xs text-center">
        <div>
          <p class="font-black text-slate-900 mb-12">{{ $t('contract.companyRep') }}</p>
          <div class="border-b border-dashed border-slate-400 w-48 mx-auto"></div>
          <p class="text-[11px] text-slate-500 mt-2">{{ printData.approved_user?.name || 'Executive Signatory' }}</p>
        </div>
        <div>
          <p class="font-black text-slate-900 mb-12">{{ $t('contract.clientRep') }}</p>
          <div class="border-b border-dashed border-slate-400 w-48 mx-auto"></div>
          <p class="text-[11px] text-slate-500 mt-2">{{ printData.opportunity?.customer?.name }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useAuthStore } from '../../stores/auth';
import RowActionDropdown from '../../components/common/RowActionDropdown.vue';
import {
  FileSignature,
  Plus,
  Search,
  Layers,
  FileText,
  Clock,
  CheckCircle2,
  Coins,
  Eye,
  Pencil,
  Trash2,
  Send,
  ShieldCheck,
  Printer,
  X,
  Receipt,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// Permissions
const canCreate = computed(() => authStore.hasPermission(['contracts.create']));
const canApprove = computed(() => authStore.hasPermission(['contracts.approve']));
const canUpdate = computed(() => authStore.hasPermission(['contracts.update']));
const canSign = computed(() => authStore.hasPermission(['contracts.sign']));
const canDelete = computed(() => authStore.hasPermission(['contracts.delete']));

const filterOpportunityId = computed(() => route.query.opportunity_id || '');

const loading = ref(false);
const saving = ref(false);
const contracts = ref([]);

const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  not_sent: 0,
  sent_for_signature: 0,
  signed: 0,
  void: 0,
  total_contract_value: 0,
});

const filters = reactive({
  search: '',
  status: '',
  signature_status: '',
  opportunity_id: filterOpportunityId.value,
});

let debounceTimer = null;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchData();
  }, 300);
};

// Modals State
const showCreateModal = ref(false);
const showDetailsModal = ref(false);
const activeContract = ref(null);
const printData = ref(null);

const opportunities = ref([]);
const availableQuotations = ref([]);
const loadingQuotations = ref(false);

const createForm = reactive({
  opportunity_id: '',
  quotation_id: '',
  contract_value: 0,
  original_quotation_total: 0,
  value_adjustment_reason: '',
  start_date: '',
  expected_completion_date: '',
  terms_and_conditions: '',
  notes: '',
  milestone_type: 'percentage',
  milestones: [
    { milestone_label: 'Advance Payment', percentage: 20.00, fixed_amount: null, trigger_description: 'Upon contract signing' },
    { milestone_label: 'Progress Handover', percentage: 70.00, fixed_amount: null, trigger_description: 'Upon completion of works' },
    { milestone_label: 'Final Delivery & Retention', percentage: 10.00, fixed_amount: null, trigger_description: 'Upon initial handover' },
  ],
});

const isValueAdjusted = computed(() => {
  return Math.abs(createForm.contract_value - createForm.original_quotation_total) > 0.01;
});

const internalStatusBadgeClass = (status) => {
  switch (status) {
    case 'Draft':
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    case 'Under Review':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300';
    case 'Approved':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300';
    case 'Superseded':
      return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400 line-through';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};

const signatureStatusBadgeClass = (status) => {
  switch (status) {
    case 'Not Sent':
      return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    case 'Sent for Signature':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-300';
    case 'Signed':
      return 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-300';
    case 'Void':
      return 'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-300 line-through';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};

const formatCurrency = (val) => {
  const num = Number(val || 0);
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'EGP',
    minimumFractionDigits: 2,
  }).format(num);
};

const calculateMilestonesPercentageSum = (milestones) => {
  if (!milestones || milestones.length === 0) return '0.00';
  const sum = milestones.reduce((acc, m) => acc + Number(m.percentage || 0), 0);
  return sum.toFixed(2);
};

const calculateMilestonesFixedSum = (milestones) => {
  if (!milestones || milestones.length === 0) return 0;
  return milestones.reduce((acc, m) => acc + Number(m.fixed_amount || 0), 0);
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = {
      search: filters.search || undefined,
      status: filters.status || undefined,
      signature_status: filters.signature_status || undefined,
      opportunity_id: filters.opportunity_id || undefined,
    };
    const res = await api.get('/contracts', { params });
    if (res.data?.success) {
      contracts.value = res.data.data;
      if (res.data.stats) {
        Object.assign(stats, res.data.stats);
      }
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error fetching contracts');
  } finally {
    loading.value = false;
  }
};

const fetchOpportunities = async () => {
  try {
    const res = await api.get('/opportunities', { params: { per_page: 100 } });
    if (res.data?.success) {
      opportunities.value = res.data.data;
    }
  } catch (err) {
    // Silent fail
  }
};

const onOpportunityChanged = async () => {
  if (!createForm.opportunity_id) return;
  loadingQuotations.value = true;
  try {
    const res = await api.get('/quotations', {
      params: {
        opportunity_id: createForm.opportunity_id,
        status: 'Approved',
        client_status: 'Accepted',
      },
    });
    if (res.data?.success) {
      availableQuotations.value = res.data.data;
      if (availableQuotations.value.length > 0) {
        createForm.quotation_id = availableQuotations.value[0].id;
        onQuotationChanged();
      } else {
        createForm.quotation_id = '';
      }
    }
  } catch (err) {
    alertService.error('Error loading quotations for opportunity');
  } finally {
    loadingQuotations.value = false;
  }
};

const onQuotationChanged = () => {
  const q = availableQuotations.value.find((item) => item.id === createForm.quotation_id);
  if (q) {
    createForm.contract_value = Number(q.grand_total || 0);
    createForm.original_quotation_total = Number(q.grand_total || 0);
    createForm.terms_and_conditions = q.payment_terms_text || '';
    createForm.value_adjustment_reason = '';
    onMilestoneTypeChanged();
  }
};

const onMilestoneTypeChanged = () => {
  if (createForm.milestone_type === 'percentage') {
    createForm.milestones = [
      { milestone_label: 'Advance Payment', percentage: 20.00, fixed_amount: null, trigger_description: 'Upon contract signing' },
      { milestone_label: 'Progress Handover', percentage: 70.00, fixed_amount: null, trigger_description: 'Upon completion of works' },
      { milestone_label: 'Final Delivery & Retention', percentage: 10.00, fixed_amount: null, trigger_description: 'Upon initial handover' },
    ];
  } else {
    const total = Number(createForm.contract_value || 0);
    createForm.milestones = [
      { milestone_label: 'Advance Payment', percentage: null, fixed_amount: Number((total * 0.2).toFixed(2)), trigger_description: 'Upon contract signing' },
      { milestone_label: 'Progress Handover', percentage: null, fixed_amount: Number((total * 0.7).toFixed(2)), trigger_description: 'Upon completion of works' },
      { milestone_label: 'Final Delivery & Retention', percentage: null, fixed_amount: Number((total * 0.1).toFixed(2)), trigger_description: 'Upon initial handover' },
    ];
  }
};

const addMilestone = (form) => {
  form.milestones.push({
    milestone_label: '',
    percentage: form.milestone_type === 'percentage' ? 0.00 : null,
    fixed_amount: form.milestone_type === 'fixed' ? 0.00 : null,
    trigger_description: '',
  });
};

const removeMilestone = (form, index) => {
  form.milestones.splice(index, 1);
};

const openCreateModal = (oppId) => {
  createForm.opportunity_id = oppId || '';
  createForm.quotation_id = '';
  createForm.contract_value = 0;
  createForm.original_quotation_total = 0;
  createForm.value_adjustment_reason = '';
  createForm.start_date = '';
  createForm.expected_completion_date = '';
  createForm.terms_and_conditions = '';
  createForm.notes = '';
  createForm.milestone_type = 'percentage';
  availableQuotations.value = [];
  onMilestoneTypeChanged();
  showCreateModal.value = true;
  if (oppId) {
    onOpportunityChanged();
  }
};

const submitCreateContract = async () => {
  saving.value = true;
  try {
    const payload = {
      opportunity_id: createForm.opportunity_id,
      quotation_id: createForm.quotation_id,
      contract_value: createForm.contract_value,
      value_adjustment_reason: createForm.value_adjustment_reason || undefined,
      start_date: createForm.start_date || undefined,
      expected_completion_date: createForm.expected_completion_date || undefined,
      terms_and_conditions: createForm.terms_and_conditions || undefined,
      notes: createForm.notes || undefined,
      milestones: createForm.milestones.map((m, idx) => ({
        milestone_label: m.milestone_label,
        percentage: createForm.milestone_type === 'percentage' ? m.percentage : undefined,
        fixed_amount: createForm.milestone_type === 'fixed' ? m.fixed_amount : undefined,
        trigger_description: m.trigger_description || undefined,
        sort_order: idx,
      })),
    };

    const res = await api.post('/contracts', payload);
    if (res.data?.success) {
      alertService.success(res.data.message || t('common.successSave'));
      showCreateModal.value = false;
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || t('common.validationError'));
  } finally {
    saving.value = false;
  }
};

const viewDetails = (item) => {
  activeContract.value = item;
  showDetailsModal.value = true;
};

const printContract = (item) => {
  printData.value = item;
  setTimeout(() => {
    window.print();
  }, 250);
};

const clearOpportunityFilter = () => {
  router.push({ path: '/contracts' });
  filters.opportunity_id = '';
  fetchData();
};

const submitReview = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.submitReview') + '?',
    t('contract.submitReview') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/submit-review`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error submitting for review');
  }
};

const approveContract = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.approve') + '?',
    t('contract.approve') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/approve`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error approving contract');
  }
};

const createRevision = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.createRevision') + '?',
    t('contract.createRevision') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/create-revision`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error creating revision');
  }
};

const sendForSignature = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.sendForSignature') + '?',
    t('contract.sendForSignature') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/send-for-signature`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error sending for signature');
  }
};

const markSigned = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.markSigned') + '?',
    t('contract.markSigned') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/mark-signed`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error marking signed');
  }
};

const voidSignature = async (item) => {
  const confirmed = await alertService.confirm(
    t('contract.voidSignature') + '?',
    t('contract.voidSignature') + ` #${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/contracts/${item.id}/void-signature`);
    if (res.data?.success) {
      alertService.success(res.data.message);
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error voiding signature');
  }
};

const deleteContract = async (item) => {
  const confirmed = await alertService.confirm(
    t('common.confirmDelete'),
    `#${item.contract_number}`
  );
  if (!confirmed) return;

  try {
    const res = await api.delete(`/contracts/${item.id}`);
    if (res.data?.success) {
      alertService.success(res.data.message || t('common.successDelete'));
      fetchData();
    }
  } catch (err) {
    alertService.error(err.response?.data?.message || 'Error deleting contract');
  }
};

const getRowActions = (contract) => {
  const actions = [
    {
      label: t('common.actions'),
      icon: Eye,
      handler: () => viewDetails(contract),
    },
  ];

  if (contract.status === 'Draft' && canUpdate.value) {
    actions.push({
      label: t('contract.submitReview'),
      icon: Clock,
      handler: () => submitReview(contract),
    });
  }

  if ((contract.status === 'Draft' || contract.status === 'Under Review') && canApprove.value) {
    actions.push({
      label: t('contract.approve'),
      icon: CheckCircle2,
      handler: () => approveContract(contract),
    });
  }

  if (contract.status === 'Approved' && canCreate.value) {
    actions.push({
      label: t('contract.createRevision'),
      icon: Layers,
      handler: () => createRevision(contract),
    });
  }

  if (contract.status === 'Approved' && contract.signature_status === 'Not Sent' && canSign.value) {
    actions.push({
      label: t('contract.sendForSignature'),
      icon: Send,
      handler: () => sendForSignature(contract),
    });
  }

  if (contract.status === 'Approved' && (contract.signature_status === 'Sent for Signature' || contract.signature_status === 'Not Sent') && canSign.value) {
    actions.push({
      label: t('contract.markSigned'),
      icon: ShieldCheck,
      handler: () => markSigned(contract),
    });
  }

  if (contract.signature_status !== 'Signed' && contract.signature_status !== 'Void' && canSign.value) {
    actions.push({
      label: t('contract.voidSignature'),
      icon: X,
      handler: () => voidSignature(contract),
    });
  }

  actions.push({
    label: t('contract.printContract'),
    icon: Printer,
    handler: () => printContract(contract),
  });

  if (contract.status === 'Draft' && canDelete.value) {
    actions.push({
      label: t('common.delete'),
      icon: Trash2,
      danger: true,
      handler: () => deleteContract(contract),
    });
  }

  return actions;
};

onMounted(() => {
  fetchOpportunities();
  fetchData();
});
</script>
