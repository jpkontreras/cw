import { cn } from '@/lib/utils';
import { useCurrencyFormatter } from '@/hooks/use-currency';
import { Badge } from '@/components/ui/badge';
import {
  Clock,
  User,
  MapPin,
  Receipt,
  Package,
  DollarSign,
  CreditCard,
  Calendar,
  Hash,
  Info,
  ChevronRight,
  ShoppingBag,
  Phone,
  AlertCircle,
  CheckCircle,
  Layers,
  Activity,
  TrendingUp
} from 'lucide-react';
import { format } from 'date-fns';
import React from 'react';

interface OrderItem {
  id: string | number;
  name: string;
  quantity: number;
  price?: number;
  unitPrice?: number;
  basePrice?: number;
  modifiers?: Array<{
    name: string;
    price: number;
  }>;
  notes?: string;
}

interface EventDetailViewProps {
  event: {
    id: number;
    type: string;
    eventClass: string;
    version: number;
    properties: Record<string, any>;
    metadata: Record<string, any>;
    userId: number | null;
    userName: string;
    description: string;
    icon: string;
    color: string;
    createdAt: string;
    timestamp: string;
    relativeTime: string;
  };
  orderState: {
    order?: any;
    items?: OrderItem[];
    customerName?: string;
    customerPhone?: string;
    customerEmail?: string;
    status?: string;
    subtotal?: number;
    total?: number;
    tax?: number;
    discount?: number;
    tip?: number;
    location?: any;
    payments?: any[];
    _isHistorical?: boolean;
    _timestamp?: string;
    _eventCount?: number;
  };
  isHistorical?: boolean;
  className?: string;
}

export function EventDetailView({
  event,
  orderState,
  isHistorical = false,
  className
}: EventDetailViewProps) {
  const { formatCurrency } = useCurrencyFormatter();

  const getStatusColor = (status: string) => {
    const colors: Record<string, string> = {
      draft: 'bg-gray-100 text-gray-700',
      started: 'bg-blue-100 text-blue-700',
      confirmed: 'bg-green-100 text-green-700',
      preparing: 'bg-orange-100 text-orange-700',
      ready: 'bg-purple-100 text-purple-700',
      completed: 'bg-green-200 text-green-800',
      cancelled: 'bg-red-100 text-red-700',
    };
    return colors[status?.toLowerCase()] || 'bg-gray-100 text-gray-700';
  };

  const getEventIcon = () => {
    switch (event.type) {
      case 'OrderStatusChanged':
        return <Layers className="h-5 w-5" />;
      case 'ItemAddedToOrder':
        return <Package className="h-5 w-5" />;
      case 'PaymentProcessed':
        return <CreditCard className="h-5 w-5" />;
      case 'CustomerInfoEntered':
        return <User className="h-5 w-5" />;
      default:
        return <Activity className="h-5 w-5" />;
    }
  };

  // Extract status transition if this is a status change event
  const getStatusTransition = () => {
    if (event.type === 'OrderStatusChanged') {
      const from = event.properties.fromStatus || event.properties.from || event.properties.oldStatus;
      const to = event.properties.toStatus || event.properties.to || event.properties.newStatus || event.properties.status;
      return { from, to };
    }
    return null;
  };

  const statusTransition = getStatusTransition();

  return (
    <div className={cn("flex flex-col h-full bg-white", className)}>
      {/* Event Header */}
      <div className="border-b bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-4">
        <div className="flex items-start justify-between mb-3">
          <div className="flex items-center gap-3">
            <div className={cn(
              "p-2 rounded-lg",
              event.color === 'green' ? 'bg-green-100 text-green-700' :
              event.color === 'blue' ? 'bg-blue-100 text-blue-700' :
              event.color === 'orange' ? 'bg-orange-100 text-orange-700' :
              'bg-gray-100 text-gray-700'
            )}>
              {getEventIcon()}
            </div>
            <div>
              <h2 className="text-lg font-semibold text-gray-900">{event.description}</h2>
              {statusTransition && (
                <div className="flex items-center gap-2 mt-1">
                  <Badge className={cn("text-xs", getStatusColor(statusTransition.from))}>
                    {statusTransition.from?.charAt(0).toUpperCase() + statusTransition.from?.slice(1)}
                  </Badge>
                  <ChevronRight className="h-4 w-4 text-gray-400" />
                  <Badge className={cn("text-xs", getStatusColor(statusTransition.to))}>
                    {statusTransition.to?.charAt(0).toUpperCase() + statusTransition.to?.slice(1)}
                  </Badge>
                </div>
              )}
              <p className="text-sm text-gray-500 flex items-center gap-2 mt-1">
                <Clock className="h-3.5 w-3.5" />
                {format(new Date(event.createdAt), 'MMM d, yyyy h:mm:ss a')}
                <span className="text-gray-400">•</span>
                <span>{event.relativeTime}</span>
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Badge variant="outline" className="font-mono">
              <Hash className="h-3 w-3 mr-1" />
              Event {event.version}
            </Badge>
            {isHistorical && (
              <Badge className="bg-amber-100 text-amber-800 border-amber-200">
                <Clock className="h-3 w-3 mr-1" />
                Historical
              </Badge>
            )}
          </div>
        </div>

        {/* Event Metadata Bar */}
        <div className="flex items-center gap-4 text-xs text-gray-600">
          <div className="flex items-center gap-1">
            <User className="h-3.5 w-3.5" />
            <span className="font-medium">{event.userName}</span>
          </div>
          <div className="flex items-center gap-1">
            <Layers className="h-3.5 w-3.5" />
            <span className="font-mono">{event.eventClass.split('\\').pop()}</span>
          </div>
        </div>
      </div>

      {/* Main Content Area */}
      <div className="flex-1 overflow-y-auto">
        <div className="p-6 space-y-6">
          {/* Order Status & Info */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {/* Order Status Card */}
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-700">Order Status</h3>
                <CheckCircle className="h-4 w-4 text-gray-400" />
              </div>
              <div className={cn(
                "inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium",
                getStatusColor(orderState.status || 'draft')
              )}>
                {orderState.status ? orderState.status.charAt(0).toUpperCase() + orderState.status.slice(1) : 'Draft'}
              </div>
              {orderState._eventCount && (
                <p className="text-xs text-gray-500 mt-2">
                  Based on {orderState._eventCount} events
                </p>
              )}
            </div>

            {/* Order Total Card */}
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-700">Order Total</h3>
                <DollarSign className="h-4 w-4 text-gray-400" />
              </div>
              <div className="text-2xl font-bold text-gray-900">
                {formatCurrency(orderState.total || 0)}
              </div>
              {(orderState.subtotal > 0 || orderState.discount > 0 || orderState.tip > 0) && (
                <div className="text-xs text-gray-500 mt-2 space-y-1">
                  {orderState.subtotal > 0 && (
                    <div className="flex justify-between items-center">
                      <span>Subtotal</span>
                      <span className="font-medium">{formatCurrency(orderState.subtotal)}</span>
                    </div>
                  )}
                  {orderState.discount > 0 && (
                    <div className="flex justify-between items-center text-green-600">
                      <span>Discount</span>
                      <span className="font-medium">-{formatCurrency(orderState.discount)}</span>
                    </div>
                  )}
                  {orderState.tip > 0 && (
                    <div className="flex justify-between items-center">
                      <span>Tip</span>
                      <span className="font-medium">+{formatCurrency(orderState.tip)}</span>
                    </div>
                  )}
                </div>
              )}
            </div>

            {/* Customer Info Card */}
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="flex items-center justify-between mb-2">
                <h3 className="text-sm font-medium text-gray-700">Customer</h3>
                <User className="h-4 w-4 text-gray-400" />
              </div>
              {orderState.customerName ? (
                <div className="space-y-1">
                  <p className="text-sm font-medium text-gray-900">{orderState.customerName}</p>
                  {orderState.customerPhone && (
                    <p className="text-xs text-gray-600 flex items-center gap-1">
                      <Phone className="h-3 w-3" />
                      {orderState.customerPhone}
                    </p>
                  )}
                </div>
              ) : (
                <p className="text-sm text-gray-500">No customer info</p>
              )}
            </div>
          </div>

          {/* Items Grid */}
          <div>
            <div className="flex items-center justify-between mb-3">
              <h3 className="text-base font-semibold text-gray-900 flex items-center gap-2">
                <ShoppingBag className="h-5 w-5" />
                Order Items
                {orderState.items && orderState.items.length > 0 && (
                  <Badge variant="secondary">{orderState.items.length}</Badge>
                )}
              </h3>
              <div className="text-sm text-gray-500">
                Total Items: {orderState.items?.reduce((sum, item) => sum + item.quantity, 0) || 0}
              </div>
            </div>

            {orderState.items && orderState.items.length > 0 ? (
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                {orderState.items.map((item, index) => (
                  <div
                    key={item.id || index}
                    className="bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
                  >
                    <div className="flex items-start justify-between mb-2">
                      <div className="flex-1">
                        <h4 className="font-medium text-gray-900 text-sm line-clamp-2">{item.name}</h4>
                        {item.modifiers && item.modifiers.length > 0 && (
                          <div className="mt-1 space-y-0.5">
                            {item.modifiers.map((mod, idx) => (
                              <p key={idx} className="text-xs text-gray-500 line-clamp-1">
                                + {mod.name} {mod.price && mod.price > 0 && `(+${formatCurrency(mod.price)})`}
                              </p>
                            ))}
                          </div>
                        )}
                        {item.notes && item.notes !== 'null' && item.notes !== '' && (
                          <p className="text-xs text-gray-500 mt-1 italic line-clamp-1">{item.notes}</p>
                        )}
                      </div>
                      <Badge className="ml-2 bg-blue-100 text-blue-700 flex-shrink-0">
                        ×{item.quantity}
                      </Badge>
                    </div>
                    <div className="flex items-center justify-between pt-2 border-t border-gray-100">
                      <span className="text-xs text-gray-500">Unit Price</span>
                      <span className="font-semibold text-gray-900">
                        {(() => {
                          const price = item.unitPrice ?? item.price ?? item.basePrice;
                          return formatCurrency(price);
                        })()}
                      </span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="bg-gray-50 rounded-lg p-8 text-center">
                <Package className="h-12 w-12 text-gray-300 mx-auto mb-3" />
                <p className="text-gray-500">No items in this order state</p>
              </div>
            )}
          </div>

          {/* Event Properties - Only show meaningful data */}
          {(() => {
            const meaningfulProperties = Object.entries(event.properties)
              .filter(([key, value]) => {
                // Filter out items, null, undefined, empty arrays, empty strings
                if (['items', 'item', 'userId', 'fromStatus', 'toStatus', 'from', 'to', 'oldStatus', 'newStatus', 'status'].includes(key)) return false;
                if (value === null || value === undefined || value === 'null') return false;
                if (value === '' || value === '[]' || value === '{}') return false;
                if (Array.isArray(value) && value.length === 0) return false;
                if (typeof value === 'object' && Object.keys(value).length === 0) return false;
                return true;
              });

            if (meaningfulProperties.length === 0) return null;

            // Check if we have status transition data to show separately
            const hasStatusTransition = statusTransition && (statusTransition.from || statusTransition.to);

            return (
              <div>
                <h3 className="text-base font-semibold text-gray-900 mb-3 flex items-center gap-2">
                  <Info className="h-5 w-5" />
                  Event Details
                </h3>
                <div className="bg-gray-50 rounded-lg p-4">
                  {/* Status Transition Section */}
                  {hasStatusTransition && (
                    <div className="mb-4 pb-4 border-b border-gray-200">
                      <div className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                        {statusTransition.from && (
                          <div>
                            <dt className="text-xs font-medium text-gray-500 mb-1">From Status</dt>
                            <dd>
                              <Badge variant="secondary" className={cn("text-xs", getStatusColor(statusTransition.from))}>
                                {statusTransition.from.charAt(0).toUpperCase() + statusTransition.from.slice(1)}
                              </Badge>
                            </dd>
                          </div>
                        )}
                        {statusTransition.to && (
                          <div>
                            <dt className="text-xs font-medium text-gray-500 mb-1">To Status</dt>
                            <dd>
                              <Badge variant="secondary" className={cn("text-xs", getStatusColor(statusTransition.to))}>
                                {statusTransition.to.charAt(0).toUpperCase() + statusTransition.to.slice(1)}
                              </Badge>
                            </dd>
                          </div>
                        )}
                      </div>
                    </div>
                  )}

                  {/* Other Properties */}
                  <dl className="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-3">
                    {meaningfulProperties.map(([key, value]) => (
                      <div key={key} className="flex justify-between items-start sm:block">
                        <dt className="text-xs font-medium text-gray-500 capitalize">
                          {key.replace(/([A-Z])/g, ' $1').replace(/_/g, ' ').trim()}
                        </dt>
                        <dd className="text-sm text-gray-900 sm:mt-0.5 font-medium">
                          {typeof value === 'boolean' ? (value ? 'Yes' : 'No') :
                           typeof value === 'object' ?
                             (Array.isArray(value) ? `${value.length} items` : 'Complex data') :
                           String(value)}
                        </dd>
                      </div>
                    ))}
                  </dl>
                </div>
              </div>
            );
          })()}
        </div>
      </div>
    </div>
  );
}