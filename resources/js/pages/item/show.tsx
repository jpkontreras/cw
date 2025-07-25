import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ArrowLeft, Edit, Package, DollarSign, Tag, Layers } from 'lucide-react';
import { formatCurrency } from '@/lib/utils';

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
  created_at: string;
  updated_at: string;
}

interface PageProps {
  item: ItemData & {
    category?: { id: number; name: string };
    variants?: Array<{
      id: number;
      name: string;
      sku: string | null;
      price_adjustment: number;
      is_active: boolean;
    }>;
    modifierGroups?: Array<{
      id: number;
      name: string;
      min_selections: number;
      max_selections: number;
      modifiers: Array<{
        id: number;
        name: string;
        price_adjustment: number;
        is_default: boolean;
      }>;
    }>;
    pricing?: Array<{
      id: number;
      location_id: number;
      location_name: string;
      price: number;
      is_active: boolean;
    }>;
  };
  features: {
    variants: boolean;
    modifiers: boolean;
    recipes: boolean;
    location_pricing: boolean;
    inventory_tracking: boolean;
  };
}

export default function ItemShow({ item, features }: PageProps) {
  const profit = item.base_price - item.cost;
  const profitMargin = item.base_price > 0 ? (profit / item.base_price) * 100 : 0;

  return (
    <AppLayout>
      <Head title={item.name} />

      <div className="space-y-6">
        <PageHeader
          title={item.name}
          description={item.description || 'Product details'}
          action={
            <div className="flex gap-2">
              <Button variant="outline" asChild>
                <Link href="/items">
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Back to Items
                </Link>
              </Button>
              <Button asChild>
                <Link href={`/items/${item.id}/edit`}>
                  <Edit className="mr-2 h-4 w-4" />
                  Edit
                </Link>
              </Button>
            </div>
          }
        />

        <div className="grid gap-6 md:grid-cols-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Base Price</CardTitle>
              <DollarSign className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{formatCurrency(item.base_price)}</div>
              <p className="text-xs text-muted-foreground">
                Cost: {formatCurrency(item.cost)}
              </p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Profit Margin</CardTitle>
              <Tag className="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{profitMargin.toFixed(1)}%</div>
              <p className="text-xs text-muted-foreground">
                {formatCurrency(profit)} per item
              </p>
            </CardContent>
          </Card>

          {features.inventory_tracking && (
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Stock Level</CardTitle>
                <Package className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {item.stock_quantity !== null ? item.stock_quantity : 'N/A'}
                </div>
                {item.low_stock_threshold && item.stock_quantity !== null && (
                  <p className="text-xs text-muted-foreground">
                    Low stock at: {item.low_stock_threshold}
                  </p>
                )}
              </CardContent>
            </Card>
          )}
        </div>

        <Tabs defaultValue="details" className="space-y-4">
          <TabsList>
            <TabsTrigger value="details">Details</TabsTrigger>
            {features.variants && item.variants && item.variants.length > 0 && (
              <TabsTrigger value="variants">Variants</TabsTrigger>
            )}
            {features.modifiers && item.modifierGroups && item.modifierGroups.length > 0 && (
              <TabsTrigger value="modifiers">Modifiers</TabsTrigger>
            )}
            {features.location_pricing && item.pricing && item.pricing.length > 0 && (
              <TabsTrigger value="pricing">Location Pricing</TabsTrigger>
            )}
          </TabsList>

          <TabsContent value="details" className="space-y-4">
            <Card>
              <CardHeader>
                <CardTitle>Item Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Status</p>
                    <Badge variant={item.is_active ? 'default' : 'secondary'}>
                      {item.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Category</p>
                    <p className="text-sm">{item.category?.name || 'Uncategorized'}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">SKU</p>
                    <p className="text-sm">{item.sku || '-'}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Barcode</p>
                    <p className="text-sm">{item.barcode || '-'}</p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Type</p>
                    <p className="text-sm">
                      {item.is_compound ? (
                        <Badge variant="outline">
                          <Layers className="mr-1 h-3 w-3" />
                          Compound Item
                        </Badge>
                      ) : (
                        'Simple Item'
                      )}
                    </p>
                  </div>
                  <div>
                    <p className="text-sm font-medium text-muted-foreground">Created</p>
                    <p className="text-sm">
                      {new Date(item.created_at).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          </TabsContent>

          {features.variants && item.variants && item.variants.length > 0 && (
            <TabsContent value="variants" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Product Variants</CardTitle>
                  <CardDescription>
                    Different variations of this item
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {item.variants.map((variant) => (
                      <div key={variant.id} className="flex items-center justify-between border-b pb-4 last:border-0">
                        <div>
                          <p className="font-medium">{variant.name}</p>
                          {variant.sku && (
                            <p className="text-sm text-muted-foreground">SKU: {variant.sku}</p>
                          )}
                        </div>
                        <div className="flex items-center gap-4">
                          <p className="text-sm">
                            {variant.price_adjustment > 0 ? '+' : ''}
                            {formatCurrency(variant.price_adjustment)}
                          </p>
                          <Badge variant={variant.is_active ? 'default' : 'secondary'}>
                            {variant.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          )}

          {features.modifiers && item.modifierGroups && item.modifierGroups.length > 0 && (
            <TabsContent value="modifiers" className="space-y-4">
              {item.modifierGroups.map((group) => (
                <Card key={group.id}>
                  <CardHeader>
                    <CardTitle>{group.name}</CardTitle>
                    <CardDescription>
                      {group.min_selections === group.max_selections
                        ? `Select exactly ${group.min_selections}`
                        : group.min_selections > 0
                        ? `Select ${group.min_selections} to ${group.max_selections}`
                        : `Select up to ${group.max_selections}`}
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <div className="space-y-2">
                      {group.modifiers.map((modifier) => (
                        <div key={modifier.id} className="flex items-center justify-between">
                          <div className="flex items-center gap-2">
                            <span>{modifier.name}</span>
                            {modifier.is_default && (
                              <Badge variant="secondary" className="text-xs">Default</Badge>
                            )}
                          </div>
                          {modifier.price_adjustment !== 0 && (
                            <span className="text-sm">
                              {modifier.price_adjustment > 0 ? '+' : ''}
                              {formatCurrency(modifier.price_adjustment)}
                            </span>
                          )}
                        </div>
                      ))}
                    </div>
                  </CardContent>
                </Card>
              ))}
            </TabsContent>
          )}

          {features.location_pricing && item.pricing && item.pricing.length > 0 && (
            <TabsContent value="pricing" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Location-Specific Pricing</CardTitle>
                  <CardDescription>
                    Custom pricing for different locations
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {item.pricing.map((price) => (
                      <div key={price.id} className="flex items-center justify-between border-b pb-4 last:border-0">
                        <div>
                          <p className="font-medium">{price.location_name}</p>
                        </div>
                        <div className="flex items-center gap-4">
                          <p className="font-medium">{formatCurrency(price.price)}</p>
                          <Badge variant={price.is_active ? 'default' : 'secondary'}>
                            {price.is_active ? 'Active' : 'Inactive'}
                          </Badge>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          )}
        </Tabs>
      </div>
    </AppLayout>
  );
}