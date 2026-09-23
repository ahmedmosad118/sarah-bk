import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Check if the document is in Dark Mode.
 */
const isDarkMode = () => {
  return document.documentElement.classList.contains('dark') ||
    document.body.classList.contains('dark') ||
    localStorage.getItem('sarh_theme') === 'dark';
};

/**
 * Check if current direction is RTL.
 */
const isRTL = () => {
  const dir = document.documentElement.getAttribute('dir') || document.body.getAttribute('dir');
  if (dir) return dir === 'rtl';
  const lang = localStorage.getItem('sarh_locale') || 'ar';
  return lang === 'ar';
};

/**
 * Create base SweetAlert2 configured instance with SARH ERP Luxury Theme.
 */
export const getSwalInstance = () => {
  const dark = isDarkMode();
  const rtl = isRTL();

  return Swal.mixin({
    background: dark ? '#0F172A' : '#FFFFFF',
    color: dark ? '#F8FAFC' : '#0F172A',
    backdrop: 'rgba(15, 23, 42, 0.65)',
    buttonsStyling: false,
    reverseButtons: rtl,
    customClass: {
      popup: `sarh-swal-popup ${dark ? 'dark-mode' : ''} ${rtl ? 'rtl' : 'ltr'}`,
      title: 'sarh-swal-title',
      htmlContainer: 'sarh-swal-content',
      confirmButton: 'sarh-swal-btn sarh-swal-confirm',
      cancelButton: 'sarh-swal-btn sarh-swal-cancel',
      denyButton: 'sarh-swal-btn sarh-swal-deny',
      actions: 'sarh-swal-actions',
      icon: 'sarh-swal-icon',
    },
    showClass: {
      popup: 'swal2-show animate-in fade-in zoom-in-95 duration-200',
    },
    hideClass: {
      popup: 'swal2-hide animate-out fade-out zoom-out-95 duration-150',
    },
  });
};

/**
 * Confirmation dialog for Destructive / Delete Actions.
 *
 * @param {Object} options
 * @param {string} options.title
 * @param {string} options.text
 * @param {string} options.confirmButtonText
 * @param {string} options.cancelButtonText
 * @returns {Promise<boolean>} Resolves to true if confirmed, false otherwise.
 */
export const confirmDelete = async ({
  title = isRTL() ? 'تأكيد الحذف النهائي' : 'Confirm Permanent Deletion',
  text = isRTL() ? 'هل أنت متأكد من رغبتك في حذف هذا السجل؟ لا يمكن التراجع عن هذا الإجراء.' : 'Are you sure you want to delete this record? This action cannot be undone.',
  confirmButtonText = isRTL() ? 'نعم، احذف السجل' : 'Yes, delete it',
  cancelButtonText = isRTL() ? 'إلغاء الأمر' : 'Cancel',
} = {}) => {
  const swal = getSwalInstance();

  const result = await swal.fire({
    title,
    text,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    focusCancel: true,
    customClass: {
      popup: `sarh-swal-popup ${isDarkMode() ? 'dark-mode' : ''} ${isRTL() ? 'rtl' : 'ltr'}`,
      title: 'sarh-swal-title text-rose-600 dark:text-rose-400',
      htmlContainer: 'sarh-swal-content',
      confirmButton: 'sarh-swal-btn sarh-swal-delete',
      cancelButton: 'sarh-swal-btn sarh-swal-cancel',
      actions: 'sarh-swal-actions',
      icon: 'sarh-swal-icon sarh-swal-icon-warning',
    },
  });

  return result.isConfirmed;
};

/**
 * Confirmation dialog for General Actions (e.g., Conversion, Status change, Approval).
 *
 * @param {Object} options
 * @returns {Promise<boolean>}
 */
export const confirmAction = async ({
  title = isRTL() ? 'تأكيد الإجراء' : 'Confirm Action',
  text = isRTL() ? 'هل تريد الاستمرار في تنفيذ هذا الإجراء؟' : 'Do you want to proceed with this action?',
  icon = 'question',
  confirmButtonText = isRTL() ? 'تأكيد' : 'Confirm',
  cancelButtonText = isRTL() ? 'إلغاء' : 'Cancel',
} = {}) => {
  const swal = getSwalInstance();

  const result = await swal.fire({
    title,
    text,
    icon,
    showCancelButton: true,
    confirmButtonText,
    cancelButtonText,
    focusConfirm: true,
  });

  return result.isConfirmed;
};

/**
 * Success Alert Modal.
 */
export const showSuccess = async ({
  title = isRTL() ? 'تمت العملية بنجاح' : 'Success',
  text = '',
  timer = 2000,
  showConfirmButton = false,
} = {}) => {
  const swal = getSwalInstance();
  return swal.fire({
    icon: 'success',
    title,
    text,
    timer: timer > 0 ? timer : undefined,
    showConfirmButton,
    timerProgressBar: timer > 0,
  });
};

/**
 * Error Alert Modal.
 */
export const showError = async ({
  title = isRTL() ? 'حدث خطأ' : 'Error',
  text = isRTL() ? 'تعذر إتمام العملية، يرجى المحاولة مرة أخرى.' : 'Operation could not be completed, please try again.',
  confirmButtonText = isRTL() ? 'حسناً' : 'OK',
} = {}) => {
  const swal = getSwalInstance();
  return swal.fire({
    icon: 'error',
    title,
    text,
    showConfirmButton: true,
    confirmButtonText,
  });
};

/**
 * Warning Alert Modal.
 */
export const showWarning = async ({
  title = isRTL() ? 'تنبيه' : 'Warning',
  text = '',
  confirmButtonText = isRTL() ? 'موافق' : 'OK',
} = {}) => {
  const swal = getSwalInstance();
  return swal.fire({
    icon: 'warning',
    title,
    text,
    showConfirmButton: true,
    confirmButtonText,
  });
};

/**
 * Info Alert Modal.
 */
export const showInfo = async ({
  title = isRTL() ? 'إشعار' : 'Information',
  text = '',
  confirmButtonText = isRTL() ? 'حسناً' : 'OK',
} = {}) => {
  const swal = getSwalInstance();
  return swal.fire({
    icon: 'info',
    title,
    text,
    showConfirmButton: true,
    confirmButtonText,
  });
};

export default {
  fire: (...args) => getSwalInstance().fire(...args),
  confirmDelete,
  confirmAction,
  success: showSuccess,
  error: showError,
  warning: showWarning,
  info: showInfo,
  getSwalInstance,
};
