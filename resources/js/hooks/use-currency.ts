import { usePage } from '@inertiajs/react';
import { formatCurrency as formatCurrencyLib, parseCurrencyInput, formatCurrencyForInput } from '@/lib/format';
import type { CurrencyConfig } from '@/types';

/**
 * Hook to get currency configuration from Inertia shared data
 */
export function useCurrency(): CurrencyConfig {
  const page = usePage();
  const sharedData = page.props as any;

  // Try to get from business.currency (camelCase) or root currency (snake_case)
  const currency = sharedData.business?.currency || sharedData.currency;

  if (currency) {
    // Normalize to camelCase if needed
    return {
      code: currency.code,
      symbol: currency.symbol,
      precision: currency.precision,
      subunit: currency.subunit,
      symbolFirst: currency.symbolFirst ?? currency.symbol_first ?? true,
      decimalMark: currency.decimalMark ?? currency.decimal_mark ?? ',',
      thousandsSeparator: currency.thousandsSeparator ?? currency.thousands_separator ?? '.',
    };
  }

  return {
    code: 'CLP',
    symbol: '$',
    precision: 0,
    subunit: 1,
    symbolFirst: true,
    decimalMark: ',',
    thousandsSeparator: '.',
  };
}

/**
 * Hook to get currency formatting functions
 */
export function useCurrencyFormatter() {
  const currency = useCurrency();

  const formatCurrency = (amount: number | null | undefined): string => {
    return formatCurrencyLib(amount, currency);
  };

  const fromMinorUnits = (amount: number | null | undefined): number => {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return 0;
    }
    // If subunit is 1 (like CLP), the amount is already in major units
    return currency.subunit > 1 ? amount / currency.subunit : amount;
  };

  const toMinorUnits = (amount: number | null | undefined): number => {
    if (amount === null || amount === undefined || isNaN(amount)) {
      return 0;
    }
    // If subunit is 1 (like CLP), the amount is already in minor units
    return currency.subunit > 1 ? Math.round(amount * currency.subunit) : amount;
  };

  const parseCurrency = (input: string | null | undefined): number | null => {
    return parseCurrencyInput(input, currency);
  };

  const formatForInput = (value: number | null | undefined, includeSymbol: boolean = false): string => {
    return formatCurrencyForInput(value, currency, includeSymbol);
  };

  return {
    formatCurrency,
    fromMinorUnits,
    toMinorUnits,
    parseCurrency,
    formatForInput,
    currency,
  };
}