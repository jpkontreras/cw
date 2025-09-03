import React from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShoppingBag, PanelRightOpen, Trash2, Minus, Plus } from 'lucide-react';
import { OrderItem } from '@/modules/order/contexts/OrderContext';

interface CollapsedSidebarProps {
  orderItems: OrderItem[];
  getTotalItems: () => number;
  calculateTotal: () => number;
  setOrderItems: React.Dispatch<React.SetStateAction<OrderItem[]>>;
  processOrder: () => void;
  toggleCollapse: () => void;
}

export const CollapsedSidebar: React.FC<CollapsedSidebarProps> = ({
  orderItems,
  getTotalItems,
  calculateTotal,
  setOrderItems,
  processOrder,
  toggleCollapse,
}) => {
  const updateQuantity = (itemId: number, delta: number) => {
    setOrderItems(prev => prev.map(item => {
      if (item.id === itemId) {
        const newQuantity = item.quantity + delta;
        if (newQuantity <= 0) return null;
        return { ...item, quantity: newQuantity };
      }
      return item;
    }).filter(Boolean) as OrderItem[]);
  };

  const removeItem = (itemId: number) => {
    setOrderItems(prev => prev.filter(item => item.id !== itemId));
  };

  return (
    <div className="h-full flex flex-col bg-gray-50 dark:bg-gray-900">
      <div className="p-2 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <Button
          variant="ghost"
          size="sm"
          onClick={toggleCollapse}
          className="w-full justify-start gap-2 hover:bg-gray-100 dark:hover:bg-gray-700"
        >
          <PanelRightOpen className="h-4 w-4" />
          <ShoppingBag className="h-4 w-4" />
        </Button>
      </div>

      <div className="flex-1 overflow-y-auto p-2">
        <div className="space-y-2">
          {orderItems.map((item) => (
            <div
              key={item.id}
              className="bg-white dark:bg-gray-800 rounded-lg p-2 shadow-sm"
            >
              <div className="flex items-start gap-2">
                <div className="flex-1 min-w-0">
                  <p className="text-xs font-medium text-gray-900 dark:text-white truncate">
                    {item.name}
                  </p>
                  <p className="text-xs text-gray-500 dark:text-gray-400">
                    ${item.price.toLocaleString('es-CL')}
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    onClick={() => updateQuantity(item.id, -1)}
                  >
                    <Minus className="h-3 w-3" />
                  </Button>
                  <span className="text-xs font-medium w-4 text-center">
                    {item.quantity}
                  </span>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6"
                    onClick={() => updateQuantity(item.id, 1)}
                  >
                    <Plus className="h-3 w-3" />
                  </Button>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="h-6 w-6 text-red-500 hover:text-red-600"
                    onClick={() => removeItem(item.id)}
                  >
                    <Trash2 className="h-3 w-3" />
                  </Button>
                </div>
              </div>
              {item.notes && (
                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1 italic">
                  {item.notes}
                </p>
              )}
            </div>
          ))}
        </div>
      </div>

      <div className="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-2">
        <div className="flex justify-between items-center mb-2">
          <span className="text-xs font-medium text-gray-700 dark:text-gray-300">
            Total
          </span>
          <span className="text-sm font-bold text-green-600">
            ${calculateTotal().toLocaleString('es-CL')}
          </span>
        </div>
        <Button
          className="w-full"
          size="sm"
          onClick={processOrder}
          disabled={orderItems.length === 0}
        >
          Procesar
        </Button>
      </div>
    </div>
  );
};