import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { formatOrderNumber, getStatusIndicatorColor, isOrderInSafeState, getStatusLabel } from '../utils/utils';
import type { Order, OrderStatus } from '../types/types';
import { Circle, AlertCircle, CheckCircle } from 'lucide-react';

interface OrderNumberDisplayProps {
  order: Order;
  showPopover?: boolean;
  className?: string;
  size?: 'sm' | 'md' | 'lg';
}

export function OrderNumberDisplay({ 
  order, 
  showPopover = true, 
  className = '',
  size = 'md'
}: OrderNumberDisplayProps) {
  const isInSafeState = isOrderInSafeState(order.status);
  const indicatorColor = getStatusIndicatorColor(order.status);
  
  const sizeClasses = {
    sm: 'text-sm',
    md: 'text-base',
    lg: 'text-lg font-semibold'
  };
  
  const iconSizes = {
    sm: 'h-3 w-3',
    md: 'h-4 w-4',
    lg: 'h-5 w-5'
  };
  
  const getStatusDescription = (status: OrderStatus): string => {
    const descriptions: Record<OrderStatus, string> = {
      draft: 'Order is being created and has not been submitted',
      started: 'Order has been started but items are still being added',
      items_added: 'Items have been added, awaiting validation',
      items_validated: 'Items validated, calculating promotions',
      promotions_calculated: 'Promotions applied, calculating final price',
      price_calculated: 'Price calculated, ready for confirmation',
      confirmed: 'Order confirmed and sent to kitchen',
      preparing: 'Kitchen is preparing the order',
      ready: 'Order is ready for pickup/serving',
      delivering: 'Order is being delivered',
      delivered: 'Order has been delivered',
      completed: 'Order completed successfully',
      cancelled: 'Order was cancelled',
      refunded: 'Order was refunded'
    };
    
    return descriptions[status] || 'Unknown status';
  };
  
  const StatusIcon = () => {
    if (order.status === 'cancelled' || order.status === 'refunded') {
      return <AlertCircle className={`${iconSizes[size]} text-red-500`} />;
    }
    if (isInSafeState) {
      return <CheckCircle className={`${iconSizes[size]} text-green-500`} />;
    }
    return <Circle className={`${iconSizes[size]} ${indicatorColor} fill-current`} />;
  };
  
  const orderDisplay = (
    <div className={`inline-flex items-center gap-1.5 ${sizeClasses[size]} ${className}`}>
      <span>{order.orderNumber || 'New Order'}</span>
      <StatusIcon />
    </div>
  );
  
  if (!showPopover) {
    return orderDisplay;
  }
  
  return (
    <Popover>
      <PopoverTrigger asChild>
        <button className="inline-flex items-center gap-1.5 hover:opacity-80 transition-opacity cursor-help">
          {orderDisplay}
        </button>
      </PopoverTrigger>
      <PopoverContent className="w-80">
        <div className="space-y-3">
          <div>
            <h4 className="font-medium text-sm mb-1">Order Status</h4>
            <Badge variant={isInSafeState ? 'default' : 'secondary'}>
              {getStatusLabel(order.status)}
            </Badge>
          </div>
          
          <div>
            <p className="text-sm text-gray-600">
              {getStatusDescription(order.status)}
            </p>
          </div>
          
          {!isInSafeState && (
            <div className="pt-2 border-t">
              <div className="flex items-start gap-2">
                <AlertCircle className="h-4 w-4 text-orange-500 mt-0.5" />
                <p className="text-xs text-gray-600">
                  This order is still being processed and may change. 
                  It will be finalized once confirmed.
                </p>
              </div>
            </div>
          )}
          
          {order.createdAt && (
            <div className="text-xs text-gray-500">
              Created: {new Date(order.createdAt).toLocaleString()}
            </div>
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}