/**
 * Format a number as currency
 * @param amount - The amount to format
 * @param currency - The currency code (default: CLP)
 * @param locale - The locale for formatting (default: es-CL)
 * @returns Formatted currency string
 */
export function formatCurrency(
  amount: number,
  currency: string = 'CLP',
  locale: string = 'es-CL'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency,
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

/**
 * Format a number with thousands separator
 * @param value - The number to format
 * @param locale - The locale for formatting (default: es-CL)
 * @returns Formatted number string
 */
export function formatNumber(
  value: number,
  locale: string = 'es-CL'
): string {
  return new Intl.NumberFormat(locale).format(value);
}

/**
 * Format a date to a readable string
 * @param date - The date to format
 * @param locale - The locale for formatting (default: es-CL)
 * @param options - Intl.DateTimeFormat options
 * @returns Formatted date string
 */
export function formatDate(
  date: Date | string,
  locale: string = 'es-CL',
  options?: Intl.DateTimeFormatOptions
): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  return new Intl.DateTimeFormat(locale, options).format(dateObj);
}

/**
 * Format a date to a relative time string (e.g., "2 hours ago")
 * @param date - The date to format
 * @param locale - The locale for formatting (default: es-CL)
 * @returns Formatted relative time string
 */
export function formatRelativeTime(
  date: Date | string,
  locale: string = 'es-CL'
): string {
  const dateObj = typeof date === 'string' ? new Date(date) : date;
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - dateObj.getTime()) / 1000);
  
  const rtf = new Intl.RelativeTimeFormat(locale, { numeric: 'auto' });
  
  if (diffInSeconds < 60) {
    return rtf.format(-diffInSeconds, 'second');
  } else if (diffInSeconds < 3600) {
    return rtf.format(-Math.floor(diffInSeconds / 60), 'minute');
  } else if (diffInSeconds < 86400) {
    return rtf.format(-Math.floor(diffInSeconds / 3600), 'hour');
  } else if (diffInSeconds < 2592000) {
    return rtf.format(-Math.floor(diffInSeconds / 86400), 'day');
  } else if (diffInSeconds < 31536000) {
    return rtf.format(-Math.floor(diffInSeconds / 2592000), 'month');
  } else {
    return rtf.format(-Math.floor(diffInSeconds / 31536000), 'year');
  }
}

/**
 * Format a percentage
 * @param value - The decimal value to format as percentage
 * @param decimals - Number of decimal places (default: 0)
 * @param locale - The locale for formatting (default: es-CL)
 * @returns Formatted percentage string
 */
export function formatPercentage(
  value: number,
  decimals: number = 0,
  locale: string = 'es-CL'
): string {
  return new Intl.NumberFormat(locale, {
    style: 'percent',
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
  }).format(value);
}

/**
 * Format bytes to human readable format
 * @param bytes - The number of bytes
 * @param decimals - Number of decimal places (default: 2)
 * @returns Formatted string (e.g., "1.5 MB")
 */
export function formatBytes(bytes: number, decimals: number = 2): string {
  if (bytes === 0) return '0 Bytes';
  
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

/**
 * Format a time string (HH:MM:SS) to a more readable format
 * @param time - The time string to format (HH:MM:SS)
 * @param includeSeconds - Whether to include seconds in the output (default: false)
 * @returns Formatted time string
 */
export function formatTime(
  time: string | null | undefined,
  includeSeconds: boolean = false
): string {
  if (!time) return '';
  
  const parts = time.split(':');
  if (parts.length < 2) return time;
  
  const hours = parseInt(parts[0], 10);
  const minutes = parts[1];
  const seconds = parts[2];
  
  // Convert to 12-hour format
  const period = hours >= 12 ? 'PM' : 'AM';
  const displayHours = hours % 12 || 12;
  
  let formattedTime = `${displayHours}:${minutes}`;
  if (includeSeconds && seconds) {
    formattedTime += `:${seconds}`;
  }
  formattedTime += ` ${period}`;
  
  return formattedTime;
}