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
          @click="openDxfImportModal"
          class="inline-flex items-center gap-2 rounded-xl border border-blue-200 bg-blue-50/50 px-4 py-2.5 text-xs font-bold text-blue-700 shadow-xs hover:bg-blue-100 dark:border-blue-900/40 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/60 transition-colors cursor-pointer"
        >
          <FileCode2 class="h-4 w-4 text-blue-600 dark:text-blue-400" />
          <span>{{ $t('measurements.importFromDxf') || 'استيراد من أوتوكاد (DXF)' }}</span>
        </button>

        <button
          v-if="canCreate"
          @click="openImportModal"
          class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-gray-700 shadow-xs hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800 transition-colors cursor-pointer"
        >
          <DownloadCloud class="h-4 w-4 text-[#00C896]" />
          <span>{{ $t('measurements.importFromSiteVisit') }}</span>
        </button>

        <button
          v-if="canCreate"
          @click="openCreateModal"
          class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] transition-colors cursor-pointer"
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

      <div class="w-48">
        <SearchableSelect
          :model-value="selectedStatus"
          :options="statusFilterOptions"
          :placeholder="$t('measurements.filterAll')"
          :allow-empty="true"
          :clearable="true"
          @update:model-value="onStatusFilterChange"
        />
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

                  <router-link
                    v-if="item.status === 'Approved'"
                    :to="`/scopes?opportunity_id=${item.opportunity_id}`"
                    title="نطاق الأعمال والمواصفات (Scope of Work)"
                    class="rounded-lg p-1.5 text-teal-600 hover:bg-teal-50 dark:hover:bg-teal-950/40 transition-colors"
                  >
                    <ClipboardList class="h-4 w-4" />
                  </router-link>

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
            <SearchableSelect
              :model-value="form.opportunity_id"
              :options="formattedOpportunityOptions"
              :placeholder="$t('measurements.selectOpportunity')"
              :disabled="isEditing"
              :clearable="false"
              @update:model-value="form.opportunity_id = $event"
            />
          </div>

          <div>
            <div class="flex items-center justify-between mb-1">
              <label class="block text-[11px] font-bold text-gray-700 dark:text-gray-300">
                {{ $t('measurements.siteVisit') }}
              </label>
              <button
                v-if="form.site_visit_id"
                type="button"
                @click="importRoomsFromCurrentFormSiteVisit"
                class="text-[10px] font-bold text-teal-600 hover:text-teal-700 dark:text-teal-400 hover:underline cursor-pointer"
                title="جلب الغرف المسجلة في هذه المعاينة إلى الجدول"
              >
                + جلب غرف المعاينة
              </button>
            </div>
            <SearchableSelect
              :model-value="form.site_visit_id"
              :options="formattedSiteVisitOptions"
              :placeholder="$t('measurements.selectSiteVisit')"
              :clearable="true"
              @update:model-value="onFormSiteVisitChange($event)"
            />
          </div>

          <div>
            <label class="mb-1 block text-[11px] font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.measuredBy') }}
            </label>
            <SearchableSelect
              :model-value="form.measured_by"
              :options="formattedEngineerOptions"
              :placeholder="$t('measurements.selectMeasuredBy')"
              :clearable="true"
              @update:model-value="form.measured_by = $event"
            />
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

            <div class="flex flex-wrap items-center gap-2">
              <button
                type="button"
                @click="openDxfImportForCurrentForm"
                class="inline-flex items-center gap-1.5 rounded-xl bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:hover:bg-blue-900/50 transition-colors cursor-pointer"
                title="استيراد الغرف والأبعاد مباشرة من ملف أوتوكاد DXF إلى هذا الجدول"
              >
                <FileCode2 class="h-3.5 w-3.5" />
                <span>+ استيراد من DXF</span>
              </button>

              <button
                v-if="form.site_visit_id"
                type="button"
                @click="importRoomsFromCurrentFormSiteVisit"
                class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700 hover:bg-teal-100 dark:bg-teal-950/40 dark:text-teal-300 dark:hover:bg-teal-900/50 transition-colors cursor-pointer"
                title="جلب الغرف المسجلة في المعاينة الميدانية المحددة إلى الجدول"
              >
                <DownloadCloud class="h-3.5 w-3.5" />
                <span>+ جلب غرف المعاينة</span>
              </button>

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

                  <td class="py-2 px-2 min-w-[110px]">
                    <SearchableSelect
                      :model-value="item.unit"
                      :options="unitOptions"
                      :clearable="false"
                      :allow-empty="false"
                      @update:model-value="onItemUnitUpdate(item, $event)"
                    />
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

    <!-- 1. Import From Site Visit Modal -->
    <div
      v-if="showImportModal"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative w-full max-w-lg rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#00C896]/10 text-[#00A87E]">
              <DownloadCloud class="h-5 w-5" />
            </div>
            <h3 class="text-sm font-black text-gray-900 dark:text-white">{{ $t('measurements.importModalTitle') }}</h3>
          </div>
          <button @click="showImportModal = false" class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
            <X class="h-4 w-4" />
          </button>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ $t('measurements.importModalDesc') }}
        </p>

        <div class="space-y-4 py-1">
          <!-- 1. Select Site Visit -->
          <div>
            <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
              {{ $t('measurements.selectVisitToImport') }} <span class="text-rose-500">*</span>
            </label>
            <SearchableSelect
              :model-value="importSiteVisitId"
              :options="formattedSiteVisitOptions"
              :placeholder="$t('measurements.selectVisitToImport') || 'اختر المعاينة الميدانية...'"
              :clearable="true"
              @update:model-value="onImportSiteVisitSelect($event)"
            />
          </div>

          <!-- 2. Selected Site Visit Information & Explicit Opportunity Selector -->
          <div v-if="selectedVisitForImport" class="p-3.5 rounded-2xl bg-gray-50/90 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 space-y-3">
            <div class="flex items-center justify-between text-xs pb-2 border-b border-gray-200/60 dark:border-gray-700">
              <span class="text-gray-500 font-medium">العميل المرتبط بالمعاينة:</span>
              <span class="font-bold text-gray-900 dark:text-white">{{ selectedVisitForImport.customer?.name || 'عميل غير محدد' }}</span>
            </div>

            <!-- Opportunity Selector -->
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                الفرصة التجارية المرتبطة بالمقايسة <span class="text-rose-500">*</span>
              </label>

              <!-- If Site Visit has a linked Opportunity -->
              <div v-if="selectedVisitForImport.opportunity" class="p-2.5 rounded-xl bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/40 text-xs flex items-center justify-between text-blue-900 dark:text-blue-300">
                <span class="font-bold">#{{ selectedVisitForImport.opportunity.id }} - {{ selectedVisitForImport.opportunity.title }}</span>
                <span class="text-[10px] bg-blue-200/60 dark:bg-blue-900/60 px-2 py-0.5 rounded-md font-bold">مرتبطة بالمعاينة</span>
              </div>

              <!-- If Site Visit is Direct (No Opportunity) -> Pick from Customer's open opportunities -->
              <div v-else class="space-y-2">
                <SearchableSelect
                  v-if="availableOpportunitiesForVisitCustomer.length > 0"
                  :model-value="importOpportunityId"
                  :options="availableOpportunitiesForVisitCustomer"
                  :placeholder="'اختر فرصة تجارية مفتوحة للعميل...'"
                  :clearable="false"
                  @update:model-value="importOpportunityId = $event"
                />

                <div v-else class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/40 text-xs text-amber-900 dark:text-amber-300 space-y-1.5">
                  <p class="font-bold flex items-center gap-1.5">
                    <AlertCircle class="h-4 w-4 text-amber-600 shrink-0" />
                    <span>لا توجد فرص تجارية مسجلة لهذا العميل</span>
                  </p>
                  <p class="text-[11px] text-amber-700 dark:text-amber-400 leading-relaxed">
                    يلزم تحديد فرصة تجارية صريحة لربط المقايسة الهندسية بها. يرجى إنشاء فرصة تجارية للعميل أولاً قبل الاستيراد.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-gray-100 dark:border-gray-800">
          <button
            @click="showImportModal = false"
            class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>

          <button
            :disabled="!importSiteVisitId || !importOpportunityId || importing"
            @click="executeImport"
            class="inline-flex items-center gap-2 rounded-xl bg-[#00C896] px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-[#00A87E] disabled:opacity-50 transition-colors cursor-pointer"
          >
            <Loader2 v-if="importing" class="h-4 w-4 animate-spin" />
            <DownloadCloud v-else class="h-4 w-4" />
            <span>{{ $t('measurements.importAction') }}</span>
          </button>
        </div>
      </div>
    </div>

    <!-- 2. Import From AutoCAD (DXF) Modal -->
    <div
      v-if="showDxfModal"
      class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-black/60 p-4 backdrop-blur-xs animate-in fade-in duration-150"
    >
      <div class="relative w-full max-w-3xl rounded-3xl bg-white p-6 shadow-2xl dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-5 max-h-[90vh] overflow-y-auto custom-scrollbar">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-2.5">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
              <FileCode2 class="h-5 w-5" />
            </div>
            <div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">استيراد أبعاد ومساحات الغرف من أوتوكاد (DXF)</h3>
              <p class="text-[11px] text-gray-500 dark:text-gray-400">حساب هندسي دقيق لمساحات ومحيط الغرف باستخدام معادلة Shoelace الرياضية</p>
            </div>
          </div>
          <button @click="closeDxfModal" class="rounded-xl p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 cursor-pointer">
            <X class="h-4 w-4" />
          </button>
        </div>

        <!-- Step 1: Upload File -->
        <div class="space-y-4">
          <div
            @dragover.prevent
            @drop.prevent="onDxfFileDrop"
            class="border-2 border-dashed rounded-2xl p-6 text-center transition-all cursor-pointer"
            :class="dxfFile ? 'border-blue-500 bg-blue-50/40 dark:bg-blue-950/20' : 'border-gray-200 hover:border-blue-400 dark:border-gray-700'"
            @click="triggerDxfFileInput"
          >
            <input
              ref="dxfFileInputRef"
              type="file"
              accept=".dxf"
              class="hidden"
              @change="onDxfFileChange"
            />
            <div class="flex flex-col items-center gap-2">
              <div class="p-3 rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30">
                <UploadCloud class="h-6 w-6" />
              </div>
              <div v-if="dxfFile">
                <p class="text-xs font-bold text-gray-900 dark:text-white">{{ dxfFile.name }}</p>
                <p class="text-[11px] text-gray-500 font-mono">حجم الملف: {{ (dxfFile.size / 1024).toFixed(1) }} KB</p>
              </div>
              <div v-else>
                <p class="text-xs font-bold text-gray-700 dark:text-gray-300">انقر هنا لاختيار ملف .DXF أو اسحبه وأفلته هنا</p>
                <p class="text-[11px] text-gray-400 mt-0.5">الصيغة المدعومة: DXF فقط (حتى 10 ميجابايت)</p>
              </div>
            </div>
          </div>

          <!-- Format notice banner -->
          <div class="p-3 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 text-[11px] text-gray-600 dark:text-gray-400 flex items-center gap-2">
            <Info class="h-4 w-4 text-blue-500 shrink-0" />
            <span>الصيغة المدعومة حاليًا هي DXF فقط. لو عندك ملف DWG، افتحه بالأوتوكاد واعمل Save As → DXF ثم ارفعه هنا.</span>
          </div>

          <!-- Parsing Loader -->
          <div v-if="dxfParsing" class="flex items-center justify-center gap-2 py-4 text-xs font-bold text-blue-600">
            <Loader2 class="h-4 w-4 animate-spin" />
            <span>جاري فحص الطبقات (Layers) وقراءة المضلعات الهندسية...</span>
          </div>

          <!-- Step 2: Layer & Opportunity Selectors (Once parsed) -->
          <div v-if="dxfSummary && !dxfParsing" class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
            <!-- Rooms Layer -->
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                Layer حدود الغرف (Polylines) <span class="text-rose-500">*</span>
              </label>
              <SearchableSelect
                :model-value="dxfRoomsLayer"
                :options="dxfRoomsLayerOptions"
                :placeholder="'اختر Layer الغرف...'"
                :clearable="false"
                @update:model-value="onRoomsLayerChange($event)"
              />
              <p v-if="dxfRoomsLayerOptions.length === 0" class="mt-1 text-[11px] text-rose-500">
                لم يتم العثور على مضلعات مغلقة في أي طبقة بالملف.
              </p>
            </div>

            <!-- Labels Layer -->
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                Layer أسماء الغرف (Labels/Text)
              </label>
              <SearchableSelect
                :model-value="dxfLabelsLayer"
                :options="dxfLabelsLayerOptions"
                :placeholder="'(اختياري) اختر Layer الأسماء...'"
                :clearable="true"
                @update:model-value="onLabelsLayerChange($event)"
              />
            </div>

            <!-- Opportunity Link -->
            <div>
              <label class="mb-1.5 block text-xs font-bold text-gray-700 dark:text-gray-300">
                الفرصة التجارية المرتبطة <span class="text-rose-500">*</span>
              </label>
              <SearchableSelect
                :model-value="dxfOpportunityId"
                :options="formattedOpportunityOptions"
                :placeholder="'اختر الفرصة التجارية...'"
                :clearable="false"
                @update:model-value="dxfOpportunityId = $event"
              />
            </div>
          </div>

          <!-- Step 3: Interactive Preview Table of Detected Rooms -->
          <div v-if="dxfPreviewRooms.length > 0" class="space-y-2">
            <div class="flex items-center justify-between">
              <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
                <CheckCircle2 class="h-4 w-4 text-emerald-500" />
                <span>الغرف المكتشفة بالملف ({{ dxfPreviewRooms.length }} غرفة)</span>
              </h4>
              <span class="text-xs font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2.5 py-1 rounded-xl">
                إجمالي المساحة: {{ dxfTotalPreviewArea.toFixed(2) }} م²
              </span>
            </div>

            <div class="max-h-56 overflow-y-auto rounded-2xl border border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-900 custom-scrollbar">
              <table class="w-full text-right text-xs">
                <thead class="bg-gray-50 text-[11px] font-bold text-gray-500 dark:bg-gray-800/80 dark:text-gray-400">
                  <tr>
                    <th class="py-2.5 px-3">#</th>
                    <th class="py-2.5 px-3">اسم الفراغ / الغرفة</th>
                    <th class="py-2.5 px-3 text-center">الأبعاد (طول × عرض)</th>
                    <th class="py-2.5 px-3 text-center">المساحة (Shoelace)</th>
                    <th class="py-2.5 px-3 text-center">المحيط (م.ط)</th>
                    <th class="py-2.5 px-3 text-center">الأضلاع</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                  <tr v-for="(room, rIdx) in dxfPreviewRooms" :key="rIdx" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                    <td class="py-2 px-3 font-mono text-gray-400">{{ rIdx + 1 }}</td>
                    <td class="py-2 px-3">
                      <input
                        v-model="room.room_name"
                        type="text"
                        class="w-full rounded-lg border border-gray-200 px-2.5 py-1 text-xs font-bold text-gray-900 outline-hidden focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                      />
                    </td>
                    <td class="py-2 px-3 text-center font-mono font-bold text-blue-600 dark:text-blue-400">
                      <span v-if="room.length && room.width">{{ Number(room.length).toFixed(2) }} × {{ Number(room.width).toFixed(2) }} م</span>
                      <span v-else class="text-[11px] text-amber-600 dark:text-amber-400 font-normal">شكل غير منتظم</span>
                    </td>
                    <td class="py-2 px-3 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">
                      {{ Number(room.area).toFixed(2) }} م²
                    </td>
                    <td class="py-2 px-3 text-center font-mono text-gray-500">
                      {{ Number(room.perimeter || 0).toFixed(2) }}
                    </td>
                    <td class="py-2 px-3 text-center font-mono text-gray-400">
                      {{ room.vertex_count }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Modal Actions -->
        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-gray-100 dark:border-gray-800">
          <button
            @click="closeDxfModal"
            class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 cursor-pointer"
          >
            {{ $t('common.cancel') }}
          </button>

          <button
            :disabled="!dxfFile || !dxfRoomsLayer || (!dxfOpportunityId && dxfImportMode !== 'form_append') || dxfImporting"
            @click="executeDxfImport"
            class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-5 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700 disabled:opacity-50 transition-colors cursor-pointer"
          >
            <Loader2 v-if="dxfImporting" class="h-4 w-4 animate-spin" />
            <DownloadCloud v-else class="h-4 w-4" />
            <span v-if="dxfImportMode === 'form_append'">إدراج الغرف في جدول المقايسة</span>
            <span v-else>استيراد وإنشاء مسودة المقايسة</span>
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
  FileCode2,
  UploadCloud,
  Info,
  AlertCircle,
  ClipboardList,
} from 'lucide-vue-next';
import SearchableSelect from '../../components/common/SearchableSelect.vue';

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

const statusFilterOptions = computed(() => [
  { value: '', label: t('measurements.filterAll') },
  { value: 'Draft', label: t('measurements.filterDraft') },
  { value: 'Under Review', label: t('measurements.filterUnderReview') },
  { value: 'Approved', label: t('measurements.filterApproved') },
  { value: 'Superseded', label: t('measurements.filterSuperseded') },
]);

const formattedOpportunityOptions = computed(() => {
  return (opportunities.value || []).map((opp) => ({
    value: opp.id,
    label: `${opp.title} (${opp.customer?.name || 'عميل'})`,
  }));
});

const formattedSiteVisitOptions = computed(() => {
  return (siteVisits.value || []).map((sv) => ({
    value: sv.id,
    label: `معاينة #${sv.id} - ${sv.opportunity?.title || 'معاينة موقع'} (${sv.customer?.name || ''} ${sv.scheduled_date || sv.visit_date ? `- ${sv.scheduled_date || sv.visit_date}` : ''})`,
  }));
});

const formattedEngineerOptions = computed(() => {
  return (engineers.value || []).map((u) => ({
    value: u.id,
    label: u.name,
  }));
});

const unitOptions = computed(() => [
  { value: 'm2', label: t('measurements.typeArea') },
  { value: 'm3', label: t('measurements.typeVolume') },
  { value: 'lm', label: t('measurements.typeLinear') },
  { value: 'pcs', label: t('measurements.typeCount') },
]);

const onStatusFilterChange = (val) => {
  selectedStatus.value = val || '';
  fetchMeasurements();
};

const onItemUnitUpdate = (item, val) => {
  item.unit = val;
  onUnitChange(item);
};

const showEditModal = ref(false);
const showDetailsModal = ref(false);
const showImportModal = ref(false);
const isEditing = ref(false);
const selectedMeasurement = ref(null);
const importSiteVisitId = ref('');
const importOpportunityId = ref('');

const selectedVisitForImport = computed(() => {
  return siteVisits.value.find((v) => String(v.id) === String(importSiteVisitId.value)) || null;
});

const availableOpportunitiesForVisitCustomer = computed(() => {
  if (!selectedVisitForImport.value?.customer_id) return [];
  const cId = selectedVisitForImport.value.customer_id;
  return (opportunities.value || [])
    .filter((opp) => opp.customer_id === cId)
    .map((opp) => ({
      value: opp.id,
      label: `#${opp.id} - ${opp.title} (${opp.stage || 'مفتوحة'})`,
    }));
});

// DXF Import States
const showDxfModal = ref(false);
const dxfFile = ref(null);
const dxfFileInputRef = ref(null);
const dxfParsing = ref(false);
const dxfImporting = ref(false);
const dxfSummary = ref(null);
const dxfRoomsLayer = ref('');
const dxfLabelsLayer = ref('');
const dxfOpportunityId = ref('');
const dxfPreviewRooms = ref([]);

const dxfRoomsLayerOptions = computed(() => {
  if (!dxfSummary.value?.layers_found) return [];
  const layers = dxfSummary.value.layers_found;
  return layers.map((l) => {
    const polyCount = dxfSummary.value.polyline_count_per_layer?.[l] || 0;
    return {
      value: l,
      label: polyCount > 0 ? `${l} (${polyCount} مضلع/مساحة)` : `${l} (لا يحتوي على مضلعات)`,
    };
  });
});

const dxfLabelsLayerOptions = computed(() => {
  if (!dxfSummary.value?.layers_found) return [];
  const layers = dxfSummary.value.layers_found;
  return layers.map((l) => {
    const txtCount = dxfSummary.value.text_count_per_layer?.[l] || 0;
    return {
      value: l,
      label: txtCount > 0 ? `${l} (${txtCount} نص/اسم)` : `${l} (لا يحتوي على نصوص)`,
    };
  });
});

const dxfTotalPreviewArea = computed(() => {
  return dxfPreviewRooms.value.reduce((acc, r) => acc + (Number(r.area) || 0), 0);
});

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

const onImportSiteVisitSelect = (visitId) => {
  importSiteVisitId.value = visitId;
  const visit = siteVisits.value.find((v) => String(v.id) === String(visitId));
  if (visit?.opportunity_id) {
    importOpportunityId.value = visit.opportunity_id;
  } else if (visit?.customer_id) {
    const opps = (opportunities.value || []).filter((o) => o.customer_id === visit.customer_id);
    importOpportunityId.value = opps.length > 0 ? opps[0].id : '';
  } else {
    importOpportunityId.value = '';
  }
};

const openImportModal = () => {
  const initialVisit = siteVisits.value[0];
  if (initialVisit) {
    onImportSiteVisitSelect(initialVisit.id);
  } else {
    importSiteVisitId.value = '';
    importOpportunityId.value = '';
  }
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
  if (!importSiteVisitId.value || !importOpportunityId.value) {
    notificationStore.addToast({
      type: 'error',
      title: 'تنبيه',
      message: 'يرجى تحديد المعاينة والفرصة التجارية المرتبطة بها صراحة.',
    });
    return;
  }

  importing.value = true;
  try {
    const res = await api.post(`/measurements/import-from-site-visit/${importSiteVisitId.value}`, {
      opportunity_id: importOpportunityId.value,
    });
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم الاستيراد',
        message: 'تم استيراد غرف المعاينة وإنشاء مسودة المقايسة بنجاح.',
      });
      showImportModal.value = false;
      fetchMeasurements();
      openEditModal(res.data.data);
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الاستيراد',
      message: err.response?.data?.message || 'تعذر استيراد غرف المعاينة.',
    });
  } finally {
    importing.value = false;
  }
};

// DXF Import Handlers
const dxfImportMode = ref('new_measurement');

const openDxfImportModal = () => {
  dxfImportMode.value = 'new_measurement';
  dxfFile.value = null;
  dxfSummary.value = null;
  dxfRoomsLayer.value = '';
  dxfLabelsLayer.value = '';
  dxfOpportunityId.value = filterOpportunityId.value ? Number(filterOpportunityId.value) : (opportunities.value[0]?.id || '');
  dxfPreviewRooms.value = [];
  showDxfModal.value = true;
};

const openDxfImportForCurrentForm = () => {
  dxfImportMode.value = 'form_append';
  dxfFile.value = null;
  dxfSummary.value = null;
  dxfRoomsLayer.value = '';
  dxfLabelsLayer.value = '';
  dxfOpportunityId.value = form.opportunity_id || (opportunities.value[0]?.id || '');
  dxfPreviewRooms.value = [];
  showDxfModal.value = true;
};

const onFormSiteVisitChange = async (val) => {
  form.site_visit_id = val;
  if (val) {
    const sv = siteVisits.value.find((v) => String(v.id) === String(val));
    if (sv && !form.opportunity_id && sv.opportunity_id) {
      form.opportunity_id = sv.opportunity_id;
    }
  }
};

const importRoomsFromCurrentFormSiteVisit = async () => {
  if (!form.site_visit_id) {
    notificationStore.addToast({
      type: 'warning',
      title: 'تنبيه',
      message: 'يرجى اختيار المعاينة الميدانية من الحقل بالأعلى أولاً.',
    });
    return;
  }

  try {
    const res = await api.get(`/site-visits/${form.site_visit_id}`);
    const sv = res.data?.data;
    if (sv?.rooms?.length) {
      const newItems = sv.rooms.map((room, idx) => ({
        room_name: room.room_name,
        item_name: 'أعمال تشطيبات عامة',
        unit: 'm2',
        measurement_type: 'area',
        count: 1,
        length: null,
        width: null,
        height: null,
        deductions: 0,
        net_quantity: Number(room.estimated_area || 0) || null,
        notes: `مستورد من المعاينة الميدانية #${sv.id} ${room.notes ? '(' + room.notes + ')' : ''}`,
        sort_order: form.items.length + idx,
      }));

      if (form.items.length === 1 && (!form.items[0].room_name || form.items[0].room_name === 'ريسبشن')) {
        form.items = newItems;
      } else {
        form.items.push(...newItems);
      }

      notificationStore.addToast({
        type: 'success',
        title: 'تم جلب الغرف',
        message: `تم جلب ${newItems.length} غرف من المعاينة بنجاح وإدراجها في الجدول.`,
      });
    } else {
      notificationStore.addToast({
        type: 'info',
        title: 'لا توجد غرف',
        message: 'هذه المعاينة الميدانية لا تحتوي على غرف مسجلة.',
      });
    }
  } catch (e) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ',
      message: 'تعذر جلب غرف المعاينة.',
    });
  }
};

const closeDxfModal = () => {
  showDxfModal.value = false;
  dxfFile.value = null;
  dxfSummary.value = null;
  dxfPreviewRooms.value = [];
};

const triggerDxfFileInput = () => {
  dxfFileInputRef.value?.click();
};

const onDxfFileChange = (e) => {
  const file = e.target.files?.[0];
  if (file) handleDxfFile(file);
};

const onDxfFileDrop = (e) => {
  const file = e.dataTransfer?.files?.[0];
  if (file) handleDxfFile(file);
};

const handleDxfFile = async (file) => {
  const ext = file.name.split('.').pop()?.toLowerCase();
  if (ext === 'dwg' || ext === 'pdf') {
    await alertService.showError({
      title: 'صيغة غير مدعومة',
      text: 'الصيغة المدعومة حاليًا هي DXF فقط. لو عندك ملف DWG، افتحه بالأوتوكاد واعمل Save As → DXF ثم ارفعه هنا.',
    });
    return;
  }

  if (ext !== 'dxf') {
    await alertService.showError({
      title: 'صيغة غير صحيحة',
      text: 'يرجى اختيار ملف بامتداد .DXF صالح.',
    });
    return;
  }

  dxfFile.value = file;
  await parseDxfFile(file);
};

const parseDxfFile = async (file) => {
  dxfParsing.value = true;
  dxfPreviewRooms.value = [];
  try {
    const formData = new FormData();
    formData.append('file', file);

    const res = await api.post('/measurements/parse-dxf', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.success) {
      dxfSummary.value = res.data.data;
      const layers = res.data.data.layers_found || [];

      // Auto-suggest rooms layer: MUST have polylines > 0
      const polyLayers = layers.filter(
        (l) => (res.data.data.polyline_count_per_layer?.[l] || 0) > 0
      );
      const roomsGuess =
        polyLayers.find((l) => /room|space|area|حدود|فراغ/i.test(l)) ||
        (polyLayers.includes('ROOMS') ? 'ROOMS' : polyLayers[0] || '');
      dxfRoomsLayer.value = roomsGuess;

      // Auto-suggest labels layer: MUST have texts > 0
      const textLayers = layers.filter(
        (l) => (res.data.data.text_count_per_layer?.[l] || 0) > 0
      );
      const labelsGuess =
        textLayers.find((l) => /label|text|name|اسم/i.test(l)) ||
        (textLayers.includes('ROOM_LABELS') ? 'ROOM_LABELS' : textLayers[0] || '');
      dxfLabelsLayer.value = labelsGuess;

      if (roomsGuess) {
        await refreshDxfPreview();
      }
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في فحص الملف',
      message: err.response?.data?.message || 'تعذر فحص طبقات ملف DXF.',
    });
  } finally {
    dxfParsing.value = false;
  }
};

const refreshDxfPreview = async () => {
  if (!dxfFile.value || !dxfRoomsLayer.value) {
    dxfPreviewRooms.value = [];
    return;
  }

  // If selected rooms layer has 0 polylines, clear preview immediately
  const polyCount = dxfSummary.value?.polyline_count_per_layer?.[dxfRoomsLayer.value] || 0;
  if (polyCount === 0) {
    dxfPreviewRooms.value = [];
    return;
  }

  try {
    const formData = new FormData();
    formData.append('file', dxfFile.value);
    formData.append('rooms_layer', dxfRoomsLayer.value);
    if (dxfLabelsLayer.value) formData.append('labels_layer', dxfLabelsLayer.value);

    const res = await api.post('/measurements/parse-dxf', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.success && res.data.data?.rooms_preview) {
      dxfPreviewRooms.value = res.data.data.rooms_preview.map((r) => ({
        room_name: r.room_name,
        area: Number(r.area || 0),
        perimeter: Number(r.perimeter || 0),
        length: r.length ? Number(r.length) : null,
        width: r.width ? Number(r.width) : null,
        vertex_count: r.vertex_count || 4,
      }));
    }
  } catch (e) {
    dxfPreviewRooms.value = [];
  }
};

const onRoomsLayerChange = async (layer) => {
  dxfRoomsLayer.value = layer;
  await refreshDxfPreview();
};

const onLabelsLayerChange = async (layer) => {
  dxfLabelsLayer.value = layer;
  await refreshDxfPreview();
};

const executeDxfImport = async () => {
  if (!dxfFile.value || !dxfRoomsLayer.value || (!dxfOpportunityId.value && dxfImportMode.value !== 'form_append')) {
    notificationStore.addToast({
      type: 'error',
      title: 'تنبيه',
      message: 'يرجى استكمال اختيار الطبقات وتحديد الفرصة التجارية.',
    });
    return;
  }

  const polyCount = dxfSummary.value?.polyline_count_per_layer?.[dxfRoomsLayer.value] || 0;
  if (polyCount === 0) {
    notificationStore.addToast({
      type: 'error',
      title: 'طبقة غير صالحة للغرف',
      message: 'الطبقة المحددة لحدود الغرف لا تحتوي على مضلعات مغلقة (Polylines). يرجى اختيار طبقة تحتوي على مضلعات مثل ROOMS.',
    });
    return;
  }

  // If appending directly to current active form table
  if (dxfImportMode.value === 'form_append') {
    if (dxfPreviewRooms.value.length === 0) {
      notificationStore.addToast({
        type: 'warning',
        title: 'تنبيه',
        message: 'لا توجد غرف مكتشفة لإدراجها في الجدول.',
      });
      return;
    }

    const newItems = dxfPreviewRooms.value.map((r, idx) => ({
      room_name: r.room_name,
      item_name: 'أعمال تشطيبات عامة',
      unit: 'm2',
      measurement_type: 'area',
      count: 1,
      length: r.length || null,
      width: r.width || null,
      height: null,
      deductions: 0,
      net_quantity: !r.length ? Number(r.area) : null,
      notes: r.length && r.width ? `أبعاد هندسية ${r.length} × ${r.width} م (مساحة: ${r.area} م²)` : `مساحة هندسية غير منتظمة = ${r.area} م²`,
      sort_order: form.items.length + idx,
    }));

    if (form.items.length === 1 && (!form.items[0].room_name || form.items[0].room_name === 'ريسبشن')) {
      form.items = newItems;
    } else {
      form.items.push(...newItems);
    }

    notificationStore.addToast({
      type: 'success',
      title: 'تم إدراج الغرف',
      message: `تم إدراج ${newItems.length} غرف بنجاح في جدول البنود.`,
    });
    closeDxfModal();
    return;
  }

  dxfImporting.value = true;
  try {
    const formData = new FormData();
    formData.append('file', dxfFile.value);
    formData.append('rooms_layer', dxfRoomsLayer.value);
    if (dxfLabelsLayer.value) formData.append('labels_layer', dxfLabelsLayer.value);
    formData.append('opportunity_id', dxfOpportunityId.value);

    const res = await api.post('/measurements/import-from-dxf', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    if (res.data?.success) {
      await alertService.showSuccess({
        title: 'تم الاستيراد بنجاح',
        text: 'تم استيراد أبعاد ومساحات الغرف من ملف DXF وحسابها بدقة، وتم إنشاء مسودة المقايسة.',
      });

      closeDxfModal();
      fetchMeasurements();
      openEditModal(res.data.data);
    }
  } catch (err) {
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في الاستيراد',
      message: err.response?.data?.message || 'تعذر استيراد ملف DXF.',
    });
  } finally {
    dxfImporting.value = false;
  }
};

const submitForReview = async (item) => {
  const confirmed = await alertService.confirmAction({
    title: t('measurements.submitReviewAction') || 'تقديم المقايسة للمراجعة الفنية',
    text: t('measurements.submitReviewConfirm') || 'هل أنت متأكد من تقديم هذه المقايسة للمراجعة الفنية؟',
    icon: 'question',
    confirmButtonText: 'نعم، إرسال للمراجعة',
    cancelButtonText: 'إلغاء',
  });
  if (!confirmed) return;

  try {
    const res = await api.post(`/measurements/${item.id}/submit-review`);
    if (res.data?.success) {
      notificationStore.addToast({
        type: 'success',
        title: 'تم التقديم',
        message: 'تم إرسال المقايسة للمراجعة الفنية بنجاح.',
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
  const confirmed = await alertService.confirmAction({
    title: t('measurements.approveAction') || 'اعتماد المقايسة الهندسية',
    text: t('measurements.approveConfirm') || 'تأكيد اعتماد المقايسة الهندسية؟ سيتم قفل التعديل عليها واعتبارها المصدر الرسمي لحصر الكميات.',
    icon: 'warning',
    confirmButtonText: 'نعم، اعتمد المقايسة',
    cancelButtonText: 'إلغاء الأمر',
  });
  if (!confirmed) return;

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
  const confirmed = await alertService.confirmAction({
    title: t('measurements.createRevisionAction') || 'إنشاء إصدار جديد (Revision)',
    text: t('measurements.createRevisionConfirm') || 'هل تريد إنشاء إصدار جديد (نسخة مسودة قابلة للتعديل) من هذه المقايسة المعتمدة؟',
    icon: 'info',
    confirmButtonText: 'نعم، أنشئ الإصدار',
    cancelButtonText: 'إلغاء الأمر',
  });
  if (!confirmed) return;

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
  const confirmed = await alertService.confirmDelete({
    title: 'تأكيد حذف المقايسة',
    text: 'هل أنت متأكد من رغبتك في حذف هذه المقايسة؟ لا يمكن التراجع عن هذا الإجراء.',
    confirmButtonText: 'نعم، احذف المقايسة',
    cancelButtonText: 'إلغاء الأمر',
  });
  if (!confirmed) return;

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
