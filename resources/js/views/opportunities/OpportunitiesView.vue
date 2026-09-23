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
    <QuickCustomerModal
      :show="showQuickCustomerModal"
      @close="showQuickCustomerModal = false"
      @customer-created="onQuickCustomerCreated"
    />

    <!-- 2. Opportunity Details & Stage Progression Modal -->
    <CrudModal
      :show="showDetailsModal"
      :title="$t('opportunities.opportunityDetails')"
      :show-save-button="false"
      :size="'5xl'"
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

        <!-- Commercial Flow Hub: Site Visits & Measurements Sections -->
        <div class="space-y-4">
          <!-- 1. Site Visits Section -->
          <div class="p-4 rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/40 dark:bg-blue-950/20 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-blue-600 text-white shadow-xs">
                  <MapPin class="h-4 w-4" />
                </div>
                <div>
                  <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
                    <span>المعاينات الميدانية (Site Visits)</span>
                    <span v-if="selectedOpportunity.site_visits?.length" class="ms-1 px-1.5 py-0.5 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-[10px] font-mono font-bold">
                      {{ selectedOpportunity.site_visits.length }}
                    </span>
                  </h4>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">حجز وإدارة مواعيد المعاينة وتفكيك غرف الموقع</p>
                </div>
              </div>

              <button
                type="button"
                @click="openQuickSiteVisitModal(selectedOpportunity)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer self-start sm:self-auto"
              >
                <Plus class="h-3.5 w-3.5" />
                <span>حجز معاينة جديدة</span>
              </button>
            </div>

            <!-- List of Site Visits -->
            <div v-if="selectedOpportunity.site_visits && selectedOpportunity.site_visits.length > 0" class="space-y-2">
              <div
                v-for="v in selectedOpportunity.site_visits"
                :key="v.id"
                class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-blue-100 dark:border-blue-900/30 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
              >
                <div class="flex items-start sm:items-center gap-2.5">
                  <div class="p-2 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold shrink-0 font-mono">
                    #{{ v.id }}
                  </div>
                  <div>
                    <div class="flex items-center gap-2 flex-wrap">
                      <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="getVisitStatusClass(v.status)"
                      >
                        {{ getVisitStatusLabel(v.status) }}
                      </span>
                      <span v-if="v.scheduled_date || v.visit_date" class="text-[11px] font-mono text-gray-700 dark:text-gray-300 font-bold flex items-center gap-1">
                        <Calendar class="h-3 w-3 text-gray-400" />
                        {{ formatDate(v.visit_date || v.scheduled_date) }} {{ v.scheduled_time || '' }}
                      </span>
                    </div>
                    <p v-if="v.assigned_user" class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 flex items-center gap-1">
                      <User class="h-3 w-3 text-gray-400" />
                      <span>المهندس المكلف: <strong class="text-gray-700 dark:text-gray-300">{{ v.assigned_user.name }}</strong></span>
                    </p>
                    <p v-if="v.general_assessment" class="text-[11px] text-gray-600 dark:text-gray-300 mt-0.5 line-clamp-1 italic">
                      "{{ v.general_assessment }}"
                    </p>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                  <span v-if="v.rooms && v.rooms.length" class="text-[11px] text-purple-600 dark:text-purple-400 font-bold bg-purple-50 dark:bg-purple-900/20 px-2 py-0.5 rounded-md">
                    {{ v.rooms.length }} فراغات
                  </span>
                  <router-link
                    :to="`/site-visits?opportunity_id=${selectedOpportunity.id}`"
                    class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                    title="فتح في شاشة المعاينات التفصيلية"
                  >
                    <ExternalLink class="h-3.5 w-3.5" />
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Empty Site Visits -->
            <div v-else class="p-3.5 rounded-xl bg-white/70 dark:bg-gray-900/40 border border-dashed border-blue-200 dark:border-blue-900/40 text-center py-4">
              <p class="text-xs text-gray-500 dark:text-gray-400">لم يتم تسجيل أي معاينة ميدانية لهذه الفرصة حتى الآن.</p>
              <button
                type="button"
                @click="openQuickSiteVisitModal(selectedOpportunity)"
                class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 cursor-pointer"
              >
                <Plus class="h-3.5 w-3.5" />
                <span>حجز موعد معاينة الآن مباشرة من هنا</span>
              </button>
            </div>
          </div>

          <!-- 2. Measurements Section -->
          <div class="p-4 rounded-2xl border border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/40 dark:bg-emerald-950/20 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
              <div class="flex items-center gap-2">
                <div class="p-2 rounded-xl bg-[#00C896] text-white shadow-xs">
                  <Ruler class="h-4 w-4" />
                </div>
                <div>
                  <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
                    <span>المقايسات وحصر الكميات (Measurements)</span>
                    <span v-if="selectedOpportunity.measurements?.length" class="ms-1 px-1.5 py-0.5 rounded-full bg-emerald-100 dark:bg-emerald-900/50 text-emerald-700 dark:text-emerald-300 text-[10px] font-mono font-bold">
                      {{ selectedOpportunity.measurements.length }}
                    </span>
                  </h4>
                  <p class="text-[11px] text-gray-500 dark:text-gray-400">حصر الكميات والأبعاد المسطحة والحجوم والمقايسات الهندسية</p>
                </div>
              </div>

              <div class="flex items-center gap-1.5 self-start sm:self-auto flex-wrap">
                <button
                  v-if="hasCompletedSiteVisit"
                  type="button"
                  @click="createMeasurementFromCompletedVisit"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
                  title="استيراد غرف وفراغات المعاينة المكتملة"
                >
                  <Sparkles class="h-3.5 w-3.5" />
                  <span>استيراد غرف المعاينة</span>
                </button>

                <button
                  type="button"
                  @click="createBlankMeasurement"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
                >
                  <Plus class="h-3.5 w-3.5" />
                  <span>إنشاء مقايسة جديدة</span>
                </button>
              </div>
            </div>

            <!-- List of Measurements -->
            <div v-if="selectedOpportunity.measurements && selectedOpportunity.measurements.length > 0" class="space-y-2">
              <div
                v-for="m in selectedOpportunity.measurements"
                :key="m.id"
                class="p-3 rounded-xl bg-white dark:bg-gray-900 border border-emerald-100 dark:border-emerald-900/30 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
              >
                <div class="flex items-start sm:items-center gap-2.5">
                  <div class="p-2 rounded-lg bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold shrink-0 font-mono">
                    {{ m.measurement_number || `#${m.id}` }}
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span
                        class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                        :class="getMeasurementStatusClass(m.status)"
                      >
                        {{ getMeasurementStatusLabel(m.status) }}
                      </span>
                      <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300 font-mono">
                        v{{ m.version || 1 }}
                      </span>
                    </div>
                    <div class="flex items-center gap-3 text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                      <span v-if="m.total_area > 0" class="font-mono font-bold text-gray-800 dark:text-gray-200">
                        {{ Number(m.total_area).toLocaleString() }} م²
                      </span>
                      <span v-if="m.total_volume > 0" class="font-mono font-bold text-gray-800 dark:text-gray-200">
                        {{ Number(m.total_volume).toLocaleString() }} م³
                      </span>
                      <span v-if="m.measured_user">
                        المعد: {{ m.measured_user.name }}
                      </span>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                  <router-link
                    :to="`/measurements?opportunity_id=${selectedOpportunity.id}`"
                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 dark:text-emerald-300 font-bold text-xs transition-colors"
                  >
                    <span>فتح جدول المقايسة</span>
                    <ExternalLink class="h-3 w-3" />
                  </router-link>
                </div>
              </div>
            </div>

            <!-- Empty Measurements -->
            <div v-else class="p-3.5 rounded-xl bg-white/70 dark:bg-gray-900/40 border border-dashed border-emerald-200 dark:border-emerald-900/40 text-center py-4">
              <p class="text-xs text-gray-500 dark:text-gray-400">لم يتم إنشاء أي مقايسة أو حصر كميات لهذه الفرصة حتى الآن.</p>
              <button
                type="button"
                @click="createBlankMeasurement"
                class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-600 hover:text-emerald-700 dark:text-emerald-400 cursor-pointer"
              >
                <Plus class="h-3.5 w-3.5" />
                <span>إنشاء أول مقايسة لهذه الفرصة الآن</span>
              </button>
            </div>
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

    <!-- 4. Quick Site Visit Booking Modal -->
    <CrudModal
      :show="showQuickSiteVisitModal"
      :title="'حجز وتحديد موعد معاينة ميدانية للفرصة'"
      :loading="quickSiteVisitLoading"
      :save-text="'حجز المعاينة وتأكيد الموعد'"
      @close="showQuickSiteVisitModal = false"
      @save="submitQuickSiteVisit"
    >
      <div class="space-y-4">
        <div class="p-3 rounded-xl bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-900/40 text-xs text-blue-800 dark:text-blue-300 flex items-center gap-2">
          <MapPin class="h-4 w-4 shrink-0 text-blue-600 dark:text-blue-400" />
          <span>سيتم ربط المعاينة الميدانية مباشرة بالفرصة: <strong>{{ selectedOpportunity?.title }}</strong> وعميلها: <strong>{{ selectedOpportunity?.customer?.name }}</strong></span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              تاريخ المعاينة المجدول <span class="text-rose-500">*</span>
            </label>
            <input
              type="date"
              v-model="quickSiteVisitForm.scheduled_date"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              الوقت المتوقع (اختياري)
            </label>
            <input
              type="text"
              v-model="quickSiteVisitForm.scheduled_time"
              placeholder="مثال: 11:30 صباحاً"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            المهندس الفني المسؤول عن المعاينة
          </label>
          <select
            v-model="quickSiteVisitForm.assigned_to"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
          >
            <option :value="null">اختر المهندس المسؤول...</option>
            <option v-for="u in userOptions" :key="u.value" :value="u.value">
              {{ u.label }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            عنوان الموقع أو ملاحظات الموعد
          </label>
          <textarea
            v-model="quickSiteVisitForm.internal_notes"
            rows="2"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-xs text-gray-900 outline-hidden focus:border-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
            placeholder="أدخل عنوان الموقع بدقة أو أي تعليمات خاصة للمهندس..."
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
import QuickCustomerModal from '../../components/common/QuickCustomerModal.vue';
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
  MapPin,
  Ruler,
  Calendar,
  CheckCircle2,
  Plus,
  ExternalLink,
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

// Quick Site Visit State
const showQuickSiteVisitModal = ref(false);
const quickSiteVisitLoading = ref(false);
const quickSiteVisitForm = reactive({
  scheduled_date: '',
  scheduled_time: '',
  assigned_to: null,
  internal_notes: '',
});

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

const openOpportunityDetails = async (opp) => {
  selectedOpportunity.value = opp;
  showDetailsModal.value = true;
  await refreshSelectedOpportunity(opp.id);
};

const refreshSelectedOpportunity = async (oppId) => {
  try {
    const res = await api.get(`/opportunities/${oppId}`);
    if (res.data?.success && res.data?.data) {
      selectedOpportunity.value = res.data.data;
    }
  } catch (e) {
    console.error('Failed to refresh opportunity details:', e);
  }
};

const openQuickSiteVisitModal = (opp) => {
  quickSiteVisitForm.scheduled_date = new Date().toISOString().split('T')[0];
  quickSiteVisitForm.scheduled_time = '';
  quickSiteVisitForm.assigned_to = opp.assigned_to || null;
  quickSiteVisitForm.internal_notes = '';
  showQuickSiteVisitModal.value = true;
};

const submitQuickSiteVisit = async () => {
  if (!selectedOpportunity.value) return;
  if (!quickSiteVisitForm.scheduled_date) {
    notify.error('يرجى تحديد تاريخ المعاينة المجدول');
    return;
  }

  quickSiteVisitLoading.value = true;
  try {
    const payload = {
      customer_id: selectedOpportunity.value.customer_id,
      opportunity_id: selectedOpportunity.value.id,
      lead_id: selectedOpportunity.value.lead_id || null,
      status: 'Scheduled',
      scheduled_date: quickSiteVisitForm.scheduled_date,
      scheduled_time: quickSiteVisitForm.scheduled_time || null,
      assigned_to: quickSiteVisitForm.assigned_to || null,
      internal_notes: quickSiteVisitForm.internal_notes || null,
    };

    const res = await api.post('/site-visits', payload);
    if (res.data?.success) {
      notify.success('تم حجز وتأكيد موعد المعاينة الميدانية بنجاح');
      showQuickSiteVisitModal.value = false;
      await refreshSelectedOpportunity(selectedOpportunity.value.id);
      crudRef.value?.loadData();
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'حدث خطأ أثناء حجز المعاينة');
  } finally {
    quickSiteVisitLoading.value = false;
  }
};

const hasCompletedSiteVisit = computed(() => {
  if (!selectedOpportunity.value?.site_visits) return false;
  return selectedOpportunity.value.site_visits.some((v) => v.status === 'Completed');
});

const completedSiteVisit = computed(() => {
  if (!selectedOpportunity.value?.site_visits) return null;
  return selectedOpportunity.value.site_visits.find((v) => v.status === 'Completed');
});

const createMeasurementFromCompletedVisit = async () => {
  if (!completedSiteVisit.value) return;
  try {
    const res = await api.post(`/measurements/import-from-site-visit/${completedSiteVisit.value.id}`);
    if (res.data?.success) {
      notify.success('تم استيراد غرف وفراغات المعاينة وإنشاء مسودة المقايسة بنجاح');
      await refreshSelectedOpportunity(selectedOpportunity.value.id);
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'حدث خطأ أثناء استيراد المعاينة');
  }
};

const createBlankMeasurement = async () => {
  if (!selectedOpportunity.value) return;
  try {
    const res = await api.post(`/measurements/from-opportunity/${selectedOpportunity.value.id}`);
    if (res.data?.success) {
      notify.success('تم إنشاء مسودة مقايسة جديدة للفرصة بنجاح');
      await refreshSelectedOpportunity(selectedOpportunity.value.id);
    }
  } catch (err) {
    notify.error(err.response?.data?.message || 'حدث خطأ أثناء إنشاء المقايسة');
  }
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
    showQuickCustomerModal.value = true;
  }
};

const onQuickCustomerCreated = (newCustomer) => {
  const optionLabel = newCustomer.company_name
    ? `${newCustomer.name} (${newCustomer.company_name})`
    : newCustomer.name;

  const newOption = {
    value: newCustomer.id,
    label: optionLabel,
  };

  // 1. Add to local customerOptions ref
  customerOptions.value.unshift(newOption);

  // 2. Append option in CrudIndex schema
  crudRef.value?.appendOption('customer_id', newOption);

  // 3. Auto-select in form
  crudRef.value?.setFieldValue('customer_id', newCustomer.id);
  if (crudRef.value?.formData) {
    crudRef.value.formData.customer_id = newCustomer.id;
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

const getVisitStatusLabel = (st) => {
  const map = {
    Requested: 'مطلوبة',
    Scheduled: 'مجدولة',
    Completed: 'مكتملة',
    Cancelled: 'ملغاة',
    Rescheduled: 'معاد جدولتها',
  };
  return map[st] || st;
};

const getVisitStatusClass = (st) => {
  const map = {
    Requested: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    Scheduled: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    Completed: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    Cancelled: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    Rescheduled: 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
  };
  return map[st] || 'bg-gray-100 text-gray-700';
};

const getMeasurementStatusLabel = (st) => {
  const map = {
    Draft: 'مسودة',
    'Under Review': 'قيد المراجعة',
    Approved: 'معتمدة',
    Rejected: 'مرفوضة',
    Superseded: 'مستبدلة بإصدار أحدث',
  };
  return map[st] || st;
};

const getMeasurementStatusClass = (st) => {
  const map = {
    Draft: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    'Under Review': 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    Approved: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    Rejected: 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
    Superseded: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
  };
  return map[st] || 'bg-gray-100 text-gray-700';
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
