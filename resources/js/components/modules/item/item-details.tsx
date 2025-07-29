import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
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
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion';
import { Separator } from '@/components/ui/separator';
import { 
  Package,
  DollarSign,
  Info,
  Clock,
  ChefHat,
  ShoppingCart,
  Hash,
  Calendar,
  MapPin,
  Truck,
  Shield,
  AlertCircle,
  FileText,
  BarChart3,
  Star,
  TrendingUp,
  Users,
  Heart,
  Share2,
  Bookmark
} from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/format';
import { ItemPrice } from './item-price';
import { ItemStockIndicator } from './item-stock-indicator';

interface NutritionalInfo {
  calories?: number;
  protein?: number;
  carbs?: number;
  fat?: number;
  fiber?: number;
  sodium?: number;
  [key: string]: number | undefined;
}

interface ItemDetailsProps {
  item: {
    id: number;
    name: string;
    description?: string | null;
    long_description?: string | null;
    type: 'product' | 'service' | 'combo';
    category_name?: string | null;
    base_price: number;
    final_price?: number;
    cost?: number | null;
    sku?: string | null;
    barcode?: string | null;
    is_available: boolean;
    track_stock?: boolean;
    current_stock?: number;
    min_stock?: number;
    preparation_time?: number | null;
    tags?: string[];
    allergens?: string[];
    nutritional_info?: NutritionalInfo;
    serving_size?: string;
    origin?: string;
    brand?: string;
    warranty?: string;
    return_policy?: string;
    created_at: string;
    updated_at: string;
    stats?: {
      total_sold?: number;
      revenue?: number;
      avg_rating?: number;
      review_count?: number;
      favorite_count?: number;
    };
  };
  showFullDetails?: boolean;
  showActions?: boolean;
  onAddToCart?: () => void;
  onToggleFavorite?: () => void;
  onShare?: () => void;
  className?: string;
}

export function ItemDetails({
  item,
  showFullDetails = true,
  showActions = true,
  onAddToCart,
  onToggleFavorite,
  onShare,
  className,
}: ItemDetailsProps) {
  const typeStyles = {
    product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  };

  const profitMargin = item.cost
    ? ((item.base_price - item.cost) / item.base_price * 100).toFixed(1)
    : null;

  return (
    <div className={cn('space-y-6', className)}>
      {/* Header Section */}
      <div className="space-y-4">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <h1 className="text-2xl font-bold">{item.name}</h1>
            <div className="flex flex-wrap items-center gap-2 mt-2">
              <Badge variant="secondary" className={cn('text-sm', typeStyles[item.type])}>
                {item.type}
              </Badge>
              {item.category_name && (
                <Badge variant="outline">{item.category_name}</Badge>
              )}
              <Badge variant={item.is_available ? 'success' : 'secondary'}>
                {item.is_available ? 'Available' : 'Unavailable'}
              </Badge>
            </div>
          </div>
          
          {showActions && (
            <div className="flex items-center gap-2">
              <Button
                variant="outline"
                size="icon"
                onClick={onToggleFavorite}
              >
                <Heart className="h-4 w-4" />
              </Button>
              <Button
                variant="outline"
                size="icon"
                onClick={onShare}
              >
                <Share2 className="h-4 w-4" />
              </Button>
            </div>
          )}
        </div>
        
        {item.description && (
          <p className="text-muted-foreground">{item.description}</p>
        )}
      </div>

      {/* Pricing Section */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <DollarSign className="h-5 w-5" />
            Pricing
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <ItemPrice
            basePrice={item.base_price}
            finalPrice={item.final_price}
            cost={item.cost}
            showCost={showFullDetails}
            showMargin={showFullDetails}
            size="lg"
          />
          
          {showFullDetails && profitMargin && (
            <div className="flex items-center justify-between p-3 bg-muted/50 rounded-lg">
              <span className="text-sm text-muted-foreground">Profit Margin</span>
              <span className="font-medium">{profitMargin}%</span>
            </div>
          )}
        </CardContent>
        {showActions && onAddToCart && (
          <CardFooter>
            <Button 
              className="w-full" 
              size="lg"
              onClick={onAddToCart}
              disabled={!item.is_available}
            >
              <ShoppingCart className="mr-2 h-4 w-4" />
              Add to Cart
            </Button>
          </CardFooter>
        )}
      </Card>

      {/* Stock Information */}
      {item.track_stock && item.current_stock !== undefined && (
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Package className="h-5 w-5" />
              Stock Information
            </CardTitle>
          </CardHeader>
          <CardContent>
            <ItemStockIndicator
              currentStock={item.current_stock}
              minStock={item.min_stock}
              variant="detailed"
              showProgress={true}
            />
          </CardContent>
        </Card>
      )}

      {/* Stats */}
      {item.stats && (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          {item.stats.total_sold !== undefined && (
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">Total Sold</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-baseline gap-2">
                  <span className="text-2xl font-bold">{item.stats.total_sold}</span>
                  <TrendingUp className="h-4 w-4 text-muted-foreground" />
                </div>
              </CardContent>
            </Card>
          )}
          
          {item.stats.revenue !== undefined && (
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">Revenue</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-baseline gap-2">
                  <span className="text-2xl font-bold">{formatCurrency(item.stats.revenue)}</span>
                  <DollarSign className="h-4 w-4 text-muted-foreground" />
                </div>
              </CardContent>
            </Card>
          )}
          
          {item.stats.avg_rating !== undefined && (
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">Rating</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-baseline gap-2">
                  <span className="text-2xl font-bold">{item.stats.avg_rating.toFixed(1)}</span>
                  <div className="flex items-center gap-1">
                    <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
                    <span className="text-sm text-muted-foreground">
                      ({item.stats.review_count})
                    </span>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}
          
          {item.stats.favorite_count !== undefined && (
            <Card>
              <CardHeader className="pb-3">
                <CardTitle className="text-sm font-medium">Favorites</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="flex items-baseline gap-2">
                  <span className="text-2xl font-bold">{item.stats.favorite_count}</span>
                  <Heart className="h-4 w-4 text-muted-foreground" />
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      )}

      {/* Detailed Information */}
      {showFullDetails && (
        <Tabs defaultValue="details" className="w-full">
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="details">Details</TabsTrigger>
            <TabsTrigger value="specifications">Specifications</TabsTrigger>
            {item.nutritional_info && (
              <TabsTrigger value="nutrition">Nutrition</TabsTrigger>
            )}
          </TabsList>
          
          <TabsContent value="details" className="space-y-4">
            <Card>
              <CardContent className="pt-6 space-y-4">
                {item.long_description && (
                  <div>
                    <h3 className="font-medium mb-2">Description</h3>
                    <p className="text-sm text-muted-foreground whitespace-pre-wrap">
                      {item.long_description}
                    </p>
                  </div>
                )}
                
                {item.tags && item.tags.length > 0 && (
                  <div>
                    <h3 className="font-medium mb-2">Tags</h3>
                    <div className="flex flex-wrap gap-2">
                      {item.tags.map((tag) => (
                        <Badge key={tag} variant="secondary">
                          {tag}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
                
                {item.allergens && item.allergens.length > 0 && (
                  <div>
                    <h3 className="font-medium mb-2 flex items-center gap-2">
                      <AlertCircle className="h-4 w-4 text-amber-500" />
                      Allergens
                    </h3>
                    <div className="flex flex-wrap gap-2">
                      {item.allergens.map((allergen) => (
                        <Badge key={allergen} variant="warning">
                          {allergen}
                        </Badge>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>
          </TabsContent>
          
          <TabsContent value="specifications" className="space-y-4">
            <Card>
              <CardContent className="pt-6">
                <dl className="space-y-3">
                  {item.sku && (
                    <div className="flex justify-between">
                      <dt className="text-sm text-muted-foreground">SKU</dt>
                      <dd className="text-sm font-medium">{item.sku}</dd>
                    </div>
                  )}
                  
                  {item.barcode && (
                    <div className="flex justify-between">
                      <dt className="text-sm text-muted-foreground">Barcode</dt>
                      <dd className="text-sm font-medium">{item.barcode}</dd>
                    </div>
                  )}
                  
                  {item.brand && (
                    <div className="flex justify-between">
                      <dt className="text-sm text-muted-foreground">Brand</dt>
                      <dd className="text-sm font-medium">{item.brand}</dd>
                    </div>
                  )}
                  
                  {item.origin && (
                    <div className="flex justify-between">
                      <dt className="text-sm text-muted-foreground">Origin</dt>
                      <dd className="text-sm font-medium">{item.origin}</dd>
                    </div>
                  )}
                  
                  {item.preparation_time && (
                    <div className="flex justify-between">
                      <dt className="text-sm text-muted-foreground">Preparation Time</dt>
                      <dd className="text-sm font-medium">{item.preparation_time} minutes</dd>
                    </div>
                  )}
                  
                  <Separator />
                  
                  <div className="flex justify-between">
                    <dt className="text-sm text-muted-foreground">Created</dt>
                    <dd className="text-sm font-medium">{formatDate(item.created_at)}</dd>
                  </div>
                  
                  <div className="flex justify-between">
                    <dt className="text-sm text-muted-foreground">Last Updated</dt>
                    <dd className="text-sm font-medium">{formatDate(item.updated_at)}</dd>
                  </div>
                </dl>
              </CardContent>
            </Card>
            
            {(item.warranty || item.return_policy) && (
              <Card>
                <CardHeader>
                  <CardTitle className="text-base flex items-center gap-2">
                    <Shield className="h-4 w-4" />
                    Warranty & Returns
                  </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                  {item.warranty && (
                    <div>
                      <h4 className="font-medium text-sm mb-1">Warranty</h4>
                      <p className="text-sm text-muted-foreground">{item.warranty}</p>
                    </div>
                  )}
                  {item.return_policy && (
                    <div>
                      <h4 className="font-medium text-sm mb-1">Return Policy</h4>
                      <p className="text-sm text-muted-foreground">{item.return_policy}</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}
          </TabsContent>
          
          {item.nutritional_info && (
            <TabsContent value="nutrition" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Nutritional Information</CardTitle>
                  {item.serving_size && (
                    <CardDescription>Per {item.serving_size}</CardDescription>
                  )}
                </CardHeader>
                <CardContent>
                  <div className="space-y-3">
                    {Object.entries(item.nutritional_info).map(([key, value]) => {
                      if (value === undefined) return null;
                      
                      const label = key.charAt(0).toUpperCase() + key.slice(1);
                      const unit = key === 'calories' ? 'cal' : 
                                  key === 'sodium' ? 'mg' : 'g';
                      
                      return (
                        <div key={key} className="flex justify-between items-center">
                          <span className="text-sm">{label}</span>
                          <span className="font-medium">
                            {value}{unit}
                          </span>
                        </div>
                      );
                    })}
                  </div>
                </CardContent>
              </Card>
            </TabsContent>
          )}
        </Tabs>
      )}
    </div>
  );
}

export function ItemQuickView({
  item,
  onClose,
  onAddToCart,
  className,
}: {
  item: ItemDetailsProps['item'];
  onClose?: () => void;
  onAddToCart?: () => void;
  className?: string;
}) {
  return (
    <div className={cn('space-y-4', className)}>
      <div className="flex items-start justify-between">
        <div>
          <h2 className="text-xl font-semibold">{item.name}</h2>
          <p className="text-sm text-muted-foreground mt-1">
            {item.category_name} • {item.type}
          </p>
        </div>
        <Badge variant={item.is_available ? 'success' : 'secondary'}>
          {item.is_available ? 'Available' : 'Unavailable'}
        </Badge>
      </div>
      
      {item.description && (
        <p className="text-sm">{item.description}</p>
      )}
      
      <Separator />
      
      <div className="space-y-3">
        <ItemPrice
          basePrice={item.base_price}
          finalPrice={item.final_price}
          size="lg"
          orientation="vertical"
        />
        
        {item.track_stock && item.current_stock !== undefined && (
          <ItemStockIndicator
            currentStock={item.current_stock}
            minStock={item.min_stock}
            variant="compact"
          />
        )}
      </div>
      
      {item.tags && item.tags.length > 0 && (
        <div className="flex flex-wrap gap-1">
          {item.tags.slice(0, 5).map((tag) => (
            <Badge key={tag} variant="secondary" className="text-xs">
              {tag}
            </Badge>
          ))}
        </div>
      )}
      
      <div className="flex gap-2 pt-4">
        <Button
          className="flex-1"
          onClick={onAddToCart}
          disabled={!item.is_available}
        >
          <ShoppingCart className="mr-2 h-4 w-4" />
          Add to Cart
        </Button>
        {onClose && (
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
        )}
      </div>
    </div>
  );
}