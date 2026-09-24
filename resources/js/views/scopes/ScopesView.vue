<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2.5">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
            <ClipboardList class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">
              {{ $t('scopes.title') }}
            </h1>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
              {{ $t('scopes.subtitle') }}
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
          <span>{{ $t('scopes.createTitle') }}</span>
        </button>
      </div>
    </div>

    <!-- Zero Pricing & Business Governance Notice Banner -->
    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/20 flex items-start gap-3.5">
      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400 mt-0.5">
        <ShieldCheck class="h-4 w-4" />
      </div>
      <div class="flex-1 text-xs">
        <div class="flex items-center gap-2">
          <span class="font-black text-blue-950 dark:text-blue-200">محددات نطاق الأعمال (Scope of Work Governance):</span>
          <span class="rounded-md bg-blue-200/60 px-2 py-0.5 text-[10px] font-black text-blue-800 dark:bg-blue-900 dark:text-blue-300">
            Zero Pricing Standard
          </span>
        </div>
        <p class="text-blue-800/80 dark:text-blue-300/80 mt-1 leading-relaxed">
          نطاق الأعمال مخصص لحزم ومواصفات العمل الفنية، الاشتمالات، والاستثناءات التعاقدية، ومبني حصرًا على <strong>مقايسة هندسية معتمدة (Approved Measurement)</strong>. التسعير والتكاليف وهوامش الربح مسؤولية مرحلة الـ BOQ والـ Estimation اللاحقة.
        </p>
      </div>
    </div>

    <!-- Active Opportunity Filter Banner -->
    <div v-if="filterOpportunityId" class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
      <div class="flex items-center gap-2.5 text-xs text-emerald-900 dark:text-emerald-300">
        <div class="p-1.5 rounded-lg bg-[#00C896] text-white">
          <ClipboardList class="h-4 w-4" />
        </div>
        <div>
          <p class="font-bold">{{ $t('scopes.filterByOpportunity') }} #{{ filterOpportunityId }}</p>
          <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400">يتم تصفية وثائق نطاق الأعمال للفرصة المحددة فقط.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="openCreateModal(filterOpportunityId)"
          class="px-3 py-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          + إنشاء نطاق أعمال لهذه الفرصة
        </button>
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          عرض كل النطاقات
        </button>
      </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('scopes.totalScopes') }}</span>
          <Layers class="h-4 w-4 text-gray-400" />
        </div>
        <p class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ $t('scopes.draftScopes') }}</span>
          <FileText class="h-4 w-4 text-slate-400" />
        </div>
        <p class="mt-2 text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $t('scopes.underReviewScopes') }}</span>
          <Clock class="h-4 w-4 text-amber-500" />
        </div>
        <p class="mt-2 text-xl font-black text-amber-900 dark:text-amber-300">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">{{ $t('scopes.approvedScopes') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500" />
        </div>
        <p class="mt-2 text-xl font-black text-emerald-900 dark:text-emerald-300">{{ stats.approved }}</p>
      </div>

      <div class="col-span-2 sm:col-span-1 rounded-2xl border border-teal-100 bg-teal-50/40 p-4 shadow-xs dark:border-teal-900/30 dark:bg-teal-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-teal-700 dark:text-teal-400">{{ $t('scopes.totalItemsCount') }}</span>
          <CheckSquare class="h-4 w-4 text-teal-500" />
        </div>
        <p class="mt-2 text-xl font-black text-teal-900 dark:text-teal-300">{{ stats.total_items_count }}</p>
      </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="flex flex-col gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative flex-1">
        <Search class="absolute right-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
        <input
          v-model="searchQuery"
          @input="debounceFetch"
          type="text"
          :placeholder="$t('common.searchPlaceholder')"
          class="w-full rounded-xl border border-gray-200 bg-gray-50/50 py-2 pr-10 pl-4 text-xs text-gray-900 outline-none focus:border-[#00C896] focus:bg-white dark:border-gray-800 dark:bg-gray-800 dark:text-white dark:focus:bg-gray-900"
        />
      </div>

      <div class="w-48">
        <SearchableSelect
          :model-value="selectedStatus"
          :options="statusFilterOptions"
          :placeholder="$t('scopes.filterAll')"
          :allow-empty="true"
          :clearable="true"
          @update:model-value="onStatusFilterChange"
        />
      </div>
    </div>

    <!-- Scopes Table -->
    <!-- Bulk Actions Bar for Scopes -->
    <div
      v-if="selectedScopeIds.length > 0"
      class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3.5 mb-4 rounded-2xl bg-rose-50/90 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 shadow-xs animate-in fade-in duration-200"
    >
      <div class="flex items-center gap-2.5">
        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-rose-600 text-white font-mono font-black text-xs shadow-2xs">
          {{ selectedScopeIds.length }}
        </span>
        <div>
          <p class="text-xs font-black text-rose-950 dark:text-rose-200">
            تم تحديد {{ selectedScopeIds.length }} نطاق عمل من الجدول
          </p>
          <p class="text-[11px] text-rose-700/80 dark:text-rose-400">
            يمكنك حذف نطاقات العمل المحددة (المسودات وقيد المراجعة). النطاقات المعتمدة محمية للحفاظ على سلامة بنود الأعمال التعاقدية.
          </p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="confirmBulkDeleteScopes"
          class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 hover:bg-rose-700 px-4 py-2 text-xs font-black text-white shadow-xs transition-colors cursor-pointer"
        >
          <Trash2 class="h-3.5 w-3.5" />
          <span>حذف المحدد (Delete Selected)</span>
        </button>

        <button
          type="button"
          @click="selectedScopeIds = []"
          class="inline-flex items-center gap-1 rounded-xl border border-rose-200 bg-white dark:bg-gray-800 dark:border-rose-900/50 px-3 py-2 text-xs font-bold text-rose-700 dark:text-rose-300 hover:bg-rose-100 transition-colors cursor-pointer"
        >
          <X class="h-3.5 w-3.5" />
          <span>إلغاء التحديد</span>
        </button>
      </div>
    </div>

    <!-- Scopes Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
      <div v-if="loading" class="flex h-64 items-center justify-center">
        <Loader2 class="h-8 w-8 animate-spin text-[#00C896]" />
      </div>

      <div v-else-if="scopes.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-50 text-gray-400 dark:bg-gray-800/60">
          <ClipboardList class="h-8 w-8" />
        </div>
        <h3 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.noData') }}</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('common.noDataDesc') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="border-b border-gray-100 bg-gray-50/75 text-gray-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
            <tr>
              <th class="py-3.5 px-4 w-10">
                <input
                  type="checkbox"
                  :checked="isAllScopesSelected"
                  @change="toggleSelectAllScopes"
                  class="rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896] cursor-pointer"
                />
              </th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.scopeNumber') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.opportunity') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.basedOnMeasurement') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.scopeTitle') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.status') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('scopes.items') }}</th>
              <th class="py-3.5 px-4 font-bold text-left">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="item in scopes"
              :key="item.id"
              class="group hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors"
            >
              <td class="py-3.5 px-4 w-10">
                <input
                  type="checkbox"
                  :value="item.id"
                  v-model="selectedScopeIds"
                  class="rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896] cursor-pointer"
                />
              </td>
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span class="font-black text-gray-900 dark:text-white">{{ item.scope_number }}</span>
                  <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    V{{ item.version }}
                  </span>
                </div>
                <span class="text-[11px] text-gray-400">{{ item.created_at?.slice(0, 10) }}</span>
              </td>

              <td class="py-3.5 px-4">
                <p class="font-bold text-gray-900 dark:text-white">{{ item.opportunity?.title || '—' }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ item.opportunity?.customer?.display_name || item.opportunity?.customer?.name || '—' }}</p>
              </td>

              <td class="py-3.5 px-4">
                <div v-if="item.measurement" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2 py-1 text-[11px] font-bold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                  <Ruler class="h-3 w-3 text-emerald-600" />
                  <span>{{ item.measurement.measurement_number }} (V{{ item.measurement.version }})</span>
                </div>
                <span v-else class="text-gray-400">—</span>
              </td>

              <td class="py-3.5 px-4 max-w-xs truncate">
                <span class="font-medium text-gray-800 dark:text-gray-200">{{ item.title || '—' }}</span>
              </td>

              <td class="py-3.5 px-4">
                <span
                  class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-bold"
                  :class="{
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': item.status === 'Draft',
                    'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800': item.status === 'Under Review',
                    'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800': item.status === 'Approved',
                    'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300 border border-purple-200 dark:border-purple-800': item.status === 'Superseded',
                  }"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="{
                    'bg-slate-500': item.status === 'Draft',
                    'bg-amber-500': item.status === 'Under Review',
                    'bg-emerald-500': item.status === 'Approved',
                    'bg-purple-500': item.status === 'Superseded',
                  }"></span>
                  {{ item.status }}
                </span>
              </td>

              <td class="py-3.5 px-4">
                <div class="flex items-center gap-1 text-gray-700 dark:text-gray-300 font-bold">
                  <span>{{ item.items?.length || 0 }}</span>
                  <span class="text-[11px] font-normal text-gray-400">حزم عمل</span>
                </div>
              </td>

              <td class="py-3.5 px-4 text-left">
                <div class="flex items-center justify-end gap-1">
                  <!-- View Details Button -->
                  <button
                    @click="openDetailsModal(item)"
                    title="عرض وتدقيق نطاق العمل"
                    class="rounded-lg p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:hover:bg-gray-800 dark:hover:text-white transition-colors cursor-pointer"
                  >
                    <Eye class="h-4 w-4" />
                  </button>

                  <!-- Quick Print Button -->
                  <button
                    @click="quickPrintScope(item)"
                    title="طباعة وثيقة نطاق الأعمال الرسمية"
                    class="rounded-lg p-1.5 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition-colors cursor-pointer"
                  >
                    <Printer class="h-4 w-4" />
                  </button>

                  <!-- Edit (Draft or Under Review only) -->
                  <button
                    v-if="item.status === 'Draft' || item.status === 'Under Review'"
                    @click="openEditModal(item)"
                    title="تعديل النطاق والمواصفات"
                    class="rounded-lg p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 transition-colors cursor-pointer"
                  >
                    <Edit3 class="h-4 w-4" />
                  </button>

                  <!-- Submit for Review (Draft only) -->
                  <button
                    v-if="item.status === 'Draft'"
                    @click="submitForReview(item)"
                    title="تقديم للمراجعة الفنية"
                    class="rounded-lg p-1.5 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 transition-colors cursor-pointer"
                  >
                    <Send class="h-4 w-4" />
                  </button>

                  <!-- Approve (Draft or Under Review) -->
                  <button
                    v-if="(item.status === 'Draft' || item.status === 'Under Review') && canApprove"
                    @click="approveScope(item)"
                    title="اعتماد نطاق العمل رسمياً"
                    class="rounded-lg p-1.5 text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 transition-colors cursor-pointer"
                  >
                    <Check class="h-4 w-4" />
                  </button>

                  <!-- Create Revision (Approved only) -->
                  <button
                    v-if="item.status === 'Approved' && canCreate"
                    @click="createRevision(item)"
                    title="إنشاء إصدار جديد (Revision)"
                    class="rounded-lg p-1.5 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition-colors cursor-pointer"
                  >
                    <GitBranch class="h-4 w-4" />
                  </button>

                  <!-- Delete (Draft or Under Review only) -->
                  <button
                    v-if="item.status === 'Draft' || item.status === 'Under Review'"
                    @click="deleteScope(item)"
                    title="حذف النطاق"
                    class="rounded-lg p-1.5 text-rose-500 hover:bg-rose-50 hover:text-rose-700 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Create / Edit Scope Modal -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-3 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative flex h-[94vh] w-full max-w-6xl flex-col rounded-3xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
              <ClipboardList class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ isEditing ? $t('scopes.editTitle') : $t('scopes.createTitle') }}
              </h3>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                ربط المواصفات وحزم الأعمال ببنود المقايسة المعتمدة (Zero Pricing).
              </p>
            </div>
          </div>

          <button
            @click="showEditModal = false"
            class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Form Body -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <!-- Opportunity & Measurement Selectors -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                {{ $t('scopes.opportunity') }} <span class="text-rose-500">*</span>
              </label>
              <SearchableSelect
                :model-value="form.opportunity_id"
                :options="formattedOpportunityOptions"
                :placeholder="$t('scopes.selectOpportunity')"
                :disabled="isEditing"
                @update:model-value="onOpportunitySelect"
              />
            </div>

            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                {{ $t('scopes.basedOnMeasurement') }} <span class="text-rose-500">*</span>
              </label>
              <SearchableSelect
                :model-value="form.measurement_id"
                :options="formattedMeasurementOptions"
                :placeholder="$t('scopes.selectMeasurement')"
                :disabled="isEditing || !form.opportunity_id"
                @update:model-value="onMeasurementSelect"
              />
              <p v-if="form.opportunity_id && formattedMeasurementOptions.length === 0" class="mt-1.5 text-[11px] text-amber-600 font-bold">
                ⚠️ لا توجد مقايسة معتمدة (Approved) لهذه الفرصة. يلزم اعتماد مقايسة أولاً.
              </p>
            </div>
          </div>

          <!-- Scope Title & Extra Info -->
          <div class="grid grid-cols-1 gap-4">
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                {{ $t('scopes.scopeTitle') }} <span class="text-rose-500">*</span>
              </label>
              <input
                v-model="form.title"
                type="text"
                :placeholder="$t('scopes.scopeTitlePlaceholder')"
                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              />
            </div>
          </div>

          <!-- General Inclusions & General Exclusions -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="mb-1.5 flex items-center gap-1 text-xs font-bold text-gray-700 dark:text-gray-300">
                <CheckCircle2 class="h-3.5 w-3.5 text-emerald-600" />
                <span>{{ $t('scopes.generalInclusions') }}</span>
              </label>
              <textarea
                v-model="form.general_inclusions"
                rows="3"
                :placeholder="$t('scopes.generalInclusionsPlaceholder')"
                class="w-full rounded-xl border border-gray-200 bg-white p-3 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              ></textarea>
            </div>

            <div>
              <label class="mb-1.5 flex items-center gap-1 text-xs font-bold text-rose-700 dark:text-rose-400">
                <AlertCircle class="h-3.5 w-3.5 text-rose-600" />
                <span>{{ $t('scopes.generalExclusions') }}</span>
              </label>
              <textarea
                v-model="form.general_exclusions"
                rows="3"
                :placeholder="$t('scopes.generalExclusionsPlaceholder')"
                class="w-full rounded-xl border border-rose-200 bg-rose-50/30 p-3 text-xs text-gray-900 outline-none focus:border-rose-500 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-white"
              ></textarea>
            </div>
          </div>

          <!-- Scope Items Builder (Work Packages) -->
          <div class="space-y-3">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="text-xs font-black text-gray-900 dark:text-white">
                  {{ $t('scopes.items') }}
                </h4>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">
                  {{ $t('scopes.itemsDesc') }}
                </p>
              </div>

              <div class="flex items-center gap-2">
                <button
                  type="button"
                  @click="addScopeItemRow"
                  class="inline-flex items-center gap-1.5 rounded-xl bg-gray-900 px-3 py-1.5 text-xs font-bold text-white hover:bg-black dark:bg-gray-800 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                >
                  <Plus class="h-3.5 w-3.5" />
                  <span>{{ $t('scopes.addItem') }}</span>
                </button>
              </div>
            </div>

            <!-- Items List -->
            <div v-if="form.items.length === 0" class="p-8 text-center rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800">
              <ClipboardList class="h-8 w-8 mx-auto text-gray-300" />
              <p class="mt-2 text-xs font-bold text-gray-500">{{ $t('scopes.emptyItemsDesc') }}</p>
            </div>

            <div v-else class="space-y-4">
              <div
                v-for="(item, idx) in form.items"
                :key="idx"
                class="p-4 rounded-2xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-3"
              >
                <!-- Item Header Row -->
                <div class="flex items-center justify-between gap-3 border-b border-gray-100 pb-2.5 dark:border-gray-800">
                  <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-gray-100 text-xs font-black text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                      {{ idx + 1 }}
                    </span>
                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200">
                      {{ item.item_name || 'بند عمل جديد' }}
                    </span>
                  </div>

                  <button
                    type="button"
                    @click="removeScopeItemRow(idx)"
                    class="rounded-lg p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 cursor-pointer"
                  >
                    <Trash2 class="h-4 w-4" />
                  </button>
                </div>

                <!-- Fields Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                  <!-- Trade Category -->
                  <div>
                    <label class="mb-1 block text-[11px] font-bold text-gray-600 dark:text-gray-400">
                      {{ $t('scopes.tradeCategory') }} <span class="text-rose-500">*</span>
                    </label>
                    <SearchableSelect
                      :model-value="item.trade_category"
                      :options="tradeCategoryOptions"
                      :placeholder="$t('scopes.selectTradeCategory')"
                      :allow-empty="false"
                      @update:model-value="item.trade_category = $event"
                    />
                  </div>

                  <!-- Item Title -->
                  <div class="sm:col-span-2">
                    <label class="mb-1 block text-[11px] font-bold text-gray-600 dark:text-gray-400">
                      {{ $t('scopes.itemTitle') }} <span class="text-rose-500">*</span>
                    </label>
                    <input
                      v-model="item.item_name"
                      type="text"
                      :placeholder="$t('scopes.itemTitlePlaceholder')"
                      class="w-full rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                  </div>
                </div>

                <!-- Detailed Specification -->
                <div>
                  <label class="mb-1 block text-[11px] font-bold text-gray-600 dark:text-gray-400">
                    {{ $t('scopes.specification') }} <span class="text-rose-500">*</span>
                  </label>
                  <textarea
                    v-model="item.specification"
                    rows="2"
                    :placeholder="$t('scopes.specificationPlaceholder')"
                    class="w-full rounded-xl border border-gray-200 bg-white p-2.5 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                  ></textarea>
                </div>

                <!-- Specific Inclusions & Exclusions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <label class="mb-1 block text-[11px] font-bold text-emerald-700 dark:text-emerald-400">
                      {{ $t('scopes.inclusions') }}
                    </label>
                    <input
                      v-model="item.inclusions"
                      type="text"
                      :placeholder="$t('scopes.inclusionsPlaceholder')"
                      class="w-full rounded-xl border border-emerald-200 bg-white px-3 py-1.5 text-xs text-gray-900 outline-none focus:border-emerald-500 dark:border-emerald-900/50 dark:bg-gray-900 dark:text-white"
                    />
                  </div>

                  <div>
                    <label class="mb-1 block text-[11px] font-bold text-rose-700 dark:text-rose-400">
                      {{ $t('scopes.exclusions') }}
                    </label>
                    <input
                      v-model="item.exclusions"
                      type="text"
                      :placeholder="$t('scopes.exclusionsPlaceholder')"
                      class="w-full rounded-xl border border-rose-200 bg-white px-3 py-1.5 text-xs text-gray-900 outline-none focus:border-rose-500 dark:border-rose-900/50 dark:bg-gray-900 dark:text-white"
                    />
                  </div>
                </div>

                <!-- Linked Measurement Items Linker -->
                <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                      <Ruler class="h-3.5 w-3.5 text-[#00C896]" />
                      <span>{{ $t('scopes.linkedMeasurementItems') }}</span>
                    </span>
                    <span class="text-[10px] font-black text-emerald-700 dark:text-emerald-400 bg-emerald-100/60 dark:bg-emerald-950 px-2 py-0.5 rounded-md">
                      {{ (item.measurement_item_ids || []).length }} بنود قياس مرتبطة
                    </span>
                  </div>

                  <!-- Checkboxes / Selector for Available Measurement Items -->
                  <div v-if="selectedMeasurementItemsList.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-1 custom-scrollbar">
                    <label
                      v-for="mItem in selectedMeasurementItemsList"
                      :key="mItem.id"
                      class="flex items-start gap-2 p-2 rounded-lg border text-[11px] transition-all cursor-pointer"
                      :class="(item.measurement_item_ids || []).includes(mItem.id)
                        ? 'border-emerald-300 bg-emerald-50/70 text-emerald-950 font-bold dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200'
                        : 'border-gray-200 bg-white text-gray-700 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300'"
                    >
                      <input
                        type="checkbox"
                        :value="mItem.id"
                        :checked="(item.measurement_item_ids || []).includes(mItem.id)"
                        @change="toggleMeasurementItemLink(item, mItem.id)"
                        class="rounded border-gray-300 text-[#00C896] focus:ring-[#00C896] mt-0.5"
                      />
                      <div class="flex-1 min-w-0">
                        <p class="truncate font-semibold">{{ mItem.room_name }} - {{ mItem.item_name }}</p>
                        <p class="text-[10px] text-gray-400 font-mono">{{ mItem.net_quantity }} {{ mItem.unit }}</p>
                      </div>
                    </label>
                  </div>
                  <div v-else class="text-[11px] text-gray-400 text-center py-2">
                    يرجى اختيار مقايسة معتمدة أولاً لعرض بنود القياس المتاحة للربط.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
              {{ $t('scopes.notes') }}
            </label>
            <textarea
              v-model="form.notes"
              rows="2"
              :placeholder="$t('scopes.notesPlaceholder')"
              class="w-full rounded-xl border border-gray-200 bg-white p-3 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            ></textarea>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/80 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
          <p class="text-[11px] text-gray-500 font-bold">
            عدد بنود النطاق: {{ form.items.length }} بند
          </p>

          <div class="flex items-center gap-2.5">
            <button
              @click="showEditModal = false"
              class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
            >
              {{ $t('common.cancel') }}
            </button>

            <button
              :disabled="submitting"
              @click="saveScope"
              class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] disabled:opacity-50 transition-colors cursor-pointer"
            >
              <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
              <Save v-else class="h-4 w-4" />
              <span>{{ $t('common.save') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Review & Details Modal (Printable Sheet) -->
    <div
      v-if="showDetailsModal && selectedScope"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-3 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative flex h-[94vh] w-full max-w-5xl flex-col rounded-3xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        <!-- Details Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
              <CheckCircle2 v-if="selectedScope.status === 'Approved'" class="h-5 w-5" />
              <ClipboardList v-else class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">
                  وثيقة نطاق العمل #{{ selectedScope.scope_number }}
                </h3>
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                  الإصدار V{{ selectedScope.version }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ selectedScope.opportunity?.title }} ({{ selectedScope.opportunity?.customer?.display_name || selectedScope.opportunity?.customer?.name }})
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="printScopeSheet"
              class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
              <Printer class="h-3.5 w-3.5" />
              <span>{{ $t('scopes.printScopeAction') }}</span>
            </button>

            <button
              @click="showDetailsModal = false"
              class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Details Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6">
          <!-- Status Notice Banner -->
          <div
            v-if="selectedScope.status === 'Approved'"
            class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-800 dark:bg-emerald-950/20"
          >
            <div class="flex items-start gap-3">
              <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-black text-emerald-900 dark:text-emerald-300">{{ $t('scopes.approvedNotice') }}</h4>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                  تم الاعتماد بواسطة: {{ selectedScope.approved_user?.name || 'المهندس المسؤول' }} بتاريخ {{ selectedScope.approved_at?.slice(0, 16) || '—' }}.
                </p>
              </div>
            </div>
          </div>

          <div
            v-else-if="selectedScope.status === 'Superseded'"
            class="rounded-2xl border border-purple-200 bg-purple-50/60 p-4 dark:border-purple-800 dark:bg-purple-950/20"
          >
            <div class="flex items-start gap-3">
              <History class="h-5 w-5 text-purple-600 shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-black text-purple-900 dark:text-purple-300">{{ $t('scopes.supersededNotice') }}</h4>
                <p class="text-[11px] text-purple-700 dark:text-purple-400 mt-0.5">
                  هذا إصدار تاريخي تم استبداله بإصدار أحدث معتمد لنفس الفرصة التجارية.
                </p>
              </div>
            </div>
          </div>

          <!-- Scope General Overview -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-gray-50/75 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800">
            <div>
              <span class="text-[11px] text-gray-400 font-bold">عنوان حزمة العمل:</span>
              <p class="text-xs font-black text-gray-900 dark:text-white mt-0.5">{{ selectedScope.title || '—' }}</p>
            </div>
            <div>
              <span class="text-[11px] text-gray-400 font-bold">المقايسة الهندسية المرجعية:</span>
              <p class="text-xs font-black text-emerald-700 dark:text-emerald-400 mt-0.5">
                {{ selectedScope.measurement?.measurement_number }} (إصدار V{{ selectedScope.measurement?.version }} - معتمدة)
              </p>
            </div>
          </div>

          <!-- Inclusions & Exclusions Highlights -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="p-4 rounded-2xl bg-emerald-50/40 border border-emerald-100 dark:bg-emerald-950/20 dark:border-emerald-900/40">
              <h4 class="text-xs font-black text-emerald-950 dark:text-emerald-300 flex items-center gap-1.5 mb-2">
                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                <span>الاشتمالات والالتزامات العامة:</span>
              </h4>
              <p class="text-xs text-emerald-900 dark:text-emerald-300 leading-relaxed whitespace-pre-line">
                {{ selectedScope.general_inclusions || 'لا توجد اشتمالات عامة مدونة.' }}
              </p>
            </div>

            <div class="p-4 rounded-2xl bg-rose-50/40 border border-rose-100 dark:bg-rose-950/20 dark:border-rose-900/40">
              <h4 class="text-xs font-black text-rose-950 dark:text-rose-300 flex items-center gap-1.5 mb-2">
                <AlertCircle class="h-4 w-4 text-rose-600" />
                <span>الاستثناءات والحدود التعاقدية (مستثنى):</span>
              </h4>
              <p class="text-xs text-rose-900 dark:text-rose-300 leading-relaxed whitespace-pre-line">
                {{ selectedScope.general_exclusions || 'لا توجد استثناءات عامة مدونة.' }}
              </p>
            </div>
          </div>

          <!-- Scope Work Items Table -->
          <div>
            <h4 class="mb-3 text-xs font-black text-gray-900 dark:text-white">
              حزم العمل والمواصفات الفنية التفصيلية ({{ selectedScope.items?.length || 0 }} بند)
            </h4>
            <div class="space-y-4">
              <div
                v-for="(it, i) in selectedScope.items"
                :key="it.id"
                class="p-4 rounded-2xl border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-xs space-y-2.5"
              >
                <div class="flex items-start justify-between gap-3">
                  <div class="flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700 text-xs font-black dark:bg-emerald-950 dark:text-emerald-300">
                      {{ i + 1 }}
                    </span>
                    <div>
                      <h5 class="text-xs font-black text-gray-900 dark:text-white">{{ it.item_name }}</h5>
                      <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                        {{ it.trade_category }}
                      </span>
                    </div>
                  </div>
                </div>

                <!-- Spec -->
                <div class="p-3 rounded-xl bg-gray-50/70 dark:bg-gray-800/40 text-xs">
                  <span class="font-bold text-gray-500 dark:text-gray-400 block mb-1">المواصفة الفنية وطريقة التنفيذ:</span>
                  <p class="text-gray-800 dark:text-gray-200 leading-relaxed whitespace-pre-line">{{ it.specification }}</p>
                </div>

                <!-- Inclusions & Exclusions -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                  <div v-if="it.inclusions" class="text-emerald-800 dark:text-emerald-300">
                    <span class="font-bold">المتضمن:</span> {{ it.inclusions }}
                  </div>
                  <div v-if="it.exclusions" class="text-rose-800 dark:text-rose-300">
                    <span class="font-bold">المستثنى:</span> {{ it.exclusions }}
                  </div>
                </div>

                <!-- Linked Measurement Items -->
                <div v-if="it.measurement_items && it.measurement_items.length > 0" class="pt-2 border-t border-gray-100 dark:border-gray-800/60">
                  <span class="text-[11px] font-bold text-gray-500 block mb-1.5">بنود القياس والحصر المرتبطة بهذا التوصيف:</span>
                  <div class="flex flex-wrap gap-1.5">
                    <span
                      v-for="m in it.measurement_items"
                      :key="m.id"
                      class="inline-flex items-center gap-1.5 rounded-lg bg-teal-50 px-2 py-1 text-[10px] font-bold text-teal-800 dark:bg-teal-950/40 dark:text-teal-300 border border-teal-200 dark:border-teal-900/40"
                    >
                      <Ruler class="h-3 w-3 text-teal-600" />
                      <span>{{ m.room_name }} - {{ m.item_name }} ({{ m.net_quantity }} {{ m.unit }})</span>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Notes -->
          <div v-if="selectedScope.notes" class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 text-xs">
            <span class="font-bold text-gray-500 block mb-1">ملاحظات وشروط إضافية:</span>
            <p class="text-gray-800 dark:text-gray-200 leading-relaxed">{{ selectedScope.notes }}</p>
          </div>

          <!-- Workflow Signatures -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800 text-xs">
            <div class="p-3 rounded-xl bg-gray-50/60 dark:bg-gray-800/30">
              <span class="text-gray-400 font-bold block mb-1">إعداد المواصفات:</span>
              <p class="font-black text-gray-900 dark:text-white">{{ selectedScope.prepared_user?.name || selectedScope.creator?.name || '—' }}</p>
              <span class="text-[10px] text-gray-400">{{ selectedScope.prepared_at || selectedScope.created_at?.slice(0, 16) }}</span>
            </div>

            <div class="p-3 rounded-xl bg-gray-50/60 dark:bg-gray-800/30">
              <span class="text-gray-400 font-bold block mb-1">المراجعة الفنية:</span>
              <p class="font-black text-gray-900 dark:text-white">{{ selectedScope.reviewed_user?.name || '—' }}</p>
              <span class="text-[10px] text-gray-400">{{ selectedScope.reviewed_at || '—' }}</span>
            </div>

            <div class="p-3 rounded-xl bg-gray-50/60 dark:bg-gray-800/30">
              <span class="text-gray-400 font-bold block mb-1">الاعتماد النهائي:</span>
              <p class="font-black text-emerald-700 dark:text-emerald-400">{{ selectedScope.approved_user?.name || '—' }}</p>
              <span class="text-[10px] text-gray-400">{{ selectedScope.approved_at || '—' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================ -->
    <!-- OFFICIAL CORPORATE PRINTABLE DOCUMENT (Visible ONLY on print) -->
    <!-- ============================================================ -->
    <div v-if="selectedScope" id="official-printable-scope" class="hidden text-slate-900 bg-white">
      <!-- 1. Corporate Header / Letterhead -->
      <div class="border-b-2 border-slate-900 pb-4 mb-4">
        <div class="flex items-start justify-between gap-4">
          <!-- Right: Company Info -->
          <div class="text-right flex-1">
            <h1 class="text-base font-black text-slate-900 tracking-tight">
              {{ authStore.tenant?.name || 'شركة الصرح للمقاولات العامة والتشطيبات' }}
            </h1>
            <p class="text-[11px] font-bold text-slate-600 mt-0.5">
              إدارة المكتب الفني وحساب الكميات • قسم العقود والتوصيف الهندسي
            </p>
            <p class="text-[10px] text-slate-500 font-mono mt-0.5">
              سجل تجاري: 1084920 | بطاقة ضريبية: 492-381-092 | بنها - القليوبية
            </p>
          </div>

          <!-- Center: Document Title & Badges -->
          <div class="text-center px-4 shrink-0">
            <div class="inline-block border-2 border-slate-900 bg-slate-50 px-4 py-1.5 rounded-lg shadow-2xs">
              <h2 class="text-sm font-black text-slate-900">وثيقة نطاق الأعمال والاشتراطات الفنية</h2>
              <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Approved Technical Scope of Work (SOW)</p>
            </div>
            <div class="mt-1.5 flex items-center justify-center gap-2 text-[10px]">
              <span class="font-mono font-bold text-slate-800">كود: {{ selectedScope.scope_number }}</span>
              <span class="text-slate-400">•</span>
              <span
                class="font-bold px-2 py-0.5 rounded text-[10px]"
                :class="selectedScope.status === 'Approved' ? 'text-emerald-800 bg-emerald-100 border border-emerald-300' : 'text-amber-800 bg-amber-100 border border-amber-300'"
              >
                {{ selectedScope.status === 'Approved' ? 'معتمد رسمياً (Approved)' : selectedScope.status }}
              </span>
              <span class="text-slate-400">•</span>
              <span class="font-bold text-slate-700">إصدار: V{{ selectedScope.version }}</span>
            </div>
          </div>

          <!-- Left: Logo & Print Date -->
          <div class="text-left flex flex-col items-end shrink-0">
            <div class="flex items-center gap-2 border border-slate-400 rounded-lg px-2.5 py-1 bg-slate-50">
              <div class="h-6 w-6 rounded bg-emerald-700 text-white flex items-center justify-center font-black text-xs">
                S
              </div>
              <span class="font-black text-xs text-slate-800 font-mono tracking-wider">SARH ERP</span>
            </div>
            <div class="mt-2 text-[10px] text-slate-500 font-mono text-left space-y-0.5">
              <p>تاريخ الإعداد: {{ selectedScope.prepared_at || selectedScope.created_at?.slice(0, 10) }}</p>
              <p v-if="selectedScope.approved_at">تاريخ الاعتماد: {{ selectedScope.approved_at?.slice(0, 10) }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Project & Commercial Identification Table -->
      <div class="mb-4 border border-slate-400 rounded-lg overflow-hidden text-xs">
        <div class="bg-slate-100 px-3 py-1.5 border-b border-slate-300 flex items-center justify-between font-bold">
          <span class="text-[11px] font-black text-slate-800">بيانات العملية والارتباط التجاري والهندسي:</span>
          <span class="text-[10px] text-slate-500 font-mono">Commercial & Engineering Scope File</span>
        </div>
        <div class="grid grid-cols-2 divide-x divide-x-reverse divide-y divide-slate-300">
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">الفرصة / المشروع:</span>
            <span class="font-black text-slate-900">{{ selectedScope.opportunity?.title || '—' }}</span>
          </div>
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">الطرف الثاني (العميل):</span>
            <span class="font-black text-slate-900">
              {{ selectedScope.opportunity?.customer?.name || '—' }}
              <span v-if="selectedScope.opportunity?.customer?.company_name" class="font-normal text-slate-600">
                ({{ selectedScope.opportunity?.customer?.company_name }})
              </span>
            </span>
          </div>
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">المقايسة المرجعية:</span>
            <span class="font-black text-emerald-800">
              مقايسة رقم #{{ selectedScope.measurement?.measurement_number }}
              (الإصدار V{{ selectedScope.measurement?.version }} - معتمدة فنياً)
            </span>
          </div>
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">إجمالي المسطحات:</span>
            <span class="font-black text-slate-900">
              {{ Number(selectedScope.measurement?.total_area || 0).toFixed(2) }} متر مربع (م²)
            </span>
          </div>
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">عنوان نطاق العمل:</span>
            <span class="font-black text-slate-900">{{ selectedScope.title || '—' }}</span>
          </div>
          <div class="p-2 flex items-start gap-2">
            <span class="font-bold text-slate-600 min-w-24">مسؤولية الاعتماد:</span>
            <span class="font-black text-slate-900">
              إعداد: {{ selectedScope.prepared_user?.name || selectedScope.creator?.name || 'مكتب فني' }} |
              اعتماد: {{ selectedScope.approved_user?.name || 'الإدارة الهندسية' }}
            </span>
          </div>
        </div>
      </div>

      <!-- 3. General Inclusions & Exclusions (Contract Boundaries) -->
      <div class="grid grid-cols-2 gap-3 mb-4 print-avoid-break">
        <!-- Inclusions -->
        <div class="border border-emerald-700/60 rounded-lg p-2.5 bg-emerald-50/25">
          <div class="flex items-center gap-1.5 border-b border-emerald-600/30 pb-1 mb-1.5">
            <span class="h-2 w-2 rounded-full bg-emerald-700"></span>
            <h3 class="text-xs font-black text-emerald-950">الاشتمالات والالتزامات العامة (المتضمن تعاقدياً):</h3>
          </div>
          <p class="text-[11px] leading-relaxed text-slate-800 whitespace-pre-line text-justify">
            {{ selectedScope.general_inclusions || 'لا توجد اشتمالات خاصة مدونة.' }}
          </p>
        </div>

        <!-- Exclusions -->
        <div class="border border-rose-700/60 rounded-lg p-2.5 bg-rose-50/25">
          <div class="flex items-center gap-1.5 border-b border-rose-600/30 pb-1 mb-1.5">
            <span class="h-2 w-2 rounded-full bg-rose-700"></span>
            <h3 class="text-xs font-black text-rose-950">الاستثناءات والحدود العامة (خارج نطاق التعاقد):</h3>
          </div>
          <p class="text-[11px] leading-relaxed text-slate-800 whitespace-pre-line text-justify">
            {{ selectedScope.general_exclusions || 'لا توجد استثناءات مدونة.' }}
          </p>
        </div>
      </div>

      <!-- 4. Detailed Technical Specifications Table -->
      <div class="mb-4">
        <div class="bg-slate-900 text-white px-3 py-1.5 rounded-t-lg flex items-center justify-between">
          <h3 class="text-xs font-black">جدول حزم وبنود الأعمال والمواصفات الفنية التفصيلية ({{ selectedScope.items?.length || 0 }} بند)</h3>
          <span class="text-[10px] text-slate-300 font-mono">Detailed Work Breakdown & Specifications</span>
        </div>

        <table class="w-full text-right border-collapse border border-slate-400 text-[11px]">
          <thead>
            <tr class="bg-slate-100 text-slate-900 border-b border-slate-400 font-bold">
              <th class="p-2 border-l border-slate-400 text-center w-8">#</th>
              <th class="p-2 border-l border-slate-400 w-44">بند العمل والتصنيف</th>
              <th class="p-2 border-l border-slate-400">المواصفة الفنية التفصيلية وطريقة التنفيذ</th>
              <th class="p-2 border-l border-slate-400 w-48">الاشتمالات والاستثناءات</th>
              <th class="p-2 w-48">بنود الحصر والمقايسة المرتبطة</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="(it, idx) in selectedScope.items"
              :key="it.id"
              class="border-b border-slate-400 align-top print-avoid-break"
              :class="idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'"
            >
              <!-- 1. Index -->
              <td class="p-2 border-l border-slate-400 text-center font-bold font-mono">
                {{ idx + 1 }}
              </td>

              <!-- 2. Item Name & Trade -->
              <td class="p-2 border-l border-slate-400">
                <p class="font-black text-slate-900 text-xs leading-snug">{{ it.item_name }}</p>
                <span class="inline-block mt-1 bg-slate-200 text-slate-800 text-[9px] font-bold px-1.5 py-0.5 rounded border border-slate-300">
                  {{ it.trade_category }}
                </span>
                <p v-if="it.notes" class="mt-1 text-[10px] text-slate-500 italic">ملاحظة: {{ it.notes }}</p>
              </td>

              <!-- 3. Technical Spec -->
              <td class="p-2 border-l border-slate-400 leading-relaxed text-slate-800 whitespace-pre-line text-justify">
                {{ it.specification || 'حسب أصول الصناعة ومواصفات الكود.' }}
              </td>

              <!-- 4. Inclusions & Exclusions -->
              <td class="p-2 border-l border-slate-400 text-[10px] space-y-1.5">
                <div v-if="it.inclusions" class="bg-emerald-50 p-1.5 rounded border border-emerald-300">
                  <span class="font-bold text-emerald-900 block">المتضمن بالبند:</span>
                  <span class="text-slate-800 leading-snug block mt-0.5">{{ it.inclusions }}</span>
                </div>
                <div v-if="it.exclusions" class="bg-rose-50 p-1.5 rounded border border-rose-300">
                  <span class="font-bold text-rose-900 block">المستثنى من البند:</span>
                  <span class="text-slate-800 leading-snug block mt-0.5">{{ it.exclusions }}</span>
                </div>
                <span v-if="!it.inclusions && !it.exclusions" class="text-slate-400 italic">طبقاً للمواصفات العامة</span>
              </td>

              <!-- 5. Linked Measurement Items -->
              <td class="p-2">
                <div v-if="it.measurement_items && it.measurement_items.length > 0" class="space-y-1">
                  <div
                    v-for="m in it.measurement_items"
                    :key="m.id"
                    class="border border-slate-300 rounded p-1 bg-slate-50 text-[10px]"
                  >
                    <div class="flex items-center justify-between gap-1">
                      <span class="font-bold text-slate-800 truncate">{{ m.room_name }}</span>
                      <span class="font-mono font-black text-slate-900 text-[9px] bg-white border border-slate-400 px-1 rounded">
                        {{ Number(m.net_quantity) }} {{ m.unit }}
                      </span>
                    </div>
                    <p class="text-[9px] text-slate-600 truncate mt-0.5">{{ m.item_name }}</p>
                  </div>
                </div>
                <span v-else class="text-slate-400 text-[10px] italic">غير مرتبط ببنود مقايسة مفردة</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- 5. General Contractual Conditions -->
      <div v-if="selectedScope.notes" class="mb-4 border border-slate-400 rounded-lg p-2.5 bg-slate-50/60 print-avoid-break">
        <h4 class="text-xs font-black text-slate-900 mb-1">ملاحظات واشتراطات إضافية:</h4>
        <p class="text-[10px] leading-relaxed text-slate-700">{{ selectedScope.notes }}</p>
      </div>

      <!-- 6. Engineering Standards & Handover -->
      <div class="mb-4 border border-slate-400 rounded-lg p-2.5 bg-slate-50/80 print-avoid-break">
        <h4 class="text-xs font-black text-slate-900 mb-1">الاشتراطات الهندسية وأصول الصناعة والتسليم:</h4>
        <ol class="list-decimal list-inside text-[10px] leading-relaxed text-slate-700 space-y-0.5">
          <li>تعتبر هذه الوثيقة المرجع الفني والهندسي الملزم لتحديد حدود ومواصفات الأعمال وطريقة تنفيذها.</li>
          <li>تلتزم جهة التنفيذ بتقديم عينات معتمدة لجميع المواد والخامات لمهندس الإشراف قبل التوريد أو التركيب.</li>
          <li>كافة القياسات والكميات المذكورة مبنية على المقايسة الهندسية المعتمدة رقم (#{{ selectedScope.measurement?.measurement_number }} - V{{ selectedScope.measurement?.version }}).</li>
          <li>لا يعتد بأي تعديلات في نطاق الأعمال إلا بموجب ملحق رسمي وإصدار جديد معتمد (New Scope Revision).</li>
        </ol>
      </div>

      <!-- 7. Official Signatures Block -->
      <div class="border border-slate-400 rounded-lg p-3 bg-white print-avoid-break mb-3">
        <h4 class="text-center text-xs font-black text-slate-900 mb-2.5 border-b border-slate-200 pb-1">
          الاعتماد والتوثيق التعاقدي الرسمي لوثيقة نطاق الأعمال
        </h4>
        <div class="grid grid-cols-4 gap-2.5 text-center text-[10px]">
          <!-- 1. Technical Office -->
          <div class="border border-slate-300 rounded p-2 bg-slate-50/50">
            <span class="font-bold text-slate-500 block mb-1">إعداد المكتب الفني:</span>
            <p class="font-black text-slate-900 text-[11px] mb-5">{{ selectedScope.prepared_user?.name || selectedScope.creator?.name || 'مهندس المكتب الفني' }}</p>
            <div class="border-t border-dashed border-slate-400 pt-1 text-[9px] text-slate-400">التوقيع والتاريخ</div>
          </div>

          <!-- 2. Project Manager -->
          <div class="border border-slate-300 rounded p-2 bg-slate-50/50">
            <span class="font-bold text-slate-500 block mb-1">مراجعة إدارة المشروعات:</span>
            <p class="font-black text-slate-900 text-[11px] mb-5">{{ selectedScope.reviewed_user?.name || 'مدير المشروعات' }}</p>
            <div class="border-t border-dashed border-slate-400 pt-1 text-[9px] text-slate-400">التوقيع والتاريخ</div>
          </div>

          <!-- 3. Approved Executive -->
          <div class="border border-slate-300 rounded p-2 bg-slate-50/50 relative overflow-hidden">
            <span class="font-bold text-slate-500 block mb-1">الاعتماد الفني والهندسي:</span>
            <p class="font-black text-emerald-900 text-[11px] mb-5">{{ selectedScope.approved_user?.name || 'الإدارة الهندسية' }}</p>
            <!-- Stamp simulation -->
            <div v-if="selectedScope.status === 'Approved'" class="absolute inset-x-0 bottom-5 flex justify-center opacity-90 pointer-events-none">
              <div class="border-2 border-emerald-700 text-emerald-800 font-black text-[9px] px-2 py-0.5 rounded uppercase rotate-[-6deg] bg-white/95 shadow-2xs">
                معتمد رسمياً • APPROVED
              </div>
            </div>
            <div class="border-t border-dashed border-slate-400 pt-1 text-[9px] text-slate-400">التوقيع والختم</div>
          </div>

          <!-- 4. Client Acceptance -->
          <div class="border border-slate-300 rounded p-2 bg-slate-50/50">
            <span class="font-bold text-slate-500 block mb-1">موافقة الطرف الثاني (العميل):</span>
            <p class="font-black text-slate-900 text-[11px] mb-5">{{ selectedScope.opportunity?.customer?.name || 'السيد العميل' }}</p>
            <div class="border-t border-dashed border-slate-400 pt-1 text-[9px] text-slate-400">التوقيع والصفة</div>
          </div>
        </div>
      </div>

      <!-- 8. Document Footer -->
      <div class="flex items-center justify-between text-[9px] text-slate-500 border-t border-slate-300 pt-1.5 font-mono">
        <span>نظام SARH ERP المتكامل لإدارة المقاولات والتشطيبات • وثيقة صادرة إلكترونياً</span>
        <span>تاريخ وتوقيت الطباعة: {{ new Date().toLocaleString('ar-EG') }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import { useAuthStore } from '../../stores/auth';
import {
  ClipboardList,
  Plus,
  Search,
  Layers,
  FileText,
  Clock,
  CheckCircle2,
  CheckSquare,
  Eye,
  Edit3,
  GitBranch,
  Trash2,
  X,
  Save,
  Loader2,
  Send,
  Check,
  Printer,
  History,
  Info,
  AlertCircle,
  Ruler,
  ShieldCheck,
} from 'lucide-vue-next';
import SearchableSelect from '../../components/common/SearchableSelect.vue';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

const filterOpportunityId = computed(() => route.query.opportunity_id || '');

const canCreate = computed(() => authStore.hasPermission('scopes.create') || authStore.hasPermission('scope.create'));
const canApprove = computed(() => authStore.hasPermission('scopes.approve') || authStore.hasPermission('scope.approve'));

const loading = ref(false);
const submitting = ref(false);
const scopes = ref([]);
const opportunities = ref([]);
const approvedMeasurements = ref([]);

const searchQuery = ref('');
const selectedStatus = ref('');
const selectedScopeIds = ref([]);

const isAllScopesSelected = computed(() => {
  return scopes.value.length > 0 && selectedScopeIds.value.length === scopes.value.length;
});

const toggleSelectAllScopes = () => {
  if (isAllScopesSelected.value) {
    selectedScopeIds.value = [];
  } else {
    selectedScopeIds.value = scopes.value.map((s) => s.id);
  }
};
const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  total_items_count: 0,
});

const statusFilterOptions = computed(() => [
  { value: '', label: t('scopes.filterAll') },
  { value: 'Draft', label: t('scopes.filterDraft') },
  { value: 'Under Review', label: t('scopes.filterUnderReview') },
  { value: 'Approved', label: t('scopes.filterApproved') },
  { value: 'Superseded', label: t('scopes.filterSuperseded') },
]);

const tradeCategoryOptions = computed(() => [
  { value: 'أعمال التكسير والإزالة والتجهيز', label: 'أعمال التكسير والإزالة والتجهيز' },
  { value: 'أعمال البناء والمباني', label: 'أعمال البناء والمباني' },
  { value: 'أعمال بياض المحارة واللياسة', label: 'أعمال بياض المحارة واللياسة' },
  { value: 'أعمال التأسيسات والتمديدات الكهربائية', label: 'أعمال التأسيسات والتمديدات الكهربائية' },
  { value: 'أعمال التأسيسات الصحية وشبكات التغذية والصرف', label: 'أعمال التأسيسات الصحية وشبكات التغذية والصرف' },
  { value: 'أعمال تمديدات التكييف والتهوية', label: 'أعمال تمديدات التكييف والتهوية' },
  { value: 'أعمال عزل الرطوبة والحرارة والحمامات والأسطح', label: 'أعمال عزل الرطوبة والحرارة والحمامات والأسطح' },
  { value: 'أعمال الأرضيات والبورسلين والرخام والجرانيت', label: 'أعمال الأرضيات والبورسلين والرخام والجرانيت' },
  { value: 'أعمال الأسقف المعلقة والجبسوم بورد', label: 'أعمال الأسقف المعلقة والجبسوم بورد' },
  { value: 'أعمال الدهانات والتشطيبات والديكورات الحائطية', label: 'أعمال الدهانات والتشطيبات والديكورات الحائطية' },
  { value: 'أعمال النجارة والأبواب والتجاليد الخشبية', label: 'أعمال النجارة والأبواب والتجاليد الخشبية' },
  { value: 'أعمال الألوميتال وقطاعات الزجاج والواجهات', label: 'أعمال الألوميتال وقطاعات الزجاج والواجهات' },
  { value: 'أعمال الحديد والكريتال والحماية', label: 'أعمال الحديد والكريتال والحماية' },
  { value: 'أعمال الديكورات والتجهيزات الخاصة والتسليم', label: 'أعمال الديكورات والتجهيزات الخاصة والتسليم' },
  { value: 'أخرى', label: 'أخرى' },
]);

const formattedOpportunityOptions = computed(() => {
  return (opportunities.value || []).map((opp) => ({
    value: opp.id,
    label: `#${opp.id} - ${opp.title} (${opp.customer?.display_name || opp.customer?.name || 'عميل'})`,
  }));
});

const formattedMeasurementOptions = computed(() => {
  if (!form.opportunity_id) return [];
  return (approvedMeasurements.value || [])
    .filter((m) => Number(m.opportunity_id) === Number(form.opportunity_id) && m.status === 'Approved')
    .map((m) => ({
      value: m.id,
      label: `مقايسة معتمدة #${m.measurement_number} (V${m.version} - ${m.measured_at || m.created_at?.slice(0, 10)})`,
    }));
});

const selectedMeasurementObject = computed(() => {
  if (!form.measurement_id) return null;
  return (approvedMeasurements.value || []).find((m) => Number(m.id) === Number(form.measurement_id)) || null;
});

const selectedMeasurementItemsList = computed(() => {
  return selectedMeasurementObject.value?.items || [];
});

const showEditModal = ref(false);
const showDetailsModal = ref(false);
const isEditing = ref(false);
const selectedScope = ref(null);

const form = reactive({
  id: null,
  opportunity_id: null,
  measurement_id: null,
  title: '',
  general_inclusions: '',
  general_exclusions: '',
  notes: '',
  items: [],
});

const onStatusFilterChange = (val) => {
  selectedStatus.value = val || '';
  fetchScopes();
};

const onOpportunitySelect = (oppId) => {
  form.opportunity_id = oppId;
  // Automatically select the latest approved measurement if available
  const oppMeasurements = (approvedMeasurements.value || []).filter(
    (m) => Number(m.opportunity_id) === Number(oppId) && m.status === 'Approved'
  );
  if (oppMeasurements.length > 0) {
    form.measurement_id = oppMeasurements[0].id;
  } else {
    form.measurement_id = null;
  }
};

const onMeasurementSelect = (measId) => {
  form.measurement_id = measId;
};

const toggleMeasurementItemLink = (item, measurementItemId) => {
  if (!item.measurement_item_ids) {
    item.measurement_item_ids = [];
  }
  const idx = item.measurement_item_ids.indexOf(measurementItemId);
  if (idx > -1) {
    item.measurement_item_ids.splice(idx, 1);
  } else {
    item.measurement_item_ids.push(measurementItemId);
  }
};

const addScopeItemRow = () => {
  form.items.push({
    trade_category: 'أعمال بياض المحارة واللياسة',
    item_name: '',
    specification: '',
    inclusions: '',
    exclusions: '',
    notes: '',
    measurement_item_ids: [],
    sort_order: form.items.length + 1,
  });
};

const removeScopeItemRow = (idx) => {
  form.items.splice(idx, 1);
};

// API calls
const fetchScopes = async () => {
  loading.value = true;
  try {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (filterOpportunityId.value) params.opportunity_id = filterOpportunityId.value;

    const res = await api.get('/scopes', { params });
    selectedScopeIds.value = [];
    if (res.data?.success) {
      scopes.value = res.data.data;
      if (res.data.stats) {
        Object.assign(stats, res.data.stats);
      }
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'فشل تحميل بيانات نطاقات الأعمال',
    });
  } finally {
    loading.value = false;
  }
};

let debounceTimer = null;
const debounceFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchScopes();
  }, 350);
};

const fetchDependencies = async () => {
  try {
    const [oppRes, measRes] = await Promise.all([
      api.get('/opportunities'),
      api.get('/measurements', { params: { status: 'Approved' } }),
    ]);

    if (oppRes.data?.success) {
      opportunities.value = oppRes.data.data;
    }
    if (measRes.data?.success) {
      approvedMeasurements.value = measRes.data.data;
    }
  } catch (err) {
    // Non-blocking
  }
};

const clearOpportunityFilter = () => {
  router.replace({ query: {} });
  setTimeout(() => {
    fetchScopes();
  }, 50);
};

const openCreateModal = (preselectedOppId = null) => {
  isEditing.value = false;
  form.id = null;
  form.opportunity_id = preselectedOppId ? Number(preselectedOppId) : (filterOpportunityId.value ? Number(filterOpportunityId.value) : null);
  form.measurement_id = null;
  form.title = '';
  form.general_inclusions = 'يشمل توريد المواد والخامات المطابقة للمواصفات، النقل والتشوين، العمالة الفنية المتخصصة، نظافة الموقع يومياً، وتسليم الأعمال لمهندس الإشراف.';
  form.general_exclusions = 'لا يشمل الأجهزة الكهربائية، وحدات الإنارة الديكورية، الإكسسوارات الخاصة، ورسوم وتراخيص الجهات الحكومية.';
  form.notes = '';
  form.items = [];

  if (form.opportunity_id) {
    onOpportunitySelect(form.opportunity_id);
  }

  // Add default first item row
  addScopeItemRow();

  showEditModal.value = true;
};

const openEditModal = (scope) => {
  isEditing.value = true;
  form.id = scope.id;
  form.opportunity_id = scope.opportunity_id;
  form.measurement_id = scope.measurement_id;
  form.title = scope.title || '';
  form.general_inclusions = scope.general_inclusions || '';
  form.general_exclusions = scope.general_exclusions || '';
  form.notes = scope.notes || '';

  // Copy items
  form.items = (scope.items || []).map((it) => ({
    id: it.id,
    trade_category: it.trade_category || '',
    item_name: it.item_name || '',
    specification: it.specification || '',
    inclusions: it.inclusions || '',
    exclusions: it.exclusions || '',
    notes: it.notes || '',
    measurement_item_ids: (it.measurement_items || []).map((m) => m.id),
    sort_order: it.sort_order || 0,
  }));

  showEditModal.value = true;
};

const openDetailsModal = (scope) => {
  selectedScope.value = scope;
  showDetailsModal.value = true;
};

const saveScope = async () => {
  if (!form.opportunity_id) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: 'يرجى اختيار الفرصة التجارية.',
    });
    return;
  }

  if (!form.measurement_id) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: 'يرجى اختيار مقايسة هندسية معتمدة (Approved).',
    });
    return;
  }

  if (!form.title) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: 'يرجى كتابة عنوان نطاق الأعمال.',
    });
    return;
  }

  if (form.items.length === 0) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: t('scopes.noItemsWarning'),
    });
    return;
  }

  // Validate item fields
  for (let i = 0; i < form.items.length; i++) {
    const it = form.items[i];
    if (!it.trade_category || !it.item_name || !it.specification) {
      notificationStore.addToast({
        type: 'error',
        title: t('common.validationError'),
        message: `يرجى استكمال الحقول الإلزامية (التخصص، الاسم، المواصفة) للبند رقم ${i + 1}.`,
      });
      return;
    }
  }

  submitting.value = true;
  try {
    const payload = {
      opportunity_id: form.opportunity_id,
      measurement_id: form.measurement_id,
      title: form.title,
      general_inclusions: form.general_inclusions,
      general_exclusions: form.general_exclusions,
      notes: form.notes,
      items: form.items,
    };

    let res;
    if (isEditing.value) {
      res = await api.put(`/scopes/${form.id}`, payload);
    } else {
      res = await api.post('/scopes', payload);
    }

    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: t('common.successSave'),
        message: res.data.message || 'تم حفظ وثيقة نطاق الأعمال بنجاح',
      });
      showEditModal.value = false;
      fetchScopes();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'فشل حفظ نطاق الأعمال. تحقق من البيانات المدخلة.',
    });
  } finally {
    submitting.value = false;
  }
};

const submitForReview = async (scope) => {
  if (!confirm(t('scopes.submitReviewConfirm'))) return;

  try {
    const res = await api.post(`/scopes/${scope.id}/submit-review`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم التقديم للمراجعة',
        message: res.data.message || 'تم تحويل حالة نطاق الأعمال إلى قيد المراجعة الفنية بنجاح',
      });
      fetchScopes();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر تقديم نطاق الأعمال للمراجعة',
    });
  }
};

const approveScope = async (scope) => {
  if (!confirm(t('scopes.approveConfirm'))) return;

  try {
    const res = await api.post(`/scopes/${scope.id}/approve`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم الاعتماد بنجاح',
        message: res.data.message || 'تم اعتماد وثيقة نطاق العمل وقفل التعديل عليها',
      });
      fetchScopes();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر اعتماد نطاق الأعمال',
    });
  }
};

const createRevision = async (scope) => {
  if (!confirm(t('scopes.createRevisionConfirm'))) return;

  try {
    const res = await api.post(`/scopes/${scope.id}/create-revision`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم إنشاء الإصدار الجديد',
        message: res.data.message || 'تم إنشاء مسودة جديدة مطابقة برقم إصدار أحدث بنجاح',
      });
      fetchScopes();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر إنشاء إصدار جديد من نطاق الأعمال',
    });
  }
};

const deleteScope = async (scope) => {
  if (!confirm(t('common.confirmDelete'))) return;

  try {
    const res = await api.delete(`/scopes/${scope.id}`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: t('common.successDelete'),
        message: res.data.message || 'تم حذف نطاق الأعمال بنجاح',
      });
      fetchScopes();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر حذف هذا السجل',
    });
  }
};

const confirmBulkDeleteScopes = async () => {
  if (selectedScopeIds.value.length === 0) return;

  const selectedItems = scopes.value.filter((s) => selectedScopeIds.value.includes(s.id));
  const deletableItems = selectedItems.filter((s) => s.status !== 'Approved');
  const approvedCount = selectedItems.length - deletableItems.length;

  if (deletableItems.length === 0) {
    notificationStore.addToast({
      type: 'warning',
      title: 'تنبيه النظام',
      message: 'كافة نطاقات الأعمال المحددة معتمدة رسمياً ولا يمكن حذفها للحفاظ على سلامة بنود الأعمال والتعاقد.',
    });
    return;
  }

  const confirmed = await alertService.confirmDelete({
    title: 'تأكيد حذف نطاقات الأعمال المحددة',
    text: `هل أنت متأكد من رغبتك في حذف ${deletableItems.length} نطاق عمل محدد؟ لا يمكن التراجع عن هذا الإجراء.${approvedCount > 0 ? ` (تم استثناء ${approvedCount} نطاق عمل معتمد رسمياً)` : ''}`,
    confirmButtonText: `نعم، احذف (${deletableItems.length})`,
    cancelButtonText: 'إلغاء الأمر',
  });

  if (!confirmed) return;

  loading.value = true;
  let successCount = 0;
  for (const item of deletableItems) {
    try {
      await api.delete(`/scopes/${item.id}`);
      successCount++;
    } catch (err) {}
  }

  if (successCount > 0) {
    notificationStore.addToast({
      type: 'success',
      title: 'تم الحذف بنجاح',
      message: `تم حذف ${successCount} نطاق عمل بنجاح.`,
    });
  }

  selectedScopeIds.value = [];
  await fetchScopes();
};

const printScopeSheet = () => {
  window.print();
};

const quickPrintScope = (scope) => {
  selectedScope.value = scope;
  setTimeout(() => {
    window.print();
  }, 120);
};

onMounted(() => {
  fetchScopes();
  fetchDependencies();
});
</script>

<style scoped>
@media screen {
  #official-printable-scope {
    display: none !important;
  }
}

@media print {
  @page {
    size: A4 portrait;
    margin: 10mm 10mm 12mm 10mm;
  }

  body {
    background: #ffffff !important;
    color: #0f172a !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* Completely hide all screen elements, headers, modals, and backdrop */
  body * {
    visibility: hidden !important;
  }

  /* Make our official engineering document visible */
  #official-printable-scope,
  #official-printable-scope * {
    visibility: visible !important;
  }

  #official-printable-scope {
    display: block !important;
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    background: #ffffff !important;
    color: #0f172a !important;
    font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
    direction: rtl !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  .print-avoid-break {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }

  table {
    page-break-inside: auto;
  }

  tr {
    page-break-inside: avoid;
    page-break-after: auto;
  }

  thead {
    display: table-header-group;
  }

  tfoot {
    display: table-footer-group;
  }
}

.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
</style>
