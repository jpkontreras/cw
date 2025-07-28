import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { FilterMetadata } from '@/types/datatable';
import { format } from 'date-fns';
import { Calendar as CalendarIcon } from 'lucide-react';
import * as React from 'react';

interface DateFilterProps {
  filter: FilterMetadata;
  value?: string | { from: string; to: string };
  onChange: (value: string | { from: string; to: string } | undefined) => void;
  className?: string;
}

export function DateFilter({ filter, value, onChange, className }: DateFilterProps) {
  const [date, setDate] = React.useState<Date | undefined>(value && typeof value === 'string' ? new Date(value) : undefined);
  const [presetValue, setPresetValue] = React.useState<string>('__custom__');

  // Handle preset changes
  const handlePresetChange = (preset: string) => {
    setPresetValue(preset);

    const today = new Date();
    today.setHours(0, 0, 0, 0);

    switch (preset) {
      case 'today':
        onChange(format(today, 'yyyy-MM-dd'));
        setDate(today);
        break;
      case 'yesterday': {
        const yesterday = new Date(today);
        yesterday.setDate(yesterday.getDate() - 1);
        onChange(format(yesterday, 'yyyy-MM-dd'));
        setDate(yesterday);
        break;
      }
      case 'week': {
        const weekStart = new Date(today);
        weekStart.setDate(weekStart.getDate() - weekStart.getDay());
        onChange({
          from: format(weekStart, 'yyyy-MM-dd'),
          to: format(today, 'yyyy-MM-dd'),
        });
        setDate(undefined);
        break;
      }
      case 'month': {
        const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
        onChange({
          from: format(monthStart, 'yyyy-MM-dd'),
          to: format(today, 'yyyy-MM-dd'),
        });
        setDate(undefined);
        break;
      }
      case '__custom__':
        onChange(undefined);
        setDate(undefined);
        break;
    }
  };

  // Handle calendar date selection
  const handleDateSelect = (newDate: Date | undefined) => {
    setDate(newDate);
    setPresetValue('__custom__');
    if (newDate) {
      onChange(format(newDate, 'yyyy-MM-dd'));
    } else {
      onChange(undefined);
    }
  };

  const displayValue = () => {
    if (presetValue && presetValue !== '__custom__') {
      const preset = filter.presets?.find((p) => p.value === presetValue);
      return preset?.label || 'Select date';
    }
    if (date) {
      return format(date, 'PPP');
    }
    if (value && typeof value === 'object') {
      return `${value.from} - ${value.to}`;
    }
    return filter.placeholder || 'Select date';
  };

  return (
    <div className={cn('flex gap-2', className)}>
      {filter.presets && filter.presets.length > 0 && (
        <Select value={presetValue} onValueChange={handlePresetChange}>
          <SelectTrigger className="w-[140px]">
            <SelectValue placeholder="Quick select" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="__custom__">Custom</SelectItem>
            {filter.presets.map((preset) => (
              <SelectItem key={preset.value} value={preset.value}>
                {preset.label}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
      )}

      <Popover>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            className={cn('justify-start text-left font-normal', !date && !value && 'text-muted-foreground')}
            style={{ width: filter.width || '240px' }}
          >
            <CalendarIcon className="mr-2 h-4 w-4" />
            {displayValue()}
          </Button>
        </PopoverTrigger>
        <PopoverContent className="w-auto p-0" align="start">
          <Calendar
            mode="single"
            selected={date}
            onSelect={handleDateSelect}
            disabled={(date) => {
              if (filter.minDate && date < new Date(filter.minDate)) return true;
              if (filter.maxDate && date > new Date(filter.maxDate)) return true;
              return false;
            }}
            initialFocus
          />
        </PopoverContent>
      </Popover>
    </div>
  );
}
