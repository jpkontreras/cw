import * as React from 'react';
import type { FilterConfig } from '@/components/datatable';
import type { ColumnDef } from '@tanstack/react-table';
import { Calendar, MapPin, Package } from 'lucide-react';

// Resource metadata types
export interface ResourceMetadata {
  columns: Record<string, ColumnMetadata>;
  filters: FilterMetadata[];
  defaultFilters?: string[];
  defaultSort?: string;
  filterPresets?: FilterPreset[];
  settings?: Record<string, any>;
}

export interface ColumnMetadata {
  key: string;
  label: string;
  type: string;
  sortable?: boolean;
  searchable?: boolean;
  visible?: boolean;
  exportable?: boolean;
  format?: string;
  align?: string;
  width?: string;
  filter?: FilterMetadata;
  metadata?: Record<string, any>;
}

export interface FilterMetadata {
  key: string;
  label: string;
  type: string;
  filterType: string;
  placeholder?: string;
  icon?: string;
  width?: string;
  options?: Array<{ value: string; label: string; icon?: string; disabled?: boolean }>;
  maxItems?: number;
  debounceMs?: number;
  presets?: any[];
  format?: string;
  minDate?: string;
  maxDate?: string;
  validation?: any[];
}

export interface FilterPreset {
  id: string;
  name: string;
  description?: string;
  filters: Record<string, any>;
  icon?: string;
  isDefault?: boolean;
}

// Icon mapping
const iconMap: Record<string, React.ComponentType<{ className?: string }>> = {
  Package,
  MapPin,
  Calendar,
};

// Convert server metadata to filter config
export function metadataToFilterConfig(metadata?: ResourceMetadata): FilterConfig[] {
  if (!metadata?.filters) return [];
  
  return metadata.filters.map((filter) => ({
    key: filter.key,
    label: filter.label,
    type: filter.filterType as any,
    placeholder: filter.placeholder,
    width: filter.width || 'w-auto',
    icon: filter.icon ? iconMap[filter.icon] : undefined,
    options: filter.options || [],
    maxItems: filter.maxItems,
    debounceMs: filter.debounceMs,
    presets: filter.presets,
    format: filter.format,
    minDate: filter.minDate ? new Date(filter.minDate) : undefined,
    maxDate: filter.maxDate ? new Date(filter.maxDate) : undefined,
  }));
}

// Get visible columns from metadata
export function getVisibleColumns(metadata?: ResourceMetadata): string[] {
  if (!metadata?.columns) return [];
  
  return Object.values(metadata.columns)
    .filter((col) => col.visible !== false && !['search', 'location_id', 'date'].includes(col.key))
    .map((col) => col.key);
}

// Get sortable columns from metadata
export function getSortableColumns(metadata?: ResourceMetadata): string[] {
  if (!metadata?.columns) return [];
  
  return Object.values(metadata.columns)
    .filter((col) => col.sortable)
    .map((col) => col.key);
}

// Parse sort string (e.g., "-created_at,order_number")
export function parseSortString(sort?: string): Array<{ id: string; desc: boolean }> {
  if (!sort) return [];
  
  return sort.split(',').map((field) => {
    const trimmed = field.trim();
    if (!trimmed) return null;
    
    const desc = trimmed.startsWith('-');
    const id = desc ? trimmed.substring(1) : trimmed;
    
    return { id, desc };
  }).filter(Boolean) as Array<{ id: string; desc: boolean }>;
}

// Convert sort array to string
export function sortToString(sorting: Array<{ id: string; desc: boolean }>): string {
  return sorting
    .map((s) => (s.desc ? '-' : '') + s.id)
    .join(',');
}

// Hook to process resource metadata
export function useResourceMetadata(metadata?: ResourceMetadata) {
  const filters = React.useMemo(() => metadataToFilterConfig(metadata), [metadata]);
  
  const visibleColumns = React.useMemo(() => getVisibleColumns(metadata), [metadata]);
  
  const sortableColumns = React.useMemo(() => getSortableColumns(metadata), [metadata]);
  
  const defaultSort = React.useMemo(() => 
    metadata?.defaultSort ? parseSortString(metadata.defaultSort) : [],
    [metadata]
  );
  
  const filterPresets = React.useMemo(() => 
    metadata?.filterPresets || [],
    [metadata]
  );
  
  const settings = React.useMemo(() => 
    metadata?.settings || {},
    [metadata]
  );
  
  return {
    filters,
    visibleColumns,
    sortableColumns,
    defaultSort,
    filterPresets,
    settings,
  };
}

// Get column definition from metadata
export function getColumnDef<TData>(
  col: ColumnMetadata,
  customCell?: (value: any, row: TData) => React.ReactNode
): ColumnDef<TData> {
  const def: ColumnDef<TData> = {
    accessorKey: col.key,
    header: col.label,
  };
  
  if (customCell) {
    def.cell = ({ getValue, row }) => customCell(getValue(), row.original);
  }
  
  if (col.sortable) {
    def.header = ({ column }) => (
      <button
        className="flex items-center gap-1 font-medium"
        onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
      >
        {col.label}
        <span className="text-muted-foreground">↕</span>
      </button>
    );
  }
  
  return def;
}