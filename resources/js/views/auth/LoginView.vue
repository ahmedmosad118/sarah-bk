<template>
  <div>
    <!-- Title and Subtitle -->
    <div class="mb-6" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
      <h2 class="text-2xl font-black text-gray-900 dark:text-white">
        {{ $t('auth.loginTitle') }}
      </h2>
      <p class="text-xs font-medium text-gray-500 dark:text-gray-400 mt-1">
        {{ $t('auth.loginSubtitle') }}
      </p>
    </div>

    <!-- 1-Click Demo Login Helper Pill -->
    <button
      type="button"
      @click="fillDemoCredentials"
      class="w-full mb-6 flex items-center justify-center gap-2 p-2.5 rounded-2xl border border-[#00C896]/30 bg-[#00C896]/10 hover:bg-[#00C896]/15 dark:border-[#00C896]/30 dark:bg-[#00C896]/10 dark:hover:bg-[#00C896]/20 text-[#00A87E] dark:text-[#00C896] text-xs font-bold transition-all shadow-xs"
    >
      <Zap class="h-4 w-4 text-amber-500" />
      <span>{{ $t('auth.demoLoginPill') }}</span>
    </button>

    <!-- Error Alert -->
    <div
      v-if="errorMessage"
      class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-semibold text-rose-800 dark:border-rose-900/40 dark:bg-rose-950/30 dark:text-rose-300 flex items-start gap-2.5 animate-in fade-in duration-150"
      :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'"
    >
      <AlertTriangle class="h-4 w-4 text-rose-500 shrink-0 mt-0.5" />
      <span>{{ errorMessage }}</span>
    </div>

    <form @submit.prevent="handleLogin" class="space-y-4" :class="$i18n.locale === 'ar' ? 'text-right' : 'text-left'">
      <!-- Email -->
      <div>
        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1.5">
          {{ $t('auth.email') }} <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <input
            type="email"
            v-model="form.email"
            required
            placeholder="admin@company.com"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-[#00C896] transition-colors"
            :class="$i18n.locale === 'ar' ? 'pr-10 pl-4' : 'pl-10 pr-4'"
          />
          <span
            class="absolute top-2.5 text-gray-400 pointer-events-none"
            :class="$i18n.locale === 'ar' ? 'right-3.5' : 'left-3.5'"
          >
            <Mail class="h-4 w-4" />
          </span>
        </div>
      </div>

      <!-- Password -->
      <div>
        <div class="flex items-center justify-between mb-1.5">
          <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
            {{ $t('auth.password') }} <span class="text-rose-500">*</span>
          </label>
          <a href="#" class="text-[11px] font-semibold text-[#00C896] hover:underline">
            {{ $t('auth.forgotPassword') }}
          </a>
        </div>

        <div class="relative">
          <input
            :type="showPassword ? 'text' : 'password'"
            v-model="form.password"
            required
            placeholder="••••••••"
            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 text-xs font-medium text-gray-900 outline-hidden focus:border-[#00C896] focus:bg-white dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:focus:border-[#00C896] transition-colors"
            :class="$i18n.locale === 'ar' ? 'pr-10 pl-10' : 'pl-10 pr-10'"
          />
          <span
            class="absolute top-2.5 text-gray-400 pointer-events-none"
            :class="$i18n.locale === 'ar' ? 'right-3.5' : 'left-3.5'"
          >
            <Lock class="h-4 w-4" />
          </span>
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute top-2.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors"
            :class="$i18n.locale === 'ar' ? 'left-3.5' : 'right-3.5'"
            tabindex="-1"
          >
            <EyeOff v-if="showPassword" class="h-4 w-4" />
            <Eye v-else class="h-4 w-4" />
          </button>
        </div>
      </div>

      <!-- Remember Me -->
      <div class="flex items-center gap-2 pt-1">
        <input
          type="checkbox"
          id="remember"
          v-model="rememberMe"
          class="rounded-md border-gray-300 text-[#00C896] focus:ring-[#00C896]"
        />
        <label for="remember" class="text-xs font-medium text-gray-600 dark:text-gray-400 cursor-pointer">
          {{ $t('auth.rememberMe') }}
        </label>
      </div>

      <!-- Submit Button -->
      <button
        type="submit"
        :disabled="loading"
        class="w-full mt-2 flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-[#00C896] via-[#00B386] to-[#009973] py-3 text-xs font-bold text-white hover:from-[#00B386] hover:to-[#008060] disabled:opacity-50 transition-all shadow-md shadow-[#00C896]/25 cursor-pointer"
      >
        <span v-if="loading" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
        <LogIn v-else class="h-4 w-4" />
        <span>{{ $t('auth.loginButton') }}</span>
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { AlertTriangle, LogIn, Mail, Lock, Building2, Eye, EyeOff, Zap } from 'lucide-vue-next';
import { useAuthStore } from '../../stores/auth';
import { useNotificationStore } from '../../stores/notification';

const { t } = useI18n();
const authStore = useAuthStore();
const notificationStore = useNotificationStore();
const router = useRouter();

const form = ref({
  slug: '',
  email: '',
  password: '',
});

const showPassword = ref(false);
const rememberMe = ref(true);
const loading = ref(false);
const errorMessage = ref('');

onMounted(() => {
  const savedSlug = localStorage.getItem('sarh_tenant_slug');
  if (savedSlug) {
    form.value.slug = savedSlug;
  } else {
    const host = window.location.hostname;
    const parts = host.split('.');
    if (parts.length >= 2 && !['localhost', '127.0.0.1'].includes(host)) {
      form.value.slug = parts[0];
    }
  }
});

const fillDemoCredentials = () => {
  form.value.slug = 'demo';
  form.value.email = 'owner@sarh.app';
  form.value.password = 'password123';
};

const handleLogin = async () => {
  loading.value = true;
  errorMessage.value = '';

  const res = await authStore.login(form.value.email, form.value.password, form.value.slug);

  if (res.success) {
    notificationStore.success(t('auth.welcomeBack'));
    router.push('/dashboard');
  } else {
    errorMessage.value = res.message;
  }

  loading.value = false;
};
</script>
