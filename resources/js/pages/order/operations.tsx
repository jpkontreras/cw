import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import type { Order } from '@/modules/order';
import {
  formatCurrency,
  formatOrderNumber,
  getKitchenStatusColor,
  getKitchenStatusLabel,
  getOrderAge,
  getStatusColor,
  getStatusLabel,
  getTypeLabel,
} from '@/modules/order';
import { Head, router } from '@inertiajs/react';
import {
  Activity,
  AlertCircle,
  Bell,
  ChefHat,
  Clock,
  Eye,
  EyeOff,
  Grid,
  List,
  Maximize2,
  Minimize2,
  Package,
  RefreshCw,
  ShoppingCart,
  Timer,
  Truck,
  Volume2,
  VolumeX,
} from 'lucide-react';
import { useCallback, useEffect, useMemo, useState } from 'react';

interface OperationsPageProps {
  orders: Order[];
  locations: Array<{ id: number; name: string }>;
  stats: {
    active: number;
    preparing: number;
    ready: number;
    avgWaitTime: number;
  };
}

// Order Card Component
const OrderCard = ({
  order,
  view = 'grid',
  onAction,
}: {
  order: Order;
  view?: 'grid' | 'list';
  onAction?: (orderId: string, action: string) => void;
}) => {
  const urgencyColor = useMemo(() => {
    const age = getOrderAge(order);
    if (age.includes('hour') || parseInt(age) > 30) return 'bg-red-500';
    if (parseInt(age) > 15) return 'bg-yellow-500';
    return 'bg-green-500';
  }, [order]);

  if (view === 'list') {
    return (
      <div className="flex items-center justify-between border-b p-4 hover:bg-gray-50">
        <div className="flex items-center gap-4">
          <div className={`h-12 w-2 rounded-full ${urgencyColor}`} />
          <div>
            <div className="flex items-center gap-2">
              <span className="font-medium">{formatOrderNumber(order.orderNumber)}</span>
              <Badge variant="outline" className="text-xs">
                {getTypeLabel(order.type)}
              </Badge>
            </div>
            <p className="text-sm text-gray-500">
              {order.customerName || 'Walk-in'} • {getOrderAge(order)}
            </p>
          </div>
        </div>
        <div className="flex items-center gap-4">
          <Badge className={getStatusColor(order.status)}>{getStatusLabel(order.status)}</Badge>
          <span className="font-medium">{formatCurrency(order.totalAmount)}</span>
          <Button size="sm" variant="outline" onClick={() => onAction?.(String(order.id), 'view')}>
            View
          </Button>
        </div>
      </div>
    );
  }

  return (
    <Card className="cursor-pointer transition-shadow hover:shadow-lg">
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div>
            <CardTitle className="text-lg">{formatOrderNumber(order.orderNumber)}</CardTitle>
            <div className="mt-1 flex items-center gap-2">
              <Badge variant="outline" className="text-xs">
                {getTypeLabel(order.type)}
              </Badge>
              {order.priority === 'high' && (
                <Badge variant="destructive" className="text-xs">
                  <AlertCircle className="mr-1 h-3 w-3" />
                  Priority
                </Badge>
              )}
            </div>
          </div>
          <div className={`h-3 w-3 rounded-full ${urgencyColor}`} />
        </div>
      </CardHeader>
      <CardContent className="space-y-3">
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-500">Customer</span>
          <span className="text-sm font-medium">{order.customerName || 'Walk-in'}</span>
        </div>
        {order.tableNumber && (
          <div className="flex items-center justify-between">
            <span className="text-sm text-gray-500">Table</span>
            <span className="text-sm font-medium">{order.tableNumber}</span>
          </div>
        )}
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-500">Items</span>
          <span className="text-sm font-medium">{order.items.length}</span>
        </div>
        <div className="flex items-center justify-between">
          <span className="text-sm text-gray-500">Time</span>
          <span className="text-sm font-medium">{getOrderAge(order)}</span>
        </div>
        <div className="border-t pt-3">
          <div className="mb-3 flex items-center justify-between">
            <Badge className={getStatusColor(order.status)}>{getStatusLabel(order.status)}</Badge>
            <span className="font-semibold">{formatCurrency(order.total_amount)}</span>
          </div>
          <Button className="w-full" size="sm" onClick={() => onAction?.(order.id, 'next')}>
            {order.status === 'placed' && 'Start Preparing'}
            {order.status === 'preparing' && 'Mark Ready'}
            {order.status === 'ready' && 'Complete Order'}
            {!['placed', 'preparing', 'ready'].includes(order.status) && 'View Details'}
          </Button>
        </div>
      </CardContent>
    </Card>
  );
};

// Kitchen Display Component
const KitchenDisplay = ({ orders }: { orders: Order[] }) => {
  const ordersByStatus = useMemo(() => {
    return {
      confirmed: orders.filter((o) => o.status === 'confirmed'),
      preparing: orders.filter((o) => o.status === 'preparing'),
      ready: orders.filter((o) => o.status === 'ready'),
    };
  }, [orders]);

  return (
    <div className="grid h-full grid-cols-3 gap-6">
      {/* Confirmed Orders */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold">New Orders</h3>
          <Badge variant="secondary">{ordersByStatus.confirmed.length}</Badge>
        </div>
        <ScrollArea className="h-[calc(100vh-250px)]">
          <div className="space-y-3 pr-4">
            {ordersByStatus.confirmed.map((order) => (
              <Card key={order.id} className="border-blue-200 bg-blue-50">
                <CardContent className="p-6">
                  <div className="mb-2 flex items-center justify-between">
                    <span className="text-lg font-bold">
                      {order.table_number ? `Table ${order.table_number}` : formatOrderNumber(order.order_number)}
                    </span>
                    <Badge>{getOrderAge(order)}</Badge>
                  </div>
                  <div className="space-y-2">
                    {order.items.map((item, idx) => (
                      <div key={idx} className="text-sm">
                        <div className="flex items-center justify-between">
                          <span className="font-medium">
                            {item.quantity}x {item.item_name}
                          </span>
                        </div>
                        {item.modifiers && item.modifiers.length > 0 && (
                          <div className="ml-4 text-xs text-gray-600">{item.modifiers.map((m) => m.modifier_name).join(', ')}</div>
                        )}
                        {item.notes && <div className="ml-4 text-xs font-medium text-red-600">Note: {item.notes}</div>}
                      </div>
                    ))}
                  </div>
                  {order.special_instructions && (
                    <div className="mt-3 rounded bg-yellow-100 p-2 text-sm">
                      <ChefHat className="mr-1 inline h-4 w-4" />
                      {order.special_instructions}
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Preparing Orders */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold">Preparing</h3>
          <Badge variant="secondary">{ordersByStatus.preparing.length}</Badge>
        </div>
        <ScrollArea className="h-[calc(100vh-250px)]">
          <div className="space-y-3 pr-4">
            {ordersByStatus.preparing.map((order) => (
              <Card key={order.id} className="border-orange-200 bg-orange-50">
                <CardContent className="p-6">
                  <div className="mb-2 flex items-center justify-between">
                    <span className="text-lg font-bold">
                      {order.table_number ? `Table ${order.table_number}` : formatOrderNumber(order.order_number)}
                    </span>
                    <div className="flex items-center gap-2">
                      <Timer className="h-4 w-4" />
                      <span className="text-sm">{getOrderAge(order)}</span>
                    </div>
                  </div>
                  <div className="space-y-2">
                    {order.items.map((item, idx) => (
                      <div key={idx} className="flex items-center justify-between text-sm">
                        <span>
                          {item.quantity}x {item.item_name}
                        </span>
                        <Badge variant="outline" className={getKitchenStatusColor(item.kitchen_status)}>
                          {getKitchenStatusLabel(item.kitchen_status)}
                        </Badge>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </ScrollArea>
      </div>

      {/* Ready Orders */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold">Ready to Serve</h3>
          <Badge variant="secondary">{ordersByStatus.ready.length}</Badge>
        </div>
        <ScrollArea className="h-[calc(100vh-250px)]">
          <div className="space-y-3 pr-4">
            {ordersByStatus.ready.map((order) => (
              <Card key={order.id} className="border-green-200 bg-green-50">
                <CardContent className="p-6">
                  <div className="mb-2 flex items-center justify-between">
                    <span className="text-lg font-bold">
                      {order.table_number ? `Table ${order.table_number}` : formatOrderNumber(order.order_number)}
                    </span>
                    <Bell className="h-5 w-5 animate-pulse text-green-600" />
                  </div>
                  <div className="space-y-1">
                    <div className="text-sm text-gray-600">
                      {order.type === 'dine_in' && order.waiter && <span>Waiter: {order.waiter.name}</span>}
                      {order.type === 'delivery' && <span>Delivery to: {order.delivery_address}</span>}
                      {order.type === 'takeout' && <span>Customer: {order.customer_name}</span>}
                    </div>
                    <div className="text-xs text-gray-500">Ready for: {getOrderAge(order)}</div>
                  </div>
                </CardContent>
              </Card>
            ))}
          </div>
        </ScrollArea>
      </div>
    </div>
  );
};

// Main Operations Center Component
export default function OperationsCenter({ orders: initialOrders, locations, stats }: OperationsPageProps) {
  const [orders, setOrders] = useState(initialOrders);
  const [selectedLocation, setSelectedLocation] = useState('all');
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');
  const [selectedView, setSelectedView] = useState('overview');
  const [soundEnabled, setSoundEnabled] = useState(true);
  const [autoRefresh, setAutoRefresh] = useState(true);
  const [fullscreen, setFullscreen] = useState(false);
  const [isRefreshing, setIsRefreshing] = useState(false);

  // Auto-refresh functionality
  useEffect(() => {
    if (!autoRefresh) return;

    const interval = setInterval(() => {
      handleRefresh();
    }, 30000); // Refresh every 30 seconds

    return () => clearInterval(interval);
  }, [autoRefresh]);

  const handleRefresh = useCallback(() => {
    setIsRefreshing(true);
    router.reload({
      preserveScroll: true,
      only: ['orders', 'stats'],
      onFinish: () => setIsRefreshing(false),
    });
  }, []);

  const handleOrderAction = useCallback(
    (orderId: string, action: string) => {
      if (action === 'view') {
        router.visit(`/orders/${orderId}`);
      } else if (action === 'next') {
        // Handle status progression
        const order = orders.find((o) => o.id === orderId);
        if (!order) return;

        let route = '';
        switch (order.status) {
          case 'placed':
            route = `/orders/${orderId}/confirm`;
            break;
          case 'confirmed':
            route = `/orders/${orderId}/start-preparing`;
            break;
          case 'preparing':
            route = `/orders/${orderId}/mark-ready`;
            break;
          case 'ready':
            route = order.type === 'delivery' ? `/orders/${orderId}/start-delivery` : `/orders/${orderId}/complete`;
            break;
        }

        if (route) {
          router.post(
            route,
            {},
            {
              preserveScroll: true,
              onSuccess: () => {
                if (soundEnabled) {
                  // Play notification sound
                  const audio = new Audio('/sounds/notification.mp3');
                  audio.play().catch(() => {});
                }
              },
            },
          );
        }
      }
    },
    [orders, soundEnabled],
  );

  const filteredOrders = useMemo(() => {
    if (selectedLocation === 'all') return orders;
    return orders.filter((order) => order.location_id === parseInt(selectedLocation));
  }, [orders, selectedLocation]);

  const activeOrders = useMemo(() => {
    return filteredOrders.filter((o) => !['completed', 'cancelled', 'refunded'].includes(o.status));
  }, [filteredOrders]);

  return (
    <AppLayout>
      <Head title="Operations Center" />
      <Page>
        <div className={`${fullscreen ? 'fixed inset-0 z-50 bg-white' : 'flex h-full flex-col'}`}>
          <Page.Header
            title="Operations Center"
            actions={
              <>
                <Badge variant="outline" className="text-lg">
                  <Activity className="mr-1 h-4 w-4 animate-pulse text-green-500" />
                  Live
                </Badge>
            {/* Location Filter */}
            <Select value={selectedLocation} onValueChange={setSelectedLocation}>
              <SelectTrigger className="w-48">
                <SelectValue />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Locations</SelectItem>
                {locations.map((loc) => (
                  <SelectItem key={loc.id} value={loc.id.toString()}>
                    {loc.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {/* View Mode Toggle */}
            <div className="flex items-center gap-1 rounded-lg bg-gray-100 p-1">
              <Button size="sm" variant={viewMode === 'grid' ? 'default' : 'ghost'} onClick={() => setViewMode('grid')}>
                <Grid className="h-4 w-4" />
              </Button>
              <Button size="sm" variant={viewMode === 'list' ? 'default' : 'ghost'} onClick={() => setViewMode('list')}>
                <List className="h-4 w-4" />
              </Button>
            </div>

            {/* Controls */}
            <Button
              variant="outline"
              size="icon"
              onClick={() => setSoundEnabled(!soundEnabled)}
              title={soundEnabled ? 'Mute sounds' : 'Enable sounds'}
            >
              {soundEnabled ? <Volume2 className="h-4 w-4" /> : <VolumeX className="h-4 w-4" />}
            </Button>
            <Button
              variant="outline"
              size="icon"
              onClick={() => setAutoRefresh(!autoRefresh)}
              title={autoRefresh ? 'Disable auto-refresh' : 'Enable auto-refresh'}
            >
              {autoRefresh ? <Eye className="h-4 w-4" /> : <EyeOff className="h-4 w-4" />}
            </Button>
            <Button variant="outline" size="icon" onClick={handleRefresh} disabled={isRefreshing}>
              <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
            </Button>
                <Button variant="outline" size="icon" onClick={() => setFullscreen(!fullscreen)}>
                  {fullscreen ? <Minimize2 className="h-4 w-4" /> : <Maximize2 className="h-4 w-4" />}
                </Button>
              </>
            }
          />
          <Page.Content noPadding={fullscreen}>

            {/* Stats Bar */}
            <div className={`${fullscreen ? 'p-6' : 'px-4'} mb-6 grid grid-cols-4 gap-4`}>
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Active Orders</p>
                  <p className="text-2xl font-bold">{stats.active}</p>
                </div>
                <ShoppingCart className="h-8 w-8 text-blue-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Preparing</p>
                  <p className="text-2xl font-bold">{stats.preparing}</p>
                </div>
                <ChefHat className="h-8 w-8 text-orange-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Ready</p>
                  <p className="text-2xl font-bold">{stats.ready}</p>
                </div>
                <Package className="h-8 w-8 text-green-500" />
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div>
                  <p className="text-sm text-gray-600">Avg. Wait</p>
                  <p className="text-2xl font-bold">{stats.avgWaitTime}m</p>
                </div>
                <Clock className="h-8 w-8 text-purple-500" />
              </div>
            </CardContent>
          </Card>
        </div>

            {/* Main Content */}
            <Tabs value={selectedView} onValueChange={setSelectedView} className={fullscreen ? 'px-6' : 'px-4'}>
          <TabsList className="grid w-full grid-cols-4">
            <TabsTrigger value="overview">Overview</TabsTrigger>
            <TabsTrigger value="kitchen">Kitchen Display</TabsTrigger>
            <TabsTrigger value="delivery">Delivery Tracker</TabsTrigger>
            <TabsTrigger value="alerts">Alerts</TabsTrigger>
          </TabsList>

          {/* Overview Tab */}
          <TabsContent value="overview" className="mt-6">
            {viewMode === 'grid' ? (
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {activeOrders.map((order) => (
                  <OrderCard key={order.id} order={order} onAction={handleOrderAction} />
                ))}
              </div>
            ) : (
              <Card>
                <CardContent className="p-0">
                  {activeOrders.map((order) => (
                    <OrderCard key={order.id} order={order} view="list" onAction={handleOrderAction} />
                  ))}
                </CardContent>
              </Card>
            )}
          </TabsContent>

          {/* Kitchen Display Tab */}
          <TabsContent value="kitchen" className="mt-6">
            <KitchenDisplay orders={activeOrders} />
          </TabsContent>

          {/* Delivery Tracker Tab */}
          <TabsContent value="delivery" className="mt-6">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
              {/* Active Deliveries */}
              <Card>
                <CardHeader>
                  <CardTitle>Active Deliveries</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {activeOrders
                      .filter((o) => o.type === 'delivery' && o.status === 'delivering')
                      .map((order) => (
                        <div key={order.id} className="flex items-center justify-between rounded-lg bg-blue-50 p-3">
                          <div className="flex items-center gap-3">
                            <Truck className="h-5 w-5 text-blue-600" />
                            <div>
                              <p className="font-medium">{formatOrderNumber(order.order_number)}</p>
                              <p className="text-sm text-gray-600">{order.delivery_address}</p>
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="text-sm font-medium">Est. 15 min</p>
                            <p className="text-xs text-gray-500">Driver: Juan P.</p>
                          </div>
                        </div>
                      ))}
                  </div>
                </CardContent>
              </Card>

              {/* Ready for Delivery */}
              <Card>
                <CardHeader>
                  <CardTitle>Ready for Pickup</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {activeOrders
                      .filter((o) => o.type === 'delivery' && o.status === 'ready')
                      .map((order) => (
                        <div key={order.id} className="flex items-center justify-between rounded-lg bg-green-50 p-3">
                          <div>
                            <p className="font-medium">{formatOrderNumber(order.order_number)}</p>
                            <p className="text-sm text-gray-600">{order.customer_name}</p>
                          </div>
                          <Button size="sm" onClick={() => handleOrderAction(order.id, 'next')}>
                            Assign Driver
                          </Button>
                        </div>
                      ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>

          {/* Alerts Tab */}
          <TabsContent value="alerts" className="mt-6">
            <Card>
              <CardHeader>
                <CardTitle>Active Alerts</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {/* Delayed Orders */}
                  {activeOrders
                    .filter((o) => {
                      const age = getOrderAge(o);
                      return age.includes('hour') || parseInt(age) > 30;
                    })
                    .map((order) => (
                      <div key={order.id} className="flex items-start gap-3 rounded-lg bg-red-50 p-3">
                        <AlertCircle className="mt-0.5 h-5 w-5 text-red-600" />
                        <div className="flex-1">
                          <p className="font-medium">Order Delayed</p>
                          <p className="text-sm text-gray-600">
                            {formatOrderNumber(order.orderNumber)} - {getOrderAge(order)} old
                          </p>
                        </div>
                        <Button size="sm" variant="outline" onClick={() => handleOrderAction(order.id, 'view')}>
                          View
                        </Button>
                      </div>
                    ))}

                  {/* High Priority Orders */}
                  {activeOrders
                    .filter((o) => o.priority === 'high')
                    .map((order) => (
                      <div key={order.id} className="flex items-start gap-3 rounded-lg bg-yellow-50 p-3">
                        <AlertCircle className="mt-0.5 h-5 w-5 text-yellow-600" />
                        <div className="flex-1">
                          <p className="font-medium">High Priority Order</p>
                          <p className="text-sm text-gray-600">
                            {formatOrderNumber(order.orderNumber)} - {getStatusLabel(order.status)}
                          </p>
                        </div>
                        <Button size="sm" variant="outline" onClick={() => handleOrderAction(order.id, 'view')}>
                          View
                        </Button>
                      </div>
                    ))}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
            </Tabs>
          </Page.Content>
        </div>
      </Page>
    </AppLayout>
  );
}
