import * as React from 'react';
import { usePage } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { parseCurrencyInput, formatCurrencyForInput } from '@/lib/format';
import { CurrencyConfig } from '@/types';

export interface CurrencyInputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange' | 'type'> {
  value?: number | null; // Value in minor units (cents, fils, etc.)
  onChange?: (value: number | null) => void;
  currencyConfig?: CurrencyConfig; // Optional override, will use business config by default
  showSymbol?: boolean;
}

const CurrencyInput = React.forwardRef<HTMLInputElement, CurrencyInputProps>(
  ({ className, value, onChange, currencyConfig: overrideCurrencyConfig, showSymbol = false, ...props }, ref) => {
    // Get currency config from Inertia props if not provided
    const { props: pageProps } = usePage<any>();
    const currencyConfig = overrideCurrencyConfig || pageProps.business?.currency;
    const [displayValue, setDisplayValue] = React.useState('');
    const [isFocused, setIsFocused] = React.useState(false);

    // Update display value when the value prop changes
    React.useEffect(() => {
      if (!isFocused) {
        const formatted = formatCurrencyForInput(value, currencyConfig, showSymbol);
        setDisplayValue(formatted);
      }
    }, [value, currencyConfig, showSymbol, isFocused]);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
      const inputValue = e.target.value;
      setDisplayValue(inputValue);

      if (onChange) {
        const parsedValue = parseCurrencyInput(inputValue, currencyConfig);
        onChange(parsedValue);
      }
    };

    const handleFocus = () => {
      setIsFocused(true);
      // When focusing, show the raw value without symbol
      const formatted = formatCurrencyForInput(value, currencyConfig, false);
      setDisplayValue(formatted);
    };

    const handleBlur = () => {
      setIsFocused(false);
      // When blurring, format with symbol if requested
      const formatted = formatCurrencyForInput(value, currencyConfig, showSymbol);
      setDisplayValue(formatted);
    };

    return (
      <Input
        type="text"
        inputMode="decimal"
        ref={ref}
        className={cn('font-mono', className)}
        value={displayValue}
        onChange={handleChange}
        onFocus={handleFocus}
        onBlur={handleBlur}
        {...props}
      />
    );
  }
);

CurrencyInput.displayName = 'CurrencyInput';

export { CurrencyInput };