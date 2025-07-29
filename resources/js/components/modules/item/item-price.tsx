import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { 
  DollarSign, 
  TrendingDown, 
  Info,
  Clock,
  MapPin,
  Users,
  Calendar,
  Percent,
  Calculator
} from 'lucide-react';
import { formatCurrency } from '@/lib/format';

interface PriceRule {
  id: number;
  name: string;
  type: 'percentage_discount' | 'fixed_discount' | 'override' | 'multiplier';
  value: number;
  adjustment: number;
}

interface ItemPriceProps {
  basePrice: number;
  finalPrice?: number;
  cost?: number;
  currency?: string;
  showCost?: boolean;
  showDiscount?: boolean;
  showMargin?: boolean;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  orientation?: 'horizontal' | 'vertical';
  appliedRules?: PriceRule[];
  className?: string;
}

export function ItemPrice({
  basePrice,
  finalPrice,
  cost,
  currency = '$',
  showCost = false,
  showDiscount = true,
  showMargin = false,
  size = 'md',
  orientation = 'horizontal',
  appliedRules = [],
  className,
}: ItemPriceProps) {
  const hasDiscount = finalPrice !== undefined && finalPrice < basePrice;
  const discountAmount = hasDiscount ? basePrice - finalPrice : 0;
  const discountPercentage = hasDiscount ? ((discountAmount / basePrice) * 100).toFixed(0) : 0;
  const margin = cost ? ((basePrice - cost) / basePrice * 100).toFixed(1) : null;
  
  const sizeStyles = {
    sm: {
      base: 'text-sm',
      final: 'text-lg',
      cost: 'text-xs',
      badge: 'text-xs',
    },
    md: {
      base: 'text-base',
      final: 'text-xl',
      cost: 'text-sm',
      badge: 'text-sm',
    },
    lg: {
      base: 'text-lg',
      final: 'text-2xl',
      cost: 'text-base',
      badge: 'text-base',
    },
    xl: {
      base: 'text-xl',
      final: 'text-3xl',
      cost: 'text-lg',
      badge: 'text-lg',
    },
  };

  const isVertical = orientation === 'vertical';

  return (
    <div className={cn(
      'flex items-center gap-3',
      isVertical && 'flex-col items-start gap-2',
      className
    )}>
      {/* Main Price Display */}
      <div className={cn(
        'flex items-baseline gap-2',
        isVertical && 'flex-col items-start gap-1'
      )}>
        {hasDiscount ? (
          <>
            <span className={cn(
              'font-bold text-primary',
              sizeStyles[size].final
            )}>
              {formatCurrency(finalPrice)}
            </span>
            <span className={cn(
              'line-through text-muted-foreground',
              sizeStyles[size].base
            )}>
              {formatCurrency(basePrice)}
            </span>
          </>
        ) : (
          <span className={cn(
            'font-bold',
            sizeStyles[size].final
          )}>
            {formatCurrency(basePrice)}
          </span>
        )}
      </div>

      {/* Discount Badge */}
      {showDiscount && hasDiscount && (
        <div className="flex items-center gap-2">
          <Badge variant="destructive" className={sizeStyles[size].badge}>
            <TrendingDown className="mr-1 h-3 w-3" />
            {discountPercentage}% OFF
          </Badge>
          {appliedRules.length > 0 && (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <button className="text-muted-foreground hover:text-foreground transition-colors">
                    <Info className="h-4 w-4" />
                  </button>
                </TooltipTrigger>
                <TooltipContent className="max-w-xs">
                  <div className="space-y-2">
                    <p className="font-medium">Applied Discounts:</p>
                    {appliedRules.map((rule) => (
                      <div key={rule.id} className="flex justify-between gap-4 text-sm">
                        <span>{rule.name}</span>
                        <span className="font-medium text-destructive">
                          -{formatCurrency(Math.abs(rule.adjustment))}
                        </span>
                      </div>
                    ))}
                  </div>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          )}
        </div>
      )}

      {/* Cost and Margin */}
      {(showCost || showMargin) && cost && (
        <div className={cn(
          'flex items-center gap-3 text-muted-foreground',
          sizeStyles[size].cost,
          isVertical && 'flex-col items-start gap-1'
        )}>
          {showCost && (
            <div className="flex items-center gap-1">
              <DollarSign className="h-3 w-3" />
              <span>Cost: {formatCurrency(cost)}</span>
            </div>
          )}
          {showMargin && margin && (
            <div className="flex items-center gap-1">
              <Percent className="h-3 w-3" />
              <span>Margin: {margin}%</span>
            </div>
          )}
        </div>
      )}
    </div>
  );
}

interface PriceBreakdownProps {
  basePrice: number;
  appliedRules: Array<{
    id: number;
    name: string;
    type: 'percentage_discount' | 'fixed_discount' | 'override' | 'multiplier';
    value: number;
    adjustment: number;
    conditions?: {
      location?: string;
      time?: string;
      quantity?: number;
      customerGroup?: string;
    };
  }>;
  finalPrice: number;
  className?: string;
}

export function PriceBreakdown({
  basePrice,
  appliedRules,
  finalPrice,
  className,
}: PriceBreakdownProps) {
  const getConditionIcon = (condition: string) => {
    switch (condition) {
      case 'location':
        return MapPin;
      case 'time':
        return Clock;
      case 'quantity':
        return Calculator;
      case 'customerGroup':
        return Users;
      default:
        return Calendar;
    }
  };

  const getRuleTypeIcon = (type: string) => {
    switch (type) {
      case 'percentage_discount':
        return Percent;
      case 'fixed_discount':
        return DollarSign;
      case 'override':
        return Calculator;
      case 'multiplier':
        return TrendingDown;
      default:
        return DollarSign;
    }
  };

  return (
    <div className={cn('space-y-3', className)}>
      {/* Base Price */}
      <div className="flex items-center justify-between">
        <span className="text-sm text-muted-foreground">Base Price</span>
        <span className="font-medium">{formatCurrency(basePrice)}</span>
      </div>

      {/* Applied Rules */}
      {appliedRules.map((rule) => {
        const RuleIcon = getRuleTypeIcon(rule.type);
        const conditions = Object.entries(rule.conditions || {}).filter(([_, value]) => value);
        
        return (
          <div key={rule.id} className="space-y-1">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <RuleIcon className="h-4 w-4 text-muted-foreground" />
                <span className="text-sm">{rule.name}</span>
              </div>
              <span className={cn(
                "font-medium text-sm",
                rule.adjustment < 0 ? "text-destructive" : "text-green-600"
              )}>
                {rule.adjustment > 0 ? '+' : ''}{formatCurrency(rule.adjustment)}
              </span>
            </div>
            {conditions.length > 0 && (
              <div className="flex items-center gap-2 ml-6">
                {conditions.map(([key, value]) => {
                  const ConditionIcon = getConditionIcon(key);
                  return (
                    <Badge key={key} variant="secondary" className="text-xs">
                      <ConditionIcon className="mr-1 h-3 w-3" />
                      {value}
                    </Badge>
                  );
                })}
              </div>
            )}
          </div>
        );
      })}

      {/* Final Price */}
      <div className="flex items-center justify-between pt-3 border-t">
        <span className="font-medium">Final Price</span>
        <span className="text-lg font-bold text-primary">{formatCurrency(finalPrice)}</span>
      </div>
    </div>
  );
}

interface ComparisonPriceProps {
  originalPrice: number;
  currentPrice: number;
  comparisonLabel?: string;
  showSavings?: boolean;
  className?: string;
}

export function ComparisonPrice({
  originalPrice,
  currentPrice,
  comparisonLabel = "Was",
  showSavings = true,
  className,
}: ComparisonPriceProps) {
  const savings = originalPrice - currentPrice;
  const savingsPercentage = ((savings / originalPrice) * 100).toFixed(0);
  const hasSavings = savings > 0;

  if (!hasSavings) {
    return (
      <div className={cn('text-2xl font-bold', className)}>
        {formatCurrency(currentPrice)}
      </div>
    );
  }

  return (
    <div className={cn('space-y-2', className)}>
      <div className="flex items-baseline gap-3">
        <span className="text-2xl font-bold text-primary">
          {formatCurrency(currentPrice)}
        </span>
        <span className="text-sm text-muted-foreground">
          {comparisonLabel}: <span className="line-through">{formatCurrency(originalPrice)}</span>
        </span>
      </div>
      {showSavings && (
        <div className="flex items-center gap-2">
          <Badge variant="success">
            Save {formatCurrency(savings)} ({savingsPercentage}%)
          </Badge>
        </div>
      )}
    </div>
  );
}