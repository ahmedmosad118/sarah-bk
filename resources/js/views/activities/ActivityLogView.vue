<template>
  <div class="space-y-6">
    <BreadcrumbDefault :pageTitle="$t('activities.title')" />

    <!-- Top Stats Overview Cards -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
      <!-- Total Activities -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-800 transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('activities.statsTotal') }}</p>
            <h4 class="mt-2 text-2xl font-black text-gray-900 dark:text-white font-mono">
              {{ stats.total }}
            </h4>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-[#00C896] dark:bg-emerald-950/50 dark:text-[#00C896]">
            <Activity class="h-6 w-6 stroke-[2.2]" />
          </div>
        </div>
      </div>

      <!-- Today's Activities -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-800 transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('activities.statsToday') }}</p>
            <h4 class="mt-2 text-2xl font-black text-blue-600 dark:text-blue-400 font-mono">
              {{ stats.today }}
            </h4>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400">
            <Clock class="h-6 w-6 stroke-[2.2]" />
          </div>
        </div>
      </div>

      <!-- Auth & Security -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-800 transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('activities.statsAuth') }}</p>
            <h4 class="mt-2 text-2xl font-black text-indigo-600 dark:text-indigo-400 font-mono">
              {{ stats.auth }}
            </h4>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400">
            <ShieldCheck class="h-6 w-6 stroke-[2.2]" />
          </div>
        </div>
      </div>

      <!-- Data Mutations (CRUD) -->
      <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs dark:border-gray-800 dark:bg-gray-800 transition-all hover:shadow-md">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs font-bold text-gray-500 dark:text-gray-400">{{ $t('activities.statsMutations') }}</p>
            <h4 class="mt-2 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">
              {{ stats.mutations }}
            </h4>
          </div>
          <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400">
            <Database class="h-6 w-6 stroke-[2.2]" />
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table Card -->
    <TailAdminDataTable
      :columns="columns"
      :items="items"
      :loading="loading"
      :meta="meta"
      :allow-create="false"
      :selectable="false"
      :search-placeholder="$t('activities.searchPlaceholder')"
      @search="onSearch"
      @page-change="loadLogs"
    >
      <!-- Filter Slots: Luxury Select2 Custom Dropdowns with SVG Icons -->
      <template #filters>
        <div class="flex flex-wrap items-center gap-3" @click.stop>
          <!-- 1. Module / Section Select2 Dropdown -->
          <div class="relative w-64 sm:w-72" ref="moduleDropdownRef">
            <button
              type="button"
              @click="toggleModuleDropdown"
              class="w-full flex items-center justify-between rounded-xl border bg-gray-50/90 py-2.5 px-3.5 text-xs font-bold transition-all outline-hidden cursor-pointer"
              :class="[
                $i18n.locale === 'ar' ? 'text-right' : 'text-left',
                isModuleOpen
                  ? 'border-[#00C896] bg-white ring-2 ring-[#00C896]/20 dark:border-[#00C896] dark:bg-gray-900 shadow-sm'
                  : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900/60 dark:hover:border-gray-600',
              ]"
            >
              <div class="flex items-center gap-2 truncate">
                <component
                  :is="selectedModuleObj.icon"
                  class="h-4 w-4 shrink-0"
                  :class="selectedModuleObj.color"
                />
                <span class="truncate text-gray-800 dark:text-gray-100">
                  {{ selectedModuleObj.label }}
                </span>
              </div>

              <div class="flex items-center gap-1 shrink-0 mr-1.5">
                <span
                  v-if="filterLogName"
                  @click.stop="selectModule('')"
                  class="p-0.5 rounded-full text-gray-400 hover:text-rose-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  title="إلغاء الفلتر"
                >
                  <X class="h-3.5 w-3.5" />
                </span>
                <ChevronDown
                  class="h-4 w-4 text-gray-400 transition-transform duration-200"
                  :class="{ 'rotate-180 text-[#00C896]': isModuleOpen }"
                />
              </div>
            </button>

            <!-- Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0 -translate-y-1"
              enter-to-class="transform scale-100 opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100 translate-y-0"
              leave-to-class="transform scale-95 opacity-0 -translate-y-1"
            >
              <div
                v-if="isModuleOpen"
                class="absolute right-0 z-50 mt-1.5 w-full rounded-2xl border border-gray-100 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-800 flex flex-col"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              >
                <!-- Search Box inside Module Dropdown -->
                <div class="p-1 mb-1 border-b border-gray-100 dark:border-gray-700">
                  <div class="relative">
                    <input
                      ref="moduleSearchInputRef"
                      type="text"
                      v-model="moduleSearch"
                      :placeholder="$t('activities.searchPlaceholder') || 'بحث في الأقسام...'"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 py-1.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                      :class="$i18n.locale === 'ar' ? 'pr-8 pl-3 text-right' : 'pl-8 pr-3 text-left'"
                      @click.stop
                    />
                    <Search
                      class="absolute top-2 h-3.5 w-3.5 text-gray-400 pointer-events-none"
                      :class="$i18n.locale === 'ar' ? 'right-2.5' : 'left-2.5'"
                    />
                  </div>
                </div>

                <!-- Options List -->
                <div class="overflow-y-auto max-h-56 space-y-1 py-1">
                  <button
                    v-for="opt in filteredModuleOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectModule(opt.value)"
                    class="w-full flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold transition-all text-right cursor-pointer"
                    :class="[
                      filterLogName === opt.value
                        ? 'bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]'
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50',
                    ]"
                  >
                    <div class="flex items-center gap-2.5">
                      <component :is="opt.icon" class="h-4 w-4 shrink-0" :class="opt.color" />
                      <span>{{ opt.label }}</span>
                    </div>
                    <Check v-if="filterLogName === opt.value" class="h-3.5 w-3.5 text-[#00C896]" />
                  </button>
                </div>
              </div>
            </Transition>
          </div>

          <!-- 2. Operation Type Select2 Dropdown -->
          <div class="relative w-52 sm:w-60" ref="eventDropdownRef">
            <button
              type="button"
              @click="toggleEventDropdown"
              class="w-full flex items-center justify-between rounded-xl border bg-gray-50/90 py-2.5 px-3.5 text-xs font-bold transition-all outline-hidden cursor-pointer"
              :class="[
                $i18n.locale === 'ar' ? 'text-right' : 'text-left',
                isEventOpen
                  ? 'border-[#00C896] bg-white ring-2 ring-[#00C896]/20 dark:border-[#00C896] dark:bg-gray-900 shadow-sm'
                  : 'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:bg-gray-900/60 dark:hover:border-gray-600',
              ]"
            >
              <div class="flex items-center gap-2 truncate">
                <component
                  :is="selectedEventObj.icon"
                  class="h-4 w-4 shrink-0"
                  :class="selectedEventObj.color"
                />
                <span class="truncate text-gray-800 dark:text-gray-100">
                  {{ selectedEventObj.label }}
                </span>
              </div>

              <div class="flex items-center gap-1 shrink-0 mr-1.5">
                <span
                  v-if="filterEvent"
                  @click.stop="selectEvent('')"
                  class="p-0.5 rounded-full text-gray-400 hover:text-rose-500 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                  title="إلغاء الفلتر"
                >
                  <X class="h-3.5 w-3.5" />
                </span>
                <ChevronDown
                  class="h-4 w-4 text-gray-400 transition-transform duration-200"
                  :class="{ 'rotate-180 text-[#00C896]': isEventOpen }"
                />
              </div>
            </button>

            <!-- Dropdown Menu -->
            <Transition
              enter-active-class="transition duration-150 ease-out"
              enter-from-class="transform scale-95 opacity-0 -translate-y-1"
              enter-to-class="transform scale-100 opacity-100 translate-y-0"
              leave-active-class="transition duration-100 ease-in"
              leave-from-class="transform scale-100 opacity-100 translate-y-0"
              leave-to-class="transform scale-95 opacity-0 -translate-y-1"
            >
              <div
                v-if="isEventOpen"
                class="absolute right-0 z-50 mt-1.5 w-full rounded-2xl border border-gray-100 bg-white p-2 shadow-2xl dark:border-gray-700 dark:bg-gray-800 flex flex-col"
                :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
              >
                <!-- Options List -->
                <div class="overflow-y-auto max-h-56 space-y-1 py-1">
                  <button
                    v-for="opt in eventOptions"
                    :key="opt.value"
                    type="button"
                    @click="selectEvent(opt.value)"
                    class="w-full flex items-center justify-between rounded-xl px-3 py-2 text-xs font-bold transition-all text-right cursor-pointer"
                    :class="[
                      filterEvent === opt.value
                        ? 'bg-[#00C896]/10 text-[#00A87E] dark:bg-[#00C896]/20 dark:text-[#00C896]'
                        : 'text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700/50',
                    ]"
                  >
                    <div class="flex items-center gap-2.5">
                      <component :is="opt.icon" class="h-4 w-4 shrink-0" :class="opt.color" />
                      <span>{{ opt.label }}</span>
                    </div>
                    <Check v-if="filterEvent === opt.value" class="h-3.5 w-3.5 text-[#00C896]" />
                  </button>
                </div>
              </div>
            </Transition>
          </div>
        </div>
      </template>

      <!-- Custom Description Column with Module Badge -->
      <template #col-description="{ item }">
        <div class="py-1" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
          <div class="font-bold text-gray-900 dark:text-white leading-relaxed text-xs sm:text-sm">
            {{ item.description }}
          </div>

          <div class="mt-1.5 flex flex-wrap items-center gap-2">
            <!-- Module / Section SVG Badge -->
            <span
              class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-[11px] font-bold shadow-2xs"
              :class="getModuleInfo(item).color"
            >
              <component :is="getModuleInfo(item).iconComponent" class="h-3.5 w-3.5 shrink-0" />
              <span>{{ getModuleInfo(item).label }}</span>
            </span>
          </div>
        </div>
      </template>

      <!-- Custom Causer Column -->
      <template #col-causer="{ item }">
        <div v-if="item.causer" class="flex items-center gap-2.5">
          <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-gray-100 font-bold text-xs text-gray-800 dark:bg-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600">
            {{ (item.causer.name || '?').charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <span class="block truncate font-bold text-gray-900 dark:text-white text-xs">
              {{ item.causer.name }}
            </span>
            <span v-if="item.causer.email" class="block truncate text-[10px] text-gray-400 font-mono">
              {{ item.causer.email }}
            </span>
          </div>
        </div>
        <div v-else class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100/80 dark:bg-gray-800/60 px-2.5 py-1 text-[11px] font-bold text-gray-500 dark:text-gray-400 border border-gray-200/60 dark:border-gray-700/60">
          <Cpu class="h-3.5 w-3.5" />
          <span>{{ $t('activities.systemCauser') }}</span>
        </div>
      </template>

      <!-- Custom Event Column with Translated Luxury SVG Badges -->
      <template #col-event="{ item }">
        <span
          class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-bold shadow-2xs"
          :class="getEventInfo(item.event, item).color"
        >
          <component :is="getEventInfo(item.event, item).iconComponent" class="h-3.5 w-3.5 shrink-0" />
          <span>{{ getEventInfo(item.event, item).label }}</span>
        </span>
      </template>

      <!-- Custom Date Column -->
      <template #col-created_at="{ item }">
        <div class="flex items-center gap-1.5 text-xs text-gray-600 dark:text-gray-300 font-medium">
          <Clock class="h-3.5 w-3.5 text-gray-400 shrink-0" />
          <span>{{ formatDate(item.created_at) }}</span>
        </div>
      </template>

      <!-- Action Column: View Audit Diff -->
      <template #actions="{ item }">
        <div class="flex items-center justify-center">
          <button
            @click="openAuditDetails(item)"
            class="flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-1.5 text-xs font-bold text-gray-700 hover:border-[#00C896] hover:text-[#00C896] dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 dark:hover:border-[#00C896] dark:hover:text-[#00C896] transition-all shadow-2xs"
            :title="$t('activities.auditDetails')"
          >
            <Eye class="h-3.5 w-3.5" />
            <span>{{ $t('activities.actionsCol') }}</span>
          </button>
        </div>
      </template>
    </TailAdminDataTable>

    <!-- Audit Details Modal -->
    <div
      v-if="showModal && selectedItem"
      class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 backdrop-blur-sm p-4 overflow-y-auto animate-in fade-in duration-200"
      @click.self="showModal = false"
    >
      <div class="relative w-full max-w-2xl rounded-3xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900 my-8 transition-all">
        <!-- Modal Header -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-4 dark:border-gray-800">
          <div class="flex items-center gap-3">
            <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-[#00C896] dark:bg-emerald-950/50 dark:text-[#00C896]">
              <ShieldCheck class="h-6 w-6 stroke-[2]" />
            </div>
            <div>
              <h3 class="text-base font-black text-gray-900 dark:text-white">
                {{ $t('activities.auditDetails') }}
              </h3>
              <p class="text-xs text-gray-500 dark:text-gray-400">
                {{ $t('activities.auditDetailsSub') }}
              </p>
            </div>
          </div>
          <button
            @click="showModal = false"
            class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200 transition-colors"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Modal Body -->
        <div class="mt-5 space-y-5 text-xs">
          <!-- Event Overview Banner -->
          <div class="rounded-2xl border border-gray-100 bg-gray-50/80 p-4 dark:border-gray-800 dark:bg-gray-800/50">
            <div class="text-sm font-bold text-gray-900 dark:text-white mb-3">
              {{ selectedItem.description }}
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div>
                <span class="block text-[11px] text-gray-400 font-bold mb-1">{{ $t('activities.eventCol') }}</span>
                <span
                  class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold"
                  :class="getEventInfo(selectedItem.event, selectedItem).color"
                >
                  <component :is="getEventInfo(selectedItem.event, selectedItem).iconComponent" class="h-3.5 w-3.5 shrink-0" />
                  {{ getEventInfo(selectedItem.event, selectedItem).label }}
                </span>
              </div>

              <div>
                <span class="block text-[11px] text-gray-400 font-bold mb-1">{{ $t('activities.moduleCol') }}</span>
                <span
                  class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-bold"
                  :class="getModuleInfo(selectedItem).color"
                >
                  <component :is="getModuleInfo(selectedItem).iconComponent" class="h-3.5 w-3.5 shrink-0" />
                  {{ getModuleInfo(selectedItem).label }}
                </span>
              </div>

              <div>
                <span class="block text-[11px] text-gray-400 font-bold mb-1">{{ $t('activities.timeCol') }}</span>
                <span class="text-gray-700 dark:text-gray-300 font-bold">{{ formatDate(selectedItem.created_at) }}</span>
              </div>

              <div>
                <span class="block text-[11px] text-gray-400 font-bold mb-1">{{ $t('activities.causerCol') }}</span>
                <span class="text-gray-900 dark:text-white font-bold">
                  {{ selectedItem.causer?.name || $t('activities.systemCauser') }}
                </span>
              </div>
            </div>
          </div>

          <!-- Updated Event: Changes Diff Table (Only when fields were actually updated) -->
          <div v-if="selectedItem.event === 'updated' && diffRows.length > 0">
            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider mb-2 flex items-center gap-1.5">
              <Layers class="h-4 w-4 text-[#00C896]" />
              <span>{{ $t('activities.changedAttributes') }}</span>
            </h4>

            <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-800">
              <table class="w-full text-right text-xs">
                <thead class="bg-gray-50 dark:bg-gray-800/80 border-b border-gray-200 dark:border-gray-800 text-[11px] font-black text-gray-500 uppercase">
                  <tr>
                    <th class="py-2.5 px-4">{{ $t('activities.field') }}</th>
                    <th class="py-2.5 px-4 text-rose-600 dark:text-rose-400">{{ $t('activities.oldValue') }}</th>
                    <th class="py-2.5 px-4 text-emerald-600 dark:text-emerald-400">{{ $t('activities.newValue') }}</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
                  <tr v-for="diff in diffRows" :key="diff.field" class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                    <td class="py-2.5 px-4 font-bold text-gray-800 dark:text-gray-200">
                      {{ diff.label }}
                    </td>
                    <td class="py-2.5 px-4 text-rose-600 dark:text-rose-400 font-mono bg-rose-50/30 dark:bg-rose-950/20">
                      {{ diff.oldValue }}
                    </td>
                    <td class="py-2.5 px-4 text-emerald-600 dark:text-emerald-400 font-mono bg-emerald-50/30 dark:bg-emerald-950/20 font-bold">
                      {{ diff.newValue }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Deleted Event Info Banner -->
          <div v-else-if="selectedItem.event === 'deleted'" class="rounded-2xl border border-rose-200/70 bg-rose-50/40 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/50 dark:text-rose-400">
                <Trash2 class="h-5 w-5 stroke-[2]" />
              </div>
              <div>
                <h5 class="text-xs font-bold text-rose-900 dark:text-rose-200">تم حذف السجل بالكامل</h5>
                <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5">تمت إزالة هذا السجل نهائياً من قاعدة البيانات وتوثيق العملية في سجل التدقيق الأمني.</p>
              </div>
            </div>
          </div>

          <!-- Created Event Info Banner -->
          <div v-else-if="selectedItem.event === 'created'" class="rounded-2xl border border-emerald-200/70 bg-emerald-50/40 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 dark:bg-emerald-900/50 dark:text-emerald-400">
                <PlusCircle class="h-5 w-5 stroke-[2]" />
              </div>
              <div>
                <h5 class="text-xs font-bold text-emerald-900 dark:text-emerald-200">تم إنشاء وإضافة السجل بنجاح</h5>
                <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">تم تسجيل هذا العنصر ككيان جديد داخل المنظومة وفق الصلاحيات المعتمدة.</p>
              </div>
            </div>
          </div>

          <!-- Auth / Security Action Event Info Banner -->
          <div v-else class="rounded-2xl border border-indigo-200/70 bg-indigo-50/40 p-4 dark:border-indigo-900/40 dark:bg-indigo-950/20">
            <div class="flex items-center gap-3">
              <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/50 dark:text-indigo-400">
                <ShieldCheck class="h-5 w-5 stroke-[2]" />
              </div>
              <div>
                <h5 class="text-xs font-bold text-indigo-900 dark:text-indigo-200">تم التحقق الأمني والإجرائي بنجاح</h5>
                <p class="text-[11px] text-indigo-600 dark:text-indigo-400 mt-0.5">تم تنفيذ وتوثيق هذا الإجراء الإداري بنجاح في سجل التدقيق المعتمد.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="mt-6 flex justify-end">
          <button
            @click="showModal = false"
            class="rounded-xl bg-gray-100 hover:bg-gray-200 px-5 py-2.5 text-xs font-bold text-gray-800 dark:bg-gray-800 dark:hover:bg-gray-700 dark:text-gray-200 transition-colors"
          >
            {{ $t('common.close') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  Activity,
  Clock,
  ShieldCheck,
  Database,
  Eye,
  X,
  Layers,
  ChevronDown,
  Cpu,
  Users,
  Target,
  UserCheck,
  Shield,
  Briefcase,
  Settings,
  Building2,
  KeyRound,
  PlusCircle,
  Pencil,
  Trash2,
  LogIn,
  LogOut,
  Zap,
  Search,
  Check,
} from 'lucide-vue-next';
import api from '../../services/api';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';
import TailAdminDataTable from '../../components/tailadmin/TailAdminDataTable.vue';
import { isIsoDateString, formatDate as formatAppDate } from '../../utils/date';

const { t, locale } = useI18n();

const columns = computed(() => [
  { name: 'description', label: t('activities.descCol') },
  { name: 'causer', label: t('activities.causerCol') },
  { name: 'event', label: t('activities.eventCol') },
  { name: 'created_at', label: t('activities.timeCol') },
]);

const items = ref([]);
const meta = ref(null);
const loading = ref(false);
const filterLogName = ref('');
const filterEvent = ref('');
const searchQuery = ref('');

// Dropdowns state
const isModuleOpen = ref(false);
const isEventOpen = ref(false);
const moduleSearch = ref('');
const moduleDropdownRef = ref(null);
const eventDropdownRef = ref(null);

const moduleOptions = computed(() => [
  { value: '', label: t('activities.allModules'), icon: Building2, color: 'text-gray-400 dark:text-gray-500' },
  { value: 'auth', label: t('activities.authModule'), icon: KeyRound, color: 'text-indigo-500' },
  { value: 'customers', label: t('activities.customersModule'), icon: Users, color: 'text-emerald-500' },
  { value: 'leads', label: t('activities.leadsModule'), icon: Target, color: 'text-amber-500' },
  { value: 'users', label: t('activities.usersModule'), icon: UserCheck, color: 'text-blue-500' },
  { value: 'roles', label: t('activities.rolesModule'), icon: Shield, color: 'text-purple-500' },
  { value: 'job_titles', label: t('activities.jobTitlesModule'), icon: Briefcase, color: 'text-cyan-500' },
  { value: 'settings', label: t('activities.settingsModule'), icon: Settings, color: 'text-slate-500' },
]);

const filteredModuleOptions = computed(() => {
  if (!moduleSearch.value) return moduleOptions.value;
  const q = moduleSearch.value.toLowerCase();
  return moduleOptions.value.filter((opt) => opt.label.toLowerCase().includes(q));
});

const eventOptions = computed(() => [
  { value: '', label: t('activities.events.all'), icon: Activity, color: 'text-gray-400 dark:text-gray-500' },
  { value: 'created', label: t('activities.events.created'), icon: PlusCircle, color: 'text-emerald-500' },
  { value: 'updated', label: t('activities.events.updated'), icon: Pencil, color: 'text-blue-500' },
  { value: 'deleted', label: t('activities.events.deleted'), icon: Trash2, color: 'text-rose-500' },
  { value: 'login', label: t('activities.events.login'), icon: LogIn, color: 'text-indigo-500' },
  { value: 'logout', label: t('activities.events.logout'), icon: LogOut, color: 'text-amber-500' },
]);

const selectedModuleObj = computed(() => {
  return moduleOptions.value.find((opt) => opt.value === filterLogName.value) || moduleOptions.value[0];
});

const selectedEventObj = computed(() => {
  return eventOptions.value.find((opt) => opt.value === filterEvent.value) || eventOptions.value[0];
});

const toggleModuleDropdown = () => {
  isModuleOpen.value = !isModuleOpen.value;
  if (isModuleOpen.value) {
    isEventOpen.value = false;
    moduleSearch.value = '';
  }
};

const toggleEventDropdown = () => {
  isEventOpen.value = !isEventOpen.value;
  if (isEventOpen.value) {
    isModuleOpen.value = false;
  }
};

const selectModule = (val) => {
  filterLogName.value = val;
  isModuleOpen.value = false;
  moduleSearch.value = '';
  loadLogs(1);
};

const selectEvent = (val) => {
  filterEvent.value = val;
  isEventOpen.value = false;
  loadLogs(1);
};

const handleWindowClick = (e) => {
  if (moduleDropdownRef.value && !moduleDropdownRef.value.contains(e.target)) {
    isModuleOpen.value = false;
  }
  if (eventDropdownRef.value && !eventDropdownRef.value.contains(e.target)) {
    isEventOpen.value = false;
  }
};

const stats = ref({
  total: 0,
  today: 0,
  auth: 0,
  mutations: 0,
});

// Modal state
const showModal = ref(false);
const selectedItem = ref(null);
const showRawJson = ref(false);

const loadLogs = async (page = 1) => {
  loading.value = true;
  try {
    const res = await api.get('/activity-logs', {
      params: {
        page,
        log_name: filterLogName.value,
        event: filterEvent.value,
        search: searchQuery.value,
      },
    });
    items.value = res.data.data;
    meta.value = res.data.meta;
    if (res.data.stats) {
      stats.value = res.data.stats;
    }
  } catch (err) {
    // Ignore
  } finally {
    loading.value = false;
  }
};

const onSearch = (q) => {
  searchQuery.value = q;
  loadLogs(1);
};

const openAuditDetails = (item) => {
  selectedItem.value = item;
  showRawJson.value = false;
  showModal.value = true;
};

// Module info with pure SVG Lucide components
const getModuleInfo = (item) => {
  const logName = (item.log_name || '').toLowerCase();
  const subjectType = item.subject_type || '';

  if (logName === 'auth' || item.event === 'login' || item.event === 'logout') {
    return {
      key: 'auth',
      label: t('activities.authModule'),
      color: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800/60',
      iconComponent: KeyRound,
    };
  }
  if (logName === 'customers' || logName === 'customer' || subjectType.includes('Customer')) {
    return {
      key: 'customers',
      label: t('activities.customersModule'),
      color: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60',
      iconComponent: Users,
    };
  }
  if (logName === 'leads' || logName === 'lead' || subjectType.includes('Lead')) {
    return {
      key: 'leads',
      label: t('activities.leadsModule'),
      color: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60',
      iconComponent: Target,
    };
  }
  if (logName === 'users' || logName === 'user' || subjectType.includes('User')) {
    return {
      key: 'users',
      label: t('activities.usersModule'),
      color: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/60',
      iconComponent: UserCheck,
    };
  }
  if (
    logName === 'roles' ||
    logName === 'role' ||
    logName === 'permissions' ||
    subjectType.includes('Role') ||
    subjectType.includes('Permission')
  ) {
    return {
      key: 'roles',
      label: t('activities.rolesModule'),
      color: 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800/60',
      iconComponent: Shield,
    };
  }
  if (logName === 'job_titles' || logName === 'job_title' || subjectType.includes('JobTitle')) {
    return {
      key: 'job_titles',
      label: t('activities.jobTitlesModule'),
      color: 'bg-cyan-50 text-cyan-700 border-cyan-200/80 dark:bg-cyan-950/40 dark:text-cyan-400 dark:border-cyan-800/60',
      iconComponent: Briefcase,
    };
  }
  if (logName === 'settings' || logName === 'setting' || subjectType.includes('Setting')) {
    return {
      key: 'settings',
      label: t('activities.settingsModule'),
      color: 'bg-slate-50 text-slate-700 border-slate-200/80 dark:bg-slate-900/40 dark:text-slate-400 dark:border-slate-800/60',
      iconComponent: Settings,
    };
  }

  return {
    key: 'general',
    label: t('activities.generalModule'),
    color: 'bg-gray-50 text-gray-700 border-gray-200/80 dark:bg-gray-900/40 dark:text-gray-400 dark:border-gray-800/60',
    iconComponent: Building2,
  };
};

// Event info with pure SVG Lucide components
const getEventInfo = (event, item = null) => {
  let e = (event || '').toLowerCase();

  // If event is missing, infer from item module/description
  if (!e && item) {
    const desc = (item.description || '').toLowerCase();
    const logName = (item.log_name || '').toLowerCase();
    if (logName === 'auth' || desc.includes('تسجيل الدخول') || desc.includes('login')) {
      e = 'login';
    } else if (desc.includes('تسجيل الخروج') || desc.includes('logout')) {
      e = 'logout';
    }
  }

  if (e === 'created') {
    return {
      label: t('activities.events.created'),
      color: 'bg-emerald-50 text-emerald-700 border-emerald-200/80 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/60',
      iconComponent: PlusCircle,
    };
  }
  if (e === 'updated') {
    return {
      label: t('activities.events.updated'),
      color: 'bg-blue-50 text-blue-700 border-blue-200/80 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/60',
      iconComponent: Pencil,
    };
  }
  if (e === 'deleted') {
    return {
      label: t('activities.events.deleted'),
      color: 'bg-rose-50 text-rose-700 border-rose-200/80 dark:bg-rose-950/40 dark:text-rose-400 dark:border-rose-800/60',
      iconComponent: Trash2,
    };
  }
  if (e === 'login' || e === 'auth') {
    return {
      label: t('activities.events.login'),
      color: 'bg-indigo-50 text-indigo-700 border-indigo-200/80 dark:bg-indigo-950/40 dark:text-indigo-400 dark:border-indigo-800/60',
      iconComponent: LogIn,
    };
  }
  if (e === 'logout') {
    return {
      label: t('activities.events.logout'),
      color: 'bg-amber-50 text-amber-700 border-amber-200/80 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-800/60',
      iconComponent: LogOut,
    };
  }
  return {
    label: t('activities.events.action'),
    color: 'bg-purple-50 text-purple-700 border-purple-200/80 dark:bg-purple-950/40 dark:text-purple-400 dark:border-purple-800/60',
    iconComponent: Zap,
  };
};

// Field Name dictionary for friendly Arabic labels
const fieldLabels = {
  customer_type: 'نوع العميل',
  name: 'الاسم',
  name_ar: 'الاسم بالعربية',
  name_en: 'الاسم بالإنجليزية',
  company_name: 'اسم الشركة',
  phone: 'رقم الهاتف',
  whatsapp: 'رقم الواتساب',
  email: 'البريد الإلكتروني',
  address: 'العنوان',
  status: 'الحالة',
  title: 'عنوان الطلب',
  source: 'مصدر العميل',
  estimated_value: 'القيمة التقديرية',
  expected_start_date: 'تاريخ البدء المتوقع',
  notes: 'الملاحظات',
  description: 'الوصف',
  is_active: 'حالة التفعيل',
  job_title_id: 'المسمى الوظيفي',
  key: 'مفتاح الإعداد',
  value: 'القيمة',
  group: 'المجموعة',
  joining_date: 'تاريخ الانضمام',
};

// Format values cleanly without exposing technical identifiers
// Format values cleanly without exposing technical identifiers or raw ISO timestamps
const formatValue = (val) => {
  if (val === null || val === undefined || val === '') return '—';
  if (typeof val === 'boolean') return val ? 'نعم (مفعل)' : 'لا (معطل)';
  if (isIsoDateString(val)) {
    return formatAppDate(val, locale.value);
  }
  if (typeof val === 'object') return '—';
  return String(val);
};

// Compute Diff for Selected Item ONLY on update events with actual modified fields
const diffRows = computed(() => {
  if (!selectedItem.value || selectedItem.value.event !== 'updated' || !selectedItem.value.properties) {
    return [];
  }
  const props = selectedItem.value.properties;
  const attributes = props.attributes || {};
  const old = props.old || {};

  const allKeys = Array.from(new Set([...Object.keys(attributes), ...Object.keys(old)]));

  return allKeys
    .filter((key) => {
      // Exclude internal technical timestamps or IDs
      if (['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'].includes(key)) {
        return false;
      }
      const oldVal = old[key];
      const newVal = attributes[key];
      return oldVal !== undefined && newVal !== undefined && String(oldVal) !== String(newVal);
    })
    .map((key) => ({
      field: key,
      label: fieldLabels[key] || key.replace(/_/g, ' '),
      oldValue: formatValue(old[key]),
      newValue: formatValue(attributes[key]),
    }));
});

const formatDate = (dateStr) => {
  return formatAppDate(dateStr, locale.value);
};

onMounted(() => {
  loadLogs();
  window.addEventListener('click', handleWindowClick);
});

onUnmounted(() => {
  window.removeEventListener('click', handleWindowClick);
});
</script>
