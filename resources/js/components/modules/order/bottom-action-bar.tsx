import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import type { CreateOrderRequest } from '@/types/modules/order';
import { formatCurrency } from '@/types/modules/order/utils';
import {
  ArrowRight,
  CheckCircle2,
  ChevronDown,
  ChevronUp,
  Clock,
  Info,
  MapPin,
  Minus,
  Package,
  Plus,
  ShoppingBag,
  Trash2,
  Truck,
  Utensils,
} from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface Props {
  data: CreateOrderRequest;
  items: Array<{
    id: number;
    name: string;
    price: number;
    category: string;
    modifiers?: Array<{
      id: number;
      name: string;
      price: number;
    }>;
  }>;
  locations: Array<{ id: number; name: string }>;
  currentStep: 'menu' | 'details';
  subtotal: number;
  tax: number;
  total: number;
  canProceedToDetails: boolean;
  canPlaceOrder: boolean;
  processing?: boolean;
  onContinueToDetails: () => void;
  onPlaceOrder: (e: FormEvent) => void;
  onUpdateQuantity: (index: number, quantity: number) => void;
  onRemoveItem: (index: number) => void;
  selectedModifiers?: Record<number, number[]>;
}

export function BottomActionBar({
  data,
  items,
  locations,
  currentStep,
  subtotal,
  tax,
  total,
  canProceedToDetails,
  canPlaceOrder,
  processing = false,
  onContinueToDetails,
  onPlaceOrder,
  onUpdateQuantity,
  onRemoveItem,
  selectedModifiers = {},
}: Props) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [showBarInitially, setShowBarInitially] = useState(false);

  const itemCount = data.items.reduce((sum, item) => sum + item.quantity, 0);
  const hasItems = itemCount > 0;

  // Animate in the bar when first item is added
  useEffect(() => {
    if (hasItems && !showBarInitially) {
      setShowBarInitially(true);
    }
  }, [hasItems, showBarInitially]);

  if (!hasItems && currentStep === 'menu') {
    return null;
  }

  // Group items by category for display
  const categorizedItems = data.items.reduce(
    (acc, orderItem, index) => {
      const menuItem = items.find((i) => i.id === orderItem.item_id);
      if (!menuItem) return acc;

      const category = menuItem.category || 'Other';
      if (!acc[category]) acc[category] = [];
      acc[category].push({ orderItem, menuItem, index });
      return acc;
    },
    {} as Record<string, Array<{ orderItem: any; menuItem: any; index: number }>>,
  );

  const getOrderTypeIcon = (type: string) => {
    switch (type) {
      case 'dine_in':
        return Utensils;
      case 'takeout':
        return Package;
      case 'delivery':
        return Truck;
      default:
        return ShoppingBag;
    }
  };

  const OrderTypeIcon = getOrderTypeIcon(data.type);
  const currentLocation = locations.find((l) => l.id === data.locationId);

  return (
    <div
      className={cn(
        'fixed right-0 bottom-0 left-0 z-40 transform transition-all duration-300 ease-out',
        showBarInitially ? 'translate-y-0' : 'translate-y-full',
      )}
    >
      {/* Expanded content */}
      <div
        className={cn(
          'absolute right-0 bottom-full left-0 border-t border-gray-200 bg-white shadow-2xl transition-all duration-300 ease-out',
          isExpanded ? 'translate-y-0' : 'translate-y-full',
        )}
      >
        <div className="flex max-h-[70vh] flex-col">
          {/* Header */}
          <div className="flex items-center justify-between border-b bg-gray-50/50 px-4 py-4 sm:px-6">
            <h3 className="flex items-center gap-2 text-lg font-semibold">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10">
                <ShoppingBag className="h-4 w-4 text-primary" />
              </div>
              Order Summary
            </h3>
            <Button
              variant="ghost"
              size="icon"
              className="h-9 w-9 rounded-full transition-colors hover:bg-gray-200"
              onClick={() => setIsExpanded(false)}
            >
              <ChevronDown className="h-5 w-5" />
            </Button>
          </div>

          {/* Items list */}
          <ScrollArea className="flex-1 bg-gray-50/30">
            <div className="space-y-6 p-4 sm:px-6">
              {Object.entries(categorizedItems).map(([category, categoryItems]) => (
                <div key={category}>
                  <div className="mb-3 flex items-center gap-2">
                    <div className="h-px flex-1 bg-gray-200" />
                    <span className="px-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">{category}</span>
                    <div className="h-px flex-1 bg-gray-200" />
                  </div>
                  <div className="space-y-2">
                    {categoryItems.map(({ orderItem, menuItem, index }) => {
                      const itemModifiers = selectedModifiers[orderItem.item_id] || [];
                      const modifierPrice = itemModifiers.reduce((sum, modId) => {
                        const mod = menuItem.modifiers?.find((m) => m.id === modId);
                        return sum + (mod?.price || 0);
                      }, 0);
                      const itemTotal = (menuItem.price + modifierPrice) * orderItem.quantity;

                      return (
                        <div
                          key={`${orderItem.item_id}-${index}`}
                          className="rounded-xl border border-gray-200 bg-white p-4 transition-shadow hover:shadow-sm"
                        >
                          <div className="flex items-start gap-3">
                            {/* Quantity controls */}
                            <div className="flex items-center gap-1 rounded-lg bg-gray-100 p-1">
                              <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="h-7 w-7 hover:bg-white"
                                onClick={() => onUpdateQuantity(index, orderItem.quantity - 1)}
                              >
                                <Minus className="h-3 w-3" />
                              </Button>
                              <span className="w-8 text-center text-sm font-semibold">{orderItem.quantity}</span>
                              <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="h-7 w-7 hover:bg-white"
                                onClick={() => onUpdateQuantity(index, orderItem.quantity + 1)}
                              >
                                <Plus className="h-3 w-3" />
                              </Button>
                            </div>

                            {/* Item details */}
                            <div className="min-w-0 flex-1">
                              <div className="truncate font-medium text-gray-900">{menuItem.name}</div>
                              {itemModifiers.length > 0 && (
                                <div className="mt-1 flex items-center gap-1">
                                  <div className="h-1 w-1 rounded-full bg-primary" />
                                  <div className="text-xs text-gray-600">
                                    {itemModifiers
                                      .map((modId) => {
                                        const mod = menuItem.modifiers?.find((m) => m.id === modId);
                                        return mod?.name;
                                      })
                                      .filter(Boolean)
                                      .join(', ')}
                                  </div>
                                </div>
                              )}
                              {orderItem.notes && (
                                <div className="mt-1 flex items-start gap-1">
                                  <Info className="mt-0.5 h-3 w-3 flex-shrink-0 text-gray-400" />
                                  <div className="text-xs text-gray-500 italic">{orderItem.notes}</div>
                                </div>
                              )}
                            </div>

                            {/* Price and remove */}
                            <div className="flex flex-shrink-0 items-center gap-2">
                              <span className="font-semibold text-gray-900 tabular-nums">{formatCurrency(itemTotal)}</span>
                              <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="h-8 w-8 rounded-full text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600"
                                onClick={() => onRemoveItem(index)}
                              >
                                <Trash2 className="h-4 w-4" />
                              </Button>
                            </div>
                          </div>
                        </div>
                      );
                    })}
                  </div>
                </div>
              ))}
            </div>
          </ScrollArea>

          {/* Totals */}
          <div className="space-y-3 border-t bg-white p-4 sm:px-6">
            <div className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Subtotal</span>
                <span className="font-medium tabular-nums">{formatCurrency(subtotal)}</span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="flex items-center gap-1 text-gray-600">
                  Tax
                  <span className="text-xs text-gray-500">(19%)</span>
                </span>
                <span className="font-medium tabular-nums">{formatCurrency(tax)}</span>
              </div>
            </div>
            <div className="h-px bg-gray-200" />
            <div className="flex items-baseline justify-between">
              <span className="font-semibold text-gray-900">Total Amount</span>
              <span className="bg-gradient-to-r from-primary to-primary/80 bg-clip-text text-2xl font-bold text-transparent tabular-nums">
                {formatCurrency(total)}
              </span>
            </div>

            {/* Estimated time */}
            <div className="pt-2">
              <div className="flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2">
                <Clock className="h-4 w-4 text-blue-600" />
                <span className="text-sm text-blue-900">
                  Estimated preparation: <span className="font-semibold">15-20 minutes</span>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Main bar */}
      <div className="border-t border-gray-200 bg-white shadow-2xl">
        {/* Animated progress indicator */}
        {processing && (
          <div className="absolute top-0 right-0 left-0 h-1 bg-gray-200">
            <div className="h-full animate-pulse bg-primary" style={{ width: '60%' }} />
          </div>
        )}

        <div className="px-4 py-4 sm:px-6">
          <div className="flex items-center justify-between gap-4">
            {/* Left side - Summary info */}
            <button
              type="button"
              onClick={() => setIsExpanded(!isExpanded)}
              className="group flex flex-1 items-center gap-3 text-left transition-all hover:scale-[1.02]"
            >
              <div className="flex flex-1 items-center gap-3">
                {/* Left: Order info */}
                <div className="flex flex-1 items-center gap-3">
                  <div className="relative">
                    <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-primary/20 to-primary/10 shadow-sm transition-shadow group-hover:shadow-md">
                      <ShoppingBag className="h-7 w-7 text-primary" />
                      {hasItems && (
                        <Badge
                          className="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center border-2 border-white bg-gradient-to-r from-primary to-primary/80 p-0 text-xs shadow-md"
                          variant="default"
                        >
                          {itemCount}
                        </Badge>
                      )}
                    </div>
                  </div>

                  <div className="flex-1 text-left">
                    <div className="flex items-center gap-1 text-xs font-semibold tracking-wider text-gray-500 uppercase">
                      <span>Order Total</span>
                      {itemCount > 0 && (
                        <span className="text-primary">
                          • {itemCount} {itemCount === 1 ? 'item' : 'items'}
                        </span>
                      )}
                    </div>
                    <div className="mt-0.5 flex items-baseline gap-2">
                      <span className="bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-3xl font-bold text-transparent">
                        {formatCurrency(total)}
                      </span>
                      {tax > 0 && <span className="text-xs text-gray-500">incl. tax</span>}
                    </div>
                  </div>
                </div>

                {/* Center: Location and type info */}
                <div className="hidden items-center gap-3 lg:flex">
                  <div className="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <MapPin className="h-3.5 w-3.5 text-gray-600" />
                    <span className="text-xs font-medium text-gray-700">{currentLocation?.name || 'Select location'}</span>
                  </div>
                  <div className="flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5">
                    <OrderTypeIcon className="h-3.5 w-3.5 text-gray-600" />
                    <span className="text-xs font-medium text-gray-700">
                      {data.type === 'dine_in' ? 'Dine In' : data.type === 'takeout' ? 'Takeout' : 'Delivery'}
                    </span>
                  </div>
                </div>

                {/* Right: Expand button and time */}
                <div className="flex items-center gap-2">
                  {hasItems && (
                    <div className="hidden items-center gap-1 rounded-full bg-gray-100 px-3 py-1.5 sm:flex">
                      <Clock className="h-3.5 w-3.5 text-gray-600" />
                      <span className="text-xs font-medium text-gray-600">15-20 min</span>
                    </div>
                  )}
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 transition-colors group-hover:bg-gray-200">
                    <ChevronUp className={cn('h-5 w-5 text-gray-600 transition-transform duration-200', isExpanded ? 'rotate-180' : '')} />
                  </div>
                </div>
              </div>
            </button>

            {/* Right side - Action button */}
            <div className="flex-shrink-0">
              {currentStep === 'menu' ? (
                <Button
                  type="button"
                  size="lg"
                  onClick={onContinueToDetails}
                  disabled={!canProceedToDetails}
                  className={cn(
                    'relative overflow-hidden px-6 py-3 text-base font-semibold transition-all sm:px-8',
                    'bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80',
                    'text-white shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50',
                    'transform hover:scale-105 active:scale-100',
                  )}
                >
                  <span className="relative z-10 flex items-center">
                    <span className="hidden sm:inline">Continue to&nbsp;</span>
                    <span>Checkout</span>
                    <ArrowRight className="ml-2 h-5 w-5 transition-transform group-hover:translate-x-1" />
                  </span>
                </Button>
              ) : (
                <Button
                  type="button"
                  size="lg"
                  onClick={(e) => onPlaceOrder(e as any)}
                  disabled={!canPlaceOrder || processing}
                  className={cn(
                    'relative overflow-hidden px-6 py-3 text-base font-semibold transition-all sm:px-8',
                    'bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400',
                    'text-white shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50',
                    'transform hover:scale-105 active:scale-100',
                  )}
                >
                  {processing ? (
                    <span className="flex items-center">
                      <div className="mr-2 h-5 w-5 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                      Processing...
                    </span>
                  ) : (
                    <span className="relative z-10 flex items-center">
                      <CheckCircle2 className="mr-2 h-5 w-5" />
                      <span className="hidden sm:inline">Place Order •</span>
                      <span className="ml-1 font-bold">{formatCurrency(total)}</span>
                    </span>
                  )}
                </Button>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
