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
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
  Package, 
  Plus,
  Minus,
  AlertCircle,
  TrendingUp,
  TrendingDown,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface AdjustmentType {
  value: string;
  label: string;
}

interface RecentAdjustment {
  id: number;
  item_name: string;
  quantity_change: number;
  adjustment_type: string;
  reason: string;
  adjusted_by: string;
  adjusted_at: string;
}

interface PageProps {
  adjustment_types: AdjustmentType[];
  recent_adjustments: RecentAdjustment[];
}

export default function InventoryAdjustments({ adjustment_types, recent_adjustments }: PageProps) {
  const [isIncreasing, setIsIncreasing] = useState(true);

  const { data, setData, post, processing, errors, reset } = useForm({
    item_id: '',
    variant_id: '',
    location_id: '',
    quantity_change: '',
    adjustment_type: '',
    reason: '',
    notes: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    const quantity = parseFloat(data.quantity_change);
    const finalQuantity = isIncreasing ? Math.abs(quantity) : -Math.abs(quantity);
    
    post(route('inventory.adjust'), {
      data: {
        ...data,
        quantity_change: finalQuantity,
      },
      onSuccess: () => reset(),
    });
  };

  return (
    <AppLayout>
      <Head title="Inventory Adjustments" />

      <PageLayout>
        <PageLayout.Header
          title="Inventory Adjustments"
          subtitle="Manually adjust inventory levels for items"
        />
        
        <PageLayout.Content>
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <Card>
            <CardHeader>
              <CardTitle>New Adjustment</CardTitle>
              <CardDescription>
                Create a manual inventory adjustment
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="flex gap-2">
                  <Button
                    type="button"
                    variant={isIncreasing ? 'default' : 'outline'}
                    onClick={() => setIsIncreasing(true)}
                    className="flex-1"
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Increase Stock
                  </Button>
                  <Button
                    type="button"
                    variant={!isIncreasing ? 'default' : 'outline'}
                    onClick={() => setIsIncreasing(false)}
                    className="flex-1"
                  >
                    <Minus className="mr-2 h-4 w-4" />
                    Decrease Stock
                  </Button>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="item_id">Item *</Label>
                  <Input
                    id="item_id"
                    type="number"
                    value={data.item_id}
                    onChange={(e) => setData('item_id', e.target.value)}
                    placeholder="Enter item ID"
                    required
                  />
                  {errors.item_id && (
                    <p className="text-sm text-destructive">{errors.item_id}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="variant_id">Variant ID (Optional)</Label>
                  <Input
                    id="variant_id"
                    type="number"
                    value={data.variant_id}
                    onChange={(e) => setData('variant_id', e.target.value)}
                    placeholder="Enter variant ID if applicable"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="quantity_change">Quantity *</Label>
                  <Input
                    id="quantity_change"
                    type="number"
                    step="0.01"
                    value={data.quantity_change}
                    onChange={(e) => setData('quantity_change', e.target.value)}
                    placeholder="Enter quantity to adjust"
                    required
                  />
                  {errors.quantity_change && (
                    <p className="text-sm text-destructive">{errors.quantity_change}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="adjustment_type">Adjustment Type *</Label>
                  <Select
                    value={data.adjustment_type}
                    onValueChange={(value) => setData('adjustment_type', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select adjustment type" />
                    </SelectTrigger>
                    <SelectContent>
                      {adjustment_types.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.adjustment_type && (
                    <p className="text-sm text-destructive">{errors.adjustment_type}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="reason">Reason *</Label>
                  <Input
                    id="reason"
                    value={data.reason}
                    onChange={(e) => setData('reason', e.target.value)}
                    placeholder="Brief reason for adjustment"
                    required
                  />
                  {errors.reason && (
                    <p className="text-sm text-destructive">{errors.reason}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="notes">Additional Notes</Label>
                  <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    placeholder="Any additional details..."
                    rows={3}
                  />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                  {processing ? 'Processing...' : 'Submit Adjustment'}
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Recent Adjustments</CardTitle>
              <CardDescription>
                Latest inventory adjustments made
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {recent_adjustments.length > 0 ? (
                  recent_adjustments.map((adjustment) => (
                    <div key={adjustment.id} className="flex items-start justify-between p-4 border rounded-lg">
                      <div className="flex-1">
                        <div className="flex items-center gap-2 mb-1">
                          <span className="font-medium">{adjustment.item_name}</span>
                          <Badge variant="outline" className="text-xs">
                            {adjustment.adjustment_type}
                          </Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                          {adjustment.reason}
                        </p>
                        <p className="text-xs text-muted-foreground mt-1">
                          by {adjustment.adjusted_by} • {new Date(adjustment.adjusted_at).toLocaleString()}
                        </p>
                      </div>
                      <div className="text-right">
                        <div className={cn(
                          "font-semibold",
                          adjustment.quantity_change > 0 ? "text-green-600" : "text-red-600"
                        )}>
                          {adjustment.quantity_change > 0 ? '+' : ''}
                          {adjustment.quantity_change}
                        </div>
                      </div>
                    </div>
                  ))
                ) : (
                  <div className="text-center py-8 text-muted-foreground">
                    <Package className="mx-auto h-12 w-12 mb-4 opacity-30" />
                    <p>No recent adjustments</p>
                  </div>
                )}
              </div>
            </CardContent>
          </Card>
          </div>
        </PageLayout.Content>
      </PageLayout>
    </AppLayout>
  );
}