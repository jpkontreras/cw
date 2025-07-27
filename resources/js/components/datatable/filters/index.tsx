import { cn } from '@/lib/utils';
import { DateFilter } from './date-filter';
import { FilterReset, countActiveFilters } from './filter-reset';
import { SelectFilter } from './select-filter';
import { SearchFilter } from './search-filter';
import { MultiSelectFilter } from './multi-select-filter';
import type { DataTableFiltersProps, FilterConfig, FilterValue } from './types';

export function DataTableFilters({
  filters,
  values,
  onChange,
  onReset,
  className,
  showReset = true,
  resetLabel = 'Reset',
  inline = true,
  responsive = true,
}: DataTableFiltersProps) {
  const activeFilterCount = countActiveFilters(values);

  const renderFilter = (config: FilterConfig) => {
    const value = values[config.key];
    const handleChange = (newValue: FilterValue) => {
      onChange(config.key, newValue);
    };

    const commonProps = {
      config,
      value,
      onChange: handleChange,
      className: '',
    };

    switch (config.type) {
      case 'select':
        return <SelectFilter key={config.key} {...commonProps} config={config} />;
      case 'date':
        return <DateFilter key={config.key} {...commonProps} config={config} />;
      case 'search':
        return <SearchFilter key={config.key} {...commonProps} config={config} />;
      case 'multi-select':
        return <MultiSelectFilter key={config.key} {...commonProps} config={config} />;
      case 'date-range':
        // Implement DateRangeFilter when needed
        return null;
      case 'boolean':
        // Implement BooleanFilter when needed
        return null;
      default:
        console.warn(`Unknown filter type: ${(config as any).type}`);
        return null;
    }
  };

  const handleReset = () => {
    if (onReset) {
      onReset();
    } else {
      // Default reset behavior - clear all filters
      filters.forEach((filter) => {
        onChange(filter.key, undefined);
      });
    }
  };

  return (
    <div
      className={cn(
        'flex items-center gap-2',
        inline ? 'flex-row flex-wrap' : 'flex-col',
        responsive && 'sm:flex-row',
        className
      )}
    >
      {filters.map((filter) => renderFilter(filter))}
      
      {showReset && (
        <FilterReset
          onReset={handleReset}
          activeCount={activeFilterCount}
          label={resetLabel}
          className={inline ? '' : 'w-full'}
        />
      )}
    </div>
  );
}

// Re-export types and components for convenience
export * from './types';
export { DateFilter } from './date-filter';
export { SelectFilter } from './select-filter';
export { SearchFilter } from './search-filter';
export { MultiSelectFilter, MultiSelectFilterCompact } from './multi-select-filter';
export { FilterReset, countActiveFilters } from './filter-reset';