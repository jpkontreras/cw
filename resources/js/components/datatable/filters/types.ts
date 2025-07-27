export type FilterType = 'select' | 'multi-select' | 'date' | 'date-range' | 'search' | 'boolean';

// Generic constraint for filter values
export type FilterValueConstraint = string | string[] | Date | { from: Date; to: Date } | boolean | undefined | null;

export interface FilterOption {
  value: string;
  label: string;
  icon?: React.ComponentType<{ className?: string }>;
  disabled?: boolean;
}

export interface BaseFilterConfig {
  key: string;
  label: string;
  placeholder?: string;
  className?: string;
  width?: string;
  disabled?: boolean;
  icon?: React.ComponentType<{ className?: string }>;
}

export interface SelectFilterConfig extends BaseFilterConfig {
  type: 'select';
  options: FilterOption[] | (() => Promise<FilterOption[]>);
  allowClear?: boolean;
  searchable?: boolean;
}

export interface MultiSelectFilterConfig extends BaseFilterConfig {
  type: 'multi-select';
  options: FilterOption[] | (() => Promise<FilterOption[]>);
  maxItems?: number;
}

export interface DateFilterConfig extends BaseFilterConfig {
  type: 'date';
  format?: string;
  minDate?: Date;
  maxDate?: Date;
  presets?: Array<{
    label: string;
    value: string;
    getValue: () => Date | string;
  }>;
}

export interface DateRangeFilterConfig extends BaseFilterConfig {
  type: 'date-range';
  format?: string;
  minDate?: Date;
  maxDate?: Date;
  presets?: Array<{
    label: string;
    value: string;
    getValue: () => { from: Date; to: Date };
  }>;
}

export interface SearchFilterConfig extends BaseFilterConfig {
  type: 'search';
  debounceMs?: number;
  minLength?: number;
}

export interface BooleanFilterConfig extends BaseFilterConfig {
  type: 'boolean';
  trueLabel?: string;
  falseLabel?: string;
}

export type FilterConfig = 
  | SelectFilterConfig 
  | MultiSelectFilterConfig 
  | DateFilterConfig 
  | DateRangeFilterConfig 
  | SearchFilterConfig
  | BooleanFilterConfig;

export type FilterValue = FilterValueConstraint;

// Type guards for filter values
export const isStringFilter = (value: FilterValue): value is string => 
  typeof value === 'string';

export const isArrayFilter = (value: FilterValue): value is string[] => 
  Array.isArray(value);

export const isDateFilter = (value: FilterValue): value is Date => 
  value instanceof Date;

export const isDateRangeFilter = (value: FilterValue): value is { from: Date; to: Date } => 
  value !== null && 
  typeof value === 'object' && 
  'from' in value && 
  'to' in value;

export const isBooleanFilter = (value: FilterValue): value is boolean => 
  typeof value === 'boolean';

export interface DataTableFiltersProps {
  filters: FilterConfig[];
  values: Record<string, FilterValue>;
  onChange: (key: string, value: FilterValue) => void;
  onReset?: () => void;
  onApply?: () => void;
  className?: string;
  showReset?: boolean;
  resetLabel?: string;
  applyLabel?: string;
  inline?: boolean;
  responsive?: boolean;
}

export type FilterChangeHandler = (key: string, value: FilterValue) => void;

export type FilterResetHandler = () => void;

// Enhanced filter state management
export interface FilterState {
  values: Record<string, FilterValue>;
  touched: Record<string, boolean>;
  errors: Record<string, string>;
}

export interface UseFiltersReturn {
  values: Record<string, FilterValue>;
  setValue: (key: string, value: FilterValue) => void;
  setValues: (values: Record<string, FilterValue>) => void;
  reset: (keys?: string[]) => void;
  clear: () => void;
  hasActiveFilters: boolean;
  activeFilterCount: number;
  touchedKeys: string[];
}

export interface SingleFilterProps<T extends FilterConfig = FilterConfig> {
  config: T;
  value: FilterValue;
  onChange: (value: FilterValue) => void;
  className?: string;
}

export interface FilterGroupConfig {
  label?: string;
  filters: FilterConfig[];
  collapsible?: boolean;
  defaultExpanded?: boolean;
}

export interface DataTableFiltersGroupedProps extends Omit<DataTableFiltersProps, 'filters'> {
  groups: FilterGroupConfig[];
}

export interface FilterPreset {
  id: string;
  name: string;
  description?: string;
  filters: Record<string, FilterValue>;
  isDefault?: boolean;
}

export interface FilterPresetsConfig {
  presets: FilterPreset[];
  onSavePreset?: (name: string, filters: Record<string, FilterValue>) => void;
  onDeletePreset?: (id: string) => void;
  onApplyPreset?: (preset: FilterPreset) => void;
  allowUserPresets?: boolean;
  maxUserPresets?: number;
}

// Inertia-specific filter types
export interface InertiaFilterConfig extends DataTableFiltersProps {
  preserveState?: boolean;
  preserveScroll?: boolean;
  only?: string[];
  replace?: boolean;
  debounce?: number;
}

// Filter validation
export interface FilterValidator<T = FilterValue> {
  (value: T): string | undefined;
}

export interface FilterConfigWithValidation extends FilterConfig {
  validate?: FilterValidator;
  validateAsync?: (value: FilterValue) => Promise<string | undefined>;
}