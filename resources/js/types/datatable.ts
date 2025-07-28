// DataTable Type Definitions

export interface DataTablePagination {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
  path: string;
  first_page_url: string;
  last_page_url: string;
  next_page_url: string | null;
  prev_page_url: string | null;
  links: PaginationLink[];
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

export interface ColumnMetadata {
  key: string;
  label: string;
  type: 'string' | 'number' | 'enum' | 'currency' | 'datetime' | 'date' | 'boolean';
  sortable: boolean;
  searchable: boolean;
  visible: boolean;
  exportable: boolean;
  format?: string | null;
  align?: 'left' | 'center' | 'right' | null;
  width?: string | null;
  filter?: FilterMetadata | null;
  metadata?: any;
}

export interface FilterOption {
  value: string;
  label: string;
  icon?: string | null;
  disabled?: boolean;
  metadata?: any;
}

export interface FilterMetadata {
  key: string;
  label: string;
  type: string;
  filterType: 'search' | 'select' | 'multi-select' | 'date' | 'date-range' | 'number-range';
  placeholder?: string;
  icon?: string | null;
  width?: string | null;
  options?: FilterOption[] | null;
  sortable?: boolean;
  searchable?: boolean;
  required?: boolean;
  debounceMs?: number | null;
  maxItems?: number | null;
  presets?: FilterPreset[] | null;
  format?: string | null;
  minDate?: string | null;
  maxDate?: string | null;
  validation?: any[];
}

export interface FilterPreset {
  label: string;
  value: string;
}

export interface DataTableMetadata {
  columns: ColumnMetadata[];
  filters: FilterMetadata[];
  defaultFilters: string[];
  defaultSort: string;
  filterPresets: TableFilterPreset[];
  exportFormats?: string[];
  actions?: TableAction[];
  bulkActions?: string[];
  settings?: TableSettings;
}

export interface TableFilterPreset {
  id: string;
  name: string;
  description: string;
  filters: Record<string, any>;
  isDefault?: boolean;
  icon?: string;
}

export interface TableAction {
  id: string;
  label: string;
  icon: string;
  route: string;
  condition?: string;
  confirmRequired?: boolean;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
}

export interface TableSettings {
  searchableColumns?: string[];
  sortableColumns?: string[];
  refreshInterval?: number;
  pageSize?: number;
}

export interface DataTableProps<TData> {
  data: TData[];
  pagination: DataTablePagination;
  metadata: DataTableMetadata;
  onSort?: (column: string) => void;
  onFilter?: (filters: Record<string, any>) => void;
  onPageChange?: (page: number) => void;
  onPageSizeChange?: (pageSize: number) => void;
  isLoading?: boolean;
}

export interface DataTableState {
  sorting: SortingState;
  columnFilters: ColumnFiltersState;
  columnVisibility: VisibilityState;
  rowSelection: RowSelectionState;
  pagination: PaginationState;
}

export interface SortingState {
  id: string;
  desc: boolean;
}
[];

export interface ColumnFiltersState {
  id: string;
  value: any;
}
[];

export interface VisibilityState {
  [key: string]: boolean;
}

export interface RowSelectionState {
  [key: string]: boolean;
}

export interface PaginationState {
  pageIndex: number;
  pageSize: number;
}
