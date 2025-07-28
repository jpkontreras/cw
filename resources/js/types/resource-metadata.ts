// Resource metadata types shared across the application

export interface ResourceMetadata {
  columns: Record<string, ColumnMetadata>;
  filters: FilterMetadata[];
  defaultFilters?: string[];
  defaultSort?: string;
  filterPresets?: FilterPreset[];
  exportFormats?: string[];
  actions?: ResourceAction[];
  bulkActions?: string[];
  settings?: Record<string, any>;
  perPageOptions?: number[];
  defaultPerPage?: number;
  rowActions?: boolean;
}

export interface ColumnMetadata {
  key: string;
  label: string;
  type: ColumnType;
  sortable?: boolean;
  searchable?: boolean;
  visible?: boolean;
  exportable?: boolean;
  format?: string;
  align?: 'left' | 'center' | 'right';
  width?: string;
  filter?: FilterMetadata;
  metadata?: Record<string, any>;
}

export interface FilterMetadata {
  key: string;
  label: string;
  type: string;
  filterType: FilterType;
  placeholder?: string;
  icon?: string;
  width?: string;
  options?: FilterOption[];
  maxItems?: number;
  debounceMs?: number;
  presets?: FilterPreset[];
  format?: string;
  minDate?: string;
  maxDate?: string;
  validation?: any[];
}

export interface FilterOption {
  value: string;
  label: string;
  icon?: string;
  disabled?: boolean;
  metadata?: Record<string, any>;
}

export interface FilterPreset {
  id: string;
  name: string;
  description?: string;
  filters: Record<string, any>;
  icon?: string;
  isDefault?: boolean;
}

export interface ResourceAction {
  id: string;
  label: string;
  icon?: string;
  route?: string;
  handler?: (item: any) => void;
  condition?: string | ((item: any) => boolean);
  confirmRequired?: boolean;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost';
}

export type ColumnType = 'string' | 'number' | 'boolean' | 'date' | 'datetime' | 'currency' | 'enum' | 'array' | 'object';

export type FilterType = 'search' | 'select' | 'multi-select' | 'date' | 'date-range' | 'boolean' | 'number-range';

// Helper type for paginated responses
export interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    path: string;
    first_page_url: string | null;
    last_page_url: string | null;
    next_page_url: string | null;
    prev_page_url: string | null;
    links: Array<{
      url: string | null;
      label: string;
      active: boolean;
    }>;
  };
  metadata?: ResourceMetadata;
}

// Type guards
export function isEnumColumn(column: ColumnMetadata): boolean {
  return column.type === 'enum' && !!column.filter?.options;
}

export function isDateColumn(column: ColumnMetadata): boolean {
  return column.type === 'date' || column.type === 'datetime';
}

export function isCurrencyColumn(column: ColumnMetadata): boolean {
  return column.type === 'currency';
}

export function isSearchableColumn(column: ColumnMetadata): boolean {
  return column.searchable === true;
}

export function isSortableColumn(column: ColumnMetadata): boolean {
  return column.sortable === true;
}
