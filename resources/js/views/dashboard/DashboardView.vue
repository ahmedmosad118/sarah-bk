<template>
  <div class="space-y-6">
    <!-- 1. Executive Welcome Header Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-emerald-500/10 via-[#00C896]/5 to-blue-500/10 dark:from-slate-900 dark:via-[#0c1f20] dark:to-slate-900 border border-emerald-200/80 dark:border-emerald-500/20 p-6 sm:p-8 text-gray-900 dark:text-white shadow-xs">
      <!-- Ambient Glow Accents -->
      <div class="absolute -top-24 -left-24 h-56 w-56 rounded-full bg-[#00C896]/15 dark:bg-[#00C896]/20 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 h-56 w-56 rounded-full bg-blue-500/10 dark:bg-blue-500/15 blur-3xl pointer-events-none"></div>

      <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
        <div>
          <div class="inline-flex items-center gap-2 rounded-full bg-[#00C896]/15 text-emerald-800 dark:text-[#00C896] border border-[#00C896]/30 px-3 py-1 text-xs font-bold backdrop-blur-md mb-3">
            <Building2 class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ authStore.tenant?.name || $t('common.system') }}</span>
            <span>•</span>
            <span class="font-mono">{{ authStore.tenant?.company_code }}</span>
          </div>

          <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white">
            {{ $t('dashboard.welcome', { name: authStore.user?.name || '' }) }}
          </h1>
          <p class="text-xs text-gray-500 dark:text-gray-300 mt-1 max-w-2xl leading-relaxed">
            {{ $t('dashboard.commercialSubtitle') }}
          </p>
        </div>

        <!-- Quick Primary Commercial Actions -->
        <div class="flex flex-wrap items-center gap-2">
          <router-link
            to="/site-visits"
            class="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 px-3.5 py-2.5 text-xs font-black text-white transition-all shadow-xs"
          >
            <MapPin class="h-4 w-4" />
            <span>{{ $t('dashboard.quickScheduleVisit') }}</span>
          </router-link>

          <router-link
            to="/opportunities"
            class="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 px-3.5 py-2.5 text-xs font-black text-white transition-all shadow-xs"
          >
            <Target class="h-4 w-4" />
            <span>{{ $t('dashboard.quickCreateOpportunity') }}</span>
          </router-link>

          <router-link
            to="/leads"
            class="inline-flex items-center gap-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 px-3.5 py-2.5 text-xs font-black text-white transition-all shadow-xs"
          >
            <Sparkles class="h-4 w-4" />
            <span>{{ $t('dashboard.quickCaptureLead') }}</span>
          </router-link>

          <router-link
            to="/customers"
            class="inline-flex items-center gap-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] px-3.5 py-2.5 text-xs font-black text-white transition-all shadow-xs"
          >
            <UserPlus class="h-4 w-4" />
            <span>{{ $t('dashboard.quickAddCustomer') }}</span>
          </router-link>
        </div>
      </div>
    </div>

    <!-- 2. Core Commercial KPI Cards Grid -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- 1. Customers KPI -->
      <router-link
        to="/customers"
        class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-[#00C896]/40 hover:shadow-md"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('dashboard.kpiCustomers') }}</p>
            <h3 class="mt-1 text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.customers?.total || 0 }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400 transition-transform group-hover:scale-110">
            <Users class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-gray-50 dark:border-gray-800/80 pt-2 text-[11px]">
          <span class="text-gray-500 dark:text-gray-400 font-medium">
            {{ stats.customers?.individual || 0 }} أفراد • {{ stats.customers?.company || 0 }} شركات
          </span>
          <span class="text-[#00C896] font-bold flex items-center gap-0.5">
            {{ $t('dashboard.viewAll') }}
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3 w-3" />
            <ChevronRight v-else class="h-3 w-3" />
          </span>
        </div>
      </router-link>

      <!-- 2. Leads KPI -->
      <router-link
        to="/leads"
        class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-amber-500/40 hover:shadow-md"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('dashboard.kpiLeads') }}</p>
            <h3 class="mt-1 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ stats.leads?.total || 0 }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 transition-transform group-hover:scale-110">
            <Sparkles class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-gray-50 dark:border-gray-800/80 pt-2 text-[11px]">
          <span class="text-gray-500 dark:text-gray-400 font-medium">
            {{ stats.leads?.new || 0 }} جديد • {{ stats.leads?.contacted || 0 }} متابعة
          </span>
          <span class="text-amber-600 dark:text-amber-400 font-bold flex items-center gap-0.5">
            {{ $t('dashboard.viewAll') }}
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3 w-3" />
            <ChevronRight v-else class="h-3 w-3" />
          </span>
        </div>
      </router-link>

      <!-- 3. Opportunities & Pipeline Value KPI -->
      <router-link
        to="/opportunities"
        class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/40 hover:shadow-md"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('dashboard.kpiOpportunities') }}</p>
            <div class="mt-1 flex items-baseline gap-2">
              <span class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ stats.opportunities?.active || 0 }}</span>
              <span class="text-xs font-bold text-gray-400">فرصة نشطة</span>
            </div>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 transition-transform group-hover:scale-110">
            <Target class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-gray-50 dark:border-gray-800/80 pt-2 text-[11px]">
          <span class="font-mono font-bold text-purple-700 dark:text-purple-300">
            {{ formatMoney(stats.opportunities?.total_estimated_value) }} <span class="text-[10px] text-gray-400 font-sans">ج.م</span>
          </span>
          <span class="text-purple-600 dark:text-purple-400 font-bold flex items-center gap-0.5">
            {{ $t('dashboard.viewAll') }}
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3 w-3" />
            <ChevronRight v-else class="h-3 w-3" />
          </span>
        </div>
      </router-link>

      <!-- 4. Site Visits KPI -->
      <router-link
        to="/site-visits"
        class="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/40 hover:shadow-md"
      >
        <div class="flex items-center justify-between">
          <div>
            <p class="text-[11px] font-bold text-gray-500 dark:text-gray-400">{{ $t('dashboard.kpiSiteVisits') }}</p>
            <h3 class="mt-1 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">{{ stats.site_visits?.total || 0 }}</h3>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 transition-transform group-hover:scale-110">
            <MapPin class="h-6 w-6" />
          </div>
        </div>
        <div class="mt-3 flex items-center justify-between border-t border-gray-50 dark:border-gray-800/80 pt-2 text-[11px]">
          <span class="text-blue-600 dark:text-blue-400 font-bold">
            {{ stats.site_visits?.scheduled_today || 0 }} معاينة اليوم • {{ stats.site_visits?.scheduled || 0 }} مجدولة
          </span>
          <span class="text-blue-600 dark:text-blue-400 font-bold flex items-center gap-0.5">
            {{ $t('dashboard.viewAll') }}
            <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3 w-3" />
            <ChevronRight v-else class="h-3 w-3" />
          </span>
        </div>
      </router-link>
    </div>

    <!-- 3. Commercial Journey & Funnel Flow Widget -->
    <div class="rounded-3xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-4">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
        <div>
          <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
            <TrendingUp class="h-4 w-4 text-[#00C896]" />
            <span>{{ $t('dashboard.businessJourneyTitle') }}</span>
          </h3>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
            {{ $t('dashboard.businessJourneySubtitle') }}
          </p>
        </div>
        <div class="flex items-center gap-1 text-[11px] font-bold text-gray-400">
          <span>دورة العمل الكاملة</span>
        </div>
      </div>

      <!-- Horizontal Commercial Flow Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-2.5 pt-2">
        <!-- 1. Customers -->
        <router-link
          to="/customers"
          class="p-3 rounded-2xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 text-center space-y-1 hover:border-emerald-500 transition-all"
        >
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl bg-emerald-500 text-white flex items-center justify-center text-xs font-bold shadow-xs">
              1
            </div>
          </div>
          <span class="block text-xs font-black text-emerald-900 dark:text-emerald-300">العملاء</span>
          <span class="block font-mono font-black text-sm text-emerald-700 dark:text-emerald-400">{{ stats.customers?.total || 0 }}</span>
        </router-link>

        <!-- 2. Leads -->
        <router-link
          to="/leads"
          class="p-3 rounded-2xl bg-amber-50/50 dark:bg-amber-950/20 border border-amber-100 dark:border-amber-900/40 text-center space-y-1 hover:border-amber-500 transition-all"
        >
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl bg-amber-500 text-white flex items-center justify-center text-xs font-bold shadow-xs">
              2
            </div>
          </div>
          <span class="block text-xs font-black text-amber-900 dark:text-amber-300">الطلبات (Leads)</span>
          <span class="block font-mono font-black text-sm text-amber-700 dark:text-amber-400">{{ stats.leads?.total || 0 }}</span>
        </router-link>

        <!-- 3. Opportunities -->
        <router-link
          to="/opportunities"
          class="p-3 rounded-2xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 text-center space-y-1 hover:border-purple-500 transition-all"
        >
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl bg-purple-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
              3
            </div>
          </div>
          <span class="block text-xs font-black text-purple-900 dark:text-purple-300">الفرص البيعية</span>
          <span class="block font-mono font-black text-sm text-purple-700 dark:text-purple-400">{{ stats.opportunities?.total || 0 }}</span>
        </router-link>

        <!-- 4. Site Visits -->
        <router-link
          to="/site-visits"
          class="p-3 rounded-2xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 text-center space-y-1 hover:border-blue-500 transition-all ring-2 ring-blue-500/30"
        >
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl bg-blue-600 text-white flex items-center justify-center text-xs font-bold shadow-xs">
              4
            </div>
          </div>
          <span class="block text-xs font-black text-blue-900 dark:text-blue-300">المعاينات</span>
          <span class="block font-mono font-black text-sm text-blue-700 dark:text-blue-400">{{ stats.site_visits?.total || 0 }}</span>
        </router-link>

        <!-- 5. Measurements & BOQ (Upcoming Phase 7) -->
        <div class="p-3 rounded-2xl bg-gray-50/60 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-800 text-center space-y-1 opacity-70">
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl border border-dashed border-gray-400 text-gray-500 flex items-center justify-center text-xs font-bold">
              5
            </div>
          </div>
          <span class="block text-xs font-bold text-gray-500 dark:text-gray-400">المقايسات و BOQ</span>
          <span class="block text-[10px] text-purple-600 dark:text-purple-400 font-bold">المرحلة القادمة</span>
        </div>

        <!-- 6. Quotation (Upcoming Phase 8) -->
        <div class="p-3 rounded-2xl bg-gray-50/60 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-800 text-center space-y-1 opacity-50">
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-400 flex items-center justify-center text-xs">
              6
            </div>
          </div>
          <span class="block text-xs font-medium text-gray-400">عروض الأسعار</span>
          <span class="block text-[10px] text-gray-400">—</span>
        </div>

        <!-- 7. Contract & Project -->
        <div class="p-3 rounded-2xl bg-gray-50/60 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-800 text-center space-y-1 opacity-50">
          <div class="flex items-center justify-center">
            <div class="h-7 w-7 rounded-xl border border-gray-300 dark:border-gray-700 text-gray-400 flex items-center justify-center text-xs">
              7
            </div>
          </div>
          <span class="block text-xs font-medium text-gray-400">التعاقد والمشروع</span>
          <span class="block text-[10px] text-gray-400">—</span>
        </div>
      </div>
    </div>

    <!-- 4. Interactive Operations Grid (Split Columns) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Left Column: Upcoming Site Visits & Active Opportunities -->
      <div class="space-y-6">
        <!-- Widget 1: Today's & Upcoming Site Visits -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="p-2 rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <Calendar class="h-4 w-4" />
              </div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ $t('dashboard.upcomingVisitsTitle') }}
              </h3>
            </div>
            <router-link
              to="/site-visits"
              class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
            >
              <span>{{ $t('dashboard.viewAll') }}</span>
              <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
              <ChevronRight v-else class="h-3.5 w-3.5" />
            </router-link>
          </div>

          <div v-if="upcomingVisits.length > 0" class="space-y-2.5">
            <div
              v-for="visit in upcomingVisits"
              :key="visit.id"
              class="p-3.5 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 hover:border-blue-500/40 transition-all"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-gray-900 dark:text-white">{{ visit.customer?.name }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="getVisitStatusClass(visit.status)"
                  >
                    {{ getVisitStatusLabel(visit.status) }}
                  </span>
                </div>
                <div class="flex flex-wrap items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400 font-medium">
                  <span v-if="visit.scheduled_date" class="flex items-center gap-1 font-mono text-blue-600 dark:text-blue-400 font-bold">
                    <Clock class="h-3 w-3" />
                    {{ formatDate(visit.scheduled_date) }} {{ visit.scheduled_time ? `(${visit.scheduled_time})` : '' }}
                  </span>
                  <span v-if="visit.assigned_user" class="flex items-center gap-1">
                    <User class="h-3 w-3" />
                    {{ visit.assigned_user.name }}
                  </span>
                </div>
              </div>

              <!-- Quick Communication Actions -->
              <div class="flex items-center gap-1.5 shrink-0">
                <a
                  v-if="visit.customer?.phone"
                  :href="`tel:${visit.customer.phone}`"
                  class="p-2 rounded-xl bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 transition-colors"
                  title="اتصال"
                >
                  <Phone class="h-3.5 w-3.5" />
                </a>
                <a
                  v-if="visit.customer?.whatsapp || visit.customer?.phone"
                  :href="`https://wa.me/${(visit.customer.whatsapp || visit.customer.phone).replace(/[^0-9]/g, '')}`"
                  target="_blank"
                  class="p-2 rounded-xl bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400 transition-colors"
                  title="واتساب"
                >
                  <MessageSquare class="h-3.5 w-3.5" />
                </a>
                <router-link
                  :to="`/site-visits?id=${visit.id}`"
                  class="p-2 rounded-xl bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 transition-colors"
                  title="عرض التفاصيل"
                >
                  <Eye class="h-3.5 w-3.5" />
                </router-link>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-6 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl">
            {{ $t('dashboard.noUpcomingVisits') }}
          </p>
        </div>

        <!-- Widget 2: Top Active Opportunities -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <Target class="h-4 w-4" />
              </div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ $t('dashboard.activeOpportunitiesTitle') }}
              </h3>
            </div>
            <router-link
              to="/opportunities"
              class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1"
            >
              <span>{{ $t('dashboard.viewAll') }}</span>
              <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
              <ChevronRight v-else class="h-3.5 w-3.5" />
            </router-link>
          </div>

          <div v-if="activeOpportunities.length > 0" class="space-y-2.5">
            <div
              v-for="opp in activeOpportunities"
              :key="opp.id"
              class="p-3.5 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 hover:border-purple-500/40 transition-all"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-gray-900 dark:text-white">{{ opp.title }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="getStageBadgeClass(opp.stage)"
                  >
                    {{ getStageLabel(opp.stage) }}
                  </span>
                </div>
                <p v-if="opp.customer" class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">
                  {{ opp.customer.name }} {{ opp.customer.company_name ? `• ${opp.customer.company_name}` : '' }}
                </p>
              </div>

              <div class="text-end shrink-0">
                <p class="text-xs font-mono font-black text-purple-600 dark:text-purple-400">
                  {{ opp.estimated_value ? Number(opp.estimated_value).toLocaleString() : '—' }}
                  <span class="text-[10px] text-gray-400 font-sans">ج.م</span>
                </p>
                <span v-if="opp.expected_close_date" class="text-[10px] text-gray-400 font-mono">
                  إغلاق: {{ formatDate(opp.expected_close_date) }}
                </span>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-6 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl">
            {{ $t('dashboard.noActiveOpportunities') }}
          </p>
        </div>
      </div>

      <!-- Right Column: Recent Leads & Commercial Activity Feed -->
      <div class="space-y-6">
        <!-- Widget 3: Recent Incoming Leads -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="p-2 rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <Sparkles class="h-4 w-4" />
              </div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ $t('dashboard.recentLeadsTitle') }}
              </h3>
            </div>
            <router-link
              to="/leads"
              class="text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline flex items-center gap-1"
            >
              <span>{{ $t('dashboard.viewAll') }}</span>
              <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
              <ChevronRight v-else class="h-3.5 w-3.5" />
            </router-link>
          </div>

          <div v-if="recentLeads.length > 0" class="space-y-2.5">
            <div
              v-for="lead in recentLeads"
              :key="lead.id"
              class="p-3.5 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 hover:border-amber-500/40 transition-all"
            >
              <div class="space-y-1">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-gray-900 dark:text-white">{{ lead.title }}</span>
                  <span
                    class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                    :class="lead.status === 'New' ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'"
                  >
                    {{ lead.status === 'New' ? 'جديد' : 'تم التواصل' }}
                  </span>
                </div>
                <p v-if="lead.customer" class="text-[11px] text-gray-500 dark:text-gray-400 font-semibold">
                  {{ lead.customer.name }} {{ lead.customer.phone ? `(${lead.customer.phone})` : '' }}
                </p>
              </div>

              <div class="flex items-center gap-1.5 shrink-0">
                <router-link
                  :to="`/leads?id=${lead.id}`"
                  class="p-2 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 transition-colors"
                  title="عرض ومتابعة"
                >
                  <Eye class="h-3.5 w-3.5" />
                </router-link>
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-6 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl">
            {{ $t('dashboard.noRecentLeads') }}
          </p>
        </div>

        <!-- Widget 4: Recent Commercial Activity Stream -->
        <div class="rounded-3xl border border-gray-100 bg-white p-6 dark:border-gray-800 dark:bg-gray-900 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2">
              <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <Activity class="h-4 w-4" />
              </div>
              <h3 class="text-sm font-black text-gray-900 dark:text-white">
                {{ $t('dashboard.recentActivityTitle') }}
              </h3>
            </div>
            <router-link
              to="/activities"
              class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
            >
              <span>{{ $t('dashboard.viewAll') }}</span>
              <ChevronLeft v-if="$i18n.locale === 'ar'" class="h-3.5 w-3.5" />
              <ChevronRight v-else class="h-3.5 w-3.5" />
            </router-link>
          </div>

          <div v-if="recentActivities.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800">
            <div
              v-for="act in recentActivities"
              :key="act.id"
              class="py-3 flex items-start justify-between gap-3 text-xs"
            >
              <div class="space-y-0.5">
                <p class="font-bold text-gray-800 dark:text-gray-200 leading-snug">
                  {{ act.description }}
                </p>
                <span v-if="act.causer" class="text-[11px] text-gray-400 flex items-center gap-1">
                  <User class="h-3 w-3" />
                  {{ act.causer.name }}
                </span>
              </div>
              <span class="text-[10px] text-gray-400 font-mono shrink-0">
                {{ formatTimeAgo(act.created_at) }}
              </span>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-6 text-center bg-gray-50/50 dark:bg-gray-800/20 rounded-2xl">
            لا توجد أنشطة مسجلة بعد
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../../stores/auth';
import api from '../../services/api';
import { formatDate } from '../../utils/date';

// Lucide Icons
import {
  Building2,
  Users,
  UserPlus,
  Tag,
  ShieldCheck,
  Activity,
  Sparkles,
  Target,
  MapPin,
  Calendar,
  Clock,
  CheckCircle2,
  Phone,
  MessageSquare,
  Eye,
  TrendingUp,
  User,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next';

const authStore = useAuthStore();

// Reactive State
const stats = ref({
  customers: { total: 0, individual: 0, company: 0 },
  leads: { total: 0, new: 0, contacted: 0, qualified: 0, converted: 0 },
  opportunities: { total: 0, active: 0, won: 0, total_estimated_value: 0 },
  site_visits: { total: 0, scheduled: 0, completed: 0, scheduled_today: 0, overdue: 0 },
  team: { total_users: 1 },
});

const upcomingVisits = ref([]);
const activeOpportunities = ref([]);
const recentLeads = ref([]);
const recentActivities = ref([]);

const formatMoney = (val) => {
  if (!val) return '0';
  return Number(val).toLocaleString();
};

const formatTimeAgo = (dateStr) => {
  if (!dateStr) return '';
  const date = new Date(dateStr);
  const now = new Date();
  const diffSec = Math.floor((now - date) / 1000);

  if (diffSec < 60) return 'الآن';
  if (diffSec < 3600) return `منذ ${Math.floor(diffSec / 60)} دقيقة`;
  if (diffSec < 86400) return `منذ ${Math.floor(diffSec / 3600)} ساعة`;
  return formatDate(dateStr);
};

// Stage and Status Style Helpers
const getVisitStatusClass = (status) => {
  switch (status) {
    case 'Scheduled':
      return 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    case 'Completed':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    case 'Requested':
      return 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    case 'Rescheduled':
      return 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
    case 'Cancelled':
      return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
  }
};

const getVisitStatusLabel = (status) => {
  switch (status) {
    case 'Scheduled':
      return 'مجدولة';
    case 'Completed':
      return 'مكتملة';
    case 'Requested':
      return 'طلب جديد';
    case 'Rescheduled':
      return 'معاد جدولتها';
    case 'Cancelled':
      return 'ملغاة';
    default:
      return status || '—';
  }
};

const getStageBadgeClass = (stage) => {
  switch (stage) {
    case 'New':
      return 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300';
    case 'Qualified':
      return 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300';
    case 'Proposal':
      return 'bg-amber-50 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300';
    case 'Negotiation':
      return 'bg-orange-50 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300';
    case 'Won':
      return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300';
    case 'Lost':
      return 'bg-rose-50 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
  }
};

const getStageLabel = (stage) => {
  switch (stage) {
    case 'New':
      return 'فرصة جديدة';
    case 'Qualified':
      return 'مؤهلة';
    case 'Proposal':
      return 'تقديم عرض';
    case 'Negotiation':
      return 'تفاوض';
    case 'Won':
      return 'تم التعاقد';
    case 'Lost':
      return 'فرصة ضائعة';
    default:
      return stage || '—';
  }
};

const fetchDashboardData = async () => {
  try {
    const res = await api.get('/dashboard/summary');
    if (res.data?.success) {
      stats.value = res.data.stats || stats.value;
      upcomingVisits.value = res.data.upcoming_visits || [];
      activeOpportunities.value = res.data.active_opportunities || [];
      recentLeads.value = res.data.recent_leads || [];
      recentActivities.value = res.data.recent_activities || [];
    }
  } catch (err) {
    console.error('Failed to load dashboard summary', err);
  }
};

onMounted(() => {
  fetchDashboardData();
});
</script>
