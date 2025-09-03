import React from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { PanelRightClose, Home, ShoppingBag, Car } from 'lucide-react';
import { cn } from '@/lib/utils';
import { CustomerInfo, OrderItem } from '@/modules/order/contexts/OrderContext';

interface ExpandedSidebarProps {
  customerInfo: CustomerInfo;
  setCustomerInfo: React.Dispatch<React.SetStateAction<CustomerInfo>>;
  orderItems: OrderItem[];
  setOrderItems: React.Dispatch<React.SetStateAction<OrderItem[]>>;
  toggleCollapse: () => void;
  processOrder: () => void;
  calculateSubtotal: () => number;
  calculateTax: () => number;
  calculateTotal: () => number;
}

export const ExpandedSidebar: React.FC<ExpandedSidebarProps> = ({
  customerInfo,
  setCustomerInfo,
  orderItems,
  setOrderItems,
  toggleCollapse,
  processOrder,
  calculateSubtotal,
  calculateTax,
  calculateTotal,
}) => {
  return (
    <div className="h-full flex flex-col bg-gray-50 dark:bg-gray-900/50">
      {/* Fixed Header */}
      <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex-shrink-0">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Checkout</h2>
        <Button
          variant="ghost"
          size="icon"
          onClick={toggleCollapse}
          className="h-8 w-8 hover:bg-gray-200 dark:hover:bg-gray-800"
        >
          <PanelRightClose className="h-5 w-5" />
        </Button>
      </div>
      
      {/* Content that expands to fill available space */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Order Type Section */}
        <div className="p-4 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Tipo de Orden</h3>
          <RadioGroup 
            value={customerInfo.orderType} 
            onValueChange={(value) => setCustomerInfo({ ...customerInfo, orderType: value as 'dine_in' | 'takeout' | 'delivery' })}
          >
            <div className="grid grid-cols-3 gap-2">
              <Label className="cursor-pointer">
                <RadioGroupItem value="dine_in" className="sr-only" />
                <div className={cn(
                  "flex flex-col items-center gap-1 p-3 rounded-lg border-2 transition-all",
                  customerInfo.orderType === 'dine_in' 
                    ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                    : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                )}>
                  <Home className="h-5 w-5" />
                  <span className="text-xs font-medium">Local</span>
                </div>
              </Label>
              <Label className="cursor-pointer">
                <RadioGroupItem value="takeout" className="sr-only" />
                <div className={cn(
                  "flex flex-col items-center gap-1 p-3 rounded-lg border-2 transition-all",
                  customerInfo.orderType === 'takeout' 
                    ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                    : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                )}>
                  <ShoppingBag className="h-5 w-5" />
                  <span className="text-xs font-medium">Llevar</span>
                </div>
              </Label>
              <Label className="cursor-pointer">
                <RadioGroupItem value="delivery" className="sr-only" />
                <div className={cn(
                  "flex flex-col items-center gap-1 p-3 rounded-lg border-2 transition-all",
                  customerInfo.orderType === 'delivery' 
                    ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                    : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                )}>
                  <Car className="h-5 w-5" />
                  <span className="text-xs font-medium">Delivery</span>
                </div>
              </Label>
            </div>
          </RadioGroup>
        </div>

        {/* Customer Info - Fills remaining space */}
        <div className="flex-1 bg-white dark:bg-gray-900 overflow-y-auto">
          <div className="p-4">
            <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Información del Cliente</h3>
            <div className="space-y-3 pb-4">
              <Input
                placeholder="Nombre del cliente"
                value={customerInfo.name}
                onChange={(e) => setCustomerInfo({ ...customerInfo, name: e.target.value })}
                className="h-10"
              />
              <Input
                placeholder="Teléfono"
                value={customerInfo.phone}
                onChange={(e) => setCustomerInfo({ ...customerInfo, phone: e.target.value })}
                className="h-10"
              />
              {customerInfo.orderType === 'dine_in' && (
                <Input
                  placeholder="Número de mesa"
                  value={customerInfo.tableNumber || ''}
                  onChange={(e) => setCustomerInfo({ ...customerInfo, tableNumber: e.target.value })}
                  className="h-10"
                />
              )}
              {customerInfo.orderType === 'delivery' && (
                <Input
                  placeholder="Dirección de entrega"
                  value={customerInfo.address || ''}
                  onChange={(e) => setCustomerInfo({ ...customerInfo, address: e.target.value })}
                  className="h-10"
                />
              )}
              
              {/* Special Instructions */}
              <div className="pt-2">
                <label className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1 block">
                  Instrucciones Especiales
                </label>
                <textarea
                  placeholder="Ej: Sin cebolla, extra picante, etc."
                  value={customerInfo.specialInstructions || ''}
                  onChange={(e) => setCustomerInfo({ ...customerInfo, specialInstructions: e.target.value })}
                  className="w-full h-20 px-3 py-2 text-sm border border-gray-200 dark:border-gray-700 rounded-lg resize-none focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-800"
                />
              </div>
              
              {/* Payment Method */}
              <div className="pt-2">
                <label className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2 block">
                  Método de Pago
                </label>
                <RadioGroup 
                  value={customerInfo.paymentMethod || 'cash'} 
                  onValueChange={(value) => setCustomerInfo({ ...customerInfo, paymentMethod: value as 'cash' | 'card' | 'transfer' })}
                >
                  <div className="grid grid-cols-3 gap-2">
                    <Label className="cursor-pointer">
                      <RadioGroupItem value="cash" className="sr-only" />
                      <div className={cn(
                        "flex items-center justify-center p-2 rounded-lg border-2 transition-all text-xs font-medium",
                        (customerInfo.paymentMethod || 'cash') === 'cash' 
                          ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                          : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                      )}>
                        Efectivo
                      </div>
                    </Label>
                    <Label className="cursor-pointer">
                      <RadioGroupItem value="card" className="sr-only" />
                      <div className={cn(
                        "flex items-center justify-center p-2 rounded-lg border-2 transition-all text-xs font-medium",
                        customerInfo.paymentMethod === 'card' 
                          ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                          : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                      )}>
                        Tarjeta
                      </div>
                    </Label>
                    <Label className="cursor-pointer">
                      <RadioGroupItem value="transfer" className="sr-only" />
                      <div className={cn(
                        "flex items-center justify-center p-2 rounded-lg border-2 transition-all text-xs font-medium",
                        customerInfo.paymentMethod === 'transfer' 
                          ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                          : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                      )}>
                        Transferencia
                      </div>
                    </Label>
                  </div>
                </RadioGroup>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Fixed Footer with Totals - Outside scrollable area */}
      <div className="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 flex-shrink-0">
        <div className="p-4 space-y-2">
          <div className="flex justify-between text-sm">
            <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
            <span className="font-medium">${calculateSubtotal().toLocaleString('es-CL')}</span>
          </div>
          <div className="flex justify-between text-sm">
            <span className="text-gray-600 dark:text-gray-400">IVA (19%)</span>
            <span className="font-medium">${calculateTax().toLocaleString('es-CL')}</span>
          </div>
          <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
            <span>Total</span>
            <span className="text-green-600">${calculateTotal().toLocaleString('es-CL')}</span>
          </div>
        </div>
        
        <div className="px-4 pb-4 space-y-2">
          <Button
            className="w-full"
            size="lg"
            onClick={processOrder}
            disabled={orderItems.length === 0}
          >
            Procesar Orden
          </Button>
          <Button
            variant="outline"
            className="w-full"
            size="lg"
            onClick={() => setOrderItems([])}
            disabled={orderItems.length === 0}
          >
            Limpiar
          </Button>
        </div>
      </div>
    </div>
  );
};