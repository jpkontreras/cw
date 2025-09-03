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
      <div className="space-y-4">
        {items.map((item) => (
          <div key={item.id} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div className="flex items-start gap-6">
              <div className="w-24 h-24 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-lg flex items-center justify-center flex-shrink-0">
                <Package2 className="h-12 w-12 text-gray-400" />
              </div>
              
              <div className="flex-1">
                <div className="flex items-start justify-between">
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                      {item.name}
                    </h3>
                    <div className="flex items-center gap-4 mt-2">
                      <span className="text-sm text-gray-500">
                        {item.category || 'Sin categoría'}
                      </span>
                      {item.preparationTime && (
                        <>
                          <span className="text-gray-300">•</span>
                          <div className="flex items-center gap-1 text-sm text-gray-500">
                            <Clock className="h-3.5 w-3.5" />
                            {item.preparationTime} min
                          </div>
                        </>
                      )}
                    </div>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => onRemoveItem(item.id)}
                    className="h-8 w-8 ml-4 flex-shrink-0"
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
                
                <div className="mt-4 space-y-3">
                  {/* Notes Section */}
                  <div className="relative">
                    <Input
                      placeholder="Agregar nota especial (ej: sin cebolla, extra queso)..."
                      value={item.notes || ''}
                      onChange={(e) => onUpdateNotes(item.id, e.target.value)}
                      className="w-full pl-3 pr-10 h-10 bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-400"
                    />
                    {item.notes && (
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onUpdateNotes(item.id, '')}
                        className="absolute right-1 top-1 h-8 w-8"
                      >
                        <X className="h-3 w-3" />
                      </Button>
                    )}
                  </div>
                  
                  {/* Quantity and Price Section */}
                  <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-2 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-8 w-8 p-0"
                        onClick={() => onUpdateQuantity(item.id, -1)}
                      >
                        <Minus className="h-4 w-4" />
                      </Button>
                      <span className="text-sm font-semibold min-w-[40px] text-center">
                        {item.quantity}
                      </span>
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-8 w-8 p-0"
                        onClick={() => onUpdateQuantity(item.id, 1)}
                      >
                        <Plus className="h-4 w-4" />
                      </Button>
                    </div>
                    
                    <div className="text-right">
                      <div className="text-2xl font-bold text-gray-900 dark:text-white">
                        ${(item.price * item.quantity).toLocaleString('es-CL')}
                      </div>
                      <span className="text-xs text-gray-500">
                        ${item.price.toLocaleString('es-CL')} c/u
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    );
  }

  // Grid View
  return (
    <div className="grid gap-4 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
      {items.map((item) => (
        <div key={item.id} className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow">
          <div className="aspect-video bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 relative">
            <div className="flex items-center justify-center h-full">
              <Package2 className="h-16 w-16 text-gray-400" />
            </div>
            <Button
              variant="ghost"
              size="icon"
              onClick={() => onRemoveItem(item.id)}
              className="absolute top-2 right-2 bg-white/80 dark:bg-gray-800/80 backdrop-blur"
            >
              <X className="h-4 w-4" />
            </Button>
          </div>
          <div className="p-4">
            <h3 className="font-semibold text-lg text-gray-900 dark:text-white">
              {item.name}
            </h3>
            <p className="text-sm text-gray-500 mt-1">
              {item.category || 'Sin categoría'}
            </p>
            
            <div className="flex items-center justify-between mt-4">
              <div>
                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                  ${(item.price * item.quantity).toLocaleString('es-CL')}
                </div>
                <span className="text-xs text-gray-500">
                  ${item.price.toLocaleString('es-CL')} × {item.quantity}
                </span>
              </div>
              <div className="flex items-center gap-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 w-7 p-0"
                  onClick={() => onUpdateQuantity(item.id, -1)}
                >
                  <Minus className="h-3.5 w-3.5" />
                </Button>
                <span className="text-xs font-semibold min-w-[28px] text-center">
                  {item.quantity}
                </span>
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 w-7 p-0"
                  onClick={() => onUpdateQuantity(item.id, 1)}
                >
                  <Plus className="h-3.5 w-3.5" />
                </Button>
              </div>
            </div>
            
            <div className="relative mt-3">
              <Input
                placeholder="Agregar nota especial..."
                value={item.notes || ''}
                onChange={(e) => onUpdateNotes(item.id, e.target.value)}
                className="w-full h-9 text-xs bg-gray-50 dark:bg-gray-900 border-gray-300 dark:border-gray-600 focus:border-blue-500 dark:focus:border-blue-400 pr-8"
              />
              {item.notes && (
                <Button
                  variant="ghost"
                  size="icon"
                  onClick={() => onUpdateNotes(item.id, '')}
                  className="absolute right-0.5 top-0.5 h-8 w-8"
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