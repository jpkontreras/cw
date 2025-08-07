import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
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
  Settings,
  Tag,
  Barcode,
  Building2,
  Percent,
  Timer,
  Calculator
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';

interface Variant {
  id: number;
  name: string;
  sku: string | null;
  price: number;
  cost: number | null;
  trackStock?: boolean;
  isAvailable?: boolean;
  currentStock?: number;
  // Legacy snake_case support
  track_stock?: boolean;
  is_available?: boolean;
  current_stock?: number;
}

interface ModifierGroup {
  id: number;
  name: string;
  description: string | null;
  minSelections?: number;
  maxSelections?: number;
  isRequired?: boolean;
  modifiers: Array<{
    id: number;
    name: string;
    priceAdjustment?: number;
    isAvailable?: boolean;
    // Legacy snake_case support
    price_adjustment?: number;
    is_available?: boolean;
  }>;
  // Legacy snake_case support
  min_selections?: number;
  max_selections?: number;
  is_required?: boolean;
}

interface PriceCalculation {
  basePrice: number;
  total: number;
  subtotal: number;
  variantAdjustment: number;
  modifierAdjustments: any[];
  locationPrice: number | null;
  appliedRules: Array<{
    type: string;
    name: string;
    adjustment: number;
  }>;
  currency: string;
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
  basePrice: number | null;
  baseCost: number | null;
  sku: string | null;
  barcode: string | null;
  trackInventory: boolean;
  isAvailable: boolean;
  preparationTime: number | null;
  createdAt: string;
  updatedAt: string;
  variants: Variant[];
  images: Array<{
    id: number;
    url: string;
    isPrimary: boolean;
  }>;
  // Legacy snake_case support
  base_price?: number | null;
  base_cost?: number | null;
  track_stock?: boolean;
  is_available?: boolean;
  preparation_time?: number | null;
  created_at?: string;
  updated_at?: string;
  allow_modifiers?: boolean;
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
  // Handle both camelCase and snake_case properties
  const basePrice = item.basePrice ?? item.base_price;
  const cost = item.baseCost ?? item.base_cost ?? (item as any).cost;
  const isAvailable = item.isAvailable ?? item.is_available;
  const trackStock = item.trackInventory ?? item.track_stock;
  const preparationTime = item.preparationTime ?? item.preparation_time;
  const createdAt = item.createdAt ?? item.created_at;
  const updatedAt = item.updatedAt ?? item.updated_at;
  const allowModifiers = item.allow_modifiers ?? false;
  
  const primaryImage = item.images?.find(img => img.isPrimary ?? (img as any).is_primary) || item.images?.[0];
  const margin = cost && basePrice ? ((basePrice - cost) / basePrice * 100).toFixed(1) : null;
  
  const typeStyles = {
    product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  };
  
  const typeIcons = {
    product: Package,
    service: Settings,
    combo: Building2,
  };
  
  const TypeIcon = typeIcons[item.type] || Package;

  const stockStatus = inventory ? {
    level: (inventory.quantity_on_hand / (inventory.reorder_quantity * 2)) * 100,
    status: inventory.quantity_on_hand <= inventory.min_quantity ? 'critical' :
            inventory.quantity_on_hand <= inventory.reorder_quantity ? 'low' : 'good',
    color: inventory.quantity_on_hand <= inventory.min_quantity ? 'bg-red-500' :
           inventory.quantity_on_hand <= inventory.reorder_quantity ? 'bg-amber-500' : 'bg-green-500'
  } : null;

  return (
    <AppLayout>
      <Head title={item.name} />
      
      <Page>
        <Page.Header
          title={
            <div className="flex items-center gap-3">
              <span>{item.name}</span>
              <Badge variant={isAvailable ? 'success' : 'secondary'}>
                {isAvailable ? 'Available' : 'Unavailable'}
              </Badge>
              <Badge variant="secondary" className={cn(typeStyles[item.type])}>
                {item.type}
              </Badge>
            </div>
          }
          subtitle={item.description || 'No description available'}
          actions={
            <Page.Actions>
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
            </Page.Actions>
          }
        />
        
        <Page.Content>
          {/* Hero Section with Image */}
          <div className="mb-6">
            <Card className="overflow-hidden">
              <div className="grid md:grid-cols-2 gap-0">
                {/* Image Section */}
                <div className="relative bg-gray-50 dark:bg-gray-900">
                  {primaryImage ? (
                    <img
                      src={primaryImage.url}
                      alt={item.name}
                      className="w-full h-full object-cover"
                    />
                  ) : (
                    <div className="flex items-center justify-center h-full min-h-[400px] bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800">
                      <div className="text-center p-12">
                        <div className="relative">
                          <div className="absolute inset-0 flex items-center justify-center">
                            <div className="w-32 h-32 bg-gray-200 dark:bg-gray-700 rounded-full opacity-20" />
                          </div>
                          <TypeIcon className="h-16 w-16 mx-auto text-gray-400 dark:text-gray-600 relative z-10" />
                        </div>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-4">No image available</p>
                        <Button
                          variant="outline"
                          size="sm"
                          className="mt-4"
                          onClick={() => router.visit(`/items/${item.id}/edit#images`)}
                        >
                          <Plus className="mr-2 h-4 w-4" />
                          Add Image
                        </Button>
                      </div>
                    </div>
                  )}
                  
                  {/* Type Badge Overlay */}
                  <div className="absolute top-4 left-4">
                    <Badge variant="secondary" className={cn('gap-1', typeStyles[item.type])}>
                      <TypeIcon className="h-3 w-3" />
                      {item.type}
                    </Badge>
                  </div>
                  
                  {/* Status Badge Overlay */}
                  <div className="absolute top-4 right-4">
                    <Badge variant={isAvailable ? 'success' : 'secondary'}>
                      {isAvailable ? 'Available' : 'Unavailable'}
                    </Badge>
                  </div>
                </div>
                
                {/* Details Section */}
                <div className="p-6 lg:p-8 space-y-6">
                  <div>
                    <h2 className="text-2xl font-bold mb-2">{item.name}</h2>
                    <p className="text-muted-foreground">
                      {item.description || 'No description available'}
                    </p>
                  </div>
                  
                  <Separator />
                  
                  {/* Key Details Grid */}
                  <div className="grid grid-cols-2 gap-4">
                    <div className="space-y-1">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Tag className="h-4 w-4" />
                        <span>Category</span>
                      </div>
                      <p className="font-medium">{item.category_name || 'Uncategorized'}</p>
                    </div>
                    
                    <div className="space-y-1">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Timer className="h-4 w-4" />
                        <span>Prep Time</span>
                      </div>
                      <p className="font-medium">
                        {preparationTime ? `${preparationTime} min` : '—'}
                      </p>
                    </div>
                    
                    <div className="space-y-1">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Hash className="h-4 w-4" />
                        <span>SKU</span>
                      </div>
                      <p className="font-medium font-mono text-sm">{item.sku || '—'}</p>
                    </div>
                    
                    <div className="space-y-1">
                      <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <Barcode className="h-4 w-4" />
                        <span>Barcode</span>
                      </div>
                      <p className="font-medium font-mono text-sm">{item.barcode || '—'}</p>
                    </div>
                  </div>
                  
                  {/* Price Display */}
                  <div className="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-2">
                      <span className="text-sm text-muted-foreground">Base Price</span>
                      {margin && (
                        <Badge variant="outline" className="gap-1">
                          <Percent className="h-3 w-3" />
                          {margin}% margin
                        </Badge>
                      )}
                    </div>
                    <div className="flex items-baseline gap-4">
                      <span className="text-3xl font-bold">
                        {basePrice !== null ? formatCurrency(basePrice) : '—'}
                      </span>
                      {cost !== null && (
                        <span className="text-sm text-muted-foreground">
                          Cost: {formatCurrency(cost)}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </Card>
          </div>
          
          {/* Main Content Area */}
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
                  {/* Pricing Overview */}
                  <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium flex items-center gap-2">
                          <DollarSign className="h-4 w-4" />
                          Base Price
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">
                          {basePrice !== null ? formatCurrency(basePrice) : 'Not set'}
                        </p>
                        {basePrice === null && (
                          <p className="text-xs text-muted-foreground mt-1">Set a price to start selling</p>
                        )}
                      </CardContent>
                    </Card>
                    
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium flex items-center gap-2">
                          <Calculator className="h-4 w-4" />
                          Cost
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">
                          {cost !== null ? formatCurrency(cost) : 'Not set'}
                        </p>
                        {cost === null && (
                          <p className="text-xs text-muted-foreground mt-1">Track costs for profit analysis</p>
                        )}
                      </CardContent>
                    </Card>
                    
                    <Card>
                      <CardHeader className="pb-3">
                        <CardTitle className="text-base font-medium flex items-center gap-2">
                          <Percent className="h-4 w-4" />
                          Margin
                        </CardTitle>
                      </CardHeader>
                      <CardContent>
                        <p className="text-2xl font-bold">
                          {margin ? `${margin}%` : '—'}
                        </p>
                        {!margin && (
                          <p className="text-xs text-muted-foreground mt-1">Requires price and cost</p>
                        )}
                      </CardContent>
                    </Card>
                  </div>

                  {/* Dynamic Pricing Card */}
                  {current_price && features.dynamic_pricing && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                          <TrendingUp className="h-5 w-5" />
                          Current Pricing
                        </CardTitle>
                        <CardDescription>
                          Price with active rules and adjustments
                        </CardDescription>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="space-y-2">
                          <div className="flex justify-between py-2">
                            <span className="text-sm text-muted-foreground">Base Price</span>
                            <span className="font-medium">{formatCurrency(current_price.basePrice)}</span>
                          </div>
                          {current_price.appliedRules && current_price.appliedRules.length > 0 && (
                            <>
                              <Separator />
                              {current_price.appliedRules.map((rule, index) => (
                                <div key={index} className="flex justify-between py-2">
                                  <span className="text-sm text-muted-foreground">{rule.name}</span>
                                  <span className={cn(
                                    'font-medium',
                                    rule.adjustment > 0 ? 'text-red-600' : 'text-green-600'
                                  )}>
                                    {rule.adjustment > 0 ? '+' : ''}{formatCurrency(rule.adjustment)}
                                  </span>
                                </div>
                              ))}
                            </>
                          )}
                          <Separator />
                          <div className="flex justify-between py-2">
                            <span className="font-medium">Final Price</span>
                            <span className="text-xl font-bold">{formatCurrency(current_price.total)}</span>
                          </div>
                        </div>
                      </CardContent>
                    </Card>
                  )}

                  {/* Inventory Card */}
                  {features.inventory && trackStock && inventory && (
                    <Card>
                      <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                          <Box className="h-5 w-5" />
                          Inventory Status
                        </CardTitle>
                        <CardDescription>
                          Real-time stock levels and tracking
                        </CardDescription>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        {/* Stock Level Visual */}
                        <div className="space-y-3">
                          <div className="flex justify-between items-center">
                            <div>
                              <p className="text-2xl font-bold">{inventory.quantity_on_hand}</p>
                              <p className="text-sm text-muted-foreground">units available</p>
                            </div>
                            <Badge variant={stockStatus?.status === 'critical' ? 'destructive' : 
                                          stockStatus?.status === 'low' ? 'warning' : 'success'} 
                                   className="h-8 px-3">
                              {stockStatus?.status === 'critical' ? 'Critical' :
                               stockStatus?.status === 'low' ? 'Low Stock' : 'In Stock'}
                            </Badge>
                          </div>
                          <Progress value={stockStatus?.level} className="h-2" />
                        </div>
                        
                        <Separator />
                        
                        {/* Stock Details Grid */}
                        <div className="grid grid-cols-2 gap-3">
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Reserved</p>
                            <p className="text-lg font-semibold">{inventory.quantity_reserved}</p>
                          </div>
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Available</p>
                            <p className="text-lg font-semibold">
                              {inventory.quantity_on_hand - inventory.quantity_reserved}
                            </p>
                          </div>
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Min Level</p>
                            <p className="font-semibold">{inventory.min_quantity}</p>
                          </div>
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Reorder At</p>
                            <p className="font-semibold">{inventory.reorder_quantity}</p>
                          </div>
                        </div>
                        
                        <div className="flex gap-2">
                          <Button 
                            variant="outline" 
                            size="sm"
                            className="flex-1"
                            onClick={() => router.visit(`/inventory/adjustments?item_id=${item.id}`)}
                          >
                            <Plus className="mr-1 h-3 w-3" />
                            Adjust Stock
                          </Button>
                          <Button 
                            variant="outline" 
                            size="sm"
                            className="flex-1"
                            onClick={() => router.visit(`/inventory?item_id=${item.id}`)}
                          >
                            View History
                          </Button>
                        </div>
                        
                        <div className="text-xs text-muted-foreground space-y-1 pt-2">
                          <div className="flex justify-between">
                            <span>Last counted</span>
                            <span>{formatDate(inventory.last_counted_at)}</span>
                          </div>
                          <div className="flex justify-between">
                            <span>Last restocked</span>
                            <span>{formatDate(inventory.last_restocked_at)}</span>
                          </div>
                        </div>
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
                        <CardDescription>
                          Production recipe and costs
                        </CardDescription>
                      </CardHeader>
                      <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-3">
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Yield</p>
                            <p className="font-semibold">
                              {recipe.yield_quantity} {recipe.yield_unit}
                            </p>
                          </div>
                          <div className="bg-muted/50 rounded-lg p-3">
                            <p className="text-xs text-muted-foreground mb-1">Ingredients</p>
                            <p className="font-semibold">{recipe.ingredients_count} items</p>
                          </div>
                        </div>
                        
                        <div className="border rounded-lg p-4 space-y-2">
                          <div className="flex justify-between items-center">
                            <span className="text-sm text-muted-foreground">Total Cost</span>
                            <span className="font-medium">{formatCurrency(recipe.total_cost)}</span>
                          </div>
                          <Separator />
                          <div className="flex justify-between items-center">
                            <span className="text-sm font-medium">Cost per Portion</span>
                            <span className="text-lg font-bold">{formatCurrency(recipe.portion_cost)}</span>
                          </div>
                        </div>
                        
                        <Button 
                          variant="outline" 
                          size="sm"
                          className="w-full"
                          onClick={() => router.visit(`/recipes/${recipe.id}`)}
                        >
                          <FileText className="mr-2 h-3 w-3" />
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
                                  {(variant.trackStock ?? variant.track_stock) ? (variant.currentStock ?? variant.current_stock ?? 0) : '—'}
                                </TableCell>
                              )}
                              <TableCell>
                                <Badge variant={(variant.isAvailable ?? variant.is_available) ? 'success' : 'secondary'}>
                                  {(variant.isAvailable ?? variant.is_available) ? 'Available' : 'Unavailable'}
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
                                  {((modifier.priceAdjustment ?? modifier.price_adjustment) ?? 0) > 0 && '+'}
                                  {formatCurrency((modifier.priceAdjustment ?? modifier.price_adjustment) ?? 0)}
                                </TableCell>
                                <TableCell>
                                  <Badge variant={(modifier.isAvailable ?? modifier.is_available) ? 'success' : 'secondary'}>
                                    {(modifier.isAvailable ?? modifier.is_available) ? 'Available' : 'Unavailable'}
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
                        <Card className="relative overflow-hidden">
                          <div className="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 dark:bg-blue-400/10 rounded-full -mr-16 -mt-16" />
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Sold</CardTitle>
                            <ShoppingCart className="h-4 w-4 text-muted-foreground relative z-10" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{stats.total_sold.toLocaleString()}</div>
                            <p className="text-xs text-muted-foreground">units sold to date</p>
                          </CardContent>
                        </Card>
                        
                        <Card className="relative overflow-hidden">
                          <div className="absolute top-0 right-0 w-32 h-32 bg-green-500/10 dark:bg-green-400/10 rounded-full -mr-16 -mt-16" />
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Revenue</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground relative z-10" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.revenue)}</div>
                            <p className="text-xs text-muted-foreground">total revenue generated</p>
                          </CardContent>
                        </Card>
                        
                        <Card className="relative overflow-hidden">
                          <div className="absolute top-0 right-0 w-32 h-32 bg-purple-500/10 dark:bg-purple-400/10 rounded-full -mr-16 -mt-16" />
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Avg Order Value</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground relative z-10" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">{formatCurrency(stats.avg_order_value)}</div>
                            <p className="text-xs text-muted-foreground">per transaction</p>
                          </CardContent>
                        </Card>
                        
                        <Card className="relative overflow-hidden">
                          <div className="absolute top-0 right-0 w-32 h-32 bg-orange-500/10 dark:bg-orange-400/10 rounded-full -mr-16 -mt-16" />
                          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Popularity Rank</CardTitle>
                            <BarChart3 className="h-4 w-4 text-muted-foreground relative z-10" />
                          </CardHeader>
                          <CardContent>
                            <div className="text-2xl font-bold">#{stats.popularity_rank}</div>
                            <p className="text-xs text-muted-foreground">in your menu</p>
                            <Progress value={100 - (stats.popularity_rank * 10)} className="mt-2 h-1" />
                          </CardContent>
                        </Card>
                      </div>
                      
                      {/* Performance Insights */}
                      <Card>
                        <CardHeader>
                          <CardTitle className="text-base">Performance Insights</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                          <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                              <span className="text-muted-foreground">Profit Margin</span>
                              <span className="font-medium">
                                {cost && basePrice ? 
                                  `${((basePrice - cost) / basePrice * 100).toFixed(1)}%` : '—'}
                              </span>
                            </div>
                            <div className="flex justify-between text-sm">
                              <span className="text-muted-foreground">Avg Daily Sales</span>
                              <span className="font-medium">
                                {stats.total_sold > 0 ? Math.round(stats.total_sold / 30) : 0} units
                              </span>
                            </div>
                            <div className="flex justify-between text-sm">
                              <span className="text-muted-foreground">Revenue per Unit</span>
                              <span className="font-medium">
                                {stats.total_sold > 0 ? formatCurrency(stats.revenue / stats.total_sold) : '—'}
                              </span>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    </>
                  ) : (
                    <Card>
                      <CardContent className="flex flex-col items-center justify-center py-16">
                        <div className="relative mb-6">
                          <div className="absolute inset-0 flex items-center justify-center">
                            <div className="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-full opacity-20" />
                          </div>
                          <BarChart3 className="h-12 w-12 text-muted-foreground relative z-10" />
                        </div>
                        <p className="text-lg font-medium mb-2">No analytics data yet</p>
                        <p className="text-sm text-muted-foreground text-center max-w-sm">
                          Analytics will appear here once this item starts generating sales
                        </p>
                      </CardContent>
                    </Card>
                  )}
                </TabsContent>
              </Tabs>
            </div>

            {/* Sidebar */}
            <div className="space-y-4">

              {/* Quick Actions */}
              <Card>
                <CardHeader className="pb-3">
                  <CardTitle className="text-base">Quick Actions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                  {features.dynamic_pricing && basePrice === null && (
                    <Button 
                      variant="default" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/items/${item.id}/edit#pricing`)}
                    >
                      <DollarSign className="mr-2 h-4 w-4" />
                      Set Price
                    </Button>
                  )}
                  
                  {features.dynamic_pricing && basePrice !== null && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/pricing/create?item_id=${item.id}`)}
                    >
                      <DollarSign className="mr-2 h-4 w-4" />
                      Add Price Rule
                    </Button>
                  )}
                  
                  {features.inventory && trackStock && (
                    <Button 
                      variant="outline" 
                      className="w-full justify-start"
                      onClick={() => router.visit(`/inventory/adjustments?item_id=${item.id}`)}
                    >
                      <Box className="mr-2 h-4 w-4" />
                      Adjust Stock
                    </Button>
                  )}
                  
                  {features.modifiers && allowModifiers && (
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
                <CardHeader className="pb-3">
                  <CardTitle className="text-base">Information</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  <div className="grid grid-cols-2 gap-3 text-sm">
                    <div>
                      <p className="text-muted-foreground mb-1">ID</p>
                      <p className="font-mono font-medium">#{item.id}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground mb-1">Stock</p>
                      <Badge variant={trackStock ? 'default' : 'secondary'} className="text-xs">
                        {trackStock ? 'Tracked' : 'Not Tracked'}
                      </Badge>
                    </div>
                    <div>
                      <p className="text-muted-foreground mb-1">Created</p>
                      <p className="font-medium">{formatDate(createdAt)}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground mb-1">Updated</p>
                      <p className="font-medium">{formatDate(updatedAt)}</p>
                    </div>
                  </div>
                  
                  {features.modifiers && (
                    <div className="pt-3 border-t">
                      <div className="flex items-center justify-between">
                        <span className="text-sm text-muted-foreground">Modifiers</span>
                        <Badge variant={allowModifiers ? 'default' : 'secondary'} className="text-xs">
                          {allowModifiers ? 'Allowed' : 'Not Allowed'}
                        </Badge>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>
            </div>
          </div>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}