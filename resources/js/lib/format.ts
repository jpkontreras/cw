import { CurrencyConfig, SharedData } from '@/types';

declare global {
  interface Window {
    $page?: {
      props: SharedData;
    };
  }
}

/**
 * Parse user input string to integer value in minor units (cents, fils, etc.)
 * Handles different locale formats (1,234.56 vs 1.234,56)
 * @param input - User input string
 * @param currencyConfig - Currency configuration
 * @returns Parsed value in minor units or null if invalid
 */
export function parseCurrencyInput(
  input: string | null | undefined,
  currencyConfig?: CurrencyConfig
): number | null {
  if (!input || input === '') return null;
  
  // Get currency configuration from Inertia props if not provided
  if (!currencyConfig && typeof window !== 'undefined' && window.$page) {
    currencyConfig = window.$page.props.business?.currency;
  }
  
  const config = currencyConfig || {
    code: 'CLP',
    precision: 0,
    subunit: 1,
    symbol: '$',
    symbolFirst: true,
    decimalMark: ',',
    thousandsSeparator: '.',
  };
  
  // Remove currency symbols, spaces, and keep only numbers, decimal/thousand separators, and minus
  let cleaned = input.replace(/[^\d,.\-]/g, '');
  
  // Handle different decimal/thousand separator formats
  if (config.decimalMark === ',' && config.thousandsSeparator === '.') {
    // European format: 1.234,56
    // Remove thousand separators (.) and replace decimal mark (,) with (.)
    cleaned = cleaned.replace(/\./g, '').replace(',', '.');
  } else if (config.decimalMark === '.' && config.thousandsSeparator === ',') {
    // US/UK format: 1,234.56
    // Remove thousand separators (,)
    cleaned = cleaned.replace(/,/g, '');
  } else if (config.decimalMark === ',' && config.thousandsSeparator === ' ') {
    // Some European formats: 1 234,56
    // Replace decimal mark (,) with (.)
    cleaned = cleaned.replace(',', '.');
  }
  
  // Parse to float
  const parsed = parseFloat(cleaned);
  if (isNaN(parsed)) return null;
  
  // Convert to minor units (multiply by subunit)
  return Math.round(parsed * config.subunit);
}

/**
 * Format integer value from minor units to display string
 * @param value - Value in minor units (cents, fils, etc.)
 * @param currencyConfig - Currency configuration
 * @param includeSymbol - Whether to include currency symbol
 * @returns Formatted string for display in input
 */
export function formatCurrencyForInput(
  value: number | null | undefined,
  currencyConfig?: CurrencyConfig,
  includeSymbol: boolean = false
): string {
  if (value === null || value === undefined) return '';
  
  // Get currency configuration from Inertia props if not provided
  if (!currencyConfig && typeof window !== 'undefined' && window.$page) {
    currencyConfig = window.$page.props.business?.currency;
  }
  
  const config = currencyConfig || {
    code: 'CLP',
    precision: 0,
    subunit: 1,
    symbol: '$',
    symbolFirst: true,
    decimalMark: ',',
    thousandsSeparator: '.',
  };
  
  // Convert from minor units to major units
  const majorUnits = value / config.subunit;
  
  // Format with proper decimal places
  let formatted = majorUnits.toFixed(config.precision);
  
  // Replace decimal separator if needed
  if (config.decimalMark !== '.') {
    formatted = formatted.replace('.', config.decimalMark);
  }
  
  // Add thousands separator
  const parts = formatted.split(config.decimalMark);
  parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, config.thousandsSeparator);
  formatted = parts.join(config.decimalMark);
  
  // Add symbol if requested
  if (includeSymbol) {
    if (config.symbolFirst) {
      formatted = `${config.symbol}${formatted}`;
    } else {
      formatted = `${formatted} ${config.symbol}`;
    }
  }
  
  return formatted;
}

/**
 * Format a number as currency
 * @param amount - The amount to format (in minor units - cents, fils, etc.)
 * @param currencyConfig - Optional currency configuration from backend
 * @param locale - The locale for formatting (default: es-CL)
 * @returns Formatted currency string
 */
export function formatCurrency(
  amount: number | null | undefined,
  currencyConfig?: CurrencyConfig,
  locale: string = 'es-CL'
): string {
  if (amount === null || amount === undefined || isNaN(amount)) {
    return '—';
  }
  
  // Get currency configuration from Inertia props if not provided
  if (!currencyConfig && typeof window !== 'undefined' && window.$page) {
    currencyConfig = window.$page.props.business?.currency;
  }
  
  // Use defaults if still no config
  const config = currencyConfig || {
    code: 'CLP',
    precision: 0,
    subunit: 1,
    symbol: '$',
    symbolFirst: true,
    decimalMark: ',',
    thousandsSeparator: '.',
  };
  
  // Convert from minor units to major currency units
  const amountInMajorUnits = amount / config.subunit;
  
  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: config.code,
    minimumFractionDigits: config.precision,
    maximumFractionDigits: config.precision,
  }).format(amountInMajorUnits);
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