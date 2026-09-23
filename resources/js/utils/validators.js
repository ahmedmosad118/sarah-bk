/**
 * SARH ERP — Comprehensive Form & Input Validators
 * Specialized for Construction & Finishing Commercial Workflows
 */

/**
 * Validate Egyptian and International phone numbers.
 * Supported valid formats:
 * - Egyptian Mobile: 010xxxxxxxx, 011xxxxxxxx, 012xxxxxxxx, 015xxxxxxxx (11 digits)
 * - Egyptian Landline: 02xxxxxxxx, 03xxxxxxxx, 04xxxxxxxx, 05xxxxxxxx, 08xxxxxxxx, 09xxxxxxxx (8 to 10 digits)
 * - International: +20..., +966..., +971..., 00966... (7 to 15 digits)
 *
 * @param {string|number} phone
 * @param {Object} options
 * @returns {{ isValid: boolean, message: string }}
 */
export function validatePhone(phone, { required = false, label = 'رقم الهاتف' } = {}) {
  if (phone === undefined || phone === null || phone.toString().trim() === '') {
    if (required) {
      return { isValid: false, message: `${label} مطلوب.` };
    }
    return { isValid: true, message: '' };
  }

  const raw = phone.toString().trim();
  // Remove permissible formatting characters like spaces, dashes, dots, parentheses
  const clean = raw.replace(/[\s\-_().]/g, '');

  // Must only contain digits with an optional single '+' at the beginning
  if (!/^\+?[0-9]+$/.test(clean)) {
    return {
      isValid: false,
      message: `${label} غير صحيح. يجب أن يحتوي على أرقام فقط (مثال: 01012345678 أو +201012345678).`,
    };
  }

  // Egyptian mobile format: starts with 010, 011, 012, 015 -> must be 11 digits
  if (/^01[0125]/.test(clean)) {
    if (clean.length !== 11) {
      return {
        isValid: false,
        message: `${label} يبدأ برقم محمول مصري ويجب أن يتكون من 11 رقماً بالضبط (أدخلت ${clean.length} أرقام).`,
      };
    }
    return { isValid: true, message: '' };
  }

  // Egyptian landline format: starts with 02, 03, 04, 05, 06, 08, 09 -> 8-10 digits
  if (/^0[2345689]/.test(clean)) {
    if (clean.length < 8 || clean.length > 10) {
      return {
        isValid: false,
        message: `${label} أرضي مصري يجب أن يتكون من 8 إلى 10 أرقام.`,
      };
    }
    return { isValid: true, message: '' };
  }

  // International format: starts with + or 00 -> 7 to 15 digits
  if (clean.startsWith('+') || clean.startsWith('00')) {
    const digitsOnly = clean.replace(/^\+|^00/, '');
    if (digitsOnly.length < 7 || digitsOnly.length > 15) {
      return {
        isValid: false,
        message: `${label} الدولي يجب أن يتكون من 7 إلى 15 رقماً متضمناً كود الدولة.`,
      };
    }
    return { isValid: true, message: '' };
  }

  // Fallback for general valid numbers
  if (clean.length < 7 || clean.length > 15) {
    return {
      isValid: false,
      message: `${label} يجب أن يتكون من 7 إلى 15 رقماً صالحاً.`,
    };
  }

  return { isValid: true, message: '' };
}

/**
 * Validate RFC 5322 compliant Email format.
 *
 * @param {string} email
 * @param {Object} options
 * @returns {{ isValid: boolean, message: string }}
 */
export function validateEmail(email, { required = false, label = 'البريد الإلكتروني' } = {}) {
  if (email === undefined || email === null || email.toString().trim() === '') {
    if (required) {
      return { isValid: false, message: `${label} مطلوب.` };
    }
    return { isValid: true, message: '' };
  }

  const clean = email.toString().trim();
  // Standard RFC email regex with proper TLD validation
  const emailRegex = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;

  if (!emailRegex.test(clean) || clean.endsWith('.')) {
    return {
      isValid: false,
      message: `${label} غير صحيح. يرجى إدخال بريد صالح (مثال: name@example.com).`,
    };
  }

  return { isValid: true, message: '' };
}

/**
 * Validate Required string with minimum length.
 *
 * @param {any} val
 * @param {Object} options
 * @returns {{ isValid: boolean, message: string }}
 */
export function validateRequired(val, { minLength = 2, label = 'هذا الحقل' } = {}) {
  if (val === undefined || val === null || val.toString().trim() === '') {
    return { isValid: false, message: `${label} مطلوب ولا يمكن تركه فارغاً.` };
  }

  const str = val.toString().trim();
  if (str.length < minLength) {
    return { isValid: false, message: `${label} يجب ألا يقل عن ${minLength} أحرف.` };
  }

  return { isValid: true, message: '' };
}
