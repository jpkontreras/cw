import { useState } from 'react';
import { router } from '@inertiajs/react';
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
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Progress } from '@/components/ui/progress';
import { Skeleton } from '@/components/ui/skeleton';
import {
  Package,
  MoreVertical,
  Edit,
  Eye,
  Trash,
  AlertCircle,
  DollarSign,
  Box,
  Image as ImageIcon,
  TrendingUp,
  Clock,
  Star,
} from 'lucide-react';
import { formatCurrency } from '@/lib/format';

interface ItemCardProps {
  item: {
    id: number;
    name: string;
    description?: string | null;
    type: 'product' | 'service' | 'combo';
    category_name?: string | null;
    base_price: number;
    cost?: number | null;
    sku?: string | null;
    is_available: boolean;
    track_stock?: boolean;
    current_stock?: number;
    min_stock?: number;
    image_url?: string | null;
    variants_count?: number;
    modifiers_count?: number;
    rating?: number;
    sold_count?: number;
  };
  onEdit?: (item: any) => void;
  onDelete?: (item: any) => void;
  onView?: (item: any) => void;
  showActions?: boolean;
  showStock?: boolean;
  showStats?: boolean;
  className?: string;
}

export function ItemCard({
  item,
  onEdit,
  onDelete,
  onView,
  showActions = true,
  showStock = true,
  showStats = false,
  className,
}: ItemCardProps) {
  const [imageError, setImageError] = useState(false);
  
  const typeStyles = {
    product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  };

  const profitMargin = item.cost
    ? ((item.base_price - item.cost) / item.base_price * 100).toFixed(1)
    : null;

  const stockLevel = item.track_stock && item.current_stock !== undefined && item.min_stock
    ? (item.current_stock / (item.min_stock * 2)) * 100
    : 0;

  const isLowStock = item.track_stock && item.current_stock !== undefined && item.min_stock
    ? item.current_stock <= item.min_stock
    : false;

  const handleImageError = () => {
    setImageError(true);
  };

  const handleView = () => {
    if (onView) {
      onView(item);
    } else {
      router.visit(`/items/${item.id}`);
    }
  };

  return (
    <Card className={cn('group hover:shadow-lg transition-all duration-200', className)}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex-1">
            <CardTitle className="line-clamp-1">{item.name}</CardTitle>
            {item.description && (
              <CardDescription className="line-clamp-2 mt-1">
                {item.description}
              </CardDescription>
            )}
          </div>
          {showActions && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  variant="ghost"
                  size="sm"
                  className="h-8 w-8 p-0 opacity-0 group-hover:opacity-100 transition-opacity"
                >
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleView}>
                  <Eye className="mr-2 h-4 w-4" />
                  View Details
                </DropdownMenuItem>
                {onEdit && (
                  <DropdownMenuItem onClick={() => onEdit(item)}>
                    <Edit className="mr-2 h-4 w-4" />
                    Edit
                  </DropdownMenuItem>
                )}
                <DropdownMenuSeparator />
                {onDelete && (
                  <DropdownMenuItem 
                    className="text-destructive"
                    onClick={() => onDelete(item)}
                  >
                    <Trash className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
        <div className="flex flex-wrap gap-2 mt-3">
          <Badge variant="secondary" className={cn('text-xs', typeStyles[item.type])}>
            {item.type}
          </Badge>
          {item.category_name && (
            <Badge variant="outline" className="text-xs">
              {item.category_name}
            </Badge>
          )}
          {item.sku && (
            <Badge variant="outline" className="text-xs">
              SKU: {item.sku}
            </Badge>
          )}
          <Badge variant={item.is_available ? 'success' : 'secondary'} className="text-xs">
            {item.is_available ? 'Available' : 'Unavailable'}
          </Badge>
        </div>
      </CardHeader>

      <CardContent className="space-y-4">
        {/* Image */}
        <div className="relative aspect-video rounded-lg overflow-hidden bg-muted">
          {item.image_url && !imageError ? (
            <img
              src={item.image_url}
              alt={item.name}
              className="w-full h-full object-cover"
              onError={handleImageError}
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center">
              <ImageIcon className="h-12 w-12 text-muted-foreground" />
            </div>
          )}
          {isLowStock && (
            <div className="absolute top-2 right-2">
              <Badge variant="warning" className="shadow-lg">
                <AlertCircle className="mr-1 h-3 w-3" />
                Low Stock
              </Badge>
            </div>
          )}
        </div>

        {/* Pricing */}
        <div className="space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-sm text-muted-foreground">Price</span>
            <span className="text-lg font-semibold">{formatCurrency(item.base_price)}</span>
          </div>
          {item.cost && (
            <div className="flex items-center justify-between">
              <span className="text-sm text-muted-foreground">Cost</span>
              <span className="text-sm">{formatCurrency(item.cost)}</span>
            </div>
          )}
          {profitMargin && (
            <div className="space-y-1">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Profit Margin</span>
                <span className={cn(
                  "font-medium",
                  parseFloat(profitMargin) < 30 && "text-amber-600",
                  parseFloat(profitMargin) >= 30 && parseFloat(profitMargin) < 50 && "text-green-600",
                  parseFloat(profitMargin) >= 50 && "text-blue-600"
                )}>
                  {profitMargin}%
                </span>
              </div>
              <Progress 
                value={Math.min(parseFloat(profitMargin), 100)} 
                className="h-1.5"
                indicatorClassName={cn(
                  parseFloat(profitMargin) < 30 && "bg-amber-500",
                  parseFloat(profitMargin) >= 30 && parseFloat(profitMargin) < 50 && "bg-green-500",
                  parseFloat(profitMargin) >= 50 && "bg-blue-500"
                )}
              />
            </div>
          )}
        </div>

        {/* Stock */}
        {showStock && item.track_stock && item.current_stock !== undefined && (
          <div className="space-y-1">
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Stock Level</span>
              <span className={cn(
                "font-medium",
                isLowStock && "text-amber-600"
              )}>
                {item.current_stock} units
              </span>
            </div>
            <Progress 
              value={Math.min(stockLevel, 100)} 
              className="h-1.5"
              indicatorClassName={cn(
                isLowStock ? "bg-amber-500" : "bg-green-500"
              )}
            />
          </div>
        )}

        {/* Features */}
        {(item.variants_count || item.modifiers_count) && (
          <div className="flex gap-3 pt-2">
            {item.variants_count && item.variants_count > 0 && (
              <div className="flex items-center gap-1 text-sm text-muted-foreground">
                <Package className="h-4 w-4" />
                <span>{item.variants_count} variants</span>
              </div>
            )}
            {item.modifiers_count && item.modifiers_count > 0 && (
              <div className="flex items-center gap-1 text-sm text-muted-foreground">
                <Box className="h-4 w-4" />
                <span>{item.modifiers_count} modifiers</span>
              </div>
            )}
          </div>
        )}

        {/* Stats */}
        {showStats && (item.rating || item.sold_count) && (
          <div className="flex items-center justify-between pt-2 border-t">
            {item.rating && (
              <div className="flex items-center gap-1">
                <Star className="h-4 w-4 fill-amber-400 text-amber-400" />
                <span className="text-sm font-medium">{item.rating.toFixed(1)}</span>
              </div>
            )}
            {item.sold_count && (
              <div className="flex items-center gap-1 text-sm text-muted-foreground">
                <TrendingUp className="h-4 w-4" />
                <span>{item.sold_count} sold</span>
              </div>
            )}
          </div>
        )}
      </CardContent>

      <CardFooter className="pt-3">
        <Button 
          className="w-full" 
          variant="outline"
          onClick={handleView}
        >
          View Details
        </Button>
      </CardFooter>
    </Card>
  );
}

export function ItemCardSkeleton() {
  return (
    <Card>
      <CardHeader className="pb-3">
        <Skeleton className="h-5 w-3/4" />
        <Skeleton className="h-4 w-full mt-2" />
        <div className="flex gap-2 mt-3">
          <Skeleton className="h-5 w-16" />
          <Skeleton className="h-5 w-20" />
        </div>
      </CardHeader>
      <CardContent className="space-y-4">
        <Skeleton className="aspect-video rounded-lg" />
        <div className="space-y-2">
          <div className="flex justify-between">
            <Skeleton className="h-4 w-16" />
            <Skeleton className="h-6 w-20" />
          </div>
          <Skeleton className="h-1.5 w-full" />
        </div>
      </CardContent>
      <CardFooter className="pt-3">
        <Skeleton className="h-10 w-full" />
      </CardFooter>
    </Card>
  );
}