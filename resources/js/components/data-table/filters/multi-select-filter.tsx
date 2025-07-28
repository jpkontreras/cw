import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { FilterMetadata } from '@/types/datatable';
import { Check, ChevronsUpDown } from 'lucide-react';
import * as React from 'react';

interface MultiSelectFilterProps {
  filter: FilterMetadata;
  value?: string[];
  onChange: (value: string[]) => void;
  className?: string;
}

export function MultiSelectFilter({ filter, value = [], onChange, className }: MultiSelectFilterProps) {
  const [open, setOpen] = React.useState(false);

  const handleSelect = (optionValue: string) => {
    const newValue = value.includes(optionValue) ? value.filter((v) => v !== optionValue) : [...value, optionValue];

    // Respect maxItems if set
    if (filter.maxItems && newValue.length > filter.maxItems) {
      return;
    }

    onChange(newValue);
  };

  const selectedLabels = value.map((v) => filter.options?.find((opt) => opt.value === v)?.label).filter(Boolean);

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn('justify-between', className)}
          style={{ width: filter.width || '200px' }}
        >
          <span className="truncate">
            {value.length === 0
              ? filter.placeholder || `Select ${filter.label}`
              : value.length === 1
                ? selectedLabels[0]
                : `${value.length} selected`}
          </span>
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[200px] p-0">
        <Command>
          <CommandInput placeholder={`Search ${filter.label.toLowerCase()}...`} />
          <CommandEmpty>No {filter.label.toLowerCase()} found.</CommandEmpty>
          <CommandGroup>
            {filter.options?.map((option) => (
              <CommandItem key={option.value} value={option.value} onSelect={() => handleSelect(option.value)} disabled={option.disabled}>
                <Check className={cn('mr-2 h-4 w-4', value.includes(option.value) ? 'opacity-100' : 'opacity-0')} />
                {option.icon && <span className="mr-2">{option.icon}</span>}
                {option.label}
              </CommandItem>
            ))}
          </CommandGroup>
        </Command>
      </PopoverContent>
    </Popover>
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
