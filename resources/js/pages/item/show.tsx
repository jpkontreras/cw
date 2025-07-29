import { Head, router } from '@inertiajs/react';
import PageLayout from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Separator } from '@/components/ui/separator';
import { Progress } from '@/components/ui/progress';
import { 
  ArrowLeft, 
  Edit, 
  Package, 
  DollarSign,
  BarChart3,
  AlertCircle,
  Clock,
  Box,
  FileText,
  Beaker,
  TrendingUp,
  Image as ImageIcon,
  Hash,
  Calendar,
  Info,
  Plus,
  ShoppingCart,
  Settings
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';

interface Variant {
  id: number;
  name: string;
  sku: string | null;
  price: number;
  cost: number | null;
  track_stock: boolean;
  is_available: boolean;
  current_stock?: number;
}

interface ModifierGroup {
  id: number;
  name: string;
  description: string | null;
  min_selections: number;
  max_selections: number;
  is_required: boolean;
  modifiers: Array<{
    id: number;
    name: string;
    price_adjustment: number;
    is_available: boolean;
  }>;
}

interface PriceCalculation {
  base_price: number;
  final_price: number;
  modifier_total: number;
  applied_rules: Array<{
    type: string;
    name: string;
    adjustment: number;
  }>;
}

interface Inventory {
  quantity_on_hand: number;
  quantity_reserved: number;
  min_quantity: number;
  reorder_quantity: number;
  last_counted_at: string;
  last_restocked_at: string;
}

interface Recipe {
  id: number;
  name: string;
  yield_quantity: number;
  yield_unit: string;
  total_cost: number;
  portion_cost: number;
  ingredients_count: number;
}

interface Item {
  id: number;
  name: string;
  description: string | null;
  type: 'product' | 'service' | 'combo';
  category_name: string | null;
  base_price: number;
  cost: number | null;
  sku: string | null;
  barcode: string | null;
  track_stock: boolean;
  is_available: boolean;
  allow_modifiers: boolean;
  preparation_time: number | null;
  created_at: string;
  updated_at: string;
  variants: Variant[];
  images: Array<{
    id: number;
    url: string;
    is_primary: boolean;
  }>;
}

interface PageProps {
  item: Item;
  modifier_groups: ModifierGroup[];
  current_price: PriceCalculation | null;
  inventory: Inventory | null;
  recipe: Recipe | null;
  features: {
    variants: boolean;
    modifiers: boolean;
    dynamic_pricing: boolean;
    inventory: boolean;
    recipes: boolean;
  };
  stats?: {
    total_sold: number;
    revenue: number;
    avg_order_value: number;
    popularity_rank: number;
  };
}

export default function ItemShow({ 
  item, 
  modifier_groups, 
  current_price,
  inventory,
  recipe,
  features,
  stats
}: PageProps) {
  const primaryImage = item.images.find(img => img.is_primary) || item.images[0];
  const margin = item.cost ? ((item.base_price - item.cost) / item.base_price * 100).toFixed(1) : null;
  
  const typeStyles = {
    product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  };

  const stockStatus = inventory ? {
    level: (inventory.quantity_on_hand / (inventory.reorder_quantity * 2)) * 100,
    status: inventory.quantity_on_hand <= inventory.min_quantity ? 'critical' :
            inventory.quantity_on_hand <= inventory.reorder_quantity ? 'low' : 'good',
    color: inventory.quantity_on_hand <= inventory.min_quantity ? 'bg-red-500' :
           inventory.quantity_on_hand <= inventory.reorder_quantity ? 'bg-amber-500' : 'bg-green-500'
  } : null;

  return (
    <>
      <Head title={item.name} />
      
      <PageLayout>
        <PageLayout.Header
          title={
            <div className="flex items-center gap-3">
              <span>{item.name}</span>
              <Badge variant={item.is_available ? 'success' : 'secondary'}>
                {item.is_available ? 'Available' : 'Unavailable'}
              </Badge>
              <Badge variant="secondary" className={cn(typeStyles[item.type])}>
                {item.type}
              </Badge>
            </div>
          }
          subtitle={item.description || 'No description available'}
          actions={
            <PageLayout.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/items')}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back
              </Button>
              <Button
                size="sm"
                onClick={() => router.visit(`/items/${item.id}/edit`)}
              >
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
          <div className="grid gap-6 lg:grid-cols-3">
            {/* Main Content */}
            <div className="lg:col-span-2 space-y-6">
              <Tabs defaultValue="overview" className="w-full">
                <TabsList className="grid w-full grid-cols-4">
                  <TabsTrigger value="overview">Overview</TabsTrigger>
                  <TabsTrigger value="variants" disabled={!features.variants || item.variants.length === 0}>
                    Variants ({item.variants.length})
                  </TabsTrigger>
                  <TabsTrigger value="modifiers" disabled={!features.modifiers || modifier_groups.length === 0}>
                    Modifiers ({modifier_groups.length})
                  </TabsTrigger>
                  <TabsTrigger value="analytics">Analytics</TabsTrigger>
                </TabsList>

                <TabsContent value="overview" className="space-y-6">
                  {/* Basic Info */}
                  <Card>
                    <CardHeader>
                      <CardTitle>Item Details</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">SKU</p>
                          <p className="font-medium">{item.sku || '—'}</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">Barcode</p>
                          <p className="font-medium">{item.barcode || '—'}</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">Category</p>
                          <p className="font-medium">{item.category_name || 'Uncategorized'}</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">Preparation Time</p>
                          <p className="font-medium">
                            {item.preparation_time ? `${item.preparation_time} minutes` : '—'}
                          </p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  {/* Pricing Card */}
                  <Card>
                    <CardHeader>
                      <CardTitle className="flex items-center gap-2">
                        <DollarSign className="h-5 w-5" />
                        Pricing
                      </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                      <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">Base Price</p>
                          <p className="text-2xl font-bold">{formatCurrency(item.base_price)}</p>
                        </div>
                        <div className="space-y-1">
                          <p className="text-sm text-muted-foreground">Cost</p>
                          <p className="text-2xl font-bold">
                            {item.cost ? formatCurrency(item.cost) : '—'}
                          </p>
                        </div>
                      </div>
                      
                      {margin && (
                        <div className="p-4 bg-muted/50 rounded-lg">
                          <div className="flex justify-between items-center">
                            <span className="text-sm text-muted-foreground">Profit Margin</span>
                            <span className="font-medium">{margin}%</span>
                          </div>
                          <Progress value={parseFloat(margin)} className="mt-2" />
                        </div>
                      )}

                      {current_price && features.dynamic_pricing && (
                        <>
                          <Separator />
                          <div className="space-y-3">
                            <h4 className="text-sm font-medium">Current Pricing</h4>
                            <div className="space-y-2">
                              <div className="flex justify-between">
                                <span className="text-sm text-muted-foreground">Base Price</span>
                                <span>{formatCurrency(current_price.base_price)}</span>
                              </div>
                              {current_price.applied_rules.map((rule, index) => (
                                <div key={index} className="flex justify-between">
                                  <span className="text-sm text-muted-foreground">{rule.name}</span>
                                  <span className={cn(
                                    rule.adjustment > 0 ? 'text-red-600' : 'text-green-600'
                                  )}>
                                    {rule.adjustment > 0 ? '+' : ''}{formatCurrency(rule.adjustment)}
                                  </span>
                                </div>
                              ))}
                              <Separator />
                              <div className="flex justify-between font-medium">
                                <span>Final Price</span>
                                <span className="text-lg">{formatCurrency(current_price.final_price)}</span>
                              </div>
                            </div>
                          </div>
                        </>
                      )}
                    </CardContent>
                  </Card>

                  {/* Inventory Card */}
                  {features.inventory && item.track_stock && inventory && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                          <Box className="h-5 w-5" />
                          Inventory
                        </CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="space-y-2">
                          <div className="flex justify-between items-center">
                            <span className="text-sm text-muted-foreground">Stock Level</span>
                            <Badge variant={stockStatus?.status === 'critical' ? 'destructive' : 
                                          stockStatus?.status === 'low' ? 'warning' : 'success'}>
                              {stockStatus?.status === 'critical' ? 'Critical' :
                               stockStatus?.status === 'low' ? 'Low Stock' : 'Good'}
                            </Badge>
                          </div>
                          <Progress value={stockStatus?.level} className="h-3" />
                        </div>
                        
                        <div className="grid grid-cols-2 gap-4">
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">On Hand</p>
                            <p className="text-xl font-semibold">{inventory.quantity_on_hand}</p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Reserved</p>
                            <p className="text-xl font-semibold">{inventory.quantity_reserved}</p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Min Level</p>
                            <p className="font-medium">{inventory.min_quantity}</p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Reorder Point</p>
                            <p className="font-medium">{inventory.reorder_quantity}</p>
                          </div>
                        </div>
                        
                        <Separator />
                        
                        <div className="space-y-2 text-sm">
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Last Counted</span>
                            <span>{formatDate(inventory.last_counted_at)}</span>
                          </div>
                          <div className="flex justify-between">
                            <span className="text-muted-foreground">Last Restocked</span>
                            <span>{formatDate(inventory.last_restocked_at)}</span>
                          </div>
                        </div>
                        
                        <Button 
                          variant="outline" 
                          className="w-full"
                          onClick={() => router.visit(`/inventory?item_id=${item.id}`)}
                        >
                          Manage Inventory
                        </Button>
                      </CardContent>
                    </Card>
                  )}

                  {/* Recipe Card */}
                  {features.recipes && recipe && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                          <Beaker className="h-5 w-5" />
                          Recipe
                        </CardTitle>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Yield</p>
                            <p className="font-medium">
                              {recipe.yield_quantity} {recipe.yield_unit}
                            </p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Ingredients</p>
                            <p className="font-medium">{recipe.ingredients_count} items</p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Total Cost</p>
                            <p className="font-medium">{formatCurrency(recipe.total_cost)}</p>
                          </div>
                          <div className="space-y-1">
                            <p className="text-sm text-muted-foreground">Cost per Portion</p>
                            <p className="font-medium">{formatCurrency(recipe.portion_cost)}</p>
                          </div>
                        </div>
                        
                        <Button 
                          variant="outline" 
                          className="w-full"
                          onClick={() => router.visit(`/recipes/${recipe.id}`)}
                        >
                          View Recipe Details
                        </Button>
                      </CardContent>
                    </Card>
                  )}
                </TabsContent>

                <TabsContent value="variants" className="space-y-4">
                  <Card>
                    <CardHeader>
                      <CardTitle>Variants</CardTitle>
                      <CardDescription>
                        Different versions of this item
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <Table>
                        <TableHeader>
                          <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>SKU</TableHead>
                            <TableHead>Price</TableHead>
                            <TableHead>Cost</TableHead>
                            {features.inventory && <TableHead>Stock</TableHead>}
                            <TableHead>Status</TableHead>
                          </TableRow>
                        </TableHeader>
                        <TableBody>
                          {item.variants.map((variant) => (
                            <TableRow key={variant.id}>
                              <TableCell className="font-medium">{variant.name}</TableCell>
                              <TableCell>{variant.sku || '—'}</TableCell>
                              <TableCell>{formatCurrency(variant.price)}</TableCell>
                              <TableCell>{variant.cost ? formatCurrency(variant.cost) : '—'}</TableCell>
                              {features.inventory && (
                                <TableCell>
                                  {variant.track_stock ? variant.current_stock || 0 : '—'}
                                </TableCell>
                              )}
                              <TableCell>
                                <Badge variant={variant.is_available ? 'success' : 'secondary'}>
                                  {variant.is_available ? 'Available' : 'Unavailable'}
                                </Badge>
                              </TableCell>
                            </TableRow>
                          ))}
                        </TableBody>
                      </Table>
                    </CardContent>
                  </Card>
                </TabsContent>

                <TabsContent value="modifiers" className="space-y-4">
                  {modifier_groups.map((group) => (
                    <Card key={group.id}>
                      <CardHeader>
                        <CardTitle className="text-lg">{group.name}</CardTitle>
                        {group.description && (
                          <CardDescription>{group.description}</CardDescription>
                        )}
                        <div className="flex gap-2 mt-2">
                          {group.is_required && (
                            <Badge variant="secondary">Required</Badge>
                          )}
                          <Badge variant="outline">
                            {group.min_selections}-{group.max_selections || '∞'} selections
                          </Badge>
                        </div>
                      </CardHeader>
                      <CardContent>
                        <Table>
                          <TableHeader>
                            <TableRow>
                              <TableHead>Modifier</TableHead>
                              <TableHead>Price Adjustment</TableHead>
                              <TableHead>Status</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {group.modifiers.map((modifier) => (
                              <TableRow key={modifier.id}>
                                <TableCell>{modifier.name}</TableCell>
                                <TableCell>
                                  {modifier.price_adjustment > 0 && '+'}
                                  {formatCurrency(modifier.price_adjustment)}
                                </TableCell>
                                <TableCell>
                                  <Badge variant={modifier.is_available ? 'success' : 'secondary'}>
                                    {modifier.is_available ? 'Available' : 'Unavailable'}
                                  </Badge>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </CardContent>
                    </Card>
                  ))}
                </TabsContent>

                <TabsContent value="analytics" className="space-y-4">
                  {stats ? (
                    <>
                      <div className="grid gap-4 md:grid-cols-2">
                        <Card>
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Sold</CardTitle>
                            <ShoppingCart className="h-4 w-4 text-muted-foreground" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{stats.total_sold}</div>
                            <p className="text-xs text-muted-foreground">units sold to date</p>
                          </CardContent>
                        </Card>
                        
                        <Card>
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Revenue</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.revenue)}</div>
                            <p className="text-xs text-muted-foreground">total revenue generated</p>
                          </CardContent>
                        </Card>
                        
                        <Card>
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Avg Order Value</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.avg_order_value)}</div>
                            <p className="text-xs text-muted-foreground">per transaction</p>
                          </CardContent>
                        </Card>
                        
                        <Card>
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Popularity Rank</CardTitle>
                            <BarChart3 className="h-4 w-4 text-muted-foreground" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">#{stats.popularity_rank}</div>
                            <p className="text-xs text-muted-foreground">in your menu</p>
                          </CardContent>
                        </Card>
                      </div>
                    </>
                  ) : (
                    <Card>
                      <CardContent className="flex flex-col items-center justify-center py-12">
                        <BarChart3 className="h-12 w-12 text-muted-foreground mb-4" />
                        <p className="text-muted-foreground">No analytics data available yet</p>
                      </CardContent>
                    </Card>
                  )}
                </TabsContent>
              </Tabs>
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Image */}
              {primaryImage ? (
                <Card>
                  <CardContent className="p-0">
                    <img
                      src={primaryImage.url}
                      alt={item.name}
                      className="w-full h-64 object-cover rounded-lg"
                    />
                  </CardContent>
                </Card>
              ) : (
                <Card>
                  <CardContent className="flex items-center justify-center h-64">
                    <div className="text-center">
                      <ImageIcon className="h-12 w-12 mx-auto text-muted-foreground mb-2" />
                      <p className="text-sm text-muted-foreground">No image available</p>
                    </div>
                  </CardContent>
                </Card>
              )}

              {/* Quick Actions */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Quick Actions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {features.inventory && item.track_stock && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/inventory/adjustments?item_id=${item.id}`)}
                    >
                      <Box className="mr-2 h-4 w-4" />
                      Adjust Stock
                    </Button>
                  )}
                  
                  {features.dynamic_pricing && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/pricing/create?item_id=${item.id}`)}
                    >
                      <DollarSign className="mr-2 h-4 w-4" />
                      Add Price Rule
                    </Button>
                  )}
                  
                  {features.modifiers && item.allow_modifiers && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/modifiers/create?item_id=${item.id}`)}
                    >
                      <Settings className="mr-2 h-4 w-4" />
                      Manage Modifiers
                    </Button>
                  )}
                  
                  {features.recipes && !recipe && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/recipes/create?item_id=${item.id}`)}
                    >
                      <Beaker className="mr-2 h-4 w-4" />
                      Create Recipe
                    </Button>
                  )}
                </CardContent>
              </Card>

              {/* Meta Information */}
              <Card>
                <CardHeader>
                  <CardTitle className="text-base">Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="flex items-center gap-2 text-sm">
                    <Hash className="h-4 w-4 text-muted-foreground" />
                    <span className="text-muted-foreground">ID:</span>
                    <span className="font-mono">{item.id}</span>
                  </div>
                  
                  <div className="flex items-center gap-2 text-sm">
                    <Calendar className="h-4 w-4 text-muted-foreground" />
                    <span className="text-muted-foreground">Created:</span>
                    <span>{formatDate(item.created_at)}</span>
                  </div>
                  
                  <div className="flex items-center gap-2 text-sm">
                    <Clock className="h-4 w-4 text-muted-foreground" />
                    <span className="text-muted-foreground">Updated:</span>
                    <span>{formatDate(item.updated_at)}</span>
                  </div>
                  
                  <Separator />
                  
                  <div className="space-y-2">
                    <div className="flex items-center gap-2 text-sm">
                      <Box className="h-4 w-4 text-muted-foreground" />
                      <span className="text-muted-foreground">Stock Tracking:</span>
                      <Badge variant={item.track_stock ? 'default' : 'secondary'}>
                        {item.track_stock ? 'Enabled' : 'Disabled'}
                      </Badge>
                    </div>
                    
                    {features.modifiers && (
                      <div className="flex items-center gap-2 text-sm">
                        <Settings className="h-4 w-4 text-muted-foreground" />
                        <span className="text-muted-foreground">Modifiers:</span>
                        <Badge variant={item.allow_modifiers ? 'default' : 'secondary'}>
                          {item.allow_modifiers ? 'Allowed' : 'Not Allowed'}
                        </Badge>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </PageLayout.Content>
      </PageLayout>
    </>
  );
}