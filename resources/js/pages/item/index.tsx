import { useState, useMemo } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuSeparator,
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import { 
  Card, 
  CardContent, 
  CardDescription, 
  CardHeader, 
  CardTitle 
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { 
  ChevronDown, 
  Download, 
  FileUp, 
  MoreHorizontal, 
  Package, 
  Plus,
  AlertCircle,
  TrendingUp,
  DollarSign,
  Box
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { type BreadcrumbItem } from '@/types';
import { EmptyState } from '@/components/empty-state';

interface Item {
  id: number;
  name: string;
  description: string | null;
  type: 'product' | 'service' | 'combo';
  category_name: string | null;
  base_price: number;
  cost: number | null;
  sku: string | null;
  is_available: boolean;
  track_stock: boolean;
  variants_count: number;
  modifiers_count: number;
  current_stock?: number;
  low_stock?: boolean;
}

interface PageProps {
  items: Item[];
  pagination: any;
  metadata: any;
  features: {
    variants: boolean;
    modifiers: boolean;
    dynamic_pricing: boolean;
    inventory: boolean;
    recipes: boolean;
  };
  stats?: {
    totalItems: number;
    activeItems: number;
    lowStockCount: number;
    totalValue: number;
  };
}

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Items',
    href: '/items',
  },
];

function ItemsIndexContent({ 
  items, 
  pagination, 
  metadata, 
  features,
  stats 
}: PageProps) {
  const [selectedItems, setSelectedItems] = useState<number[]>([]);
  const [importDialogOpen, setImportDialogOpen] = useState(false);
  const [exportDialogOpen, setExportDialogOpen] = useState(false);

  const columns = useMemo<ColumnDef<Item>[]>(() => {
    const cols: ColumnDef<Item>[] = [
      {
        id: 'select',
        header: () => {
          const allSelected = items.length > 0 && items.every(item => selectedItems.includes(item.id));
          const someSelected = items.some(item => selectedItems.includes(item.id));
          return (
            <div className="flex w-8 justify-center">
              <Checkbox
                checked={allSelected}
                // indeterminate={someSelected && !allSelected}
                onCheckedChange={(value) => {
                  if (value) {
                    setSelectedItems(items.map(item => item.id));
                  } else {
                    setSelectedItems([]);
                  }
                }}
                aria-label="Select all"
              />
            </div>
          );
        },
        cell: ({ row }) => (
          <div className="flex w-8 justify-center">
            <Checkbox
              checked={selectedItems.includes(row.original.id)}
              onCheckedChange={(value) => {
                if (value) {
                  setSelectedItems([...selectedItems, row.original.id]);
                } else {
                  setSelectedItems(selectedItems.filter(id => id !== row.original.id));
                }
              }}
              aria-label="Select row"
            />
          </div>
        ),
        size: 32,
        enableSorting: false,
        enableHiding: false,
      },
      {
        accessorKey: 'name',
        header: 'Item',
        cell: ({ row }) => {
          const item = row.original;
          return (
            <div className="flex flex-col">
              <div className="flex items-center gap-2">
                <span className="font-medium">{item.name}</span>
                {item.sku && (
                  <Badge variant="secondary" className="text-xs">
                    {item.sku}
                  </Badge>
                )}
              </div>
              {item.description && (
                <span className="text-xs text-muted-foreground line-clamp-1">
                  {item.description}
                </span>
              )}
            </div>
          );
        },
      },
      {
        accessorKey: 'type',
        header: 'Type',
        cell: ({ row }) => {
          const typeStyles = {
            product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
            combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
          };
          return (
            <Badge 
              variant="secondary" 
              className={cn('capitalize', typeStyles[row.original.type])}
            >
              {row.original.type}
            </Badge>
          );
        },
      },
      {
        accessorKey: 'categoryName',
        header: 'Category',
        cell: ({ row }) => row.original.categoryName || (
          <span className="text-muted-foreground">Uncategorized</span>
        ),
      },
      {
        accessorKey: 'basePrice',
        header: 'Price',
        cell: ({ row }) => (
          <div className="font-medium">
            {row.original.basePrice ? formatCurrency(row.original.basePrice) : '—'}
          </div>
        ),
      },
    ];

    // Add inventory column if feature is enabled
    if (features.inventory) {
      cols.push({
        accessorKey: 'stockQuantity',
        header: 'Stock',
        cell: ({ row }) => {
          const item = row.original;
          if (!item.trackInventory) {
            return <span className="text-muted-foreground">—</span>;
          }
          
          const isLowStock = item.stockQuantity < (item.lowStockThreshold || 10);
          return (
            <div className="flex items-center gap-1">
              {isLowStock && (
                <AlertCircle className="h-3 w-3 text-amber-500" />
              )}
              <span className={cn(
                isLowStock && 'text-amber-600 dark:text-amber-400 font-medium'
              )}>
                {item.stockQuantity ?? 0}
              </span>
            </div>
          );
        },
      });
    }

    // Add features column
    cols.push({
      id: 'features',
      header: 'Features',
      cell: ({ row }) => {
        const item = row.original;
        return (
          <div className="flex gap-1">
            {features.variants && item.variants_count > 0 && (
              <Badge variant="outline" className="text-xs">
                {item.variants_count} variants
              </Badge>
            )}
            {features.modifiers && item.modifiers_count > 0 && (
              <Badge variant="outline" className="text-xs">
                Modifiers
              </Badge>
            )}
          </div>
        );
      },
    });

    // Add status column
    cols.push({
      accessorKey: 'isAvailable',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.isAvailable ? 'default' : 'secondary'} className={row.original.isAvailable ? 'bg-green-500 hover:bg-green-600' : ''}>
          {row.original.isAvailable ? 'Available' : 'Unavailable'}
        </Badge>
      ),
    });

    // Add actions column
    cols.push({
      id: 'actions',
      cell: ({ row }) => {
        const item = row.original;
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => router.visit(`/items/${item.id}`)}>
                View details
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.visit(`/items/${item.id}/edit`)}>
                Edit
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              {features.inventory && item.trackInventory && (
                <DropdownMenuItem onClick={() => router.visit(`/inventory?item_id=${item.id}`)}>
                  Manage inventory
                </DropdownMenuItem>
              )}
              {features.dynamic_pricing && (
                <DropdownMenuItem onClick={() => router.visit(`/pricing?item_id=${item.id}`)}>
                  Pricing rules
                </DropdownMenuItem>
              )}
              {features.recipes && (
                <DropdownMenuItem onClick={() => router.visit(`/recipes?item_id=${item.id}`)}>
                  Recipe
                </DropdownMenuItem>
              )}
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                className="text-destructive"
                onClick={() => handleDelete(item.id)}
              >
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    });

    return cols;
  }, [features, items, selectedItems]);

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this item?')) {
      router.delete(`/items/${id}`);
    }
  };

  const handleBulkAction = (action: string) => {
    if (selectedItems.length === 0) {
      alert('Please select items first');
      return;
    }

    switch (action) {
      case 'delete':
        if (confirm(`Delete ${selectedItems.length} items?`)) {
          router.post('/items/bulk-update', {
            item_ids: selectedItems,
            action: 'delete',
          });
        }
        break;
      case 'make_available':
        router.post('/items/bulk-update', {
          item_ids: selectedItems,
          action: 'update_availability',
          data: { isAvailable: true },
        });
        break;
      case 'make_unavailable':
        router.post('/items/bulk-update', {
          item_ids: selectedItems,
          action: 'update_availability',
          data: { isAvailable: false },
        });
        break;
    }
  };

  const statsCards = stats ? [
    {
      title: 'Total Items',
      value: stats.totalItems,
      icon: Package,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: 'Active Items',
      value: stats.activeItems,
      icon: TrendingUp,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: 'Low Stock',
      value: stats.lowStockCount,
      icon: AlertCircle,
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
    },
    {
      title: 'Inventory Value',
      value: formatCurrency(stats.totalValue),
      icon: DollarSign,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
  ] : [];

  return (
    <>
      <Page.Header
        title="Items"
        subtitle="Manage your products, services, and combos"
        actions={
          items.length > 0 && (
            <>
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="outline" size="sm">
                    <FileUp className="mr-2 h-4 w-4" />
                    Import
                    <ChevronDown className="ml-2 h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent>
                  <DropdownMenuItem onClick={() => setImportDialogOpen(true)}>
                    Import from CSV
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={() => router.get('/items/import-template')}>
                    Download template
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
              
              <Button 
                variant="outline" 
                size="sm"
                onClick={() => setExportDialogOpen(true)}
              >
                <Download className="mr-2 h-4 w-4" />
                Export
              </Button>
              
              <Link href="/items/create">
                <Button size="sm">
                  <Plus className="mr-2 h-4 w-4" />
                  New Item
                </Button>
              </Link>
            </>
          )
        }
      />

      <Page.Content>
        {items.length === 0 ? (
          <EmptyState
            icon={Package}
            title="No items in your inventory"
            description="Add products, services, or combos to start building your catalog. Your items will appear here once created."
            actions={
              <Link href="/items/create">
                <Button size="lg">
                  <Plus className="mr-2 h-4 w-4" />
                  Add First Item
                </Button>
              </Link>
            }
            helpText={
              <>
                Need help? Read our <a href="#" className="text-primary hover:underline">inventory guide</a>
              </>
            }
          />
        ) : (
          <div className="space-y-6">
          {/* Stats Cards */}
          {statsCards.length > 0 && (
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
              {statsCards.map((stat, index) => {
                const Icon = stat.icon;
                return (
                  <Card key={index}>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                      <CardTitle className="text-sm font-medium">
                        {stat.title}
                      </CardTitle>
                      <div className={cn('p-2 rounded-lg', stat.bgColor)}>
                        <Icon className={cn('h-4 w-4', stat.color)} />
                      </div>
                    </CardHeader>
                    <CardContent>
                      <div className="text-2xl font-bold">{stat.value}</div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>
            )}

            {/* Bulk Actions */}
            {selectedItems.length > 0 && (
            <Alert>
              <AlertDescription className="flex items-center justify-between">
                <span>{selectedItems.length} items selected</span>
                <div className="flex gap-2">
                  <Button 
                    size="sm" 
                    variant="outline"
                    onClick={() => handleBulkAction('make_available')}
                  >
                    Make Available
                  </Button>
                  <Button 
                    size="sm" 
                    variant="outline"
                    onClick={() => handleBulkAction('make_unavailable')}
                  >
                    Make Unavailable
                  </Button>
                  <Button 
                    size="sm" 
                    variant="outline"
                    className="text-destructive"
                    onClick={() => handleBulkAction('delete')}
                  >
                    Delete
                  </Button>
                </div>
              </AlertDescription>
            </Alert>
          )}

          {/* Data Table */}
          <InertiaDataTable
            columns={columns}
            data={items}
            pagination={pagination}
            metadata={metadata}
            rowClickRoute="/items/:id"
          />
          </div>
        )}

        {/* Import Dialog */}
        <Dialog open={importDialogOpen} onOpenChange={setImportDialogOpen}>
          <DialogContent>
          <DialogHeader>
            <DialogTitle>Import Items</DialogTitle>
            <DialogDescription>
              Upload a CSV file to import items in bulk
            </DialogDescription>
          </DialogHeader>
          {/* Import form would go here */}
          </DialogContent>
        </Dialog>

      {/* Export Dialog */}
      <Dialog open={exportDialogOpen} onOpenChange={setExportDialogOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Export Items</DialogTitle>
            <DialogDescription>
              Choose export format and options
            </DialogDescription>
          </DialogHeader>
          {/* Export form would go here */}
          </DialogContent>
        </Dialog>
      </Page.Content>
    </>
  );
}

export default function ItemsIndex(props: PageProps) {
  return (
    <AppLayout>
      <Head title="Items" />
      <Page>
        <ItemsIndexContent {...props} />
      </Page>
    </AppLayout>
  );
}