import { cn, formatCurrency } from '@/lib/utils';

interface ItemPriceProps {
  price: number;
  originalPrice?: number;
  size?: 'sm' | 'md' | 'lg';
  showCurrency?: boolean;
  className?: string;
}

export function ItemPrice({ price, originalPrice, size = 'md', showCurrency = true, className }: ItemPriceProps) {
  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg',
  };

  const hasDiscount = originalPrice && originalPrice > price;

  return (
    <div className={cn('flex items-center gap-2', className)}>
      <span className={cn('font-semibold', sizeClasses[size], hasDiscount && 'text-destructive')}>
        {showCurrency ? formatCurrency(price) : price.toFixed(2)}
      </span>
      {hasDiscount && (
        <span className={cn('text-muted-foreground line-through', size === 'sm' ? 'text-xs' : 'text-sm')}>
          {showCurrency ? formatCurrency(originalPrice) : originalPrice.toFixed(2)}
        </span>
      )}
    </div>
  );
}
