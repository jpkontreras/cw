import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import PageLayout from '@/layouts/page-layout';
import { InertiaDataTable } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Progress } from '@/components/ui/progress';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  Package, 
  AlertCircle, 
  TrendingDown, 
  TrendingUp,
  MoreHorizontal,
  Plus,
  Minus,
  RefreshCw,
  FileDown,
  ArrowUpDown,
  DollarSign,
  Box,
  ShoppingCart,
  Clock,
  Filter
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';

interface InventoryItem {
  id: number;
  item_id: number;
  item_name: string;
  variant_name: string | null;
  sku: string | null;
  location_name: string | null;
  quantity_on_hand: number;
  quantity_reserved: number;
  min_quantity: number;
  reorder_quantity: number;
  max_quantity: number | null;
  unit_cost: number;
  total_value: number;
  last_counted_at: string;
  last_restocked_at: string;
  low_stock: boolean;
  out_of_stock: boolean;
}

interface PageProps {
  inventory: InventoryItem[];
  pagination: any;
  metadata: any;
  low_stock_items: InventoryItem[];
  inventory_value: {
    total_value: number;
    total_items: number;
    value_by_category: Record<string, number>;
    item_count: number;
  };
  features: {
    stock_transfers: boolean;
    stock_reservation: boolean;
    auto_reorder: boolean;
    batch_tracking: boolean;
  };
  adjustment_types: Array<{ value: string; label: string }>;
  recent_adjustments: Array<{
    id: number;
    item_name: string;
    quantity_change: number;
    adjustment_type: string;
    reason: string;
    adjusted_by: string;
    adjusted_at: string;
  }>;
}

export default function InventoryIndex({ 
  inventory, 
  pagination, 
  metadata,
  low_stock_items,
  inventory_value,
  features,
  adjustment_types,
  recent_adjustments
}: PageProps) {
  const [adjustmentDialogOpen, setAdjustmentDialogOpen] = useState(false);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);
  const [transferDialogOpen, setTransferDialogOpen] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    item_id: '',
    variant_id: '',
    location_id: '',
    quantity_change: '',
    adjustment_type: 'recount',
    reason: '',
    notes: '',
  });

  const columns: ColumnDef<InventoryItem>[] = [
    {
      accessorKey: 'item_name',
      header: 'Item',
      cell: ({ row }) => {
        const item = row.original;
        return (
          <div className="flex flex-col">
            <span className="font-medium">{item.item_name}</span>
            {item.variant_name && (
              <span className="text-xs text-muted-foreground">{item.variant_name}</span>
            )}
            {item.sku && (
              <span className="text-xs text-muted-foreground">{item.sku}</span>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'location_name',
      header: 'Location',
      cell: ({ row }) => row.original.location_name || 'Default',
    },
    {
      id: 'stock_level',
      header: 'Stock Level',
      cell: ({ row }) => {
        const item = row.original;
        const available = item.quantity_on_hand - item.quantity_reserved;
        const percentage = item.reorder_quantity > 0 
          ? (item.quantity_on_hand / (item.reorder_quantity * 2)) * 100 
          : 0;
        
        return (
          <div className="space-y-1">
            <div className="flex items-center gap-2">
              <span className={cn(
                "font-medium",
                item.out_of_stock && "text-red-600",
                item.low_stock && !item.out_of_stock && "text-amber-600"
              )}>
                {item.quantity_on_hand}
              </span>
              {item.quantity_reserved > 0 && (
                <span className="text-xs text-muted-foreground">
                  ({item.quantity_reserved} reserved)
                </span>
              )}
              {(item.low_stock || item.out_of_stock) && (
                <AlertCircle className="h-3 w-3 text-amber-500" />
              )}
            </div>
            <Progress 
              value={Math.min(percentage, 100)} 
              className="h-1.5"
              indicatorClassName={cn(
                item.out_of_stock && "bg-red-500",
                item.low_stock && !item.out_of_stock && "bg-amber-500",
                !item.low_stock && !item.out_of_stock && "bg-green-500"
              )}
            />
          </div>
        );
      },
    },
    {
      id: 'levels',
      header: 'Min / Reorder / Max',
      cell: ({ row }) => {
        const item = row.original;
        return (
          <div className="text-sm">
            <span>{item.min_quantity}</span>
            <span className="text-muted-foreground"> / </span>
            <span className="font-medium">{item.reorder_quantity}</span>
            <span className="text-muted-foreground"> / </span>
            <span>{item.max_quantity || '∞'}</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'unit_cost',
      header: 'Unit Cost',
      cell: ({ row }) => formatCurrency(row.original.unit_cost),
    },
    {
      accessorKey: 'total_value',
      header: 'Total Value',
      cell: ({ row }) => (
        <span className="font-medium">
          {formatCurrency(row.original.total_value)}
        </span>
      ),
    },
    {
      id: 'status',
      header: 'Status',
      cell: ({ row }) => {
        const item = row.original;
        if (item.out_of_stock) {
          return <Badge variant="destructive">Out of Stock</Badge>;
        }
        if (item.low_stock) {
          return <Badge variant="warning">Low Stock</Badge>;
        }
        return <Badge variant="success">In Stock</Badge>;
      },
    },
    {
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
              <DropdownMenuItem onClick={() => handleAdjustment(item)}>
                <ArrowUpDown className="mr-2 h-4 w-4" />
                Adjust Stock
              </DropdownMenuItem>
              {features.stock_transfers && (
                <DropdownMenuItem onClick={() => handleTransfer(item)}>
                  <RefreshCw className="mr-2 h-4 w-4" />
                  Transfer Stock
                </DropdownMenuItem>
              )}
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => router.visit(`/inventory/history?item_id=${item.item_id}`)}>
                View History
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => router.visit(`/items/${item.item_id}`)}>
                View Item
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const handleAdjustment = (item: InventoryItem) => {
    setSelectedItem(item);
    setData({
      item_id: item.item_id.toString(),
      variant_id: '',
      location_id: '',
      quantity_change: '',
      adjustment_type: 'recount',
      reason: '',
      notes: '',
    });
    setAdjustmentDialogOpen(true);
  };

  const handleTransfer = (item: InventoryItem) => {
    setSelectedItem(item);
    setTransferDialogOpen(true);
  };

  const submitAdjustment = (e: React.FormEvent) => {
    e.preventDefault();
    post('/inventory/adjust', {
      onSuccess: () => {
        setAdjustmentDialogOpen(false);
        reset();
      },
    });
  };

  const statsCards = [
    {
      title: 'Total Value',
      value: formatCurrency(inventory_value.total_value),
      icon: DollarSign,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: 'Total Items',
      value: inventory_value.total_items.toLocaleString(),
      icon: Package,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: 'Low Stock Items',
      value: low_stock_items.length,
      icon: AlertCircle,
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
      alert: low_stock_items.length > 0,
    },
    {
      title: 'Items Tracked',
      value: inventory_value.item_count,
      icon: Box,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
  ];

  return (
    <AppLayout>
      <Head title="Inventory Management" />
      
      <PageLayout>
        <PageLayout.Header
          title="Inventory Management"
          subtitle="Track and manage stock levels across all items"
          actions={
            <PageLayout.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/inventory/stock-take')}
              >
                <ShoppingCart className="mr-2 h-4 w-4" />
                Stock Take
              </Button>
              {features.stock_transfers && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => router.visit('/inventory/transfers')}
                >
                  <RefreshCw className="mr-2 h-4 w-4" />
                  Transfers
                </Button>
              )}
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/inventory/export')}
              >
                <FileDown className="mr-2 h-4 w-4" />
                Export
              </Button>
              <Button
                size="sm"
                onClick={() => router.visit('/inventory/adjustments')}
              >
                <ArrowUpDown className="mr-2 h-4 w-4" />
                New Adjustment
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
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
                    {stat.alert && (
                      <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                        Requires attention
                      </p>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>

          {/* Low Stock Alert */}
          {low_stock_items.length > 0 && (
            <Alert className="mb-6">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                <span className="font-medium">{low_stock_items.length} items</span> are running low on stock.
                {features.auto_reorder && (
                  <Button 
                    variant="link" 
                    className="ml-2 p-0 h-auto"
                    onClick={() => router.visit('/inventory/reorder-settings')}
                  >
                    Configure auto-reorder
                  </Button>
                )}
              </AlertDescription>
            </Alert>
          )}

          <Tabs defaultValue="all" className="w-full">
            <TabsList>
              <TabsTrigger value="all">All Items</TabsTrigger>
              <TabsTrigger value="low-stock">Low Stock</TabsTrigger>
              <TabsTrigger value="recent">Recent Activity</TabsTrigger>
            </TabsList>

            <TabsContent value="all" className="mt-6">
              <Card>
                <CardContent className="p-0">
                  <InertiaDataTable
                    columns={columns}
                    data={inventory}
                    pagination={pagination}
                    filters={metadata?.filters}
                  />
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="low-stock" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Low Stock Items</CardTitle>
                  <CardDescription>
                    Items that need to be restocked soon
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {low_stock_items.map((item) => (
                      <div key={item.id} className="flex items-center justify-between p-4 border rounded-lg">
                        <div className="flex-1">
                          <h4 className="font-medium">{item.item_name}</h4>
                          {item.variant_name && (
                            <p className="text-sm text-muted-foreground">{item.variant_name}</p>
                          )}
                          <div className="flex items-center gap-4 mt-2">
                            <Badge variant="warning">
                              {item.quantity_on_hand} in stock
                            </Badge>
                            <span className="text-sm text-muted-foreground">
                              Reorder at: {item.reorder_quantity}
                            </span>
                          </div>
                        </div>
                        <Button
                          size="sm"
                          onClick={() => handleAdjustment(item)}
                        >
                          Restock
                        </Button>
                      </div>
                    ))}
                    
                    {low_stock_items.length === 0 && (
                      <div className="text-center py-8">
                        <Package className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <p className="text-muted-foreground">All items are well stocked</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="recent" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Recent Adjustments</CardTitle>
                  <CardDescription>
                    Latest inventory changes and adjustments
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {recent_adjustments.map((adjustment) => (
                      <div key={adjustment.id} className="flex items-center justify-between">
                        <div className="flex-1">
                          <div className="flex items-center gap-2">
                            <span className="font-medium">{adjustment.item_name}</span>
                            <Badge variant="outline" className="text-xs">
                              {adjustment.adjustment_type}
                            </Badge>
                          </div>
                          <p className="text-sm text-muted-foreground mt-1">
                            {adjustment.reason} • by {adjustment.adjusted_by}
                          </p>
                        </div>
                        <div className="text-right">
                          <div className={cn(
                            "font-medium",
                            adjustment.quantity_change > 0 ? "text-green-600" : "text-red-600"
                          )}>
                            {adjustment.quantity_change > 0 ? '+' : ''}{adjustment.quantity_change}
                          </div>
                          <p className="text-xs text-muted-foreground">
                            {formatDate(adjustment.adjusted_at)}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>

          {/* Value by Category */}
          {Object.keys(inventory_value.value_by_category).length > 0 && (
            <Card className="mt-6">
              <CardHeader>
                <CardTitle>Inventory Value by Category</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {Object.entries(inventory_value.value_by_category).map(([category, value]) => {
                    const percentage = (value / inventory_value.total_value) * 100;
                    return (
                      <div key={category}>
                        <div className="flex justify-between mb-1">
                          <span className="text-sm font-medium">{category}</span>
                          <span className="text-sm">{formatCurrency(value)}</span>
                        </div>
                        <Progress value={percentage} className="h-2" />
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          )}
        </PageLayout.Content>
      </PageLayout>

      {/* Adjustment Dialog */}
      <Dialog open={adjustmentDialogOpen} onOpenChange={setAdjustmentDialogOpen}>
        <DialogContent>
          <form onSubmit={submitAdjustment}>
            <DialogHeader>
              <DialogTitle>Adjust Stock</DialogTitle>
              <DialogDescription>
                {selectedItem && `Adjusting stock for ${selectedItem.item_name}`}
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4 my-4">
              <div className="space-y-2">
                <Label htmlFor="adjustment_type">Adjustment Type</Label>
                <Select
                  value={data.adjustment_type}
                  onValueChange={(value) => setData('adjustment_type', value)}
                >
                  <SelectTrigger>
                    <SelectValue placeholder="Select type" />
                  </SelectTrigger>
                  <SelectContent>
                    {adjustment_types.map((type) => (
                      <SelectItem key={type.value} value={type.value}>
                        {type.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="quantity_change">
                  Quantity Change <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="quantity_change"
                  type="number"
                  value={data.quantity_change}
                  onChange={(e) => setData('quantity_change', e.target.value)}
                  placeholder="Enter positive or negative number"
                  className={errors.quantity_change ? 'border-destructive' : ''}
                />
                {errors.quantity_change && (
                  <p className="text-sm text-destructive">{errors.quantity_change}</p>
                )}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="reason">
                  Reason <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="reason"
                  value={data.reason}
                  onChange={(e) => setData('reason', e.target.value)}
                  placeholder="Brief reason for adjustment"
                  className={errors.reason ? 'border-destructive' : ''}
                />
                {errors.reason && (
                  <p className="text-sm text-destructive">{errors.reason}</p>
                )}
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="notes">Notes</Label>
                <Textarea
                  id="notes"
                  value={data.notes}
                  onChange={(e) => setData('notes', e.target.value)}
                  placeholder="Additional notes (optional)"
                  rows={3}
                />
              </div>
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAdjustmentDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                Save Adjustment
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}