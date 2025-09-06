import React from 'react';
import { ShoppingCart, Plus, Minus, Trash2, ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';

interface CartItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  notes?: string;
}

interface CartPopoverProps {
  items: CartItem[];
  onUpdateQuantity?: (itemId: number, delta: number) => void;
  onRemoveItem?: (itemId: number) => void;
  onGoToCheckout?: () => void;
  className?: string;
}

export const CartPopover: React.FC<CartPopoverProps> = ({
  items,
  onUpdateQuantity,
  onRemoveItem,
  onGoToCheckout,
  className
}) => {
  const totalItems = items.reduce((sum, item) => sum + item.quantity, 0);
  // Prices are fetched fresh from backend items table for display
  const totalPrice = items.reduce((sum, item) => sum + ((item.price || 0) * item.quantity), 0);

  if (items.length === 0) {
    return null;
  }

  return (
    <div className="animate-in fade-in slide-in-from-left-2 duration-300">
      <Popover>
        <PopoverTrigger asChild>
          <Button
            variant="outline"
            size="icon"
            className={cn(
              "relative h-12 w-12 rounded-full transition-all hover:scale-105",
              "bg-white border-2 border-primary/20 hover:border-primary/40",
              className
            )}
          >
            <ShoppingCart className="h-5 w-5" />
            {totalItems > 0 && (
              <span className="absolute -top-1 -right-1 h-5 w-5 rounded-full bg-primary text-primary-foreground text-xs font-bold flex items-center justify-center">
                {totalItems > 99 ? '99+' : totalItems}
              </span>
            )}
          </Button>
        </PopoverTrigger>
      <PopoverContent 
        className="w-96 p-0" 
        align="end"
        sideOffset={5}
      >
        {/* Header */}
        <div className="p-4 border-b">
          <div className="flex items-center justify-between">
            <div>
              <h3 className="font-semibold text-lg">Tu Carrito</h3>
              <p className="text-sm text-muted-foreground">
                {totalItems} {totalItems === 1 ? 'producto' : 'productos'}
              </p>
            </div>
            <Badge variant="secondary" className="text-lg px-3 py-1">
              ${totalPrice.toLocaleString('es-CL')}
            </Badge>
          </div>
        </div>

        {/* Items List */}
        <ScrollArea className="h-[400px] p-4">
          <div className="space-y-3">
            {items.map((item, index) => (
              <div key={item.id}>
                <div className="flex items-start gap-3 group">
                  <div className="flex-1">
                    <h4 className="font-medium text-sm leading-tight">
                      {item.name}
                    </h4>
                    <p className="text-sm text-muted-foreground mt-1">
                      ${(item.price || 0).toLocaleString('es-CL')} c/u
                    </p>
                    {item.notes && (
                      <p className="text-xs text-muted-foreground italic mt-1">
                        {item.notes}
                      </p>
                    )}
                  </div>
                  
                  <div className="flex items-center gap-1">
                    <Button
                      size="icon"
                      variant="ghost"
                      className="h-7 w-7 hover:bg-destructive/10"
                      onClick={() => onUpdateQuantity?.(item.id, -1)}
                    >
                      <Minus className="h-3 w-3" />
                    </Button>
                    <span className="w-8 text-center font-semibold text-sm">
                      {item.quantity}
                    </span>
                    <Button
                      size="icon"
                      variant="ghost"
                      className="h-7 w-7"
                      onClick={() => onUpdateQuantity?.(item.id, 1)}
                    >
                      <Plus className="h-3 w-3" />
                    </Button>
                    <Button
                      size="icon"
                      variant="ghost"
                      className="h-7 w-7 ml-1 opacity-0 group-hover:opacity-100 transition-opacity hover:bg-destructive/10 hover:text-destructive"
                      onClick={() => onRemoveItem?.(item.id)}
                    >
                      <Trash2 className="h-3 w-3" />
                    </Button>
                  </div>
                </div>
                
                <div className="flex justify-end mt-1">
                  <span className="text-sm font-semibold">
                    ${(item.price * item.quantity).toLocaleString('es-CL')}
                  </span>
                </div>
                
                {index < items.length - 1 && <Separator className="my-3" />}
              </div>
            ))}
          </div>
        </ScrollArea>

        {/* Footer */}
        <div className="p-4 border-t bg-muted/30">
          <div className="flex items-center justify-between mb-3">
            <span className="font-semibold">Total</span>
            <span className="text-xl font-bold">
              ${totalPrice.toLocaleString('es-CL')}
            </span>
          </div>
          <Button 
            className="w-full" 
            size="lg"
            onClick={onGoToCheckout}
          >
            Ir al Checkout
            <ArrowRight className="ml-2 h-4 w-4" />
          </Button>
        </div>
      </PopoverContent>
    </Popover>
    </div>
  );
};