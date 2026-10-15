<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2.5">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
            <Calculator class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">
              {{ $t('estimation.title') }}
            </h1>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
              {{ $t('estimation.subtitle') }}
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <button
          v-if="canCreate"
          @click="openCreateModal()"
          class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] transition-colors cursor-pointer"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('estimation.createTitle') }}</span>
        </button>
      </div>
    </div>

    <!-- Costing Governance Notice -->
    <div v-if="showGovernanceNotice" class="rounded-xl border border-blue-100 bg-blue-50/50 p-3.5 dark:border-blue-900/30 dark:bg-blue-950/20 flex items-center justify-between gap-3 text-xs">
      <div class="flex items-center gap-2.5 text-blue-950 dark:text-blue-200">
        <ShieldCheck class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
        <span class="font-bold">دراسة التكاليف مبنية حصرًا على جدول كميات معتمد (Approved BOQ)، والكميات والوحدات محمية هندسياً.</span>
      </div>
      <button
        type="button"
        @click="showGovernanceNotice = false"
        class="text-blue-400 hover:text-blue-700 p-1 rounded-md transition-colors cursor-pointer"
        :title="$t('common.close')"
      >
        <X class="h-3.5 w-3.5" />
      </button>
    </div>

    <!-- Active Opportunity Filter Banner -->
    <div v-if="filterOpportunityId" class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
      <div class="flex items-center gap-2.5 text-xs text-emerald-900 dark:text-emerald-300">
        <div class="p-1.5 rounded-lg bg-[#00C896] text-white">
          <Calculator class="h-4 w-4" />
        </div>
        <div>
          <p class="font-bold">{{ $t('estimation.filterByOpportunity') }} #{{ filterOpportunityId }}</p>
          <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400">يتم تصفية دراسات التسعير للفرصة المحددة فقط.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="openCreateModal(filterOpportunityId)"
          class="px-3 py-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          + إعداد دراسة تسعير لهذه الفرصة
        </button>
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          عرض كل الدراسات
        </button>
      </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4">
      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate">{{ $t('estimation.totalEstimations') }}</span>
          <Layers class="h-4 w-4 text-gray-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 truncate">{{ $t('estimation.draftEstimations') }}</span>
          <FileText class="h-4 w-4 text-slate-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-3.5 sm:p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300 truncate">{{ $t('estimation.underReviewEstimations') }}</span>
          <Clock class="h-4 w-4 text-amber-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-amber-800 dark:text-amber-200">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-3.5 sm:p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 truncate">{{ $t('estimation.approvedEstimations') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-emerald-800 dark:text-emerald-200">{{ stats.approved }}</p>
      </div>

      <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-3.5 sm:p-4 shadow-xs dark:border-blue-900/30 dark:bg-blue-950/20 col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-blue-700 dark:text-blue-300 truncate">{{ $t('estimation.totalEstimatedCost') }}</span>
          <Coins class="h-4 w-4 text-blue-500 shrink-0" />
        </div>
        <p class="mt-2 text-base sm:text-lg font-black text-blue-900 dark:text-blue-100 truncate">
          {{ formatCurrency(stats.total_estimated_cost) }}
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
            :placeholder="$t('estimation.searchPlaceholder')"
            @input="debouncedFetch"
            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2 pe-4 ps-10 text-xs text-gray-900 focus:border-[#00C896] focus:bg-white focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <select
            v-model="filters.status"
            @change="fetchData"
            class="rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs text-gray-700 focus:border-[#00C896] focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
          >
            <option value="">{{ $t('estimation.allStatuses') }}</option>
            <option value="Draft">{{ $t('estimation.statusDraft') }}</option>
            <option value="Under Review">{{ $t('estimation.statusUnderReview') }}</option>
            <option value="Approved">{{ $t('estimation.statusApproved') }}</option>
            <option value="Superseded">{{ $t('estimation.statusSuperseded') }}</option>
          </select>

          <button
            v-if="hasActiveFilters"
            @click="resetFilters"
            class="rounded-xl border border-gray-200 px-3 py-2 text-xs font-bold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer"
          >
            {{ $t('common.reset') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Data Table Card -->
    <div class="rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
      <div v-if="loading" class="flex h-64 items-center justify-center">
        <div class="flex flex-col items-center gap-2">
          <div class="h-8 w-8 animate-spin rounded-full border-2 border-[#00C896] border-t-transparent"></div>
          <span class="text-xs text-gray-400 font-medium">{{ $t('common.loading') }}</span>
        </div>
      </div>

      <div v-else-if="items.length === 0" class="flex h-64 flex-col items-center justify-center p-6 text-center">
        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-800">
          <Calculator class="h-6 w-6" />
        </div>
        <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">
          {{ $t('estimation.noEstimations') }}
        </h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-sm">
          {{ $t('estimation.noEstimationsDesc') }}
        </p>
        <button
          v-if="canCreate"
          @click="openCreateModal()"
          class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-4 py-2 text-xs font-bold text-white hover:bg-[#00A87E] transition-colors cursor-pointer"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('estimation.createFirst') }}</span>
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-start text-xs">
          <thead>
            <tr class="border-b border-gray-100 bg-gray-50/75 dark:border-gray-800 dark:bg-gray-800/40 font-bold text-gray-500 dark:text-gray-400">
              <th class="px-4 py-3 text-start">{{ $t('estimation.colNumber') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colOpportunity') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colSourceBoq') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colDirectCost') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colOverhead') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colTotalCost') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colStatus') }}</th>
              <th class="px-4 py-3 text-start">{{ $t('estimation.colPreparedAt') }}</th>
              <th class="px-4 py-3 text-end">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="row in items"
              :key="row.id"
              class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors"
            >
              <td class="px-4 py-3 font-bold text-gray-900 dark:text-white">
                <div class="flex items-center gap-2">
                  <span>#{{ row.estimation_number }}</span>
                  <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-black text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    V{{ row.version }}
                  </span>
                </div>
                <div v-if="row.title" class="text-[11px] font-normal text-gray-500 truncate max-w-xs">
                  {{ row.title }}
                </div>
              </td>

              <td class="px-4 py-3">
                <div class="font-bold text-gray-800 dark:text-gray-200">
                  {{ row.opportunity?.title || '—' }}
                </div>
                <div class="text-[10px] text-gray-400">
                  {{ row.opportunity?.customer?.name || row.opportunity?.customer?.company_name || '—' }}
                </div>
              </td>

              <td class="px-4 py-3">
                <div v-if="row.boq" class="flex items-center gap-1.5">
                  <span class="font-bold text-slate-700 dark:text-slate-300 text-xs">
                    #{{ row.boq.boq_number }}
                  </span>
                  <span class="rounded bg-slate-100 px-1 py-0.2 text-[9px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                    V{{ row.boq.version }}
                  </span>
                </div>
                <span v-else class="text-gray-400 text-xs">—</span>
              </td>

              <td class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-300">
                {{ formatCurrency(row.direct_cost) }}
              </td>

              <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                <div class="font-bold text-xs">{{ Number(row.overhead_rate) }}%</div>
                <div class="text-[10px] text-gray-400">{{ formatCurrency(row.overhead_amount) }}</div>
              </td>

              <td class="px-4 py-3 font-black text-gray-900 dark:text-white">
                {{ formatCurrency(row.total_cost) }}
              </td>

              <td class="px-4 py-3">
                <span
                  class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black"
                  :class="statusBadgeClass(row.status)"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="statusDotClass(row.status)"></span>
                  {{ $t('estimation.status' + row.status.replace(/\s+/g, '')) }}
                </span>
              </td>

              <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-[11px]">
                {{ row.prepared_at || '—' }}
              </td>

              <td class="px-4 py-3 text-end">
                <div class="flex items-center justify-end gap-1.5">
                  <!-- Primary Action: View Details -->
                  <button
                    @click="openDetailsModal(row.id)"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-300 cursor-pointer"
                    :title="$t('common.view')"
                  >
                    <Eye class="h-4 w-4" />
                  </button>

                  <!-- Direct Action: Cost / Edit Lines if Draft / Under Review -->
                  <button
                    v-if="canEdit && canBeEdited(row)"
                    @click="openEditModal(row.id)"
                    class="rounded-lg p-1.5 text-blue-500 hover:bg-blue-50 hover:text-blue-700 dark:hover:bg-blue-950/40 cursor-pointer"
                    :title="$t('estimation.costLines')"
                  >
                    <Calculator class="h-4 w-4" />
                  </button>

                  <!-- Secondary Actions Dropdown -->
                  <RowActionDropdown
                    v-if="(canEdit && row.status === 'Draft') || (canApprove && canBeApproved(row)) || (canCreate && row.status === 'Approved') || (canDelete && canBeEdited(row))"
                    v-slot="{ close }"
                  >
                    <div class="py-1">
                      <!-- Submit for Review (Draft) -->
                      <button
                        v-if="canEdit && row.status === 'Draft'"
                        @click="close(); submitReview(row)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40 transition-colors"
                      >
                        <Send class="h-3.5 w-3.5 text-amber-500" />
                        <span>{{ $t('estimation.submitReview') }}</span>
                      </button>

                      <!-- Approve (Draft / Under Review) -->
                      <button
                        v-if="canApprove && canBeApproved(row)"
                        @click="close(); approveEstimation(row)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors"
                      >
                        <CheckCircle2 class="h-3.5 w-3.5 text-emerald-500" />
                        <span>{{ $t('estimation.approve') }}</span>
                      </button>

                      <!-- Create Revision (Approved only) -->
                      <button
                        v-if="canCreate && row.status === 'Approved'"
                        @click="close(); createRevision(row)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-purple-700 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40 transition-colors"
                      >
                        <History class="h-3.5 w-3.5 text-purple-500" />
                        <span>{{ $t('estimation.createRevision') }}</span>
                      </button>

                      <!-- Delete (Draft / Under Review) -->
                      <button
                        v-if="canDelete && canBeEdited(row)"
                        @click="close(); confirmDelete(row)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/40 transition-colors"
                      >
                        <Trash2 class="h-3.5 w-3.5 text-rose-500" />
                        <span>{{ $t('common.delete') }}</span>
                      </button>
                    </div>
                  </RowActionDropdown>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="pagination.total > pagination.per_page" class="flex items-center justify-between border-t border-gray-100 p-4 dark:border-gray-800">
        <span class="text-xs text-gray-500">
          {{ pagination.total }} {{ $t('common.total') }}
        </span>
        <div class="flex items-center gap-1">
          <button
            :disabled="pagination.current_page <= 1"
            @click="changePage(pagination.current_page - 1)"
            class="rounded-lg px-2.5 py-1 text-xs font-bold border border-gray-200 disabled:opacity-40 cursor-pointer"
          >
            {{ $t('common.prev') }}
          </button>
          <span class="px-2 text-xs font-bold text-gray-700 dark:text-gray-300">
            {{ pagination.current_page }} / {{ pagination.last_page }}
          </span>
          <button
            :disabled="pagination.current_page >= pagination.last_page"
            @click="changePage(pagination.current_page + 1)"
            class="rounded-lg px-2.5 py-1 text-xs font-bold border border-gray-200 disabled:opacity-40 cursor-pointer"
          >
            {{ $t('common.next') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Create Estimation Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs">
      <div class="w-full max-w-2xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-[#00C896]/10 text-[#00A87E]">
              <Calculator class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ $t('estimation.createTitle') }}
              </h3>
              <p class="text-[11px] text-gray-500">{{ $t('estimation.createSubtitle') }}</p>
            </div>
          </div>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitCreate" class="mt-5 space-y-4 text-xs">
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('estimation.formOpportunity') }} *
            </label>
            <select
              v-model="createForm.opportunity_id"
              @change="onOpportunityChange"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="" disabled>{{ $t('estimation.selectOpportunity') }}</option>
              <option v-for="opp in opportunitiesList" :key="opp.id" :value="opp.id">
                #{{ opp.opportunity_number || opp.id }} — {{ opp.title }} ({{ opp.customer?.name || opp.customer?.company_name || 'عميل' }})
              </option>
            </select>
          </div>

          <div v-if="createForm.opportunity_id">
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('estimation.formSourceBoq') }} *
            </label>
            <div v-if="loadingBoqs" class="p-3 text-center text-gray-400">
              <span class="animate-spin inline-block mr-2">⏳</span> جاري تحميل جداول الكميات المعتمدة...
            </div>
            <div v-else-if="eligibleBoqs.length === 0" class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300">
              ⚠️ لا يوجد أي جدول كميات معتمد (Approved BOQ) لهذه الفرصة. يجب إعداد جدول كميات واعتماده أولاً.
            </div>
            <select
              v-else
              v-model="createForm.boq_id"
              @change="onBoqChange"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="" disabled>{{ $t('estimation.selectBoq') }}</option>
              <option v-for="b in eligibleBoqs" :key="b.id" :value="b.id">
                #{{ b.boq_number }} (الإصدار V{{ b.version }}) — {{ b.items?.length || 0 }} بند كميات
              </option>
            </select>
          </div>

          <!-- Preview of lines to be imported -->
          <div v-if="selectedBoqPreview" class="rounded-2xl border border-gray-100 bg-gray-50/50 p-3.5 dark:border-gray-800 dark:bg-gray-800/40">
            <div class="flex items-center justify-between mb-2">
              <span class="font-bold text-gray-700 dark:text-gray-300 text-[11px]">
                معاينة بنود جدول الكميات المراد تسعيرها ({{ selectedBoqPreview.items?.length || 0 }} بند):
              </span>
              <span class="rounded bg-emerald-100 px-2 py-0.5 text-[10px] font-black text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300">
                كميات ووحدات مجمدة
              </span>
            </div>
            <div class="max-h-40 overflow-y-auto space-y-1 text-[11px]">
              <div
                v-for="(line, idx) in selectedBoqPreview.items"
                :key="line.id || idx"
                class="flex items-center justify-between bg-white dark:bg-gray-900 p-2 rounded-xl border border-gray-100 dark:border-gray-800"
              >
                <div class="truncate max-w-sm">
                  <span class="font-bold text-gray-900 dark:text-white mr-1.5">{{ line.item_code }}.</span>
                  <span class="text-gray-700 dark:text-gray-300">{{ line.description }}</span>
                </div>
                <div class="font-black text-[#00A87E] dark:text-[#00C896] whitespace-nowrap">
                  {{ Number(line.quantity).toFixed(2) }} {{ line.unit }}
                </div>
              </div>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('estimation.formTitle') }}
              </label>
              <input
                v-model="createForm.title"
                type="text"
                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                placeholder="دراسة تكلفة أعمال الفرصة..."
              />
            </div>

            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('estimation.formOverheadRate') }} (%)
              </label>
              <input
                v-model.number="createForm.overhead_rate"
                type="number"
                step="0.01"
                min="0"
                max="100"
                class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                placeholder="5.00"
              />
            </div>
          </div>

          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('estimation.formNotes') }}
            </label>
            <textarea
              v-model="createForm.notes"
              rows="2"
              class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2.5 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
              placeholder="ملاحظات أو اشتراطات تسعير..."
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-4 border-t border-gray-100 dark:border-gray-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 font-bold hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="submit"
              :disabled="saving || !createForm.boq_id"
              class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white font-bold disabled:opacity-50 transition-colors cursor-pointer"
            >
              <span v-if="saving">جاري الإنشاء...</span>
              <span v-else>{{ $t('estimation.btnGenerate') }}</span>
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit / Costing Modal -->
    <div v-if="showEditModal && activeEstimation" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-xs">
      <div class="w-full max-w-5xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-h-[95vh] flex flex-col">
        <!-- Edit Modal Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-[#00C896]/10 text-[#00A87E]">
              <Calculator class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">
                  دراسة تسعير #{{ activeEstimation.estimation_number }}
                </h3>
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-black text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                  V{{ activeEstimation.version }}
                </span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="statusBadgeClass(activeEstimation.status)">
                  {{ activeEstimation.status }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500">
                جدول الكميات المصدر: #{{ activeEstimation.boq?.boq_number }} (V{{ activeEstimation.boq?.version }}) • {{ activeEstimation.opportunity?.title }}
              </p>
            </div>
          </div>
          <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 p-1">
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Costing Table Area (Scrollable) -->
        <div class="my-4 overflow-y-auto grow space-y-4 pe-1">
          <!-- Document Level Settings & Overhead -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 p-3.5 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 text-xs">
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('estimation.formTitle') }}
              </label>
              <input
                v-model="editForm.title"
                type="text"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              />
            </div>

            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                نسبة المصاريف العمومية والإشراف (Overhead %)
              </label>
              <div class="flex items-center gap-1.5">
                <input
                  v-model.number="editForm.overhead_rate"
                  type="number"
                  step="0.01"
                  min="0"
                  max="100"
                  class="w-full rounded-xl border border-gray-200 bg-white p-2 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                  placeholder="0.00"
                />
                <span class="font-bold text-gray-500">%</span>
              </div>
            </div>

            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
                {{ $t('estimation.formNotes') }}
              </label>
              <input
                v-model="editForm.notes"
                type="text"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 text-xs text-gray-900 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              />
            </div>
          </div>

          <!-- Lines Cost Breakdown Table -->
          <div class="rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="p-3 bg-gray-50/75 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
              <span class="font-black text-gray-800 dark:text-gray-200 text-xs">
                بنود التكلفة المباشرة (Direct Cost Components):
              </span>
              <span class="text-[11px] text-gray-500">
                الكميات مجمدة، والأسعار المدخلة هي سعر تكلفة الوحدة المباشرة
              </span>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="(item, idx) in editForm.items"
                :key="item.boq_item_id"
                class="p-4 space-y-3 bg-white dark:bg-gray-900 hover:bg-gray-50/30 transition-colors"
              >
                <!-- Line Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                  <div class="flex items-start gap-2">
                    <span class="font-black text-gray-900 dark:text-white text-xs mt-0.5">
                      {{ item.item_code || (idx + 1) }}.
                    </span>
                    <div>
                      <p class="font-bold text-gray-900 dark:text-white text-xs">{{ item.description }}</p>
                      <p class="text-[10px] text-gray-400 mt-0.5" v-if="item.notes">{{ item.notes }}</p>
                    </div>
                  </div>

                  <div class="flex items-center gap-3 shrink-0 text-xs">
                    <div class="text-end">
                      <span class="text-[10px] text-gray-400 block">كمية البند:</span>
                      <span class="font-black text-[#00A87E] dark:text-[#00C896]">
                        {{ Number(item.quantity).toFixed(2) }} {{ item.unit }}
                      </span>
                    </div>

                    <div class="text-end border-s border-gray-200 dark:border-gray-800 ps-3">
                      <span class="text-[10px] text-gray-400 block">إجمالي تكلفة البند:</span>
                      <span class="font-black text-gray-900 dark:text-white">
                        {{ formatCurrency(calculateLineDirectCost(item)) }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- 5 Direct Cost Components Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-2.5 pt-2 border-t border-gray-50 dark:border-gray-800/60 text-xs">
                  <div>
                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">مواد (Material / وحدة)</label>
                    <input
                      v-model.number="item.rates.material"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2 text-xs font-bold text-gray-800 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">مصنعيات (Labor / وحدة)</label>
                    <input
                      v-model.number="item.rates.labor"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2 text-xs font-bold text-gray-800 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">معدات (Equipment / وحدة)</label>
                    <input
                      v-model.number="item.rates.equipment"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2 text-xs font-bold text-gray-800 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">مقاولو باطن (Subcon / وحدة)</label>
                    <input
                      v-model.number="item.rates.subcontractor"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2 text-xs font-bold text-gray-800 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="0.00"
                    />
                  </div>

                  <div>
                    <label class="block text-[10px] font-bold text-gray-500 mb-0.5">أخرى (Other / وحدة)</label>
                    <input
                      v-model.number="item.rates.other"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50/50 p-2 text-xs font-bold text-gray-800 focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      placeholder="0.00"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Live Totals Footer & Actions -->
        <div class="pt-4 border-t border-gray-100 dark:border-gray-800 shrink-0 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <!-- Totals Breakdown Pills -->
          <div class="flex flex-wrap items-center gap-4 text-xs">
            <div class="p-2 rounded-xl bg-gray-50 dark:bg-gray-800">
              <span class="text-gray-400 block text-[10px]">إجمالي التكلفة المباشرة:</span>
              <span class="font-black text-gray-800 dark:text-gray-200">
                {{ formatCurrency(computedDirectCostSubtotal) }}
              </span>
            </div>

            <div class="p-2 rounded-xl bg-gray-50 dark:bg-gray-800">
              <span class="text-gray-400 block text-[10px]">
                المصاريف العمومية ({{ editForm.overhead_rate || 0 }}%):
              </span>
              <span class="font-black text-gray-800 dark:text-gray-200">
                {{ formatCurrency(computedOverheadAmount) }}
              </span>
            </div>

            <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
              <span class="text-emerald-700 dark:text-emerald-300 block text-[10px] font-bold">
                إجمالي التكلفة التقديرية (Total Estimated Cost):
              </span>
              <span class="font-black text-emerald-950 dark:text-emerald-200 text-sm">
                {{ formatCurrency(computedGrandTotalCost) }}
              </span>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 font-bold hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer text-xs"
            >
              {{ $t('common.cancel') }}
            </button>
            <button
              type="button"
              @click="submitUpdate"
              :disabled="saving"
              class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white font-bold disabled:opacity-50 transition-colors cursor-pointer text-xs"
            >
              <span v-if="saving">جاري الحفظ...</span>
              <span v-else>حفظ دراسة التكلفة</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details / Printable Sheet Modal -->
    <div v-if="showDetailsModal && activeEstimation" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-6 bg-black/60 backdrop-blur-xs">
      <div class="w-full max-w-4xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 max-h-[95vh] flex flex-col">
        <!-- Details Header -->
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div class="flex items-center gap-2.5">
            <div class="p-2 rounded-xl bg-[#00C896]/10 text-[#00A87E]">
              <FileText class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">
                  وثيقة دراسة التسعير والتكلفة #{{ activeEstimation.estimation_number }}
                </h3>
                <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-black text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                  V{{ activeEstimation.version }}
                </span>
                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="statusBadgeClass(activeEstimation.status)">
                  {{ activeEstimation.status }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500">
                الفرصة: {{ activeEstimation.opportunity?.title }} ({{ activeEstimation.opportunity?.customer?.name }})
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="printCostSheet"
              class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 text-xs font-bold hover:bg-gray-50 cursor-pointer"
            >
              <Printer class="h-4 w-4" />
              <span>طباعة الشيت</span>
            </button>
            <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 p-1">
              <X class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Printable Area -->
        <div id="cost-sheet-printable" class="my-4 overflow-y-auto grow space-y-4 pe-1 text-xs">
          <!-- Stale Source Warning Banner -->
          <div v-if="activeEstimationDetails?.is_stale_source" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20 text-amber-900 dark:text-amber-300">
            <div class="flex items-start gap-3">
              <AlertTriangle class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
              <div>
                <p class="font-black text-xs">تحذير: جدول الكميات المعتمد الحالي لهذه الفرصة تم تحديثه!</p>
                <p class="text-[11px] mt-0.5 leading-relaxed">
                  هذه الدراسة مرتبطة بجدول كميات سابق (#{{ activeEstimation.boq?.boq_number }} - V{{ activeEstimation.boq?.version }}). تم اعتماد إصدار أحدث (#{{ activeEstimationDetails.latest_approved_boq?.boq_number }} - V{{ activeEstimationDetails.latest_approved_boq?.version }}). تعتبر هذه الدراسة تاريخية، ويجب توليد دراسة تسعير جديدة من جدول الكميات الأحدث.
                </p>
              </div>
            </div>
          </div>

          <!-- Document Metadata Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/40 text-xs">
            <div>
              <span class="text-gray-400 block text-[10px]">جدول الكميات المصدر:</span>
              <span class="font-bold text-gray-900 dark:text-white">
                #{{ activeEstimation.boq?.boq_number }} (V{{ activeEstimation.boq?.version }})
              </span>
            </div>

            <div>
              <span class="text-gray-400 block text-[10px]">تاريخ الإعداد:</span>
              <span class="font-bold text-gray-900 dark:text-white">
                {{ activeEstimation.prepared_at || '—' }}
              </span>
            </div>

            <div>
              <span class="text-gray-400 block text-[10px]">معد الدراسة:</span>
              <span class="font-bold text-gray-900 dark:text-white">
                {{ activeEstimation.prepared_user?.name || '—' }}
              </span>
            </div>

            <div>
              <span class="text-gray-400 block text-[10px]">معتمد الدراسة:</span>
              <span class="font-bold text-gray-900 dark:text-white">
                {{ activeEstimation.approved_user?.name || '—' }}
              </span>
            </div>
          </div>

          <!-- Items Breakdown Table -->
          <div class="rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-start text-xs">
              <thead>
                <tr class="bg-gray-50 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800 text-[10px] font-bold text-gray-500">
                  <th class="p-2.5 text-start">#</th>
                  <th class="p-2.5 text-start">الوصف والمواصفة</th>
                  <th class="p-2.5 text-end">الكمية والوحدة</th>
                  <th class="p-2.5 text-end">تكلفة الوحدة</th>
                  <th class="p-2.5 text-end">التكلفة المباشرة</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="line in activeEstimation.items" :key="line.id">
                  <td class="p-2.5 font-bold text-gray-400">{{ line.item_code }}</td>
                  <td class="p-2.5">
                    <p class="font-bold text-gray-900 dark:text-white">{{ line.description }}</p>
                    <div class="flex flex-wrap gap-2 text-[10px] text-gray-400 mt-1">
                      <span v-if="Number(line.material_cost) > 0">مواد: {{ formatCurrency(line.material_cost) }}</span>
                      <span v-if="Number(line.labor_cost) > 0">مصنعيات: {{ formatCurrency(line.labor_cost) }}</span>
                      <span v-if="Number(line.equipment_cost) > 0">معدات: {{ formatCurrency(line.equipment_cost) }}</span>
                      <span v-if="Number(line.subcontractor_cost) > 0">باطن: {{ formatCurrency(line.subcontractor_cost) }}</span>
                      <span v-if="Number(line.other_cost) > 0">أخرى: {{ formatCurrency(line.other_cost) }}</span>
                    </div>
                  </td>
                  <td class="p-2.5 text-end font-bold text-gray-700 dark:text-gray-300">
                    {{ Number(line.quantity).toFixed(2) }} {{ line.unit }}
                  </td>
                  <td class="p-2.5 text-end font-semibold text-gray-600 dark:text-gray-400">
                    {{ formatCurrency(line.unit_cost) }}
                  </td>
                  <td class="p-2.5 text-end font-black text-gray-900 dark:text-white">
                    {{ formatCurrency(line.total_cost) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Document Totals Summary Box -->
          <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1 text-xs">
              <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400">
                <span>إجمالي التكاليف المباشرة (Direct Subtotal):</span>
                <span class="font-bold text-gray-900 dark:text-white">
                  {{ formatCurrency(activeEstimation.direct_cost) }}
                </span>
              </div>
              <div class="flex items-center gap-4 text-gray-600 dark:text-gray-400">
                <span>المصاريف العمومية والإشراف ({{ Number(activeEstimation.overhead_rate) }}%):</span>
                <span class="font-bold text-gray-900 dark:text-white">
                  {{ formatCurrency(activeEstimation.overhead_amount) }}
                </span>
              </div>
            </div>

            <div class="p-3 rounded-2xl bg-[#00C896]/10 border border-[#00C896]/20 text-end">
              <span class="text-[10px] font-bold text-[#00A87E] dark:text-[#00C896] block">
                إجمالي التكلفة التقديرية المعتمدة (Internal Estimated Cost):
              </span>
              <span class="text-xl font-black text-[#00A87E] dark:text-[#00C896]">
                {{ formatCurrency(activeEstimation.total_cost) }}
              </span>
            </div>
          </div>
        </div>

        <!-- Details Modal Footer -->
        <div class="pt-4 border-t border-gray-100 dark:border-gray-800 shrink-0 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <!-- Submit for Review -->
            <button
              v-if="canEdit && activeEstimation.status === 'Draft'"
              @click="submitReview(activeEstimation); showDetailsModal = false"
              class="px-3 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs cursor-pointer"
            >
              تقديم للمراجعة
            </button>

            <!-- Approve -->
            <button
              v-if="canApprove && canBeApproved(activeEstimation)"
              @click="approveEstimation(activeEstimation); showDetailsModal = false"
              class="px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs cursor-pointer"
            >
              اعتماد الدراسة رسمياً
            </button>

            <!-- Create Revision -->
            <button
              v-if="canCreate && activeEstimation.status === 'Approved'"
              @click="createRevision(activeEstimation); showDetailsModal = false"
              class="px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white font-bold text-xs cursor-pointer"
            >
              إنشاء مراجعة جديدة (V{{ activeEstimation.version + 1 }})
            </button>
          </div>

          <button
            type="button"
            @click="showDetailsModal = false"
            class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 font-bold hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 cursor-pointer text-xs"
          >
            {{ $t('common.close') }}
          </button>
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
  Calculator,
  ShieldCheck,
  Plus,
  Search,
  Layers,
  FileText,
  Clock,
  CheckCircle2,
  Coins,
  Eye,
  Trash2,
  Send,
  History,
  AlertTriangle,
  Printer,
  X,
} from 'lucide-vue-next';

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const authStore = useAuthStore();

// State
const showGovernanceNotice = ref(true);
const loading = ref(false);
const saving = ref(false);
const loadingBoqs = ref(false);
const items = ref([]);
const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  total_estimated_cost: 0,
});
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 15,
  total: 0,
});
const filters = reactive({
  search: '',
  status: '',
  opportunity_id: route.query.opportunity_id || '',
});

// Permissions
const canCreate = computed(() => authStore.isOwner || authStore.isSuperAdmin || authStore.hasPermission(['estimations.create', 'estimation.create']));
const canEdit = computed(() => authStore.isOwner || authStore.isSuperAdmin || authStore.hasPermission(['estimations.update', 'estimation.update']));
const canDelete = computed(() => authStore.isOwner || authStore.isSuperAdmin || authStore.hasPermission(['estimations.delete', 'estimation.delete']));
const canApprove = computed(() => authStore.isOwner || authStore.isSuperAdmin || authStore.hasPermission(['estimations.approve', 'estimation.approve']));

const canBeEdited = (row) => ['Draft', 'Under Review'].includes(row.status);
const canBeApproved = (row) => ['Draft', 'Under Review'].includes(row.status);

// Opportunities & BOQs
const filterOpportunityId = computed(() => filters.opportunity_id);
const hasActiveFilters = computed(() => filters.search || filters.status || filters.opportunity_id);
const opportunitiesList = ref([]);
const eligibleBoqs = ref([]);
const selectedBoqPreview = ref(null);

// Modals
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const activeEstimation = ref(null);
const activeEstimationDetails = ref(null);

const createForm = reactive({
  opportunity_id: '',
  boq_id: '',
  title: '',
  overhead_rate: 0,
  notes: '',
});

const editForm = reactive({
  title: '',
  overhead_rate: 0,
  notes: '',
  items: [],
});

// Fetch main data
const fetchData = async () => {
  loading.value = true;
  try {
    const params = {
      page: pagination.current_page,
      per_page: pagination.per_page,
      search: filters.search || undefined,
      status: filters.status || undefined,
      opportunity_id: filters.opportunity_id || undefined,
    };
    const res = await api.get('/estimations', { params });
    if (res.data?.success) {
      items.value = res.data.data;
      Object.assign(pagination, res.data.meta);
      Object.assign(stats, res.data.stats);
    }
  } catch (err) {
    console.error('Error fetching estimations:', err);
  } finally {
    loading.value = false;
  }
};

let debounceTimer = null;
const debouncedFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    pagination.current_page = 1;
    fetchData();
  }, 300);
};

const resetFilters = () => {
  filters.search = '';
  filters.status = '';
  filters.opportunity_id = '';
  pagination.current_page = 1;
  router.replace({ query: {} });
  fetchData();
};

const clearOpportunityFilter = () => {
  filters.opportunity_id = '';
  router.replace({ query: {} });
  fetchData();
};

const changePage = (page) => {
  pagination.current_page = page;
  fetchData();
};

// Opportunities list
const fetchOpportunities = async () => {
  try {
    const res = await api.get('/opportunities?per_page=100');
    if (res.data?.success) {
      opportunitiesList.value = res.data.data;
    }
  } catch (err) {
    console.error('Error fetching opportunities:', err);
  }
};

const onOpportunityChange = async () => {
  createForm.boq_id = '';
  selectedBoqPreview.value = null;
  eligibleBoqs.value = [];
  if (!createForm.opportunity_id) return;

  loadingBoqs.value = true;
  try {
    const res = await api.get('/boqs', {
      params: {
        opportunity_id: createForm.opportunity_id,
        status: 'Approved',
        per_page: 50,
      },
    });
    if (res.data?.success) {
      eligibleBoqs.value = res.data.data;
      if (eligibleBoqs.value.length === 1) {
        createForm.boq_id = eligibleBoqs.value[0].id;
        onBoqChange();
      }
    }
  } catch (err) {
    console.error('Error loading eligible BOQs:', err);
  } finally {
    loadingBoqs.value = false;
  }
};

const onBoqChange = async () => {
  selectedBoqPreview.value = null;
  if (!createForm.boq_id) return;
  try {
    const res = await api.get(`/boqs/${createForm.boq_id}`);
    if (res.data?.success) {
      selectedBoqPreview.value = res.data.data;
    }
  } catch (err) {
    console.error('Error previewing BOQ:', err);
  }
};

const openCreateModal = (oppId = null) => {
  createForm.opportunity_id = oppId || filters.opportunity_id || '';
  createForm.boq_id = '';
  createForm.title = '';
  createForm.overhead_rate = 0;
  createForm.notes = '';
  selectedBoqPreview.value = null;
  showCreateModal.value = true;
  if (createForm.opportunity_id) {
    onOpportunityChange();
  }
};

const submitCreate = async () => {
  saving.value = true;
  try {
    const res = await api.post('/estimations', {
      opportunity_id: createForm.opportunity_id,
      boq_id: createForm.boq_id,
      title: createForm.title || undefined,
      overhead_rate: createForm.overhead_rate || 0,
      notes: createForm.notes || undefined,
    });
    if (res.data?.success) {
      showCreateModal.value = false;
      await fetchData();
      alertService.showSuccess({
        title: 'تم إنشاء دراسة التسعير بنجاح',
        text: 'يمكنك الآن إدخال أسعار التكلفة للبنود المباشرة والمصاريف العمومية.',
      });
      // Immediately open costing editor for the new estimation
      if (res.data.data?.id) {
        openEditModal(res.data.data.id);
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'حدث خطأ أثناء إنشاء دراسة التسعير';
    alertService.showError({ title: 'خطأ', text: msg });
  } finally {
    saving.value = false;
  }
};

// Edit / Costing
const openEditModal = async (id) => {
  try {
    const res = await api.get(`/estimations/${id}`);
    if (res.data?.success) {
      activeEstimation.value = res.data.data;
      editForm.title = activeEstimation.value.title || '';
      editForm.overhead_rate = Number(activeEstimation.value.overhead_rate) || 0;
      editForm.notes = activeEstimation.value.notes || '';

      // Initialize lines with their existing component rates
      editForm.items = (activeEstimation.value.items || []).map((item) => {
        const rates = {
          material: 0,
          labor: 0,
          equipment: 0,
          subcontractor: 0,
          other: 0,
        };

        (item.costs || []).forEach((c) => {
          if (rates[c.cost_type] !== undefined) {
            rates[c.cost_type] = Number(c.cost_rate) || 0;
          }
        });

        return {
          boq_item_id: item.boq_item_id,
          item_code: item.item_code,
          description: item.description,
          unit: item.unit,
          quantity: Number(item.quantity),
          notes: item.notes || '',
          rates,
        };
      });

      showEditModal.value = true;
    }
  } catch (err) {
    console.error('Error loading estimation for edit:', err);
  }
};

const calculateLineDirectCost = (item) => {
  const sumRates =
    Number(item.rates.material || 0) +
    Number(item.rates.labor || 0) +
    Number(item.rates.equipment || 0) +
    Number(item.rates.subcontractor || 0) +
    Number(item.rates.other || 0);
  return Number(item.quantity || 0) * sumRates;
};

const computedDirectCostSubtotal = computed(() => {
  return editForm.items.reduce((sum, it) => sum + calculateLineDirectCost(it), 0);
});

const computedOverheadAmount = computed(() => {
  const rate = Math.max(0, Math.min(100, Number(editForm.overhead_rate || 0)));
  return (computedDirectCostSubtotal.value * rate) / 100;
});

const computedGrandTotalCost = computed(() => {
  return computedDirectCostSubtotal.value + computedOverheadAmount.value;
});

const submitUpdate = async () => {
  saving.value = true;
  try {
    const formattedItems = editForm.items.map((it) => {
      const costs = [];
      ['material', 'labor', 'equipment', 'subcontractor', 'other'].forEach((type) => {
        const rate = Number(it.rates[type] || 0);
        if (rate > 0) {
          costs.push({
            cost_type: type,
            cost_rate: rate,
            description: `${type} cost`,
          });
        }
      });

      return {
        boq_item_id: it.boq_item_id,
        notes: it.notes || undefined,
        costs,
      };
    });

    const res = await api.put(`/estimations/${activeEstimation.value.id}`, {
      title: editForm.title || undefined,
      overhead_rate: editForm.overhead_rate || 0,
      notes: editForm.notes || undefined,
      items: formattedItems,
    });

    if (res.data?.success) {
      showEditModal.value = false;
      await fetchData();
      alertService.showSuccess({
        title: 'تم حفظ دراسة التكلفة بنجاح',
      });
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'حدث خطأ أثناء حفظ التعديلات';
    alertService.showError({ title: 'خطأ', text: msg });
  } finally {
    saving.value = false;
  }
};

// Details Modal
const openDetailsModal = async (id) => {
  try {
    const res = await api.get(`/estimations/${id}`);
    if (res.data?.success) {
      activeEstimation.value = res.data.data;
      activeEstimationDetails.value = res.data;
      showDetailsModal.value = true;
    }
  } catch (err) {
    console.error('Error loading details:', err);
  }
};

// Approval Workflow
const submitReview = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'تقديم دراسة التسعير للمراجعة؟',
    text: `سيتم تحويل دراسة التسعير رقم #${row.estimation_number} إلى حالة "قيد المراجعة" للتدقيق الفني والإداري.`,
    icon: 'question',
    confirmButtonText: 'نعم، تقديم للمراجعة',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/estimations/${row.id}/submit-review`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم تقديم الدراسة للمراجعة بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر تقديم الدراسة للمراجعة';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const approveEstimation = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'اعتماد دراسة التسعير والتكلفة؟',
    text: `سيتم قفل تعديل دراسة التسعير #${row.estimation_number} واعتمادها رسمياً كالأساس المالي لعرض السعر (Quotation).`,
    icon: 'warning',
    confirmButtonText: 'نعم، اعتماد رسمي',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/estimations/${row.id}/approve`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم اعتماد دراسة التسعير بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر اعتماد دراسة التسعير';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const createRevision = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'إنشاء مراجعة تسعير جديدة؟',
    text: `سيتم إنشاء مسودة جديدة V${row.version + 1} مستنسخة من النسخة المعتمدة الحالية للتعديل عليها دون المساس بالتاريخ السابق.`,
    icon: 'info',
    confirmButtonText: 'إنشاء مراجعة',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/estimations/${row.id}/create-revision`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم إنشاء المراجعة بنجاح' });
        if (res.data.data?.id) {
          openEditModal(res.data.data.id);
        }
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر إنشاء المراجعة';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const confirmDelete = async (row) => {
  const confirmed = await alertService.confirmDelete({
    title: 'حذف دراسة التسعير؟',
    text: `هل أنت متأكد من حذف مسودة دراسة التسعير رقم #${row.estimation_number}؟ لا يمكن التراجع عن هذا الإجراء.`,
    confirmButtonText: 'نعم، حذف',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.delete(`/estimations/${row.id}`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم الحذف بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر حذف الدراسة';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const printCostSheet = () => {
  window.print();
};

const formatCurrency = (val) => {
  const num = Number(val) || 0;
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ر.س';
};

const statusBadgeClass = (status) => {
  switch (status) {
    case 'Draft':
      return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
    case 'Under Review':
      return 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300';
    case 'Approved':
      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300';
    case 'Superseded':
      return 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400';
    default:
      return 'bg-gray-100 text-gray-700';
  }
};

const statusDotClass = (status) => {
  switch (status) {
    case 'Draft':
      return 'bg-slate-400';
    case 'Under Review':
      return 'bg-amber-500';
    case 'Approved':
      return 'bg-emerald-500';
    case 'Superseded':
      return 'bg-gray-400';
    default:
      return 'bg-gray-400';
  }
};

onMounted(async () => {
  await Promise.all([fetchData(), fetchOpportunities()]);
  if (route.query.create && route.query.opportunity_id) {
    openCreateModal(route.query.opportunity_id);
  }
});
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #cost-sheet-printable, #cost-sheet-printable * {
    visibility: visible;
  }
  #cost-sheet-printable {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
  }
}
</style>
