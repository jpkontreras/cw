import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { 
  Package, 
  Settings,
  AlertCircle,
  TrendingDown,
  Edit,
  Save,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface ItemToReorder {
  item_id: number;
  item_name: string;
  current_stock: number;
  reorder_point: number;
  avg_daily_usage: number;
  suggested_quantity: number;
  estimated_cost: number;
}

interface ReorderRule {
  item_id: number;
  item_name: string;
  reorder_point: number;
  reorder_quantity: number;
  current_stock: number;
}

interface PageProps {
  items_to_reorder: ItemToReorder[];
  reorder_rules: ReorderRule[];
}

export default function ReorderSettings({ items_to_reorder, reorder_rules }: PageProps) {
  const [editingItem, setEditingItem] = useState<ReorderRule | null>(null);
  const [showEditDialog, setShowEditDialog] = useState(false);

  const { data, setData, post, processing, errors } = useForm({
    item_id: '',
    variant_id: '',
    location_id: '',
    min_quantity: '',
    reorder_quantity: '',
    max_quantity: '',
  });

  const handleEdit = (rule: ReorderRule) => {
    setEditingItem(rule);
    setData({
      item_id: rule.item_id.toString(),
      variant_id: '',
      location_id: '',
      min_quantity: rule.reorder_point.toString(),
      reorder_quantity: rule.reorder_quantity.toString(),
      max_quantity: '',
    });
    setShowEditDialog(true);
  };

  const handleSave = () => {
    post(route('inventory.update-reorder-levels'), {
      onSuccess: () => {
        setShowEditDialog(false);
        setEditingItem(null);
      },
    });
  };

  const totalReorderValue = items_to_reorder.reduce((sum, item) => sum + item.estimated_cost, 0);

  return (
    <AppLayout>
      <Head title="Reorder Settings" />

      <Page
        title="Reorder Settings"
        description="Configure automatic reorder points and quantities"
        actions={
          <Button variant="outline">
            <Settings className="mr-2 h-4 w-4" />
            Configure Rules
          </Button>
        }
      >
        <div className="space-y-6">
          {items_to_reorder.length > 0 && (
            <Alert>
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                <strong>{items_to_reorder.length} items</strong> are below reorder point 
                with an estimated reorder value of <strong>${totalReorderValue.toFixed(2)}</strong>
              </AlertDescription>
            </Alert>
          )}

          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <Card>
              <CardHeader>
                <CardTitle>Items to Reorder</CardTitle>
                <CardDescription>
                  Items that have reached their reorder point
                </CardDescription>
              </CardHeader>
              <CardContent>
                {items_to_reorder.length > 0 ? (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead className="text-right">Current</TableHead>
                        <TableHead className="text-right">Reorder At</TableHead>
                        <TableHead className="text-right">Suggested Qty</TableHead>
                        <TableHead className="text-right">Cost</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {items_to_reorder.map((item) => (
                        <TableRow key={item.item_id}>
                          <TableCell>
                            <div>
                              <div className="font-medium">{item.item_name}</div>
                              <div className="text-xs text-muted-foreground">
                                ~{item.avg_daily_usage.toFixed(1)} units/day
                              </div>
                            </div>
                          </TableCell>
                          <TableCell className="text-right">
                            <span className={cn(
                              "font-medium",
                              item.current_stock <= 0 && "text-red-600"
                            )}>
                              {item.current_stock}
                            </span>
                          </TableCell>
                          <TableCell className="text-right">
                            {item.reorder_point}
                          </TableCell>
                          <TableCell className="text-right">
                            <Badge variant="secondary">
                              {item.suggested_quantity}
                            </Badge>
                          </TableCell>
                          <TableCell className="text-right">
                            ${item.estimated_cost.toFixed(2)}
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : (
                  <div className="text-center py-8 text-muted-foreground">
                    <Package className="mx-auto h-12 w-12 mb-4 opacity-30" />
                    <p>No items need reordering</p>
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader>
                <CardTitle>Reorder Rules</CardTitle>
                <CardDescription>
                  Current reorder point configurations
                </CardDescription>
              </CardHeader>
              <CardContent>
                {reorder_rules.length > 0 ? (
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead className="text-right">Reorder Point</TableHead>
                        <TableHead className="text-right">Reorder Qty</TableHead>
                        <TableHead className="text-right">Current Stock</TableHead>
                        <TableHead></TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {reorder_rules.map((rule) => (
                        <TableRow key={rule.item_id}>
                          <TableCell className="font-medium">
                            {rule.item_name}
                          </TableCell>
                          <TableCell className="text-right">
                            {rule.reorder_point}
                          </TableCell>
                          <TableCell className="text-right">
                            {rule.reorder_quantity}
                          </TableCell>
                          <TableCell className="text-right">
                            <span className={cn(
                              rule.current_stock <= rule.reorder_point && "text-yellow-600 font-medium"
                            )}>
                              {rule.current_stock}
                            </span>
                          </TableCell>
                          <TableCell>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleEdit(rule)}
                            >
                              <Edit className="h-4 w-4" />
                            </Button>
                          </TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                ) : (
                  <div className="text-center py-8 text-muted-foreground">
                    <Settings className="mx-auto h-12 w-12 mb-4 opacity-30" />
                    <p>No reorder rules configured</p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>

        <Dialog open={showEditDialog} onOpenChange={setShowEditDialog}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit Reorder Settings</DialogTitle>
              <DialogDescription>
                Update reorder levels for {editingItem?.item_name}
              </DialogDescription>
            </DialogHeader>

            <div className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="min_quantity">Minimum Quantity (Reorder Point)</Label>
                <Input
                  id="min_quantity"
                  type="number"
                  step="0.01"
                  min="0"
                  value={data.min_quantity}
                  onChange={(e) => setData('min_quantity', e.target.value)}
                />
                {errors.min_quantity && (
                  <p className="text-sm text-destructive">{errors.min_quantity}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="reorder_quantity">Reorder Quantity</Label>
                <Input
                  id="reorder_quantity"
                  type="number"
                  step="0.01"
                  min="0.01"
                  value={data.reorder_quantity}
                  onChange={(e) => setData('reorder_quantity', e.target.value)}
                />
                {errors.reorder_quantity && (
                  <p className="text-sm text-destructive">{errors.reorder_quantity}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="max_quantity">Maximum Quantity (Optional)</Label>
                <Input
                  id="max_quantity"
                  type="number"
                  step="0.01"
                  min="0"
                  value={data.max_quantity}
                  onChange={(e) => setData('max_quantity', e.target.value)}
                />
                {errors.max_quantity && (
                  <p className="text-sm text-destructive">{errors.max_quantity}</p>
                )}
              </div>
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setShowEditDialog(false)}>
                Cancel
              </Button>
              <Button onClick={handleSave} disabled={processing}>
                <Save className="mr-2 h-4 w-4" />
                Save Changes
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </Page>
    </AppLayout>
  );
}