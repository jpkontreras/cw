import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import type { CreateOrderRequest } from '@/types/modules/order';
import { formatCurrency } from '@/types/modules/order/utils';
import {
  ArrowRight,
  CheckCircle2,
  ChevronUp,
  MapPin,
  Minus,
  Plus,
  ShoppingBag,
  Trash2,
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
}: Props) {
  const [isExpanded, setIsExpanded] = useState(false);
  const [showBarInitially, setShowBarInitially] = useState(false);

  const itemCount = data.items.reduce((sum: number, item: any) => sum + item.quantity, 0);
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

  const currentLocation = locations.find((l) => l.id === data.locationId);

  return (
    <div
      className={cn(
        'sticky bottom-0 z-50 bg-white border-t border-gray-200',
        showBarInitially ? 'translate-y-0' : 'translate-y-full',
        'transform transition-all duration-300 ease-out'
      )}
    >
      <div className="relative">
        <div className="px-4 sm:px-6 lg:px-8 py-4">
          <div className="flex items-center justify-between">
            {/* Left side - Order info */}
            <div className="flex items-center gap-4">
              <button
                type="button"
                onClick={() => setIsExpanded(!isExpanded)}
                className="flex items-center gap-2 text-left"
              >
                <ShoppingBag className="h-5 w-5 text-gray-600" />
                <div className="flex items-center gap-2">
                  <span className="text-lg font-bold text-gray-900">{formatCurrency(total)}</span>
                  <span className="text-sm text-gray-600">({itemCount} items)</span>
                </div>
                <ChevronUp className={cn(
                  'h-4 w-4 text-gray-500 transition-transform',
                  isExpanded ? 'rotate-180' : ''
                )} />
              </button>

              <div className="hidden md:flex items-center gap-4 text-sm text-gray-600 border-l pl-4">
                <div className="flex items-center gap-1.5">
                  <MapPin className="h-4 w-4" />
                  <span>{currentLocation?.name || 'Main Branch'}</span>
                </div>
              </div>
            </div>

            {/* Right side - Action button */}
            <div>
              {currentStep === 'menu' ? (
                <Button
                  onClick={onContinueToDetails}
                  disabled={!canProceedToDetails}
                  className="bg-gray-900 hover:bg-gray-800 text-white"
                >
                  Continue to Checkout
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
              ) : (
                <Button
                  onClick={(e) => onPlaceOrder(e as any)}
                  disabled={!canPlaceOrder || processing}
                  className="bg-green-600 hover:bg-green-700 text-white"
                >
                  {processing ? (
                    <>
                      <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                      Processing...
                    </>
                  ) : (
                    <>
                      <CheckCircle2 className="mr-2 h-4 w-4" />
                      Place Order
                    </>
                  )}
                </Button>
              )}
            </div>
          </div>
        </div>

        {/* Expanded order summary */}
        {isExpanded && (
          <div className="absolute bottom-full left-0 right-0 border-t border-gray-200 bg-white">
            <div className="px-4 sm:px-6 lg:px-8 py-4 max-h-[60vh] overflow-auto">
              {/* Order items */}
              {data.items.map((orderItem: any, index: number) => {
                const menuItem = items.find((i) => i.id === orderItem.item_id);
                if (!menuItem) return null;

                return (
                  <div
                    key={`${orderItem.item_id}-${index}`}
                    className="flex items-center justify-between py-3 border-b border-gray-100 last:border-0"
                  >
                    <div className="flex items-center gap-3">
                      <div className="flex items-center">
                        <button
                          type="button"
                          className="h-8 w-8 rounded-l bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                          onClick={() => onUpdateQuantity(index, orderItem.quantity - 1)}
                        >
                          <Minus className="h-3 w-3" />
                        </button>
                        <span className="h-8 px-3 bg-gray-50 flex items-center justify-center text-sm font-medium">
                          {orderItem.quantity}
                        </span>
                        <button
                          type="button"
                          className="h-8 w-8 rounded-r bg-gray-100 hover:bg-gray-200 flex items-center justify-center"
                          onClick={() => onUpdateQuantity(index, orderItem.quantity + 1)}
                        >
                          <Plus className="h-3 w-3" />
                        </button>
                      </div>
                      <div>
                        <div className="font-medium text-gray-900">{menuItem.name}</div>
                        {orderItem.notes && (
                          <div className="text-sm text-gray-500">{orderItem.notes}</div>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center gap-3">
                      <span className="font-medium text-gray-900">
                        {formatCurrency(menuItem.price * orderItem.quantity)}
                      </span>
                      <button
                        type="button"
                        className="text-gray-400 hover:text-red-600"
                        onClick={() => onRemoveItem(index)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                );
              })}

              {/* Totals */}
              <div className="mt-4 pt-4 border-t border-gray-200">
                <div className="space-y-2">
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Subtotal</span>
                    <span>{formatCurrency(subtotal)}</span>
                  </div>
                  <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Tax (19%)</span>
                    <span>{formatCurrency(tax)}</span>
                  </div>
                  <div className="flex justify-between font-semibold">
                    <span>Total</span>
                    <span>{formatCurrency(total)}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}