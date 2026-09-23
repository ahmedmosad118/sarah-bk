/**
 * SARH ERP Date & Time Formatting Utilities
 * Eliminates ISO 8601 strings (e.g. 2026-09-21T00:00:00.000000Z) across the entire application.
 * Formats date only (21 سبتمبر 2026) when time is midnight or date-only,
 * or date + hour & minutes (23 سبتمبر 2026، 04:48 ص) when a specific time exists.
 */

export const isIsoDateString = (val) => {
  if (typeof val !== 'string') return false;
  const trimmed = val.trim();
  // Match YYYY-MM-DD, ISO 8601 (YYYY-MM-DDTHH:mm:ss...), or SQL datetime (YYYY-MM-DD HH:mm:ss)
  const isoPattern = /^\d{4}-\d{2}-\d{2}(?:[T\s]\d{2}:\d{2}(?::\d{2}(?:\.\d+)?)?(?:Z|[+-]\d{2}:?\d{2})?)?$/;
  if (!isoPattern.test(trimmed)) return false;
  const d = new Date(trimmed);
  return !isNaN(d.getTime());
};

const hasNonZeroTime = (rawVal, d) => {
  const rawStr = String(rawVal).trim();
  // Pure date YYYY-MM-DD has no time
  if (/^\d{4}-\d{2}-\d{2}$/.test(rawStr)) return false;

  // Explicit midnight time strings (e.g. 2026-09-21T00:00:00.000000Z, 2026-09-21 00:00:00)
  if (/^\d{4}-\d{2}-\d{2}[T\s]00:00(?::00(?:\.0+)?)?(?:Z|[+-]00:?00)?$/.test(rawStr)) {
    return false;
  }

  // Check if UTC time is 00:00:00 for ISO strings ending in Z
  if (rawStr.endsWith('Z') && d.getUTCHours() === 0 && d.getUTCMinutes() === 0 && d.getUTCSeconds() === 0) {
    return false;
  }

  return true;
};

export const formatDate = (dateVal, locale = 'ar') => {
  if (!dateVal) return '—';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return String(dateVal);

  const loc = locale === 'ar' ? 'ar-EG' : 'en-US';

  // If date-only or zero midnight time, format date only
  if (!hasNonZeroTime(dateVal, d)) {
    const match = String(dateVal).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) {
      const year = parseInt(match[1], 10);
      const month = parseInt(match[2], 10) - 1;
      const day = parseInt(match[3], 10);
      const localDate = new Date(year, month, day);
      return localDate.toLocaleDateString(loc, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
      });
    }

    return d.toLocaleDateString(loc, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }

  // Otherwise format date with hour & minutes
  return d.toLocaleString(loc, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};

export const formatDateOnly = (dateVal, locale = 'ar') => {
  if (!dateVal) return '—';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return String(dateVal);
  const loc = locale === 'ar' ? 'ar-EG' : 'en-US';

  const match = String(dateVal).trim().match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) {
    const year = parseInt(match[1], 10);
    const month = parseInt(match[2], 10) - 1;
    const day = parseInt(match[3], 10);
    const localDate = new Date(year, month, day);
    return localDate.toLocaleDateString(loc, {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    });
  }

  return d.toLocaleDateString(loc, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

export const formatDateTime = (dateVal, locale = 'ar') => {
  if (!dateVal) return '—';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return String(dateVal);
  const loc = locale === 'ar' ? 'ar-EG' : 'en-US';
  return d.toLocaleString(loc, {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
};
