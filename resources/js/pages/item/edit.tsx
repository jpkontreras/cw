import { PageHeader } from '@/components/page';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowLeft, Trash2 } from 'lucide-react';

interface ItemData {
  id: number;
  name: string;
  description: string | null;
  category_id: number | null;
  sku: string | null;
  barcode: string | null;
  base_price: number;
  cost: number;
  is_active: boolean;
  is_compound: boolean;
  stock_quantity: number | null;
  low_stock_threshold: number | null;
}

interface PageProps {
  item: ItemData;
  categories: Array<{ id: number; name: string }>;
  features: {
    variants: boolean;
    modifiers: boolean;
    recipes: boolean;
    location_pricing: boolean;
    inventory_tracking: boolean;
  };
}

export default function ItemEdit({ item, categories, features }: PageProps) {
  const { data, setData, put, processing, errors } = useForm({
    name: item.name,
    description: item.description || '',
    category_id: item.category_id?.toString() || '',
    sku: item.sku || '',
    barcode: item.barcode || '',
    base_price: item.base_price.toString(),
    cost: item.cost.toString(),
    is_active: item.is_active,
    is_compound: item.is_compound,
    stock_quantity: item.stock_quantity?.toString() || '',
    low_stock_threshold: item.low_stock_threshold?.toString() || '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(`/items/${item.id}`);
  };

  const handleDelete = () => {
    if (confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
      router.delete(`/items/${item.id}`);
    }
  };

  return (
    <AppLayout>
      <Head title={`Edit ${item.name}`} />

      <div className="space-y-6">
        <PageHeader
          title={`Edit ${item.name}`}
          description="Update item details"
          action={
            <div className="flex gap-2">
              <Button variant="outline" asChild>
                <Link href="/items">
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Back to Items
                </Link>
              </Button>
              <Button variant="destructive" onClick={handleDelete}>
                <Trash2 className="mr-2 h-4 w-4" />
                Delete Item
              </Button>
            </div>
          }
        />

        <form onSubmit={handleSubmit} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>Update the basic details for this item</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name">Name *</Label>
                  <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="e.g., Cheeseburger" required />
                  {errors.name && <p className="text-sm text-destructive">{errors.name}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="category_id">Category</Label>
                  <Select value={data.category_id} onValueChange={(value) => setData('category_id', value)}>
                    <SelectTrigger id="category_id">
                      <SelectValue placeholder="Select a category" />
                    </SelectTrigger>
                    <SelectContent>
                      {categories.map((category) => (
                        <SelectItem key={category.id} value={category.id.toString()}>
                          {category.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.category_id && <p className="text-sm text-destructive">{errors.category_id}</p>}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  placeholder="Describe your item..."
                  rows={3}
                />
                {errors.description && <p className="text-sm text-destructive">{errors.description}</p>}
              </div>

              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="sku">SKU</Label>
                  <Input id="sku" value={data.sku} onChange={(e) => setData('sku', e.target.value)} placeholder="e.g., BURG-001" />
                  {errors.sku && <p className="text-sm text-destructive">{errors.sku}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="barcode">Barcode</Label>
                  <Input id="barcode" value={data.barcode} onChange={(e) => setData('barcode', e.target.value)} placeholder="e.g., 1234567890123" />
                  {errors.barcode && <p className="text-sm text-destructive">{errors.barcode}</p>}
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Pricing</CardTitle>
              <CardDescription>Update the pricing information for this item</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="base_price">Base Price *</Label>
                  <Input
                    id="base_price"
                    type="number"
                    step="0.01"
                    value={data.base_price}
                    onChange={(e) => setData('base_price', e.target.value)}
                    placeholder="0.00"
                    required
                  />
                  {errors.base_price && <p className="text-sm text-destructive">{errors.base_price}</p>}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="cost">Cost *</Label>
                  <Input
                    id="cost"
                    type="number"
                    step="0.01"
                    value={data.cost}
                    onChange={(e) => setData('cost', e.target.value)}
                    placeholder="0.00"
                    required
                  />
                  {errors.cost && <p className="text-sm text-destructive">{errors.cost}</p>}
                </div>
              </div>
            </CardContent>
          </Card>

          {features.inventory_tracking && (
            <Card>
              <CardHeader>
                <CardTitle>Inventory</CardTitle>
                <CardDescription>Manage stock levels for this item</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="stock_quantity">Stock Quantity</Label>
                    <Input
                      id="stock_quantity"
                      type="number"
                      value={data.stock_quantity}
                      onChange={(e) => setData('stock_quantity', e.target.value)}
                      placeholder="0"
                    />
                    {errors.stock_quantity && <p className="text-sm text-destructive">{errors.stock_quantity}</p>}
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="low_stock_threshold">Low Stock Threshold</Label>
                    <Input
                      id="low_stock_threshold"
                      type="number"
                      value={data.low_stock_threshold}
                      onChange={(e) => setData('low_stock_threshold', e.target.value)}
                      placeholder="10"
                    />
                    {errors.low_stock_threshold && <p className="text-sm text-destructive">{errors.low_stock_threshold}</p>}
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          <Card>
            <CardHeader>
              <CardTitle>Settings</CardTitle>
              <CardDescription>Configure additional options for this item</CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is_active">Active</Label>
                  <p className="text-sm text-muted-foreground">Make this item available for ordering</p>
                </div>
                <Switch id="is_active" checked={data.is_active} onCheckedChange={(checked) => setData('is_active', checked)} />
              </div>

              {features.recipes && (
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label htmlFor="is_compound">Compound Item</Label>
                    <p className="text-sm text-muted-foreground">This item is made from other items (recipe)</p>
                  </div>
                  <Switch id="is_compound" checked={data.is_compound} onCheckedChange={(checked) => setData('is_compound', checked)} />
                </div>
              )}
            </CardContent>
          </Card>

          <div className="flex justify-between">
            <div className="flex gap-2">
              {features.variants && (
                <Button variant="outline" asChild>
                  <Link href={`/items/${item.id}/variants`}>Manage Variants</Link>
                </Button>
              )}
              {features.modifiers && (
                <Button variant="outline" asChild>
                  <Link href={`/items/${item.id}/modifiers`}>Manage Modifiers</Link>
                </Button>
              )}
            </div>
            <div className="flex gap-2">
              <Button variant="outline" asChild>
                <Link href="/items">Cancel</Link>
              </Button>
              <Button type="submit" disabled={processing}>
                Save Changes
              </Button>
            </div>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}
