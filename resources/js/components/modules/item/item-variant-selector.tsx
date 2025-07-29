import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { 
  Package,
  Check,
  AlertCircle,
  DollarSign,
  Hash,
  Ruler,
  Palette,
  Box,
  Image as ImageIcon
} from 'lucide-react';
import { formatCurrency } from '@/lib/format';
import { ItemPrice } from './item-price';
import { StockBadge } from './item-stock-indicator';

interface Variant {
  id: number;
  name: string;
  sku?: string | null;
  price: number;
  cost?: number | null;
  is_available: boolean;
  track_stock?: boolean;
  current_stock?: number;
  min_stock?: number;
  attributes?: Record<string, string>;
  image_url?: string | null;
  description?: string | null;
}

interface ItemVariantSelectorProps {
  variants: Variant[];
  selectedVariantId?: number;
  onVariantChange?: (variantId: number) => void;
  showPrices?: boolean;
  showStock?: boolean;
  showSku?: boolean;
  showImages?: boolean;
  variant?: 'default' | 'grid' | 'list' | 'compact';
  className?: string;
}

export function ItemVariantSelector({
  variants,
  selectedVariantId,
  onVariantChange,
  showPrices = true,
  showStock = true,
  showSku = true,
  showImages = false,
  variant = 'default',
  className,
}: ItemVariantSelectorProps) {
  const [selectedId, setSelectedId] = useState<number | undefined>(selectedVariantId);

  const handleVariantChange = (variantId: string) => {
    const id = parseInt(variantId);
    setSelectedId(id);
    onVariantChange?.(id);
  };

  const selectedVariant = variants.find(v => v.id === selectedId);

  // Group variants by common attributes
  const groupVariantsByAttribute = () => {
    const groups: Record<string, Variant[]> = {};
    
    variants.forEach(variant => {
      if (variant.attributes) {
        Object.entries(variant.attributes).forEach(([key, value]) => {
          const groupKey = `${key}:${value}`;
          if (!groups[groupKey]) {
            groups[groupKey] = [];
          }
          groups[groupKey].push(variant);
        });
      }
    });
    
    return groups;
  };

  const getAttributeIcon = (attribute: string) => {
    const lowerAttr = attribute.toLowerCase();
    if (lowerAttr.includes('size')) return Ruler;
    if (lowerAttr.includes('color')) return Palette;
    if (lowerAttr.includes('material')) return Box;
    return Package;
  };

  if (variant === 'compact') {
    return (
      <div className={cn('space-y-2', className)}>
        <Label className="text-sm font-medium">Select Variant</Label>
        <RadioGroup
          value={selectedId?.toString() || ''}
          onValueChange={handleVariantChange}
          className="flex flex-wrap gap-2"
        >
          {variants.filter(v => v.is_available).map((variant) => (
            <label
              key={variant.id}
              className={cn(
                "flex items-center gap-2 px-3 py-1.5 border rounded-md cursor-pointer transition-all",
                selectedId === variant.id
                  ? "border-primary bg-primary/10"
                  : "hover:border-gray-300"
              )}
            >
              <RadioGroupItem value={variant.id.toString()} className="sr-only" />
              <span className="text-sm font-medium">{variant.name}</span>
              {showPrices && (
                <span className="text-sm text-muted-foreground">
                  {formatCurrency(variant.price)}
                </span>
              )}
              {selectedId === variant.id && (
                <Check className="h-3 w-3 text-primary ml-auto" />
              )}
            </label>
          ))}
        </RadioGroup>
      </div>
    );
  }

  if (variant === 'grid') {
    return (
      <div className={cn('space-y-3', className)}>
        <Label className="text-base font-medium">Choose Your Option</Label>
        <RadioGroup
          value={selectedId?.toString() || ''}
          onValueChange={handleVariantChange}
          className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
        >
          {variants.filter(v => v.is_available).map((variant) => {
            const isSelected = selectedId === variant.id;
            const isLowStock = variant.track_stock && variant.current_stock !== undefined && 
                              variant.min_stock !== undefined && variant.current_stock <= variant.min_stock;
            
            return (
              <label
                key={variant.id}
                className={cn(
                  "relative border rounded-lg p-4 cursor-pointer transition-all",
                  isSelected
                    ? "border-primary bg-primary/5 shadow-sm"
                    : "hover:border-gray-300 hover:shadow-sm",
                  !variant.is_available && "opacity-50 cursor-not-allowed"
                )}
              >
                <RadioGroupItem value={variant.id.toString()} className="sr-only" />
                
                <div className="space-y-3">
                  {showImages && variant.image_url && (
                    <div className="aspect-square rounded-md overflow-hidden bg-muted">
                      <img
                        src={variant.image_url}
                        alt={variant.name}
                        className="w-full h-full object-cover"
                      />
                    </div>
                  )}
                  
                  <div>
                    <h4 className="font-medium">{variant.name}</h4>
                    {variant.description && (
                      <p className="text-sm text-muted-foreground mt-1">
                        {variant.description}
                      </p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    {showSku && variant.sku && (
                      <div className="flex items-center gap-1 text-xs text-muted-foreground">
                        <Hash className="h-3 w-3" />
                        <span>{variant.sku}</span>
                      </div>
                    )}
                    
                    {variant.attributes && (
                      <div className="flex flex-wrap gap-1">
                        {Object.entries(variant.attributes).map(([key, value]) => {
                          const Icon = getAttributeIcon(key);
                          return (
                            <Badge key={key} variant="secondary" className="text-xs">
                              <Icon className="mr-1 h-3 w-3" />
                              {value}
                            </Badge>
                          );
                        })}
                      </div>
                    )}
                  </div>
                  
                  <div className="flex items-center justify-between">
                    {showPrices && (
                      <ItemPrice
                        basePrice={variant.price}
                        cost={variant.cost}
                        size="sm"
                        showDiscount={false}
                      />
                    )}
                    
                    {showStock && variant.track_stock && variant.current_stock !== undefined && (
                      <StockBadge
                        available={variant.current_stock}
                        minStock={variant.min_stock}
                        size="sm"
                      />
                    )}
                  </div>
                </div>
                
                {isSelected && (
                  <div className="absolute top-2 right-2">
                    <div className="w-6 h-6 bg-primary rounded-full flex items-center justify-center">
                      <Check className="h-4 w-4 text-primary-foreground" />
                    </div>
                  </div>
                )}
              </label>
            );
          })}
        </RadioGroup>
      </div>
    );
  }

  if (variant === 'list') {
    return (
      <div className={cn('space-y-3', className)}>
        <Label className="text-base font-medium">Select Option</Label>
        <RadioGroup
          value={selectedId?.toString() || ''}
          onValueChange={handleVariantChange}
          className="space-y-2"
        >
          {variants.filter(v => v.is_available).map((variant) => {
            const isSelected = selectedId === variant.id;
            
            return (
              <label
                key={variant.id}
                className={cn(
                  "flex items-center gap-4 p-4 border rounded-lg cursor-pointer transition-all",
                  isSelected
                    ? "border-primary bg-primary/5"
                    : "hover:border-gray-300"
                )}
              >
                <RadioGroupItem value={variant.id.toString()} />
                
                {showImages && variant.image_url && (
                  <div className="w-16 h-16 rounded-md overflow-hidden bg-muted shrink-0">
                    <img
                      src={variant.image_url}
                      alt={variant.name}
                      className="w-full h-full object-cover"
                    />
                  </div>
                )}
                
                <div className="flex-1 space-y-1">
                  <div className="flex items-start justify-between">
                    <div>
                      <h4 className="font-medium">{variant.name}</h4>
                      {variant.description && (
                        <p className="text-sm text-muted-foreground">
                          {variant.description}
                        </p>
                      )}
                    </div>
                    {showPrices && (
                      <span className="font-semibold">
                        {formatCurrency(variant.price)}
                      </span>
                    )}
                  </div>
                  
                  <div className="flex items-center gap-3">
                    {showSku && variant.sku && (
                      <Badge variant="outline" className="text-xs">
                        SKU: {variant.sku}
                      </Badge>
                    )}
                    
                    {variant.attributes && (
                      <div className="flex gap-1">
                        {Object.entries(variant.attributes).map(([key, value]) => (
                          <Badge key={key} variant="secondary" className="text-xs">
                            {value}
                          </Badge>
                        ))}
                      </div>
                    )}
                    
                    {showStock && variant.track_stock && variant.current_stock !== undefined && (
                      <StockBadge
                        available={variant.current_stock}
                        minStock={variant.min_stock}
                        size="sm"
                        showIcon={false}
                      />
                    )}
                  </div>
                </div>
              </label>
            );
          })}
        </RadioGroup>
      </div>
    );
  }

  // Default variant
  return (
    <div className={cn('space-y-4', className)}>
      <div>
        <Label className="text-base font-medium">Select Variant</Label>
        {selectedVariant && (
          <p className="text-sm text-muted-foreground mt-1">
            Selected: {selectedVariant.name}
          </p>
        )}
      </div>
      
      <RadioGroup
        value={selectedId?.toString() || ''}
        onValueChange={handleVariantChange}
        className="space-y-3"
      >
        {variants.filter(v => v.is_available).map((variant) => {
          const isSelected = selectedId === variant.id;
          
          return (
            <Card
              key={variant.id}
              className={cn(
                "cursor-pointer transition-all",
                isSelected
                  ? "border-primary shadow-sm"
                  : "hover:border-gray-300"
              )}
            >
              <label className="block cursor-pointer">
                <CardContent className="p-4">
                  <div className="flex items-start gap-3">
                    <RadioGroupItem value={variant.id.toString()} className="mt-1" />
                    
                    <div className="flex-1 space-y-3">
                      <div className="flex items-start justify-between">
                        <div>
                          <h4 className="font-medium">{variant.name}</h4>
                          {variant.description && (
                            <p className="text-sm text-muted-foreground mt-1">
                              {variant.description}
                            </p>
                          )}
                        </div>
                        {isSelected && (
                          <Badge variant="default" className="ml-2">
                            Selected
                          </Badge>
                        )}
                      </div>
                      
                      <div className="flex flex-wrap gap-2">
                        {showSku && variant.sku && (
                          <Badge variant="outline" className="text-xs">
                            <Hash className="mr-1 h-3 w-3" />
                            {variant.sku}
                          </Badge>
                        )}
                        
                        {variant.attributes && Object.entries(variant.attributes).map(([key, value]) => {
                          const Icon = getAttributeIcon(key);
                          return (
                            <Badge key={key} variant="secondary" className="text-xs">
                              <Icon className="mr-1 h-3 w-3" />
                              {key}: {value}
                            </Badge>
                          );
                        })}
                      </div>
                      
                      <div className="flex items-center justify-between pt-2">
                        {showPrices && (
                          <ItemPrice
                            basePrice={variant.price}
                            cost={variant.cost}
                            showCost={false}
                            showDiscount={false}
                            size="sm"
                          />
                        )}
                        
                        {showStock && variant.track_stock && variant.current_stock !== undefined && (
                          <StockBadge
                            available={variant.current_stock}
                            minStock={variant.min_stock}
                            size="sm"
                          />
                        )}
                      </div>
                    </div>
                  </div>
                </CardContent>
              </label>
            </Card>
          );
        })}
      </RadioGroup>
      
      {variants.filter(v => !v.is_available).length > 0 && (
        <div className="space-y-2 opacity-60">
          <p className="text-sm text-muted-foreground">Unavailable options:</p>
          <div className="space-y-2">
            {variants.filter(v => !v.is_available).map((variant) => (
              <div key={variant.id} className="flex items-center gap-2 text-sm text-muted-foreground">
                <AlertCircle className="h-4 w-4" />
                <span className="line-through">{variant.name}</span>
                {variant.track_stock && variant.current_stock === 0 && (
                  <Badge variant="secondary" className="text-xs">Out of stock</Badge>
                )}
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export function VariantQuickSelect({
  variants,
  selectedVariantId,
  onVariantChange,
  className,
}: {
  variants: Variant[];
  selectedVariantId?: number;
  onVariantChange?: (variantId: number) => void;
  className?: string;
}) {
  return (
    <div className={cn('flex flex-wrap gap-2', className)}>
      {variants.filter(v => v.is_available).map((variant) => {
        const isSelected = selectedVariantId === variant.id;
        
        return (
          <Button
            key={variant.id}
            variant={isSelected ? "default" : "outline"}
            size="sm"
            onClick={() => onVariantChange?.(variant.id)}
            className={cn(
              "transition-all",
              isSelected && "shadow-sm"
            )}
          >
            {variant.name}
            {variant.price && (
              <span className="ml-1 text-xs opacity-75">
                {formatCurrency(variant.price)}
              </span>
            )}
          </Button>
        );
      })}
    </div>
  );
}