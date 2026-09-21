import { defineStore } from 'pinia';
import api from '../services/api';

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: JSON.parse(localStorage.getItem('sarh_user') || 'null'),
    tenant: JSON.parse(localStorage.getItem('sarh_tenant') || 'null'),
    token: localStorage.getItem('sarh_token') || null,
    tenantSlug: localStorage.getItem('sarh_tenant_slug') || '',
    permissions: JSON.parse(localStorage.getItem('sarh_permissions') || '[]'),
    roles: JSON.parse(localStorage.getItem('sarh_roles') || '[]'),
    loading: false,
    error: null,
  }),

  getters: {
    isAuthenticated: (state) => !!state.token && !!state.user,
    isOwner: (state) => state.roles.includes('Owner'),
    isSuperAdmin: (state) => state.roles.includes('Owner') || state.roles.includes('Super Admin'),
  },

  actions: {
    setTenantSlug(slug) {
      this.tenantSlug = slug;
      if (slug) {
        localStorage.setItem('sarh_tenant_slug', slug);
      } else {
        localStorage.removeItem('sarh_tenant_slug');
      }
    },

    async login(email, password, slug = null) {
      this.loading = true;
      this.error = null;

      if (slug) {
        this.setTenantSlug(slug);
      }

      try {
        const response = await api.post('/auth/login', {
          email,
          password,
        });

        const data = response.data.data;
        this.token = data.token;
        this.user = data.user;
        this.tenant = data.tenant;
        this.permissions = data.user.permissions || [];
        this.roles = data.user.roles || [];

        localStorage.setItem('sarh_token', this.token);
        localStorage.setItem('sarh_user', JSON.stringify(this.user));
        localStorage.setItem('sarh_tenant', JSON.stringify(this.tenant));
        localStorage.setItem('sarh_permissions', JSON.stringify(this.permissions));
        localStorage.setItem('sarh_roles', JSON.stringify(this.roles));

        return { success: true, data };
      } catch (err) {
        this.error = err.response?.data?.message || 'فشل تسجيل الدخول. يرجى التحقق من البيانات.';
        return {
          success: false,
          message: this.error,
          errors: err.response?.data?.errors,
        };
      } finally {
        this.loading = false;
      }
    },

    async fetchMe() {
      if (!this.token) return;

      try {
        const response = await api.get('/auth/me');
        const data = response.data.data;
        this.user = data.user;
        this.tenant = data.tenant;
        this.permissions = data.user.permissions || [];
        this.roles = data.user.roles || [];

        localStorage.setItem('sarh_user', JSON.stringify(this.user));
        localStorage.setItem('sarh_tenant', JSON.stringify(this.tenant));
        localStorage.setItem('sarh_permissions', JSON.stringify(this.permissions));
        localStorage.setItem('sarh_roles', JSON.stringify(this.roles));
      } catch (err) {
        this.logout();
      }
    },

    async logout() {
      try {
        await api.post('/auth/logout');
      } catch (err) {
        // Ignore
      } finally {
        this.user = null;
        this.tenant = null;
        this.token = null;
        this.permissions = [];
        this.roles = [];

        localStorage.removeItem('sarh_token');
        localStorage.removeItem('sarh_user');
        localStorage.removeItem('sarh_tenant');
        localStorage.removeItem('sarh_permissions');
        localStorage.removeItem('sarh_roles');
      }
    },

    async updateProfile(payload) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.put('/auth/profile', payload);
        const updatedUser = response.data.data.user;
        this.user = { ...this.user, ...updatedUser };
        localStorage.setItem('sarh_user', JSON.stringify(this.user));
        return { success: true, message: response.data.message, data: response.data.data };
      } catch (err) {
        const message = err.response?.data?.message || 'فشل تحديث بيانات الملف الشخصي';
        return {
          success: false,
          message,
          errors: err.response?.data?.errors,
        };
      } finally {
        this.loading = false;
      }
    },

    async uploadAvatar(file) {
      this.loading = true;
      this.error = null;
      try {
        const formData = new FormData();
        formData.append('avatar', file);

        const response = await api.post('/auth/profile/avatar', formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });

        const updatedUser = response.data.user;
        if (updatedUser) {
          this.user = { ...this.user, ...updatedUser, avatar_url: response.data.avatar_url };
        } else {
          this.user = { ...this.user, avatar_url: response.data.avatar_url };
        }

        localStorage.setItem('sarh_user', JSON.stringify(this.user));
        return {
          success: true,
          message: response.data.message,
          avatar_url: response.data.avatar_url,
        };
      } catch (err) {
        const message = err.response?.data?.message || 'فشل رفع الصورة الشخصية';
        return {
          success: false,
          message,
          errors: err.response?.data?.errors,
        };
      } finally {
        this.loading = false;
      }
    },

    async changePassword(payload) {
      this.loading = true;
      this.error = null;
      try {
        const response = await api.post('/auth/change-password', payload);
        return { success: true, message: response.data.message };
      } catch (err) {
        const message = err.response?.data?.message || 'فشل تغيير كلمة المرور';
        return {
          success: false,
          message,
          errors: err.response?.data?.errors,
        };
      } finally {
        this.loading = false;
      }
    },

    hasPermission(permission) {
      if (this.isOwner || this.isSuperAdmin) return true;
      if (Array.isArray(permission)) {
        return permission.some((p) => this.permissions.includes(p));
      }
      return this.permissions.includes(permission);
    },

    hasRole(role) {
      if (Array.isArray(role)) {
        return role.some((r) => this.roles.includes(r));
      }
      return this.roles.includes(role);
    },
  },
});
