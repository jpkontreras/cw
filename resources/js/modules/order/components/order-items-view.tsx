import React from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Package2, X, Minus, Plus, Clock } from 'lucide-react';

interface OrderItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  category?: string;
  image?: string;
  description?: string;
  preparationTime?: number;
  modifiers?: Array<{ id: number; name: string; price: number }>;
  notes?: string;
}

type ViewMode = 'list' | 'grid';

interface OrderItemsViewProps {
  items: OrderItem[];
  viewMode: ViewMode;
  onUpdateQuantity: (itemId: number, delta: number) => void;
  onUpdateNotes: (itemId: number, notes: string) => void;
  onRemoveItem: (itemId: number) => void;
}

export const OrderItemsView: React.FC<OrderItemsViewProps> = ({
  items,
  viewMode,
  onUpdateQuantity,
  onUpdateNotes,
  onRemoveItem,
}) => {
  if (viewMode === 'list') {
    return (
      <div className="space-y-2">
        {items.map((item) => (
          <div key={item.id} className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:shadow-sm transition-shadow">
            <div className="flex gap-3">
              {/* Left Side - Image and Quantity */}
              <div className="flex items-start gap-3">
                {/* Product Image */}
                <div className="w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-md flex items-center justify-center flex-shrink-0">
                  <Package2 className="h-7 w-7 text-gray-400" />
                </div>
                
                {/* Product Info */}
                <div className="flex-1">
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <h3 className="text-sm font-semibold text-gray-900 dark:text-white leading-tight">
                        {item.name}
                      </h3>
                      <div className="flex items-center gap-3 mt-1">
                        <span className="text-xs text-gray-500">
                          {item.category || 'Uncategorized'}
                        </span>
                        {item.preparationTime && (
                          <>
                            <span className="text-gray-400">•</span>
                            <div className="flex items-center gap-1 text-xs text-gray-500">
                              <Clock className="h-3 w-3" />
                              <span>{item.preparationTime} min</span>
                            </div>
                          </>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              {/* Right Side - Price and Actions */}
              <div className="flex items-start gap-2 ml-auto">
                <div className="text-right">
                  <div className="text-base font-bold text-gray-900 dark:text-white">
                    ${(item.price * item.quantity).toLocaleString('es-CL')}
                  </div>
                  <div className="flex items-center gap-2 mt-1">
                    <span className="text-xs text-gray-500">
                      ${item.price.toLocaleString('es-CL')}
                    </span>
                    <span className="text-xs text-gray-400">c/u</span>
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => onRemoveItem(item.id)}
                  className="h-6 w-6 -mr-1"
                >
                  <X className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
            
            {/* Bottom Section - Quantity and Notes */}
            <div className="mt-3 flex gap-2">
              {/* Quantity Controls */}
              <div className="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-md px-1 py-0.5">
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-6 w-6 p-0"
                  onClick={() => onUpdateQuantity(item.id, -1)}
                >
                  <Minus className="h-3 w-3" />
                </Button>
                <span className="text-sm font-semibold min-w-[24px] text-center">
                  {item.quantity}
                </span>
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-6 w-6 p-0"
                  onClick={() => onUpdateQuantity(item.id, 1)}
                >
                  <Plus className="h-3 w-3" />
                </Button>
              </div>
              
              {/* Notes Input */}
              <div className="relative flex-1">
                <Input
                  placeholder="Agregar nota especial (ej: sin cebolla, extra queso)..."
                  value={item.notes || ''}
                  onChange={(e) => onUpdateNotes(item.id, e.target.value)}
                  className="w-full pl-2.5 pr-8 h-8 text-xs bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700"
                />
                {item.notes && (
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onUpdateNotes(item.id, '')}
                    className="absolute right-0.5 top-0.5 h-7 w-7"
                  >
                    <X className="h-2.5 w-2.5" />
                  </Button>
                )}
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  // Grid View
  return (
    <div className="grid gap-3 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((item) => (
        <div key={item.id} className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow">
          <div className="relative">
            <div className="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700">
              <div className="flex items-center justify-center h-full">
                <Package2 className="h-14 w-14 text-gray-400" />
              </div>
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => onRemoveItem(item.id)}
              className="absolute top-2 right-2 bg-white/90 dark:bg-gray-800/90 backdrop-blur h-7 w-7"
            >
              <X className="h-3.5 w-3.5" />
            </Button>
            {/* Quantity Controls Below Image */}
            <div className="absolute bottom-2 left-1/2 -translate-x-1/2 flex items-center gap-1 bg-white/90 dark:bg-gray-800/90 backdrop-blur rounded-lg p-1">
              <Button
                size="sm"
                variant="ghost"
                className="h-7 w-7 p-0"
                onClick={() => onUpdateQuantity(item.id, -1)}
              >
                <Minus className="h-3 w-3" />
              </Button>
              <span className="text-sm font-semibold min-w-[32px] text-center">
                {item.quantity}
              </span>
              <Button
                size="sm"
                variant="ghost"
                className="h-7 w-7 p-0"
                onClick={() => onUpdateQuantity(item.id, 1)}
              >
                <Plus className="h-3 w-3" />
              </Button>
            </div>
          </div>
          <div className="p-3">
            <h3 className="font-semibold text-base text-gray-900 dark:text-white">
              {item.name}
            </h3>
            <p className="text-xs text-gray-500 mt-0.5">
              {item.category || 'Sin categoría'}
            </p>
            
            <div className="flex items-center justify-between mt-2">
              <div className="text-lg font-bold text-gray-900 dark:text-white">
                ${(item.price * item.quantity).toLocaleString('es-CL')}
              </div>
              <span className="text-xs text-gray-500">
                ${item.price.toLocaleString('es-CL')} c/u
              </span>
            </div>
            
            {/* Notes Section at Bottom */}
            <div className="relative mt-2">
              <Input
                placeholder="Agregar nota especial..."
                value={item.notes || ''}
                onChange={(e) => onUpdateNotes(item.id, e.target.value)}
                className="w-full h-8 text-xs bg-gray-50 dark:bg-gray-900 border-gray-200 dark:border-gray-700 pr-8"
              />
              {item.notes && (
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => onUpdateNotes(item.id, '')}
                  className="absolute right-0.5 top-0.5 h-7 w-7"
                >
                  <X className="h-3 w-3" />
                </Button>
              )}
            </div>
          </div>
        </div>
      ))}
    </div>
  );
};