<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white flex items-center gap-2.5">
          <Receipt class="h-6 w-6 text-[#00C896]" />
          <span>{{ $t('quotation.title') }}</span>
        </h1>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
          {{ $t('quotation.subtitle') }}
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
          <span>{{ $t('quotation.createTitle') }}</span>
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
        <span>{{ $t('quotation.filterByOpportunity') }}: #{{ filterOpportunityId }}</span>
      </div>
      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          عرض كل عروض الأسعار
        </button>
      </div>
    </div>

    <!-- Responsive KPI Stats Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 lg:gap-4">
      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400 truncate">{{ $t('quotation.totalQuotations') }}</span>
          <Layers class="h-4 w-4 text-gray-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-3.5 sm:p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400 truncate">{{ $t('quotation.draftQuotations') }}</span>
          <FileText class="h-4 w-4 text-slate-400 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-3.5 sm:p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-300 truncate">{{ $t('quotation.underReviewQuotations') }}</span>
          <Clock class="h-4 w-4 text-amber-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-amber-800 dark:text-amber-200">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-3.5 sm:p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-300 truncate">{{ $t('quotation.approvedQuotations') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500 shrink-0" />
        </div>
        <p class="mt-2 text-lg sm:text-xl font-black text-emerald-800 dark:text-emerald-200">{{ stats.approved }}</p>
      </div>

      <div class="rounded-2xl border border-blue-100 bg-blue-50/40 p-3.5 sm:p-4 shadow-xs dark:border-blue-900/30 dark:bg-blue-950/20 col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-blue-700 dark:text-blue-300 truncate">{{ $t('quotation.totalQuotedValue') }}</span>
          <Coins class="h-4 w-4 text-blue-500 shrink-0" />
        </div>
        <p class="mt-2 text-base sm:text-lg font-black text-blue-900 dark:text-blue-100 truncate">
          {{ formatCurrency(stats.total_quoted_value) }}
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
            :placeholder="$t('quotation.searchPlaceholder')"
            @input="debouncedFetch"
            class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2 pe-4 ps-10 text-xs text-gray-900 focus:border-[#00C896] focus:bg-white focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <!-- Internal Status -->
          <select
            v-model="filters.status"
            @change="fetchData"
            class="rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs font-bold text-gray-700 focus:border-[#00C896] focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
          >
            <option value="">{{ $t('quotation.allStatuses') }} (الداخلية)</option>
            <option value="Draft">{{ $t('quotation.statusDraft') }}</option>
            <option value="Under Review">{{ $t('quotation.statusUnderReview') }}</option>
            <option value="Approved">{{ $t('quotation.statusApproved') }}</option>
            <option value="Superseded">{{ $t('quotation.statusSuperseded') }}</option>
          </select>

          <!-- Client Status -->
          <select
            v-model="filters.client_status"
            @change="fetchData"
            class="rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs font-bold text-gray-700 focus:border-[#00C896] focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
          >
            <option value="">كافة حالات العميل</option>
            <option value="Not Sent">{{ $t('quotation.clientStatusNotSent') }}</option>
            <option value="Sent">{{ $t('quotation.clientStatusSent') }}</option>
            <option value="Accepted">{{ $t('quotation.clientStatusAccepted') }}</option>
            <option value="Rejected">{{ $t('quotation.clientStatusRejected') }}</option>
            <option value="Expired">{{ $t('quotation.clientStatusExpired') }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900 overflow-hidden">
      <div v-if="loading" class="p-8 text-center text-xs text-gray-500">
        <div class="inline-block h-6 w-6 animate-spin rounded-full border-2 border-solid border-[#00C896] border-r-transparent"></div>
        <p class="mt-2">جاري تحميل عروض الأسعار...</p>
      </div>

      <div v-else-if="quotations.length === 0" class="p-12 text-center">
        <Receipt class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" />
        <h3 class="mt-3 text-sm font-bold text-gray-900 dark:text-white">{{ $t('quotation.noQuotations') }}</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('quotation.noQuotationsDesc') }}</p>
        <button
          v-if="canCreate"
          type="button"
          @click="openCreateModal(filterOpportunityId)"
          class="mt-4 px-4 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs cursor-pointer inline-flex items-center gap-2"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('quotation.createFirst') }}</span>
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-start text-xs text-gray-600 dark:text-gray-300">
          <thead class="bg-gray-50/75 text-[11px] font-black uppercase text-gray-500 dark:bg-gray-800/50 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
            <tr>
              <th class="py-3 px-4 text-start">{{ $t('quotation.colNumber') }}</th>
              <th class="py-3 px-4 text-start">{{ $t('quotation.colOpportunity') }}</th>
              <th class="py-3 px-4 text-start">{{ $t('quotation.colSourceEstimation') }}</th>
              <th class="py-3 px-4 text-end">{{ $t('quotation.colSubtotal') }}</th>
              <th class="py-3 px-4 text-end">{{ $t('quotation.colGrandTotal') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('quotation.colStatus') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('quotation.colClientStatus') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('quotation.colValidUntil') }}</th>
              <th class="py-3 px-4 text-center">الإجراءات</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="item in quotations"
              :key="item.id"
              class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors"
            >
              <!-- Number & Version -->
              <td class="py-3.5 px-4 font-mono font-bold text-gray-900 dark:text-white">
                <div class="flex items-center gap-1.5">
                  <span>{{ item.quotation_number }}</span>
                  <span class="px-1.5 py-0.5 rounded-md text-[10px] font-black bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    V{{ item.version }}
                  </span>
                </div>
              </td>

              <!-- Opportunity & Customer -->
              <td class="py-3.5 px-4">
                <div class="font-bold text-gray-900 dark:text-white truncate max-w-[200px]">
                  {{ item.opportunity?.title || '—' }}
                </div>
                <div class="text-[11px] text-gray-500 dark:text-gray-400 truncate max-w-[200px]">
                  {{ item.opportunity?.customer?.company_name || item.opportunity?.customer?.name || '—' }}
                </div>
              </td>

              <!-- Source Estimation -->
              <td class="py-3.5 px-4">
                <span class="inline-flex items-center gap-1 font-mono text-[11px] text-slate-600 dark:text-slate-300">
                  <Calculator class="h-3.5 w-3.5 text-slate-400" />
                  <span>{{ item.estimation?.estimation_number || '—' }}</span>
                  <span v-if="item.estimation?.version" class="text-[10px] text-slate-400">V{{ item.estimation.version }}</span>
                </span>
              </td>

              <!-- Subtotal -->
              <td class="py-3.5 px-4 text-end font-mono">
                {{ formatCurrency(item.subtotal) }}
              </td>

              <!-- Grand Total -->
              <td class="py-3.5 px-4 text-end font-mono font-black text-emerald-600 dark:text-emerald-400">
                {{ formatCurrency(item.grand_total) }}
              </td>

              <!-- Internal Status Badge -->
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-black"
                  :class="internalStatusBadgeClass(item.status)"
                >
                  {{ item.status }}
                </span>
              </td>

              <!-- Client Status Badge -->
              <td class="py-3.5 px-4 text-center">
                <span
                  class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black border"
                  :class="clientStatusBadgeClass(item.client_status)"
                >
                  {{ clientStatusLabel(item.client_status) }}
                </span>
              </td>

              <!-- Validity Date -->
              <td class="py-3.5 px-4 text-center font-mono text-[11px] text-gray-500">
                {{ item.valid_until || '—' }}
              </td>

              <!-- Actions -->
              <td class="py-3.5 px-4 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <!-- View Details Modal -->
                  <button
                    type="button"
                    @click="openDetailsModal(item.id)"
                    class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white transition-colors cursor-pointer"
                    :title="$t('common.view')"
                  >
                    <Eye class="h-4 w-4" />
                  </button>

                  <!-- Print Sheet -->
                  <button
                    type="button"
                    @click="printQuotation(item.id)"
                    class="p-1.5 rounded-lg hover:bg-teal-50 text-teal-600 hover:text-teal-800 dark:hover:bg-teal-950/40 transition-colors cursor-pointer"
                    :title="$t('quotation.print') || 'طباعة'"
                  >
                    <Printer class="h-4 w-4" />
                  </button>

                  <!-- Direct Action: Edit if Draft/Under Review -->
                  <button
                    v-if="canBeEdited(item)"
                    type="button"
                    @click="openEditModal(item.id)"
                    class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 hover:text-blue-800 dark:hover:bg-blue-950/40 transition-colors cursor-pointer"
                    :title="$t('common.edit')"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>

                  <!-- Secondary Workflow Actions Dropdown -->
                  <RowActionDropdown
                    v-if="item.status === 'Draft' || (canApprove && (item.status === 'Draft' || item.status === 'Under Review')) || (canSend && item.status === 'Approved' && item.client_status === 'Not Sent') || (item.client_status === 'Sent') || (canCreate && item.status === 'Approved') || (canDelete && canBeEdited(item))"
                    v-slot="{ close }"
                  >
                    <div class="py-1">
                      <!-- Submit Review -->
                      <button
                        v-if="item.status === 'Draft'"
                        type="button"
                        @click="close(); submitReview(item)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-amber-700 hover:bg-amber-50 dark:text-amber-400 dark:hover:bg-amber-950/40 transition-colors"
                      >
                        <Send class="h-3.5 w-3.5 text-amber-500" />
                        <span>{{ $t('quotation.submitReview') || 'تقديم للمراجعة الداخلية' }}</span>
                      </button>

                      <!-- Approve Internally -->
                      <button
                        v-if="canApprove && (item.status === 'Draft' || item.status === 'Under Review')"
                        type="button"
                        @click="close(); approveQuotation(item)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/40 transition-colors"
                      >
                        <ShieldCheck class="h-3.5 w-3.5 text-emerald-500" />
                        <span>{{ $t('quotation.approve') || 'اعتماد العرض داخلياً' }}</span>
                      </button>

                      <!-- Mark Sent to Client -->
                      <button
                        v-if="canSend && item.status === 'Approved' && item.client_status === 'Not Sent'"
                        type="button"
                        @click="close(); markSent(item)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-950/40 transition-colors"
                      >
                        <SendHorizonal class="h-3.5 w-3.5 text-blue-500" />
                        <span>{{ $t('quotation.markSent') || 'تسجيل إرسال العرض للعميل' }}</span>
                      </button>

                      <!-- Record Client Response -->
                      <button
                        v-if="item.client_status === 'Sent'"
                        type="button"
                        @click="close(); openResponseModal(item)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-purple-700 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40 transition-colors"
                      >
                        <CheckCircle2 class="h-3.5 w-3.5 text-purple-500" />
                        <span>{{ $t('quotation.recordResponse') || 'تسجيل رد العميل (قبول / رفض)' }}</span>
                      </button>

                      <!-- Create Revision -->
                      <button
                        v-if="canCreate && item.status === 'Approved'"
                        type="button"
                        @click="close(); createRevision(item)"
                        class="w-full flex items-center gap-2 px-3 py-2 text-start text-xs font-semibold text-purple-700 hover:bg-purple-50 dark:text-purple-400 dark:hover:bg-purple-950/40 transition-colors"
                      >
                        <History class="h-3.5 w-3.5 text-purple-500" />
                        <span>{{ $t('quotation.createRevision') || 'إنشاء مراجعة جديدة' }}</span>
                      </button>

                      <!-- Delete -->
                      <button
                        v-if="canDelete && canBeEdited(item)"
                        type="button"
                        @click="close(); confirmDelete(item)"
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
    </div>

    <!-- Create Quotation Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="relative w-full max-w-2xl rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2">
            <Receipt class="h-5 w-5 text-[#00C896]" />
            <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('quotation.createTitle') }}</h3>
          </div>
          <button @click="showCreateModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
            <X class="h-5 w-5" />
          </button>
        </div>

        <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
          <!-- Opportunity Selection -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('quotation.formOpportunity') }} <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="createForm.opportunity_id"
              @change="onOpportunitySelectedInCreate"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="" disabled>{{ $t('quotation.selectOpportunity') }}</option>
              <option v-for="opp in opportunities" :key="opp.id" :value="opp.id">
                #{{ opp.id }} — {{ opp.title }} ({{ opp.customer?.name || 'عميل' }})
              </option>
            </select>
          </div>

          <!-- Estimation Selection (Approved only) -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('quotation.formSourceEstimation') }} <span class="text-rose-500">*</span>
            </label>
            <div v-if="loadingEstimations" class="text-xs text-gray-400">جاري تحميل دراسات التكلفة المعتمدة...</div>
            <select
              v-else
              v-model="createForm.estimation_id"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="" disabled>{{ $t('quotation.selectEstimation') }}</option>
              <option v-for="est in availableEstimations" :key="est.id" :value="est.id">
                #{{ est.estimation_number }} (V{{ est.version }}) — {{ est.title || 'دراسة تسعير' }}
              </option>
            </select>
            <p v-if="availableEstimations.length === 0 && createForm.opportunity_id && !loadingEstimations" class="mt-1 text-[11px] text-amber-600 font-bold">
              ⚠️ لا توجد دراسة تسعير معتمدة (Approved) لهذه الفرصة حتى الآن. يجب اعتماد دراسة التسعير أولاً.
            </p>
          </div>

          <!-- Title -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formTitle') }}</label>
            <input
              v-model="createForm.title"
              type="text"
              placeholder="مثال: عرض سعر أعمال التشطيبات والديكور"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
          </div>

          <!-- Financial Rates -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formMarkupRate') }}</label>
              <input
                v-model.number="createForm.markup_rate"
                type="number"
                step="0.01"
                min="0"
                placeholder="20.00"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formTaxRate') }}</label>
              <input
                v-model.number="createForm.tax_rate"
                type="number"
                step="0.01"
                min="0"
                placeholder="15.00"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formValidUntil') }}</label>
              <input
                v-model="createForm.valid_until"
                type="date"
                class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white font-mono"
              />
            </div>
          </div>

          <!-- Payment Terms Text -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formPaymentTerms') }}</label>
            <textarea
              v-model="createForm.payment_terms_text"
              rows="2"
              placeholder="مثال: 50% دفعة مقدمة عند توقيع العقد، 40% عند توريد المواد، 10% عند التسليم النهائي."
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            ></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
            <button
              type="button"
              @click="showCreateModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-bold cursor-pointer"
            >
              إلغاء
            </button>
            <button
              type="submit"
              :disabled="saving || !createForm.estimation_id"
              class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
            >
              {{ saving ? 'جاري الإنشاء...' : $t('quotation.btnGenerate') }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Quotation Modal -->
    <div
      v-if="showEditModal && activeQuotation"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="relative w-full max-w-4xl max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div>
            <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
              <Pencil class="h-4 w-4 text-[#00C896]" />
              <span>تعديل عرض السعر #{{ activeQuotation.quotation_number }}</span>
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">تعديل نسب الهامش العام، أسعار البيع، والخصومات</p>
          </div>
          <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="my-4 overflow-y-auto grow space-y-4 pe-1 text-xs">
          <!-- Document Header Controls -->
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 p-3.5 rounded-xl bg-gray-50/75 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formMarkupRate') }}</label>
              <input
                v-model.number="editForm.markup_rate"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">الخصم المقطوع (ج.م)</label>
              <input
                v-model.number="editForm.discount_amount"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formTaxRate') }}</label>
              <input
                v-model.number="editForm.tax_rate"
                type="number"
                step="0.01"
                min="0"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800 font-mono"
              />
            </div>
            <div>
              <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formValidUntil') }}</label>
              <input
                v-model="editForm.valid_until"
                type="date"
                class="w-full rounded-xl border border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-800 font-mono"
              />
            </div>
          </div>

          <!-- Items Table -->
          <div class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <div class="bg-gray-50 dark:bg-gray-800 px-3 py-2 font-bold text-gray-700 dark:text-gray-300 flex justify-between items-center">
              <span>{{ $t('quotation.sellingLines') }} ({{ editForm.items.length }} بند)</span>
              <span class="text-[11px] text-gray-500 font-normal">يمكنك تحديد هامش ربح خاص بكل بند لتجاوز الهامش العام</span>
            </div>
            <table class="w-full text-xs text-start">
              <thead class="bg-gray-50/50 text-[11px] font-bold text-gray-500 border-b border-gray-100 dark:border-gray-800">
                <tr>
                  <th class="py-2 px-3 text-start">الكود</th>
                  <th class="py-2 px-3 text-start">البند والتوصيف</th>
                  <th class="py-2 px-3 text-center">الوحدة</th>
                  <th class="py-2 px-3 text-center">الكمية</th>
                  <th class="py-2 px-3 text-center">الهامش الخاص (%)</th>
                  <th class="py-2 px-3 text-end">سعر بيع الوحدة</th>
                  <th class="py-2 px-3 text-end">الإجمالي</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="line in editForm.items" :key="line.id">
                  <td class="py-2 px-3 font-mono font-bold">{{ line.item_code }}</td>
                  <td class="py-2 px-3">
                    <p class="font-medium text-gray-900 dark:text-white line-clamp-2">{{ line.description }}</p>
                  </td>
                  <td class="py-2 px-3 text-center font-mono">{{ line.unit }}</td>
                  <td class="py-2 px-3 text-center font-mono font-bold">{{ line.quantity }}</td>
                  <td class="py-2 px-3 text-center">
                    <input
                      v-model.number="line.markup_rate"
                      type="number"
                      step="0.01"
                      placeholder="عام"
                      class="w-20 text-center rounded-lg border border-gray-200 p-1 dark:border-gray-700 dark:bg-gray-800 font-mono"
                    />
                  </td>
                  <td class="py-2 px-3 text-end font-mono">
                    {{ formatCurrency(line.unit_selling_price) }}
                  </td>
                  <td class="py-2 px-3 text-end font-mono font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(line.line_total) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Payment Terms -->
          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">{{ $t('quotation.formPaymentTerms') }}</label>
            <textarea
              v-model="editForm.payment_terms_text"
              rows="2"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
          <div class="text-xs text-gray-500">
            سيتم إعادة احتساب المجاميع والضرائب تلقائياً بالسيرفر فور الحفظ.
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="showEditModal = false"
              class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-bold cursor-pointer"
            >
              إلغاء
            </button>
            <button
              type="button"
              @click="saveQuotation"
              :disabled="saving"
              class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
            >
              {{ saving ? 'جاري الحفظ...' : 'حفظ التعديلات' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div
      v-if="showDetailsModal && activeQuotation"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="relative w-full max-w-4xl max-h-[90vh] flex flex-col rounded-2xl bg-white shadow-xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 p-6">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
          <div>
            <div class="flex items-center gap-2">
              <h3 class="text-base font-black text-gray-900 dark:text-white font-mono">
                #{{ activeQuotation.quotation_number }}
              </h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black" :class="internalStatusBadgeClass(activeQuotation.status)">
                {{ activeQuotation.status }}
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-black border" :class="clientStatusBadgeClass(activeQuotation.client_status)">
                {{ clientStatusLabel(activeQuotation.client_status) }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mt-1">
              {{ activeQuotation.opportunity?.title }} — العميل: {{ activeQuotation.opportunity?.customer?.name }}
            </p>
          </div>
          <button @click="showDetailsModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="my-4 overflow-y-auto grow space-y-4 pe-1 text-xs">
          <!-- Summary Cards -->
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
              <span class="text-[11px] text-gray-500 block">المجموع قبل الخصم</span>
              <span class="text-sm font-bold font-mono">{{ formatCurrency(activeQuotation.subtotal) }}</span>
            </div>
            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
              <span class="text-[11px] text-gray-500 block">قيمة الخصم</span>
              <span class="text-sm font-bold font-mono text-rose-600">-{{ formatCurrency(activeQuotation.discount_value) }}</span>
            </div>
            <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
              <span class="text-[11px] text-gray-500 block">الضريبة ({{ activeQuotation.tax_rate }}%)</span>
              <span class="text-sm font-bold font-mono">+{{ formatCurrency(activeQuotation.tax_amount) }}</span>
            </div>
            <div class="p-3 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800/40">
              <span class="text-[11px] text-emerald-800 dark:text-emerald-300 block font-bold">الإجمالي النهائي</span>
              <span class="text-base font-black font-mono text-emerald-600 dark:text-emerald-400">{{ formatCurrency(activeQuotation.grand_total) }}</span>
            </div>
          </div>

          <!-- Items Table -->
          <div class="rounded-xl border border-gray-100 dark:border-gray-800 overflow-hidden">
            <table class="w-full text-xs text-start">
              <thead class="bg-gray-50 dark:bg-gray-800 text-[11px] font-bold text-gray-500">
                <tr>
                  <th class="py-2 px-3 text-start">#</th>
                  <th class="py-2 px-3 text-start">البند والتوصيف</th>
                  <th class="py-2 px-3 text-center">الوحدة</th>
                  <th class="py-2 px-3 text-center">الكمية</th>
                  <th class="py-2 px-3 text-end">سعر البيع</th>
                  <th class="py-2 px-3 text-end">الإجمالي</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="(line, idx) in activeQuotation.items" :key="line.id">
                  <td class="py-2 px-3 font-mono text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-2 px-3 font-medium">{{ line.description }}</td>
                  <td class="py-2 px-3 text-center font-mono">{{ line.unit }}</td>
                  <td class="py-2 px-3 text-center font-mono font-bold">{{ line.quantity }}</td>
                  <td class="py-2 px-3 text-end font-mono">{{ formatCurrency(line.unit_selling_price) }}</td>
                  <td class="py-2 px-3 text-end font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(line.line_total) }}</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Payment Terms -->
          <div v-if="activeQuotation.payment_terms_text" class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800">
            <span class="font-bold text-gray-700 dark:text-gray-300 block mb-1">شروط الدفع والتعاقد:</span>
            <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ activeQuotation.payment_terms_text }}</p>
          </div>

          <!-- Client Response Notes -->
          <div v-if="activeQuotation.client_response_notes" class="p-3 rounded-xl bg-purple-50 dark:bg-purple-950/20 border border-purple-200 dark:border-purple-800/40">
            <span class="font-bold text-purple-900 dark:text-purple-300 block mb-1">ملاحظات رد العميل:</span>
            <p class="text-purple-800 dark:text-purple-200">{{ activeQuotation.client_response_notes }}</p>
          </div>
        </div>

        <div class="flex items-center justify-between pt-3 border-t border-gray-100 dark:border-gray-800 shrink-0">
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="printQuotation(activeQuotation.id)"
              class="px-4 py-2 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-xs font-bold shadow-xs cursor-pointer inline-flex items-center gap-1.5"
            >
              <Printer class="h-4 w-4" />
              <span>طباعة عرض السعر</span>
            </button>
          </div>
          <button
            type="button"
            @click="showDetailsModal = false"
            class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-bold cursor-pointer"
          >
            إغلاق
          </button>
        </div>
      </div>
    </div>

    <!-- Record Response Modal -->
    <div
      v-if="showResponseModal && activeQuotation"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <h3 class="text-sm font-black text-gray-900 dark:text-white">تسجيل رد العميل الرسمي</h3>
          <button @click="showResponseModal = false" class="text-gray-400 hover:text-gray-600 cursor-pointer">
            <X class="h-5 w-5" />
          </button>
        </div>

        <div class="space-y-3 text-xs">
          <p class="text-gray-600 dark:text-gray-400">
            حدد قرار العميل النهائي بشأن عرض السعر رقم #{{ activeQuotation.quotation_number }}:
          </p>

          <div class="grid grid-cols-2 gap-3">
            <button
              type="button"
              @click="responseForm.response = 'Accepted'"
              class="p-3 rounded-xl border text-center font-bold transition-all cursor-pointer"
              :class="responseForm.response === 'Accepted' ? 'border-emerald-500 bg-emerald-50 text-emerald-800 font-black' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
            >
              ✅ قبول العرض (Accepted)
            </button>
            <button
              type="button"
              @click="responseForm.response = 'Rejected'"
              class="p-3 rounded-xl border text-center font-bold transition-all cursor-pointer"
              :class="responseForm.response === 'Rejected' ? 'border-rose-500 bg-rose-50 text-rose-800 font-black' : 'border-gray-200 text-gray-700 hover:bg-gray-50'"
            >
              ❌ رفض العرض (Rejected)
            </button>
          </div>

          <div>
            <label class="block font-bold text-gray-700 dark:text-gray-300 mb-1">ملاحظات وأسباب الرد (اختياري)</label>
            <textarea
              v-model="responseForm.notes"
              rows="3"
              placeholder="اكتب تعليق العميل أو أسباب الرفض أو الشروط المطلوبة..."
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            ></textarea>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
          <button
            type="button"
            @click="showResponseModal = false"
            class="px-4 py-2 rounded-xl border border-gray-200 text-gray-700 hover:bg-gray-50 text-xs font-bold cursor-pointer"
          >
            إلغاء
          </button>
          <button
            type="button"
            @click="submitResponse"
            :disabled="saving"
            class="px-5 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs cursor-pointer disabled:opacity-50"
          >
            {{ saving ? 'جاري التسجيل...' : 'تأكيد وحفظ القرار' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Official Customer-Facing Printable Quotation Sheet (Teleported for Print) -->
    <div
      v-if="printData"
      id="official-printable-quotation"
      class="hidden print:block text-slate-900 bg-white p-8 max-w-4xl mx-auto"
    >
      <!-- Header -->
      <div class="flex items-center justify-between border-b-2 border-slate-900 pb-4 mb-6">
        <div>
          <h1 class="text-2xl font-black tracking-wider text-slate-900">صَرْح للتشييد والتشطيبات</h1>
          <p class="text-xs text-slate-500 font-semibold mt-0.5">SARH Construction & Finishing ERP</p>
        </div>
        <div class="text-end font-mono">
          <div class="text-lg font-black text-slate-900">{{ printData.quotation_number }}</div>
          <div class="text-xs text-slate-600">التاريخ: {{ printData.prepared_at || '—' }}</div>
          <div class="text-xs text-slate-600">صالح حتى: {{ printData.valid_until || '—' }}</div>
        </div>
      </div>

      <!-- Client & Opportunity Box -->
      <div class="grid grid-cols-2 gap-4 p-4 rounded-lg bg-slate-50 border border-slate-200 text-xs mb-6">
        <div>
          <span class="text-slate-500 font-bold block mb-1">السيد / السادة المحترمين:</span>
          <div class="text-sm font-black text-slate-900">
            {{ printData.opportunity?.customer?.company_name || printData.opportunity?.customer?.name }}
          </div>
          <div class="text-slate-600 mt-0.5">الهاتف: {{ printData.opportunity?.customer?.phone || '—' }}</div>
        </div>
        <div>
          <span class="text-slate-500 font-bold block mb-1">المشروع / العملية:</span>
          <div class="text-sm font-black text-slate-900">{{ printData.opportunity?.title }}</div>
          <div class="text-slate-600 mt-0.5">{{ printData.title }}</div>
        </div>
      </div>

      <!-- Items Table (strictly zero cost columns) -->
      <table class="w-full text-xs text-start border border-slate-300 mb-6">
        <thead class="bg-slate-100 text-slate-900 font-bold border-b border-slate-300">
          <tr>
            <th class="py-2.5 px-3 text-center border-e border-slate-300 w-12">#</th>
            <th class="py-2.5 px-3 text-start border-e border-slate-300">بيان الأعمال والمواصفات</th>
            <th class="py-2.5 px-3 text-center border-e border-slate-300 w-16">الوحدة</th>
            <th class="py-2.5 px-3 text-center border-e border-slate-300 w-20">الكمية</th>
            <th class="py-2.5 px-3 text-end border-e border-slate-300 w-28">سعر الفئة (ج.م)</th>
            <th class="py-2.5 px-3 text-end w-32">الإجمالي (ج.م)</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
          <tr v-for="(line, idx) in printData.items" :key="line.id" class="border-b border-slate-200">
            <td class="py-2 px-3 text-center font-mono border-e border-slate-200">{{ idx + 1 }}</td>
            <td class="py-2 px-3 border-e border-slate-200">
              <span class="font-bold text-slate-900 block">{{ line.description }}</span>
              <span v-if="line.item_code" class="text-[10px] text-slate-400 font-mono">كود البند: {{ line.item_code }}</span>
            </td>
            <td class="py-2 px-3 text-center font-mono border-e border-slate-200">{{ line.unit }}</td>
            <td class="py-2 px-3 text-center font-mono font-bold border-e border-slate-200">{{ line.quantity }}</td>
            <td class="py-2 px-3 text-end font-mono border-e border-slate-200">{{ formatCurrency(line.unit_selling_price) }}</td>
            <td class="py-2 px-3 text-end font-mono font-bold">{{ formatCurrency(line.line_total) }}</td>
          </tr>
        </tbody>
      </table>

      <!-- Financial Totals Block -->
      <div class="flex justify-end mb-6">
        <div class="w-72 border border-slate-300 rounded-lg overflow-hidden text-xs">
          <div class="flex justify-between px-3 py-2 border-b border-slate-200 bg-slate-50">
            <span class="font-bold text-slate-600">المجموع الفرعي:</span>
            <span class="font-mono font-bold">{{ formatCurrency(printData.subtotal) }}</span>
          </div>
          <div v-if="Number(printData.discount_value) > 0" class="flex justify-between px-3 py-2 border-b border-slate-200">
            <span class="font-bold text-rose-600">الخصم التجاري:</span>
            <span class="font-mono font-bold text-rose-600">-{{ formatCurrency(printData.discount_value) }}</span>
          </div>
          <div class="flex justify-between px-3 py-2 border-b border-slate-200 bg-slate-50">
            <span class="font-bold text-slate-600">ضريبة القيمة المضافة ({{ printData.tax_rate }}%):</span>
            <span class="font-mono font-bold">{{ formatCurrency(printData.tax_amount) }}</span>
          </div>
          <div class="flex justify-between px-3 py-2.5 bg-slate-900 text-white">
            <span class="font-black text-sm">الإجمالي النهائي:</span>
            <span class="font-mono font-black text-sm">{{ formatCurrency(printData.grand_total) }}</span>
          </div>
        </div>
      </div>

      <!-- Payment & Commercial Terms -->
      <div v-if="printData.payment_terms_text" class="border border-slate-200 rounded-lg p-4 text-xs mb-8">
        <h4 class="font-black text-slate-900 mb-2">شروط الدفع والتعاقد:</h4>
        <p class="text-slate-700 leading-relaxed whitespace-pre-line">{{ printData.payment_terms_text }}</p>
      </div>

      <!-- Signatures Box -->
      <div class="grid grid-cols-2 gap-8 pt-8 border-t border-slate-300 text-xs text-center">
        <div>
          <p class="font-black text-slate-900 mb-12">إدارة المبيعات والعقود (صَرْح ERP)</p>
          <div class="border-b border-dashed border-slate-400 w-48 mx-auto"></div>
          <p class="text-[11px] text-slate-500 mt-2">التوقيع والخاتم الرسمي</p>
        </div>
        <div>
          <p class="font-black text-slate-900 mb-12">موافقة وقبول العميل</p>
          <div class="border-b border-dashed border-slate-400 w-48 mx-auto"></div>
          <p class="text-[11px] text-slate-500 mt-2">الاسم والتوقيع بالتاريخ</p>
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
  Receipt,
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
  SendHorizonal,
  History,
  ShieldCheck,
  Printer,
  X,
  Calculator,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// Permissions
const canCreate = computed(() => authStore.hasPermission(['quotations.create']));
const canApprove = computed(() => authStore.hasPermission(['quotations.approve']));
const canUpdate = computed(() => authStore.hasPermission(['quotations.update']));
const canSend = computed(() => authStore.hasPermission(['quotations.send']));
const canDelete = computed(() => authStore.hasPermission(['quotations.delete']));

const filterOpportunityId = computed(() => route.query.opportunity_id || '');

const loading = ref(false);
const saving = ref(false);
const quotations = ref([]);

const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  sent: 0,
  accepted: 0,
  rejected: 0,
  total_quoted_value: 0,
});

const filters = reactive({
  search: '',
  status: '',
  client_status: '',
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
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showResponseModal = ref(false);
const activeQuotation = ref(null);
const printData = ref(null);

const opportunities = ref([]);
const availableEstimations = ref([]);
const loadingEstimations = ref(false);

const createForm = reactive({
  opportunity_id: '',
  estimation_id: '',
  title: '',
  markup_rate: 20.00,
  tax_rate: 15.00,
  valid_until: '',
  payment_terms_text: '',
});

const editForm = reactive({
  markup_rate: 20.00,
  discount_amount: 0.00,
  tax_rate: 15.00,
  valid_until: '',
  payment_terms_text: '',
  items: [],
});

const responseForm = reactive({
  response: 'Accepted',
  notes: '',
});

const canBeEdited = (item) => item && inArray(item.status, ['Draft', 'Under Review']);
const inArray = (val, arr) => arr.includes(val);

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

const clientStatusBadgeClass = (status) => {
  switch (status) {
    case 'Not Sent':
      return 'border-gray-200 text-gray-500 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400';
    case 'Sent':
      return 'border-blue-300 text-blue-700 bg-blue-50/50 dark:border-blue-700 dark:bg-blue-950/30 dark:text-blue-300';
    case 'Accepted':
      return 'border-emerald-300 text-emerald-700 bg-emerald-50 dark:border-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300 font-black';
    case 'Rejected':
      return 'border-rose-300 text-rose-700 bg-rose-50 dark:border-rose-700 dark:bg-rose-950/30 dark:text-rose-300';
    case 'Expired':
      return 'border-amber-300 text-amber-700 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/30 dark:text-amber-300';
    default:
      return 'border-gray-200 text-gray-600';
  }
};

const clientStatusLabel = (status) => {
  switch (status) {
    case 'Not Sent':
      return 'لم يُرسل بعد';
    case 'Sent':
      return 'أُرسل للعميل';
    case 'Accepted':
      return 'مقبول من العميل';
    case 'Rejected':
      return 'مرفوض';
    case 'Expired':
      return 'منتهي الصلاحية';
    default:
      return status;
  }
};

const formatCurrency = (val) => {
  const num = Number(val || 0);
  const symbol = authStore.tenant?.currency_symbol || 'ج.م';
  return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ' + symbol;
};

const clearOpportunityFilter = () => {
  router.push({ path: '/quotations', query: {} });
  filters.opportunity_id = '';
  fetchData();
};

const fetchData = async () => {
  loading.value = true;
  try {
    const params = {
      search: filters.search || undefined,
      status: filters.status || undefined,
      client_status: filters.client_status || undefined,
      opportunity_id: filters.opportunity_id || undefined,
    };
    const res = await api.get('/quotations', { params });
    if (res.data?.success) {
      quotations.value = res.data.data || [];
      if (res.data.stats) {
        Object.assign(stats, res.data.stats);
      }
    }
  } catch (err) {
    console.error('Error loading quotations:', err);
  } finally {
    loading.value = false;
  }
};

const fetchOpportunities = async () => {
  try {
    const res = await api.get('/opportunities?per_page=100');
    if (res.data?.success) {
      opportunities.value = res.data.data || [];
    }
  } catch (err) {
    console.error('Error fetching opportunities:', err);
  }
};

const onOpportunitySelectedInCreate = async () => {
  if (!createForm.opportunity_id) return;
  loadingEstimations.value = true;
  availableEstimations.value = [];
  createForm.estimation_id = '';
  try {
    const res = await api.get(`/estimations?opportunity_id=${createForm.opportunity_id}&status=Approved`);
    if (res.data?.success) {
      availableEstimations.value = res.data.data || [];
      if (availableEstimations.value.length > 0) {
        createForm.estimation_id = availableEstimations.value[0].id;
      }
    }
  } catch (err) {
    console.error('Error loading estimations for opportunity:', err);
  } finally {
    loadingEstimations.value = false;
  }
};

const openCreateModal = async (oppId = '') => {
  createForm.opportunity_id = oppId || '';
  createForm.estimation_id = '';
  createForm.title = '';
  createForm.markup_rate = 20.00;
  createForm.tax_rate = 15.00;
  createForm.payment_terms_text = '50% دفعة مقدمة عند توقيع العقد، 40% عند توريد المواد، 10% عند التسليم النهائي.';
  const d = new Date();
  d.setDate(d.getDate() + 30);
  createForm.valid_until = d.toISOString().split('T')[0];

  await fetchOpportunities();
  if (oppId) {
    await onOpportunitySelectedInCreate();
  }
  showCreateModal.value = true;
};

const submitCreate = async () => {
  if (!createForm.opportunity_id || !createForm.estimation_id) return;
  saving.value = true;
  try {
    const res = await api.post('/quotations', createForm);
    if (res.data?.success) {
      showCreateModal.value = false;
      await fetchData();
      alertService.showSuccess({
        title: 'تم إنشاء عرض السعر بنجاح',
        text: 'تم توليد أسعار البيع استناداً لدراسة التكلفة ونسبة الهامش المحددة.',
      });
      if (res.data.data?.id) {
        openEditModal(res.data.data.id);
      }
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'تعذر إنشاء عرض السعر';
    alertService.showError({ title: 'خطأ', text: msg });
  } finally {
    saving.value = false;
  }
};

const openEditModal = async (id) => {
  try {
    const res = await api.get(`/quotations/${id}`);
    if (res.data?.success) {
      activeQuotation.value = res.data.data;
      editForm.markup_rate = Number(activeQuotation.value.markup_rate || 20);
      editForm.discount_amount = Number(activeQuotation.value.discount_amount || 0);
      editForm.tax_rate = Number(activeQuotation.value.tax_rate || 15);
      editForm.valid_until = activeQuotation.value.valid_until || '';
      editForm.payment_terms_text = activeQuotation.value.payment_terms_text || '';
      editForm.items = (activeQuotation.value.items || []).map((i) => ({
        id: i.id,
        item_code: i.item_code,
        description: i.description,
        unit: i.unit,
        quantity: i.quantity,
        markup_rate: i.markup_rate !== null ? Number(i.markup_rate) : null,
        unit_selling_price: i.unit_selling_price,
        line_total: i.line_total,
      }));
      showEditModal.value = true;
    }
  } catch (err) {
    console.error('Error loading quotation for edit:', err);
  }
};

const saveQuotation = async () => {
  if (!activeQuotation.value) return;
  saving.value = true;
  try {
    const payload = {
      markup_rate: editForm.markup_rate,
      discount_amount: editForm.discount_amount,
      tax_rate: editForm.tax_rate,
      valid_until: editForm.valid_until,
      payment_terms_text: editForm.payment_terms_text,
      items: editForm.items.map((i) => ({
        id: i.id,
        markup_rate: i.markup_rate !== null && i.markup_rate !== '' ? Number(i.markup_rate) : null,
      })),
    };
    const res = await api.put(`/quotations/${activeQuotation.value.id}`, payload);
    if (res.data?.success) {
      showEditModal.value = false;
      await fetchData();
      alertService.showSuccess({ title: 'تم حفظ وتحديث عرض السعر بنجاح' });
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'تعذر حفظ التعديلات';
    alertService.showError({ title: 'خطأ', text: msg });
  } finally {
    saving.value = false;
  }
};

const openDetailsModal = async (id) => {
  try {
    const res = await api.get(`/quotations/${id}`);
    if (res.data?.success) {
      activeQuotation.value = res.data.data;
      showDetailsModal.value = true;
    }
  } catch (err) {
    console.error('Error loading details:', err);
  }
};

const printQuotation = async (id) => {
  try {
    const res = await api.get(`/quotations/${id}`);
    if (res.data?.success) {
      printData.value = res.data.data;
      setTimeout(() => {
        window.print();
      }, 250);
    }
  } catch (err) {
    console.error('Error preparing print:', err);
  }
};

// Workflow Actions
const submitReview = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'تقديم عرض السعر للمراجعة الداخلية؟',
    text: `سيتم تحويل عرض السعر #${row.quotation_number} لحالة قيد المراجعة للتدقيق الإداري والتجاري.`,
    icon: 'question',
    confirmButtonText: 'نعم، تقديم للمراجعة',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/quotations/${row.id}/submit-review`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم تقديم عرض السعر للمراجعة بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر تقديم العرض للمراجعة';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const approveQuotation = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'اعتماد عرض السعر داخلياً؟',
    text: `سيتم اعتماد العرض #${row.quotation_number} رسمياً وقفل تعديل أسعاره تمهيداً لإرساله للعميل.`,
    icon: 'warning',
    confirmButtonText: 'نعم، اعتماد رسمي',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/quotations/${row.id}/approve`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم اعتماد عرض السعر بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر اعتماد عرض السعر';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const markSent = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'تسجيل إرسال عرض السعر للعميل؟',
    text: `سيتم توثيق إرسال العرض رقم #${row.quotation_number} للعميل وبدء فترة سريان العرض.`,
    icon: 'info',
    confirmButtonText: 'نعم، تم الإرسال',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/quotations/${row.id}/mark-sent`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم توثيق إرسال العرض للعميل بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر توثيق إرسال العرض';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

const openResponseModal = (row) => {
  activeQuotation.value = row;
  responseForm.response = 'Accepted';
  responseForm.notes = '';
  showResponseModal.value = true;
};

const submitResponse = async () => {
  if (!activeQuotation.value) return;
  saving.value = true;
  try {
    const res = await api.post(`/quotations/${activeQuotation.value.id}/record-response`, responseForm);
    if (res.data?.success) {
      showResponseModal.value = false;
      await fetchData();
      alertService.showSuccess({ title: 'تم تسجيل قرار العميل بنجاح' });
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'تعذر تسجيل الرد';
    alertService.showError({ title: 'خطأ', text: msg });
  } finally {
    saving.value = false;
  }
};

const createRevision = async (row) => {
  const confirmed = await alertService.confirmAction({
    title: 'إنشاء مراجعة وإصدار جديد من عرض السعر؟',
    text: `سيتم إنشاء مسودة جديدة V${row.version + 1} مستنسخة من النسخة المعتمدة الحالية للتعديل عليها.`,
    icon: 'info',
    confirmButtonText: 'إنشاء مراجعة',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.post(`/quotations/${row.id}/create-revision`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم إنشاء الإصدار الجديد بنجاح' });
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
    title: 'حذف مسودة عرض السعر؟',
    text: `هل أنت متأكد من حذف مسودة عرض السعر رقم #${row.quotation_number}؟ لا يمكن التراجع عن هذا الإجراء.`,
    confirmButtonText: 'نعم، حذف',
    cancelButtonText: 'إلغاء',
  });

  if (confirmed) {
    try {
      const res = await api.delete(`/quotations/${row.id}`);
      if (res.data?.success) {
        await fetchData();
        alertService.showSuccess({ title: 'تم الحذف بنجاح' });
      }
    } catch (err) {
      const msg = err.response?.data?.message || 'تعذر حذف عرض السعر';
      alertService.showError({ title: 'خطأ', text: msg });
    }
  }
};

onMounted(() => {
  fetchData();
});
</script>

<style>
@media print {
  body * {
    visibility: hidden;
  }
  #official-printable-quotation,
  #official-printable-quotation * {
    visibility: visible;
  }
  #official-printable-quotation {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 20mm;
    background: white !important;
    color: black !important;
  }
}
</style>
