import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { 
  MapPin, 
  User, 
  Calendar, 
  DollarSign, 
  ShoppingBag,
  Clock,
  Receipt,
  Percent,
  CreditCard,
  AlertCircle,
  CheckCircle,
  XCircle,
  Package,
  Hash
} from 'lucide-react';
import { format, parseISO } from 'date-fns';
import { useMemo } from 'react';

interface OrderItem {
  id: number;
  itemId: number;
  name: string;
  quantity: number;
  unitPrice: number;
  basePrice: number;
  modifiers?: Array<{
    name: string;
    price: number;
  }>;
  modifiersTotal: number;
  subtotal: number;
  notes?: string;
}

interface OrderStateViewerProps {
  orderState: Record<string, unknown> | null;
  currentTimestamp?: Date;
  className?: string;
}

const statusConfig: Record<string, { color: string; icon: React.ElementType; label: string }> = {
  draft: { color: 'bg-gray-100 text-gray-800', icon: AlertCircle, label: 'Draft' },
  pending: { color: 'bg-yellow-100 text-yellow-800', icon: Clock, label: 'Pending' },
  confirmed: { color: 'bg-blue-100 text-blue-800', icon: CheckCircle, label: 'Confirmed' },
  preparing: { color: 'bg-orange-100 text-orange-800', icon: Package, label: 'Preparing' },
  ready: { color: 'bg-green-100 text-green-800', icon: CheckCircle, label: 'Ready' },
  completed: { color: 'bg-green-100 text-green-800', icon: CheckCircle, label: 'Completed' },
  cancelled: { color: 'bg-red-100 text-red-800', icon: XCircle, label: 'Cancelled' },
};

export function OrderStateViewer({ orderState, currentTimestamp, className = '' }: OrderStateViewerProps) {
  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };
  
  // Cast orderState to a typed object for easier access
  const state = orderState as {
    uuid?: string;
    orderNumber?: string;
    status?: string;
    customerName?: string;
    customerPhone?: string;
    customerEmail?: string;
    locationId?: number;
    locationName?: string;
    items?: OrderItem[];
    promotionId?: number;
    promotionName?: string;
    promotionAmount?: number;
    tipAmount?: number;
    subtotal?: number;
    total?: number;
    notes?: string;
    createdAt?: string;
    updatedAt?: string;
    confirmedAt?: string;
    completedAt?: string;
    cancelledAt?: string;
  } | null;
  
  // Calculate if viewing historical state
  const isHistoricalView = useMemo(() => {
    if (!currentTimestamp || !state?.updatedAt) return false;
    const stateTime = parseISO(state.updatedAt);
    return currentTimestamp < stateTime;
  }, [currentTimestamp, state]);
  
  if (!state) {
    return (
      <div className={cn("flex items-center justify-center h-full bg-gray-50", className)}>
        <div className="text-center">
          <Package className="h-12 w-12 text-gray-400 mx-auto mb-3" />
          <p className="text-gray-500">No order state to display</p>
          <p className="text-sm text-gray-400 mt-1">Select an event from the timeline</p>
        </div>
      </div>
    );
  }
  
  const statusInfo = statusConfig[state.status || 'draft'] || statusConfig.draft;
  const StatusIcon = statusInfo.icon;
  
  return (
    <div className={cn("h-full overflow-hidden bg-gray-50", className)}>
      <ScrollArea className="h-full">
        <div className="p-6 space-y-6">
          {/* Historical View Banner */}
          {isHistoricalView && (
            <div className="bg-amber-50 border border-amber-200 rounded-lg p-3 flex items-center gap-2">
              <Clock className="h-4 w-4 text-amber-600" />
              <span className="text-sm text-amber-800">
                Viewing historical state from {format(currentTimestamp, 'h:mm:ss a')}
              </span>
            </div>
          )}
          
          {/* Order Header */}
          <Card>
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <div className="flex items-center gap-3">
                    <Hash className="h-5 w-5 text-gray-500" />
                    <CardTitle className="text-2xl">{state.orderNumber}</CardTitle>
                    <Badge className={cn("flex items-center gap-1", statusInfo.color)}>
                      <StatusIcon className="h-3 w-3" />
                      {statusInfo.label}
                    </Badge>
                  </div>
                  <p className="text-sm text-gray-500 mt-2">
                    UUID: {state.uuid}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-2xl font-bold">{formatCurrency(state.total || 0)}</p>
                  <p className="text-sm text-gray-500">Total Amount</p>
                </div>
              </div>
            </CardHeader>
          </Card>
          
          {/* Customer Information */}
          {(state.customerName || state.customerPhone || state.customerEmail) && (
            <Card>
              <CardHeader>
                <CardTitle className="text-lg flex items-center gap-2">
                  <User className="h-4 w-4" />
                  Customer Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-2">
                {state.customerName && (
                  <div className="flex items-center gap-2 text-sm">
                    <span className="text-gray-500">Name:</span>
                    <span className="font-medium">{state.customerName}</span>
                  </div>
                )}
                {state.customerPhone && (
                  <div className="flex items-center gap-2 text-sm">
                    <span className="text-gray-500">Phone:</span>
                    <span className="font-medium">{state.customerPhone}</span>
                  </div>
                )}
                {state.customerEmail && (
                  <div className="flex items-center gap-2 text-sm">
                    <span className="text-gray-500">Email:</span>
                    <span className="font-medium">{state.customerEmail}</span>
                  </div>
                )}
              </CardContent>
            </Card>
          )}
          
          {/* Order Items */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <ShoppingBag className="h-4 w-4" />
                Order Items ({state.items?.length || 0})
              </CardTitle>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                {state.items?.map((item, index) => (
                  <div key={item.id || index} className="pb-3 last:pb-0 border-b last:border-0">
                    <div className="flex items-start justify-between">
                      <div className="flex-1">
                        <div className="flex items-center gap-2">
                          <span className="font-medium">{item.quantity}x</span>
                          <span>{item.name}</span>
                        </div>
                        {item.modifiers && item.modifiers.length > 0 && (
                          <div className="mt-1 ml-6">
                            {item.modifiers.map((mod, idx) => (
                              <div key={idx} className="text-sm text-gray-600">
                                + {mod.name} ({formatCurrency(mod.price)})
                              </div>
                            ))}
                          </div>
                        )}
                        {item.notes && (
                          <div className="mt-1 ml-6 text-sm text-gray-500 italic">
                            Note: {item.notes}
                          </div>
                        )}
                      </div>
                      <div className="text-right">
                        <div className="font-medium">{formatCurrency(item.subtotal)}</div>
                        <div className="text-xs text-gray-500">
                          @ {formatCurrency(item.unitPrice)} each
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
          
          {/* Order Summary */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <Receipt className="h-4 w-4" />
                Order Summary
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600">Subtotal</span>
                <span>{formatCurrency(state.subtotal || 0)}</span>
              </div>
              
              {state.promotionId && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600 flex items-center gap-1">
                    <Percent className="h-3 w-3" />
                    {state.promotionName || 'Promotion'}
                  </span>
                  <span className="text-green-600">
                    -{formatCurrency(state.promotionAmount || 0)}
                  </span>
                </div>
              )}
              
              {(state.tipAmount || 0) > 0 && (
                <div className="flex justify-between text-sm">
                  <span className="text-gray-600">Tip</span>
                  <span>{formatCurrency(state.tipAmount || 0)}</span>
                </div>
              )}
              
              <Separator />
              
              <div className="flex justify-between font-medium text-lg">
                <span>Total</span>
                <span>{formatCurrency(state.total || 0)}</span>
              </div>
            </CardContent>
          </Card>
          
          {/* Location & Timing */}
          <Card>
            <CardHeader>
              <CardTitle className="text-lg flex items-center gap-2">
                <MapPin className="h-4 w-4" />
                Location & Timing
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              {state.locationName && (
                <div className="flex items-center gap-2 text-sm">
                  <MapPin className="h-3 w-3 text-gray-500" />
                  <span className="text-gray-600">Location:</span>
                  <span className="font-medium">{state.locationName}</span>
                </div>
              )}
              
              <div className="flex items-center gap-2 text-sm">
                <Calendar className="h-3 w-3 text-gray-500" />
                <span className="text-gray-600">Created:</span>
                <span className="font-medium">
                  {state.createdAt ? format(parseISO(state.createdAt), 'MMM d, yyyy h:mm a') : 'N/A'}
                </span>
              </div>
              
              {state.confirmedAt && (
                <div className="flex items-center gap-2 text-sm">
                  <CheckCircle className="h-3 w-3 text-gray-500" />
                  <span className="text-gray-600">Confirmed:</span>
                  <span className="font-medium">
                    {format(parseISO(state.confirmedAt), 'MMM d, yyyy h:mm a')}
                  </span>
                </div>
              )}
              
              {state.completedAt && (
                <div className="flex items-center gap-2 text-sm">
                  <CheckCircle className="h-3 w-3 text-green-500" />
                  <span className="text-gray-600">Completed:</span>
                  <span className="font-medium">
                    {format(parseISO(state.completedAt), 'MMM d, yyyy h:mm a')}
                  </span>
                </div>
              )}
              
              {state.cancelledAt && (
                <div className="flex items-center gap-2 text-sm">
                  <XCircle className="h-3 w-3 text-red-500" />
                  <span className="text-gray-600">Cancelled:</span>
                  <span className="font-medium">
                    {format(parseISO(state.cancelledAt), 'MMM d, yyyy h:mm a')}
                  </span>
                </div>
              )}
            </CardContent>
          </Card>
          
          {/* Notes */}
          {state.notes && (
            <Card>
              <CardHeader>
                <CardTitle className="text-lg">Order Notes</CardTitle>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-gray-700 whitespace-pre-wrap">{state.notes}</p>
              </CardContent>
            </Card>
          )}
        </div>
      </ScrollArea>
    </div>
  );
}