import { ColumnMetadata } from '@/types/datatable';
import * as React from 'react';

export interface SortState {
  column: string;
  direction: 'asc' | 'desc';
}

export interface UseDataTableSortOptions {
  columns: ColumnMetadata[];
  defaultSort?: string;
  onSortChange?: (sort: string) => void;
}

export function useDataTableSort({ columns, defaultSort, onSortChange }: UseDataTableSortOptions) {
  // Parse initial sort state from defaultSort (e.g., '-created_at' means desc on created_at)
  const initialSort = React.useMemo<SortState | null>(() => {
    if (!defaultSort) return null;

    const isDesc = defaultSort.startsWith('-');
    const column = isDesc ? defaultSort.slice(1) : defaultSort;

    return {
      column,
      direction: isDesc ? 'desc' : 'asc',
    };
  }, [defaultSort]);

  const [sortState, setSortState] = React.useState<SortState | null>(initialSort);

  // Get sortable columns
  const sortableColumns = React.useMemo(() => {
    return columns.filter((col) => col.sortable);
  }, [columns]);

  // Toggle sort for a column
  const toggleSort = React.useCallback(
    (columnKey: string) => {
      setSortState((prev) => {
        let newState: SortState | null;

        if (!prev || prev.column !== columnKey) {
          // Start with ascending if not currently sorted
          newState = { column: columnKey, direction: 'asc' };
        } else if (prev.direction === 'asc') {
          // Switch to descending
          newState = { column: columnKey, direction: 'desc' };
        } else {
          // Clear sort
          newState = null;
        }

        // Notify parent component
        if (onSortChange) {
          if (newState) {
            const sortString = newState.direction === 'desc' ? `-${newState.column}` : newState.column;
            onSortChange(sortString);
          } else {
            onSortChange('');
          }
        }

        return newState;
      });
    },
    [onSortChange],
  );

  // Set sort directly
  const setSort = React.useCallback(
    (column: string | null, direction: 'asc' | 'desc' = 'asc') => {
      const newState = column ? { column, direction } : null;
      setSortState(newState);

      if (onSortChange) {
        if (newState) {
          const sortString = newState.direction === 'desc' ? `-${newState.column}` : newState.column;
          onSortChange(sortString);
        } else {
          onSortChange('');
        }
      }
    },
    [onSortChange],
  );

  // Clear sort
  const clearSort = React.useCallback(() => {
    setSortState(null);
    if (onSortChange) {
      onSortChange('');
    }
  }, [onSortChange]);

  // Get sort state for a specific column
  const getColumnSort = React.useCallback(
    (columnKey: string): 'asc' | 'desc' | false => {
      if (!sortState || sortState.column !== columnKey) return false;
      return sortState.direction;
    },
    [sortState],
  );

  // Check if column is sortable
  const isColumnSortable = React.useCallback(
    (columnKey: string) => {
      return columns.some((col) => col.key === columnKey && col.sortable);
    },
    [columns],
  );

  return {
    sortState,
    sortableColumns,
    toggleSort,
    setSort,
    clearSort,
    getColumnSort,
    isColumnSortable,
  };
}
