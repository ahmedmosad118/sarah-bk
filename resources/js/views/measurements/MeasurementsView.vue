<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2.5">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
            <Ruler class="h-5 w-5" />
          </div>
          <div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">
              {{ $t('measurements.title') }}
            </h1>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
              {{ $t('measurements.itemsDesc') }}
            </p>
          </div>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2.5">
        <button
          v-if="canCreate"
          @click="openImportModal"
          class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors"
        >
          <DownloadCloud class="h-4 w-4 text-[#00C896]" />
          <span>{{ $t('measurements.importFromSiteVisit') }}</span>
        </button>

        <button
          v-if="canCreate"
          @click="openCreateModal"
          class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] transition-colors"
        >
          <Plus class="h-4 w-4" />
          <span>{{ $t('measurements.createTitle') }}</span>
        </button>
      </div>
    </div>

    <!-- Active Opportunity Filter Banner -->
    <div v-if="filterOpportunityId" class="p-3.5 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 w-full">
      <div class="flex items-center gap-2.5 text-xs text-emerald-900 dark:text-emerald-300">
        <div class="p-1.5 rounded-lg bg-[#00C896] text-white">
          <Ruler class="h-4 w-4" />
        </div>
        <div>
          <p class="font-bold">تصفية المقايسات للفرصة البيعية #{{ filterOpportunityId }}</p>
          <p class="text-[11px] text-emerald-700/80 dark:text-emerald-400">يتم عرض مقايسات وحصر كميات هذه الفرصة فقط.</p>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <button
          type="button"
          @click="openCreateModal"
          class="px-3 py-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
        >
          + إنشاء مقايسة لهذه الفرصة
        </button>
        <button
          type="button"
          @click="clearOpportunityFilter"
          class="px-3 py-1.5 rounded-xl border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold transition-all cursor-pointer dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
        >
          عرض كل المقايسات
        </button>
      </div>
    </div>

    <!-- KPI Stats Cards -->
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('measurements.totalMeasurements') }}</span>
          <Layers class="h-4 w-4 text-gray-400" />
        </div>
        <p class="mt-2 text-xl font-black text-gray-900 dark:text-white">{{ stats.total }}</p>
      </div>

      <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-slate-600 dark:text-slate-400">{{ $t('measurements.draftMeasurements') }}</span>
          <FileText class="h-4 w-4 text-slate-400" />
        </div>
        <p class="mt-2 text-xl font-black text-slate-700 dark:text-slate-300">{{ stats.draft }}</p>
      </div>

      <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs dark:border-amber-900/30 dark:bg-amber-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-amber-700 dark:text-amber-400">{{ $t('measurements.underReviewMeasurements') }}</span>
          <Clock class="h-4 w-4 text-amber-500" />
        </div>
        <p class="mt-2 text-xl font-black text-amber-900 dark:text-amber-300">{{ stats.under_review }}</p>
      </div>

      <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs dark:border-emerald-900/30 dark:bg-emerald-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-emerald-700 dark:text-emerald-400">{{ $t('measurements.approvedMeasurements') }}</span>
          <CheckCircle2 class="h-4 w-4 text-emerald-500" />
        </div>
        <p class="mt-2 text-xl font-black text-emerald-900 dark:text-emerald-300">{{ stats.approved }}</p>
      </div>

      <div class="col-span-2 sm:col-span-1 rounded-2xl border border-teal-100 bg-teal-50/40 p-4 shadow-xs dark:border-teal-900/30 dark:bg-teal-950/20">
        <div class="flex items-center justify-between">
          <span class="text-[11px] font-bold text-teal-700 dark:text-teal-400">{{ $t('measurements.totalApprovedArea') }}</span>
          <Ruler class="h-4 w-4 text-teal-500" />
        </div>
        <p class="mt-2 text-xl font-black text-teal-900 dark:text-teal-300">
          {{ Number(stats.total_measured_area || 0).toLocaleString() }} <span class="text-xs font-normal">م²</span>
        </p>
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

      <div class="flex items-center gap-2">
        <select
          v-model="selectedStatus"
          @change="fetchMeasurements"
          class="rounded-xl border border-gray-200 bg-gray-50/50 px-3 py-2 text-xs font-semibold text-gray-700 outline-none focus:border-[#00C896] dark:border-gray-800 dark:bg-gray-800 dark:text-gray-200"
        >
          <option value="">{{ $t('measurements.filterAll') }}</option>
          <option value="Draft">{{ $t('measurements.filterDraft') }}</option>
          <option value="Under Review">{{ $t('measurements.filterUnderReview') }}</option>
          <option value="Approved">{{ $t('measurements.filterApproved') }}</option>
          <option value="Superseded">{{ $t('measurements.filterSuperseded') }}</option>
        </select>
      </div>
    </div>

    <!-- Measurements Table -->
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-xs dark:border-gray-800 dark:bg-gray-900">
      <div v-if="loading" class="flex h-64 items-center justify-center">
        <Loader2 class="h-8 w-8 animate-spin text-[#00C896]" />
      </div>

      <div v-else-if="measurements.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-3xl bg-gray-50 text-gray-400 dark:bg-gray-800/60">
          <Ruler class="h-8 w-8" />
        </div>
        <h3 class="mt-4 text-sm font-bold text-gray-900 dark:text-white">{{ $t('common.noData') }}</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $t('common.noDataDesc') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-right text-xs">
          <thead class="border-b border-gray-100 bg-gray-50/75 text-gray-500 dark:border-gray-800 dark:bg-gray-800/50 dark:text-gray-400">
            <tr>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.measurementNumber') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.opportunity') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.siteVisit') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.status') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.totalsSummary') }}</th>
              <th class="py-3.5 px-4 font-bold">{{ $t('measurements.measuredBy') }}</th>
              <th class="py-3.5 px-4 font-bold text-left">{{ $t('common.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            <tr
              v-for="item in measurements"
              :key="item.id"
              class="group hover:bg-gray-50/80 dark:hover:bg-gray-800/40 transition-colors"
            >
              <td class="py-3.5 px-4">
                <div class="flex items-center gap-2">
                  <span class="font-black text-gray-900 dark:text-white">{{ item.measurement_number }}</span>
                  <span class="inline-flex items-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                    V{{ item.version }}
                  </span>
                </div>
                <span class="text-[11px] text-gray-400">{{ item.measured_at || item.created_at?.slice(0, 10) }}</span>
              </td>

              <td class="py-3.5 px-4">
                <p class="font-bold text-gray-900 dark:text-white">{{ item.opportunity?.title || '—' }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ item.opportunity?.customer?.display_name || item.opportunity?.customer?.name || '—' }}</p>
              </td>

              <td class="py-3.5 px-4">
                <span
                  v-if="item.site_visit_id"
                  class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-1 text-[11px] font-bold text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                >
                  <MapPin class="h-3 w-3" />
                  معاينة #{{ item.site_visit_id }}
                </span>
                <span v-else class="text-[11px] text-gray-400">مخططات مكتبية</span>
              </td>

              <td class="py-3.5 px-4">
                <span
                  :class="{
                    'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300': item.status === 'Draft',
                    'bg-amber-50 text-amber-700 border border-amber-200/50 dark:bg-amber-950/40 dark:text-amber-300': item.status === 'Under Review',
                    'bg-emerald-50 text-emerald-700 border border-emerald-200/50 dark:bg-emerald-950/40 dark:text-emerald-300': item.status === 'Approved',
                    'bg-purple-50 text-purple-700 border border-purple-200/50 dark:bg-purple-950/40 dark:text-purple-300': item.status === 'Superseded',
                  }"
                  class="inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-[11px] font-bold"
                >
                  <span class="h-1.5 w-1.5 rounded-full" :class="{
                    'bg-slate-400': item.status === 'Draft',
                    'bg-amber-500': item.status === 'Under Review',
                    'bg-emerald-500': item.status === 'Approved',
                    'bg-purple-500': item.status === 'Superseded',
                  }"></span>
                  {{ getStatusLabel(item.status) }}
                </span>
              </td>

              <td class="py-3.5 px-4">
                <div class="flex flex-wrap gap-1.5">
                  <span v-if="Number(item.total_area) > 0" class="inline-flex items-center rounded-md bg-teal-50 px-1.5 py-0.5 text-[10px] font-bold text-teal-800 dark:bg-teal-950/40 dark:text-teal-300">
                    {{ Number(item.total_area).toLocaleString() }} م²
                  </span>
                  <span v-if="Number(item.total_volume) > 0" class="inline-flex items-center rounded-md bg-indigo-50 px-1.5 py-0.5 text-[10px] font-bold text-indigo-800 dark:bg-indigo-950/40 dark:text-indigo-300">
                    {{ Number(item.total_volume).toLocaleString() }} م³
                  </span>
                  <span v-if="Number(item.total_linear) > 0" class="inline-flex items-center rounded-md bg-cyan-50 px-1.5 py-0.5 text-[10px] font-bold text-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-300">
                    {{ Number(item.total_linear).toLocaleString() }} م.ط
                  </span>
                  <span v-if="Number(item.total_count) > 0" class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-bold text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                    {{ Number(item.total_count) }} قطعة
                  </span>
                </div>
              </td>

              <td class="py-3.5 px-4">
                <span class="font-medium text-gray-700 dark:text-gray-300">{{ item.measured_user?.name || '—' }}</span>
              </td>

              <td class="py-3.5 px-4 text-left">
                <div class="flex items-center justify-end gap-1.5">
                  <button
                    @click="openDetailsModal(item)"
                    title="عرض ومراجعة التفاصيل"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                  >
                    <Eye class="h-4 w-4" />
                  </button>

                  <button
                    v-if="item.status === 'Draft' || item.status === 'Under Review'"
                    @click="openEditModal(item)"
                    title="تعديل المقايسة"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                  >
                    <Edit3 class="h-4 w-4" />
                  </button>

                  <button
                    v-if="item.status === 'Approved'"
                    @click="createRevision(item)"
                    title="إنشاء إصدار جديد (Revision)"
                    class="rounded-lg p-1.5 text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                  >
                    <GitBranch class="h-4 w-4" />
                  </button>

                  <button
                    v-if="item.status !== 'Approved'"
                    @click="deleteMeasurement(item)"
                    title="حذف"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
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

    <!-- Takeoff Spreadsheet Grid Modal (Create / Edit) -->
    <div
      v-if="showEditModal"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-3 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative flex h-[92vh] w-full max-w-6xl flex-col rounded-3xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]">
              <Ruler class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ isEditing ? $t('measurements.editTitle') : $t('measurements.createTitle') }}
              </h3>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ form.measurement_number || 'مسودة جديدة' }}
              </p>
            </div>
          </div>

          <button
            @click="closeEditModal"
            class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Header Inputs Section -->
        <div class="grid grid-cols-1 gap-3.5 border-b border-gray-100 bg-gray-50/50 p-5 dark:border-gray-800 dark:bg-gray-800/30 sm:grid-cols-3 lg:grid-cols-4">
          <div>
            <label class="mb-1 block text-[11px] font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.opportunity') }} <span class="text-rose-500">*</span>
            </label>
            <select
              v-model="form.opportunity_id"
              :disabled="isEditing"
              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white disabled:opacity-60"
            >
              <option value="" disabled>{{ $t('measurements.selectOpportunity') }}</option>
              <option v-for="opp in opportunities" :key="opp.id" :value="opp.id">
                {{ opp.title }} ({{ opp.customer?.name || 'عميل' }})
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-[11px] font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.siteVisit') }}
            </label>
            <select
              v-model="form.site_visit_id"
              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
              <option :value="null">{{ $t('measurements.selectSiteVisit') }}</option>
              <option v-for="sv in siteVisits" :key="sv.id" :value="sv.id">
                معاينة #{{ sv.id }} ({{ sv.scheduled_date || sv.visit_date }})
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-[11px] font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.measuredBy') }}
            </label>
            <select
              v-model="form.measured_by"
              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
              <option :value="null">{{ $t('measurements.selectMeasuredBy') }}</option>
              <option v-for="u in engineers" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="mb-1 block text-[11px] font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.measuredAt') }}
            </label>
            <input
              v-model="form.measured_at"
              type="date"
              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            />
          </div>
        </div>

        <!-- Spreadsheet Takeoff Grid (Scrollable) -->
        <div class="flex-1 overflow-y-auto p-5">
          <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
            <div>
              <h4 class="text-xs font-black text-gray-900 dark:text-white">{{ $t('measurements.items') }}</h4>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">يمكنك كتابة الأبعاد لحسابها آلياً، أو كتابة الكمية الإجمالية مباشرة في حقل الصافي.</p>
            </div>

            <div class="flex items-center gap-2">
              <button
                type="button"
                @click="addStandardFinishingPreset"
                class="inline-flex items-center gap-1.5 rounded-xl bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 dark:hover:bg-purple-900/50 transition-colors cursor-pointer"
                title="إضافة بنود تشطيبات نموذجية (محارة، دهانات، أرضيات، أسقف)"
              >
                <Sparkles class="h-3.5 w-3.5" />
                <span>+ بنود تشطيبات سريعة</span>
              </button>

              <button
                type="button"
                @click="addItemRow"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300 dark:hover:bg-emerald-900/50 transition-colors cursor-pointer"
              >
                <Plus class="h-3.5 w-3.5" />
                <span>{{ $t('measurements.addItem') }}</span>
              </button>
            </div>
          </div>

          <div class="overflow-x-auto rounded-2xl border border-gray-200 dark:border-gray-800">
            <table class="w-full text-right text-xs">
              <thead class="bg-gray-50 text-[11px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                <tr>
                  <th class="py-2.5 px-3 w-10">#</th>
                  <th class="py-2.5 px-3 min-w-[140px]">{{ $t('measurements.roomName') }} <span class="text-rose-500">*</span></th>
                  <th class="py-2.5 px-3 min-w-[180px]">{{ $t('measurements.itemName') }} <span class="text-rose-500">*</span></th>
                  <th class="py-2.5 px-3 w-28">{{ $t('measurements.unit') }}</th>
                  <th class="py-2.5 px-3 w-20">{{ $t('measurements.count') }}</th>
                  <th class="py-2.5 px-3 w-20">{{ $t('measurements.length') }}</th>
                  <th class="py-2.5 px-3 w-20">{{ $t('measurements.width') }}</th>
                  <th class="py-2.5 px-3 w-20">{{ $t('measurements.height') }}</th>
                  <th class="py-2.5 px-3 w-24">{{ $t('measurements.deductions') }}</th>
                  <th class="py-2.5 px-3 w-24">{{ $t('measurements.grossQuantity') }}</th>
                  <th class="py-2.5 px-3 w-28 text-emerald-600 font-black">{{ $t('measurements.netQuantity') }}</th>
                  <th class="py-2.5 px-3 w-10"></th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr
                  v-for="(item, idx) in form.items"
                  :key="idx"
                  class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30"
                >
                  <td class="py-2 px-3 text-center text-gray-400 font-bold">{{ idx + 1 }}</td>

                  <td class="py-2 px-2">
                    <input
                      v-model="item.room_name"
                      type="text"
                      :placeholder="$t('measurements.roomPlaceholder')"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                  </td>

                  <td class="py-2 px-2">
                    <input
                      v-model="item.item_name"
                      type="text"
                      :placeholder="$t('measurements.itemPlaceholder')"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                  </td>

                  <td class="py-2 px-2">
                    <select
                      v-model="item.unit"
                      @change="onUnitChange(item)"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                      <option value="m2">{{ $t('measurements.typeArea') }}</option>
                      <option value="m3">{{ $t('measurements.typeVolume') }}</option>
                      <option value="lm">{{ $t('measurements.typeLinear') }}</option>
                      <option value="pcs">{{ $t('measurements.typeCount') }}</option>
                    </select>
                  </td>

                  <td class="py-2 px-1.5">
                    <input
                      v-model.number="item.count"
                      type="number"
                      step="0.01"
                      min="0"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />
                  </td>

                  <td class="py-2 px-1.5">
                    <input
                      v-model.number="item.length"
                      type="number"
                      step="0.01"
                      min="0"
                      :disabled="item.unit === 'pcs'"
                      placeholder="—"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:opacity-40"
                    />
                  </td>

                  <td class="py-2 px-1.5">
                    <input
                      v-model.number="item.width"
                      type="number"
                      step="0.01"
                      min="0"
                      :disabled="item.unit === 'lm' || item.unit === 'pcs'"
                      placeholder="—"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:opacity-40"
                    />
                  </td>

                  <td class="py-2 px-1.5">
                    <input
                      v-model.number="item.height"
                      type="number"
                      step="0.01"
                      min="0"
                      :disabled="item.unit !== 'm3'"
                      placeholder="—"
                      class="w-full rounded-lg border border-gray-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white disabled:bg-gray-100 dark:disabled:bg-gray-800 disabled:opacity-40"
                    />
                  </td>

                  <td class="py-2 px-1.5">
                    <input
                      v-model.number="item.deductions"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0"
                      class="w-full rounded-lg border border-rose-200 bg-white px-2 py-1.5 text-center text-xs font-bold text-rose-700 outline-none focus:border-rose-500 dark:border-rose-900/60 dark:bg-gray-900 dark:text-rose-300"
                    />
                  </td>

                  <td class="py-2 px-2 text-center font-bold text-gray-600 dark:text-gray-300">
                    {{ calculateGrossPreview(item) }}
                  </td>

                  <td class="py-2 px-2 text-center">
                    <!-- If dimensions entered: show calculated net -->
                    <span v-if="(item.length > 0 || item.width > 0 || item.height > 0)" class="font-black text-emerald-600 dark:text-emerald-400 font-mono text-xs">
                      {{ calculateNetPreview(item) }}
                    </span>
                    <!-- If no dimensions: allow direct quantity input -->
                    <input
                      v-else
                      v-model.number="item.net_quantity"
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="الكمية..."
                      class="w-full rounded-lg border border-emerald-300 bg-emerald-50/50 px-2 py-1 text-center text-xs font-black text-emerald-700 outline-none focus:border-emerald-500 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300"
                    />
                  </td>

                  <td class="py-2 px-2 text-center">
                    <button
                      type="button"
                      @click="removeItemRow(idx)"
                      class="rounded-lg p-1 text-gray-400 hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40 cursor-pointer"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Footer & Live Calculations Summary -->
        <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 bg-gray-50/80 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60 gap-4">
          <!-- Live Calculated Aggregates -->
          <div class="flex flex-wrap items-center gap-3 text-xs">
            <span class="font-bold text-gray-500 dark:text-gray-400">{{ $t('measurements.totalsSummary') }}:</span>
            <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1 font-bold text-teal-800 shadow-2xs dark:bg-gray-900 dark:text-teal-300 border border-teal-100 dark:border-teal-900/40">
              المسطحات: {{ computedTotals.totalArea }} م²
            </span>
            <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1 font-bold text-indigo-800 shadow-2xs dark:bg-gray-900 dark:text-indigo-300 border border-indigo-100 dark:border-indigo-900/40">
              المكعبات: {{ computedTotals.totalVolume }} م³
            </span>
            <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1 font-bold text-cyan-800 shadow-2xs dark:bg-gray-900 dark:text-cyan-300 border border-cyan-100 dark:border-cyan-900/40">
              الأطوال: {{ computedTotals.totalLinear }} م.ط
            </span>
            <span class="inline-flex items-center rounded-lg bg-white px-2.5 py-1 font-bold text-amber-800 shadow-2xs dark:bg-gray-900 dark:text-amber-300 border border-amber-100 dark:border-amber-900/40">
              العدد: {{ computedTotals.totalCount }} قطعة
            </span>
          </div>

          <div class="flex items-center gap-2.5">
            <button
              type="button"
              @click="closeEditModal"
              class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
              {{ $t('common.cancel') }}
            </button>

            <button
              type="button"
              :disabled="submitting"
              @click="saveMeasurement"
              class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] disabled:opacity-50 transition-colors"
            >
              <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
              <Save v-else class="h-4 w-4" />
              <span>{{ $t('common.save') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Review & Details Modal -->
    <div
      v-if="showDetailsModal && selectedMeasurement"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-3 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative flex h-[92vh] w-full max-w-5xl flex-col rounded-3xl bg-white shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        <!-- Details Header -->
        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
              <CheckCircle2 v-if="selectedMeasurement.status === 'Approved'" class="h-5 w-5" />
              <Ruler v-else class="h-5 w-5" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-sm font-black text-gray-900 dark:text-white">
                  مقايسة #{{ selectedMeasurement.measurement_number }}
                </h3>
                <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                  الإصدار V{{ selectedMeasurement.version }}
                </span>
              </div>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">
                {{ selectedMeasurement.opportunity?.title }} ({{ selectedMeasurement.opportunity?.customer?.display_name || selectedMeasurement.opportunity?.customer?.name }})
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              @click="printMeasurementSheet"
              class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
            >
              <Printer class="h-3.5 w-3.5" />
              <span>{{ $t('measurements.printSheetAction') }}</span>
            </button>

            <button
              @click="showDetailsModal = false"
              class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
        </div>

        <!-- Details Info & Takeoff Content -->
        <div class="flex-1 overflow-y-auto p-6 space-y-6" id="printable-measurement-sheet">
          <!-- Status Banner -->
          <div
            v-if="selectedMeasurement.status === 'Approved'"
            class="rounded-2xl border border-emerald-200 bg-emerald-50/60 p-4 dark:border-emerald-800 dark:bg-emerald-950/20"
          >
            <div class="flex items-start gap-3">
              <CheckCircle2 class="h-5 w-5 text-emerald-600 shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-black text-emerald-900 dark:text-emerald-300">{{ $t('measurements.approvedNotice') }}</h4>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-0.5">
                  تم الاعتماد بواسطة: {{ selectedMeasurement.approved_user?.name || 'المهندس المسؤول' }} بتاريخ {{ selectedMeasurement.approved_at?.slice(0, 16) || '—' }}.
                </p>
              </div>
            </div>
          </div>

          <div
            v-else-if="selectedMeasurement.status === 'Superseded'"
            class="rounded-2xl border border-purple-200 bg-purple-50/60 p-4 dark:border-purple-800 dark:bg-purple-950/20"
          >
            <div class="flex items-start gap-3">
              <History class="h-5 w-5 text-purple-600 shrink-0 mt-0.5" />
              <div>
                <h4 class="text-xs font-black text-purple-900 dark:text-purple-300">{{ $t('measurements.supersededNotice') }}</h4>
                <p class="text-[11px] text-purple-700 dark:text-purple-400 mt-0.5">
                  تم إنشاء إصدارات أحدث معتمدة لهذه الفرصة التجارية.
                </p>
              </div>
            </div>
          </div>

          <!-- Measurement Takeoff Table -->
          <div>
            <h4 class="mb-3 text-xs font-black text-gray-900 dark:text-white">{{ $t('measurements.items') }}</h4>
            <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-800">
              <table class="w-full text-right text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                  <tr>
                    <th class="py-2.5 px-3 w-10">#</th>
                    <th class="py-2.5 px-3">{{ $t('measurements.roomName') }}</th>
                    <th class="py-2.5 px-3">{{ $t('measurements.itemName') }}</th>
                    <th class="py-2.5 px-3">{{ $t('measurements.unit') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.count') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.length') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.width') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.height') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.deductions') }}</th>
                    <th class="py-2.5 px-3 text-center">{{ $t('measurements.grossQuantity') }}</th>
                    <th class="py-2.5 px-3 text-center font-black text-emerald-600">{{ $t('measurements.netQuantity') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-for="(it, i) in selectedMeasurement.items" :key="it.id">
                    <td class="py-2 px-3 text-center text-gray-400 font-bold">{{ i + 1 }}</td>
                    <td class="py-2 px-3 font-bold text-gray-900 dark:text-white">{{ it.room_name }}</td>
                    <td class="py-2 px-3 font-medium text-gray-700 dark:text-gray-300">{{ it.item_name }}</td>
                    <td class="py-2 px-3">
                      <span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 text-[10px] font-bold text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {{ it.unit }}
                      </span>
                    </td>
                    <td class="py-2 px-3 text-center font-semibold">{{ Number(it.count) }}</td>
                    <td class="py-2 px-3 text-center font-semibold">{{ it.length ? Number(it.length) : '—' }}</td>
                    <td class="py-2 px-3 text-center font-semibold">{{ it.width ? Number(it.width) : '—' }}</td>
                    <td class="py-2 px-3 text-center font-semibold">{{ it.height ? Number(it.height) : '—' }}</td>
                    <td class="py-2 px-3 text-center text-rose-600 font-bold">{{ Number(it.deductions) > 0 ? '-' + Number(it.deductions) : '0' }}</td>
                    <td class="py-2 px-3 text-center font-bold text-gray-600 dark:text-gray-300">{{ Number(it.gross_quantity) }}</td>
                    <td class="py-2 px-3 text-center font-black text-emerald-600 dark:text-emerald-400">{{ Number(it.net_quantity) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Workflow Action Bar -->
        <div class="flex items-center justify-between border-t border-gray-100 bg-gray-50/80 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/60">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold text-gray-500">إجمالي الحصر:</span>
            <span class="font-black text-teal-700 dark:text-teal-300 text-xs">{{ Number(selectedMeasurement.total_area) }} م²</span>
          </div>

          <div class="flex items-center gap-2">
            <button
              v-if="selectedMeasurement.status === 'Draft'"
              @click="submitForReview(selectedMeasurement)"
              class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-amber-600 transition-colors"
            >
              <Send class="h-3.5 w-3.5" />
              <span>{{ $t('measurements.submitReviewAction') }}</span>
            </button>

            <button
              v-if="canApprove && (selectedMeasurement.status === 'Draft' || selectedMeasurement.status === 'Under Review')"
              @click="approveMeasurement(selectedMeasurement)"
              class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-emerald-700 transition-colors"
            >
              <Check class="h-3.5 w-3.5" />
              <span>{{ $t('measurements.approveAction') }}</span>
            </button>

            <button
              v-if="selectedMeasurement.status === 'Approved'"
              @click="createRevision(selectedMeasurement)"
              class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition-colors"
            >
              <GitBranch class="h-3.5 w-3.5" />
              <span>{{ $t('measurements.createRevisionAction') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Import From Site Visit Modal -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/10 text-[#00A87E]">
              <DownloadCloud class="h-5 w-5" />
            </div>
            <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('measurements.importModalTitle') }}</h3>
          </div>
          <button @click="showImportModal = false" class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
            <X class="h-4 w-4" />
          </button>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400 py-3">
          {{ $t('measurements.importModalDesc') }}
        </p>

        <div class="space-y-3.5 py-2">
          <div>
            <label class="mb-1 block text-xs font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.selectVisitToImport') }}
            </label>
            <select
              v-model="importSiteVisitId"
              class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-semibold text-gray-900 outline-none focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            >
              <option value="" disabled>اختر المعاينة الميدانية...</option>
              <option v-for="sv in siteVisits" :key="sv.id" :value="sv.id">
                معاينة #{{ sv.id }} - {{ sv.opportunity?.title || 'معاينة موقع' }} ({{ sv.customer?.name }})
              </option>
            </select>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-800">
          <button
            @click="showImportModal = false"
            class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
          >
            {{ $t('common.cancel') }}
          </button>

          <button
            :disabled="!importSiteVisitId || importing"
            @click="executeImport"
            class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] disabled:opacity-50 transition-colors"
          >
            <Loader2 v-if="importing" class="h-4 w-4 animate-spin" />
            <DownloadCloud v-else class="h-4 w-4" />
            <span>{{ $t('measurements.importAction') }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import { useAuthStore } from '../../stores/auth';
import {
  Ruler,
  Plus,
  Search,
  Layers,
  FileText,
  Clock,
  CheckCircle2,
  MapPin,
  Eye,
  Edit3,
  GitBranch,
  Trash2,
  X,
  Save,
  DownloadCloud,
  Loader2,
  Send,
  Check,
  Printer,
  History,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

const filterOpportunityId = computed(() => route.query.opportunity_id || '');

const canCreate = computed(() => authStore.hasPermission('measurements.create'));
const canApprove = computed(() => authStore.hasPermission('measurements.approve'));

const loading = ref(false);
const submitting = ref(false);
const importing = ref(false);
const measurements = ref([]);
const opportunities = ref([]);
const siteVisits = ref([]);
const engineers = ref([]);

const searchQuery = ref('');
const selectedStatus = ref('');
const stats = reactive({
  total: 0,
  draft: 0,
  under_review: 0,
  approved: 0,
  superseded: 0,
  total_measured_area: 0,
});

const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showImportModal = ref(false);
const isEditing = ref(false);
const selectedMeasurement = ref(null);
const importSiteVisitId = ref('');

const form = reactive({
  id: null,
  opportunity_id: '',
  site_visit_id: null,
  measurement_number: '',
  version: 1,
  measured_by: null,
  measured_at: new Date().toISOString().slice(0, 10),
  notes: '',
  items: [],
});

const getStatusLabel = (status) => {
  const map = {
    Draft: t('measurements.statusDraft'),
    'Under Review': t('measurements.statusUnderReview'),
    Approved: t('measurements.statusApproved'),
    Superseded: t('measurements.statusSuperseded'),
  };
  return map[status] || status;
};

// Item live formulas
const calculateGrossPreview = (item) => {
  const count = Number(item.count) || 1;
  const l = Number(item.length) || 0;
  const w = Number(item.width) || 0;
  const h = Number(item.height) || 0;

  if (l === 0 && w === 0 && h === 0 && item.net_quantity !== undefined && item.net_quantity !== null && item.net_quantity !== '') {
    return Number(item.net_quantity) + (Number(item.deductions) || 0);
  }

  let gross = 0;
  switch (item.unit) {
    case 'm3':
      gross = count * l * w * h;
      break;
    case 'lm':
      gross = count * l;
      break;
    case 'pcs':
      gross = count;
      break;
    case 'm2':
    default:
      gross = count * l * w;
      break;
  }
  return Number(gross.toFixed(2));
};

const calculateNetPreview = (item) => {
  const l = Number(item.length) || 0;
  const w = Number(item.width) || 0;
  const h = Number(item.height) || 0;

  if (l === 0 && w === 0 && h === 0 && item.net_quantity !== undefined && item.net_quantity !== null && item.net_quantity !== '') {
    return Number(item.net_quantity);
  }

  const gross = calculateGrossPreview(item);
  const deductions = Number(item.deductions) || 0;
  const net = Math.max(0, gross - deductions);
  return Number(net.toFixed(2));
};

const addStandardFinishingPreset = () => {
  const lastRoom = form.items.length > 0 ? (form.items[form.items.length - 1].room_name || 'غرفة جديدة') : 'ريسبشن';
  const presets = [
    { room_name: lastRoom, item_name: 'بياض محارة حوائط وأسقف', unit: 'm2', count: 1, length: null, width: null, height: null, deductions: 0, net_quantity: null, notes: '' },
    { room_name: lastRoom, item_name: 'دهانات وجه تأسيس وتشطيب', unit: 'm2', count: 1, length: null, width: null, height: null, deductions: 0, net_quantity: null, notes: '' },
    { room_name: lastRoom, item_name: 'أرضيات سيراميك / بورسلين', unit: 'm2', count: 1, length: null, width: null, height: null, deductions: 0, net_quantity: null, notes: '' },
    { room_name: lastRoom, item_name: 'أسقف جبسوم بورد فلات', unit: 'm2', count: 1, length: null, width: null, height: null, deductions: 0, net_quantity: null, notes: '' },
  ];
  form.items.push(...presets);
};

const computedTotals = computed(() => {
  let totalArea = 0;
  let totalVolume = 0;
  let totalLinear = 0;
  let totalCount = 0;

  form.items.forEach((item) => {
    const net = calculateNetPreview(item);
    switch (item.unit) {
      case 'm3':
        totalVolume += net;
        break;
      case 'lm':
        totalLinear += net;
        break;
      case 'pcs':
        totalCount += net;
        break;
      case 'm2':
      default:
        totalArea += net;
        break;
    }
  });

  return {
    totalArea: totalArea.toFixed(2),
    totalVolume: totalVolume.toFixed(2),
    totalLinear: totalLinear.toFixed(2),
    totalCount: totalCount.toFixed(2),
  };
});

const onUnitChange = (item) => {
  if (item.unit === 'pcs') {
    item.length = null;
    item.width = null;
    item.height = null;
  } else if (item.unit === 'lm') {
    item.width = null;
    item.height = null;
  } else if (item.unit === 'm2') {
    item.height = null;
  }
};

const addItemRow = () => {
  form.items.push({
    room_name: '',
    item_name: '',
    unit: 'm2',
    count: 1,
    length: null,
    width: null,
    height: null,
    deductions: 0,
    notes: '',
  });
};

const removeItemRow = (idx) => {
  form.items.splice(idx, 1);
};

// API calls
const fetchMeasurements = async () => {
  loading.value = true;
  try {
    const params = {};
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedStatus.value) params.status = selectedStatus.value;
    if (filterOpportunityId.value) params.opportunity_id = filterOpportunityId.value;

    const res = await api.get('/measurements', { params });
    if (res.data?.success) {
      measurements.value = res.data.data;
      if (res.data.stats) {
        Object.assign(stats, res.data.stats);
      }
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: 'تعذر تحميل بيانات المقايسات.',
    });
  } finally {
    loading.value = false;
  }
};

const clearOpportunityFilter = () => {
  window.history.replaceState({}, '', window.location.pathname);
  window.location.reload();
};

let debounceTimer = null;
const debounceFetch = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(fetchMeasurements, 300);
};

const fetchDependencies = async () => {
  try {
    const [oppRes, svRes, userRes] = await Promise.all([
      api.get('/opportunities?per_page=100'),
      api.get('/site-visits?per_page=100'),
      api.get('/users?per_page=100'),
    ]);

    if (oppRes.data?.success) opportunities.value = oppRes.data.data;
    if (svRes.data?.success) siteVisits.value = svRes.data.data;
    if (userRes.data?.success) engineers.value = userRes.data.data;
  } catch (e) {
    console.error('Failed to load dependencies', e);
  }
};

const openCreateModal = () => {
  isEditing.value = false;
  form.id = null;
  form.opportunity_id = filterOpportunityId.value ? Number(filterOpportunityId.value) : (opportunities.value[0]?.id || '');
  form.site_visit_id = null;
  form.measurement_number = '';
  form.version = 1;
  form.measured_by = authStore.user?.id || null;
  form.measured_at = new Date().toISOString().slice(0, 10);
  form.notes = '';
  form.items = [
    {
      room_name: 'ريسبشن',
      item_name: 'دهانات حوائط وسقف',
      unit: 'm2',
      count: 1,
      length: 6,
      width: 4,
      height: null,
      deductions: 2,
      notes: '',
    },
  ];
  showEditModal.value = true;
};

const openEditModal = (item) => {
  isEditing.value = true;
  form.id = item.id;
  form.opportunity_id = item.opportunity_id;
  form.site_visit_id = item.site_visit_id;
  form.measurement_number = item.measurement_number;
  form.version = item.version;
  form.measured_by = item.measured_by;
  form.measured_at = item.measured_at || new Date().toISOString().slice(0, 10);
  form.notes = item.notes || '';
  form.items = (item.items || []).map((it) => ({
    room_name: it.room_name,
    item_name: it.item_name,
    unit: it.unit,
    count: Number(it.count),
    length: it.length !== null ? Number(it.length) : null,
    width: it.width !== null ? Number(it.width) : null,
    height: it.height !== null ? Number(it.height) : null,
    deductions: Number(it.deductions) || 0,
    notes: it.notes || '',
  }));
  showEditModal.value = true;
};

const closeEditModal = () => {
  showEditModal.value = false;
};

const openDetailsModal = (item) => {
  selectedMeasurement.value = item;
  showDetailsModal.value = true;
};

const openImportModal = () => {
  importSiteVisitId.value = siteVisits.value[0]?.id || '';
  showImportModal.value = true;
};

const saveMeasurement = async () => {
  if (!form.opportunity_id) {
    notificationStore.addToast({
      type: 'error',
      title: 'تنبيه',
      message: 'يرجى اختيار الفرصة التجارية.',
    });
    return;
  }

  if (form.items.length === 0) {
    notificationStore.addToast({
      type: 'error',
      title: 'تنبيه',
      message: t('measurements.noItemsWarning'),
    });
    return;
  }

  submitting.value = true;
  try {
    const payload = {
      opportunity_id: form.opportunity_id,
      site_visit_id: form.site_visit_id,
      measurement_number: form.measurement_number || null,
      version: form.version,
      measured_by: form.measured_by,
      measured_at: form.measured_at,
      notes: form.notes,
      items: form.items,
    };

    if (isEditing.value && form.id) {
      await api.put(`/measurements/${form.id}`, payload);
      notificationStore.addToast({
        type: 'success',
        title: 'تم الحفظ',
        message: t('measurements.savedSuccessfully'),
      });
    } else {
      await api.post('/measurements', payload);
      notificationStore.addToast({
        type: 'success',
        title: 'تم الإنشاء',
        message: t('measurements.savedSuccessfully'),
      });
    }

    closeEditModal();
    fetchMeasurements();
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الحفظ',
      message: err.response?.data?.message || t('common.validationError'),
    });
  } finally {
    submitting.value = false;
  }
};

const executeImport = async () => {
  if (!importSiteVisitId.value) return;

  importing.value = true;
  try {
    const res = await api.post(`/measurements/import-from-site-visit/${importSiteVisitId.value}`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم الاستيراد',
        message: t('measurements.site_visit_rooms_imported_success') || 'تم استيراد غرف المعاينة بنجاح.',
      });
      showImportModal.value = false;
      fetchMeasurements();
      openEditModal(res.data.data);
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الاستيراد',
      message: err.response?.data?.message || 'تعذر استيراد الفراغات.',
    });
  } finally {
    importing.value = false;
  }
};

const submitForReview = async (item) => {
  try {
    const res = await api.post(`/measurements/${item.id}/submit-review`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم التقديم',
        message: 'تم إرسال المقايسة للمراجعة الفنية.',
      });
      showDetailsModal.value = false;
      fetchMeasurements();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر تقديم المقايسة للمراجعة.',
    });
  }
};

const approveMeasurement = async (item) => {
  if (!confirm(t('measurements.approveConfirm'))) return;

  try {
    const res = await api.post(`/measurements/${item.id}/approve`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم الاعتماد',
        message: 'تم اعتماد المقايسة الهندسية بنجاح.',
      });
      showDetailsModal.value = false;
      fetchMeasurements();
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الاعتماد',
      message: err.response?.data?.message || 'تعذر اعتماد المقايسة.',
    });
  }
};

const createRevision = async (item) => {
  if (!confirm(t('measurements.createRevisionConfirm'))) return;

  try {
    const res = await api.post(`/measurements/${item.id}/create-revision`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم إنشاء الإصدار',
        message: 'تم إنشاء إصدار مراجعة مسودة جديدة بنجاح.',
      });
      showDetailsModal.value = false;
      fetchMeasurements();
      openEditModal(res.data.data);
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: err.response?.data?.message || 'تعذر إنشاء الإصدار الجديد.',
    });
  }
};

const deleteMeasurement = async (item) => {
  if (!confirm(t('common.confirmDelete'))) return;

  try {
    await api.delete(`/measurements/${item.id}`);
    notificationStore.addToast({
      type: 'success',
      title: 'تم الحذف',
      message: t('common.successDelete'),
    });
    fetchMeasurements();
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الحذف',
      message: err.response?.data?.message || 'تعذر حذف المقايسة.',
    });
  }
};

const printMeasurementSheet = () => {
  window.print();
};

onMounted(() => {
  fetchMeasurements();
  fetchDependencies();
});
</script>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }
  #printable-measurement-sheet,
  #printable-measurement-sheet * {
    visibility: visible;
  }
  #printable-measurement-sheet {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    background: white !important;
    color: black !important;
  }
}
</style>
