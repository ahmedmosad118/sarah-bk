<template>
  <div class="space-y-5">
    <!-- 1. Top Breadcrumbs & Page Header (Clean, Light, Executive) -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mb-1">
          <router-link to="/opportunities" class="hover:text-[#00C896] transition-colors flex items-center gap-1 font-bold">
            <ArrowRight class="h-3.5 w-3.5" />
            <span>{{ $t('opportunities.title') || 'الفرص التجارية' }}</span>
          </router-link>
          <span>/</span>
          <span class="text-gray-800 dark:text-gray-200 font-bold">الفرصة #{{ oppId }}</span>
          <span>/</span>
          <span class="text-emerald-700 dark:text-emerald-400 font-bold">مسار وسلسلة التتبع</span>
        </div>
        <div class="flex items-center gap-3">
          <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0">
            <Layers class="h-5 w-5" />
          </div>
          <div>
            <div class="flex items-center gap-2.5 flex-wrap">
              <h1 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white">
                {{ chainData?.opportunity?.title || 'جاري تحميل مسار العملية...' }}
              </h1>
              <span
                v-if="chainData?.opportunity?.stage"
                class="px-2.5 py-0.5 rounded-lg text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-800"
              >
                {{ getStageLabel(chainData.opportunity.stage) }}
              </span>
            </div>
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-0.5">
              {{ chainData?.customer?.name ? `العميل: ${chainData.customer.name}` : '' }} • تدقيق السلسلة من المعاينة حتى جدول الكميات
            </p>
          </div>
        </div>
      </div>

      <!-- Header Action Buttons -->
      <div class="flex flex-wrap items-center gap-2">
        <!-- Print Button: High-priority trigger for official 3-page engineering dossier -->
        <button
          type="button"
          @click="printReport"
          class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-black text-white hover:bg-emerald-700 shadow-sm transition-all cursor-pointer"
          title="طباعة وثيقة التتبع والاعتماد الهندسي الكاملة (3 صفحات A4 منسقة)"
        >
          <Printer class="h-4 w-4" />
          <span>طباعة تقرير كامل (Print Report)</span>
        </button>

        <button
          type="button"
          @click="fetchChain"
          class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 shadow-2xs transition-colors cursor-pointer"
        >
          <RefreshCw class="h-3.5 w-3.5" :class="{ 'animate-spin': loading }" />
          <span>تحديث</span>
        </button>

        <router-link
          to="/opportunities"
          class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 shadow-2xs transition-colors"
        >
          <ArrowLeft class="h-3.5 w-3.5" />
          <span>العودة للفرص</span>
        </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading && !chainData" class="flex flex-col items-center justify-center py-20 bg-white dark:bg-gray-900 rounded-3xl border border-gray-100 dark:border-gray-800">
      <div class="h-9 w-9 animate-spin rounded-full border-3 border-[#00C896] border-t-transparent mb-3"></div>
      <p class="text-xs font-bold text-gray-600 dark:text-gray-300">جاري قراءة وتدقيق السلسلة التجارية والهندسية للفرصة...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="p-6 rounded-3xl bg-rose-50 border border-rose-200 dark:bg-rose-950/20 dark:border-rose-900/40 text-center space-y-3">
      <AlertCircle class="h-8 w-8 text-rose-600 mx-auto" />
      <h3 class="text-sm font-black text-rose-950 dark:text-rose-300">تعذر تحميل بيانات المسار</h3>
      <p class="text-xs text-rose-800 dark:text-rose-400">{{ error }}</p>
      <button
        @click="fetchChain"
        class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-rose-700 transition-colors cursor-pointer"
      >
        <RefreshCw class="h-3.5 w-3.5" />
        <span>إعادة المحاولة</span>
      </button>
    </div>

    <!-- Main Content Area: Simplified, Calm & Non-Overwhelming -->
    <div v-else-if="chainData" class="space-y-5">
      <!-- 2. Calm Journey Stepper & Executive Readiness Banner -->
      <div class="p-4 sm:p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-4">
        <!-- Horizontal Progression Stepper (Clean & Light) -->
        <div class="flex items-center justify-between overflow-x-auto py-1 text-xs gap-2 border-b border-gray-100 dark:border-gray-800 pb-4">
          <!-- Step 1: Customer -->
          <div class="flex items-center gap-2 min-w-[110px]">
            <div class="h-7 w-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-xs shadow-2xs">
              ✓
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">العميل</span>
              <span class="text-[10px] text-gray-400 block truncate max-w-[85px]">{{ chainData.customer?.name }}</span>
            </div>
          </div>
          <div class="h-0.5 w-6 bg-emerald-500 shrink-0"></div>

          <!-- Step 2: Opportunity -->
          <div class="flex items-center gap-2 min-w-[115px]">
            <div class="h-7 w-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center font-black text-xs shadow-2xs">
              ✓
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">الفرصة</span>
              <span class="text-[10px] text-emerald-600 dark:text-emerald-400 block font-bold">{{ getStageLabel(chainData.opportunity?.stage) }}</span>
            </div>
          </div>
          <div class="h-0.5 w-6 bg-emerald-500 shrink-0"></div>

          <!-- Step 3: Site Visit -->
          <div class="flex items-center gap-2 min-w-[115px]">
            <div
              class="h-7 w-7 rounded-xl flex items-center justify-center font-black text-xs shadow-2xs"
              :class="chainData.site_visits?.length > 0 ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400 border border-gray-300 dark:bg-gray-800'"
            >
              {{ chainData.site_visits?.length > 0 ? '✓' : '3' }}
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">المعاينة الميدانية</span>
              <span class="text-[10px] text-gray-400 block">{{ chainData.site_visits?.length ? `${chainData.site_visits.length} معاينات` : 'غير مسجلة' }}</span>
            </div>
          </div>
          <div class="h-0.5 w-6 shrink-0" :class="chainData.site_visits?.length > 0 ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>

          <!-- Step 4: Approved Measurement -->
          <div class="flex items-center gap-2 min-w-[125px]">
            <div
              class="h-7 w-7 rounded-xl flex items-center justify-center font-black text-xs shadow-2xs"
              :class="currentApprovedMeasurement ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400 border border-gray-300 dark:bg-gray-800'"
            >
              {{ currentApprovedMeasurement ? '✓' : '4' }}
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">المقايسة المعتمدة</span>
              <span class="text-[10px] block" :class="currentApprovedMeasurement ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                {{ currentApprovedMeasurement ? `V${currentApprovedMeasurement.version} (${currentApprovedMeasurement.total_area} م²)` : 'بانتظار الاعتماد' }}
              </span>
            </div>
          </div>
          <div class="h-0.5 w-6 shrink-0" :class="currentApprovedScope ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>

          <!-- Step 5: Approved Scope -->
          <div class="flex items-center gap-2 min-w-[125px]">
            <div
              class="h-7 w-7 rounded-xl flex items-center justify-center font-black text-xs shadow-2xs"
              :class="currentApprovedScope ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-400 border border-gray-300 dark:bg-gray-800'"
            >
              {{ currentApprovedScope ? '✓' : '5' }}
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">نطاق الأعمال</span>
              <span class="text-[10px] block" :class="currentApprovedScope ? 'text-emerald-600 font-bold' : 'text-gray-400'">
                {{ currentApprovedScope ? `V${currentApprovedScope.version} (${currentApprovedScope.items?.length || 0} حزم)` : 'بانتظار الاعتماد' }}
              </span>
            </div>
          </div>
          <div class="h-0.5 w-6 shrink-0" :class="chainData.current_status?.is_complete ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'"></div>

          <!-- Step 6: BOQ -->
          <div class="flex items-center gap-2 min-w-[110px]">
            <div
              class="h-7 w-7 rounded-xl flex items-center justify-center font-bold text-xs"
              :class="chainData.current_status?.is_complete ? 'bg-emerald-50 text-emerald-700 border-2 border-emerald-500 dark:bg-emerald-950/40' : 'bg-gray-50 text-gray-400 border border-dashed border-gray-300 dark:bg-gray-800'"
            >
              BOQ
            </div>
            <div>
              <span class="font-bold text-gray-900 dark:text-white block text-xs">جدول الكميات</span>
              <span class="text-[10px] block" :class="chainData.current_status?.is_complete ? 'text-emerald-700 font-bold' : 'text-gray-400'">
                {{ chainData.current_status?.is_complete ? 'جاهز للانتقال' : 'المرحلة التالية' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Status & Next Action Row (Calm & Balanced) -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 p-3.5 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <span
              class="h-3 w-3 rounded-full shrink-0"
              :class="chainData.current_status?.is_complete ? 'bg-emerald-500 ring-4 ring-emerald-100 dark:ring-emerald-900/40' : 'bg-amber-500 ring-4 ring-amber-100 dark:ring-amber-900/40'"
            ></span>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-gray-500 dark:text-gray-400">حالة السلسلة:</span>
                <span class="text-xs font-black" :class="chainData.current_status?.is_complete ? 'text-emerald-700 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-400'">
                  {{ chainData.current_status?.is_complete ? 'مكتملة ومعتمدة رسمياً' : 'قيد الاستكمال والمراجعة' }}
                </span>
              </div>
              <p class="text-xs text-gray-700 dark:text-gray-300 mt-0.5 font-medium">
                {{ chainData.next_action?.description || 'السلسلة التجارية والهندسية متطابقة وجاهزة للبدء في جدول الكميات.' }}
              </p>
            </div>
          </div>

          <div v-if="chainData.next_action?.action_target" class="shrink-0">
            <router-link
              :to="chainData.next_action.action_target"
              class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-2xs transition-colors"
            >
              <span>{{ chainData.next_action.action_label }}</span>
              <ArrowLeft class="h-3.5 w-3.5" />
            </router-link>
          </div>
          <div v-else class="shrink-0">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 text-xs font-bold border border-emerald-200 dark:border-emerald-800/50">
              <CheckCircle2 class="h-3.5 w-3.5" />
              <span>جاهز لمرحلة جدول الكميات (BOQ)</span>
            </span>
          </div>
        </div>
      </div>

      <!-- 3. Calm Tab Navigation: Prevents Screen Overcrowding -->
      <div class="border-b border-gray-200 dark:border-gray-800">
        <nav class="flex space-x-2 space-x-reverse overflow-x-auto pb-px" aria-label="Tabs">
          <button
            v-for="tab in availableTabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="whitespace-nowrap py-2.5 px-3.5 rounded-t-xl text-xs font-bold transition-all border-b-2 flex items-center gap-2 cursor-pointer"
            :class="[
              activeTab === tab.id
                ? 'border-emerald-500 text-emerald-700 dark:text-emerald-400 bg-white dark:bg-gray-900 shadow-2xs'
                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-200'
            ]"
          >
            <component :is="tab.icon" class="h-3.5 w-3.5" />
            <span>{{ tab.label }}</span>
            <span
              v-if="tab.badge"
              class="px-1.5 py-0.2 rounded-md text-[10px] font-bold"
              :class="activeTab === tab.id ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/80 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
            >
              {{ tab.badge }}
            </span>
          </button>
        </nav>
      </div>

      <!-- ============================================================ -->
      <!-- TAB CONTENT PANELS (Clean, Organized, Highly Scannable)      -->
      <!-- ============================================================ -->

      <!-- TAB 1: الملخص التنفيذي والمسار (Overview) -->
      <div v-if="activeTab === 'overview'" class="space-y-4">
        <!-- 3 Essential Foundations in Calm Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <!-- Card 1: Customer -->
          <div class="p-4 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-2">
            <div class="flex items-center gap-2 text-xs font-black text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 pb-2">
              <User class="h-3.5 w-3.5 text-blue-500" />
              <span>بيانات العميل</span>
            </div>
            <div>
              <p class="font-black text-gray-900 dark:text-white text-sm">{{ chainData.customer?.name || '—' }}</p>
              <p v-if="chainData.customer?.company_name" class="text-xs text-gray-500 font-medium mt-0.5">{{ chainData.customer.company_name }}</p>
              <p v-if="chainData.customer?.phone" class="font-mono text-xs text-gray-600 dark:text-gray-300 font-bold mt-1" dir="ltr">{{ chainData.customer.phone }}</p>
              <p v-if="chainData.customer?.email" class="text-gray-400 font-mono text-[11px] truncate mt-0.5">{{ chainData.customer.email }}</p>
            </div>
          </div>

          <!-- Card 2: Opportunity -->
          <div class="p-4 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-2">
            <div class="flex items-center gap-2 text-xs font-black text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 pb-2">
              <Briefcase class="h-3.5 w-3.5 text-emerald-500" />
              <span>الفرصة التجارية</span>
            </div>
            <div>
              <p class="font-black text-gray-900 dark:text-white text-sm line-clamp-1">{{ chainData.opportunity?.title }}</p>
              <p class="font-mono font-bold text-xs text-emerald-700 dark:text-emerald-400 mt-1">
                {{ Number(chainData.opportunity?.estimated_value || 0).toLocaleString() }} ج.م
              </p>
              <div class="flex items-center gap-2 text-[11px] text-gray-500 mt-1">
                <span>المسؤول: {{ chainData.opportunity?.assigned_user?.name || 'غير محدد' }}</span>
                <span v-if="chainData.opportunity?.expected_start_date">•</span>
                <span v-if="chainData.opportunity?.expected_start_date">{{ formatDate(chainData.opportunity.expected_start_date) }}</span>
              </div>
            </div>
          </div>

          <!-- Card 3: Technical Milestones Summary -->
          <div class="p-4 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-2">
            <div class="flex items-center gap-2 text-xs font-black text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800 pb-2">
              <ShieldCheck class="h-3.5 w-3.5 text-teal-500" />
              <span>الاعتماد الفني الحالي</span>
            </div>
            <div class="space-y-1.5 text-xs">
              <div class="flex items-center justify-between">
                <span class="text-gray-500">المقايسة المعتمدة:</span>
                <span v-if="currentApprovedMeasurement" class="font-bold text-teal-700 dark:text-teal-300">
                  V{{ currentApprovedMeasurement.version }} ({{ currentApprovedMeasurement.total_area }} م²)
                </span>
                <span v-else class="text-gray-400 italic">غير معتمدة</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-500">نطاق الأعمال:</span>
                <span v-if="currentApprovedScope" class="font-bold text-emerald-700 dark:text-emerald-400">
                  V{{ currentApprovedScope.version }} ({{ currentApprovedScope.items?.length || 0 }} حزم)
                </span>
                <span v-else class="text-gray-400 italic">غير معتمد</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-gray-500">المعاينات الميدانية:</span>
                <span class="font-bold text-gray-800 dark:text-gray-200">
                  {{ chainData.site_visits?.length || 0 }} معاينات
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Initial Request (Lead) Bar if present -->
        <div v-if="chainData.lead" class="p-4 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 text-xs flex items-center justify-between">
          <div class="space-y-0.5">
            <span class="font-bold text-gray-500">الطلب المبدئي المرتبط (Lead):</span>
            <p class="font-bold text-gray-900 dark:text-white">{{ chainData.lead.title }}</p>
            <p v-if="chainData.lead.description" class="text-gray-500 text-[11px] italic">"{{ chainData.lead.description }}"</p>
          </div>
          <div class="text-left text-[11px] text-gray-500 font-mono">
            <span>المصدر: {{ chainData.lead.source || 'مباشر' }}</span>
            <span class="mx-1">•</span>
            <span>{{ formatDate(chainData.lead.created_at) }}</span>
          </div>
        </div>
      </div>

      <!-- TAB 2: المقايسة وحصر الكميات (Measurements) -->
      <div v-else-if="activeTab === 'measurements'" class="space-y-4">
        <div class="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <div class="p-1.5 rounded-xl bg-teal-50 text-teal-600 dark:bg-teal-950/60 dark:text-teal-400">
                <Ruler class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-xs font-black text-gray-900 dark:text-white">المقايسة وحصر الكميات الهندسية المعتمدة</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">المرجع الهندسي المعتمد لحساب الكميات الصافية للفرصة</p>
              </div>
            </div>

            <router-link to="/measurements" class="text-xs font-bold text-teal-600 dark:text-teal-400 hover:underline flex items-center gap-1">
              <span>فتح المقايسات</span>
              <ExternalLink class="h-3 w-3" />
            </router-link>
          </div>

          <div v-if="currentApprovedMeasurement" class="space-y-4">
            <!-- Meta Bar -->
            <div class="p-3.5 rounded-2xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/30 flex flex-wrap items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2 flex-wrap">
                <span class="font-black text-teal-950 dark:text-teal-200">مقايسة معتمدة: #{{ currentApprovedMeasurement.measurement_number }}</span>
                <span class="px-2 py-0.5 rounded bg-teal-100 text-teal-800 font-bold text-[10px]">V{{ currentApprovedMeasurement.version }}</span>
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">معتمدة رسمياً</span>
              </div>
              <div class="flex items-center gap-3 text-[11px] text-teal-900 dark:text-teal-300 font-mono">
                <span>إجمالي المسطحات: <strong>{{ Number(currentApprovedMeasurement.total_area || 0) }} م²</strong></span>
                <span>•</span>
                <span>تاريخ الاعتماد: {{ formatDate(currentApprovedMeasurement.approved_at) }}</span>
              </div>
            </div>

            <!-- Full Items Table -->
            <div class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-800">
              <table class="w-full text-right text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 font-black text-[11px]">
                  <tr>
                    <th class="py-2.5 px-3 w-10 text-center">#</th>
                    <th class="py-2.5 px-3">الغرفة / الفراغ</th>
                    <th class="py-2.5 px-3">بند الأعمال</th>
                    <th class="py-2.5 px-3 text-center">الوحدة</th>
                    <th class="py-2.5 px-3 text-center">العدد</th>
                    <th class="py-2.5 px-3 text-center">الأبعاد (ط × ع × ع)</th>
                    <th class="py-2.5 px-3 text-center">الخصومات</th>
                    <th class="py-2.5 px-3 text-center">الكمية الصافية</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                  <tr v-for="(it, idx) in approvedMeasurementItems" :key="it.id || idx" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                    <td class="py-2.5 px-3 text-center font-mono text-gray-400 font-bold">{{ idx + 1 }}</td>
                    <td class="py-2.5 px-3 font-bold text-gray-900 dark:text-white">{{ it.room_name }}</td>
                    <td class="py-2.5 px-3 text-gray-700 dark:text-gray-300">{{ it.item_name }}</td>
                    <td class="py-2.5 px-3 text-center font-bold">{{ it.unit }}</td>
                    <td class="py-2.5 px-3 text-center font-mono">{{ it.count || 1 }}</td>
                    <td class="py-2.5 px-3 text-center font-mono text-[11px]">
                      <span v-if="it.length && it.width">{{ Number(it.length).toFixed(2) }} × {{ Number(it.width).toFixed(2) }} {{ it.height ? `× ${Number(it.height).toFixed(2)}` : '' }}</span>
                      <span v-else class="text-gray-400">—</span>
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono text-rose-600">
                      {{ Number(it.deductions) > 0 ? Number(it.deductions).toFixed(2) : '—' }}
                    </td>
                    <td class="py-2.5 px-3 text-center font-mono font-black text-teal-700 dark:text-teal-300 bg-teal-50/30 dark:bg-teal-950/20">
                      {{ Number(it.net_quantity).toFixed(2) }} {{ it.unit }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          <div v-else class="text-center py-10 text-gray-400 text-xs">
            لا توجد مقايسة معتمدة رسمياً بعد لهذه الفرصة.
          </div>
        </div>
      </div>

      <!-- TAB 3: نطاق الأعمال والمواصفات (Scope) -->
      <div v-else-if="activeTab === 'scope'" class="space-y-4">
        <div class="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <div class="p-1.5 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <ClipboardList class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-xs font-black text-gray-900 dark:text-white">وثيقة نطاق الأعمال والمواصفات الفنية</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">توصيف الحزم والاشتمالات والاستثناءات المعتمدة</p>
              </div>
            </div>

            <router-link to="/scopes" class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1">
              <span>فتح وثائق النطاق</span>
              <ExternalLink class="h-3 w-3" />
            </router-link>
          </div>

          <div v-if="currentApprovedScope" class="space-y-4">
            <!-- Meta Bar -->
            <div class="p-3.5 rounded-2xl bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/30 flex flex-wrap items-center justify-between gap-3 text-xs">
              <div class="flex items-center gap-2">
                <span class="font-black text-emerald-950 dark:text-emerald-200">وثيقة معتمدة: #{{ currentApprovedScope.scope_number }}</span>
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">V{{ currentApprovedScope.version }}</span>
                <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 font-bold text-[10px]">معتمدة رسمياً</span>
              </div>
              <div class="text-[11px] text-emerald-900 dark:text-emerald-300 font-mono">
                عدد الحزم: <strong>{{ currentApprovedScope.items?.length || 0 }} حزم</strong>
                • تاريخ الاعتماد: {{ formatDate(currentApprovedScope.approved_at) }}
              </div>
            </div>

            <!-- Inclusions & Exclusions Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
              <div class="p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-900/40 text-emerald-950 dark:text-emerald-200">
                <strong class="block font-black mb-1">الاشتمالات العامة المعتمدة:</strong>
                <p class="leading-relaxed text-[11px]">{{ currentApprovedScope.general_inclusions || 'يشمل توريد المواد ومطابقة المواصفات والعمالة الفنية والنظافة والتسليم.' }}</p>
              </div>
              <div class="p-3 rounded-2xl bg-rose-50/50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/40 text-rose-950 dark:text-rose-200">
                <strong class="block font-black mb-1">الاستثناءات والحدود التعاقدية:</strong>
                <p class="leading-relaxed text-[11px]">{{ currentApprovedScope.general_exclusions || 'لا يشمل الأجهزة والتجهيزات الخاصة والرسوم الحكومية والتراخيص.' }}</p>
              </div>
            </div>

            <!-- Scope Items List -->
            <div class="space-y-2.5">
              <div
                v-for="(item, idx) in currentApprovedScope.items"
                :key="item.id || idx"
                class="p-3.5 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 space-y-1.5 text-xs"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="font-mono text-gray-400 font-bold">#{{ idx + 1 }}</span>
                    <span class="font-black text-gray-900 dark:text-white">{{ item.item_name }}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                      {{ item.trade_category }}
                    </span>
                  </div>
                </div>
                <p class="text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed">{{ item.specification || 'طبقاً للمواصفات الفنية وأصول الصناعة المعتمدة.' }}</p>
                <div v-if="item.inclusions || item.exclusions" class="flex flex-wrap gap-4 text-[10px] pt-1 border-t border-gray-100 dark:border-gray-800">
                  <span v-if="item.inclusions" class="text-emerald-700 dark:text-emerald-400"><strong>يشمل:</strong> {{ item.inclusions }}</span>
                  <span v-if="item.exclusions" class="text-rose-600 dark:text-rose-400"><strong>يستثني:</strong> {{ item.exclusions }}</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-10 text-gray-400 text-xs">
            لا توجد وثيقة نطاق أعمال معتمدة رسمياً بعد لهذه الفرصة.
          </div>
        </div>
      </div>

      <!-- TAB 4: مصفوفة التتبع الفني (Traceability Matrix) -->
      <div v-else-if="activeTab === 'traceability'" class="space-y-4">
        <div class="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <div class="p-1.5 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                <Link2 class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-xs font-black text-gray-900 dark:text-white">مصفوفة التتبع الفني (Traceability Matrix)</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">الربط والتحقق الصارم بين بنود نطاق الأعمال وبنود المقايسة المعتمدة</p>
              </div>
            </div>
          </div>

          <div v-if="flattenedTraceabilityItems.length > 0" class="overflow-x-auto rounded-2xl border border-gray-100 dark:border-gray-800">
            <table class="w-full text-right text-xs">
              <thead class="bg-gray-50 dark:bg-gray-800/60 text-gray-500 font-black text-[11px]">
                <tr>
                  <th class="py-2.5 px-3 w-10 text-center">#</th>
                  <th class="py-2.5 px-3 w-52">بند نطاق العمل والتصنيف</th>
                  <th class="py-2.5 px-3">المواصفات وطريقة التنفيذ</th>
                  <th class="py-2.5 px-3 w-64">بنود المقايسة الهندسية المرتبطة والكمية</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                <tr v-for="(item, idx) in flattenedTraceabilityItems" :key="item.id || idx" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                  <td class="py-3 px-3 text-center font-mono font-bold text-gray-400">{{ idx + 1 }}</td>
                  <td class="py-3 px-3">
                    <p class="font-bold text-gray-900 dark:text-white">{{ item.item_name }}</p>
                    <span class="inline-block mt-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                      {{ item.trade_category }}
                    </span>
                  </td>
                  <td class="py-3 px-3 text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed">
                    <p>{{ item.specification || 'طبقاً للمواصفات الفنية وأصول الصناعة المعتمدة.' }}</p>
                    <p v-if="item.inclusions" class="text-emerald-700 dark:text-emerald-400 mt-0.5"><strong>يشمل:</strong> {{ item.inclusions }}</p>
                    <p v-if="item.exclusions" class="text-rose-600 dark:text-rose-400 mt-0.5"><strong>يستثني:</strong> {{ item.exclusions }}</p>
                  </td>
                  <td class="py-3 px-3">
                    <div v-if="item.linked_measurements && item.linked_measurements.length > 0" class="space-y-1">
                      <div
                        v-for="m in item.linked_measurements"
                        :key="m.id"
                        class="p-1.5 rounded-lg border border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/60 flex items-center justify-between text-[11px]"
                      >
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ m.room_name }} - {{ m.item_name }}</span>
                        <span class="font-mono font-bold text-teal-700 dark:text-teal-300 bg-white dark:bg-gray-900 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700">
                          {{ m.net_quantity }} {{ m.unit }}
                        </span>
                      </div>
                    </div>
                    <span v-else class="text-gray-400 text-[11px] italic">مواصفة عامة / غير مقيدة ببنود مفردة</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="text-center py-10 text-gray-400 text-xs">
            لا توجد بنود مطابقة مدرجة حالياً.
          </div>
        </div>
      </div>

      <!-- TAB 5: المعاينات الميدانية (Site Visits) -->
      <div v-else-if="activeTab === 'site_visits'" class="space-y-4">
        <div class="p-5 rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
            <div class="flex items-center gap-2">
              <div class="p-1.5 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                <MapPin class="h-4 w-4" />
              </div>
              <div>
                <h3 class="text-xs font-black text-gray-900 dark:text-white">سجل المعاينات الميدانية ({{ chainData.site_visits?.length || 0 }})</h3>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">فحص الواقع الميداني وتوثيق الفراغات المعمارية</p>
              </div>
            </div>

            <router-link to="/site-visits" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
              <span>عرض المعاينات</span>
              <ExternalLink class="h-3 w-3" />
            </router-link>
          </div>

          <div v-if="chainData.site_visits?.length > 0" class="space-y-3 text-xs">
            <div
              v-for="v in chainData.site_visits"
              :key="v.id"
              class="p-4 rounded-2xl bg-gray-50/70 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex items-start justify-between gap-4"
            >
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="font-bold text-gray-900 dark:text-white">معاينة #{{ v.id }}</span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold" :class="v.status === 'Completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-amber-100 text-amber-800'">
                    {{ v.status === 'Completed' ? 'مكتملة' : v.status }}
                  </span>
                  <span class="text-gray-400 font-mono text-[11px]">{{ formatDate(v.scheduled_date || v.visit_date) }}</span>
                </div>
                <p v-if="v.general_assessment" class="text-gray-600 dark:text-gray-300 text-xs mt-1.5 italic">
                  "{{ v.general_assessment }}"
                </p>
                <div class="flex items-center gap-3 text-[11px] text-gray-500 mt-2">
                  <span>الفاحص: {{ v.assigned_user?.name || '—' }}</span>
                  <span>•</span>
                  <span>{{ v.rooms?.length || 0 }} فراغات معمارية مفحوصة</span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-10 text-gray-400 text-xs">
            لم تسجل أي معاينة ميدانية بعد لهذه الفرصة.
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- OFFICIAL MULTI-PAGE PRINTABLE REPORT                           -->
    <!-- Teleported to body to bypass any layout scroll/overflow traps   -->
    <!-- Formatted into 3 distinct, complete, professional A4 pages     -->
    <!-- ============================================================== -->
    <Teleport to="body">
      <div v-if="chainData" id="official-printable-chain" class="text-slate-900 bg-white">
        <!-- ========================================================== -->
        <!-- PAGE 1: وثيقة التتبع والاعتماد التنفيذي                      -->
        <!-- ========================================================== -->
        <div class="print-page">
          <div>
            <!-- Page 1 Header -->
            <div class="border-b-2 border-slate-900 pb-3 mb-4">
              <div class="flex items-start justify-between gap-4">
                <!-- Right: Company Info -->
                <div class="text-right flex-1">
                  <h1 class="text-base font-black text-slate-900 tracking-tight">
                    {{ authStore.tenant?.name || 'شركة الصرح للمقاولات العامة والتشطيبات' }}
                  </h1>
                  <p class="text-[11px] font-bold text-slate-600 mt-0.5">
                    إدارة المشروعات والمكتب الفني • قسم التتبع والرقابة الهندسية والتجارية
                  </p>
                  <p class="text-[9px] text-slate-500 font-mono mt-0.5">
                    سجل تجاري: 1084920 | بطاقة ضريبية: 492-381-092 | بنها - القليوبية
                  </p>
                </div>

                <!-- Center: Document Title & Badges -->
                <div class="text-center px-4 shrink-0">
                  <div class="border-2 border-slate-900 bg-slate-50 px-4 py-1.5 rounded-lg shadow-2xs">
                    <h2 class="text-sm font-black text-slate-900">تقرير مسار وسلسلة التتبع التجارية والفنية</h2>
                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-600">Traceability & Handover Dossier</p>
                  </div>
                  <div class="mt-1 flex items-center justify-center gap-2 text-[10px]">
                    <span class="font-mono font-bold text-slate-800">فرصة رقم: #{{ oppId }}</span>
                    <span class="text-slate-400">•</span>
                    <span class="font-bold text-emerald-800 bg-emerald-100 border border-emerald-300 px-2 py-0.2 rounded">
                      المرحلة: {{ getStageLabel(chainData.opportunity?.stage) }}
                    </span>
                    <span class="text-slate-400">•</span>
                    <span class="font-bold text-slate-700 font-mono">{{ new Date().toISOString().slice(0, 10) }}</span>
                  </div>
                </div>

                <!-- Left: Brand Logo & Print Info -->
                <div class="text-left flex flex-col items-end shrink-0">
                  <div class="flex items-center gap-2 border border-slate-400 rounded-lg px-2.5 py-1 bg-slate-50">
                    <div class="h-6 w-6 rounded bg-emerald-700 text-white flex items-center justify-center font-black text-xs">
                      S
                    </div>
                    <span class="font-black text-xs text-slate-800 font-mono tracking-wider">SARH ERP</span>
                  </div>
                  <div class="mt-1.5 text-[9px] text-slate-500 font-mono text-left space-y-0.5">
                    <p>تاريخ الطباعة: {{ new Date().toLocaleDateString('ar-EG') }}</p>
                    <p>المشغل: {{ authStore.user?.name || 'مهندس النظام' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Section 1: Customer & Commercial Hub Identification Table -->
            <div class="mb-4 border border-slate-400 rounded-lg overflow-hidden text-xs">
              <div class="bg-slate-100 px-3 py-1.5 border-b border-slate-300 flex items-center justify-between font-bold">
                <span class="text-[11px] font-black text-slate-800">أولاً: البيانات الأساسية للعملية (العميل والفرصة التجارية والطلب)</span>
                <span class="text-[9px] text-slate-500 font-mono">Commercial Foundation Identification</span>
              </div>
              <div class="grid grid-cols-3 divide-x divide-x-reverse divide-slate-300 text-[11px]">
                <!-- Customer Column -->
                <div class="p-2.5 space-y-1">
                  <span class="font-black text-slate-900 block border-b border-slate-200 pb-0.5 text-xs">1. بيانات العميل:</span>
                  <p><strong>الاسم:</strong> {{ chainData.customer?.name || '—' }}</p>
                  <p v-if="chainData.customer?.company_name"><strong>الشركة:</strong> {{ chainData.customer.company_name }}</p>
                  <p><strong>نوع العميل:</strong> {{ chainData.customer?.customer_type === 'company' ? 'شركة / مؤسسة' : 'فرد' }}</p>
                  <p><strong>الهاتف:</strong> <span dir="ltr" class="font-mono font-bold">{{ chainData.customer?.phone || '—' }}</span></p>
                  <p v-if="chainData.customer?.email"><strong>البريد:</strong> {{ chainData.customer.email }}</p>
                </div>

                <!-- Lead Column -->
                <div class="p-2.5 space-y-1">
                  <span class="font-black text-slate-900 block border-b border-slate-200 pb-0.5 text-xs">2. الطلب الأولي (Lead):</span>
                  <p><strong>عنوان الطلب:</strong> {{ chainData.lead?.title || 'فرصة تجارية مباشرة' }}</p>
                  <p><strong>المصدر:</strong> {{ chainData.lead?.source || 'مباشر' }}</p>
                  <p><strong>تاريخ الطلب:</strong> <span class="font-mono">{{ formatDate(chainData.lead?.created_at) }}</span></p>
                  <p><strong>المسؤول:</strong> {{ chainData.lead?.assigned_user?.name || '—' }}</p>
                  <p v-if="chainData.lead?.description" class="line-clamp-2 italic text-[10px] text-slate-600">"{{ chainData.lead.description }}"</p>
                </div>

                <!-- Opportunity Column -->
                <div class="p-2.5 space-y-1">
                  <span class="font-black text-slate-900 block border-b border-slate-200 pb-0.5 text-xs">3. الفرصة التجارية:</span>
                  <p><strong>عنوان الفرصة:</strong> {{ chainData.opportunity?.title }}</p>
                  <p><strong>المرحلة الحالية:</strong> <span class="font-bold text-blue-900">{{ getStageLabel(chainData.opportunity?.stage) }}</span></p>
                  <p><strong>القيمة التقديرية:</strong> <span class="font-mono font-black text-emerald-800">{{ Number(chainData.opportunity?.estimated_value || 0).toLocaleString() }} ج.م</span></p>
                  <p><strong>تاريخ البدء المتوقع:</strong> <span class="font-mono">{{ formatDate(chainData.opportunity?.expected_start_date) }}</span></p>
                  <p><strong>مسؤول العملية:</strong> {{ chainData.opportunity?.assigned_user?.name || '—' }}</p>
                </div>
              </div>
            </div>

            <!-- Section 2: Site Visits Summary Table -->
            <div class="mb-4">
              <div class="bg-slate-800 text-white px-3 py-1.5 rounded-t-lg flex items-center justify-between">
                <h3 class="text-xs font-black">ثانياً: المعاينات الميدانية والواقع الفعلي ({{ chainData.site_visits?.length || 0 }} معاينة مسجلة)</h3>
                <span class="text-[9px] text-slate-300 font-mono">Field Reality Surveys</span>
              </div>
              <table class="w-full text-right border-collapse border border-slate-400 text-[10px]">
                <thead>
                  <tr class="bg-slate-100 text-slate-900 border-b border-slate-400 font-bold">
                    <th class="p-1.5 border-l border-slate-400 text-center w-8">#</th>
                    <th class="p-1.5 border-l border-slate-400 w-24">تاريخ المعاينة</th>
                    <th class="p-1.5 border-l border-slate-400 w-24">الحالة</th>
                    <th class="p-1.5 border-l border-slate-400 w-36">المهندس المعاين</th>
                    <th class="p-1.5 border-l border-slate-400 w-28 text-center">الفراغات المفحوصة</th>
                    <th class="p-1.5">ملاحظات وتقرير المعاينة</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(v, idx) in chainData.site_visits"
                    :key="v.id"
                    class="border-b border-slate-400 align-top"
                    :class="idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'"
                  >
                    <td class="p-1.5 border-l border-slate-400 text-center font-bold font-mono">#{{ v.id }}</td>
                    <td class="p-1.5 border-l border-slate-400 font-mono font-bold">{{ formatDate(v.scheduled_date || v.visit_date) }}</td>
                    <td class="p-1.5 border-l border-slate-400 font-bold">
                      <span class="px-1.5 py-0.5 rounded text-[9px]" :class="v.status === 'Completed' ? 'bg-emerald-100 text-emerald-900' : 'bg-slate-200 text-slate-800'">
                        {{ v.status === 'Completed' ? 'تمت بنجاح' : v.status }}
                      </span>
                    </td>
                    <td class="p-1.5 border-l border-slate-400 font-bold">{{ v.assigned_user?.name || '—' }}</td>
                    <td class="p-1.5 border-l border-slate-400 text-center font-mono font-bold">
                      {{ v.rooms?.length || 0 }} فراغات معمارية
                    </td>
                    <td class="p-1.5 text-slate-700 italic">{{ v.general_assessment || v.notes || 'تمت المعاينة ومطابقة أبعاد الموقع.' }}</td>
                  </tr>
                  <tr v-if="!chainData.site_visits?.length">
                    <td colspan="6" class="p-2 text-center text-slate-400 italic">لا توجد زيارات ميدانية مسجلة لهذه الفرصة.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Section 3: Technical Chain State & BOQ Readiness -->
            <div class="mb-4 border border-slate-400 rounded-lg p-3 bg-slate-50/80 text-xs">
              <div class="flex items-center justify-between border-b border-slate-300 pb-1.5 mb-2 font-bold">
                <span class="text-xs font-black text-slate-900">ثالثاً: ملخص الاعتماد الفني وسلسلة الجاهزية للانتقال لـ BOQ</span>
                <span class="text-[9px] text-emerald-800 bg-emerald-100 px-2 py-0.5 rounded font-black">
                  {{ chainData.current_status?.is_complete ? '✓ السلسلة مكتملة ومقفلة (Locked)' : 'قيد الاستكمال الفني' }}
                </span>
              </div>
              <div class="grid grid-cols-3 gap-3 text-[10px]">
                <div class="border border-slate-300 rounded p-2 bg-white">
                  <strong class="block text-slate-500 mb-0.5">المقايسة المعتمدة (الحصر):</strong>
                  <p v-if="currentApprovedMeasurement" class="text-emerald-900 font-black">
                    مقايسة #{{ currentApprovedMeasurement.measurement_number }} (V{{ currentApprovedMeasurement.version }}) - مسطح: {{ currentApprovedMeasurement.total_area }} م²
                  </p>
                  <p v-else class="text-amber-800 font-bold">بانتظار الاعتماد الفني</p>
                </div>
                <div class="border border-slate-300 rounded p-2 bg-white">
                  <strong class="block text-slate-500 mb-0.5">نطاق الأعمال المعتمد (التوصيف):</strong>
                  <p v-if="currentApprovedScope" class="text-emerald-900 font-black">
                    وثيقة #{{ currentApprovedScope.scope_number }} (V{{ currentApprovedScope.version }}) - {{ currentApprovedScope.items?.length || 0 }} حزم عمل
                  </p>
                  <p v-else class="text-amber-800 font-bold">بانتظار الاعتماد</p>
                </div>
                <div class="border border-slate-300 rounded p-2 bg-white">
                  <strong class="block text-slate-500 mb-0.5">القرار الهندسي والتنفيذي:</strong>
                  <p class="font-bold" :class="chainData.current_status?.is_complete ? 'text-emerald-900' : 'text-slate-700'">
                    {{ chainData.current_status?.is_complete ? 'جاهز تماماً للبدء في جدول الكميات (Ready for BOQ)' : 'يجب استكمال الاعتمادات الفنية أولاً' }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Page 1 Footer -->
          <div class="flex items-center justify-between text-[9px] text-slate-500 border-t border-slate-300 pt-2 font-mono">
            <span>نظام SARH ERP المتكامل لإدارة المقاولات • وثيقة تتبع رسمية</span>
            <span>صفحة 1 من 3</span>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- PAGE 2: جدول حصر الكميات والمقايسة الهندسية المعتمدة           -->
        <!-- ========================================================== -->
        <div class="print-page">
          <div>
            <!-- Page 2 Header -->
            <div class="border-b border-slate-300 pb-2 mb-3">
              <div class="flex items-center justify-between text-xs">
                <span class="font-black text-slate-900">شركة الصرح للمقاولات العامة والتشطيبات • جدول حصر الكميات والمقايسة المعتمدة</span>
                <span class="font-mono text-slate-600">الفرصة #{{ oppId }}: {{ chainData.opportunity?.title }}</span>
              </div>
            </div>

            <!-- Measurement Meta Bar -->
            <div class="bg-teal-900 text-white px-3 py-1.5 rounded-lg flex items-center justify-between mb-3 text-xs">
              <div class="flex items-center gap-3">
                <span class="font-black">رابعاً: المقايسة الهندسية المعتمدة لحصر الكميات (Authoritative Quantity Takeoff)</span>
                <span v-if="currentApprovedMeasurement" class="bg-teal-700 text-teal-100 px-2 py-0.5 rounded font-mono font-bold text-[10px]">
                  كود: #{{ currentApprovedMeasurement.measurement_number }} (إصدار V{{ currentApprovedMeasurement.version }})
                </span>
              </div>
              <div v-if="currentApprovedMeasurement" class="text-[10px] text-teal-200 font-mono">
                إجمالي المسطحات: <strong class="text-white font-bold">{{ currentApprovedMeasurement.total_area }} م²</strong>
                | تاريخ الاعتماد: {{ formatDate(currentApprovedMeasurement.approved_at) }}
              </div>
            </div>

            <!-- Measurement Items Full Table -->
            <table class="w-full text-right border-collapse border border-slate-400 text-[10px] mb-3">
              <thead>
                <tr class="bg-slate-100 text-slate-900 border-b border-slate-400 font-bold">
                  <th class="p-1.5 border-l border-slate-400 text-center w-8">#</th>
                  <th class="p-1.5 border-l border-slate-400 w-36">الغرفة / الفراغ المعماري</th>
                  <th class="p-1.5 border-l border-slate-400">بند الأعمال الهندسية</th>
                  <th class="p-1.5 border-l border-slate-400 w-16 text-center">الوحدة</th>
                  <th class="p-1.5 border-l border-slate-400 w-14 text-center">العدد</th>
                  <th class="p-1.5 border-l border-slate-400 w-32 text-center">الأبعاد (ط × ع × ع)</th>
                  <th class="p-1.5 border-l border-slate-400 w-20 text-center">الخصومات</th>
                  <th class="p-1.5 border-l border-slate-400 w-28 text-center bg-teal-50 text-teal-950 font-black">الكمية الصافية</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(it, idx) in approvedMeasurementItems"
                  :key="it.id || idx"
                  class="border-b border-slate-400 align-top"
                  :class="idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'"
                >
                  <td class="p-1.5 border-l border-slate-400 text-center font-bold font-mono">{{ idx + 1 }}</td>
                  <td class="p-1.5 border-l border-slate-400 font-bold text-slate-900">{{ it.room_name }}</td>
                  <td class="p-1.5 border-l border-slate-400 text-slate-800">{{ it.item_name }}</td>
                  <td class="p-1.5 border-l border-slate-400 text-center font-bold">{{ it.unit }}</td>
                  <td class="p-1.5 border-l border-slate-400 text-center font-mono">{{ it.count || 1 }}</td>
                  <td class="p-1.5 border-l border-slate-400 text-center font-mono text-[9px]">
                    <span v-if="it.length && it.width">{{ Number(it.length).toFixed(2) }} × {{ Number(it.width).toFixed(2) }} {{ it.height ? `× ${Number(it.height).toFixed(2)}` : '' }}</span>
                    <span v-else class="text-slate-400">—</span>
                  </td>
                  <td class="p-1.5 border-l border-slate-400 text-center font-mono text-rose-800">
                    {{ Number(it.deductions) > 0 ? Number(it.deductions).toFixed(2) : '—' }}
                  </td>
                  <td class="p-1.5 border-l border-slate-400 text-center font-mono font-black text-teal-950 bg-teal-50/50 text-[11px]">
                    {{ Number(it.net_quantity).toFixed(2) }} {{ it.unit }}
                  </td>
                </tr>
                <tr v-if="!approvedMeasurementItems.length">
                  <td colspan="8" class="p-3 text-center text-slate-400 italic">لا توجد بنود مقايسة معتمدة مسجلة بعد.</td>
                </tr>
              </tbody>
            </table>

            <!-- Quantities Confirmation Note -->
            <div class="border border-slate-300 rounded p-2 bg-slate-50 text-[10px] space-y-0.5">
              <p class="font-bold text-slate-800">تأكيد الاعتماد الهندسي:</p>
              <p class="text-slate-600">
                تعتبر هذه الكميات الصافية هي المرجع الهندسي النهائي والحصري المعتمد من المكتب الفني، ولا يجوز تعديلها إلا بموجب إصدار مراجعة هندسي جديد (Revision).
              </p>
            </div>
          </div>

          <!-- Page 2 Footer -->
          <div class="flex items-center justify-between text-[9px] text-slate-500 border-t border-slate-300 pt-2 font-mono">
            <span>نظام SARH ERP المتكامل لإدارة المقاولات • وثيقة حصر الكميات والمقايسة المعتمدة</span>
            <span>صفحة 2 من 3</span>
          </div>
        </div>

        <!-- ========================================================== -->
        <!-- PAGE 3: وثيقة نطاق الأعمال ومصفوفة التتبع الفني والتواقيع      -->
        <!-- ========================================================== -->
        <div class="print-page">
          <div>
            <!-- Page 3 Header -->
            <div class="border-b border-slate-300 pb-2 mb-3">
              <div class="flex items-center justify-between text-xs">
                <span class="font-black text-slate-900">شركة الصرح للمقاولات العامة والتشطيبات • وثيقة نطاق الأعمال ومصفوفة التتبع والاعتماد النهائي</span>
                <span class="font-mono text-slate-600">الفرصة #{{ oppId }}: {{ chainData.opportunity?.title }}</span>
              </div>
            </div>

            <!-- Scope Document Summary Bar -->
            <div class="bg-emerald-950 text-white px-3 py-1.5 rounded-lg flex items-center justify-between mb-3 text-xs">
              <div class="flex items-center gap-3">
                <span class="font-black">خامساً: وثيقة نطاق الأعمال المعتمدة (Approved Scope of Work)</span>
                <span v-if="currentApprovedScope" class="bg-emerald-800 text-emerald-100 px-2 py-0.5 rounded font-mono font-bold text-[10px]">
                  كود: #{{ currentApprovedScope.scope_number }} (إصدار V{{ currentApprovedScope.version }})
                </span>
              </div>
              <div v-if="currentApprovedScope" class="text-[10px] text-emerald-200 font-mono">
                عدد حزم العمل: <strong class="text-white font-bold">{{ currentApprovedScope.items?.length || 0 }} حزمة</strong>
                | تاريخ الاعتماد: {{ formatDate(currentApprovedScope.approved_at) }}
              </div>
            </div>

            <!-- Inclusions & Exclusions Summary -->
            <div v-if="currentApprovedScope" class="grid grid-cols-2 gap-2 mb-3 text-[10px]">
              <div class="p-2 rounded border border-emerald-300 bg-emerald-50/60 text-emerald-950">
                <strong class="block font-black mb-0.5">الاشتمالات العامة المعتمدة:</strong>
                <p class="leading-relaxed">{{ currentApprovedScope.general_inclusions || 'يشمل توريد المواد ومطابقة المواصفات والعمالة الفنية والنظافة والتسليم.' }}</p>
              </div>
              <div class="p-2 rounded border border-rose-300 bg-rose-50/60 text-rose-950">
                <strong class="block font-black mb-0.5">الاستثناءات والحدود التعاقدية:</strong>
                <p class="leading-relaxed">{{ currentApprovedScope.general_exclusions || 'لا يشمل الأجهزة والتجهيزات الخاصة والرسوم الحكومية والتراخيص.' }}</p>
              </div>
            </div>

            <!-- Complete Traceability Matrix Table -->
            <div class="mb-3">
              <div class="bg-slate-800 text-white px-3 py-1 rounded-t-lg flex items-center justify-between text-xs">
                <span class="font-black">مصفوفة المطابقة والربط بين بنود العمل وحصر الكميات الفنية (Traceability Matrix)</span>
                <span class="text-[9px] text-slate-300 font-mono">Scope Items to Quantity Takeoff Mapping</span>
              </div>
              <table class="w-full text-right border-collapse border border-slate-400 text-[10px]">
                <thead>
                  <tr class="bg-slate-100 text-slate-900 border-b border-slate-400 font-bold">
                    <th class="p-1.5 border-l border-slate-400 text-center w-8">#</th>
                    <th class="p-1.5 border-l border-slate-400 w-44">بند نطاق العمل والتصنيف</th>
                    <th class="p-1.5 border-l border-slate-400">المواصفة الفنية وطريقة التنفيذ والاشتمالات</th>
                    <th class="p-1.5 w-60">بنود المقايسة الهندسية المرجعية والكميات الصافية</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(item, idx) in flattenedTraceabilityItems"
                    :key="item.id || idx"
                    class="border-b border-slate-400 align-top"
                    :class="idx % 2 === 1 ? 'bg-slate-50/50' : 'bg-white'"
                  >
                    <td class="p-1.5 border-l border-slate-400 text-center font-bold font-mono">{{ idx + 1 }}</td>
                    <td class="p-1.5 border-l border-slate-400">
                      <p class="font-black text-slate-900 leading-snug">{{ item.item_name }}</p>
                      <span class="inline-block mt-0.5 bg-slate-200 text-slate-800 text-[8px] font-bold px-1 rounded">
                        {{ item.trade_category }}
                      </span>
                    </td>
                    <td class="p-1.5 border-l border-slate-400 text-[9px] leading-relaxed text-slate-800">
                      <p class="whitespace-pre-line">{{ item.specification || 'طبقاً لأصول الصناعة والمواصفات القياسية.' }}</p>
                      <div v-if="item.inclusions" class="text-emerald-800 mt-0.5"><strong>يشمل:</strong> {{ item.inclusions }}</div>
                      <div v-if="item.exclusions" class="text-rose-800 mt-0.5"><strong>يستثني:</strong> {{ item.exclusions }}</div>
                    </td>
                    <td class="p-1.5">
                      <div v-if="item.linked_measurements && item.linked_measurements.length > 0" class="space-y-1">
                        <div
                          v-for="mIt in item.linked_measurements"
                          :key="mIt.id"
                          class="border border-slate-300 rounded p-1 bg-slate-50 text-[9px] flex items-center justify-between gap-1"
                        >
                          <div>
                            <strong class="text-slate-800 block">{{ mIt.room_name }}</strong>
                            <span class="text-slate-600 block text-[8px]">{{ mIt.item_name }}</span>
                          </div>
                          <div class="text-left font-mono font-black text-slate-900 bg-white border border-slate-400 px-1 py-0.5 rounded text-[9px] shrink-0">
                            {{ Number(mIt.net_quantity) }} {{ mIt.unit }}
                          </div>
                        </div>
                      </div>
                      <span v-else class="text-slate-400 italic text-[9px]">غير مقيد ببند مقايسة محدد</span>
                    </td>
                  </tr>
                  <tr v-if="!flattenedTraceabilityItems.length">
                    <td colspan="4" class="p-3 text-center text-slate-400 italic">لا توجد بنود مطابقة مسجلة حالياً.</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Official Signatures & Certifications Block -->
            <div class="border border-slate-400 rounded-lg p-2.5 bg-white mb-2">
              <h4 class="text-center text-xs font-black text-slate-900 mb-2 border-b border-slate-200 pb-1">
                الاعتماد والتوثيق الرسمي لسلسلة العمليات التجارية والفنية (SARH Operational Sign-Off)
              </h4>
              <div class="grid grid-cols-4 gap-2 text-center text-[10px]">
                <!-- 1. Commercial Lead -->
                <div class="border border-slate-300 rounded p-1.5 bg-slate-50/50">
                  <span class="font-bold text-slate-500 block mb-0.5">إدارة المبيعات والتأهيل:</span>
                  <p class="font-black text-slate-900 text-[10px] mb-3">{{ chainData.opportunity?.assigned_user?.name || 'مسؤول المبيعات' }}</p>
                  <div class="border-t border-dashed border-slate-400 pt-0.5 text-[8px] text-slate-400">التوقيع والتاريخ</div>
                </div>

                <!-- 2. Site Survey Lead -->
                <div class="border border-slate-300 rounded p-1.5 bg-slate-50/50">
                  <span class="font-bold text-slate-500 block mb-0.5">المعاينة الميدانية:</span>
                  <p class="font-black text-slate-900 text-[10px] mb-3">{{ chainData.site_visits?.[0]?.assigned_user?.name || 'مهندس المعاينة' }}</p>
                  <div class="border-t border-dashed border-slate-400 pt-0.5 text-[8px] text-slate-400">التوقيع والتاريخ</div>
                </div>

                <!-- 3. Technical Office Lead -->
                <div class="border border-slate-300 rounded p-1.5 bg-slate-50/50">
                  <span class="font-bold text-slate-500 block mb-0.5">المكتب الفني والمقايسات:</span>
                  <p class="font-black text-slate-900 text-[10px] mb-3">{{ currentApprovedMeasurement?.approved_user?.name || 'مهندس حساب الكميات' }}</p>
                  <div class="border-t border-dashed border-slate-400 pt-0.5 text-[8px] text-slate-400">التوقيع والتاريخ</div>
                </div>

                <!-- 4. Engineering Approval with Stamp Simulation -->
                <div class="border border-slate-300 rounded p-1.5 bg-slate-50/50 relative overflow-hidden">
                  <span class="font-bold text-slate-500 block mb-0.5">الاعتماد الهندسي والتنفيذي:</span>
                  <p class="font-black text-emerald-900 text-[10px] mb-3">{{ currentApprovedScope?.approved_user?.name || 'مدير الإدارة الهندسية' }}</p>
                  <!-- Stamp simulation -->
                  <div class="absolute inset-x-0 bottom-3 flex justify-center opacity-90 pointer-events-none">
                    <div class="border-2 border-emerald-700 text-emerald-800 font-black text-[8px] px-1.5 py-0.5 rounded uppercase rotate-[-5deg] bg-white/95 shadow-2xs">
                      معتمد رسمياً • SARH APPROVED
                    </div>
                  </div>
                  <div class="border-t border-dashed border-slate-400 pt-0.5 text-[8px] text-slate-400">التوقيع والختم</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Page 3 Footer -->
          <div class="flex items-center justify-between text-[9px] text-slate-500 border-t border-slate-300 pt-2 font-mono">
            <span>نظام SARH ERP المتكامل لإدارة المقاولات • وثيقة التتبع والاعتماد النهائي</span>
            <span>صفحة 3 من 3</span>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import { useNotificationStore } from '../../stores/notification';
import { useAuthStore } from '../../stores/auth';
import {
  Layers,
  ArrowRight,
  ArrowLeft,
  RefreshCw,
  AlertCircle,
  Briefcase,
  Ruler,
  ClipboardList,
  User,
  MapPin,
  CheckCircle2,
  Printer,
  ShieldCheck,
  ExternalLink,
  Link2,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
const notificationStore = useNotificationStore();
const authStore = useAuthStore();

const oppId = computed(() => route.params.id);
const loading = ref(false);
const error = ref(null);
const chainData = ref(null);

// Active Tab navigation state
const activeTab = ref('overview');

const currentApprovedMeasurement = computed(() => {
  return chainData.value?.approved_measurement || (chainData.value?.measurements || []).find((m) => m.status === 'Approved') || null;
});

const currentApprovedScope = computed(() => {
  return chainData.value?.approved_scope || (chainData.value?.scopes || []).find((s) => s.status === 'Approved') || null;
});

const approvedMeasurementItems = computed(() => {
  if (currentApprovedMeasurement.value?.items) {
    return currentApprovedMeasurement.value.items;
  }
  return [];
});

const flattenedTraceabilityItems = computed(() => {
  if (!chainData.value?.traceability_matrix) return [];
  const list = [];
  chainData.value.traceability_matrix.forEach((scopeTrace) => {
    (scopeTrace.items || []).forEach((item) => {
      list.push({
        ...item,
        scope_number: scopeTrace.scope_number,
        scope_version: scopeTrace.version,
      });
    });
  });
  return list;
});

// Dynamic Tab list with counts
const availableTabs = computed(() => [
  { id: 'overview', label: 'ملخص المسار والعملية', icon: Briefcase },
  {
    id: 'measurements',
    label: 'المقايسة وحصر الكميات',
    icon: Ruler,
    badge: approvedMeasurementItems.value.length ? `${approvedMeasurementItems.value.length} بند` : null,
  },
  {
    id: 'scope',
    label: 'نطاق الأعمال والمواصفات',
    icon: ClipboardList,
    badge: currentApprovedScope.value ? `V${currentApprovedScope.value.version}` : null,
  },
  {
    id: 'traceability',
    label: 'مصفوفة التتبع الفني',
    icon: Link2,
    badge: flattenedTraceabilityItems.value.length ? `${flattenedTraceabilityItems.value.length}` : null,
  },
  {
    id: 'site_visits',
    label: 'المعاينات الميدانية',
    icon: MapPin,
    badge: chainData.value?.site_visits?.length ? `${chainData.value.site_visits.length}` : null,
  },
]);

const getStageLabel = (stage) => {
  const map = {
    New: 'فرصة جديدة',
    Qualified: 'مؤهلة تجارياً',
    Proposal: 'تقديم عرض',
    Negotiation: 'تفاوض تعاقدي',
    Won: 'عملية فائزة',
    Lost: 'عملية خاسرة',
  };
  return map[stage] || stage || 'غير محدد';
};

const fetchChain = async () => {
  if (!oppId.value) return;
  loading.value = true;
  error.value = null;

  try {
    const res = await api.get(`/opportunities/${oppId.value}/chain`);
    if (res.data?.success) {
      chainData.value = res.data.data;
    } else {
      error.value = res.data?.message || 'تعذر تحميل بيانات السلسلة';
    }
  } catch (err) {
    error.value = err.response?.data?.message || 'فشل الاتصال بالخادم لجلب بيانات مسار الفرصة';
    notificationStore.addToast({
      type: 'error',
      title: 'خطأ في التحميل',
      message: error.value,
    });
  } finally {
    loading.value = false;
  }
};

const formatDate = (val) => {
  if (!val) return '—';
  return String(val).slice(0, 10);
};

const printReport = () => {
  window.print();
};

onMounted(() => {
  fetchChain();
});
</script>

<style>
@media screen {
  #official-printable-chain {
    display: none !important;
  }
}

@media print {
  @page {
    size: A4 portrait;
    margin: 8mm 8mm 8mm 8mm;
  }

  html, body {
    margin: 0 !important;
    padding: 0 !important;
    overflow: visible !important;
    height: auto !important;
    min-height: 100% !important;
    background: #ffffff !important;
    color: #0f172a !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    font-family: 'Cairo', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif !important;
  }

  /* 1. Completely hide the entire Vue SPA Application root on print */
  #app {
    display: none !important;
  }

  /* 2. Show ONLY the teleported official printable document */
  #official-printable-chain {
    display: block !important;
    position: static !important;
    width: 100% !important;
    height: auto !important;
    overflow: visible !important;
    margin: 0 !important;
    padding: 0 !important;
    background: #ffffff !important;
    color: #0f172a !important;
    direction: rtl !important;
  }

  /* 3. Strict 3-page division with explicit breaks */
  .print-page {
    page-break-after: always !important;
    break-after: page !important;
    min-height: 275mm !important;
    display: flex !important;
    flex-direction: column !important;
    justify-content: space-between !important;
    box-sizing: border-box !important;
    padding: 2mm 0 !important;
  }

  .print-page:last-child {
    page-break-after: avoid !important;
    break-after: avoid !important;
  }

  table {
    width: 100% !important;
    border-collapse: collapse !important;
  }

  tr {
    break-inside: avoid !important;
    page-break-inside: avoid !important;
  }

  thead {
    display: table-header-group !important;
  }

  tfoot {
    display: table-footer-group !important;
  }
}
</style>
