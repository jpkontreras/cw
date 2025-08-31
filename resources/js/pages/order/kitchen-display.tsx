import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { cn } from '@/lib/utils';
import { Head, router } from '@inertiajs/react';
import { Activity, AlertCircle, CheckCircle, ChefHat, Clock, Home, Maximize2, Package, RefreshCw, ShoppingBag, Truck, User } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';

interface OrderItem {
  id: number;
  itemName: string;
  quantity: number;
  status: string;
  notes?: string;
  kitchenStatus?: string;
}

interface Order {
  id: number;
  status: string;
  customerName?: string;
  items: OrderItem[];
  placedAt?: string;
  createdAt: string;
  type?: 'dine_in' | 'dine-in' | 'takeout' | 'delivery';
  orderType?: 'dine-in' | 'takeout' | 'delivery';
  orderNumber?: string;
  tableNumber?: number;
  priority?: 'normal' | 'rush' | 'vip';
  specialInstructions?: string;
}

interface Props {
  orders: Order[];
  locationId: number;
}

const OrderCard = ({ order, onStatusUpdate }: { order: Order; onStatusUpdate: (status: string) => void }) => {
  const orderTime = new Date(order.placedAt || order.createdAt);
  const elapsedMinutes = Math.floor((Date.now() - orderTime.getTime()) / 60000);

  // Time-based urgency colors
  const getTimeColor = () => {
    if (elapsedMinutes >= 20) return 'text-red-600 bg-red-50';
    if (elapsedMinutes >= 15) return 'text-orange-600 bg-orange-50';
    if (elapsedMinutes >= 10) return 'text-yellow-600 bg-yellow-50';
    return 'text-gray-600 bg-gray-50';
  };

  // Order type icon
  const getOrderTypeIcon = () => {
    switch (order.type || order.orderType) {
      case 'dine_in':
      case 'dine-in':
        return <Home className="h-4 w-4" />;
      case 'takeout':
        return <ShoppingBag className="h-4 w-4" />;
      case 'delivery':
        return <Truck className="h-4 w-4" />;
      default:
        return <Package className="h-4 w-4" />;
    }
  };

  const getOrderTypeColor = () => {
    switch (order.type || order.orderType) {
      case 'dine_in':
      case 'dine-in':
        return 'bg-blue-50 text-blue-700 border-blue-200';
      case 'takeout':
        return 'bg-purple-50 text-purple-700 border-purple-200';
      case 'delivery':
        return 'bg-green-50 text-green-700 border-green-200';
      default:
        return 'bg-gray-50 text-gray-700 border-gray-200';
    }
  };

  // Priority styling
  const getPriorityStyles = () => {
    if (order.priority === 'rush') return 'ring-2 ring-purple-500 shadow-lg';
    if (order.priority === 'vip') return 'ring-2 ring-yellow-500 shadow-lg';
    return '';
  };

  // Status-based card styles
  const getCardStyles = () => {
    const base = 'bg-white border transition-all duration-300 hover:shadow-lg';
    switch (order.status) {
      case 'confirmed':
        return `${base} border-amber-200 shadow-md`;
      case 'preparing':
        return `${base} border-blue-200 shadow-md`;
      case 'ready':
        return `${base} border-green-200 shadow-md`;
      default:
        return `${base} border-gray-200`;
    }
  };

  return (
    <Card className={cn(getCardStyles(), getPriorityStyles(), 'relative overflow-hidden')}>
      {order.priority === 'rush' && (
        <div className="absolute top-0 right-0 rounded-bl-lg bg-purple-600 px-3 py-1 text-xs font-bold text-white">RUSH</div>
      )}
      {order.priority === 'vip' && (
        <div className="absolute top-0 right-0 rounded-bl-lg bg-yellow-500 px-3 py-1 text-xs font-bold text-yellow-900">VIP</div>
      )}

      {/* Order Header */}
      <div className="space-y-2 px-4 py-3">
        {/* Order Number and Time */}
        <div className="flex items-start justify-between">
          <div>
            <div className="text-xl font-bold text-gray-900">#{order.orderNumber || `ORD-${order.id}`}</div>
            <div className="text-xs text-gray-600">Order {order.orderNumber?.split('-').pop() || order.id}</div>
          </div>
          <div className={cn('flex items-center gap-1 rounded px-2.5 py-1 font-mono text-xs font-bold', getTimeColor())}>
            <Clock className="h-3.5 w-3.5" />
            {elapsedMinutes} min
          </div>
        </div>

        {/* Order Type Badge */}
        <Badge className={cn('text-xs font-medium', getOrderTypeColor())}>
          {getOrderTypeIcon()}
          <span className="ml-1.5">
            {(order.type || order.orderType) === 'dine_in' && order.tableNumber
              ? `Table ${order.tableNumber}`
              : (order.type || order.orderType)?.replace('_', ' ').toUpperCase() || 'ORDER'}
          </span>
        </Badge>

        {/* Customer info */}
        <div className="flex items-center justify-between border-t pt-2">
          <div className="flex items-center gap-2 text-sm text-gray-700">
            <User className="h-4 w-4 text-gray-500" />
            <span className="font-medium">{order.customerName || 'Walk-in Customer'}</span>
          </div>
          <div className="text-xs text-gray-500">
            {orderTime.toLocaleTimeString('en-US', {
              hour: 'numeric',
              minute: '2-digit',
              hour12: true,
            })}
          </div>
        </div>
      </div>

      {/* Order Items and Content */}
      <CardContent className="space-y-3 px-4 py-3">
        {/* Items Section - Only show if there are items */}
        {order.items && order.items.length > 0 && (
          <>
            <div className="-mx-4 border-t border-gray-100" />
            <div className="space-y-1.5">
              {order.items.map((item) => (
                <div
                  key={item.id}
                  className={cn(
                    'relative flex items-start gap-2.5 rounded-lg p-2.5 transition-all duration-200',
                    item.status === 'prepared'
                      ? 'border border-green-200 bg-green-50'
                      : 'border border-gray-100 bg-white hover:border-gray-200 hover:shadow-sm',
                  )}
                >
                  {/* Quantity Badge */}
                  <div
                    className={cn(
                      'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-bold',
                      item.status === 'prepared' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700',
                    )}
                  >
                    {item.quantity}
                  </div>

                  {/* Item Details */}
                  <div className="flex-1">
                    <div className={cn('text-sm font-medium', item.status === 'prepared' ? 'text-green-700 line-through' : 'text-gray-900')}>
                      {item.itemName}
                    </div>

                    {/* Special Notes */}
                    {item.notes && (
                      <div className="mt-1.5 flex items-start gap-1.5 rounded border border-amber-200 bg-amber-50 p-1.5 text-xs">
                        <AlertCircle className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-amber-600" />
                        <p className="font-medium text-amber-800">{item.notes}</p>
                      </div>
                    )}
                  </div>

                  {/* Prepared Check */}
                  {item.status === 'prepared' && <CheckCircle className="h-4 w-4 flex-shrink-0 text-green-600" />}
                </div>
              ))}
            </div>
          </>
        )}

        {/* Special Instructions */}
        {order.specialInstructions && (
          <div
            className={cn(
              'rounded-lg border border-purple-200 bg-purple-50 p-2.5',
              (!order.items || order.items.length === 0) &&
                '-mx-4 rounded-none border-x-0 border-t border-b-0 border-gray-100 bg-purple-50/50 px-4 pt-3',
            )}
          >
            <div className="flex items-start gap-2">
              <ChefHat className="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-purple-600" />
              <div>
                <p className="text-xs font-medium tracking-wider text-purple-700 uppercase">Kitchen Notes</p>
                <p className="mt-0.5 text-xs text-purple-800">{order.specialInstructions}</p>
              </div>
            </div>
          </div>
        )}

        {/* Action Button */}
        {order.status === 'confirmed' && (
          <Button
            size="lg"
            className="h-11 w-full bg-blue-600 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
            onClick={() => onStatusUpdate('start-preparing')}
          >
            <ChefHat className="mr-2 h-4 w-4" />
            Start Preparing
          </Button>
        )}
        {order.status === 'preparing' && (
          <Button
            size="lg"
            className="h-11 w-full bg-green-600 text-sm font-semibold text-white shadow-sm hover:bg-green-700"
            onClick={() => onStatusUpdate('mark-ready')}
          >
            <CheckCircle className="mr-2 h-4 w-4" />
            Mark Ready
          </Button>
        )}
        {order.status === 'ready' && (
          <Button
            size="lg"
            className="h-11 w-full bg-gray-700 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
            onClick={() => onStatusUpdate('complete')}
          >
            <Package className="mr-2 h-4 w-4" />
            Complete Order
          </Button>
        )}
      </CardContent>
    </Card>
  );
};

export default function KitchenDisplay({ orders: initialOrders, locationId }: Props) {
  const [orders, setOrders] = useState(initialOrders);
  const [isFullscreen, setIsFullscreen] = useState(false);
  const [lastRefresh, setLastRefresh] = useState(new Date());
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Sort and categorize orders
  const sortedOrders = [...orders].sort((a, b) => {
    // Priority orders first
    if (a.priority === 'rush' && b.priority !== 'rush') return -1;
    if (b.priority === 'rush' && a.priority !== 'rush') return 1;
    if (a.priority === 'vip' && b.priority !== 'vip') return -1;
    if (b.priority === 'vip' && a.priority !== 'vip') return 1;

    // Then by time
    const aTime = new Date(a.placedAt || a.createdAt).getTime();
    const bTime = new Date(b.placedAt || b.createdAt).getTime();
    return aTime - bTime;
  });

  const confirmedOrders = sortedOrders.filter((o) => o.status === 'confirmed');
  const preparingOrders = sortedOrders.filter((o) => o.status === 'preparing');
  const readyOrders = sortedOrders.filter((o) => o.status === 'ready');

  // Handle status updates
  const handleStatusUpdate = useCallback((orderId: number, status: string) => {
    router.post(
      `/orders/${orderId}/${status}`,
      {},
      {
        preserveScroll: true,
        onSuccess: () => {
          // Optimistically update the UI
          setOrders(
            (prevOrders) =>
              prevOrders
                .map((order) => {
                  if (order.id === orderId) {
                    switch (status) {
                      case 'start-preparing':
                        return { ...order, status: 'preparing' };
                      case 'mark-ready':
                        return { ...order, status: 'ready' };
                      case 'complete':
                        return prevOrders.filter((o) => o.id !== orderId);
                      default:
                        return order;
                    }
                  }
                  return order;
                })
                .filter(Boolean) as Order[],
          );
        },
      },
    );
  }, []);

  // Auto-refresh functionality
  const refreshOrders = useCallback(() => {
    setIsRefreshing(true);
    router.reload({
      only: ['orders'],
      preserveUrl: true,
      onSuccess: (page) => {
        const newOrders = page.props.orders as Order[];
        setOrders(newOrders);
        setLastRefresh(new Date());
        setIsRefreshing(false);
      },
      onError: () => {
        setIsRefreshing(false);
      },
    });
  }, []);

  // Set up auto-refresh
  useEffect(() => {
    const interval = setInterval(refreshOrders, 10000); // Refresh every 10 seconds
    return () => clearInterval(interval);
  }, [refreshOrders]);

  // Fullscreen handling
  const toggleFullscreen = useCallback(() => {
    if (!document.fullscreenElement) {
      // Get the main content area for fullscreen
      const mainContent = document.getElementById('kitchen-display-content');
      if (mainContent) {
        mainContent.requestFullscreen();
        setIsFullscreen(true);
      }
    } else {
      document.exitFullscreen();
      setIsFullscreen(false);
    }
  }, []);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyPress = (e: KeyboardEvent) => {
      // F11 or F for fullscreen
      if (e.key === 'F11' || (e.key === 'f' && !e.ctrlKey && !e.metaKey)) {
        e.preventDefault();
        toggleFullscreen();
      }
      // R for refresh
      if (e.key === 'r' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        refreshOrders();
      }
    };

    window.addEventListener('keydown', handleKeyPress);
    return () => window.removeEventListener('keydown', handleKeyPress);
  }, [toggleFullscreen, refreshOrders]);

  return (
    <AppLayout>
      <Head title="Kitchen Display" />

      <Page>
        {!isFullscreen && (
          <Page.Header 
            title="Kitchen Display" 
            subtitle={`Live order tracking for Location ${locationId}`}
            actions={
              <div className="flex items-center gap-2">
                <Button variant="outline" size="sm" onClick={refreshOrders} disabled={isRefreshing}>
                  <RefreshCw className={cn('mr-2 h-4 w-4', isRefreshing && 'animate-spin')} />
                  Refresh
                </Button>

                <Button variant="outline" size="sm" onClick={toggleFullscreen}>
                  <Maximize2 className="mr-2 h-4 w-4" />
                  Fullscreen
                </Button>
              </div>
            }
          />
        )}

        {/* Main content */}
        <Page.Content noPadding>
          <div id="kitchen-display-content" className={cn('flex flex-1 flex-col', isFullscreen && 'fixed inset-0 z-50 bg-white')}>
        {/* Fullscreen header */}
        {isFullscreen && (
          <div className="border-b border-gray-200 bg-white px-6 py-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-4">
                <h1 className="text-xl font-semibold">Kitchen Display - Location {locationId}</h1>
                <span className="text-sm text-gray-500">
                  Last updated:{' '}
                  {lastRefresh.toLocaleTimeString('en-US', {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false,
                  })}
                </span>
              </div>
              <Button size="sm" variant="outline" onClick={toggleFullscreen}>
                Exit Fullscreen
              </Button>
            </div>
          </div>
        )}

        {/* Check if there are no orders at all */}
        {orders.length === 0 ? (
          <div className="flex flex-1 items-center justify-center">
            <div className="text-center">
              <EmptyState
                icon={ChefHat}
                title="Kitchen is ready"
                description="Waiting for new orders. The display will update automatically when orders arrive."
                helpText={
                  <div className="flex flex-col items-center gap-2">
                    <div className="flex items-center gap-2 text-sm text-gray-600">
                      <Activity className="h-4 w-4 animate-pulse text-green-500" />
                      <span>System is live and monitoring</span>
                    </div>
                    <div className="text-xs text-gray-500">
                      Last checked: {lastRefresh.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                      })}
                    </div>
                  </div>
                }
              />
            </div>
          </div>
        ) : (
          <div className="grid flex-1 grid-cols-3">
            {/* New Orders Column */}
            <div className="flex flex-col border-r border-gray-200 bg-gray-50">
              <div className="border-b border-amber-200 bg-gradient-to-r from-amber-100 to-amber-50 px-6 py-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-xl font-semibold text-gray-900">New Orders</h2>
                  <div className="flex items-center gap-3">
                    <span className="text-3xl font-bold text-amber-600">{confirmedOrders.length}</span>
                  </div>
                </div>
              </div>
              <div className="flex-1 overflow-y-auto p-4">
                {confirmedOrders.length === 0 ? (
                  <div className="flex h-full flex-col items-center justify-center text-gray-400">
                    <ChefHat className="mb-4 h-16 w-16 opacity-20" />
                    <p className="text-sm">No new orders</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {confirmedOrders.map((order) => (
                      <OrderCard key={order.id} order={order} onStatusUpdate={(status) => handleStatusUpdate(order.id, status)} />
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* Preparing Column */}
            <div className="flex flex-col border-r border-gray-200 bg-gray-50">
              <div className="border-b border-blue-200 bg-gradient-to-r from-blue-100 to-blue-50 px-6 py-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-xl font-semibold text-gray-900">Preparing</h2>
                  <div className="flex items-center gap-3">
                    <span className="text-3xl font-bold text-blue-600">{preparingOrders.length}</span>
                  </div>
                </div>
              </div>
              <div className="flex-1 overflow-y-auto p-4">
                {preparingOrders.length === 0 ? (
                  <div className="flex h-full flex-col items-center justify-center text-gray-400">
                    <ChefHat className="mb-4 h-16 w-16 opacity-20" />
                    <p className="text-sm">No orders being prepared</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {preparingOrders.map((order) => (
                      <OrderCard key={order.id} order={order} onStatusUpdate={(status) => handleStatusUpdate(order.id, status)} />
                    ))}
                  </div>
                )}
              </div>
            </div>

            {/* Ready Column */}
            <div className="flex flex-col bg-gray-50">
              <div className="border-b border-green-200 bg-gradient-to-r from-green-100 to-green-50 px-6 py-4">
                <div className="flex items-center justify-between">
                  <h2 className="text-xl font-semibold text-gray-900">Ready for Pickup</h2>
                  <div className="flex items-center gap-3">
                    <span className="text-3xl font-bold text-green-600">{readyOrders.length}</span>
                  </div>
                </div>
              </div>
              <div className="flex-1 overflow-y-auto p-4">
                {readyOrders.length === 0 ? (
                  <div className="flex h-full flex-col items-center justify-center text-gray-400">
                    <ChefHat className="mb-4 h-16 w-16 opacity-20" />
                    <p className="text-sm">No orders ready</p>
                  </div>
                ) : (
                  <div className="space-y-4">
                    {readyOrders.map((order) => (
                      <OrderCard key={order.id} order={order} onStatusUpdate={(status) => handleStatusUpdate(order.id, status)} />
                    ))}
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </div>

          {/* Keyboard shortcuts help */}
          {!isFullscreen && (
            <div className="fixed right-4 bottom-4 rounded-lg border border-gray-200 bg-white p-3 text-xs text-gray-600 shadow-lg">
              <div className="space-y-1">
                <div>
                  Press <kbd className="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">F</kbd> for fullscreen
                </div>
                <div>
                  Press <kbd className="rounded bg-gray-100 px-1.5 py-0.5 text-gray-700">R</kbd> to refresh
                </div>
              </div>
            </div>
          )}
        </Page.Content>
      </Page>
    </AppLayout>
  );
}
