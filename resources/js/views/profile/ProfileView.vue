<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <BreadcrumbDefault :pageTitle="$t('profile.pageTitle')" />

    <!-- User Profile Hero Card Banner -->
    <div class="relative overflow-hidden rounded-3xl bg-[#0C1315] border border-gray-800 p-6 sm:p-8 text-white shadow-xl">
      <!-- Ambient Glow Accents -->
      <div class="absolute -top-24 -left-24 h-56 w-56 rounded-full bg-[#00C896]/15 blur-3xl pointer-events-none"></div>
      <div class="absolute -bottom-24 -right-24 h-56 w-56 rounded-full bg-[#00C896]/10 blur-3xl pointer-events-none"></div>

      <div class="relative flex flex-col sm:flex-row items-center sm:items-start justify-between gap-6">
        <div class="flex flex-col sm:flex-row items-center gap-6 text-center sm:text-right" :class="$i18n.locale === 'ar' ? 'sm:text-right' : 'sm:text-left'">
          <!-- Avatar with camera upload overlay -->
          <div class="relative group cursor-pointer shrink-0" @click="triggerFileInput" title="انقر لتغيير الصورة الشخصية">
            <div class="relative h-28 w-28 rounded-3xl overflow-hidden ring-4 ring-[#00C896]/40 shadow-2xl bg-[#0C1315]">
              <img
                :src="currentAvatarUrl"
                @error="handleAvatarError"
                alt="Avatar"
                class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
              />
              <div class="absolute inset-0 bg-black/50 backdrop-blur-xs flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="h-6 w-6 text-[#00C896]" />
                <span class="text-[10px] font-bold text-white mt-1">تغيير الصورة</span>
              </div>
              <div v-if="uploadingAvatar" class="absolute inset-0 bg-black/75 flex flex-col items-center justify-center gap-1.5 z-10">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-[#00C896] border-t-transparent"></div>
                <span class="text-[9px] font-bold text-[#00C896]">جاري الرفع...</span>
              </div>
            </div>

            <!-- Floating Action Button -->
            <button
              type="button"
              class="absolute -bottom-1.5 -left-1.5 flex h-8 w-8 items-center justify-center rounded-xl bg-[#00C896] text-[#0C1315] shadow-lg hover:bg-[#00B386] transition-transform hover:scale-110"
            >
              <Camera class="h-4 w-4" />
            </button>

            <input
              ref="fileInputRef"
              type="file"
              accept="image/jpeg,image/png,image/webp,image/jpg"
              class="hidden"
              @change="handleFileSelected"
            />
          </div>

          <!-- User Info Headline -->
          <div>
            <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2.5 mb-1.5">
              <h1 class="text-xl sm:text-2xl font-black text-white">
                {{ authStore.user?.name }}
              </h1>
              <span class="inline-flex items-center gap-1 rounded-full bg-[#00C896]/15 border border-[#00C896]/30 px-2.5 py-0.5 text-xs font-bold text-[#00C896]">
                <span class="h-1.5 w-1.5 rounded-full bg-[#00C896]"></span>
                {{ $t('common.active') }}
              </span>
            </div>

            <p class="text-xs text-gray-300 font-mono">
              {{ authStore.user?.email }}
            </p>

            <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-2">
              <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1 text-xs font-bold text-gray-200">
                <Tag class="h-3.5 w-3.5 text-amber-400" />
                <span>{{ authStore.user?.job_title || 'Owner' }}</span>
              </span>

              <span
                v-for="role in authStore.roles"
                :key="role"
                class="rounded-xl bg-[#00C896]/15 px-3 py-1 text-xs font-bold text-[#00C896] border border-[#00C896]/30"
              >
                {{ role }}
              </span>
            </div>
          </div>
        </div>

        <!-- Quick Upload Button in Banner -->
        <button
          type="button"
          @click="triggerFileInput"
          :disabled="uploadingAvatar"
          class="flex items-center gap-2 rounded-2xl bg-[#00C896] hover:bg-[#00B386] px-5 py-2.5 text-xs font-black text-[#0C1315] shadow-lg shadow-[#00C896]/20 transition-all cursor-pointer"
        >
          <Upload class="h-4 w-4" />
          <span>{{ uploadingAvatar ? $t('profile.uploadingAvatar') : $t('profile.avatarUpload') }}</span>
        </button>
      </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Left (or Right in RTL) Form Column -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Tabs Bar -->
        <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-800 shadow-xs">
          <button
            type="button"
            @click="activeTab = 'info'"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            :class="activeTab === 'info' ? 'bg-[#00C896] text-[#0C1315] shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
          >
            <User class="h-4 w-4" />
            <span>{{ $t('profile.personalInfo') }}</span>
          </button>

          <button
            type="button"
            @click="activeTab = 'security'"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            :class="activeTab === 'security' ? 'bg-[#00C896] text-[#0C1315] shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
          >
            <KeyRound class="h-4 w-4" />
            <span>{{ $t('profile.security') }}</span>
          </button>

          <button
            type="button"
            @click="activeTab = 'preferences'"
            class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl text-xs font-bold transition-all cursor-pointer"
            :class="activeTab === 'preferences' ? 'bg-[#00C896] text-[#0C1315] shadow-sm' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/60'"
          >
            <Palette class="h-4 w-4" />
            <span>{{ $t('profile.preferences') }}</span>
          </button>
        </div>

        <!-- 1. TAB: Personal Information -->
        <div
          v-if="activeTab === 'info'"
          class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-800 shadow-xs space-y-6"
          :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
        >
          <div class="flex items-center gap-2.5 pb-4 border-b border-gray-100 dark:border-gray-700">
            <User class="h-5 w-5 text-[#00C896]" />
            <h2 class="text-sm font-black text-gray-900 dark:text-white">
              {{ $t('profile.personalInfo') }}
            </h2>
          </div>

          <form @submit.prevent="handleSaveProfile" class="space-y-5">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Full Name -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.name') }} <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    type="text"
                    v-model="profileForm.name"
                    required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                    :class="$i18n.locale === 'ar' ? 'pr-9' : 'pl-9'"
                  />
                  <User
                    class="absolute top-2.5 h-4 w-4 text-gray-400 pointer-events-none"
                    :class="$i18n.locale === 'ar' ? 'right-3' : 'left-3'"
                  />
                </div>
                <p v-if="profileErrors.name" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.name[0] }}
                </p>
              </div>

              <!-- Email Address -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.email') }} <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    type="email"
                    v-model="profileForm.email"
                    required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
                    :class="$i18n.locale === 'ar' ? 'pr-9' : 'pl-9'"
                    dir="ltr"
                  />
                  <Mail
                    class="absolute top-2.5 h-4 w-4 text-gray-400 pointer-events-none"
                    :class="$i18n.locale === 'ar' ? 'right-3' : 'left-3'"
                  />
                </div>
                <p v-if="profileErrors.email" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.email[0] }}
                </p>
              </div>

              <!-- Phone Number -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.phone') }}
                </label>
                <div class="relative">
                  <input
                    type="tel"
                    v-model="profileForm.phone"
                    placeholder="010xxxxxxxx"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
                    :class="$i18n.locale === 'ar' ? 'pr-9' : 'pl-9'"
                    dir="ltr"
                  />
                  <Phone
                    class="absolute top-2.5 h-4 w-4 text-gray-400 pointer-events-none"
                    :class="$i18n.locale === 'ar' ? 'right-3' : 'left-3'"
                  />
                </div>
                <p v-if="profileErrors.phone" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.phone[0] }}
                </p>
              </div>

              <!-- Job Title -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.jobTitle') }}
                </label>
                <div class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-100/80 dark:border-gray-700 dark:bg-gray-900/40 text-xs font-bold text-gray-700 dark:text-gray-300">
                  <Tag class="h-4 w-4 text-amber-500 shrink-0" />
                  <span>{{ authStore.user?.job_title || 'Owner' }}</span>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
              <button
                type="submit"
                :disabled="savingProfile"
                class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-6 py-2.5 text-xs font-black text-[#0C1315] disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20 cursor-pointer"
              >
                <span v-if="savingProfile" class="h-4 w-4 animate-spin rounded-full border-2 border-[#0C1315] border-t-transparent"></span>
                <Check v-else class="h-4 w-4" />
                <span>{{ $t('profile.saveProfile') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- 2. TAB: Security & Password -->
        <div
          v-else-if="activeTab === 'security'"
          class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-800 shadow-xs space-y-6"
          :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
        >
          <div class="flex items-center gap-2.5 pb-4 border-b border-gray-100 dark:border-gray-700">
            <KeyRound class="h-5 w-5 text-[#00C896]" />
            <h2 class="text-sm font-black text-gray-900 dark:text-white">
              {{ $t('profile.security') }}
            </h2>
          </div>

          <div class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 flex items-start gap-3 text-xs">
            <ShieldAlert class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
            <div class="text-amber-800 dark:text-amber-300 text-[11px] leading-relaxed">
              احرص على استخدام كلمة مرور قوية وغير مكررة مع تفعيل مزيج من الحروف والأرقام لضمان أعلى مستويات الأمان لمنظومة شركتك.
            </div>
          </div>

          <form @submit.prevent="handleChangePassword" class="space-y-4">
            <!-- Current Password -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('profile.currentPassword') }} <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  :type="showCurrentPass ? 'text' : 'password'"
                  v-model="passwordForm.current_password"
                  required
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                  :class="$i18n.locale === 'ar' ? 'pl-10' : 'pr-10'"
                />
                <button
                  type="button"
                  @click="showCurrentPass = !showCurrentPass"
                  class="absolute top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                  :class="$i18n.locale === 'ar' ? 'left-3' : 'right-3'"
                >
                  <EyeOff v-if="showCurrentPass" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>
              <p v-if="passwordErrors.current_password" class="mt-1 text-[11px] font-bold text-rose-500">
                {{ passwordErrors.current_password[0] }}
              </p>
            </div>

            <!-- New Password -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('profile.newPassword') }} <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  :type="showNewPass ? 'text' : 'password'"
                  v-model="passwordForm.new_password"
                  required
                  minlength="8"
                  :placeholder="$t('profile.passwordMinLength')"
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                  :class="$i18n.locale === 'ar' ? 'pl-10' : 'pr-10'"
                />
                <button
                  type="button"
                  @click="showNewPass = !showNewPass"
                  class="absolute top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                  :class="$i18n.locale === 'ar' ? 'left-3' : 'right-3'"
                >
                  <EyeOff v-if="showNewPass" class="h-4 w-4" />
                  <Eye v-else class="h-4 w-4" />
                </button>
              </div>
              <p v-if="passwordErrors.new_password" class="mt-1 text-[11px] font-bold text-rose-500">
                {{ passwordErrors.new_password[0] }}
              </p>
            </div>

            <!-- Confirm Password -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                {{ $t('profile.confirmPassword') }} <span class="text-rose-500">*</span>
              </label>
              <div class="relative">
                <input
                  type="password"
                  v-model="passwordForm.new_password_confirmation"
                  required
                  minlength="8"
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                />
              </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex justify-end">
              <button
                type="submit"
                :disabled="savingPassword"
                class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-6 py-2.5 text-xs font-black text-[#0C1315] disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20 cursor-pointer"
              >
                <span v-if="savingPassword" class="h-4 w-4 animate-spin rounded-full border-2 border-[#0C1315] border-t-transparent"></span>
                <KeyRound v-else class="h-4 w-4" />
                <span>{{ $t('profile.savePassword') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- 3. TAB: Appearance & Preferences -->
        <div
          v-else-if="activeTab === 'preferences'"
          class="rounded-3xl border border-gray-200 bg-white p-6 sm:p-8 dark:border-gray-800 dark:bg-gray-800 shadow-xs space-y-6"
          :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
        >
          <div class="flex items-center gap-2.5 pb-4 border-b border-gray-100 dark:border-gray-700">
            <Palette class="h-5 w-5 text-[#00C896]" />
            <h2 class="text-sm font-black text-gray-900 dark:text-white">
              {{ $t('profile.preferences') }}
            </h2>
          </div>

          <!-- Theme Preference -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
              {{ $t('profile.themePreference') }}
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Light Card -->
              <button
                type="button"
                @click="themeStore.setDark(false)"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                :class="!themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <div class="flex items-center gap-3.5">
                  <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 border border-amber-200/80 shadow-xs">
                    <Sun class="h-6 w-6" />
                  </div>
                  <div>
                    <span class="block text-xs font-bold text-gray-900 dark:text-white">{{ $t('profile.themeLight') }}</span>
                    <span class="text-[11px] text-gray-400">واجهة مضيئة واضحة</span>
                  </div>
                </div>
                <div v-if="!themeStore.isDark" class="flex h-6 w-6 items-center justify-center rounded-full bg-[#00C896] text-[#0C1315] shadow-xs">
                  <Check class="h-4 w-4 stroke-[3]" />
                </div>
              </button>

              <!-- Dark Card -->
              <button
                type="button"
                @click="themeStore.setDark(true)"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                :class="themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <div class="flex items-center gap-3.5">
                  <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#0C1315] text-[#00C896] border border-gray-800 shadow-xs">
                    <Moon class="h-6 w-6" />
                  </div>
                  <div>
                    <span class="block text-xs font-bold text-gray-900 dark:text-white">{{ $t('profile.themeDark') }}</span>
                    <span class="text-[11px] text-gray-400">نمط داكن أنيق ومريح للعين</span>
                  </div>
                </div>
                <div v-if="themeStore.isDark" class="flex h-6 w-6 items-center justify-center rounded-full bg-[#00C896] text-[#0C1315] shadow-xs">
                  <Check class="h-4 w-4 stroke-[3]" />
                </div>
              </button>
            </div>
          </div>

          <!-- Language Preference -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
              {{ $t('profile.languagePreference') }}
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <button
                type="button"
                @click="setLanguage('ar')"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                :class="$i18n.locale === 'ar' ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400 font-bold text-xs">
                    ع
                  </div>
                  <div>
                    <span class="block text-xs font-bold text-gray-900 dark:text-white">العربية</span>
                    <span class="text-[11px] text-gray-400">واجهة من اليمين لليسار (RTL)</span>
                  </div>
                </div>
                <Check v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-[#00C896]" />
              </button>

              <button
                type="button"
                @click="setLanguage('en')"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-left"
                :class="$i18n.locale === 'en' ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'"
              >
                <div class="flex items-center gap-3">
                  <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 font-bold text-xs font-mono">
                    EN
                  </div>
                  <div>
                    <span class="block text-xs font-bold text-gray-900 dark:text-white">English</span>
                    <span class="text-[11px] text-gray-400">Left-to-Right layout (LTR)</span>
                  </div>
                </div>
                <Check v-if="$i18n.locale === 'en'" class="h-4 w-4 text-[#00C896]" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right (or Left in RTL) Sidebar Metadata Card -->
      <div class="space-y-6">
        <!-- Account Overview Card -->
        <div
          class="rounded-3xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-800 shadow-xs"
          :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
        >
          <h3 class="text-sm font-black text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <Building2 class="h-4 w-4 text-[#00C896]" />
            <span>{{ $t('profile.tenantCompany') }}</span>
          </h3>

          <div class="space-y-3 text-xs">
            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
              <span class="text-gray-400 block text-[11px]">{{ $t('settings.companyRegisteredName') }}</span>
              <span class="font-bold text-gray-900 dark:text-white mt-0.5 block">{{ authStore.tenant?.name || $t('common.system') }}</span>
            </div>

            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
              <span class="text-gray-400 block text-[11px]">{{ $t('settings.companyCode') }}</span>
              <span class="font-mono font-bold text-[#00A87E] dark:text-[#00C896] mt-0.5 block">{{ authStore.tenant?.company_code || 'SARH-DEMO' }}</span>
            </div>

            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
              <span class="text-gray-400 block text-[11px]">{{ $t('profile.rolesAssigned') }}</span>
              <div class="mt-1.5 flex flex-wrap gap-1">
                <span
                  v-for="role in authStore.roles"
                  :key="role"
                  class="rounded-md bg-[#00C896]/10 px-2 py-0.5 text-[10px] font-bold text-[#00A87E] dark:text-[#00C896]"
                >
                  {{ role }}
                </span>
              </div>
            </div>

            <div class="p-3 rounded-2xl bg-gray-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-800">
              <span class="text-gray-400 block text-[11px]">{{ $t('settings.isolatedDb') }}</span>
              <span class="font-mono font-bold text-purple-600 dark:text-purple-400 mt-0.5 block">{{ authStore.tenant?.database_name || 'sarh_tenant_1' }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  User,
  Mail,
  Phone,
  Tag,
  KeyRound,
  Palette,
  Camera,
  Upload,
  Eye,
  EyeOff,
  Sun,
  Moon,
  Check,
  Building2,
  ShieldAlert,
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useThemeStore } from '../../stores/theme';
import { useNotificationStore } from '../../stores/notification';
import { setLanguage } from '../../i18n';
import BreadcrumbDefault from '../../components/tailadmin/BreadcrumbDefault.vue';

const authStore = useAuthStore();
const themeStore = useThemeStore();
const notificationStore = useNotificationStore();
const { t } = useI18n();

const activeTab = ref('info');
const fileInputRef = ref(null);
const localPreviewUrl = ref(null);

// Generate localized SVG avatar fallback with emerald color
const generateSvgFallback = (name) => {
  const initial = (name || 'U').trim().charAt(0);
  return `data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 128 128" width="128" height="128"><rect width="128" height="128" rx="36" fill="%230C1315"/><rect x="4" y="4" width="120" height="120" rx="32" fill="none" stroke="%2300C896" stroke-width="3" stroke-opacity="0.5"/><text x="50%" y="54%" font-family="Cairo, Outfit, sans-serif" font-weight="900" font-size="54" fill="%2300C896" dominant-baseline="middle" text-anchor="middle">${encodeURIComponent(initial)}</text></svg>`;
};

const currentAvatarUrl = computed(() => {
  if (localPreviewUrl.value) {
    return localPreviewUrl.value;
  }
  if (authStore.user?.avatar_url && !authStore.user.avatar_url.includes('ui-avatars.com')) {
    return authStore.user.avatar_url;
  }
  return generateSvgFallback(authStore.user?.name);
});

const handleAvatarError = (e) => {
  if (!localPreviewUrl.value) {
    e.target.src = generateSvgFallback(authStore.user?.name);
  }
};

// Tab 1 state
const profileForm = ref({
  name: '',
  email: '',
  phone: '',
});
const profileErrors = ref({});
const savingProfile = ref(false);
const uploadingAvatar = ref(false);

// Tab 2 state
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});
const passwordErrors = ref({});
const savingPassword = ref(false);
const showCurrentPass = ref(false);
const showNewPass = ref(false);

const populateProfileData = () => {
  if (authStore.user) {
    profileForm.value = {
      name: authStore.user.name || '',
      email: authStore.user.email || '',
      phone: authStore.user.phone || '',
    };
  }
};

const triggerFileInput = () => {
  fileInputRef.value?.click();
};

const handleFileSelected = async (e) => {
  const file = e.target.files?.[0];
  if (!file) return;

  if (file.size > 4 * 1024 * 1024) {
    notificationStore.error('حجم الصورة كبير جداً، الحد الأقصى 4 ميجابايت.');
    return;
  }

  // Instant local preview
  const objectUrl = URL.createObjectURL(file);
  localPreviewUrl.value = objectUrl;

  uploadingAvatar.value = true;
  const res = await authStore.uploadAvatar(file);
  uploadingAvatar.value = false;

  if (res.success) {
    localPreviewUrl.value = res.avatar_url || authStore.user?.avatar_url || objectUrl;
    notificationStore.success(res.message || t('profile.avatarSuccess'));
  } else {
    localPreviewUrl.value = null;
    notificationStore.error(res.message || 'فشل رفع الصورة الشخصية.');
  }

  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const handleSaveProfile = async () => {
  savingProfile.value = true;
  profileErrors.value = {};

  const res = await authStore.updateProfile(profileForm.value);
  savingProfile.value = false;

  if (res.success) {
    notificationStore.success(res.message || t('profile.profileUpdated'));
  } else {
    if (res.errors) {
      profileErrors.value = res.errors;
    }
    notificationStore.error(res.message);
  }
};

const handleChangePassword = async () => {
  savingPassword.value = true;
  passwordErrors.value = {};

  const res = await authStore.changePassword(passwordForm.value);
  savingPassword.value = false;

  if (res.success) {
    notificationStore.success(res.message || t('profile.passwordUpdated'));
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    };
  } else {
    if (res.errors) {
      passwordErrors.value = res.errors;
    }
    notificationStore.error(res.message);
  }
};

onMounted(() => {
  populateProfileData();
});
</script>
