import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { 
  AlertCircle, 
  CheckCircle, 
  XCircle,
  Package,
  TrendingDown,
  TrendingUp,
  Clock,
  Info,
  AlertTriangle,
  Box
} from 'lucide-react';

interface StockIndicatorProps {
  currentStock: number;
  minStock?: number;
  maxStock?: number;
  reorderPoint?: number;
  reserved?: number;
  incoming?: number;
  showDetails?: boolean;
  showProgress?: boolean;
  size?: 'sm' | 'md' | 'lg';
  variant?: 'default' | 'compact' | 'detailed';
  className?: string;
}

export function ItemStockIndicator({
  currentStock,
  minStock = 0,
  maxStock,
  reorderPoint,
  reserved = 0,
  incoming = 0,
  showDetails = true,
  showProgress = true,
  size = 'md',
  variant = 'default',
  className,
}: StockIndicatorProps) {
  const available = currentStock - reserved;
  const effectiveReorderPoint = reorderPoint || minStock * 2;
  const referenceMax = maxStock || effectiveReorderPoint * 2;
  
  // Calculate stock status
  const getStockStatus = () => {
    if (available <= 0) return 'out-of-stock';
    if (available <= minStock) return 'critical';
    if (available <= effectiveReorderPoint) return 'low';
    if (maxStock && available >= maxStock * 0.9) return 'overstocked';
    return 'good';
  };

  const status = getStockStatus();
  const stockPercentage = (available / referenceMax) * 100;

  const statusConfig = {
    'out-of-stock': {
      label: 'Out of Stock',
      color: 'text-red-600 dark:text-red-400',
      bgColor: 'bg-red-100 dark:bg-red-900/30',
      progressColor: 'bg-red-500',
      icon: XCircle,
      variant: 'destructive' as const,
    },
    'critical': {
      label: 'Critical',
      color: 'text-red-600 dark:text-red-400',
      bgColor: 'bg-red-100 dark:bg-red-900/30',
      progressColor: 'bg-red-500',
      icon: AlertTriangle,
      variant: 'destructive' as const,
    },
    'low': {
      label: 'Low Stock',
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
      progressColor: 'bg-amber-500',
      icon: AlertCircle,
      variant: 'warning' as const,
    },
    'good': {
      label: 'In Stock',
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
      progressColor: 'bg-green-500',
      icon: CheckCircle,
      variant: 'success' as const,
    },
    'overstocked': {
      label: 'Overstocked',
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
      progressColor: 'bg-blue-500',
      icon: TrendingUp,
      variant: 'secondary' as const,
    },
  };

  const config = statusConfig[status];
  const StatusIcon = config.icon;

  const sizeStyles = {
    sm: {
      icon: 'h-3 w-3',
      text: 'text-xs',
      badge: 'text-xs',
      progress: 'h-1.5',
    },
    md: {
      icon: 'h-4 w-4',
      text: 'text-sm',
      badge: 'text-sm',
      progress: 'h-2',
    },
    lg: {
      icon: 'h-5 w-5',
      text: 'text-base',
      badge: 'text-base',
      progress: 'h-3',
    },
  };

  if (variant === 'compact') {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <div className={cn('flex items-center gap-2', className)}>
              <StatusIcon className={cn(sizeStyles[size].icon, config.color)} />
              <span className={cn(sizeStyles[size].text, 'font-medium', config.color)}>
                {available}
              </span>
            </div>
          </TooltipTrigger>
          <TooltipContent>
            <div className="space-y-1">
              <p className="font-medium">{config.label}</p>
              <p className="text-xs">Available: {available} units</p>
              {reserved > 0 && <p className="text-xs">Reserved: {reserved} units</p>}
              {incoming > 0 && <p className="text-xs">Incoming: {incoming} units</p>}
            </div>
          </TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (variant === 'detailed') {
    return (
      <div className={cn('space-y-3', className)}>
        {/* Status Badge */}
        <div className="flex items-center justify-between">
          <Badge variant={config.variant} className={sizeStyles[size].badge}>
            <StatusIcon className={cn('mr-1', sizeStyles[size].icon)} />
            {config.label}
          </Badge>
          {incoming > 0 && (
            <Badge variant="outline" className={sizeStyles[size].badge}>
              <TrendingDown className={cn('mr-1', sizeStyles[size].icon)} />
              {incoming} incoming
            </Badge>
          )}
        </div>

        {/* Stock Details */}
        <div className="grid grid-cols-2 gap-3">
          <div className="space-y-1">
            <p className={cn(sizeStyles[size].text, 'text-muted-foreground')}>On Hand</p>
            <p className={cn(sizeStyles[size].text, 'font-semibold')}>{currentStock}</p>
          </div>
          <div className="space-y-1">
            <p className={cn(sizeStyles[size].text, 'text-muted-foreground')}>Available</p>
            <p className={cn(sizeStyles[size].text, 'font-semibold', config.color)}>
              {available}
            </p>
          </div>
          {reserved > 0 && (
            <div className="space-y-1">
              <p className={cn(sizeStyles[size].text, 'text-muted-foreground')}>Reserved</p>
              <p className={cn(sizeStyles[size].text, 'font-semibold')}>{reserved}</p>
            </div>
          )}
          {reorderPoint && (
            <div className="space-y-1">
              <p className={cn(sizeStyles[size].text, 'text-muted-foreground')}>Reorder At</p>
              <p className={cn(sizeStyles[size].text, 'font-semibold')}>{reorderPoint}</p>
            </div>
          )}
        </div>

        {/* Progress Bar */}
        {showProgress && (
          <div className="space-y-2">
            <div className="flex justify-between items-center">
              <span className={cn(sizeStyles[size].text, 'text-muted-foreground')}>Stock Level</span>
              <span className={cn(sizeStyles[size].text, 'font-medium')}>
                {Math.round(stockPercentage)}%
              </span>
            </div>
            <Progress 
              value={Math.min(Math.max(stockPercentage, 0), 100)} 
              className={sizeStyles[size].progress}
              indicatorClassName={config.progressColor}
            />
            <div className="flex justify-between">
              <span className={cn(sizeStyles[size].text, 'text-xs text-muted-foreground')}>Min: {minStock}</span>
              {maxStock && (
                <span className={cn(sizeStyles[size].text, 'text-xs text-muted-foreground')}>Max: {maxStock}</span>
              )}
            </div>
          </div>
        )}
      </div>
    );
  }

  // Default variant
  return (
    <div className={cn('space-y-2', className)}>
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-2">
          <StatusIcon className={cn(sizeStyles[size].icon, config.color)} />
          <span className={cn(sizeStyles[size].text, config.color, 'font-medium')}>
            {config.label}
          </span>
        </div>
        {showDetails && (
          <span className={cn(sizeStyles[size].text, 'font-medium')}>
            {available} {reserved > 0 && `(${currentStock} - ${reserved})`}
          </span>
        )}
      </div>
      
      {showProgress && (
        <Progress 
          value={Math.min(Math.max(stockPercentage, 0), 100)} 
          className={sizeStyles[size].progress}
          indicatorClassName={config.progressColor}
        />
      )}

      {showDetails && incoming > 0 && (
        <div className="flex items-center gap-1">
          <Clock className={cn(sizeStyles[size].icon, 'text-muted-foreground')} />
          <span className={cn(sizeStyles[size].text, 'text-muted-foreground')}>
            {incoming} units incoming
          </span>
        </div>
      )}
    </div>
  );
}

interface StockBadgeProps {
  available: number;
  minStock?: number;
  showIcon?: boolean;
  size?: 'sm' | 'md' | 'lg';
  className?: string;
}

export function StockBadge({
  available,
  minStock = 0,
  showIcon = true,
  size = 'md',
  className,
}: StockBadgeProps) {
  const getStatus = () => {
    if (available <= 0) return 'out-of-stock';
    if (available <= minStock) return 'critical';
    if (available <= minStock * 2) return 'low';
    return 'good';
  };

  const status = getStatus();

  const statusConfig = {
    'out-of-stock': {
      label: 'Out of Stock',
      icon: XCircle,
      variant: 'destructive' as const,
    },
    'critical': {
      label: 'Critical',
      icon: AlertTriangle,
      variant: 'destructive' as const,
    },
    'low': {
      label: 'Low Stock',
      icon: AlertCircle,
      variant: 'warning' as const,
    },
    'good': {
      label: `${available} in stock`,
      icon: Package,
      variant: 'success' as const,
    },
  };

  const config = statusConfig[status];
  const StatusIcon = config.icon;

  const sizeStyles = {
    sm: 'text-xs',
    md: 'text-sm',
    lg: 'text-base',
  };

  return (
    <Badge variant={config.variant} className={cn(sizeStyles[size], className)}>
      {showIcon && <StatusIcon className="mr-1 h-3 w-3" />}
      {config.label}
    </Badge>
  );
}

interface MultiLocationStockProps {
  locations: Array<{
    id: number;
    name: string;
    stock: number;
    minStock?: number;
    reserved?: number;
  }>;
  showTotal?: boolean;
  className?: string;
}

export function MultiLocationStock({
  locations,
  showTotal = true,
  className,
}: MultiLocationStockProps) {
  const totalStock = locations.reduce((sum, loc) => sum + loc.stock, 0);
  const totalReserved = locations.reduce((sum, loc) => sum + (loc.reserved || 0), 0);
  const totalAvailable = totalStock - totalReserved;

  return (
    <div className={cn('space-y-3', className)}>
      {showTotal && (
        <div className="pb-3 border-b">
          <div className="flex items-center justify-between">
            <span className="text-sm font-medium">Total Stock</span>
            <span className="text-lg font-semibold">{totalAvailable}</span>
          </div>
        </div>
      )}
      
      <div className="space-y-2">
        {locations.map((location) => {
          const available = location.stock - (location.reserved || 0);
          const isLow = location.minStock && available <= location.minStock;
          
          return (
            <div key={location.id} className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <Box className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">{location.name}</span>
              </div>
              <div className="flex items-center gap-2">
                <span className={cn(
                  "text-sm font-medium",
                  available <= 0 && "text-red-600",
                  isLow && available > 0 && "text-amber-600"
                )}>
                  {available}
                </span>
                {location.reserved && location.reserved > 0 && (
                  <span className="text-xs text-muted-foreground">
                    ({location.reserved} reserved)
                  </span>
                )}
              </div>
            </div>
          );
        })}
      </div>
    </div>
  );
}