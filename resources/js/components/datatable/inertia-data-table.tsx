import { router } from '@inertiajs/react'
import * as React from 'react'
import { ColumnDef } from '@tanstack/react-table'
import { DataTable } from './data-table'
import { DataTableFilters } from './filters'
import { useInertiaFilters } from './hooks/use-filters'
import { useDataTable } from './hooks/use-data-table'
import type { FilterConfig, FilterValue } from './filters/types'
import { cn } from '@/lib/utils'

export interface PaginationData {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

export interface InertiaDataTableProps<TData, TValue> {
  // Data props
  data: TData[]
  columns: ColumnDef<TData, TValue>[]
  pagination?: PaginationData
  
  // Filter props
  filters?: FilterConfig[]
  filterValues?: Record<string, FilterValue>
  preserveQueryParams?: string[]
  
  // Table options
  pageSize?: number
  showSearch?: boolean
  searchPlaceholder?: string
  showColumnToggle?: boolean
  showPagination?: boolean
  stickyHeader?: boolean
  maxHeight?: string
  
  // Inertia options
  preserveState?: boolean
  preserveScroll?: boolean
  only?: string[]
  routeName?: string
  
  // Callbacks
  onRowClick?: (row: TData) => void
  onSort?: (sorting: any) => void
  
  // Custom components
  toolbar?: React.ReactNode
  emptyState?: React.ReactNode
  
  className?: string
}

export function InertiaDataTable<TData, TValue>({
  data,
  columns,
  pagination,
  filters = [],
  filterValues = {},
  preserveQueryParams = [],
  pageSize = 10,
  showSearch = true,
  searchPlaceholder = 'Search...',
  showColumnToggle = true,
  showPagination = true,
  stickyHeader = false,
  maxHeight,
  preserveState = false,
  preserveScroll = true,
  only,
  routeName,
  onRowClick,
  onSort,
  toolbar,
  emptyState,
  className,
}: InertiaDataTableProps<TData, TValue>) {
  // Get current route if not provided
  const currentRoute = routeName || window.location.pathname

  // Extract filter keys from config
  const filterKeys = React.useMemo(
    () => filters.map((f) => f.key),
    [filters]
  )

  // Use Inertia filters hook for URL sync
  const {
    values: activeFilters,
    setValue: setFilter,
    reset: resetFilters,
    hasActiveFilters,
  } = useInertiaFilters([...filterKeys, 'search', 'page', 'sort'], {
    initialValues: filterValues,
    preserveState,
    preserveScroll,
    only,
  })

  // Handle search with debounce
  const [searchValue, setSearchValue] = React.useState(
    (activeFilters.search as string) || ''
  )
  
  // Sync search value when activeFilters change (e.g., from URL navigation)
  React.useEffect(() => {
    const newSearchValue = (activeFilters.search as string) || ''
    if (newSearchValue !== searchValue) {
      setSearchValue(newSearchValue)
    }
  }, [activeFilters.search])

  const searchTimeoutRef = React.useRef<NodeJS.Timeout>()
  const isFirstRender = React.useRef(true)

  React.useEffect(() => {
    // Skip on first render to avoid initial sync
    if (isFirstRender.current) {
      isFirstRender.current = false
      return
    }

    if (searchTimeoutRef.current) {
      clearTimeout(searchTimeoutRef.current)
    }

    searchTimeoutRef.current = setTimeout(() => {
      if (searchValue !== activeFilters.search) {
        setFilter('search', searchValue || undefined)
      }
    }, 300)

    return () => {
      if (searchTimeoutRef.current) {
        clearTimeout(searchTimeoutRef.current)
      }
    }
  }, [searchValue]) // Remove activeFilters.search dependency to avoid circular updates

  // Handle pagination
  const handlePageChange = React.useCallback(
    (page: number) => {
      const params = new URLSearchParams(window.location.search)
      
      // Preserve existing params
      preserveQueryParams.forEach((param) => {
        const value = params.get(param)
        if (value) params.set(param, value)
      })
      
      // Update page
      if (page > 1) {
        params.set('page', String(page))
      } else {
        params.delete('page')
      }

      router.get(currentRoute, Object.fromEntries(params), {
        preserveState,
        preserveScroll,
        only,
        replace: true,
      })
    },
    [currentRoute, preserveQueryParams, preserveState, preserveScroll, only]
  )

  // Handle sorting
  const handleSort = React.useCallback(
    (sorting: any) => {
      if (onSort) {
        onSort(sorting)
      } else {
        const sortString = sorting
          .map((s: any) => `${s.desc ? '-' : ''}${s.id}`)
          .join(',')
        
        setFilter('sort', sortString || undefined)
      }
    },
    [onSort, setFilter]
  )

  // Create data table instance
  const { table } = useDataTable({
    data,
    columns,
    pageSize: pagination?.per_page || pageSize,
    manualPagination: !!pagination,
    manualSorting: true,
    rowCount: pagination?.total,
  })

  // Override table pagination handlers if server-side
  React.useEffect(() => {
    if (pagination) {
      table.setPageIndex(pagination.current_page - 1)
    }
  }, [pagination, table])

  return (
    <div className={cn('space-y-4', className)}>
      {/* Toolbar section */}
      <div className="flex items-center justify-between">
        <div className="flex flex-1 items-center space-x-2">
          {showSearch && (
            <input
              type="text"
              placeholder={searchPlaceholder}
              value={searchValue}
              onChange={(e) => setSearchValue(e.target.value)}
              className="h-10 w-full max-w-sm rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            />
          )}
          
          {filters.length > 0 && (
            <DataTableFilters
              filters={filters}
              values={activeFilters}
              onChange={setFilter}
              onReset={() => {
                resetFilters([...filterKeys, 'search'])
                setSearchValue('')
              }}
              showReset={hasActiveFilters}
            />
          )}
        </div>
        
        {toolbar}
      </div>

      {/* Data table */}
      <DataTable
        columns={columns}
        data={data}
        showSearch={false} // We handle search above
        showPagination={false} // We handle pagination below
        showColumnToggle={showColumnToggle}
        onRowClick={onRowClick}
        stickyHeader={stickyHeader}
        maxHeight={maxHeight}
      />

      {/* Custom empty state */}
      {data.length === 0 && emptyState}

      {/* Server-side pagination */}
      {showPagination && pagination && pagination.last_page > 1 && (
        <div className="flex items-center justify-between px-2 py-4">
          <div className="text-sm text-muted-foreground">
            Showing {pagination.from} to {pagination.to} of {pagination.total} results
          </div>
          <div className="flex items-center space-x-2">
            <button
              className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
              onClick={() => handlePageChange(pagination.current_page - 1)}
              disabled={pagination.current_page <= 1}
            >
              Previous
            </button>
            
            {/* Page numbers */}
            <div className="flex items-center gap-1">
              {Array.from({ length: Math.min(5, pagination.last_page) }, (_, i) => {
                const pageNumber = i + 1
                return (
                  <button
                    key={pageNumber}
                    className={cn(
                      'inline-flex h-9 w-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                      pagination.current_page === pageNumber
                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                        : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground'
                    )}
                    onClick={() => handlePageChange(pageNumber)}
                  >
                    {pageNumber}
                  </button>
                )
              })}
              
              {pagination.last_page > 5 && (
                <>
                  <span className="px-2">...</span>
                  <button
                    className={cn(
                      'inline-flex h-9 w-9 items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2',
                      pagination.current_page === pagination.last_page
                        ? 'bg-primary text-primary-foreground hover:bg-primary/90'
                        : 'border border-input bg-background hover:bg-accent hover:text-accent-foreground'
                    )}
                    onClick={() => handlePageChange(pagination.last_page)}
                  >
                    {pagination.last_page}
                  </button>
                </>
              )}
            </div>

            <button
              className="inline-flex items-center justify-center rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-input bg-background hover:bg-accent hover:text-accent-foreground h-9 px-3"
              onClick={() => handlePageChange(pagination.current_page + 1)}
              disabled={pagination.current_page >= pagination.last_page}
            >
              Next
            </button>
          </div>
        </div>
      )}
    </div>
  )
}