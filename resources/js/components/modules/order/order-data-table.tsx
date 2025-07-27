import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { InertiaDataTable, type FilterConfig, type PaginationData } from '@/components/datatable';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Order } from '@/types/modules/order';
import { formatCurrency, formatOrderNumber, getOrderAge, getStatusColor, getStatusLabel, getTypeLabel } from '@/types/modules/order/utils';
import { router } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, Calendar, Edit, Eye, MapPin, MoreHorizontal, Package, Receipt, Trash } from 'lucide-react';
import * as React from 'react';

interface OrderDataTableProps {
  orders: Order[];
  pagination?: PaginationData;
  locations: Array<{ id: number; name: string }>;
  statuses: string[];
  types: string[];
  filters: Record<string, any>;
  onExport?: () => void;
  preserveQueryParams?: string[];
}

export function OrderDataTable({
  orders,
  pagination,
  locations,
  statuses,
  types,
  filters,
  onExport,
  preserveQueryParams = [],
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

  // Configure filters with icons
  const filterConfig: FilterConfig[] = React.useMemo(() => [
    {
      key: 'search',
      label: 'Orders',
      type: 'search',
      placeholder: 'Search orders...',
      width: 'w-[250px]',
      debounceMs: 300,
    },
    {
      key: 'status',
      label: 'Status',
      type: 'multi-select',
      placeholder: 'Filter by status',
      width: 'w-[180px]',
      icon: Package,
      options: statuses.map((status) => ({
        value: status,
        label: getStatusLabel(status as any),
      })),
      maxItems: 3,
    },
    {
      key: 'type',
      label: 'Type',
      type: 'select',
      placeholder: 'All Types',
      width: 'w-[140px]',
      options: types.map((type) => ({
        value: type,
        label: getTypeLabel(type as any),
      })),
    },
    {
      key: 'location_id',
      label: 'Location',
      type: 'select',
      placeholder: 'All Locations',
      width: 'w-[180px]',
      icon: MapPin,
      options: locations.map((location) => ({
        value: location.id.toString(),
        label: location.name,
      })),
    },
    {
      key: 'date',
      label: 'Date',
      type: 'date',
      placeholder: 'All Time',
      width: 'w-[140px]',
      icon: Calendar,
    },
  ], [statuses, types, locations]);

  // Toolbar with export button
  const toolbar = (
    <div className="flex items-center gap-2">
      {onExport && (
        <Button variant="outline" size="sm" onClick={onExport}>
          Export
        </Button>
      )}
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
          {Object.entries(columnVisibility).map(([key, value]) => (
            <DropdownMenuCheckboxItem
              key={key}
              checked={value}
              onCheckedChange={(checked) =>
                setColumnVisibility((prev) => ({ ...prev, [key]: checked }))
              }
            >
              {key.charAt(0).toUpperCase() + key.slice(1).replace(/([A-Z])/g, ' $1')}
            </DropdownMenuCheckboxItem>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );

  return (
    <InertiaDataTable
      columns={columns}
      data={orders}
      pagination={pagination}
      filters={filterConfig}
      filterValues={filters}
      preserveQueryParams={preserveQueryParams}
      showColumnToggle={false}
      stickyHeader={true}
      maxHeight="calc(100vh - 16rem)"
      preserveScroll={true}
      only={['orders', 'pagination', 'filters']}
      onRowClick={(order) => router.visit(`/orders/${order.id}`)}
      toolbar={toolbar}
      emptyState={
        <div className="flex h-[450px] shrink-0 items-center justify-center rounded-md border border-dashed">
          <div className="mx-auto flex max-w-[420px] flex-col items-center justify-center text-center">
            <Package className="h-10 w-10 text-muted-foreground" />
            <h3 className="mt-4 text-lg font-semibold">No orders found</h3>
            <p className="mb-4 mt-2 text-sm text-muted-foreground">
              No orders match your current filters. Try adjusting your search criteria.
            </p>
          </div>
        </div>
      }
    />
  );
}
