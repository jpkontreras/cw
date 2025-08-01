import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import PageLayout from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
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
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { 
  Package, 
  AlertCircle, 
  Save,
  Calculator,
  FileText,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface StockItem {
  id: number;
  item_id: number;
  variant_id?: number;
  name: string;
  variant_name?: string;
  sku?: string;
  current_stock: number;
  unit: string;
  location_id?: number;
}

interface LastStockTake {
  date: string;
  user: string;
  items_counted: number;
  total_adjustments: number;
  location_id?: number;
}

interface PageProps {
  items: StockItem[];
  last_stock_take?: LastStockTake;
}

export default function StockTake({ items, last_stock_take }: PageProps) {
  const [counts, setCounts] = useState<Record<string, string>>({});
  const [notes, setNotes] = useState<Record<string, string>>({});
  const [variances, setVariances] = useState<Record<string, number>>({});

  const { data, setData, post, processing, errors } = useForm({
    counts: [] as Array<{
      item_id: number;
      variant_id?: number;
      location_id?: number;
      counted_quantity: number;
      notes?: string;
    }>,
  });

  const handleCountChange = (item: StockItem, value: string) => {
    const key = `${item.item_id}_${item.variant_id || 0}`;
    setCounts({ ...counts, [key]: value });

    const countedQty = parseFloat(value) || 0;
    const variance = countedQty - item.current_stock;
    setVariances({ ...variances, [key]: variance });
  };

  const handleNotesChange = (item: StockItem, value: string) => {
    const key = `${item.item_id}_${item.variant_id || 0}`;
    setNotes({ ...notes, [key]: value });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();

    const countsData = items
      .map((item) => {
        const key = `${item.item_id}_${item.variant_id || 0}`;
        const countedQuantity = parseFloat(counts[key] || item.current_stock.toString());
        
        return {
          item_id: item.item_id,
          variant_id: item.variant_id,
          location_id: item.location_id,
          counted_quantity: countedQuantity,
          notes: notes[key],
        };
      })
      .filter((count) => !isNaN(count.counted_quantity));

    setData('counts', countsData);
    post(route('inventory.process-stock-take'));
  };

  const totalVariance = Object.values(variances).reduce((sum, v) => sum + v, 0);
  const itemsWithVariance = Object.values(variances).filter(v => v !== 0).length;

  return (
    <AppLayout>
      <Head title="Stock Take" />

      <PageLayout>
        <PageLayout.Header
          title="Stock Take"
          subtitle="Perform physical inventory count and reconciliation"
          actions={
            <PageLayout.Actions>
              <Button variant="outline" onClick={() => window.history.back()}>
                Cancel
              </Button>
              <Button onClick={handleSubmit} disabled={processing}>
                <Save className="mr-2 h-4 w-4" />
                Complete Stock Take
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
          <div className="space-y-6">
          {last_stock_take && (
            <Alert>
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                Last stock take was performed on {new Date(last_stock_take.date).toLocaleDateString()} by {last_stock_take.user}. 
                {last_stock_take.items_counted} items were counted with {last_stock_take.total_adjustments} adjustments made.
              </AlertDescription>
            </Alert>
          )}

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base font-medium">Items to Count</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{items.length}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base font-medium">Items with Variance</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{itemsWithVariance}</div>
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-base font-medium">Total Variance</CardTitle>
              </CardHeader>
              <CardContent>
                <div className={cn(
                  "text-2xl font-bold",
                  totalVariance > 0 && "text-green-600",
                  totalVariance < 0 && "text-red-600"
                )}>
                  {totalVariance > 0 && '+'}
                  {totalVariance.toFixed(2)}
                </div>
              </CardContent>
            </Card>
          </div>

          <Card>
            <CardHeader>
              <CardTitle>Count Items</CardTitle>
              <CardDescription>
                Enter the physical count for each item. Variances will be calculated automatically.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit}>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Item</TableHead>
                      <TableHead>SKU</TableHead>
                      <TableHead className="text-right">System Count</TableHead>
                      <TableHead className="text-right">Physical Count</TableHead>
                      <TableHead className="text-right">Variance</TableHead>
                      <TableHead>Notes</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {items.map((item) => {
                      const key = `${item.item_id}_${item.variant_id || 0}`;
                      const variance = variances[key] || 0;
                      
                      return (
                        <TableRow key={key}>
                          <TableCell>
                            <div>
                              <div className="font-medium">{item.name}</div>
                              {item.variant_name && (
                                <div className="text-sm text-muted-foreground">
                                  {item.variant_name}
                                </div>
                              )}
                            </div>
                          </TableCell>
                          <TableCell>
                            <span className="text-sm text-muted-foreground">
                              {item.sku || '-'}
                            </span>
                          </TableCell>
                          <TableCell className="text-right">
                            {item.current_stock} {item.unit}
                          </TableCell>
                          <TableCell className="text-right">
                            <Input
                              type="number"
                              step="0.01"
                              value={counts[key] || item.current_stock}
                              onChange={(e) => handleCountChange(item, e.target.value)}
                              className="w-24 text-right"
                            />
                          </TableCell>
                          <TableCell className="text-right">
                            <span className={cn(
                              "font-medium",
                              variance > 0 && "text-green-600",
                              variance < 0 && "text-red-600"
                            )}>
                              {variance > 0 && '+'}
                              {variance !== 0 ? variance.toFixed(2) : '-'}
                            </span>
                          </TableCell>
                          <TableCell>
                            <Input
                              type="text"
                              value={notes[key] || ''}
                              onChange={(e) => handleNotesChange(item, e.target.value)}
                              placeholder="Optional notes..."
                              className="w-full"
                            />
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>

                {items.length === 0 && (
                  <div className="text-center py-8 text-muted-foreground">
                    <Package className="mx-auto h-12 w-12 mb-4 opacity-30" />
                    <p>No items found for stock take</p>
                  </div>
                )}
              </form>
            </CardContent>
          </Card>
          </div>
        </PageLayout.Content>
      </PageLayout>
    </AppLayout>
  );
}