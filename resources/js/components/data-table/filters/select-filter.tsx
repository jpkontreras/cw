import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { FilterMetadata } from '@/types/datatable';

interface SelectFilterProps {
  filter: FilterMetadata;
  value?: string;
  onChange: (value: string | undefined) => void;
  className?: string;
}

export function SelectFilter({ filter, value, onChange, className }: SelectFilterProps) {
  const handleValueChange = (newValue: string) => {
    // Use '__all__' as internal value for "All" option
    onChange(newValue === '__all__' ? undefined : newValue);
  };

  return (
    <Select value={value || '__all__'} onValueChange={handleValueChange}>
      <SelectTrigger className={cn(className)} style={{ width: filter.width || 'auto' }}>
        <SelectValue placeholder={filter.placeholder || `Select ${filter.label}`} />
      </SelectTrigger>
      <SelectContent>
        <SelectItem value="__all__">All</SelectItem>
        {filter.options?.map((option) => (
          <SelectItem key={option.value} value={option.value} disabled={option.disabled}>
            {option.icon && <span className="mr-2">{option.icon}</span>}
            {option.label}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  );
}
