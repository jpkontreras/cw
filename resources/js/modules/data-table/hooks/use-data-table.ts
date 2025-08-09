import { DataTableMetadata, DataTablePagination } from '@/types/datatable';
import * as React from 'react';
import { useDataTableFilters } from './use-data-table-filters';
import { useDataTableSort } from './use-data-table-sort';

export interface UseDataTableOptions {
  metadata: DataTableMetadata;
  pagination: DataTablePagination;
  initialFilters?: Record<string, any>;
  initialSort?: string;
  onFilterChange?: (filters: Record<string, any>) => void;
  onSortChange?: (sort: string) => void;
  onPageChange?: (page: number) => void;
  onPageSizeChange?: (pageSize: number) => void;
}

export function useDataTable({
  metadata,
  pagination,
  initialFilters = {},
  initialSort,
  onFilterChange,
  onSortChange,
  onPageChange,
  onPageSizeChange,
}: UseDataTableOptions) {
  // Use filter hook
  const filterHelpers = useDataTableFilters({
    filters: metadata.filters,
    defaultFilters: metadata.defaultFilters,
    initialValues: initialFilters,
    onFilterChange,
  });

  // Use sort hook
  const sortHelpers = useDataTableSort({
    columns: metadata.columns,
    defaultSort: initialSort || metadata.defaultSort,
    onSortChange,
  });

  // Pagination helpers
  const canGoToPreviousPage = pagination.current_page > 1;
  const canGoToNextPage = pagination.current_page < pagination.last_page;

  const goToPage = React.useCallback(
    (page: number) => {
      if (page >= 1 && page <= pagination.last_page && onPageChange) {
        onPageChange(page);
      }
    },
    [pagination.last_page, onPageChange],
  );

  const goToPreviousPage = React.useCallback(() => {
    if (canGoToPreviousPage) {
      goToPage(pagination.current_page - 1);
    }
  }, [canGoToPreviousPage, goToPage, pagination.current_page]);

  const goToNextPage = React.useCallback(() => {
    if (canGoToNextPage) {
      goToPage(pagination.current_page + 1);
    }
  }, [canGoToNextPage, goToPage, pagination.current_page]);

  const setPageSize = React.useCallback(
    (pageSize: number) => {
      if (onPageSizeChange) {
        onPageSizeChange(pageSize);
      }
    },
    [onPageSizeChange],
  );

  // Get active preset
  const activePreset = React.useMemo(() => {
    if (!metadata.filterPresets) return null;

    return metadata.filterPresets.find((preset) => {
      const presetFilters = preset.filters;
      const activeFilters = filterHelpers.activeFilters;

      // Check if all preset filters match active filters
      const presetKeys = Object.keys(presetFilters);
      const activeKeys = Object.keys(activeFilters);

      if (presetKeys.length !== activeKeys.length) return false;

      return presetKeys.every((key) => {
        const presetValue = presetFilters[key];
        const activeValue = activeFilters[key];

        if (Array.isArray(presetValue) && Array.isArray(activeValue)) {
          return presetValue.length === activeValue.length && presetValue.every((v) => activeValue.includes(v));
        }

        return presetValue === activeValue;
      });
    });
  }, [metadata.filterPresets, filterHelpers.activeFilters]);

  // Apply preset
  const applyPreset = React.useCallback(
    (presetId: string) => {
      const preset = metadata.filterPresets?.find((p) => p.id === presetId);
      if (!preset) return;

      // Clear existing filters
      filterHelpers.clearFilters();

      // Apply preset filters
      Object.entries(preset.filters).forEach(([key, value]) => {
        filterHelpers.updateFilter(key, value);
      });
    },
    [metadata.filterPresets, filterHelpers],
  );

  return {
    // Filter helpers
    ...filterHelpers,

    // Sort helpers
    ...sortHelpers,

    // Pagination helpers
    pagination,
    canGoToPreviousPage,
    canGoToNextPage,
    goToPage,
    goToPreviousPage,
    goToNextPage,
    setPageSize,

    // Preset helpers
    activePreset,
    applyPreset,

    // Metadata
    metadata,
  };
}
