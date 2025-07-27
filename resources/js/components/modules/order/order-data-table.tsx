import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/ui/data-table';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Order } from '@/types/modules/order';
import { formatCurrency, formatOrderNumber, getOrderAge, getStatusColor, getStatusLabel, getTypeLabel } from '@/types/modules/order/utils';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Edit, Eye, MoreHorizontal, Receipt, Trash } from 'lucide-react';
import * as React from 'react';

interface OrderDataTableProps {
  orders: Order[];
  locations: Array<{ id: number; name: string }>;
  statuses: string[];
  types: string[];
  filters: Record<string, any>;
  onExport: () => void;
  onFilterChange: (key: string, value: string | undefined) => void;
  onSearch: (query: string) => void;
  searchQuery: string;
}

export function OrderDataTable({
  orders,
  locations,
  statuses,
  types,
  filters,
  onExport,
  onFilterChange,
  onSearch,
  searchQuery,
}: OrderDataTableProps) {
  const [columnVisibility, setColumnVisibility] = React.useState({
    orderNumber: true,
    customerName: true,
    type: true,
    status: true,
    items: true,
    totalAmount: true,
    paymentStatus: true,
    createdAt: true,
    actions: true,
  });
  const columns: ColumnDef<Order>[] = [
    {
      accessorKey: 'orderNumber',
      header: ({ column }) => {
        return (
          <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
            Order
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
      cell: ({ row }) => {
        const order = row.original;
        return (
          <div className="flex items-center gap-2">
            <span className="font-medium whitespace-nowrap">{formatOrderNumber(order.orderNumber)}</span>
            {order.priority === 'high' && (
              <Badge variant="destructive" className="shrink-0 text-xs">
                Priority
              </Badge>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'customerName',
      header: 'Customer',
      cell: ({ row }) => {
        const order = row.original;
        return (
          <div>
            <div className="font-medium whitespace-nowrap">{order.customerName || 'Walk-in'}</div>
            {order.tableNumber && <div className="text-sm text-gray-500 whitespace-nowrap">Table {order.tableNumber}</div>}
          </div>
        );
      },
    },
    {
      accessorKey: 'type',
      header: 'Type',
      cell: ({ row }) => {
        return <Badge variant="outline">{getTypeLabel(row.getValue('type'))}</Badge>;
      },
      filterFn: (row, id, value) => {
        return value === 'all' || row.getValue(id) === value;
      },
    },
    {
      accessorKey: 'status',
      header: ({ column }) => {
        return (
          <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
            Status
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
      cell: ({ row }) => {
        const status = row.getValue('status') as string;
        return <Badge className={getStatusColor(status as any)}>{getStatusLabel(status as any)}</Badge>;
      },
      filterFn: (row, id, value) => {
        return value === 'all' || row.getValue(id) === value;
      },
    },
    {
      id: 'items',
      header: 'Items',
      cell: ({ row }) => {
        const order = row.original;
        return <span className="text-center">{order.items?.length || 0}</span>;
      },
    },
    {
      accessorKey: 'totalAmount',
      header: ({ column }) => {
        return (
          <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
            Total
            <ArrowUpDown className="ml-2 h-4 w-4" />
          </Button>
        );
      },
      cell: ({ row }) => {
        return <div className="font-medium">{formatCurrency(row.getValue('totalAmount'))}</div>;
      },
    },
    {
      accessorKey: 'paymentStatus',
      header: 'Payment',
      cell: ({ row }) => {
        const status = row.getValue('paymentStatus') as string;
        return <Badge variant={status === 'paid' ? 'default' : 'secondary'}>{status}</Badge>;
      },
    },
    {
      accessorKey: 'createdAt',
      header: 'Time',
      cell: ({ row }) => {
        const order = row.original;
        return <span className="text-sm text-gray-500 whitespace-nowrap">{getOrderAge(order)}</span>;
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const order = row.original;

        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0" onClick={(e) => e.stopPropagation()}>
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuLabel>Actions</DropdownMenuLabel>
              <DropdownMenuItem onClick={() => router.visit(`/orders/${order.id}`)}>
                <Eye className="mr-2 h-4 w-4" />
                View details
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.visit(`/orders/${order.id}/edit`)}>
                <Edit className="mr-2 h-4 w-4" />
                Edit order
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.visit(`/orders/${order.id}/receipt`)}>
                <Receipt className="mr-2 h-4 w-4" />
                Print receipt
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={() => {
                  if (confirm('Are you sure you want to cancel this order?')) {
                    router.post(`/orders/${order.id}/cancel`);
                  }
                }}
                className="text-red-600"
              >
                <Trash className="mr-2 h-4 w-4" />
                Cancel order
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const activeFilterCount = Object.keys(filters).filter((key) => filters[key] && filters[key] !== 'all').length;

  return (
    <div className="space-y-4">
      <div className="flex flex-row gap-3">
        <div className="flex flex-wrap items-center gap-2">
          <Select value={filters.status || 'all'} onValueChange={(value) => onFilterChange('status', value === 'all' ? undefined : value)}>
            <SelectTrigger className="w-[140px]">
              <SelectValue placeholder="All Statuses" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Statuses</SelectItem>
              {statuses.map((status) => (
                <SelectItem key={status} value={status}>
                  {getStatusLabel(status as any)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={filters.type || 'all'} onValueChange={(value) => onFilterChange('type', value === 'all' ? undefined : value)}>
            <SelectTrigger className="w-[120px]">
              <SelectValue placeholder="All Types" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              {types.map((type) => (
                <SelectItem key={type} value={type}>
                  {getTypeLabel(type as any)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={filters.location_id || 'all'} onValueChange={(value) => onFilterChange('location_id', value === 'all' ? undefined : value)}>
            <SelectTrigger className="w-[160px]">
              <SelectValue placeholder="All Locations" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Locations</SelectItem>
              {locations.map((location) => (
                <SelectItem key={location.id} value={location.id.toString()}>
                  {location.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select value={filters.date || 'all'} onValueChange={(value) => onFilterChange('date', value === 'all' ? undefined : value)}>
            <SelectTrigger className="w-[120px]">
              <SelectValue placeholder="All Time" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Time</SelectItem>
              <SelectItem value="today">Today</SelectItem>
              <SelectItem value="yesterday">Yesterday</SelectItem>
              <SelectItem value="week">This Week</SelectItem>
              <SelectItem value="month">This Month</SelectItem>
            </SelectContent>
          </Select>

          {activeFilterCount > 0 && (
            <Button
              variant="ghost"
              onClick={() => {
                onFilterChange('status', undefined);
                onFilterChange('type', undefined);
                onFilterChange('location_id', undefined);
                onFilterChange('date', undefined);
                onSearch('');
              }}
              className="h-10 px-3"
              size="sm"
            >
              Reset
              <Badge variant="secondary" className="ml-2">
                {activeFilterCount}
              </Badge>
            </Button>
          )}

          {/* View button aligned to the right */}
          <div className="ml-auto">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="icon" className="h-10 w-10">
                  <Eye className="h-4 w-4" />
                  <span className="sr-only">View options</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-[150px]">
                <DropdownMenuLabel>Toggle columns</DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.orderNumber}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, orderNumber: checked }))}
                >
                  Order
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.customerName}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, customerName: checked }))}
                >
                  Customer
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.type}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, type: checked }))}
                >
                  Type
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.status}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, status: checked }))}
                >
                  Status
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.items}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, items: checked }))}
                >
                  Items
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.totalAmount}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, totalAmount: checked }))}
                >
                  Total
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.paymentStatus}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, paymentStatus: checked }))}
                >
                  Payment
                </DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem
                  checked={columnVisibility.createdAt}
                  onCheckedChange={(checked) => setColumnVisibility((prev) => ({ ...prev, createdAt: checked }))}
                >
                  Time
                </DropdownMenuCheckboxItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </div>

      {/* Data Table */}
      <DataTable
        columns={columns}
        data={orders}
        showColumnToggle={false}
        showPagination={false}
        showSearch={false}
        columnVisibility={columnVisibility}
        onColumnVisibilityChange={(visibility) => setColumnVisibility(visibility as any)}
        onRowClick={(order) => router.visit(`/orders/${order.id}`)}
        stickyHeader={true}
      />
    </div>
  );
}
