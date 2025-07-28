import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { FilterMetadata } from '@/types/datatable';
import { Search } from 'lucide-react';
import * as React from 'react';

interface SearchFilterProps {
  filter: FilterMetadata;
  value?: string;
  onChange: (value: string) => void;
  className?: string;
}

export function SearchFilter({ filter, value = '', onChange, className }: SearchFilterProps) {
  const [searchValue, setSearchValue] = React.useState(value);
  const [debouncedValue, setDebouncedValue] = React.useState(value);

  // Debounce search input
  React.useEffect(() => {
    const timer = setTimeout(() => {
      setDebouncedValue(searchValue);
    }, filter.debounceMs || 300);

    return () => clearTimeout(timer);
  }, [searchValue, filter.debounceMs]);

  // Call onChange when debounced value changes
  React.useEffect(() => {
    onChange(debouncedValue);
  }, [debouncedValue, onChange]);

  return (
    <div className={cn('relative', className)} style={{ width: filter.width || 'auto' }}>
      <Search className="absolute top-2.5 left-2 h-4 w-4 text-muted-foreground" />
      <Input
        placeholder={filter.placeholder || `Search ${filter.label}...`}
        value={searchValue}
        onChange={(e) => setSearchValue(e.target.value)}
        className="pl-8"
      />
    </div>
  );
}
