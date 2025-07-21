import { Head } from '@inertiajs/react';
import { useState, useEffect, useCallback, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { router } from '@inertiajs/react';
import { 
    Activity,
    AlertCircle,
    Bell,
    ChefHat,
    Clock,
    Grid,
    Home,
    List,
    MapPin,
    Monitor,
    Package,
    Phone,
    RefreshCw,
    Settings,
    ShoppingCart,
    Timer,
    Truck,
    Users,
    Volume2,
    VolumeX,
    Maximize2,
    Minimize2,
    Eye,
    EyeOff
} from 'lucide-react';
import type { Order } from '@/types/modules/order';
import { 
    formatCurrency, 
    getStatusColor, 
    getStatusLabel,
    getTypeLabel,
    formatOrderNumber,
    getOrderAge,
    getKitchenStatusLabel,
    getKitchenStatusColor
} from '@/types/modules/order/utils';

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
    onAction 
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
            <div className="flex items-center justify-between p-4 hover:bg-gray-50 border-b">
                <div className="flex items-center gap-4">
                    <div className={`w-2 h-12 rounded-full ${urgencyColor}`} />
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
                    <Badge className={getStatusColor(order.status)}>
                        {getStatusLabel(order.status)}
                    </Badge>
                    <span className="font-medium">{formatCurrency(order.totalAmount)}</span>
                    <Button 
                        size="sm" 
                        variant="outline"
                        onClick={() => onAction?.(String(order.id), 'view')}
                    >
                        View
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <Card className="hover:shadow-lg transition-shadow cursor-pointer">
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                    <div>
                        <CardTitle className="text-lg">
                            {formatOrderNumber(order.orderNumber)}
                        </CardTitle>
                        <div className="flex items-center gap-2 mt-1">
                            <Badge variant="outline" className="text-xs">
                                {getTypeLabel(order.type)}
                            </Badge>
                            {order.priority === 'high' && (
                                <Badge variant="destructive" className="text-xs">
                                    <AlertCircle className="w-3 h-3 mr-1" />
                                    Priority
                                </Badge>
                            )}
                        </div>
                    </div>
                    <div className={`w-3 h-3 rounded-full ${urgencyColor}`} />
                </div>
            </CardHeader>
            <CardContent className="space-y-3">
                <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-500">Customer</span>
                    <span className="text-sm font-medium">
                        {order.customerName || 'Walk-in'}
                    </span>
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
                <div className="pt-3 border-t">
                    <div className="flex items-center justify-between mb-3">
                        <Badge className={getStatusColor(order.status)}>
                            {getStatusLabel(order.status)}
                        </Badge>
                        <span className="font-semibold">
                            {formatCurrency(order.total_amount)}
                        </span>
                    </div>
                    <Button 
                        className="w-full" 
                        size="sm"
                        onClick={() => onAction?.(order.id, 'next')}
                    >
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
            confirmed: orders.filter(o => o.status === 'confirmed'),
            preparing: orders.filter(o => o.status === 'preparing'),
            ready: orders.filter(o => o.status === 'ready')
        };
    }, [orders]);

    return (
        <div className="grid grid-cols-3 gap-6 h-full">
            {/* Confirmed Orders */}
            <div className="space-y-4">
                <div className="flex items-center justify-between">
                    <h3 className="font-semibold text-lg">New Orders</h3>
                    <Badge variant="secondary">{ordersByStatus.confirmed.length}</Badge>
                </div>
                <ScrollArea className="h-[calc(100vh-250px)]">
                    <div className="space-y-3 pr-4">
                        {ordersByStatus.confirmed.map(order => (
                            <Card key={order.id} className="bg-blue-50 border-blue-200">
                                <CardContent className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="font-bold text-lg">
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
                                                    <div className="text-xs text-gray-600 ml-4">
                                                        {item.modifiers.map(m => m.modifier_name).join(', ')}
                                                    </div>
                                                )}
                                                {item.notes && (
                                                    <div className="text-xs text-red-600 ml-4 font-medium">
                                                        Note: {item.notes}
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                    {order.special_instructions && (
                                        <div className="mt-3 p-2 bg-yellow-100 rounded text-sm">
                                            <ChefHat className="w-4 h-4 inline mr-1" />
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
                    <h3 className="font-semibold text-lg">Preparing</h3>
                    <Badge variant="secondary">{ordersByStatus.preparing.length}</Badge>
                </div>
                <ScrollArea className="h-[calc(100vh-250px)]">
                    <div className="space-y-3 pr-4">
                        {ordersByStatus.preparing.map(order => (
                            <Card key={order.id} className="bg-orange-50 border-orange-200">
                                <CardContent className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="font-bold text-lg">
                                            {order.table_number ? `Table ${order.table_number}` : formatOrderNumber(order.order_number)}
                                        </span>
                                        <div className="flex items-center gap-2">
                                            <Timer className="w-4 h-4" />
                                            <span className="text-sm">{getOrderAge(order)}</span>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        {order.items.map((item, idx) => (
                                            <div key={idx} className="flex items-center justify-between text-sm">
                                                <span>
                                                    {item.quantity}x {item.item_name}
                                                </span>
                                                <Badge 
                                                    variant="outline" 
                                                    className={getKitchenStatusColor(item.kitchen_status)}
                                                >
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
                    <h3 className="font-semibold text-lg">Ready to Serve</h3>
                    <Badge variant="secondary">{ordersByStatus.ready.length}</Badge>
                </div>
                <ScrollArea className="h-[calc(100vh-250px)]">
                    <div className="space-y-3 pr-4">
                        {ordersByStatus.ready.map(order => (
                            <Card key={order.id} className="bg-green-50 border-green-200">
                                <CardContent className="p-6">
                                    <div className="flex items-center justify-between mb-2">
                                        <span className="font-bold text-lg">
                                            {order.table_number ? `Table ${order.table_number}` : formatOrderNumber(order.order_number)}
                                        </span>
                                        <Bell className="w-5 h-5 text-green-600 animate-pulse" />
                                    </div>
                                    <div className="space-y-1">
                                        <div className="text-sm text-gray-600">
                                            {order.type === 'dine_in' && order.waiter && (
                                                <span>Waiter: {order.waiter.name}</span>
                                            )}
                                            {order.type === 'delivery' && (
                                                <span>Delivery to: {order.delivery_address}</span>
                                            )}
                                            {order.type === 'takeout' && (
                                                <span>Customer: {order.customer_name}</span>
                                            )}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            Ready for: {getOrderAge(order)}
                                        </div>
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
export default function OperationsCenter({ 
    orders: initialOrders, 
    locations, 
    stats 
}: OperationsPageProps) {
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

    const handleOrderAction = useCallback((orderId: string, action: string) => {
        if (action === 'view') {
            router.visit(`/orders/${orderId}`);
        } else if (action === 'next') {
            // Handle status progression
            const order = orders.find(o => o.id === orderId);
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
                    route = order.type === 'delivery' 
                        ? `/orders/${orderId}/start-delivery`
                        : `/orders/${orderId}/complete`;
                    break;
            }

            if (route) {
                router.post(route, {}, {
                    preserveScroll: true,
                    onSuccess: () => {
                        if (soundEnabled) {
                            // Play notification sound
                            const audio = new Audio('/sounds/notification.mp3');
                            audio.play().catch(() => {});
                        }
                    }
                });
            }
        }
    }, [orders, soundEnabled]);

    const filteredOrders = useMemo(() => {
        if (selectedLocation === 'all') return orders;
        return orders.filter(order => order.location_id === parseInt(selectedLocation));
    }, [orders, selectedLocation]);

    const activeOrders = useMemo(() => {
        return filteredOrders.filter(o => 
            !['completed', 'cancelled', 'refunded'].includes(o.status)
        );
    }, [filteredOrders]);

    return (
        <AppLayout>
            <Head title="Operations Center" />

            <div className={`${fullscreen ? 'fixed inset-0 z-50 bg-white' : 'container mx-auto'} p-6`}>
                {/* Header */}
                <div className="flex items-center justify-between mb-6 px-4">
                    <div className="flex items-center gap-4">
                        <h1 className="text-2xl font-bold">Operations Center</h1>
                        <Badge variant="outline" className="text-lg">
                            <Activity className="w-4 h-4 mr-1 text-green-500 animate-pulse" />
                            Live
                        </Badge>
                    </div>
                    <div className="flex items-center gap-3">
                        {/* Location Filter */}
                        <Select value={selectedLocation} onValueChange={setSelectedLocation}>
                            <SelectTrigger className="w-48">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All Locations</SelectItem>
                                {locations.map(loc => (
                                    <SelectItem key={loc.id} value={loc.id.toString()}>
                                        {loc.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        {/* View Mode Toggle */}
                        <div className="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                            <Button
                                size="sm"
                                variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                onClick={() => setViewMode('grid')}
                            >
                                <Grid className="w-4 h-4" />
                            </Button>
                            <Button
                                size="sm"
                                variant={viewMode === 'list' ? 'default' : 'ghost'}
                                onClick={() => setViewMode('list')}
                            >
                                <List className="w-4 h-4" />
                            </Button>
                        </div>

                        {/* Controls */}
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => setSoundEnabled(!soundEnabled)}
                            title={soundEnabled ? 'Mute sounds' : 'Enable sounds'}
                        >
                            {soundEnabled ? <Volume2 className="w-4 h-4" /> : <VolumeX className="w-4 h-4" />}
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => setAutoRefresh(!autoRefresh)}
                            title={autoRefresh ? 'Disable auto-refresh' : 'Enable auto-refresh'}
                        >
                            {autoRefresh ? <Eye className="w-4 h-4" /> : <EyeOff className="w-4 h-4" />}
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={handleRefresh}
                            disabled={isRefreshing}
                        >
                            <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                        </Button>
                        <Button
                            variant="outline"
                            size="icon"
                            onClick={() => setFullscreen(!fullscreen)}
                        >
                            {fullscreen ? <Minimize2 className="w-4 h-4" /> : <Maximize2 className="w-4 h-4" />}
                        </Button>
                    </div>
                </div>

                {/* Stats Bar */}
                <div className="grid grid-cols-4 gap-4 mb-6 px-4">
                    <Card>
                        <CardContent className="p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600">Active Orders</p>
                                    <p className="text-2xl font-bold">{stats.active}</p>
                                </div>
                                <ShoppingCart className="w-8 h-8 text-blue-500" />
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
                                <ChefHat className="w-8 h-8 text-orange-500" />
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
                                <Package className="w-8 h-8 text-green-500" />
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
                                <Clock className="w-8 h-8 text-purple-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content */}
                <Tabs value={selectedView} onValueChange={setSelectedView} className="px-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="kitchen">Kitchen Display</TabsTrigger>
                        <TabsTrigger value="delivery">Delivery Tracker</TabsTrigger>
                        <TabsTrigger value="alerts">Alerts</TabsTrigger>
                    </TabsList>

                    {/* Overview Tab */}
                    <TabsContent value="overview" className="mt-6">
                        {viewMode === 'grid' ? (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                {activeOrders.map(order => (
                                    <OrderCard 
                                        key={order.id} 
                                        order={order} 
                                        onAction={handleOrderAction}
                                    />
                                ))}
                            </div>
                        ) : (
                            <Card>
                                <CardContent className="p-0">
                                    {activeOrders.map(order => (
                                        <OrderCard 
                                            key={order.id} 
                                            order={order} 
                                            view="list"
                                            onAction={handleOrderAction}
                                        />
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
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Active Deliveries */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Active Deliveries</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {activeOrders
                                            .filter(o => o.type === 'delivery' && o.status === 'delivering')
                                            .map(order => (
                                                <div key={order.id} className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                                    <div className="flex items-center gap-3">
                                                        <Truck className="w-5 h-5 text-blue-600" />
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
                                            .filter(o => o.type === 'delivery' && o.status === 'ready')
                                            .map(order => (
                                                <div key={order.id} className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
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
                                        .filter(o => {
                                            const age = getOrderAge(o);
                                            return age.includes('hour') || parseInt(age) > 30;
                                        })
                                        .map(order => (
                                            <div key={order.id} className="flex items-start gap-3 p-3 bg-red-50 rounded-lg">
                                                <AlertCircle className="w-5 h-5 text-red-600 mt-0.5" />
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
                                        .filter(o => o.priority === 'high')
                                        .map(order => (
                                            <div key={order.id} className="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
                                                <AlertCircle className="w-5 h-5 text-yellow-600 mt-0.5" />
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
            </div>
        </AppLayout>
    );
}