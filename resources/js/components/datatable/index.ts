// Export the main DataTable components
export { DataTable } from './data-table';
export { InertiaDataTable } from './inertia-data-table';
export type { InertiaDataTableProps, PaginationData } from './inertia-data-table';

// Export all filter components and types
export { DataTableFilters } from './filters';
export type { 
  FilterConfig, 
  FilterValue, 
  FilterOption,
  DataTableFiltersProps,
  SelectFilterConfig,
  DateFilterConfig,
  SearchFilterConfig,
  MultiSelectFilterConfig,
  DateRangeFilterConfig,
  BooleanFilterConfig,
  UseFiltersReturn,
  FilterState,
  InertiaFilterConfig,
  FilterValidator,
  FilterConfigWithValidation
} from './filters/types';

// Export type guards
export {
  isStringFilter,
  isArrayFilter,
  isDateFilter,
  isDateRangeFilter,
  isBooleanFilter
} from './filters/types';

// Export hooks
export { useDataTable, useInertiaTableState, useDebouncedSearch } from './hooks/use-data-table';
export { useFilters, useInertiaFilters } from './hooks/use-filters';

// Export individual filter components if needed
export { SelectFilter } from './filters/select-filter';
export { DateFilter } from './filters/date-filter';
export { SearchFilter } from './filters/search-filter';
export { FilterReset, countActiveFilters } from './filters/filter-reset';