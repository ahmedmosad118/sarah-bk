<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[999] flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-black/75 backdrop-blur-sm transition-all duration-200"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
      <div
        class="relative w-full max-w-2xl sm:max-w-3xl rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800/90 shadow-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200"
        ref="modalCardRef"
      >
        <!-- Modal Top Header Banner -->
        <div class="relative bg-gradient-to-r from-emerald-500/10 via-teal-500/5 to-emerald-500/10 dark:from-slate-900 dark:via-[#112325] dark:to-slate-900 border-b border-emerald-200/80 dark:border-emerald-500/20 p-6 text-gray-900 dark:text-white overflow-hidden">
          <!-- Subtle Emerald Ambient Glow -->
          <div class="absolute -top-24 -left-24 h-48 w-48 rounded-full bg-[#00C896]/15 dark:bg-[#00C896]/20 blur-3xl pointer-events-none"></div>
          <div class="absolute -bottom-24 -right-24 h-48 w-48 rounded-full bg-[#00C896]/10 dark:bg-[#00C896]/15 blur-3xl pointer-events-none"></div>

          <div class="relative flex items-center justify-between">
            <div class="flex items-center gap-3.5">
              <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#00C896]/20 text-[#00C896] border border-[#00C896]/30 shadow-inner">
                <UserCog class="h-5 w-5" />
              </div>
              <div>
                <h2 class="text-base font-black text-gray-900 dark:text-white tracking-wide">
                  {{ $t('profile.modalTitle') }}
                </h2>
                <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                  <span class="font-mono text-gray-600 dark:text-gray-300">{{ authStore.user?.email }}</span>
                  <span>•</span>
                  <span class="text-emerald-700 dark:text-[#00C896] font-bold">{{ authStore.tenant?.name || $t('common.system') }}</span>
                </div>
              </div>
            </div>

            <button
              @click="$emit('close')"
              class="rounded-xl p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-white/10 hover:text-gray-700 dark:hover:text-white transition-colors"
            >
              <X class="h-5 w-5" />
            </button>
          </div>

          <!-- Tab Navigation Bar -->
          <div class="relative flex items-center gap-2 mt-6 overflow-x-auto no-scrollbar pt-1">
            <button
              type="button"
              @click="activeTab = 'info'"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
              :class="activeTab === 'info' ? 'bg-[#00C896] text-gray-950 shadow-md shadow-[#00C896]/20' : 'bg-white/80 dark:bg-white/5 border border-gray-200/60 dark:border-transparent text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-white/10'"
            >
              <User class="h-4 w-4" />
              <span>{{ $t('profile.personalInfo') }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'security'"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
              :class="activeTab === 'security' ? 'bg-[#00C896] text-gray-950 shadow-md shadow-[#00C896]/20' : 'bg-white/80 dark:bg-white/5 border border-gray-200/60 dark:border-transparent text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-white/10'"
            >
              <KeyRound class="h-4 w-4" />
              <span>{{ $t('profile.security') }}</span>
            </button>

            <button
              type="button"
              @click="activeTab = 'preferences'"
              class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all shrink-0 cursor-pointer"
              :class="activeTab === 'preferences' ? 'bg-[#00C896] text-gray-950 shadow-md shadow-[#00C896]/20' : 'bg-white/80 dark:bg-white/5 border border-gray-200/60 dark:border-transparent text-gray-600 dark:text-gray-300 hover:bg-white dark:hover:bg-white/10'"
            >
              <Palette class="h-4 w-4" />
              <span>{{ $t('profile.preferences') }}</span>
            </button>
          </div>
        </div>

        <!-- Modal Body Container -->
        <div class="p-6 overflow-y-auto flex-1 space-y-6 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100">
          <!-- 1. TAB: Personal Information & Avatar -->
          <div v-if="activeTab === 'info'" class="space-y-6">
            <!-- Premium Avatar Hero Card -->
            <div class="relative overflow-hidden p-5 rounded-3xl bg-gray-50/80 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800/80 flex flex-col sm:flex-row items-center gap-5 transition-all">
              <!-- Avatar with upload trigger -->
              <div
                class="relative group cursor-pointer shrink-0"
                @click="triggerFileInput"
                title="اضغط لتغيير الصورة الشخصية"
              >
                <div class="relative h-24 w-24 rounded-3xl overflow-hidden ring-4 ring-[#00C896]/30 ring-offset-2 ring-offset-white dark:ring-offset-gray-900 shadow-lg bg-white dark:bg-slate-800">
                  <img
                    :src="currentAvatarUrl"
                    @error="handleAvatarImgError"
                    alt="User Avatar"
                    class="h-full w-full object-cover transition-all duration-200 group-hover:scale-105"
                  />
                  <!-- Hover Camera Overlay -->
                  <div class="absolute inset-0 bg-black/50 backdrop-blur-xs flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                    <Camera class="h-6 w-6 text-[#00C896]" />
                    <span class="text-[10px] font-bold text-white mt-1">تغيير الصورة</span>
                  </div>
                  <!-- Uploading Spinner -->
                  <div v-if="uploadingAvatar" class="absolute inset-0 bg-black/70 flex flex-col items-center justify-center gap-1.5 z-10">
                    <div class="h-6 w-6 animate-spin rounded-full border-2 border-[#00C896] border-t-transparent"></div>
                    <span class="text-[9px] font-bold text-[#00C896]">جاري الرفع</span>
                  </div>
                </div>

                <!-- Floating Camera Badge Button -->
                <button
                  type="button"
                  class="absolute -bottom-1 -left-1 flex h-8 w-8 items-center justify-center rounded-xl bg-[#00C896] text-gray-950 shadow-md hover:bg-[#00B386] transition-transform hover:scale-110"
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

              <!-- User Meta Details & Badges -->
              <div class="flex-1 text-center sm:text-right" :class="$i18n.locale === 'ar' ? 'sm:text-right' : 'sm:text-left'">
                <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2 mb-1">
                  <h3 class="text-sm font-black text-gray-900 dark:text-white">
                    {{ authStore.user?.name }}
                  </h3>
                  <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    {{ $t('common.active') }}
                  </span>
                </div>

                <p class="text-xs text-gray-500 dark:text-gray-400 font-mono">
                  {{ authStore.user?.email }}
                </p>

                <div class="mt-3 flex flex-wrap items-center justify-center sm:justify-start gap-2">
                  <button
                    type="button"
                    @click="triggerFileInput"
                    :disabled="uploadingAvatar"
                    class="flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-xs font-bold text-gray-700 dark:text-gray-200 transition-colors shadow-xs"
                  >
                    <Upload class="h-3.5 w-3.5 text-[#00C896]" />
                    <span>{{ uploadingAvatar ? $t('profile.uploadingAvatar') : $t('profile.avatarUpload') }}</span>
                  </button>

                  <span
                    v-for="role in authStore.roles"
                    :key="role"
                    class="rounded-xl bg-[#00C896]/10 px-2.5 py-1 text-[11px] font-bold text-[#00A87E] dark:text-[#00C896]"
                  >
                    {{ role }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Profile Form -->
            <form @submit.prevent="handleSaveProfile" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Name Field -->
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('profile.name') }} <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      type="text"
                      v-model="profileForm.name"
                      required
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
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

                <!-- Email Field -->
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('profile.email') }} <span class="text-rose-500">*</span>
                  </label>
                  <div class="relative">
                    <input
                      type="email"
                      v-model="profileForm.email"
                      required
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
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

                <!-- Phone Field -->
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('profile.phone') }}
                  </label>
                  <div class="relative">
                    <input
                      type="tel"
                      v-model="profileForm.phone"
                      placeholder="010xxxxxxxx"
                      class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
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

                <!-- Job Title (Readonly Pill) -->
                <div>
                  <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                    {{ $t('profile.jobTitle') }}
                  </label>
                  <div class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-100/80 dark:border-gray-700 dark:bg-gray-800/40 text-xs font-bold text-gray-700 dark:text-gray-300">
                    <Tag class="h-4 w-4 text-amber-500 shrink-0" />
                    <span class="truncate">{{ authStore.user?.job_title || 'Owner' }}</span>
                  </div>
                </div>
              </div>

              <!-- Submit Profile Button -->
              <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button
                  type="submit"
                  :disabled="savingProfile"
                  class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-6 py-2.5 text-xs font-black text-gray-950 disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20 cursor-pointer"
                >
                  <span v-if="savingProfile" class="h-4 w-4 animate-spin rounded-full border-2 border-gray-950 border-t-transparent"></span>
                  <Check v-else class="h-4 w-4" />
                  <span>{{ $t('profile.saveProfile') }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- 2. TAB: Security & Password -->
          <div v-else-if="activeTab === 'security'" class="space-y-4">
            <div class="p-4 rounded-2xl bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-900/40 flex items-start gap-3 text-xs">
              <ShieldAlert class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
              <div class="text-amber-800 dark:text-amber-300 text-[11px] leading-relaxed">
                احرص على استخدام كلمة مرور قوية تحتوي على أرقام وحروف ورموز بحد أدنى 8 خانات لضمان أمان حسابك في المنظومة.
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
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
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
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
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
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800/60 dark:text-white dark:focus:border-[#00C896] transition-colors"
                  />
                </div>
              </div>

              <!-- Submit Password Button -->
              <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
                <button
                  type="submit"
                  :disabled="savingPassword"
                  class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-6 py-2.5 text-xs font-black text-gray-950 disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20 cursor-pointer"
                >
                  <span v-if="savingPassword" class="h-4 w-4 animate-spin rounded-full border-2 border-gray-950 border-t-transparent"></span>
                  <KeyRound v-else class="h-4 w-4" />
                  <span>{{ $t('profile.savePassword') }}</span>
                </button>
              </div>
            </form>
          </div>

          <!-- 3. TAB: Appearance & Preferences -->
          <div v-else-if="activeTab === 'preferences'" class="space-y-6">
            <!-- Theme Selector -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
                {{ $t('profile.themePreference') }}
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Light Theme Card -->
                <button
                  type="button"
                  @click="themeStore.setDark(false)"
                  class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                  :class="!themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
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
                  <div v-if="!themeStore.isDark" class="flex h-6 w-6 items-center justify-center rounded-full bg-[#00C896] text-gray-950 shadow-xs">
                    <Check class="h-4 w-4 stroke-[3]" />
                  </div>
                </button>

                <!-- Dark Theme Card -->
                <button
                  type="button"
                  @click="themeStore.setDark(true)"
                  class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                  :class="themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
                >
                  <div class="flex items-center gap-3.5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900 text-[#00C896] border border-slate-700 shadow-xs">
                      <Moon class="h-6 w-6" />
                    </div>
                    <div>
                      <span class="block text-xs font-bold text-gray-900 dark:text-white">{{ $t('profile.themeDark') }}</span>
                      <span class="text-[11px] text-gray-400">نمط داكن أنيق ومريح للعين</span>
                    </div>
                  </div>
                  <div v-if="themeStore.isDark" class="flex h-6 w-6 items-center justify-center rounded-full bg-[#00C896] text-gray-950 shadow-xs">
                    <Check class="h-4 w-4 stroke-[3]" />
                  </div>
                </button>
              </div>
            </div>

            <!-- Language Selector -->
            <div>
              <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
                {{ $t('profile.languagePreference') }}
              </label>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button
                  type="button"
                  @click="changeLocale('ar')"
                  class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-right"
                  :class="$i18n.locale === 'ar' ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
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
                  @click="changeLocale('en')"
                  class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all cursor-pointer text-left"
                  :class="$i18n.locale === 'en' ? 'border-[#00C896] bg-[#00C896]/5 shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
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
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  UserCog,
  User,
  Mail,
  Phone,
  KeyRound,
  Palette,
  Camera,
  Upload,
  Tag,
  Eye,
  EyeOff,
  Sun,
  Moon,
  Check,
  X,
  ShieldAlert,
} from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useThemeStore } from '../../stores/theme';
import { useNotificationStore } from '../../stores/notification';
import { setLanguage } from '../../i18n';

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  initialTab: {
    type: String,
    default: 'info',
  },
});

const emit = defineEmits(['close']);

const authStore = useAuthStore();
const themeStore = useThemeStore();
const notificationStore = useNotificationStore();
const { t } = useI18n();

const activeTab = ref(props.initialTab || 'info');
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

const handleAvatarImgError = (e) => {
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

watch(
  () => props.show,
  (val) => {
    if (val) {
      activeTab.value = props.initialTab || 'info';
      localPreviewUrl.value = null;
      populateProfileData();
      profileErrors.value = {};
      passwordErrors.value = {};
      passwordForm.value = {
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
      };
    }
  }
);

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

  // Reset file input
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

const changeLocale = (locale) => {
  setLanguage(locale);
};

onMounted(() => {
  populateProfileData();
});
</script>
