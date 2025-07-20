import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Clock, CheckCircle, XCircle } from 'lucide-react';
import { router } from '@inertiajs/react';

interface OrderItem {
    id: number;
    itemName: string;
    quantity: number;
    status: string;
    notes?: string;
}

interface Order {
    id: number;
    status: string;
    customerName?: string;
    items: OrderItem[];
    placedAt?: string;
    createdAt: string;
}

interface Props {
    orders: Order[];
    locationId: number;
}

const OrderCard = ({ order }: { order: Order }) => {
    const orderTime = new Date(order.placedAt || order.createdAt);
    const elapsedMinutes = Math.floor((Date.now() - orderTime.getTime()) / 60000);

    const handleStatusUpdate = (status: string) => {
        router.post(`/orders/${order.id}/${status}`, {}, {
            preserveScroll: true,
        });
    };

    const getStatusColor = () => {
        switch (order.status) {
            case 'confirmed':
                return 'bg-yellow-100 border-yellow-300';
            case 'preparing':
                return 'bg-orange-100 border-orange-300';
            case 'ready':
                return 'bg-green-100 border-green-300';
            default:
                return 'bg-gray-100 border-gray-300';
        }
    };

    return (
        <Card className={`border-2 ${getStatusColor()}`}>
            <CardHeader className="pb-3">
                <div className="flex justify-between items-start">
                    <div>
                        <CardTitle className="text-xl">Order #{order.id}</CardTitle>
                        {order.customerName && (
                            <p className="text-sm text-gray-600 mt-1">{order.customerName}</p>
                        )}
                    </div>
                    <div className="text-right">
                        <Badge variant="outline" className="mb-2">
                            <Clock className="w-3 h-3 mr-1" />
                            {elapsedMinutes} min
                        </Badge>
                        <p className="text-xs text-gray-500">
                            {orderTime.toLocaleTimeString()}
                        </p>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-2 mb-4">
                    {order.items.map((item) => (
                        <div
                            key={item.id}
                            className={`flex justify-between items-center p-2 rounded ${
                                item.status === 'prepared' ? 'bg-green-50 line-through' : 'bg-white'
                            }`}
                        >
                            <div>
                                <span className="font-medium">{item.quantity}x</span>{' '}
                                <span>{item.itemName}</span>
                                {item.notes && (
                                    <p className="text-sm text-gray-500 italic">{item.notes}</p>
                                )}
                            </div>
                            {item.status === 'prepared' && (
                                <CheckCircle className="w-5 h-5 text-green-600" />
                            )}
                        </div>
                    ))}
                </div>

                <div className="flex gap-2">
                    {order.status === 'confirmed' && (
                        <Button
                            size="sm"
                            className="flex-1"
                            onClick={() => handleStatusUpdate('start-preparing')}
                        >
                            Start Preparing
                        </Button>
                    )}
                    {order.status === 'preparing' && (
                        <Button
                            size="sm"
                            className="flex-1"
                            variant="default"
                            onClick={() => handleStatusUpdate('mark-ready')}
                        >
                            Mark Ready
                        </Button>
                    )}
                    {order.status === 'ready' && (
                        <Button
                            size="sm"
                            className="flex-1"
                            variant="secondary"
                            onClick={() => handleStatusUpdate('complete')}
                        >
                            Complete
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
};

export default function KitchenDisplay({ orders, locationId }: Props) {
    const confirmedOrders = orders.filter(o => o.status === 'confirmed');
    const preparingOrders = orders.filter(o => o.status === 'preparing');
    const readyOrders = orders.filter(o => o.status === 'ready');

    return (
        <AppLayout>
            <Head title="Kitchen Display" />

            <div className="container-fluid mx-auto p-4">
                <div className="flex justify-between items-center mb-6">
                    <h1 className="text-3xl font-bold">Kitchen Display - Location {locationId}</h1>
                    <div className="flex gap-4">
                        <Badge variant="secondary" className="text-lg px-4 py-2">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                Confirmed: {confirmedOrders.length}
                            </div>
                        </Badge>
                        <Badge variant="secondary" className="text-lg px-4 py-2">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 bg-orange-500 rounded-full"></div>
                                Preparing: {preparingOrders.length}
                            </div>
                        </Badge>
                        <Badge variant="secondary" className="text-lg px-4 py-2">
                            <div className="flex items-center gap-2">
                                <div className="w-3 h-3 bg-green-500 rounded-full"></div>
                                Ready: {readyOrders.length}
                            </div>
                        </Badge>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Confirmed Orders */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4 text-yellow-700">
                            Confirmed Orders
                        </h2>
                        <div className="space-y-4">
                            {confirmedOrders.length === 0 ? (
                                <Card className="border-dashed">
                                    <CardContent className="text-center py-8 text-gray-500">
                                        No confirmed orders
                                    </CardContent>
                                </Card>
                            ) : (
                                confirmedOrders.map(order => (
                                    <OrderCard key={order.id} order={order} />
                                ))
                            )}
                        </div>
                    </div>

                    {/* Preparing Orders */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4 text-orange-700">
                            Preparing
                        </h2>
                        <div className="space-y-4">
                            {preparingOrders.length === 0 ? (
                                <Card className="border-dashed">
                                    <CardContent className="text-center py-8 text-gray-500">
                                        No orders being prepared
                                    </CardContent>
                                </Card>
                            ) : (
                                preparingOrders.map(order => (
                                    <OrderCard key={order.id} order={order} />
                                ))
                            )}
                        </div>
                    </div>

                    {/* Ready Orders */}
                    <div>
                        <h2 className="text-xl font-semibold mb-4 text-green-700">
                            Ready for Pickup
                        </h2>
                        <div className="space-y-4">
                            {readyOrders.length === 0 ? (
                                <Card className="border-dashed">
                                    <CardContent className="text-center py-8 text-gray-500">
                                        No orders ready
                                    </CardContent>
                                </Card>
                            ) : (
                                readyOrders.map(order => (
                                    <OrderCard key={order.id} order={order} />
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}