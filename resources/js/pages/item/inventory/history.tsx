import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { DatePickerWithRange } from '@/components/ui/date-range-picker';
import { 
  History as HistoryIcon, 
  ArrowUp,
  ArrowDown,
  Package,
  Filter,
  Download,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { DateRange } from 'react-day-picker';
import { addDays } from 'date-fns';

interface Movement {
  id: number;
  item_name: string;
  variant_name?: string;
  movement_type: string;
  quantity: number;
  before_quantity: number;
  after_quantity: number;
  reason?: string;
  user_name?: string;
  created_at: string;
  location_name?: string;
}

interface Item {
  id: number;
  name: string;
}

interface PageProps {
  history: Movement[];
  item?: Item;
  filters: {
    item_id?: string;
    variant_id?: string;
    location_id?: string;
    start_date?: string;
    end_date?: string;
  };
}

export default function InventoryHistory({ history, item, filters }: PageProps) {
  const handleFilterChange = (key: string, value: any) => {
    const newFilters = { ...filters, [key]: value };
    
    // Remove empty values
    Object.keys(newFilters).forEach(k => {
      if (!newFilters[k]) delete newFilters[k];
    });
    
    router.get(route('inventory.history'), newFilters, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleDateRangeChange = (range: DateRange | undefined) => {
    if (range?.from && range?.to) {
      handleFilterChange('start_date', range.from.toISOString().split('T')[0]);
      handleFilterChange('end_date', range.to.toISOString().split('T')[0]);
    } else {
      handleFilterChange('start_date', '');
      handleFilterChange('end_date', '');
    }
  };

  const getMovementIcon = (type: string, quantity: number) => {
    if (quantity > 0) {
      return <ArrowUp className="h-4 w-4 text-green-600" />;
    } else if (quantity < 0) {
      return <ArrowDown className="h-4 w-4 text-red-600" />;
    }
    return null;
  };

  const getMovementTypeLabel = (type: string) => {
    const labels: Record<string, string> = {
      initial: 'Initial Stock',
      purchase: 'Purchase',
      sale: 'Sale',
      adjustment: 'Adjustment',
      transfer_in: 'Transfer In',
      transfer_out: 'Transfer Out',
      waste: 'Waste',
      return: 'Return',
      production: 'Production',
      stock_take: 'Stock Take',
    };
    return labels[type] || type;
  };

  const getMovementTypeBadge = (type: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
      sale: 'destructive',
      purchase: 'secondary',
      adjustment: 'outline',
      transfer_in: 'default',
      transfer_out: 'default',
      waste: 'destructive',
    };
    
    return (
      <Badge variant={variants[type] || 'outline'} className="text-xs">
        {getMovementTypeLabel(type)}
      </Badge>
    );
  };

  const dateRange: DateRange | undefined = filters.start_date && filters.end_date
    ? {
        from: new Date(filters.start_date),
        to: new Date(filters.end_date),
      }
    : undefined;

  return (
    <AppLayout>
      <Head title="Inventory History" />

      <Page
        title="Inventory Movement History"
        description="Track all inventory movements and adjustments"
        actions={
          <Button variant="outline">
            <Download className="mr-2 h-4 w-4" />
            Export
          </Button>
        }
      >
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Filters</CardTitle>
              <CardDescription>
                Filter inventory movements by item, date range, and more
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div className="space-y-2">
                  <Label>Item ID</Label>
                  <Input
                    type="number"
                    value={filters.item_id || ''}
                    onChange={(e) => handleFilterChange('item_id', e.target.value)}
                    placeholder="Filter by item"
                  />
                </div>
                
                <div className="space-y-2">
                  <Label>Variant ID</Label>
                  <Input
                    type="number"
                    value={filters.variant_id || ''}
                    onChange={(e) => handleFilterChange('variant_id', e.target.value)}
                    placeholder="Filter by variant"
                  />
                </div>
                
                <div className="space-y-2 md:col-span-2">
                  <Label>Date Range</Label>
                  <DatePickerWithRange
                    date={dateRange}
                    onDateChange={handleDateRangeChange}
                  />
                </div>
              </div>
              
              {item && (
                <div className="mt-4">
                  <Alert>
                    <Package className="h-4 w-4" />
                    <AlertDescription>
                      Showing history for: <strong>{item.name}</strong>
                    </AlertDescription>
                  </Alert>
                </div>
              )}
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Movement History</CardTitle>
              <CardDescription>
                All inventory movements in chronological order
              </CardDescription>
            </CardHeader>
            <CardContent>
              {history.length > 0 ? (
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Date/Time</TableHead>
                      <TableHead>Item</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead className="text-right">Quantity</TableHead>
                      <TableHead className="text-right">Before</TableHead>
                      <TableHead className="text-right">After</TableHead>
                      <TableHead>Reason</TableHead>
                      <TableHead>User</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {history.map((movement) => (
                      <TableRow key={movement.id}>
                        <TableCell className="text-sm text-muted-foreground">
                          {new Date(movement.created_at).toLocaleString()}
                        </TableCell>
                        <TableCell>
                          <div>
                            <div className="font-medium">{movement.item_name}</div>
                            {movement.variant_name && (
                              <div className="text-sm text-muted-foreground">
                                {movement.variant_name}
                              </div>
                            )}
                          </div>
                        </TableCell>
                        <TableCell>
                          {getMovementTypeBadge(movement.movement_type)}
                        </TableCell>
                        <TableCell className="text-right">
                          <div className="flex items-center justify-end gap-1">
                            {getMovementIcon(movement.movement_type, movement.quantity)}
                            <span className={cn(
                              "font-medium",
                              movement.quantity > 0 && "text-green-600",
                              movement.quantity < 0 && "text-red-600"
                            )}>
                              {movement.quantity > 0 && '+'}
                              {movement.quantity}
                            </span>
                          </div>
                        </TableCell>
                        <TableCell className="text-right">
                          {movement.before_quantity}
                        </TableCell>
                        <TableCell className="text-right font-medium">
                          {movement.after_quantity}
                        </TableCell>
                        <TableCell>
                          <span className="text-sm text-muted-foreground">
                            {movement.reason || '-'}
                          </span>
                        </TableCell>
                        <TableCell>
                          <span className="text-sm text-muted-foreground">
                            {movement.user_name || 'System'}
                          </span>
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              ) : (
                <div className="text-center py-12 text-muted-foreground">
                  <HistoryIcon className="mx-auto h-12 w-12 mb-4 opacity-30" />
                  <p>No movement history found</p>
                  <p className="text-sm mt-2">Try adjusting your filters</p>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </Page>
    </AppLayout>
  );
}