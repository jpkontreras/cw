import * as React from 'react';
import { Check, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
  CommandSeparator,
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { FilterMetadata } from '@/types/datatable';

interface MultiSelectFilterProps {
  filter: FilterMetadata;
  value?: string[];
  onChange: (value: string[]) => void;
  className?: string;
}

export function MultiSelectFilter({ filter, value = [], onChange, className }: MultiSelectFilterProps) {
  const [open, setOpen] = React.useState(false);
  const [localValue, setLocalValue] = React.useState<string[]>(value);

  // Update local value when prop changes
  React.useEffect(() => {
    setLocalValue(value);
  }, [value]);

  const handleSelect = (optionValue: string) => {
    const newValue = localValue.includes(optionValue) 
      ? localValue.filter((v) => v !== optionValue) 
      : [...localValue, optionValue];

    // Respect maxItems if set
    if (filter.maxItems && newValue.length > filter.maxItems) {
      return;
    }

    setLocalValue(newValue);
  };

  const handleClearAll = () => {
    setLocalValue([]);
  };

  const handleApplyFilter = () => {
    onChange(localValue);
  };

  const selectedLabels = value
    .map((v) => filter.options?.find((opt) => opt.value === v)?.label)
    .filter(Boolean);

  const displayText = () => {
    if (value.length === 0) {
      return filter.placeholder || `All ${filter.label}`;
    }
    if (value.length === 1) {
      return selectedLabels[0];
    }
    if (value.length === 2) {
      return selectedLabels.join(', ');
    }
    return `${selectedLabels[0]}, ${selectedLabels[1]} +${value.length - 2}`;
  };

  return (
    <div className="flex flex-col w-full max-h-[400px]">
      <div className="flex-1 overflow-hidden flex flex-col min-h-0">
        <Command className="flex-1 flex flex-col">
          <CommandInput placeholder={`Search ${filter.label.toLowerCase()}...`} className="flex-shrink-0" />
          <CommandList className="flex-1 overflow-auto">
            <CommandEmpty>No {filter.label.toLowerCase()} found.</CommandEmpty>
            <CommandGroup>
              {localValue.length > 0 && (
                <>
                  <CommandItem
                    onSelect={handleClearAll}
                    className="justify-center text-center"
                  >
                    Clear all filters
                  </CommandItem>
                  <CommandSeparator />
                </>
              )}
              {filter.options?.map((option) => {
                const isSelected = localValue.includes(option.value);
                return (
                  <CommandItem
                    key={option.value}
                    value={option.value}
                    onSelect={() => handleSelect(option.value)}
                    disabled={option.disabled}
                    className="flex items-center justify-between"
                  >
                    <div className="flex items-center">
                      <div
                        className={cn(
                          'mr-2 flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                          isSelected
                            ? 'bg-primary text-primary-foreground'
                            : 'opacity-50 [&_svg]:invisible'
                        )}
                      >
                        <Check className="h-3 w-3" />
                      </div>
                      {option.icon && <span className="mr-2">{option.icon}</span>}
                      <span>{option.label}</span>
                    </div>
                    {option.count !== undefined && (
                      <span className="ml-auto text-xs text-muted-foreground">
                        {option.count}
                      </span>
                    )}
                  </CommandItem>
                );
              })}
            </CommandGroup>
          </CommandList>
        </Command>
      </div>
      {localValue.length > 0 && (
        <div className="flex flex-wrap gap-1 p-2 border-t flex-shrink-0 max-h-[60px] overflow-auto">
          {localValue.slice(0, 3).map((v) => {
            const option = filter.options?.find((opt) => opt.value === v);
            return (
              <Badge
                key={v}
                variant="secondary"
                className="text-xs"
              >
                {option?.label || v}
              </Badge>
            );
          })}
          {localValue.length > 3 && (
            <Badge variant="outline" className="text-xs">
              +{localValue.length - 3} more
            </Badge>
          )}
        </div>
      )}
      <div className="p-2 border-t flex-shrink-0">
        <Button
          size="sm"
          className="w-full"
          onClick={handleApplyFilter}
        >
          Filter
        </Button>
      </div>
    </div>
  );
}

export function MultiSelectFilterDisplay({
  filter,
  value = [],
  onRemove,
}: {
  filter: FilterMetadata;
  value: string[];
  onRemove: (val: string) => void;
}) {
  if (value.length === 0) return null;

  return (
    <div className="flex flex-wrap gap-1">
      {value.map((v) => {
        const option = filter.options?.find((opt) => opt.value === v);
        return (
          <Badge key={v} variant="secondary" className="cursor-pointer" onClick={() => onRemove(v)}>
            {option?.label || v}
            <span className="ml-1">×</span>
          </Badge>
        );
      })}
    </div>
  );
}
