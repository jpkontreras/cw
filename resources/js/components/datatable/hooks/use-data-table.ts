import * as React from 'react'
import {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
  type Table,
} from '@tanstack/react-table'

interface UseDataTableProps<TData, TValue> {
  data: TData[]
  columns: ColumnDef<TData, TValue>[]
  pageSize?: number
  initialSorting?: SortingState
  initialColumnFilters?: ColumnFiltersState
  initialColumnVisibility?: VisibilityState
  manualPagination?: boolean
  manualSorting?: boolean
  manualFiltering?: boolean
  rowCount?: number
}

interface UseDataTableReturn<TData> {
  table: Table<TData>
  globalFilter: string
  setGlobalFilter: React.Dispatch<React.SetStateAction<string>>
}

export function useDataTable<TData, TValue>({
  data,
  columns,
  pageSize = 10,
  initialSorting = [],
  initialColumnFilters = [],
  initialColumnVisibility = {},
  manualPagination = false,
  manualSorting = false,
  manualFiltering = false,
  rowCount,
}: UseDataTableProps<TData, TValue>): UseDataTableReturn<TData> {
  const [sorting, setSorting] = React.useState<SortingState>(initialSorting)
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>(
    initialColumnFilters
  )
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>(
    initialColumnVisibility
  )
  const [globalFilter, setGlobalFilter] = React.useState('')
  const [pagination, setPagination] = React.useState({
    pageIndex: 0,
    pageSize,
  })

  // Memoize columns to prevent unnecessary re-renders
  const memoizedColumns = React.useMemo(() => columns, [columns])
  
  // Memoize data to prevent unnecessary re-renders
  const memoizedData = React.useMemo(() => data, [data])

  const table = useReactTable({
    data: memoizedData,
    columns: memoizedColumns,
    pageCount: manualPagination ? Math.ceil((rowCount || 0) / pagination.pageSize) : undefined,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      globalFilter,
      pagination,
    },
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    onGlobalFilterChange: setGlobalFilter,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: manualPagination ? undefined : getPaginationRowModel(),
    getSortedRowModel: manualSorting ? undefined : getSortedRowModel(),
    getFilteredRowModel: manualFiltering ? undefined : getFilteredRowModel(),
    manualPagination,
    manualSorting,
    manualFiltering,
    globalFilterFn: 'includesString',
  })

  return {
    table,
    globalFilter,
    setGlobalFilter,
  }
}

// Hook for managing table state persistence with Inertia
export function useInertiaTableState(key: string) {
  const [state, setState] = React.useState(() => {
    if (typeof window !== 'undefined') {
      const saved = sessionStorage.getItem(`table-state-${key}`)
      return saved ? JSON.parse(saved) : {}
    }
    return {}
  })

  React.useEffect(() => {
    if (typeof window !== 'undefined') {
      sessionStorage.setItem(`table-state-${key}`, JSON.stringify(state))
    }
  }, [key, state])

  return [state, setState] as const
}

// Hook for debounced search with Inertia
export function useDebouncedSearch(
  onChange: (value: string) => void,
  delay: number = 300
) {
  const [value, setValue] = React.useState('')
  const timeoutRef = React.useRef<NodeJS.Timeout>()

  React.useEffect(() => {
    if (timeoutRef.current) {
      clearTimeout(timeoutRef.current)
    }

    timeoutRef.current = setTimeout(() => {
      onChange(value)
    }, delay)

    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current)
      }
    }
  }, [value, delay, onChange])

  return [value, setValue] as const
}