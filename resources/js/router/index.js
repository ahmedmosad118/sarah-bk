import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

import AuthLayout from '../layouts/AuthLayout.vue';
import AppLayout from '../layouts/AppLayout.vue';

import LoginView from '../views/auth/LoginView.vue';
import DashboardView from '../views/dashboard/DashboardView.vue';
import UsersView from '../views/team/UsersView.vue';
import CustomersView from '../views/customers/CustomersView.vue';
import LeadsView from '../views/leads/LeadsView.vue';
import OpportunitiesView from '../views/opportunities/OpportunitiesView.vue';
import JobTitlesView from '../views/team/JobTitlesView.vue';
import RolesView from '../views/team/RolesView.vue';
import ActivityLogView from '../views/activities/ActivityLogView.vue';
import SettingsView from '../views/settings/SettingsView.vue';
import ProfileView from '../views/profile/ProfileView.vue';

const routes = [
  // Auth Public Routes
  {
    path: '/login',
    component: AuthLayout,
    children: [
      {
        path: '',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
      },
    ],
  },

  // Protected App Routes
  {
    path: '/',
    component: AppLayout,
    meta: { requiresAuth: true },
    children: [
      {
        path: '',
        redirect: '/dashboard',
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: DashboardView,
      },
      {
        path: 'profile',
        name: 'profile',
        component: ProfileView,
      },
      {
        path: 'customers',
        name: 'customers',
        component: CustomersView,
      },
      {
        path: 'leads',
        name: 'leads',
        component: LeadsView,
      },
      {
        path: 'opportunities',
        name: 'opportunities',
        component: OpportunitiesView,
      },
      {
        path: 'team/users',
        name: 'team.users',
        component: UsersView,
      },
      {
        path: 'team/job-titles',
        name: 'team.job-titles',
        component: JobTitlesView,
      },
      {
        path: 'team/roles',
        name: 'team.roles',
        component: RolesView,
      },
      {
        path: 'activities',
        name: 'activities',
        component: ActivityLogView,
      },
      {
        path: 'settings',
        name: 'settings',
        component: SettingsView,
      },
    ],
  },

  // Fallback
  {
    path: '/:pathMatch(.*)*',
    redirect: '/dashboard',
  },
];

// Determine dynamic base URL for subfolder deployments (e.g. /sarah-bk/public/ on XAMPP)
const getBasePath = () => {
  if (typeof window !== 'undefined' && window.SARH_BASE_URL) {
    try {
      const url = new URL(window.SARH_BASE_URL);
      return url.pathname || '/';
    } catch (e) {
      // Ignore
    }
  }

  if (typeof window !== 'undefined' && window.location.pathname.includes('/public')) {
    const idx = window.location.pathname.indexOf('/public');
    return window.location.pathname.substring(0, idx + 7);
  }

  return '/';
};

const router = createRouter({
  history: createWebHistory(getBasePath()),
  routes,
  scrollBehavior() {
    return { top: 0 };
  },
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();

  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    return next({ name: 'login' });
  }

  if (to.meta.guestOnly && authStore.isAuthenticated) {
    return next({ name: 'dashboard' });
  }

  next();
});

export default router;
