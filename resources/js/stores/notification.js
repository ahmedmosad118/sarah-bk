import { defineStore } from 'pinia';

export const useNotificationStore = defineStore('notification', {
  state: () => ({
    toasts: [],
  }),

  actions: {
    addToast({ type = 'info', title = '', message = '', duration = 4000 }) {
      const id = Date.now() + Math.random().toString(36).substring(2, 7);
      const toast = { id, type, title, message };
      this.toasts.push(toast);

      if (duration > 0) {
        setTimeout(() => {
          this.removeToast(id);
        }, duration);
      }
    },

    success(message, title = 'تمت العملية بنجاح') {
      this.addToast({ type: 'success', title, message });
    },

    error(message, title = 'تنبيه خطأ') {
      this.addToast({ type: 'error', title, message });
    },

    info(message, title = 'إشعار') {
      this.addToast({ type: 'info', title, message });
    },

    show(message, type = 'info', title = '') {
      const resolvedTitle = title || (type === 'success' ? 'تمت العملية بنجاح' : type === 'error' ? 'تنبيه خطأ' : 'إشعار');
      this.addToast({ type, title: resolvedTitle, message });
    },

    removeToast(id) {
      this.toasts = this.toasts.filter((t) => t.id !== id);
    },
  },
});
