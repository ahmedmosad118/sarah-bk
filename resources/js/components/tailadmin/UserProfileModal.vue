<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[999] flex items-center justify-center p-3 sm:p-6 overflow-y-auto bg-gray-950/75 backdrop-blur-xs transition-all duration-200"
      :dir="$i18n.locale === 'ar' ? 'rtl' : 'ltr'"
    >
    <div
      class="relative w-full max-w-2xl rounded-3xl bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 shadow-2xl overflow-hidden flex flex-col max-h-[92vh] animate-in fade-in zoom-in-95 duration-200"
      ref="modalCardRef"
    >
      <!-- Modal Header Banner -->
      <div class="relative bg-gradient-to-r from-[#0C1315] via-[#112224] to-[#0C1315] p-6 text-white border-b border-gray-800">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#00C896]/20 text-[#00C896] border border-[#00C896]/30">
              <UserCog class="h-5 w-5" />
            </div>
            <div>
              <h2 class="text-base font-black text-white">
                {{ $t('profile.modalTitle') }}
              </h2>
              <p class="text-[11px] text-gray-300">
                {{ authStore.user?.email }} • {{ authStore.tenant?.name || $t('common.system') }}
              </p>
            </div>
          </div>

          <button
            @click="$emit('close')"
            class="rounded-xl p-2 text-gray-400 hover:bg-white/10 hover:text-white transition-colors"
          >
            <X class="h-5 w-5" />
          </button>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 mt-6 overflow-x-auto no-scrollbar">
          <button
            type="button"
            @click="activeTab = 'info'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="activeTab === 'info' ? 'bg-[#00C896] text-[#0C1315] shadow-sm shadow-[#00C896]/30' : 'bg-white/5 text-gray-300 hover:bg-white/10'"
          >
            <User class="h-4 w-4" />
            <span>{{ $t('profile.personalInfo') }}</span>
          </button>

          <button
            type="button"
            @click="activeTab = 'security'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="activeTab === 'security' ? 'bg-[#00C896] text-[#0C1315] shadow-sm shadow-[#00C896]/30' : 'bg-white/5 text-gray-300 hover:bg-white/10'"
          >
            <KeyRound class="h-4 w-4" />
            <span>{{ $t('profile.security') }}</span>
          </button>

          <button
            type="button"
            @click="activeTab = 'preferences'"
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all shrink-0"
            :class="activeTab === 'preferences' ? 'bg-[#00C896] text-[#0C1315] shadow-sm shadow-[#00C896]/30' : 'bg-white/5 text-gray-300 hover:bg-white/10'"
          >
            <Palette class="h-4 w-4" />
            <span>{{ $t('profile.preferences') }}</span>
          </button>
        </div>
      </div>

      <!-- Modal Body (Scrollable) -->
      <div class="p-6 overflow-y-auto flex-1 space-y-6">
        <!-- 1. TAB: Personal Info & Avatar -->
        <div v-if="activeTab === 'info'" class="space-y-6">
          <!-- Avatar Section -->
          <div class="flex flex-col sm:flex-row items-center gap-5 p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-100 dark:border-gray-800">
            <div class="relative group cursor-pointer" @click="triggerFileInput">
              <img
                :src="authStore.user?.avatar_url || defaultAvatar"
                alt="Avatar"
                class="h-20 w-20 rounded-2xl object-cover ring-4 ring-[#00C896]/30 shadow-md transition-all group-hover:opacity-80"
              />
              <div class="absolute inset-0 rounded-2xl bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity">
                <Camera class="h-6 w-6 text-white" />
              </div>
              <div v-if="uploadingAvatar" class="absolute inset-0 rounded-2xl bg-black/60 flex items-center justify-center">
                <div class="h-6 w-6 animate-spin rounded-full border-2 border-[#00C896] border-t-transparent"></div>
              </div>
              <input
                ref="fileInputRef"
                type="file"
                accept="image/jpeg,image/png,image/webp,image/jpg"
                class="hidden"
                @change="handleFileSelected"
              />
            </div>

            <div class="flex-1 text-center sm:text-right" :class="$i18n.locale === 'ar' ? 'sm:text-right' : 'sm:text-left'">
              <h4 class="text-xs font-bold text-gray-900 dark:text-white">
                {{ $t('profile.avatarUpload') }}
              </h4>
              <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                {{ $t('profile.avatarHint') }}
              </p>
              <div class="mt-2.5 flex flex-wrap items-center justify-center sm:justify-start gap-1.5">
                <button
                  type="button"
                  @click="triggerFileInput"
                  :disabled="uploadingAvatar"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700/60 text-xs font-bold text-gray-700 dark:text-gray-200 transition-colors"
                >
                  <Upload class="h-3.5 w-3.5 text-[#00C896]" />
                  <span>{{ uploadingAvatar ? $t('profile.uploadingAvatar') : $t('profile.avatarUpload') }}</span>
                </button>
                <span
                  v-for="role in authStore.roles"
                  :key="role"
                  class="rounded-md bg-[#00C896]/10 px-2 py-0.5 text-[10px] font-bold text-[#00A87E] dark:text-[#00C896]"
                >
                  {{ role }}
                </span>
              </div>
            </div>
          </div>

          <!-- Profile Form -->
          <form @submit.prevent="handleSaveProfile" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <!-- Name -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.name') }} <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    type="text"
                    v-model="profileForm.name"
                    required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors"
                  />
                </div>
                <p v-if="profileErrors.name" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.name[0] }}
                </p>
              </div>

              <!-- Email -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.email') }} <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                  <input
                    type="email"
                    v-model="profileForm.email"
                    required
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
                    dir="ltr"
                  />
                </div>
                <p v-if="profileErrors.email" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.email[0] }}
                </p>
              </div>

              <!-- Phone -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.phone') }}
                </label>
                <div class="relative">
                  <input
                    type="tel"
                    v-model="profileForm.phone"
                    placeholder="010xxxxxxxx"
                    class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors text-left"
                    dir="ltr"
                  />
                </div>
                <p v-if="profileErrors.phone" class="mt-1 text-[11px] font-bold text-rose-500">
                  {{ profileErrors.phone[0] }}
                </p>
              </div>

              <!-- Job Title (Readonly info) -->
              <div>
                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
                  {{ $t('profile.jobTitle') }}
                </label>
                <div class="flex items-center gap-2 p-2.5 rounded-xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-gray-800/50 text-xs font-bold text-gray-700 dark:text-gray-300">
                  <Tag class="h-4 w-4 text-amber-500 shrink-0" />
                  <span>{{ authStore.user?.job_title || '—' }}</span>
                </div>
              </div>
            </div>

            <!-- Submit Profile Button -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
              <button
                type="submit"
                :disabled="savingProfile"
                class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-5 py-2.5 text-xs font-black text-[#0C1315] disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20"
              >
                <span v-if="savingProfile" class="h-4 w-4 animate-spin rounded-full border-2 border-[#0C1315] border-t-transparent"></span>
                <span>{{ $t('profile.saveProfile') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- 2. TAB: Security & Password -->
        <div v-else-if="activeTab === 'security'" class="space-y-4">
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
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors"
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
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors"
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
                  class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 px-3.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-900/50 dark:text-white dark:focus:border-[#00C896] transition-colors"
                />
              </div>
            </div>

            <!-- Submit Password Button -->
            <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex justify-end">
              <button
                type="submit"
                :disabled="savingPassword"
                class="flex items-center gap-2 rounded-xl bg-[#00C896] hover:bg-[#00B386] px-5 py-2.5 text-xs font-black text-[#0C1315] disabled:opacity-50 transition-colors shadow-sm shadow-[#00C896]/20"
              >
                <span v-if="savingPassword" class="h-4 w-4 animate-spin rounded-full border-2 border-[#0C1315] border-t-transparent"></span>
                <span>{{ $t('profile.savePassword') }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- 3. TAB: Preferences & Theme -->
        <div v-else-if="activeTab === 'preferences'" class="space-y-6">
          <!-- Dark / Light Mode Selector -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
              {{ $t('profile.themePreference') }}
            </label>
            <div class="grid grid-cols-2 gap-4">
              <!-- Light Option -->
              <button
                type="button"
                @click="setTheme(false)"
                class="flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all text-center"
                :class="!themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 text-gray-900 dark:text-white shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
              >
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-500 border border-amber-200">
                  <Sun class="h-6 w-6" />
                </div>
                <div>
                  <span class="block text-xs font-bold">{{ $t('profile.themeLight') }}</span>
                  <span class="text-[11px] text-gray-400">Clean & Bright</span>
                </div>
                <div v-if="!themeStore.isDark" class="flex h-5 w-5 items-center justify-center rounded-full bg-[#00C896] text-[#0C1315]">
                  <Check class="h-3.5 w-3.5 stroke-[3]" />
                </div>
              </button>

              <!-- Dark Option -->
              <button
                type="button"
                @click="setTheme(true)"
                class="flex flex-col items-center gap-3 p-4 rounded-2xl border-2 transition-all text-center"
                :class="themeStore.isDark ? 'border-[#00C896] bg-[#00C896]/5 text-gray-900 dark:text-white shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
              >
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gray-900 text-[#00C896] border border-gray-800">
                  <Moon class="h-6 w-6" />
                </div>
                <div>
                  <span class="block text-xs font-bold">{{ $t('profile.themeDark') }}</span>
                  <span class="text-[11px] text-gray-400">Sleek & Deep Slate</span>
                </div>
                <div v-if="themeStore.isDark" class="flex h-5 w-5 items-center justify-center rounded-full bg-[#00C896] text-[#0C1315]">
                  <Check class="h-3.5 w-3.5 stroke-[3]" />
                </div>
              </button>
            </div>
          </div>

          <!-- Language Selector -->
          <div>
            <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-3">
              {{ $t('profile.languagePreference') }}
            </label>
            <div class="grid grid-cols-2 gap-4">
              <button
                type="button"
                @click="changeLocale('ar')"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all text-right"
                :class="$i18n.locale === 'ar' ? 'border-[#00C896] bg-[#00C896]/5 text-gray-900 dark:text-white shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
              >
                <div>
                  <span class="block text-xs font-bold">العربية</span>
                  <span class="text-[11px] text-gray-400 font-sans">RTL • اللغة الأساسية</span>
                </div>
                <Check v-if="$i18n.locale === 'ar'" class="h-4 w-4 text-[#00C896]" />
              </button>

              <button
                type="button"
                @click="changeLocale('en')"
                class="flex items-center justify-between p-4 rounded-2xl border-2 transition-all text-left"
                :class="$i18n.locale === 'en' ? 'border-[#00C896] bg-[#00C896]/5 text-gray-900 dark:text-white shadow-sm' : 'border-gray-200 dark:border-gray-800 hover:border-gray-300 dark:hover:border-gray-700'"
              >
                <div>
                  <span class="block text-xs font-bold">English</span>
                  <span class="text-[11px] text-gray-400 font-sans">LTR • English Interface</span>
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
import { ref, watch, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';
import {
  UserCog,
  User,
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
const defaultAvatar = 'https://ui-avatars.com/api/?name=User&background=00C896&color=0C1315';

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

  uploadingAvatar.value = true;
  const res = await authStore.uploadAvatar(file);
  uploadingAvatar.value = false;

  if (res.success) {
    notificationStore.success(res.message || t('profile.avatarSuccess'));
  } else {
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

const setTheme = (isDark) => {
  themeStore.setDark(isDark);
};

const changeLocale = (locale) => {
  setLanguage(locale);
};

onMounted(() => {
  populateProfileData();
});
</script>
