import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';
import type { FilterOption, SelectFilterConfig, SingleFilterProps } from './types';

export function SelectFilter({ 
  config, 
  value, 
  onChange, 
  className 
}: SingleFilterProps<SelectFilterConfig>) {
  const [options, setOptions] = useState<FilterOption[]>([]);
  const [isLoading, setIsLoading] = useState(false);

  useEffect(() => {
    const loadOptions = async () => {
      if (typeof config.options === 'function') {
        setIsLoading(true);
        try {
          const loadedOptions = await config.options();
          setOptions(loadedOptions);
        } catch (error) {
          console.error('Failed to load filter options:', error);
          setOptions([]);
        } finally {
          setIsLoading(false);
        }
      } else {
        setOptions(config.options);
      }
    };

    loadOptions();
  }, [config.options]);

  const handleValueChange = (newValue: string) => {
    onChange(newValue === 'all' ? undefined : newValue);
  };

  const currentValue = value as string | undefined;
  const displayValue = currentValue || 'all';

  return (
    <Select 
      value={displayValue} 
      onValueChange={handleValueChange}
      disabled={config.disabled || isLoading}
    >
      <SelectTrigger 
        className={cn(
          'h-10',
          config.width || 'w-[160px]',
          className
        )}
      >
        {config.icon && (
          <config.icon className="mr-2 h-4 w-4 shrink-0" />
        )}
        <SelectValue placeholder={config.placeholder || `All ${config.label}`}>
          {isLoading ? 'Loading...' : undefined}
        </SelectValue>
      </SelectTrigger>
      <SelectContent>
        {config.allowClear !== false && (
          <SelectItem value="all">
            {config.placeholder || `All ${config.label}`}
          </SelectItem>
        )}
        {options.map((option) => (
          <SelectItem 
            key={option.value} 
            value={option.value}
            disabled={option.disabled}
          >
            <div className="flex items-center">
              {option.icon && (
                <option.icon className="mr-2 h-4 w-4" />
              )}
              {option.label}
            </div>
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}