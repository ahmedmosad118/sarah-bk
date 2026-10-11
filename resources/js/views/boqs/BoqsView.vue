<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2.5">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
            <TableProperties class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">
              {{ $t('boq.title') }}
            </h1>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
              {{ $t('boq.subtitle') }}
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
          <span>{{ $t('boq.createTitle') }}</span>
        </button>
      </div>
    </div>

    <!-- Zero Pricing Governance Notice -->
    <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 dark:border-blue-900/30 dark:bg-blue-950/20 flex items-start gap-3.5">
      <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400 mt-0.5">
        <ShieldCheck class="h-4 w-4" />
      </div>
      <div class="flex-1 text-xs">
        <div class="flex items-center gap-2">
          <span class="font-black text-blue-950 dark:text-blue-200">محددات جدول الكميات (BOQ Governance):</span>
          <span class="rounded-md bg-blue-200/60 px-2 py-0.5 text-[10px] font-black text-blue-800 dark:bg-blue-900 dark:text-blue-300">
            Zero Pricing Standard
          </span>
        </div>
        <p class="text-blue-800/80 dark:text-blue-300/80 mt-1 leading-relaxed">
          جدول الكميات وثيقة كميات رسمية قابلة للتسعير: الوصف من <strong>نطاق الأعمال المعتمد</strong>، والكميات من <strong>بنود الحصر المعتمدة</strong> — بدون أي أسعار أو تكاليف أو هوامش (مسؤولية مرحلة الـ Estimation اللاحقة).
        </p>
      </div>
    </div>

    <!-- Active Opportunity Filter Banner -->
    <div v-if="filterOpportunityId" class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
      <div class="flex items-center gap-2.5 text-xs text-emerald-900 dark:text-emerald-300">
        <div class="p-1.5 rounded-lg bg-[#00C896] text-white">
          <TableProperties class="h-4 w-4" />
        </div>
        <div>
          <p class="font-bold">{{ $t('boq.filterByOpportunity') }} #{{ filterOpportunityId }}</p>
          <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400">يتم تصفية جداول الكميات للفرصة المحددة فقط.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="openCreateModal(filterOpportunityId)"
          class="px-3 py-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          + إنشاء جدول كميات لهذه الفرصة
        </button>
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          عرض كل الجداول
        </button>
      </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('boq.totalBoqs') }}</span>
          <Layers class="h-4 w-4 text-gray-400" />
        </div>
        <p class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ $t('boq.draftBoqs') }}</span>
          <FileText class="h-4 w-4 text-slate-400" />
        </div>
        <p class="mt-2 text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $t('boq.underReviewBoqs') }}</span>
          <Clock class="h-4 w-4 text-amber-500" />
        </div>
        <p class="mt-2 text-xl font-black text-amber-900 dark:text-amber-300">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">{{ $t('boq.approvedBoqs') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500" />
        </div>
        <p class="mt-2 text-xl font-black text-emerald-900 dark:text-emerald-300">{{ stats.approved }}</p>
      </div>

      <div class="col-span-2 sm:col-span-1 rounded-2xl border border-teal-100 bg-teal-50/40 p-4 shadow-xs dark:border-teal-900/30 dark:bg-teal-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-teal-700 dark:text-teal-400">{{ $t('boq.totalLinesCount') }}</span>
          <ListOrdered class="h-4 w-4 text-teal-500" />
        </div>
        <p class="mt-2 text-xl font-black text-teal-900 dark:text-teal-300">{{ stats.total_lines_count }}</p>
      </div>
    </div>

    <!-- Search & Filters -->
    <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
      <div class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1">
          <Search class="absolute top-1/2 -translate-y-1/2 start-3 h-4 w-4 text-gray-400" />
          <input
            v-model="searchQuery"
            @input="debounceFetch"
            type="text"
            :placeholder="$t('common.searchPlaceholder')"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 ps-9 pe-3 text-xs text-gray-900 focus:border-[#00C896] focus:outline-hidden focus:ring-1 focus:ring-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          />
        </div>
        <select
          v-model="selectedStatus"
          @change="fetchBoqs"
          class="rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-xs font-bold text-gray-700 focus:border-[#00C896] focus:outline-hidden dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
        >
          <option v-for="opt in statusFilterOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </option>
        </select>
      </div>
    </div>

    <!-- BOQ Table -->
    <div class="rounded-2xl border border-gray-100 bg-white shadow-xs overflow-hidden dark:border-gray-800 dark:bg-gray-900">
      <div v-if="loading" class="flex items-center justify-center py-16">
        <div class="h-8 w-8 animate-spin rounded-full border-3 border-[#00C896] border-t-transparent"></div>
      </div>

      <div v-else-if="boqs.length === 0" class="text-center py-16 px-6">
        <TableProperties class="h-10 w-10 text-gray-300 mx-auto mb-3" />
        <p class="text-sm font-black text-gray-700 dark:text-gray-200">{{ $t('common.noData') }}</p>
        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $t('common.noDataDesc') }}</p>
        <button
          v-if="canCreate"
          @click="openCreateModal()"
          class="mt-4 inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-4 py-2 text-xs font-bold text-white hover:bg-[#00A87E] transition-colors cursor-pointer"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('boq.createFromScope') }}</span>
        </button>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 font-black text-[11px]">
            <tr>
              <th class="py-3 px-4">{{ $t('boq.boqNumber') }}</th>
              <th class="py-3 px-4">{{ $t('boq.boqTitle') }}</th>
              <th class="py-3 px-4">{{ $t('boq.opportunity') }}</th>
              <th class="py-3 px-4">{{ $t('boq.basedOnScope') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('boq.version') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('boq.status') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('boq.items') }}</th>
              <th class="py-3 px-4 text-center">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr v-for="boq in boqs" :key="boq.id" class="hover:bg-gray-50/60 dark:hover:bg-gray-800/40">
              <td class="py-3 px-4 font-mono font-black text-gray-900 dark:text-white">{{ boq.boq_number }}</td>
              <td class="py-3 px-4 font-bold text-gray-800 dark:text-gray-200 max-w-56 truncate">{{ boq.title || '—' }}</td>
              <td class="py-3 px-4 text-gray-600 dark:text-gray-300 max-w-48 truncate">{{ boq.opportunity?.title || `#${boq.opportunity_id}` }}</td>
              <td class="py-3 px-4 font-mono text-[11px] text-emerald-700 dark:text-emerald-400">{{ boq.scope?.scope_number || `#${boq.scope_id}` }}</td>
              <td class="py-3 px-4 text-center font-mono font-bold">V{{ boq.version }}</td>
              <td class="py-3 px-4 text-center">
                <span class="px-2 py-1 rounded-lg text-[10px] font-black" :class="statusBadgeClass(boq.status)">
                  {{ statusLabel(boq.status) }}
                </span>
              </td>
              <td class="py-3 px-4 text-center font-mono font-bold">{{ boq.items?.length || 0 }}</td>
              <td class="py-3 px-4">
                <div class="flex items-center justify-center gap-1.5">
                  <button @click="openDetailsModal(boq)" title="عرض" class="p-1.5 rounded-lg text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 cursor-pointer">
                    <Eye class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canEdit(boq)"
                    @click="openEditModal(boq)"
                    title="تعديل"
                    class="p-1.5 rounded-lg text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 cursor-pointer"
                  >
                    <Pencil class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canEdit(boq)"
                    @click="submitForReview(boq)"
                    title="تقديم للمراجعة"
                    class="p-1.5 rounded-lg text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/40 cursor-pointer"
                  >
                    <SendHorizonal class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canApprove && (boq.status === 'Draft' || boq.status === 'Under Review')"
                    @click="approveBoq(boq)"
                    title="اعتماد"
                    class="p-1.5 rounded-lg text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 cursor-pointer"
                  >
                    <CheckCircle2 class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canCreate && boq.status === 'Approved'"
                    @click="createRevision(boq)"
                    title="إصدار جديد"
                    class="p-1.5 rounded-lg text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 cursor-pointer"
                  >
                    <GitBranchPlus class="h-4 w-4" />
                  </button>
                  <button
                    v-if="canDelete(boq)"
                    @click="deleteBoq(boq)"
                    title="حذف"
                    class="p-1.5 rounded-lg text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
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

    <!-- Create Modal -->
    <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-gray-900 shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('boq.createTitle') }}</h3>
          <button @click="showCreateModal = false" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-black text-gray-600 dark:text-gray-300 mb-1">{{ $t('boq.opportunity') }} *</label>
            <SearchableSelect
              :model-value="createForm.opportunity_id"
              @update:model-value="onCreateOpportunitySelect"
              :options="formattedOpportunityOptions"
              :placeholder="$t('boq.selectOpportunity')"
            />
          </div>
          <div>
            <label class="block text-[11px] font-black text-gray-600 dark:text-gray-300 mb-1">{{ $t('boq.basedOnScope') }} *</label>
            <SearchableSelect
              :model-value="createForm.scope_id"
              @update:model-value="onCreateScopeSelect"
              :options="formattedScopeOptions"
              :placeholder="$t('boq.selectScope')"
            />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-[11px] font-black text-gray-600 dark:text-gray-300 mb-1">{{ $t('boq.boqTitle') }}</label>
            <input
              v-model="createForm.title"
              type="text"
              :placeholder="$t('boq.boqTitlePlaceholder')"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-[#00C896] focus:outline-hidden"
            />
          </div>
        </div>

        <!-- Live Preview -->
        <div v-if="previewScope" class="rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden">
          <div class="bg-gray-50 dark:bg-gray-800/60 px-4 py-2.5 flex items-center justify-between">
            <span class="text-xs font-black text-gray-800 dark:text-gray-200">
              معاينة بنود جدول الكميات ({{ previewLines.length }} بنود مولدة)
            </span>
            <span class="text-[11px] font-mono text-emerald-700 dark:text-emerald-400">
              {{ $t('boq.sourceChain', { scope: previewScope.scope_number, measurement: previewScope.measurement?.measurement_number || '—' }) }}
            </span>
          </div>

          <div v-if="previewLines.length > 0" class="overflow-x-auto max-h-64 overflow-y-auto">
            <table class="w-full text-right text-xs">
              <thead class="bg-white dark:bg-gray-900 text-gray-500 font-black text-[11px] sticky top-0">
                <tr>
                  <th class="py-2 px-3 w-14 text-center">{{ $t('boq.itemCode') }}</th>
                  <th class="py-2 px-3">{{ $t('boq.description') }}</th>
                  <th class="py-2 px-3 w-20 text-center">{{ $t('boq.unit') }}</th>
                  <th class="py-2 px-3 w-24 text-center">{{ $t('boq.quantity') }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="line in previewLines" :key="line.scope_item_id">
                  <td class="py-2 px-3 text-center font-mono font-bold">{{ line.item_code }}</td>
                  <td class="py-2 px-3 font-bold text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ line.description }}</td>
                  <td class="py-2 px-3 text-center font-bold">{{ line.unit }}</td>
                  <td class="py-2 px-3 text-center font-mono font-black text-teal-700 dark:text-teal-300">{{ line.quantity }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-6 text-xs text-gray-400">
            لا توجد بنود قابلة للتوليد من هذا النطاق — راجع التحذيرات أدناه.
          </div>

          <div v-if="previewSkipped.length > 0" class="p-3 bg-amber-50/60 dark:bg-amber-950/20 border-t border-amber-200 dark:border-amber-900/40 space-y-1.5">
            <p class="text-[11px] font-black text-amber-800 dark:text-amber-300">
              {{ $t('boq.skippedWarning', { count: previewSkipped.length }) }}
            </p>
            <div v-for="sk in previewSkipped" :key="sk.scope_item_id" class="text-[11px] text-amber-700 dark:text-amber-400">
              • <strong>{{ sk.item_name }}</strong> —
              {{ sk.reason === 'mixed_units' ? $t('boq.skippedMixedUnits') : $t('boq.skippedNoQuantities') }}
              <span v-if="sk.units" class="font-mono">({{ sk.units.join(' + ') }})</span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showCreateModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer">
            {{ $t('common.cancel') }}
          </button>
          <button
            @click="saveBoq"
            :disabled="submitting || !createForm.scope_id"
            class="px-5 py-2 rounded-xl bg-[#00C896] text-xs font-black text-white hover:bg-[#00A87E] disabled:opacity-50 transition-colors cursor-pointer"
          >
            {{ submitting ? $t('common.loading') : $t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Edit Modal (Draft only: descriptive fields + line removal) -->
    <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-gray-900 shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('boq.editTitle') }} — {{ editForm.boq_number }}</h3>
          <button @click="showEditModal = false" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-[11px] font-black text-gray-600 dark:text-gray-300 mb-1">{{ $t('boq.boqTitle') }}</label>
            <input v-model="editForm.title" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-[#00C896] focus:outline-hidden" />
          </div>
          <div>
            <label class="block text-[11px] font-black text-gray-600 dark:text-gray-300 mb-1">{{ $t('boq.notes') }}</label>
            <input v-model="editForm.notes" type="text" class="w-full rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-white focus:border-[#00C896] focus:outline-hidden" />
          </div>
        </div>

        <div class="space-y-2">
          <p class="text-[11px] font-black text-gray-600 dark:text-gray-300">{{ $t('boq.items') }} ({{ editForm.items.length }}) — الكمية والوحدة تُشتق من المصدر تلقائياً</p>
          <div v-for="(line, idx) in editForm.items" :key="line.scope_item_id || idx" class="p-3 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 space-y-2">
            <div class="flex items-center justify-between gap-2">
              <span class="font-mono text-[11px] font-black text-teal-700 dark:text-teal-300">{{ line.item_code }} • {{ line.unit }} • {{ line.quantity }}</span>
              <button @click="editForm.items.splice(idx, 1)" class="p-1 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer">
                <Trash2 class="h-3.5 w-3.5" />
              </button>
            </div>
            <input v-model="line.item_code" type="text" placeholder="كود البند (1.1)" class="w-28 rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white" />
            <textarea v-model="line.description" rows="2" class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
          </div>
          <p v-if="editForm.items.length === 0" class="text-center text-xs text-gray-400 py-4">تمت إزالة كل البنود — لا يمكن اعتماد جدول فارغ.</p>
        </div>

        <div class="flex items-center justify-end gap-2 pt-2">
          <button @click="showEditModal = false" class="px-4 py-2 rounded-xl border border-gray-200 text-xs font-bold text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800 cursor-pointer">
            {{ $t('common.cancel') }}
          </button>
          <button @click="updateBoq" :disabled="submitting" class="px-5 py-2 rounded-xl bg-[#00C896] text-xs font-black text-white hover:bg-[#00A87E] disabled:opacity-50 transition-colors cursor-pointer">
            {{ submitting ? $t('common.loading') : $t('common.save') }}
          </button>
        </div>
      </div>
    </div>

    <!-- Details Modal -->
    <div v-if="showDetailsModal && selectedBoq" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
      <div class="w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white dark:bg-gray-900 shadow-2xl p-6 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2 flex-wrap">
            <h3 class="text-sm font-black text-gray-900 dark:text-white">جدول الكميات #{{ selectedBoq.boq_number }}</h3>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-black" :class="statusBadgeClass(selectedBoq.status)">{{ statusLabel(selectedBoq.status) }}</span>
            <span class="px-2 py-0.5 rounded-lg text-[10px] font-mono font-bold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">V{{ selectedBoq.version }}</span>
          </div>
          <button @click="showDetailsModal = false" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
            <X class="h-4 w-4" />
          </button>
        </div>

        <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 text-xs">
          <span class="font-black text-emerald-950 dark:text-emerald-200">سلسلة المرجعية: </span>
          <span class="text-emerald-900 dark:text-emerald-300">
            مبني على النطاق <strong class="font-mono">#{{ selectedBoq.scope?.scope_number }} (V{{ selectedBoq.scope?.version }})</strong>
            — المبني بدوره على المقايسة <strong class="font-mono">#{{ selectedBoq.scope?.measurement?.measurement_number }} (V{{ selectedBoq.scope?.measurement?.version }})</strong>
          </span>
        </div>

        <div v-if="selectedBoq.status === 'Approved'" class="p-3 rounded-2xl bg-emerald-50 text-emerald-800 text-xs font-bold dark:bg-emerald-950/40 dark:text-emerald-300">
          {{ $t('boq.approvedNotice') }}
        </div>
        <div v-if="selectedBoq.status === 'Superseded'" class="p-3 rounded-2xl bg-gray-100 text-gray-600 text-xs font-bold dark:bg-gray-800 dark:text-gray-300">
          {{ $t('boq.supersededNotice') }}
        </div>

        <div v-if="selectedUnresolved.length > 0" id="boq-unresolved-warning" class="p-3 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900/40 space-y-1.5">
          <p class="text-[11px] font-black text-amber-800 dark:text-amber-300">
            {{ $t('boq.skippedWarning', { count: selectedUnresolved.length }) }}
          </p>
          <div v-for="sk in selectedUnresolved" :key="sk.scope_item_id" class="text-[11px] text-amber-700 dark:text-amber-400">
            • <strong>{{ sk.item_name }}</strong> —
            {{ sk.message || (sk.reason === 'mixed_units' ? $t('boq.skippedMixedUnits') : $t('boq.skippedNoQuantities')) }}
            <span v-if="sk.units" class="font-mono">({{ sk.units.join(' + ') }})</span>
          </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-800">
          <table class="w-full text-right text-xs">
            <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 font-black text-[11px]">
              <tr>
                <th class="py-2.5 px-3 w-16 text-center">{{ $t('boq.itemCode') }}</th>
                <th class="py-2.5 px-3">{{ $t('boq.description') }}</th>
                <th class="py-2.5 px-3 w-20 text-center">{{ $t('boq.unit') }}</th>
                <th class="py-2.5 px-3 w-24 text-center">{{ $t('boq.quantity') }}</th>
                <th class="py-2.5 px-3 w-48">{{ $t('boq.sourceScopeItem') }}</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              <tr v-for="it in selectedBoq.items" :key="it.id" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30 align-top">
                <td class="py-2.5 px-3 text-center font-mono font-black">{{ it.item_code || '—' }}</td>
                <td class="py-2.5 px-3 font-bold text-gray-800 dark:text-gray-200 whitespace-pre-line">{{ it.description }}</td>
                <td class="py-2.5 px-3 text-center font-bold">{{ it.unit }}</td>
                <td class="py-2.5 px-3 text-center font-mono font-black text-teal-700 dark:text-teal-300">{{ Number(it.quantity).toFixed(2) }}</td>
                <td class="py-2.5 px-3 text-[11px] text-gray-500 dark:text-gray-400">
                  {{ it.scope_item?.item_name || `#${it.scope_item_id}` }}
                  <span class="block text-gray-400">{{ it.scope_item?.trade_category || '' }}</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex flex-wrap items-center gap-2 pt-1">
          <button v-if="canEdit(selectedBoq)" @click="submitForReview(selectedBoq)" class="px-3.5 py-2 rounded-xl bg-purple-600 text-xs font-bold text-white hover:bg-purple-700 cursor-pointer">
            {{ $t('boq.submitReviewAction') }}
          </button>
          <button v-if="canApprove && (selectedBoq.status === 'Draft' || selectedBoq.status === 'Under Review')" @click="approveBoq(selectedBoq)" class="px-3.5 py-2 rounded-xl bg-emerald-600 text-xs font-bold text-white hover:bg-emerald-700 cursor-pointer">
            {{ $t('boq.approveAction') }}
          </button>
          <button v-if="canCreate && selectedBoq.status === 'Approved'" @click="createRevision(selectedBoq)" class="px-3.5 py-2 rounded-xl bg-teal-600 text-xs font-bold text-white hover:bg-teal-700 cursor-pointer">
            {{ $t('boq.createRevisionAction') }}
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
import { useNotificationStore } from '../../stores/notification';
import { useAuthStore } from '../../stores/auth';
import SearchableSelect from '../../components/common/SearchableSelect.vue';
import {
  TableProperties,
  Plus,
  Search,
  Layers,
  FileText,
  Clock,
  CheckCircle2,
  ListOrdered,
  ShieldCheck,
  Eye,
  Pencil,
  SendHorizonal,
  GitBranchPlus,
  Trash2,
  X,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

const filterOpportunityId = computed(() => route.query.opportunity_id || '');

const canCreate = computed(() => authStore.hasPermission(['boqs.create', 'boq.create']));
const canApprove = computed(() => authStore.hasPermission(['boqs.approve', 'boq.approve']));
const canUpdate = computed(() => authStore.hasPermission(['boqs.update', 'boq.update']));
const canDeletePerm = computed(() => authStore.hasPermission(['boqs.delete', 'boq.delete']));

const canEdit = (boq) => canUpdate.value && (boq.status === 'Draft' || boq.status === 'Under Review');
const canDelete = (boq) => canDeletePerm.value && boq.status !== 'Approved';

const loading = ref(false);
const submitting = ref(false);
const boqs = ref([]);
const opportunities = ref([]);
const approvedScopes = ref([]);

const searchQuery = ref('');
const selectedStatus = ref('');

const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  total_lines_count: 0,
});

const statusFilterOptions = computed(() => [
  { value: '', label: t('boq.filterAll') },
  { value: 'Draft', label: t('boq.filterDraft') },
  { value: 'Under Review', label: t('boq.filterUnderReview') },
  { value: 'Approved', label: t('boq.filterApproved') },
  { value: 'Superseded', label: t('boq.filterSuperseded') },
]);

const statusLabel = (status) => {
  const map = {
    Draft: t('boq.statusDraft'),
    'Under Review': t('boq.statusUnderReview'),
    Approved: t('boq.statusApproved'),
    Superseded: t('boq.statusSuperseded'),
  };
  return map[status] || status;
};

const statusBadgeClass = (status) => {
  const map = {
    Draft: 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
    'Under Review': 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    Approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    Superseded: 'bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
  };
  return map[status] || map.Draft;
};

const formattedOpportunityOptions = computed(() => {
  return (opportunities.value || []).map((opp) => ({
    value: opp.id,
    label: `#${opp.id} - ${opp.title} (${opp.customer?.display_name || opp.customer?.name || 'عميل'})`,
  }));
});

const formattedScopeOptions = computed(() => {
  if (!createForm.opportunity_id) return [];
  return (approvedScopes.value || [])
    .filter((s) => Number(s.opportunity_id) === Number(createForm.opportunity_id) && s.status === 'Approved')
    .map((s) => ({
      value: s.id,
      label: `نطاق معتمد #${s.scope_number} (V${s.version} - ${s.measurement?.measurement_number || ''})`,
    }));
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDetailsModal = ref(false);
const selectedBoq = ref(null);
// Scope items of the source scope without a BOQ line (server-derived, needs manual action)
const selectedUnresolved = ref([]);

const createForm = reactive({
  opportunity_id: null,
  scope_id: null,
  title: '',
});

const editForm = reactive({
  id: null,
  boq_number: '',
  title: '',
  notes: '',
  items: [],
});

const previewScope = ref(null);

const previewLines = computed(() => {
  if (!previewScope.value?.items) return [];
  const lines = [];
  const groupSeq = {};
  let groupCounter = 0;
  (previewScope.value.items || []).forEach((scopeItem) => {
    const linked = scopeItem.measurement_items || [];
    if (linked.length === 0) return;
    const units = [...new Set(linked.map((m) => m.unit))];
    if (units.length !== 1) return;
    const trade = scopeItem.trade_category || 'عام';
    if (!groupSeq[trade]) {
      groupCounter += 1;
      groupSeq[trade] = { group: groupCounter, seq: 0 };
    }
    groupSeq[trade].seq += 1;
    const qty = linked.reduce((sum, m) => sum + Number(m.net_quantity || 0), 0);
    lines.push({
      scope_item_id: scopeItem.id,
      item_code: `${groupSeq[trade].group}.${groupSeq[trade].seq}`,
      description: [scopeItem.item_name, scopeItem.specification].filter(Boolean).join('\n'),
      unit: units[0],
      quantity: Math.round(qty * 100) / 100,
    });
  });
  return lines;
});

const previewSkipped = computed(() => {
  if (!previewScope.value?.items) return [];
  const skipped = [];
  (previewScope.value.items || []).forEach((scopeItem) => {
    const linked = scopeItem.measurement_items || [];
    if (linked.length === 0) {
      skipped.push({ scope_item_id: scopeItem.id, item_name: scopeItem.item_name, reason: 'no_linked_measurement_items' });
      return;
    }
    const units = [...new Set(linked.map((m) => m.unit))];
    if (units.length !== 1) {
      skipped.push({ scope_item_id: scopeItem.id, item_name: scopeItem.item_name, reason: 'mixed_units', units });
    }
  });
  return skipped;
});

// API calls
const fetchBoqs = async () => {
  loading.value = true;
  try {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (filterOpportunityId.value) params.opportunity_id = filterOpportunityId.value;

    const res = await api.get('/boqs', { params });
    if (res.data?.success) {
      boqs.value = res.data.data;
      if (res.data.stats) {
        Object.assign(stats, res.data.stats, {
          total_lines_count: (res.data.data || []).reduce((sum, b) => sum + (b.items?.length || 0), 0),
        });
      }
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'فشل تحميل بيانات جداول الكميات',
    });
  } finally {
    loading.value = false;
  }
};

let debounceTimer = null;
const debounceFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => {
    fetchBoqs();
  }, 350);
};

const fetchDependencies = async () => {
  try {
    const [oppRes, scopeRes] = await Promise.all([
      api.get('/opportunities'),
      api.get('/scopes', { params: { status: 'Approved' } }),
    ]);

    if (oppRes.data?.success) {
      opportunities.value = oppRes.data.data;
    }
    if (scopeRes.data?.success) {
      approvedScopes.value = scopeRes.data.data;
    }
  } catch (err) {
    // Non-blocking
  }
};

const clearOpportunityFilter = () => {
  router.replace({ query: {} });
  setTimeout(() => {
    fetchBoqs();
  }, 50);
};

const onCreateOpportunitySelect = (oppId) => {
  createForm.opportunity_id = oppId;
  createForm.scope_id = null;
  previewScope.value = null;
};

const onCreateScopeSelect = async (scopeId) => {
  createForm.scope_id = scopeId;
  previewScope.value = null;
  if (!scopeId) return;
  try {
    const res = await api.get(`/scopes/${scopeId}`);
    if (res.data?.success) {
      previewScope.value = res.data.data;
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'تعذر تحميل بيانات النطاق للمعاينة',
    });
  }
};

const openCreateModal = (preselectedOppId = null) => {
  createForm.opportunity_id = preselectedOppId ? Number(preselectedOppId) : (filterOpportunityId.value ? Number(filterOpportunityId.value) : null);
  createForm.scope_id = null;
  createForm.title = '';
  previewScope.value = null;
  showCreateModal.value = true;
};

const saveBoq = async () => {
  if (!createForm.opportunity_id || !createForm.scope_id) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: 'يرجى اختيار الفرصة التجارية والنطاق المعتمد.',
    });
    return;
  }

  submitting.value = true;
  try {
    const res = await api.post('/boqs', {
      opportunity_id: createForm.opportunity_id,
      scope_id: createForm.scope_id,
      title: createForm.title || undefined,
    });

    if (res.data?.success) {
      let msg = res.data.message || 'تم إنشاء جدول الكميات بنجاح';
      const skipped = res.data.skipped_items || [];
      if (skipped.length > 0) {
        msg += ` — تنبيه: ${skipped.length} بنود نطاق لم تولد كميات وتتطلب مراجعة يدوية.`;
      }
      notificationStore.addToast({ type: 'success', title: t('common.successSave'), message: msg });
      showCreateModal.value = false;
      fetchBoqs();
      if (skipped.length > 0) {
        selectedBoq.value = res.data.data;
        selectedUnresolved.value = skipped;
        showDetailsModal.value = true;
      }
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'فشل إنشاء جدول الكميات. تحقق من البيانات المدخلة.',
    });
  } finally {
    submitting.value = false;
  }
};

const openEditModal = (boq) => {
  editForm.id = boq.id;
  editForm.boq_number = boq.boq_number;
  editForm.title = boq.title || '';
  editForm.notes = boq.notes || '';
  editForm.items = (boq.items || []).map((it) => ({
    scope_item_id: it.scope_item_id,
    item_code: it.item_code || '',
    description: it.description || '',
    notes: it.notes || '',
    unit: it.unit,
    quantity: it.quantity,
    sort_order: it.sort_order || 0,
  }));
  showEditModal.value = true;
};

const updateBoq = async () => {
  submitting.value = true;
  try {
    const res = await api.put(`/boqs/${editForm.id}`, {
      title: editForm.title,
      notes: editForm.notes,
      items: editForm.items.map((it, idx) => ({
        scope_item_id: it.scope_item_id,
        item_code: it.item_code,
        description: it.description,
        notes: it.notes,
        sort_order: it.sort_order ?? idx,
      })),
    });

    if (res.data?.success) {
      notificationStore.addToast({ type: 'success', title: t('common.successSave'), message: res.data.message || 'تم حفظ جدول الكميات بنجاح' });
      showEditModal.value = false;
      fetchBoqs();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: t('common.validationError'),
      message: err.response?.data?.message || 'فشل حفظ جدول الكميات.',
    });
  } finally {
    submitting.value = false;
  }
};

const openDetailsModal = async (boq) => {
  selectedBoq.value = boq;
  selectedUnresolved.value = [];
  showDetailsModal.value = true;
  try {
    const res = await api.get(`/boqs/${boq.id}`);
    if (res.data?.success && selectedBoq.value?.id === boq.id) {
      selectedBoq.value = res.data.data;
      selectedUnresolved.value = res.data.unresolved_items || [];
    }
  } catch (err) {
    // Keep list row data; the warning simply stays hidden if the fetch fails.
  }
};

const submitForReview = async (boq) => {
  if (!confirm(t('boq.submitReviewConfirm'))) return;
  try {
    const res = await api.post(`/boqs/${boq.id}/submit-review`);
    if (res.data?.success) {
      notificationStore.addToast({ type: 'success', title: 'تم التقديم للمراجعة', message: res.data.message || '' });
      showDetailsModal.value = false;
      fetchBoqs();
    }
  } catch (err) {
    notificationStore.addToast({ type: 'error', title: 'خطأ', message: err.response?.data?.message || 'تعذر التقديم للمراجعة' });
  }
};

const approveBoq = async (boq) => {
  if (!confirm(t('boq.approveConfirm'))) return;
  try {
    const res = await api.post(`/boqs/${boq.id}/approve`);
    if (res.data?.success) {
      notificationStore.addToast({ type: 'success', title: 'تم الاعتماد بنجاح', message: res.data.message || 'تم اعتماد جدول الكميات وقفل التعديل عليه' });
      showDetailsModal.value = false;
      fetchBoqs();
    }
  } catch (err) {
    notificationStore.addToast({ type: 'error', title: 'خطأ', message: err.response?.data?.message || 'تعذر اعتماد جدول الكميات' });
  }
};

const createRevision = async (boq) => {
  if (!confirm(t('boq.createRevisionConfirm'))) return;
  try {
    const res = await api.post(`/boqs/${boq.id}/create-revision`);
    if (res.data?.success) {
      notificationStore.addToast({ type: 'success', title: 'تم إنشاء الإصدار الجديد', message: res.data.message || '' });
      showDetailsModal.value = false;
      fetchBoqs();
    }
  } catch (err) {
    notificationStore.addToast({ type: 'error', title: 'خطأ', message: err.response?.data?.message || 'تعذر إنشاء إصدار جديد' });
  }
};

const deleteBoq = async (boq) => {
  if (!confirm(t('common.confirmDelete'))) return;
  try {
    const res = await api.delete(`/boqs/${boq.id}`);
    if (res.data?.success) {
      notificationStore.addToast({ type: 'success', title: t('common.successDelete'), message: res.data.message || '' });
      fetchBoqs();
    }
  } catch (err) {
    notificationStore.addToast({ type: 'error', title: 'خطأ', message: err.response?.data?.message || 'تعذر حذف جدول الكميات' });
  }
};

onMounted(() => {
  fetchBoqs();
  fetchDependencies();
});
</script>
