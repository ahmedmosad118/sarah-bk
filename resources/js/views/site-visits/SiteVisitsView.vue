<template>
  <div class="space-y-6">
    <CrudIndex
      ref="crudRef"
      endpoint="/site-visits"
      :title="$t('siteVisits.title')"
      :custom-columns="columns"
      :extra-params="filterParams"
      :related-options="relatedOptions"
      :allow-create="false"
      @loaded="onDataLoaded"
    >
      <!-- 1. Top Statistics Summary Cards -->
      <template #top-stats>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6 mb-6">
          <!-- Total Site Visits -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-blue-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.totalVisits') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                <MapPin class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-gray-900 dark:text-white font-mono">{{ stats.total || 0 }}</span>
            </div>
          </div>

          <!-- Scheduled Today -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-amber-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.scheduledToday') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400">
                <Calendar class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">{{ stats.scheduled_today || 0 }}</span>
            </div>
          </div>

          <!-- Scheduled Total -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-cyan-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.scheduledVisits') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-400">
                <Clock class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-cyan-600 dark:text-cyan-400 font-mono">{{ stats.scheduled || 0 }}</span>
            </div>
          </div>

          <!-- Completed Visits -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-emerald-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.completedVisits') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400">
                <CheckCircle2 class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">{{ stats.completed || 0 }}</span>
            </div>
          </div>

          <!-- Rescheduled / Requests -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-purple-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.rescheduledVisits') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                <RotateCcw class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-purple-600 dark:text-purple-400 font-mono">{{ stats.rescheduled || 0 }}</span>
            </div>
          </div>

          <!-- Overdue / Cancelled -->
          <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900 transition-all hover:border-rose-500/30">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('siteVisits.overdueVisits') }}</span>
              <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400">
                <AlertCircle class="h-4 w-4" />
              </div>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
              <span class="text-2xl font-black text-rose-600 dark:text-rose-400 font-mono">{{ stats.overdue || 0 }}</span>
            </div>
          </div>
        </div>
      </template>

      <!-- 2. Segmented Status Filters + Primary Create Button -->
      <template #filters>
        <div class="flex flex-wrap items-center justify-between gap-3 w-full">
          <!-- Status Filters -->
          <div class="inline-flex p-1 rounded-xl bg-gray-100 dark:bg-gray-900 border border-gray-200/80 dark:border-gray-800 text-xs font-bold">
            <button
              type="button"
              @click="setStatusFilter('')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="!filterParams.status ? 'bg-white text-gray-900 shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterAll') }}</span>
              <span class="ms-1 px-1.5 py-0.5 rounded-md text-[10px] bg-gray-100 dark:bg-gray-800 font-mono">{{ stats.total || 0 }}</span>
            </button>

            <button
              type="button"
              @click="setStatusFilter('Scheduled')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Scheduled' ? 'bg-white text-blue-600 shadow-xs dark:bg-gray-800 dark:text-blue-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterScheduled') }}</span>
              <span class="ms-1 px-1.5 py-0.5 rounded-md text-[10px] bg-blue-50 dark:bg-blue-900/30 font-mono">{{ stats.scheduled || 0 }}</span>
            </button>

            <button
              type="button"
              @click="setStatusFilter('Completed')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Completed' ? 'bg-white text-emerald-600 shadow-xs dark:bg-gray-800 dark:text-emerald-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterCompleted') }}</span>
              <span class="ms-1 px-1.5 py-0.5 rounded-md text-[10px] bg-emerald-50 dark:bg-emerald-900/30 font-mono">{{ stats.completed || 0 }}</span>
            </button>

            <button
              type="button"
              @click="setStatusFilter('Requested')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Requested' ? 'bg-white text-amber-600 shadow-xs dark:bg-gray-800 dark:text-amber-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterRequested') }}</span>
            </button>

            <button
              type="button"
              @click="setStatusFilter('Rescheduled')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Rescheduled' ? 'bg-white text-purple-600 shadow-xs dark:bg-gray-800 dark:text-purple-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterRescheduled') }}</span>
            </button>

            <button
              type="button"
              @click="setStatusFilter('Cancelled')"
              class="px-2.5 py-1.5 rounded-lg transition-all cursor-pointer"
              :class="filterParams.status === 'Cancelled' ? 'bg-white text-rose-600 shadow-xs dark:bg-gray-800 dark:text-rose-400' : 'text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white'"
            >
              <span>{{ $t('siteVisits.filterCancelled') }}</span>
            </button>
          </div>

          <!-- Primary Create Button -->
          <button
            type="button"
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-[#00C896] hover:bg-[#00A87E] text-white text-xs font-black shadow-xs transition-all cursor-pointer"
          >
            <Plus class="h-4 w-4" />
            <span>{{ $t('siteVisits.addSiteVisit') }}</span>
          </button>
        </div>
      </template>

      <!-- 3. Custom Visit ID / Ref Column -->
      <template #col-id="{ item }">
        <div class="flex items-center gap-1.5">
          <span class="font-mono font-bold text-xs text-gray-900 dark:text-white">#{{ item.id }}</span>
        </div>
      </template>

      <!-- 4. Custom Customer Column -->
      <template #col-customer="{ item }">
        <div v-if="item.customer" class="flex flex-col">
          <span class="font-bold text-gray-900 dark:text-white">{{ item.customer.name }}</span>
          <span v-if="item.customer.company_name" class="text-[11px] text-gray-500 dark:text-gray-400 font-medium">{{ item.customer.company_name }}</span>
          <span v-if="item.customer.phone" class="text-[10px] text-gray-400 font-mono" dir="ltr">{{ item.customer.phone }}</span>
        </div>
        <span v-else class="text-gray-400">—</span>
      </template>

      <!-- 5. Custom Opportunity & Source Column -->
      <template #col-opportunity="{ item }">
        <div v-if="item.opportunity" class="flex items-center gap-1.5">
          <Target class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400 shrink-0" />
          <span class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate max-w-[180px]" :title="item.opportunity.title">
            {{ item.opportunity.title }}
          </span>
        </div>
        <div v-else-if="item.lead" class="flex items-center gap-1.5">
          <Sparkles class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400 shrink-0" />
          <span class="text-xs font-semibold text-gray-600 dark:text-gray-300 truncate max-w-[180px]">
            {{ item.lead.title }}
          </span>
        </div>
        <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
          معاينة مباشرة
        </span>
      </template>

      <!-- 6. Custom Status Column -->
      <template #col-status="{ item }">
        <span
          class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold"
          :class="getStatusClass(item.status)"
        >
          <span class="h-1.5 w-1.5 rounded-full" :class="getStatusDotClass(item.status)"></span>
          {{ getStatusLabel(item.status) }}
        </span>
      </template>

      <!-- 7. Custom Scheduled Date Column -->
      <template #col-scheduled_date="{ item }">
        <div v-if="item.scheduled_date" class="flex flex-col text-xs font-mono font-bold text-gray-900 dark:text-white">
          <span>{{ formatDate(item.scheduled_date) }}</span>
          <span v-if="item.scheduled_time" class="text-[10px] text-gray-500 font-sans font-medium">{{ item.scheduled_time }}</span>
        </div>
        <div v-else-if="item.visit_date" class="flex flex-col text-xs font-mono font-bold text-emerald-600 dark:text-emerald-400">
          <span>{{ formatDate(item.visit_date) }}</span>
          <span class="text-[10px] text-emerald-500 font-sans font-medium">(تم التنفيذ)</span>
        </div>
        <span v-else class="text-xs text-gray-400 italic">غير محدد</span>
      </template>

      <!-- 8. Custom Assigned User Column -->
      <template #col-assigned_to="{ item }">
        <div v-if="item.assigned_user" class="flex items-center gap-1.5">
          <div class="h-6 w-6 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 flex items-center justify-center font-black text-[10px]">
            {{ item.assigned_user.name.charAt(0) }}
          </div>
          <span class="text-xs font-bold text-gray-900 dark:text-white">{{ item.assigned_user.name }}</span>
        </div>
        <span v-else class="text-xs text-gray-400 italic font-medium">غير مسند</span>
      </template>

      <!-- 9. Custom Photos Column -->
      <template #col-photos="{ item }">
        <div v-if="item.media && item.media.length > 0" class="flex items-center gap-1">
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 font-mono">
            <Camera class="h-3 w-3" />
            {{ item.media.length }}
          </span>
        </div>
        <span v-else class="text-xs text-gray-400">—</span>
      </template>

      <!-- 10. Custom Actions Column -->
      <template #actions="{ item }">
        <div class="flex items-center justify-center gap-1">
          <!-- View Details -->
          <button
            type="button"
            @click="openVisitDetails(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-[#00C896] hover:bg-[#00C896]/10 dark:hover:bg-[#00C896]/20 transition-colors cursor-pointer"
            :title="$t('siteVisits.visitDetails')"
          >
            <Eye class="h-3.5 w-3.5" />
          </button>

          <!-- Schedule / Reschedule -->
          <button
            type="button"
            @click="openScheduleModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors cursor-pointer"
            :title="$t('siteVisits.scheduleAction')"
          >
            <Calendar class="h-3.5 w-3.5" />
          </button>

          <!-- Complete -->
          <button
            v-if="item.status !== 'Completed'"
            type="button"
            @click="openCompleteModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 dark:hover:text-emerald-400 transition-colors cursor-pointer"
            :title="$t('siteVisits.completeAction')"
          >
            <CheckCircle2 class="h-3.5 w-3.5" />
          </button>

          <!-- Edit -->
          <button
            type="button"
            @click="openEditModal(item)"
            class="p-1.5 rounded-lg text-gray-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/30 dark:hover:text-amber-400 transition-colors cursor-pointer"
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

    <!-- ======================================================== -->
    <!-- 1. Custom Create / Edit Site Visit Modal (Clean UX)      -->
    <!-- ======================================================== -->
    <!-- ======================================================== -->
    <!-- 1. Custom Create / Edit Site Visit Modal (Clean UX)      -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showCreateEditModal"
      :title="isEditMode ? 'تعديل بيانات المعاينة' : $t('siteVisits.addSiteVisit')"
      :loading="savingVisit"
      :size="'5xl'"
      :save-text="isEditMode ? 'حفظ التعديلات' : 'تسجيل وحفظ المعاينة'"
      @close="showCreateEditModal = false"
      @save="submitVisitForm"
    >
      <div class="space-y-5">
        <!-- Section 1: Customer & Associated Opportunity -->
        <div class="space-y-3 p-4 rounded-2xl bg-gray-50/80 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800">
          <div class="flex items-center justify-between">
            <label class="text-xs font-black text-gray-800 dark:text-gray-200">
              {{ $t('siteVisits.customer') }} <span class="text-rose-500">*</span>
            </label>
            <button
              type="button"
              @click="showQuickCustomerModal = true"
              class="text-[11px] font-bold text-[#00C896] hover:underline flex items-center gap-1 cursor-pointer"
            >
              <Plus class="h-3 w-3" />
              <span>+ إضافة عميل جديد</span>
            </button>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <select
                v-model="visitForm.customer_id"
                required
                class="w-full rounded-xl border bg-white py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900 dark:text-white"
                :class="visitErrors.customer_id ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
              >
                <option :value="null">{{ $t('siteVisits.selectCustomer') }}</option>
                <option v-for="c in customersList" :key="c.id" :value="c.id">
                  {{ c.name }} {{ c.company_name ? `(${c.company_name})` : '' }}
                </option>
              </select>
              <p v-if="visitErrors.customer_id" class="mt-1 text-[11px] text-rose-500 font-bold">
                {{ visitErrors.customer_id[0] }}
              </p>
            </div>

            <div>
              <select
                v-model="visitForm.opportunity_id"
                class="w-full rounded-xl border border-gray-200 bg-white py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              >
                <option :value="null">{{ $t('siteVisits.directVisitNotice') }}</option>
                <option v-for="opp in filteredOpportunities" :key="opp.id" :value="opp.id">
                  {{ opp.title }} (فرصة #{{ opp.id }})
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- Section 2: Visit Mode / Status (Interactive 3-Cards Selector) -->
        <div class="space-y-2">
          <label class="block text-xs font-black text-gray-800 dark:text-gray-200">
            {{ $t('siteVisits.visitType') }}
          </label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <!-- Card 1: Scheduled Future Visit -->
            <div
              @click="setVisitMode('Scheduled')"
              class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5"
              :class="visitForm.status === 'Scheduled' ? 'border-blue-500 bg-blue-50/60 dark:bg-blue-950/30 text-blue-900 dark:text-blue-200 ring-2 ring-blue-500/20 shadow-xs' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-black flex items-center gap-1.5">
                  <Calendar class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                  {{ $t('siteVisits.typeScheduled') }}
                </span>
                <span v-if="visitForm.status === 'Scheduled'" class="h-2 w-2 rounded-full bg-blue-600"></span>
              </div>
              <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">
                {{ $t('siteVisits.typeScheduledDesc') }}
              </p>
            </div>

            <!-- Card 2: Immediate / Completed Visit -->
            <div
              @click="setVisitMode('Completed')"
              class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5"
              :class="visitForm.status === 'Completed' ? 'border-emerald-500 bg-emerald-50/60 dark:bg-emerald-950/30 text-emerald-900 dark:text-emerald-200 ring-2 ring-emerald-500/20 shadow-xs' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-black flex items-center gap-1.5">
                  <CheckCircle2 class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                  {{ $t('siteVisits.typeImmediate') }}
                </span>
                <span v-if="visitForm.status === 'Completed'" class="h-2 w-2 rounded-full bg-emerald-600"></span>
              </div>
              <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">
                {{ $t('siteVisits.typeImmediateDesc') }}
              </p>
            </div>

            <!-- Card 3: Requested Visit -->
            <div
              @click="setVisitMode('Requested')"
              class="p-3.5 rounded-2xl border transition-all cursor-pointer space-y-1.5"
              :class="visitForm.status === 'Requested' ? 'border-amber-500 bg-amber-50/60 dark:bg-amber-950/30 text-amber-900 dark:text-amber-200 ring-2 ring-amber-500/20 shadow-xs' : 'border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:border-gray-300'"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-black flex items-center gap-1.5">
                  <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                  {{ $t('siteVisits.typeRequested') }}
                </span>
                <span v-if="visitForm.status === 'Requested'" class="h-2 w-2 rounded-full bg-amber-600"></span>
              </div>
              <p class="text-[10px] text-gray-500 dark:text-gray-400 leading-tight">
                {{ $t('siteVisits.typeRequestedDesc') }}
              </p>
            </div>
          </div>
        </div>

        <!-- Section 3: Date, Time & Assignee Inputs (Dynamic per mode) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
          <!-- When Scheduled: Scheduled Date & Time -->
          <div v-if="visitForm.status === 'Scheduled' || visitForm.status === 'Rescheduled'">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.scheduledDate') }} <span class="text-rose-500">*</span>
            </label>
            <input
              type="date"
              v-model="visitForm.scheduled_date"
              class="w-full rounded-xl border bg-gray-50 py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900 dark:text-white"
              :class="visitErrors.scheduled_date ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
            />
            <p v-if="visitErrors.scheduled_date" class="mt-1 text-[11px] text-rose-500 font-bold">
              {{ visitErrors.scheduled_date[0] }}
            </p>
          </div>

          <div v-if="visitForm.status === 'Scheduled' || visitForm.status === 'Rescheduled'">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.scheduledTime') }}
            </label>
            <input
              type="time"
              v-model="visitForm.scheduled_time"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            />
          </div>

          <!-- When Completed: Actual Visit Date -->
          <div v-if="visitForm.status === 'Completed'">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.visitDate') }} <span class="text-rose-500">*</span>
            </label>
            <input
              type="date"
              v-model="visitForm.visit_date"
              required
              class="w-full rounded-xl border bg-gray-50 py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900 dark:text-white"
              :class="visitErrors.visit_date ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
            />
            <p v-if="visitErrors.visit_date" class="mt-1 text-[11px] text-rose-500 font-bold">
              {{ visitErrors.visit_date[0] }}
            </p>
          </div>

          <!-- Assigned Engineer -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.assignedTo') }}
            </label>
            <select
              v-model="visitForm.assigned_to"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3 text-xs font-semibold text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
            >
              <option :value="null">{{ $t('siteVisits.selectAssignee') }}</option>
              <option v-for="u in usersList" :key="u.id" :value="u.id">
                {{ u.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Section 4: Technical Assessment & Internal Notes -->
        <div class="space-y-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.generalAssessment') }}
            </label>
            <textarea
              v-model="visitForm.general_assessment"
              rows="2"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-xs text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              :placeholder="$t('siteVisits.generalAssessmentPlaceholder')"
            ></textarea>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">
              {{ $t('siteVisits.internalNotes') }}
            </label>
            <textarea
              v-model="visitForm.internal_notes"
              rows="2"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-xs text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900 dark:text-white"
              :placeholder="$t('siteVisits.internalNotesPlaceholder')"
            ></textarea>
          </div>
        </div>

        <!-- Section 5: Site Photos & Blueprints Uploader -->
        <div class="space-y-2 p-4 rounded-2xl border border-dashed border-gray-300 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-900/30">
          <div class="flex items-center justify-between">
            <label class="text-xs font-black text-gray-800 dark:text-gray-200 flex items-center gap-1.5">
              <Camera class="h-4 w-4 text-[#00C896]" />
              <span>{{ $t('siteVisits.sitePhotos') }}</span>
              <span v-if="selectedPhotos.length > 0" class="ms-2 px-2 py-0.5 rounded-md bg-emerald-100 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-[10px] font-bold">
                تم اختيار ({{ selectedPhotos.length }}) صور للرفع
              </span>
            </label>
            <span class="text-[10px] text-gray-400 font-semibold">JPG, PNG, WebP (بحد أقصى 10MB)</span>
          </div>

          <div class="relative flex flex-col items-center justify-center p-5 border border-dashed border-gray-200 dark:border-gray-800 rounded-2xl bg-white dark:bg-gray-900 text-center hover:bg-gray-50 dark:hover:bg-gray-800/40 transition-colors">
            <input
              type="file"
              multiple
              accept="image/jpeg,image/png,image/webp,image/heic"
              @change="onPhotoFilesSelected"
              class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
            />
            <UploadCloud class="h-7 w-7 text-emerald-500 mb-1" />
            <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('siteVisits.photosUploadHint') }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5">يمكنك اختيار أو سحب عدة صور معاً وسيتم حفظها وربطها بالمعاينة فوراً</p>
          </div>

          <!-- Preview of Selected New Photos -->
          <div v-if="selectedPhotos.length > 0" class="flex flex-wrap gap-2.5 pt-2">
            <div
              v-for="(photo, idx) in selectedPhotos"
              :key="idx"
              class="relative group h-16 w-16 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-xs"
            >
              <img :src="photo.preview" class="h-full w-full object-cover" />
              <button
                type="button"
                @click="removeSelectedPhoto(idx)"
                class="absolute top-1 right-1 h-5 w-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs opacity-90 hover:opacity-100 cursor-pointer shadow-sm"
              >
                ✕
              </button>
            </div>
          </div>
        </div>

        <!-- Section 6: Inspected Rooms Builder (for Completed visits) -->
        <div v-if="visitForm.status === 'Completed'" class="space-y-3 p-3.5 rounded-2xl bg-purple-50/40 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/30">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="text-xs font-black text-purple-900 dark:text-purple-300 flex items-center gap-1.5">
                <LayoutGrid class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
                <span>{{ $t('siteVisits.rooms') }}</span>
              </h4>
              <p class="text-[10px] text-gray-500 dark:text-gray-400">{{ $t('siteVisits.roomsDesc') }}</p>
            </div>
            <button
              type="button"
              @click="addRoomRow"
              class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-[11px] font-bold shadow-xs transition-all cursor-pointer"
            >
              {{ $t('siteVisits.addRoom') }}
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="(room, index) in visitForm.rooms"
              :key="index"
              class="grid grid-cols-12 gap-2 items-center bg-white dark:bg-gray-900 p-2 rounded-xl border border-purple-100 dark:border-purple-900/40"
            >
              <div class="col-span-5">
                <input
                  type="text"
                  v-model="room.room_name"
                  required
                  :placeholder="$t('siteVisits.roomNamePlaceholder')"
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>

              <div class="col-span-3">
                <input
                  type="number"
                  step="0.01"
                  v-model="room.estimated_area"
                  placeholder="المساحة م²"
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-mono font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>

              <div class="col-span-3">
                <input
                  type="text"
                  v-model="room.notes"
                  placeholder="ملاحظات..."
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>

              <div class="col-span-1 flex justify-center">
                <button
                  type="button"
                  @click="removeRoomRow(index)"
                  class="p-1 rounded-md text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors cursor-pointer"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CrudModal>

    <!-- ======================================================== -->
    <!-- 2. Site Visit Details & Dynamic Commercial Journey Modal -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showDetailsModal"
      :title="$t('siteVisits.visitDetails')"
      :size="'5xl'"
      :show-save-button="false"
      :cancel-text="'إغلاق'"
      @close="showDetailsModal = false"
    >
      <div v-if="selectedVisit" class="space-y-5">
        <!-- Visual Commercial Lifecycle Timeline (Dynamic Based on actual lineage!) -->
        <div class="p-4 rounded-2xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
              <Sparkles class="h-3.5 w-3.5 text-[#00C896]" />
              <span>مسار دورة العمل والمشروع (Business Journey)</span>
            </span>
            <span class="text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 px-2 py-0.5 rounded-md">
              المرحلة الحالية: معاينة الموقع
            </span>
          </div>

          <!-- Dynamic Horizontal Stepper -->
          <div class="flex items-center justify-between overflow-x-auto py-2 px-1 text-[11px] gap-1.5 scrollbar-thin">
            <!-- 1. Customer (Always Exists) -->
            <div class="flex flex-col items-center min-w-[65px] text-center">
              <div class="h-6 w-6 rounded-full bg-emerald-500 text-white flex items-center justify-center font-black text-xs shadow-xs mb-1">✓</div>
              <span class="font-bold text-gray-700 dark:text-gray-200 text-[10px]">العميل</span>
            </div>
            <div class="h-0.5 w-5 bg-emerald-500 shrink-0"></div>

            <!-- 2. Lead (Checked if exists, else Neutral Skipped) -->
            <div class="flex flex-col items-center min-w-[75px] text-center">
              <div
                v-if="selectedVisit.lead_id"
                class="h-6 w-6 rounded-full bg-emerald-500 text-white flex items-center justify-center font-black text-xs shadow-xs mb-1"
              >
                ✓
              </div>
              <div
                v-else
                class="h-6 w-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 flex items-center justify-center font-medium text-[10px] mb-1"
                title="مسار مباشر - تم تجاوز مرحلة الليد"
              >
                —
              </div>
              <span class="text-[10px]" :class="selectedVisit.lead_id ? 'font-bold text-gray-700 dark:text-gray-200' : 'text-gray-400'">
                {{ selectedVisit.lead_id ? 'طلب العميل' : 'مسار مباشر' }}
              </span>
            </div>
            <div class="h-0.5 w-5" :class="selectedVisit.lead_id ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'" shrink-0></div>

            <!-- 3. Opportunity (Checked if exists, else Neutral Direct Visit) -->
            <div class="flex flex-col items-center min-w-[75px] text-center">
              <div
                v-if="selectedVisit.opportunity_id"
                class="h-6 w-6 rounded-full bg-emerald-500 text-white flex items-center justify-center font-black text-xs shadow-xs mb-1"
              >
                ✓
              </div>
              <div
                v-else
                class="h-6 w-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 flex items-center justify-center font-medium text-[10px] mb-1"
                title="معاينة مباشرة بدون فرصة مسجلة مسبقاً"
              >
                —
              </div>
              <span class="text-[10px]" :class="selectedVisit.opportunity_id ? 'font-bold text-gray-700 dark:text-gray-200' : 'text-gray-400'">
                {{ selectedVisit.opportunity_id ? 'الفرصة' : 'معاينة مباشرة' }}
              </span>
            </div>
            <div class="h-0.5 w-5" :class="selectedVisit.opportunity_id ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-700'" shrink-0></div>

            <!-- 4. Site Visit (Active Step) -->
            <div class="flex flex-col items-center min-w-[80px] text-center">
              <div
                class="h-6 w-6 rounded-full text-white flex items-center justify-center font-black text-xs shadow-xs mb-1"
                :class="selectedVisit.status === 'Completed' ? 'bg-emerald-500 ring-4 ring-emerald-100 dark:ring-emerald-900/40' : 'bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-900/40'"
              >
                {{ selectedVisit.status === 'Completed' ? '✓' : '●' }}
              </div>
              <span
                class="text-[10px] font-black"
                :class="selectedVisit.status === 'Completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-blue-600 dark:text-blue-400'"
              >
                المعاينة ({{ getStatusLabel(selectedVisit.status) }})
              </span>
            </div>
            <div class="h-0.5 w-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

            <!-- 5. Measurement / BOQ (Next) -->
            <div class="flex flex-col items-center min-w-[70px] text-center opacity-70">
              <div class="h-6 w-6 rounded-full border-2 border-dashed border-purple-400 text-purple-500 flex items-center justify-center font-bold text-xs mb-1">5</div>
              <span class="font-medium text-gray-500 dark:text-gray-400 text-[10px]">المقايسة و BOQ</span>
            </div>
            <div class="h-0.5 w-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

            <!-- 6. Quotation -->
            <div class="flex flex-col items-center min-w-[65px] text-center opacity-40">
              <div class="h-6 w-6 rounded-full border border-gray-300 dark:border-gray-700 text-gray-400 flex items-center justify-center text-xs mb-1">6</div>
              <span class="font-medium text-gray-400 text-[10px]">عرض السعر</span>
            </div>
            <div class="h-0.5 w-5 bg-gray-200 dark:bg-gray-700 shrink-0"></div>

            <!-- 7. Contract & Project -->
            <div class="flex flex-col items-center min-w-[70px] text-center opacity-40">
              <div class="h-6 w-6 rounded-full border border-gray-300 dark:border-gray-700 text-gray-400 flex items-center justify-center text-xs mb-1">7</div>
              <span class="font-medium text-gray-400 text-[10px]">العقد والمشروع</span>
            </div>
          </div>
        </div>

        <!-- Site Visit Header & Action Buttons -->
        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800 space-y-3">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono font-bold text-gray-400">#{{ selectedVisit.id }}</span>
                <h3 class="text-base font-black text-gray-900 dark:text-white">
                  معاينة موقع: {{ selectedVisit.customer?.name }}
                </h3>
              </div>
              <p v-if="selectedVisit.opportunity" class="text-xs font-semibold text-purple-600 dark:text-purple-400 mt-0.5">
                الفرصة المرتبطة: {{ selectedVisit.opportunity.title }}
              </p>
            </div>
            <div class="flex items-center gap-2">
              <span
                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold"
                :class="getStatusClass(selectedVisit.status)"
              >
                <span class="h-2 w-2 rounded-full" :class="getStatusDotClass(selectedVisit.status)"></span>
                {{ getStatusLabel(selectedVisit.status) }}
              </span>
            </div>
          </div>

          <!-- Action Buttons Bar -->
          <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-gray-200/60 dark:border-gray-700/60">
            <button
              v-if="selectedVisit.status !== 'Completed'"
              type="button"
              @click="openCompleteModal(selectedVisit)"
              class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
            >
              <CheckCircle2 class="h-3.5 w-3.5" />
              <span>{{ $t('siteVisits.completeAction') }}</span>
            </button>

            <button
              type="button"
              @click="openScheduleModal(selectedVisit)"
              class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-xs transition-all cursor-pointer"
            >
              <Calendar class="h-3.5 w-3.5" />
              <span>{{ $t('siteVisits.scheduleAction') }}</span>
            </button>

            <button
              type="button"
              @click="openPhotosModal(selectedVisit)"
              class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300 text-xs font-bold transition-all cursor-pointer"
            >
              <Camera class="h-3.5 w-3.5" />
              <span>{{ $t('siteVisits.uploadPhotosAction') }}</span>
            </button>

            <button
              v-if="selectedVisit.status !== 'Cancelled'"
              type="button"
              @click="cancelVisitAction(selectedVisit)"
              class="flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-900/30 dark:text-rose-300 text-xs font-bold transition-all cursor-pointer"
            >
              <XCircle class="h-3.5 w-3.5" />
              <span>{{ $t('siteVisits.cancelAction') }}</span>
            </button>
          </div>
        </div>

        <!-- Client Profile Card -->
        <div v-if="selectedVisit.customer" class="p-4 rounded-2xl border border-gray-100 dark:border-gray-800 space-y-3 bg-white dark:bg-gray-900">
          <h4 class="text-xs font-black uppercase tracking-wider text-gray-400 flex items-center gap-1.5">
            <User class="h-3.5 w-3.5 text-[#00C896]" />
            <span>{{ $t('siteVisits.clientInfo') }}</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('customers.name') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedVisit.customer.name }}</p>
            </div>
            <div v-if="selectedVisit.customer.company_name">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.companyName') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">{{ selectedVisit.customer.company_name }}</p>
            </div>
            <div v-if="selectedVisit.customer.phone" class="flex items-center gap-2">
              <div>
                <p class="text-gray-400 text-[11px]">{{ $t('customers.phone') }}</p>
                <p class="font-mono font-bold text-gray-900 dark:text-white" dir="ltr">{{ selectedVisit.customer.phone }}</p>
              </div>
              <a
                :href="`tel:${selectedVisit.customer.phone}`"
                class="p-1.5 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400"
                title="اتصال"
              >
                <Phone class="h-3.5 w-3.5" />
              </a>
              <a
                v-if="selectedVisit.customer.whatsapp || selectedVisit.customer.phone"
                :href="`https://wa.me/${(selectedVisit.customer.whatsapp || selectedVisit.customer.phone).replace(/[^0-9]/g, '')}`"
                target="_blank"
                class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-400"
                title="واتساب"
              >
                <MessageSquare class="h-3.5 w-3.5" />
              </a>
            </div>
            <div v-if="selectedVisit.customer.email">
              <p class="text-gray-400 text-[11px]">{{ $t('customers.email') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">{{ selectedVisit.customer.email }}</p>
            </div>
          </div>
        </div>

        <!-- Visit Schedule & Assigned Engineer Card -->
        <div class="p-4 rounded-2xl border border-blue-100 dark:border-blue-900/40 bg-blue-50/30 dark:bg-blue-950/10 space-y-3">
          <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
            <Clock class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
            <span>بيانات الموعد والمهندس المكلف</span>
          </h4>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('siteVisits.scheduledDate') }}</p>
              <p class="font-mono font-bold text-gray-900 dark:text-white">
                {{ formatDate(selectedVisit.scheduled_date) || 'لم يحدد' }}
                <span v-if="selectedVisit.scheduled_time" class="text-gray-500 font-sans text-[11px]">({{ selectedVisit.scheduled_time }})</span>
              </p>
            </div>

            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('siteVisits.visitDate') }}</p>
              <p class="font-mono font-bold text-emerald-600 dark:text-emerald-400">
                {{ formatDate(selectedVisit.visit_date) || '—' }}
              </p>
            </div>

            <div>
              <p class="text-gray-400 text-[11px]">{{ $t('siteVisits.assignedTo') }}</p>
              <p class="font-bold text-gray-900 dark:text-white">
                {{ selectedVisit.assigned_user ? selectedVisit.assigned_user.name : 'غير مسند' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Technical Assessment & Notes -->
        <div v-if="selectedVisit.general_assessment" class="space-y-1">
          <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('siteVisits.generalAssessment') }}</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 whitespace-pre-line">
            {{ selectedVisit.general_assessment }}
          </p>
        </div>

        <div v-if="selectedVisit.internal_notes" class="space-y-1">
          <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('siteVisits.internalNotes') }}</h4>
          <p class="text-xs text-gray-600 dark:text-gray-400 p-3 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-gray-100 dark:border-gray-800 whitespace-pre-line">
            {{ selectedVisit.internal_notes }}
          </p>
        </div>

        <!-- Inspected Rooms Breakdown Table -->
        <div class="space-y-2">
          <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
            <LayoutGrid class="h-3.5 w-3.5 text-purple-600 dark:text-purple-400" />
            <span>{{ $t('siteVisits.rooms') }} ({{ selectedVisit.rooms?.length || 0 }})</span>
          </h4>

          <div v-if="selectedVisit.rooms && selectedVisit.rooms.length > 0" class="divide-y divide-gray-100 dark:divide-gray-800 rounded-2xl border border-gray-100 dark:border-gray-800 overflow-hidden bg-white dark:bg-gray-900">
            <div
              v-for="room in selectedVisit.rooms"
              :key="room.id"
              class="p-3 flex items-center justify-between text-xs"
            >
              <div>
                <span class="font-bold text-gray-900 dark:text-white">{{ room.room_name }}</span>
                <p v-if="room.notes" class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">{{ room.notes }}</p>
              </div>
              <div v-if="room.estimated_area" class="font-mono font-bold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/20 px-2 py-1 rounded-lg">
                {{ Number(room.estimated_area).toLocaleString() }} م²
              </div>
            </div>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-3 rounded-xl bg-gray-50 dark:bg-gray-800/30 text-center">
            {{ $t('siteVisits.noRoomsYet') }}
          </p>
        </div>

        <!-- Site Photos Gallery -->
        <div class="space-y-2">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black text-gray-900 dark:text-white flex items-center gap-1.5">
              <Camera class="h-3.5 w-3.5 text-[#00C896]" />
              <span>{{ $t('siteVisits.sitePhotos') }} ({{ selectedVisit.media?.length || 0 }})</span>
            </h4>
            <button
              type="button"
              @click="openPhotosModal(selectedVisit)"
              class="text-[11px] font-bold text-blue-600 dark:text-blue-400 hover:underline cursor-pointer"
            >
              + رفع صور إضافية
            </button>
          </div>

          <div v-if="selectedVisit.media && selectedVisit.media.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-2.5">
            <a
              v-for="img in selectedVisit.media"
              :key="img.id"
              :href="img.original_url || img.preview_url"
              target="_blank"
              class="relative group rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 aspect-square bg-gray-100 dark:bg-gray-800 shadow-2xs"
            >
              <img :src="img.original_url || img.preview_url" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
              <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center text-white text-xs font-bold">
                <ExternalLink class="h-4 w-4" />
              </div>
            </a>
          </div>
          <p v-else class="text-xs text-gray-400 italic p-3 rounded-xl bg-gray-50 dark:bg-gray-800/30 text-center">
            {{ $t('siteVisits.noPhotosYet') }}
          </p>
        </div>
      </div>
    </CrudModal>

    <!-- ======================================================== -->
    <!-- 3. Schedule / Reschedule Modal                           -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showScheduleModal"
      :title="$t('siteVisits.scheduleModalTitle')"
      :loading="savingSchedule"
      :size="'3xl'"
      :save-text="'حفظ وتأكيد الموعد'"
      @close="showScheduleModal = false"
      @save="submitScheduleAction"
    >
      <div class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('siteVisits.scheduledDate') }} <span class="text-rose-500">*</span>
            </label>
            <input
              type="date"
              v-model="scheduleForm.scheduled_date"
              required
              class="w-full rounded-xl border bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900/40 dark:text-white"
              :class="scheduleErrors.scheduled_date ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
            />
            <p v-if="scheduleErrors.scheduled_date" class="mt-1 text-[11px] text-rose-500 font-bold">
              {{ scheduleErrors.scheduled_date[0] }}
            </p>
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('siteVisits.scheduledTime') }}
            </label>
            <input
              type="time"
              v-model="scheduleForm.scheduled_time"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
          </div>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('siteVisits.assignedTo') }}
          </label>
          <select
            v-model="scheduleForm.assigned_to"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
          >
            <option :value="null">{{ $t('siteVisits.selectAssignee') }}</option>
            <option v-for="u in usersList" :key="u.id" :value="u.id">
              {{ u.name }}
            </option>
          </select>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('siteVisits.internalNotes') }}
          </label>
          <textarea
            v-model="scheduleForm.internal_notes"
            rows="2"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 p-2.5 text-xs text-gray-900 outline-hidden focus:border-[#00C896] dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            placeholder="ملاحظات حول الموعد..."
          ></textarea>
        </div>
      </div>
    </CrudModal>

    <!-- ======================================================== -->
    <!-- 4. Complete Visit & Assessment Modal                     -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showCompleteModal"
      :title="$t('siteVisits.completeModalTitle')"
      :loading="savingComplete"
      :size="'4xl'"
      :save-text="'إتمام المعاينة وحفظ التقرير'"
      @close="showCompleteModal = false"
      @save="submitCompleteAction"
    >
      <div class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('siteVisits.visitDate') }} <span class="text-rose-500">*</span>
          </label>
          <input
            type="date"
            v-model="completeForm.visit_date"
            required
            class="w-full rounded-xl border bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900/40 dark:text-white"
            :class="completeErrors.visit_date ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
          />
          <p v-if="completeErrors.visit_date" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ completeErrors.visit_date[0] }}
          </p>
        </div>

        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('siteVisits.generalAssessment') }} <span class="text-rose-500">*</span>
          </label>
          <textarea
            v-model="completeForm.general_assessment"
            required
            rows="3"
            class="w-full rounded-xl border bg-gray-50 p-3 text-xs text-gray-900 outline-hidden focus:border-[#00C896] dark:bg-gray-900/40 dark:text-white"
            :class="completeErrors.general_assessment ? 'border-rose-500 bg-rose-50/40 dark:border-rose-500' : 'border-gray-200 dark:border-gray-700'"
            :placeholder="$t('siteVisits.generalAssessmentPlaceholder')"
          ></textarea>
          <p v-if="completeErrors.general_assessment" class="mt-1 text-[11px] text-rose-500 font-bold">
            {{ completeErrors.general_assessment[0] }}
          </p>
        </div>

        <!-- Rooms Builder -->
        <div class="space-y-2 p-3.5 rounded-2xl bg-purple-50/40 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/30">
          <div class="flex items-center justify-between">
            <h4 class="text-xs font-black text-purple-900 dark:text-purple-300">
              {{ $t('siteVisits.rooms') }}
            </h4>
            <button
              type="button"
              @click="addCompleteRoomRow"
              class="px-2.5 py-1 rounded-lg bg-purple-600 hover:bg-purple-700 text-white text-[11px] font-bold shadow-xs transition-all cursor-pointer"
            >
              {{ $t('siteVisits.addRoom') }}
            </button>
          </div>

          <div class="space-y-2">
            <div
              v-for="(r, idx) in completeForm.rooms"
              :key="idx"
              class="grid grid-cols-12 gap-2 items-center bg-white dark:bg-gray-900 p-2 rounded-xl border border-purple-100 dark:border-purple-900/40"
            >
              <div class="col-span-5">
                <input
                  type="text"
                  v-model="r.room_name"
                  required
                  :placeholder="$t('siteVisits.roomNamePlaceholder')"
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>
              <div class="col-span-3">
                <input
                  type="number"
                  step="0.01"
                  v-model="r.estimated_area"
                  placeholder="المساحة م²"
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-mono font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>
              <div class="col-span-3">
                <input
                  type="text"
                  v-model="r.notes"
                  placeholder="ملاحظات..."
                  class="w-full rounded-lg border border-gray-200 bg-gray-50 py-1.5 px-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-purple-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                />
              </div>
              <div class="col-span-1 flex justify-center">
                <button
                  type="button"
                  @click="removeCompleteRoomRow(idx)"
                  class="p-1 rounded-md text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors cursor-pointer"
                >
                  <Trash2 class="h-3.5 w-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </CrudModal>

    <!-- ======================================================== -->
    <!-- 5. Upload Site Photos Modal                              -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showPhotosModal"
      :title="$t('siteVisits.uploadPhotosAction')"
      :loading="savingPhotos"
      :size="'3xl'"
      :save-text="'رفع وحفظ الصور'"
      @close="showPhotosModal = false"
      @save="submitPhotosAction"
    >
      <div class="space-y-4">
        <div class="relative flex flex-col items-center justify-center p-6 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl bg-gray-50 dark:bg-gray-900 text-center hover:bg-gray-100 dark:hover:bg-gray-800/40 transition-colors">
          <input
            type="file"
            multiple
            accept="image/jpeg,image/png,image/webp,image/heic"
            @change="onModalPhotosSelected"
            class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
          />
          <Camera class="h-8 w-8 text-[#00C896] mb-2" />
          <p class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $t('siteVisits.photosUploadHint') }}</p>
          <span v-if="modalPhotoFiles.length > 0" class="mt-2 inline-flex items-center px-3 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 text-xs font-bold">
            تم تحديد {{ modalPhotoFiles.length }} صورة جاهزة للرفع
          </span>
        </div>

        <div v-if="modalPhotoFiles.length > 0" class="flex flex-wrap gap-2.5">
          <div
            v-for="(photo, idx) in modalPhotoFiles"
            :key="idx"
            class="relative group h-20 w-20 rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 shadow-xs"
          >
            <img :src="photo.preview" class="h-full w-full object-cover" />
            <button
              type="button"
              @click="modalPhotoFiles.splice(idx, 1)"
              class="absolute top-1 right-1 h-5 w-5 rounded-full bg-rose-600 text-white flex items-center justify-center text-xs opacity-90 hover:opacity-100 cursor-pointer shadow-xs"
            >
              ✕
            </button>
          </div>
        </div>
      </div>
    </CrudModal>

    <!-- ======================================================== -->
    <!-- 6. Quick Customer Creation Modal                         -->
    <!-- ======================================================== -->
    <CrudModal
      :show="showQuickCustomerModal"
      :title="$t('leads.quickAddCustomerTitle')"
      :loading="quickCustomerLoading"
      @close="showQuickCustomerModal = false"
      @save="saveQuickCustomer"
    >
      <div class="space-y-4">
        <p class="text-xs text-gray-500 dark:text-gray-400">
          {{ $t('leads.quickAddCustomerDesc') }}
        </p>

        <!-- Customer Type -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('customers.customerType') }} <span class="text-rose-500">*</span>
          </label>
          <div class="grid grid-cols-2 gap-2">
            <button
              type="button"
              @click="quickCustomerForm.customer_type = 'individual'"
              class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
              :class="quickCustomerForm.customer_type === 'individual' ? 'border-[#00C896] bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
            >
              <User class="h-4 w-4" />
              <span>{{ $t('customers.individual') }}</span>
            </button>
            <button
              type="button"
              @click="quickCustomerForm.customer_type = 'company'"
              class="flex items-center justify-center gap-2 py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer"
              :class="quickCustomerForm.customer_type === 'company' ? 'border-purple-500 bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300' : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400'"
            >
              <Building2 class="h-4 w-4" />
              <span>{{ $t('customers.company') }}</span>
            </button>
          </div>
        </div>

        <!-- Name & Company Name -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.name') }} <span class="text-rose-500">*</span>
            </label>
            <input
              type="text"
              v-model="quickCustomerForm.name"
              required
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
            <p v-if="quickCustomerErrors.name" class="mt-1 text-[11px] text-rose-500 font-bold">
              {{ quickCustomerErrors.name[0] }}
            </p>
          </div>

          <div v-if="quickCustomerForm.customer_type === 'company'">
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.companyName') }}
            </label>
            <input
              type="text"
              v-model="quickCustomerForm.company_name"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            />
          </div>
        </div>

        <!-- Phone & WhatsApp -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.phone') }}
            </label>
            <input
              type="tel"
              v-model="quickCustomerForm.phone"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
              dir="ltr"
            />
          </div>

          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
              {{ $t('customers.whatsapp') }}
            </label>
            <input
              type="tel"
              v-model="quickCustomerForm.whatsapp"
              class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
              dir="ltr"
            />
          </div>
        </div>

        <!-- Email -->
        <div>
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
            {{ $t('customers.email') }}
          </label>
          <input
            type="email"
            v-model="quickCustomerForm.email"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/40 dark:text-white"
            dir="ltr"
          />
        </div>
      </div>
    </CrudModal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useI18n } from 'vue-i18n';
import api from '../../services/api';
import alertService from '../../services/alert';
import { useNotificationStore } from '../../stores/notification';
import { formatDate } from '../../utils/date';
import CrudIndex from '../../components/crud/CrudIndex.vue';
import CrudModal from '../../components/crud/CrudModal.vue';

// Lucide Icons
import {
  MapPin,
  Calendar,
  Clock,
  CheckCircle2,
  AlertCircle,
  RotateCcw,
  Sparkles,
  Target,
  User,
  Building2,
  Phone,
  MessageSquare,
  Eye,
  Plus,
  Pencil,
  Trash2,
  XCircle,
  Camera,
  LayoutGrid,
  ExternalLink,
  UploadCloud,
} from 'lucide-vue-next';

const { t } = useI18n();
const route = useRoute();
const notificationStore = useNotificationStore();
const crudRef = ref(null);

// Statistics State
const stats = ref({
  total: 0,
  scheduled: 0,
  completed: 0,
  cancelled: 0,
  rescheduled: 0,
  scheduled_today: 0,
  overdue: 0,
});

// Filter Parameters
const filterParams = reactive({
  status: '',
  opportunity_id: route.query.opportunity_id || '',
  customer_id: route.query.customer_id || '',
});

// Clean Table Columns Definition
const columns = [
  { name: 'id', label: '#', sortable: true },
  { name: 'customer', label: t('siteVisits.customer'), sortable: false },
  { name: 'opportunity', label: t('siteVisits.opportunity'), sortable: false },
  { name: 'status', label: t('siteVisits.status'), sortable: true },
  { name: 'scheduled_date', label: t('siteVisits.scheduledDate'), sortable: true },
  { name: 'assigned_to', label: t('siteVisits.assignedTo'), sortable: false },
  { name: 'photos', label: 'الصور', sortable: false },
];

// Related Options
const relatedOptions = ref({
  customers: [],
  opportunities: [],
  users: [],
});

const customersList = computed(() => relatedOptions.value.customers || []);
const allOpportunitiesList = computed(() => relatedOptions.value.opportunities || []);
const usersList = computed(() => relatedOptions.value.users || []);

// Filtered opportunities based on chosen customer in form
const filteredOpportunities = computed(() => {
  if (!visitForm.customer_id) {
    return allOpportunitiesList.value;
  }
  return allOpportunitiesList.value.filter(
    (opp) => !opp.customer_id || opp.customer_id === visitForm.customer_id
  );
});

// Modals State
const showCreateEditModal = ref(false);
const isEditMode = ref(false);
const activeRecordId = ref(null);
const savingVisit = ref(false);
const visitErrors = ref({});

const showDetailsModal = ref(false);
const selectedVisit = ref(null);

const showScheduleModal = ref(false);
const savingSchedule = ref(false);
const scheduleErrors = ref({});
const scheduleForm = reactive({
  scheduled_date: '',
  scheduled_time: '',
  assigned_to: null,
  internal_notes: '',
});

const showCompleteModal = ref(false);
const savingComplete = ref(false);
const completeErrors = ref({});
const completeForm = reactive({
  visit_date: '',
  general_assessment: '',
  rooms: [],
});

const showPhotosModal = ref(false);
const savingPhotos = ref(false);
const modalPhotoFiles = ref([]);

// Quick Customer Modal
const showQuickCustomerModal = ref(false);
const quickCustomerLoading = ref(false);
const quickCustomerErrors = ref({});
const quickCustomerForm = reactive({
  customer_type: 'individual',
  name: '',
  company_name: '',
  phone: '',
  whatsapp: '',
  email: '',
});

// Create / Edit Visit Form
const visitForm = reactive({
  customer_id: null,
  opportunity_id: null,
  status: 'Scheduled',
  scheduled_date: '',
  scheduled_time: '',
  visit_date: '',
  assigned_to: null,
  general_assessment: '',
  internal_notes: '',
  rooms: [],
});

const selectedPhotos = ref([]);

const setVisitMode = (mode) => {
  visitForm.status = mode;
  if (mode === 'Completed' && !visitForm.visit_date) {
    visitForm.visit_date = new Date().toISOString().split('T')[0];
  }
};

const addRoomRow = () => {
  visitForm.rooms.push({ room_name: '', estimated_area: null, notes: '' });
};

const removeRoomRow = (index) => {
  visitForm.rooms.splice(index, 1);
};

const addCompleteRoomRow = () => {
  completeForm.rooms.push({ room_name: '', estimated_area: null, notes: '' });
};

const removeCompleteRoomRow = (index) => {
  completeForm.rooms.splice(index, 1);
};

const onPhotoFilesSelected = (e) => {
  const files = Array.from(e.target.files || []);
  files.forEach((file) => {
    selectedPhotos.value.push({
      file,
      preview: URL.createObjectURL(file),
    });
  });
};

const removeSelectedPhoto = (index) => {
  selectedPhotos.value.splice(index, 1);
};

const onModalPhotosSelected = (e) => {
  const files = Array.from(e.target.files || []);
  files.forEach((file) => {
    modalPhotoFiles.value.push({
      file,
      preview: URL.createObjectURL(file),
    });
  });
};

const setStatusFilter = (status) => {
  filterParams.status = status;
};

const onDataLoaded = (payload) => {
  if (payload.stats) {
    stats.value = payload.stats;
  }
};

const fetchOptions = async () => {
  try {
    const [custRes, oppRes, userRes] = await Promise.all([
      api.get('/customers?per_page=100'),
      api.get('/opportunities?per_page=100'),
      api.get('/users?per_page=100'),
    ]);
    relatedOptions.value = {
      customers: custRes.data.data || [],
      opportunities: oppRes.data.data || [],
      users: userRes.data.data || [],
    };
  } catch (err) {
    console.error('Failed to load related options', err);
  }
};

// Open Create Modal
const openCreateModal = () => {
  isEditMode.value = false;
  activeRecordId.value = null;
  selectedPhotos.value = [];
  visitErrors.value = {};

  Object.assign(visitForm, {
    customer_id: route.query.customer_id ? Number(route.query.customer_id) : null,
    opportunity_id: route.query.opportunity_id ? Number(route.query.opportunity_id) : null,
    status: 'Scheduled',
    scheduled_date: new Date().toISOString().split('T')[0],
    scheduled_time: '10:00',
    visit_date: '',
    assigned_to: null,
    general_assessment: '',
    internal_notes: '',
    rooms: [],
  });

  showCreateEditModal.value = true;
};

// Open Edit Modal
const openEditModal = (item) => {
  isEditMode.value = true;
  activeRecordId.value = item.id;
  selectedPhotos.value = [];
  visitErrors.value = {};

  Object.assign(visitForm, {
    customer_id: item.customer_id,
    opportunity_id: item.opportunity_id,
    status: item.status || 'Scheduled',
    scheduled_date: item.scheduled_date ? item.scheduled_date.split('T')[0] : '',
    scheduled_time: item.scheduled_time || '',
    visit_date: item.visit_date ? item.visit_date.split('T')[0] : '',
    assigned_to: item.assigned_to,
    general_assessment: item.general_assessment || '',
    internal_notes: item.internal_notes || '',
    rooms: item.rooms ? JSON.parse(JSON.stringify(item.rooms)) : [],
  });

  showCreateEditModal.value = true;
};

// Submit Visit Form (Multipart Support)
const submitVisitForm = async () => {
  visitErrors.value = {};
  let hasClientError = false;
  const errors = {};

  if (!visitForm.customer_id) {
    errors.customer_id = [t('siteVisits.selectCustomer') || 'يرجى اختيار العميل أولاً'];
    hasClientError = true;
  }

  if (visitForm.status === 'Scheduled' || visitForm.status === 'Rescheduled') {
    if (!visitForm.scheduled_date) {
      errors.scheduled_date = ['يرجى تحديد تاريخ المعاينة المجدولة'];
      hasClientError = true;
    }
  }

  if (visitForm.status === 'Completed') {
    if (!visitForm.visit_date) {
      errors.visit_date = ['يرجى تحديد تاريخ تنفيذ المعاينة'];
      hasClientError = true;
    }
  }

  if (hasClientError) {
    visitErrors.value = errors;
    notificationStore.error('يرجى استكمال الحقول المطلوبة والمحددة باللون الأحمر');
    return;
  }

  savingVisit.value = true;
  try {
    const formData = new FormData();
    formData.append('customer_id', visitForm.customer_id);
    if (visitForm.opportunity_id) formData.append('opportunity_id', visitForm.opportunity_id);
    formData.append('status', visitForm.status);
    if (visitForm.scheduled_date) formData.append('scheduled_date', visitForm.scheduled_date);
    if (visitForm.scheduled_time) formData.append('scheduled_time', visitForm.scheduled_time);
    if (visitForm.visit_date) formData.append('visit_date', visitForm.visit_date);
    if (visitForm.assigned_to) formData.append('assigned_to', visitForm.assigned_to);
    if (visitForm.general_assessment) formData.append('general_assessment', visitForm.general_assessment);
    if (visitForm.internal_notes) formData.append('internal_notes', visitForm.internal_notes);

    // Rooms
    if (visitForm.rooms && visitForm.rooms.length > 0) {
      visitForm.rooms.forEach((r, idx) => {
        formData.append(`rooms[${idx}][room_name]`, r.room_name);
        if (r.estimated_area) formData.append(`rooms[${idx}][estimated_area]`, r.estimated_area);
        if (r.notes) formData.append(`rooms[${idx}][notes]`, r.notes);
      });
    }

    // Site Photos
    if (selectedPhotos.value.length > 0) {
      selectedPhotos.value.forEach((p) => {
        formData.append('site_photos[]', p.file);
      });
    }

    if (isEditMode.value) {
      formData.append('_method', 'PUT');
      await api.post(`/site-visits/${activeRecordId.value}`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      notificationStore.success(t('siteVisits.savedSuccessfully') || 'تم حفظ التعديلات بنجاح');
    } else {
      await api.post('/site-visits', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      notificationStore.success(t('siteVisits.savedSuccessfully') || 'تم تسجيل وحفظ المعاينة بنجاح');
    }

    showCreateEditModal.value = false;
    crudRef.value?.loadData();
  } catch (err) {
    if (err.response?.status === 422) {
      visitErrors.value = err.response.data.errors || {};
      notificationStore.error('يرجى تصحيح أخطاء الإدخال الموضحة في النموذج');
    } else {
      const msg = err.response?.data?.message || err.message;
      notificationStore.error(msg);
    }
  } finally {
    savingVisit.value = false;
  }
};

// Open Visit Details
const openVisitDetails = async (item) => {
  try {
    const res = await api.get(`/site-visits/${item.id}`);
    selectedVisit.value = res.data.data || item;
    showDetailsModal.value = true;
  } catch (err) {
    selectedVisit.value = item;
    showDetailsModal.value = true;
  }
};

// Open Schedule Modal
const openScheduleModal = (item) => {
  selectedVisit.value = item;
  scheduleErrors.value = {};
  scheduleForm.scheduled_date = item.scheduled_date ? item.scheduled_date.split('T')[0] : '';
  scheduleForm.scheduled_time = item.scheduled_time || '';
  scheduleForm.assigned_to = item.assigned_to || null;
  scheduleForm.internal_notes = item.internal_notes || '';
  showScheduleModal.value = true;
};

const submitScheduleAction = async () => {
  scheduleErrors.value = {};
  if (!scheduleForm.scheduled_date) {
    scheduleErrors.value = { scheduled_date: ['يرجى تحديد تاريخ المعاينة'] };
    notificationStore.error('يرجى تحديد تاريخ المعاينة');
    return;
  }

  savingSchedule.value = true;
  try {
    const res = await api.post(`/site-visits/${selectedVisit.value.id}/schedule`, scheduleForm);
    notificationStore.success(res.data.message || t('siteVisits.scheduledSuccessfully') || 'تم جدولة المعاينة بنجاح');
    showScheduleModal.value = false;
    crudRef.value?.loadData();
    if (selectedVisit.value) {
      selectedVisit.value = res.data.data;
    }
  } catch (err) {
    if (err.response?.status === 422) {
      scheduleErrors.value = err.response.data.errors || {};
      notificationStore.error('يرجى تصحيح الأخطاء الموضحة');
    } else {
      const msg = err.response?.data?.message || err.message;
      notificationStore.error(msg);
    }
  } finally {
    savingSchedule.value = false;
  }
};

// Open Complete Modal
const openCompleteModal = (item) => {
  selectedVisit.value = item;
  completeErrors.value = {};
  completeForm.visit_date = item.visit_date ? item.visit_date.split('T')[0] : new Date().toISOString().split('T')[0];
  completeForm.general_assessment = item.general_assessment || '';
  completeForm.rooms = item.rooms ? JSON.parse(JSON.stringify(item.rooms)) : [];
  if (completeForm.rooms.length === 0) {
    completeForm.rooms.push({ room_name: '', estimated_area: null, notes: '' });
  }
  showCompleteModal.value = true;
};

const submitCompleteAction = async () => {
  completeErrors.value = {};
  let hasErr = false;
  const errs = {};

  if (!completeForm.visit_date) {
    errs.visit_date = ['يرجى تحديد تاريخ التنفيذ'];
    hasErr = true;
  }
  if (!completeForm.general_assessment) {
    errs.general_assessment = ['يرجى كتابة التقييم الفني العام للمعاينة'];
    hasErr = true;
  }

  if (hasErr) {
    completeErrors.value = errs;
    notificationStore.error('يرجى إدخال الحقول الإلزامية لإتمام المعاينة');
    return;
  }

  savingComplete.value = true;
  try {
    const res = await api.post(`/site-visits/${selectedVisit.value.id}/complete`, completeForm);
    notificationStore.success(res.data.message || t('siteVisits.completedSuccessfully') || 'تم إتمام المعاينة بنجاح');
    showCompleteModal.value = false;
    crudRef.value?.loadData();
    if (selectedVisit.value) {
      selectedVisit.value = res.data.data;
    }
  } catch (err) {
    if (err.response?.status === 422) {
      completeErrors.value = err.response.data.errors || {};
      notificationStore.error('يرجى تصحيح الأخطاء الموضحة');
    } else {
      const msg = err.response?.data?.message || err.message;
      notificationStore.error(msg);
    }
  } finally {
    savingComplete.value = false;
  }
};

// Open Photos Modal
const openPhotosModal = (item) => {
  selectedVisit.value = item;
  modalPhotoFiles.value = [];
  showPhotosModal.value = true;
};

const submitPhotosAction = async () => {
  if (modalPhotoFiles.value.length === 0) {
    notificationStore.error('يرجى اختيار صور أو مخططات لرفعها');
    return;
  }

  savingPhotos.value = true;
  try {
    const formData = new FormData();
    modalPhotoFiles.value.forEach((p) => {
      formData.append('photos[]', p.file);
    });

    const res = await api.post(`/site-visits/${selectedVisit.value.id}/photos`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    notificationStore.success(res.data.message || t('siteVisits.photosUploadedSuccessfully') || 'تم رفع وحفظ الصور والمخططات بنجاح');
    showPhotosModal.value = false;
    modalPhotoFiles.value = [];
    crudRef.value?.loadData();
    if (selectedVisit.value && res.data.data) {
      selectedVisit.value = res.data.data;
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.message;
    notificationStore.error(msg);
  } finally {
    savingPhotos.value = false;
  }
};

// Cancel Visit Action
const cancelVisitAction = async (item) => {
  const confirmed = await alertService.confirm(
    t('siteVisits.cancelConfirm'),
    t('siteVisits.cancelAction')
  );
  if (!confirmed) return;

  try {
    const res = await api.post(`/site-visits/${item.id}/cancel`, {
      cancellation_reason: 'تم الإلغاء بواسطة المستخدم',
    });
    notificationStore.success(res.data.message || t('siteVisits.cancelledSuccessfully') || 'تم إلغاء المعاينة بنجاح');
    crudRef.value?.loadData();
    if (selectedVisit.value && selectedVisit.value.id === item.id) {
      selectedVisit.value = res.data.data;
    }
  } catch (err) {
    const msg = err.response?.data?.message || err.message;
    notificationStore.error(msg);
  }
};

// Quick Customer Save
const saveQuickCustomer = async () => {
  quickCustomerErrors.value = {};
  if (!quickCustomerForm.name) {
    quickCustomerErrors.value = { name: [t('validation.required')] };
    return;
  }

  quickCustomerLoading.value = true;
  try {
    const res = await api.post('/customers', quickCustomerForm);
    const newCustomer = res.data.data;

    // Add to options
    relatedOptions.value.customers.unshift(newCustomer);
    visitForm.customer_id = newCustomer.id;

    notificationStore.success(t('customers.createdSuccessfully') || 'تم إضافة العميل بنجاح');
    showQuickCustomerModal.value = false;

    // Reset quick form
    Object.assign(quickCustomerForm, {
      customer_type: 'individual',
      name: '',
      company_name: '',
      phone: '',
      whatsapp: '',
      email: '',
    });
  } catch (err) {
    if (err.response?.status === 422) {
      quickCustomerErrors.value = err.response.data.errors || {};
    } else {
      const msg = err.response?.data?.message || err.message;
      notificationStore.error(msg);
    }
  } finally {
    quickCustomerLoading.value = false;
  }
};

// Helpers for Status Styles
const getStatusClass = (status) => {
  switch (status) {
    case 'Scheduled':
      return 'bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:border-blue-800';
    case 'Completed':
      return 'bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:border-emerald-800';
    case 'Requested':
      return 'bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:border-amber-800';
    case 'Rescheduled':
      return 'bg-purple-50 text-purple-700 border border-purple-200 dark:bg-purple-900/30 dark:text-purple-300 dark:border-purple-800';
    case 'Cancelled':
      return 'bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-900/30 dark:text-rose-300 dark:border-rose-800';
    default:
      return 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300';
  }
};

const getStatusDotClass = (status) => {
  switch (status) {
    case 'Scheduled':
      return 'bg-blue-500';
    case 'Completed':
      return 'bg-emerald-500';
    case 'Requested':
      return 'bg-amber-500';
    case 'Rescheduled':
      return 'bg-purple-500';
    case 'Cancelled':
      return 'bg-rose-500';
    default:
      return 'bg-gray-400';
  }
};

const getStatusLabel = (status) => {
  switch (status) {
    case 'Scheduled':
      return t('siteVisits.statusScheduled');
    case 'Completed':
      return t('siteVisits.statusCompleted');
    case 'Requested':
      return t('siteVisits.statusRequested');
    case 'Rescheduled':
      return t('siteVisits.statusRescheduled');
    case 'Cancelled':
      return t('siteVisits.statusCancelled');
    default:
      return status || '—';
  }
};

onMounted(() => {
  fetchOptions();
});
</script>
