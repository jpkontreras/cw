import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { HoverCard, HoverCardContent, HoverCardTrigger } from '@/components/ui/hover-card';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { ColumnMetadata, DataTableProps } from '@/types/datatable';
import { router } from '@inertiajs/react';
import { ColumnDef, flexRender, getCoreRowModel, useReactTable, VisibilityState } from '@tanstack/react-table';
import { ArrowDown, ArrowUp, ArrowUpDown, Search, MoreVertical, MoreHorizontal, X, Filter } from 'lucide-react';
import * as React from 'react';
import { DataTablePagination } from './data-table-pagination';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MultiSelectFilter } from './filters';

interface InertiaDataTableProps<TData, TValue> extends DataTableProps<TData> {
  columns?: ColumnDef<TData, TValue>[];
  className?: string;
}

export function InertiaDataTable<TData, TValue>({ data, pagination, metadata, columns, className }: InertiaDataTableProps<TData, TValue>) {
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({});
  const [isSearchExpanded, setIsSearchExpanded] = React.useState(false);

  // Simple navigation function
  const navigate = (params: Record<string, string | number>) => {
    router.get(window.location.pathname, params, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  };

  // Generate columns from metadata if not provided
  const tableColumns = React.useMemo(() => {
    if (columns) return columns;

    const columnsArray = Object.values(metadata?.columns || {})
      .filter((col: any) => col.visible && col.key !== 'search')
      .map((col: any) => ({
        id: col.key,
        accessorKey: col.key,
        header: () => {
          const params = new URLSearchParams(window.location.search);
          const currentSort = params.get('sort') || '';
          const isSortedAsc = currentSort === col.key;
          const isSortedDesc = currentSort === `-${col.key}`;
          const isSorted = isSortedAsc || isSortedDesc;

          const handleSort = () => {
            if (!col.sortable) return;

            if (!isSorted) {
              // Not sorted -> Ascending
              params.set('sort', col.key);
            } else if (isSortedAsc) {
              // Ascending -> Descending
              params.set('sort', `-${col.key}`);
            } else {
              // Descending -> No sort
              params.delete('sort');
            }

            navigate(Object.fromEntries(params));
          };

          const currentFilterValue = params.get(col.key) || '';
          const selectedValues = currentFilterValue ? currentFilterValue.split(',') : [];

          const handleColumnFilter = (value: string) => {
            if (value && value !== '__all__') {
              params.set(col.key, value);
            } else {
              params.delete(col.key);
            }
            params.set('page', '1');
            navigate(Object.fromEntries(params));
          };

          const handleMultiSelectChange = (value: string, checked: boolean) => {
            let newValues = [...selectedValues];
            if (checked) {
              if (!newValues.includes(value)) {
                newValues.push(value);
              }
            } else {
              newValues = newValues.filter((v) => v !== value);
            }

            if (newValues.length > 0) {
              params.set(col.key, newValues.join(','));
            } else {
              params.delete(col.key);
            }
            params.set('page', '1');
            navigate(Object.fromEntries(params));
          };

          const clearFilter = () => {
            params.delete(col.key);
            params.set('page', '1');
            navigate(Object.fromEntries(params));
          };

          const hasActiveFilter = (col.filter || col.type === 'string') && currentFilterValue !== '';
          const showFilterIcon = col.filter || col.type === 'string';

          return (
            <div className="flex w-full items-center justify-between gap-2">
              <div className="flex items-center gap-1 flex-1">
                <span className="font-medium">{col.label}</span>

                {showFilterIcon && (
                  <Popover modal={false}>
                    <PopoverTrigger asChild>
                      <Button variant={hasActiveFilter ? 'secondary' : 'ghost'} size="sm" className="h-5 w-5 p-0">
                        <Search className={cn('h-3 w-3', hasActiveFilter && 'text-primary')} />
                      </Button>
                    </PopoverTrigger>
                    <PopoverContent className="w-80" onOpenAutoFocus={(e) => e.preventDefault()}>
                      <div className="space-y-4">
                        <div className="flex items-center justify-between">
                          <h4 className="leading-none font-medium">{col.filter?.label || `Search ${col.label}`}</h4>
                          {hasActiveFilter && (
                            <Button variant="ghost" size="sm" onClick={clearFilter} className="h-auto p-1 text-xs">
                              Clear
                            </Button>
                          )}
                        </div>

                        {/* Text columns without explicit filter config - allow search */}
                        {col.type === 'string' && !col.filter && (
                          <div className="space-y-2">
                            <Input
                              id={`${col.key}-filter-input`}
                              placeholder={`Search ${col.label.toLowerCase()}...`}
                              defaultValue={currentFilterValue}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                  const input = e.target as HTMLInputElement;
                                  const value = input.value;
                                  const newParams = new URLSearchParams(window.location.search);
                                  if (value) {
                                    newParams.set(col.key, value);
                                  } else {
                                    newParams.delete(col.key);
                                  }
                                  newParams.set('page', '1');
                                  navigate(Object.fromEntries(newParams));
                                }
                              }}
                            />
                            <Button
                              size="sm"
                              className="w-full"
                              onClick={() => {
                                const input = document.getElementById(`${col.key}-filter-input`) as HTMLInputElement;
                                const value = input?.value || '';
                                const newParams = new URLSearchParams(window.location.search);
                                if (value) {
                                  newParams.set(col.key, value);
                                } else {
                                  newParams.delete(col.key);
                                }
                                newParams.set('page', '1');
                                navigate(Object.fromEntries(newParams));
                              }}
                            >
                              Filter
                            </Button>
                          </div>
                        )}

                        {col.filter?.filterType === 'search' && (
                          <div className="space-y-2">
                            <Input
                              id={`${col.key}-filter-input`}
                              placeholder={col.filter.placeholder}
                              defaultValue={currentFilterValue}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                  const input = e.target as HTMLInputElement;
                                  handleColumnFilter(input.value);
                                }
                              }}
                            />
                            <Button
                              size="sm"
                              className="w-full"
                              onClick={() => {
                                const input = document.getElementById(`${col.key}-filter-input`) as HTMLInputElement;
                                handleColumnFilter(input?.value || '');
                              }}
                            >
                              Filter
                            </Button>
                          </div>
                        )}

                        {col.filter?.filterType === 'select' && (
                          <Select value={currentFilterValue || '__all__'} onValueChange={handleColumnFilter}>
                            <SelectTrigger>
                              <SelectValue placeholder={col.filter.placeholder} />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="__all__">{col.filter.placeholder || 'All'}</SelectItem>
                              {col.filter.options?.map((option) => (
                                <SelectItem key={option.value} value={option.value} disabled={option.disabled}>
                                  {option.label}
                                </SelectItem>
                              ))}
                            </SelectContent>
                          </Select>
                        )}

                        {col.filter?.filterType === 'multi-select' && (
                          <MultiSelectFilter
                            filter={col.filter}
                            value={selectedValues}
                            onChange={(values) => {
                              const params = new URLSearchParams(window.location.search);
                              if (values.length > 0) {
                                params.set(col.key, values.join(','));
                              } else {
                                params.delete(col.key);
                              }
                              params.set('page', '1');
                              navigate(Object.fromEntries(params));
                            }}
                          />
                        )}

                        {col.filter?.filterType === 'date' && (
                          <div className="space-y-2">
                            {col.filter.presets && col.filter.presets.length > 0 && (
                              <Select
                                value={currentFilterValue || '__custom__'}
                                onValueChange={(value) => {
                                  if (value !== '__custom__') {
                                    handleColumnFilter(value);
                                  }
                                }}
                              >
                                <SelectTrigger>
                                  <SelectValue placeholder="Select date range" />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="__custom__">Custom date</SelectItem>
                                  {col.filter.presets.map((preset) => (
                                    <SelectItem key={preset.value} value={preset.value}>
                                      {preset.label}
                                    </SelectItem>
                                  ))}
                                </SelectContent>
                              </Select>
                            )}
                            <Input
                              type="date"
                              defaultValue={currentFilterValue}
                              onChange={(e) => handleColumnFilter(e.target.value)}
                              max={col.filter.maxDate || undefined}
                              min={col.filter.minDate || undefined}
                            />
                          </div>
                        )}
                      </div>
                    </PopoverContent>
                  </Popover>
                )}
              </div>

              {col.sortable && (
                <Button 
                  variant="ghost" 
                  onClick={handleSort} 
                  size="sm" 
                  className="h-8 px-2 flex-shrink-0"
                  aria-label={`Sort by ${col.label}`}
                >
                  {isSortedDesc ? (
                    <ArrowDown className="h-4 w-4" />
                  ) : isSortedAsc ? (
                    <ArrowUp className="h-4 w-4" />
                  ) : (
                    <ArrowUpDown className="h-4 w-4 text-muted-foreground" />
                  )}
                </Button>
              )}
            </div>
          );
        },
        cell: ({ row }) => {
          const value = row.getValue(col.key);
          const formattedValue = formatCellValue(value, col);
          const stringValue = String(formattedValue);
          const needsTruncation = stringValue.length > 100;

          if (needsTruncation) {
            return (
              <HoverCard>
                <HoverCardTrigger asChild>
                  <div className={cn('cursor-help truncate', col.align && `text-${col.align}`)}>{stringValue.substring(0, 100)}...</div>
                </HoverCardTrigger>
                <HoverCardContent className="w-80">
                  <div className="space-y-2">
                    <p className="text-sm">{stringValue}</p>
                  </div>
                </HoverCardContent>
              </HoverCard>
            );
          }

          return <div className={cn('truncate', col.align && `text-${col.align}`)}>{formattedValue}</div>;
        },
        enableSorting: col.sortable,
        enableHiding: true,
      })) as ColumnDef<TData, TValue>[];
    
    // Add actions column if metadata includes row actions
    if (metadata.actions?.length > 0 || metadata.rowActions) {
      const actionsColumn: ColumnDef<TData, TValue> = {
        id: 'actions',
        enableSorting: false,
        enableHiding: false,
        cell: ({ row }) => {
          const item = row.original as any;
          
          return (
            <div className="flex justify-end">
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="icon" className="h-6 w-6" onClick={(e) => e.stopPropagation()}>
                    <MoreVertical className="h-3 w-3" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => router.visit(`/orders/${item.id}`)}>
                    View Details
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => router.visit(`/orders/${item.id}/edit`)}>
                    Edit
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => router.visit(`/orders/${item.id}/receipt`)}>
                    Print Receipt
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          );
        },
        header: () => <div className="text-right"></div>,
        meta: {
          isActionsColumn: true,
        },
      };
      
      return [...columnsArray, actionsColumn] as ColumnDef<TData, TValue>[];
    }
    
    return columnsArray;
  }, [columns, metadata]);

  const table = useReactTable({
    data,
    columns: tableColumns,
    getCoreRowModel: getCoreRowModel(),
    onColumnVisibilityChange: setColumnVisibility,
    state: {
      columnVisibility,
    },
    manualPagination: true,
    manualSorting: true,
    manualFiltering: true,
    pageCount: pagination.last_page,
  });

  // Simple filter handlers
  const handleSearchSubmit = (key: string, value: string) => {
    const params = new URLSearchParams(window.location.search);
    if (value) {
      params.set(key, value);
    } else {
      params.delete(key);
    }
    params.set('page', '1');
    navigate(Object.fromEntries(params));
  };

  const handleSelectChange = (key: string, value: string) => {
    const params = new URLSearchParams(window.location.search);
    if (value && value !== '__all__') {
      params.set(key, value);
    } else {
      params.delete(key);
    }
    params.set('page', '1');
    navigate(Object.fromEntries(params));
  };

  return (
    <div className={cn('space-y-4', className)}>
      {/* New toolbar design */}
      <div className="relative">
        {isSearchExpanded ? (
          // Expanded search view
          <div className="flex items-center gap-2">
            {metadata.filters
              .filter((filter) => filter.filterType === 'search' && metadata.defaultFilters.includes(filter.key))
              .map((filter) => {
                const urlParams = new URLSearchParams(window.location.search);
                const currentValue = urlParams.get(filter.key) || '';
                return (
                  <React.Fragment key={filter.key}>
                    <Input
                      placeholder={filter.placeholder}
                      defaultValue={currentValue}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter') {
                          handleSearchSubmit(filter.key, (e.target as HTMLInputElement).value);
                        }
                      }}
                      className="flex-1"
                      autoFocus
                    />
                    <Button
                      size="sm"
                      onClick={() => {
                        const input = document.querySelector('.flex-1') as HTMLInputElement;
                        handleSearchSubmit(filter.key, input?.value || '');
                      }}
                    >
                      Search
                    </Button>
                    <Button
                      variant="ghost"
                      size="sm"
                      onClick={() => setIsSearchExpanded(false)}
                    >
                      <X className="h-4 w-4" />
                    </Button>
                  </React.Fragment>
                );
              })}
          </div>
        ) : (
          // Collapsed toolbar view
          <div className="flex items-center justify-between gap-4">
            {/* Left: Search icon */}
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setIsSearchExpanded(true)}
              className="h-9 w-9 p-0"
            >
              <Search className="h-4 w-4" />
            </Button>

            {/* Center: Filter summary */}
            <div className="flex-1 flex items-center gap-2 overflow-x-auto">
              {(() => {
                const urlParams = new URLSearchParams(window.location.search);
                const activeFilters: { key: string; value: string; label: string }[] = [];
                
                // Collect all active filters
                Object.values(metadata?.columns || {}).forEach((col: any) => {
                  const value = urlParams.get(col.key);
                  if (value && col.key !== 'search') {
                    if (col.filter?.filterType === 'multi-select' && col.filter.options) {
                      const values = value.split(',');
                      const labels = values
                        .map(v => col.filter?.options?.find(opt => opt.value === v)?.label || v)
                        .join(', ');
                      activeFilters.push({
                        key: col.key,
                        value,
                        label: `${col.label}: ${labels}`
                      });
                    } else if (col.filter?.filterType === 'select' && col.filter.options) {
                      const option = col.filter.options.find(opt => opt.value === value);
                      activeFilters.push({
                        key: col.key,
                        value,
                        label: `${col.label}: ${option?.label || value}`
                      });
                    } else if (col.type === 'string') {
                      activeFilters.push({
                        key: col.key,
                        value,
                        label: `${col.label}: ${value}`
                      });
                    }
                  }
                });
                
                // Add search filter
                const searchValue = urlParams.get('search');
                if (searchValue) {
                  activeFilters.push({
                    key: 'search',
                    value: searchValue,
                    label: `Search: ${searchValue}`
                  });
                }
                
                if (activeFilters.length === 0) {
                  return (
                    <div className="text-sm text-muted-foreground">
                      No filters applied
                    </div>
                  );
                }
                
                return (
                  <>
                    <div className="flex items-center gap-1 text-sm text-muted-foreground">
                      <Filter className="h-3 w-3" />
                      <span>Filters:</span>
                    </div>
                    {activeFilters.map((filter) => (
                      <Badge
                        key={filter.key}
                        variant="secondary"
                        className="max-w-[200px] cursor-pointer hover:bg-secondary/80"
                        onClick={() => {
                          const newParams = new URLSearchParams(window.location.search);
                          newParams.delete(filter.key);
                          newParams.set('page', '1');
                          navigate(Object.fromEntries(newParams));
                        }}
                      >
                        <span className="truncate">{filter.label}</span>
                        <X className="ml-1 h-3 w-3 flex-shrink-0" />
                      </Badge>
                    ))}
                  </>
                );
              })()}
            </div>

            {/* Right: More options */}
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="h-9 w-9 p-0">
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  onClick={() => {
                    const params = new URLSearchParams();
                    const urlParams = new URLSearchParams(window.location.search);
                    const currentPage = urlParams.get('page');
                    const currentPerPage = urlParams.get('per_page');
                    if (currentPage) params.set('page', currentPage);
                    if (currentPerPage) params.set('per_page', currentPerPage);
                    navigate(Object.fromEntries(params));
                  }}
                  disabled={(() => {
                    const urlParams = new URLSearchParams(window.location.search);
                    return !Array.from(urlParams.keys()).some(key => 
                      key !== 'page' && key !== 'per_page' && urlParams.get(key)
                    );
                  })()}
                >
                  Clear all filters
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )}
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id} className="border-b">
                {headerGroup.headers.map((header) => {
                  const isActionsColumn = header.column.columnDef.meta?.isActionsColumn;
                  return (
                    <TableHead 
                      key={header.id} 
                      className={cn(
                        "border-r bg-muted/50 last:border-r-0",
                        isActionsColumn && "sticky right-0 z-10"
                      )}
                    >
                      {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                    </TableHead>
                  );
                })}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => {
                const item = row.original as any;
                const handleRowClick = () => {
                  if (item.id) {
                    router.visit(`/orders/${item.id}`);
                  }
                };
                
                return (
                  <TableRow 
                    key={row.id} 
                    className="border-b last:border-b-0 cursor-pointer hover:bg-muted/50"
                    onClick={handleRowClick}
                  >
                    {row.getVisibleCells().map((cell) => {
                      const isActionsColumn = cell.column.columnDef.meta?.isActionsColumn;
                      return (
                        <TableCell 
                          key={cell.id} 
                          className={cn(
                            "overflow-hidden border-r whitespace-nowrap last:border-r-0",
                            isActionsColumn && "sticky right-0 z-10 bg-gray-50 dark:bg-gray-900 border-l-2 border-gray-200 dark:border-gray-700"
                          )}
                        >
                          {flexRender(cell.column.columnDef.cell, cell.getContext())}
                        </TableCell>
                      );
                    })}
                  </TableRow>
                );
              })
            ) : (
              <TableRow>
                <TableCell colSpan={tableColumns.length} className="h-24 text-center">
                  No results.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>

      <DataTablePagination
        table={table}
        pagination={pagination}
        perPageOptions={metadata.perPageOptions}
        onPageChange={(page) => {
          const params = new URLSearchParams(window.location.search);
          params.set('page', String(page));
          navigate(Object.fromEntries(params));
        }}
        onPageSizeChange={(pageSize) => {
          const params = new URLSearchParams(window.location.search);
          params.set('per_page', String(pageSize));
          params.set('page', '1');
          navigate(Object.fromEntries(params));
        }}
      />
    </div>
  );
}

// Helper function to format cell values
function formatCellValue(value: unknown, column: ColumnMetadata): React.ReactNode {
  if (value === null || value === undefined) return '-';

  switch (column.type) {
    case 'number':
      // Handle arrays (like items count)
      if (Array.isArray(value)) {
        return value.length;
      }
      return typeof value === 'number' ? value : '-';

    case 'currency':
      return new Intl.NumberFormat('es-CL', {
        style: 'currency',
        currency: column.format || 'CLP',
      }).format(value);

    case 'datetime':
      if (column.format === 'relative') {
        const date = new Date(value);
        const now = new Date();
        const diff = now.getTime() - date.getTime();
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (minutes < 1) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        return `${days}d ago`;
      }
      return new Date(value).toLocaleString();

    case 'date':
      return new Date(value).toLocaleDateString();

    case 'enum':
      if (column.metadata?.options) {
        const option = column.metadata.options.find((opt: { value: string; label: string }) => opt.value === value);
        return option?.label || value;
      }
      return value;

    case 'boolean':
      return value ? 'Yes' : 'No';

    default:
      return String(value);
  }
}
