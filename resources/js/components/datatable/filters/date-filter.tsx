import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { DateFilterConfig, SingleFilterProps } from './types';

const DEFAULT_PRESETS = [
  { label: 'Today', value: 'today', getValue: () => 'today' },
  { label: 'Yesterday', value: 'yesterday', getValue: () => 'yesterday' },
  { label: 'This Week', value: 'week', getValue: () => 'week' },
  { label: 'This Month', value: 'month', getValue: () => 'month' },
  { label: 'Last 7 Days', value: 'last7days', getValue: () => 'last7days' },
  { label: 'Last 30 Days', value: 'last30days', getValue: () => 'last30days' },
];

export function DateFilter({ 
  config, 
  value, 
  onChange, 
  className 
}: SingleFilterProps<DateFilterConfig>) {
  const presets = config.presets || DEFAULT_PRESETS;
  
  const handleValueChange = (newValue: string) => {
    onChange(newValue === 'all' ? undefined : newValue);
  };

  const currentValue = value as string | undefined;
  const displayValue = currentValue || 'all';

  return (
    <Select 
      value={displayValue} 
      onValueChange={handleValueChange}
      disabled={config.disabled}
    >
      <SelectTrigger 
        className={cn(
          'h-10',
          config.width || 'w-[140px]',
          className
        )}
      >
        {config.icon && (
          <config.icon className="mr-2 h-4 w-4 shrink-0" />
        )}
        <SelectValue placeholder={config.placeholder || 'All Time'} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="all">
          {config.placeholder || 'All Time'}
        </SelectItem>
        {presets.map((preset) => (
          <SelectItem key={preset.value} value={preset.value}>
            {preset.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}