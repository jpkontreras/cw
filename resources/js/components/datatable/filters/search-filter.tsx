import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Search } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import type { SearchFilterConfig, SingleFilterProps } from './types';

export function SearchFilter({ 
  config, 
  value, 
  onChange, 
  className 
}: SingleFilterProps<SearchFilterConfig>) {
  const [localValue, setLocalValue] = useState<string>((value as string) || '');
  const [timeoutId, setTimeoutId] = useState<NodeJS.Timeout | null>(null);

  // Update local value when prop changes
  useEffect(() => {
    setLocalValue((value as string) || '');
  }, [value]);

  // Cleanup timeout on unmount
  useEffect(() => {
    return () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
  }, [timeoutId]);

  const handleChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    const newValue = e.target.value;
    setLocalValue(newValue);

    // Clear existing timeout
    if (timeoutId) {
      clearTimeout(timeoutId);
    }

    // Set new timeout for debounced onChange
    const newTimeoutId = setTimeout(() => {
      if (config.minLength && newValue.length > 0 && newValue.length < config.minLength) {
        // Don't trigger onChange if value is too short (but not empty)
        return;
      }
      onChange(newValue || undefined);
    }, config.debounceMs || 300);

    setTimeoutId(newTimeoutId);
  }, [config.debounceMs, config.minLength, onChange, timeoutId]);

  const handleClear = useCallback(() => {
    setLocalValue('');
    onChange(undefined);
  }, [onChange]);

  return (
    <div className={cn('relative', className)}>
      <div className="relative">
        <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          type="search"
          placeholder={config.placeholder || `Search ${config.label}...`}
          value={localValue}
          onChange={handleChange}
          disabled={config.disabled}
          className={cn(
            'pl-9 pr-9 h-10',
            config.width || 'w-[200px]'
          )}
        />
        {localValue && (
          <button
            type="button"
            onClick={handleClear}
            className="absolute right-2.5 top-2.5 h-4 w-4 text-muted-foreground hover:text-foreground"
            aria-label="Clear search"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 20 20"
              fill="currentColor"
              className="h-4 w-4"
            >
              <path
                d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"
              />
            </svg>
          </button>
        )}
      </div>
      {config.minLength && localValue.length > 0 && localValue.length < config.minLength && (
        <p className="mt-1 text-xs text-muted-foreground">
          Type at least {config.minLength} characters to search
        </p>
      )}
    </div>
  );
}