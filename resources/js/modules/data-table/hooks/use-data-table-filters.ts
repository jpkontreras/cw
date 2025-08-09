import { FilterMetadata } from '@/types/datatable';
import * as React from 'react';

export interface UseDataTableFiltersOptions {
  filters: FilterMetadata[];
  defaultFilters?: string[];
  initialValues?: Record<string, any>;
  onFilterChange?: (filters: Record<string, any>) => void;
}

export function useDataTableFilters({ filters, defaultFilters = [], initialValues = {}, onFilterChange }: UseDataTableFiltersOptions) {
  const [activeFilters, setActiveFilters] = React.useState<Record<string, any>>(initialValues);

  // Get visible filters
  const visibleFilters = React.useMemo(() => {
    return filters.filter((filter) => defaultFilters.includes(filter.key));
  }, [filters, defaultFilters]);

  // Update filter value
  const updateFilter = React.useCallback(
    (key: string, value: any) => {
      setActiveFilters((prev) => {
        const newFilters = { ...prev };

        if (value === undefined || value === '' || (Array.isArray(value) && value.length === 0)) {
          delete newFilters[key];
        } else {
          newFilters[key] = value;
        }

        if (onFilterChange) {
          onFilterChange(newFilters);
        }

        return newFilters;
      });
    },
    [onFilterChange],
  );

  // Clear all filters
  const clearFilters = React.useCallback(() => {
    setActiveFilters({});
    if (onFilterChange) {
      onFilterChange({});
    }
  }, [onFilterChange]);

  // Clear single filter
  const clearFilter = React.useCallback(
    (key: string) => {
      updateFilter(key, undefined);
    },
    [updateFilter],
  );

  // Check if any filters are active
  const hasActiveFilters = Object.keys(activeFilters).length > 0;

  // Get filter metadata by key
  const getFilterByKey = React.useCallback(
    (key: string) => {
      return filters.find((filter) => filter.key === key);
    },
    [filters],
  );

  // Format filter value for display
  const formatFilterValue = React.useCallback(
    (key: string, value: any) => {
      const filter = getFilterByKey(key);
      if (!filter) return String(value);

      if (filter.filterType === 'multi-select' && Array.isArray(value)) {
        return value.map((v) => filter.options?.find((opt) => opt.value === v)?.label || v).join(', ');
      }

      if (filter.filterType === 'select' && filter.options) {
        const option = filter.options.find((opt) => opt.value === value);
        return option?.label || value;
      }

      if (filter.filterType === 'date' && typeof value === 'object' && 'from' in value) {
        return `${value.from} - ${value.to}`;
      }

      return String(value);
    },
    [getFilterByKey],
  );

  return {
    activeFilters,
    visibleFilters,
    hasActiveFilters,
    updateFilter,
    clearFilters,
    clearFilter,
    getFilterByKey,
    formatFilterValue,
  };
}
