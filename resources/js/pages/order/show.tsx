import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';
import { router } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Edit, 
    XCircle, 
    CheckCircle, 
    Clock, 
    Package,
    Phone,
    Mail,
    MapPin,
    User,
    CreditCard,
    Printer,
    Share2,
    RefreshCw,
    ChefHat,
    Truck,
    AlertCircle,
    Calendar,
    DollarSign,
    FileText,
    MoreVertical,
    Copy,
    Download
} from 'lucide-react';
import type { OrderDetailPageProps, Order, OrderStatusHistory, PaymentTransaction } from '@/types/modules/order';
import { 
    getStatusColor, 
    getStatusLabel, 
    getStatusIcon,
    getTypeLabel,
    getPaymentStatusLabel,
    getPaymentStatusColor,
    formatCurrency,
    formatOrderNumber,
    formatPhone,
    getOrderAge,
    formatDuration,
    getKitchenStatusLabel,
    getKitchenStatusColor
} from '@/types/modules/order/utils';
import { ORDER_STATUS_CONFIG, PAYMENT_METHOD_CONFIG } from '@/types/modules/order/constants';

// Order Timeline Component
const OrderTimeline = ({ 
    order, 
    statusHistory 
}: { 
    order: Order; 
    statusHistory?: OrderStatusHistory[] 
}) => {
    const statuses = ['placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed'];
    const currentStatusIndex = statuses.indexOf(order.status);

    return (
        <div className="relative">
            {statuses.map((status, index) => {
                const isPast = index <= currentStatusIndex;
                const isCurrent = status === order.status;
                const statusInfo = ORDER_STATUS_CONFIG[status as keyof typeof ORDER_STATUS_CONFIG];
                const timestamp = order[`${status}_at` as keyof Order] as string | undefined;

                return (
                    <div key={status} className="flex items-start mb-4 last:mb-0">
                        {/* Timeline line */}
                        {index < statuses.length - 1 && (
                            <div className={`absolute left-4 top-8 w-0.5 h-16 ${
                                isPast ? 'bg-primary' : 'bg-gray-200'
                            }`} />
                        )}

                        {/* Status circle */}
                        <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center ${
                            isPast ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'
                        } ${isCurrent ? 'ring-4 ring-primary/20' : ''}`}>
                            {isPast ? (
                                <CheckCircle className="w-4 h-4" />
                            ) : (
                                <span className="text-xs">{index + 1}</span>
                            )}
                        </div>

                        {/* Status info */}
                        <div className="ml-4 flex-1">
                            <div className="flex items-center justify-between">
                                <h4 className={`font-medium ${isPast ? 'text-gray-900' : 'text-gray-400'}`}>
                                    {getStatusLabel(status as any)}
                                </h4>
                                {timestamp && (
                                    <span className="text-sm text-gray-500">
                                        {new Date(timestamp).toLocaleString()}
                                    </span>
                                )}
                            </div>
                            {isCurrent && (
                                <p className="text-sm text-primary mt-1">Current Status</p>
                            )}
                        </div>
                    </div>
                );
            })}

            {/* Cancelled status (if applicable) */}
            {order.status === 'cancelled' && (
                <div className="flex items-start">
                    <div className="relative z-10 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center">
                        <XCircle className="w-4 h-4" />
                    </div>
                    <div className="ml-4 flex-1">
                        <h4 className="font-medium text-red-600">Cancelled</h4>
                        {order.cancelled_at && (
                            <p className="text-sm text-gray-500">
                                {new Date(order.cancelled_at).toLocaleString()}
                            </p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
};

// Payment History Component
const PaymentHistory = ({ payments }: { payments: PaymentTransaction[] }) => {
    if (!payments || payments.length === 0) {
        return (
            <div className="text-center py-4 text-gray-500">
                No payment transactions yet
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {payments.map((payment) => {
                const methodConfig = PAYMENT_METHOD_CONFIG[payment.method];
                return (
                    <div key={payment.id} className="flex items-center justify-between p-3 rounded-lg bg-gray-50">
                        <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-lg ${
                                payment.status === 'completed' ? 'bg-green-100' : 'bg-gray-100'
                            }`}>
                                <CreditCard className="w-4 h-4" />
                            </div>
                            <div>
                                <p className="font-medium">{methodConfig?.label || payment.method}</p>
                                <p className="text-sm text-gray-500">
                                    {payment.reference_number || 'No reference'}
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="font-medium">{formatCurrency(payment.amount)}</p>
                            <Badge 
                                variant={payment.status === 'completed' ? 'default' : 'secondary'}
                                className="text-xs"
                            >
                                {payment.status}
                            </Badge>
                        </div>
                    </div>
                );
            })}
        </div>
    );
};

export default function ShowOrder({ 
    order: orderData,
    user,
    location,
    payments = [],
    offers = [],
    isPaid,
    remainingAmount,
    statusHistory = []
}: OrderDetailPageProps) {
    const [isRefreshing, setIsRefreshing] = useState(false);
    const order = orderData as Order;

    const handleStatusUpdate = (action: string) => {
        router.post(`/orders/${order.id}/${action}`, {}, {
            preserveScroll: true,
        });
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            preserveScroll: true,
            onFinish: () => setIsRefreshing(false),
        });
    };

    const handleCopyOrderNumber = () => {
        navigator.clipboard.writeText(order.order_number);
        // TODO: Show toast notification
    };

    const canEdit = ['draft', 'placed'].includes(order.status);
    const canCancel = ['draft', 'placed', 'confirmed'].includes(order.status);
    const showDeliveryInfo = order.type === 'delivery';
    const showTableInfo = order.type === 'dine_in';

    return (
        <AppLayout>
            <Head title={`Order ${formatOrderNumber(order.order_number)}`} />

            <div className="container mx-auto p-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Main Content */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Header */}
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <Link href="/orders" className="text-gray-600 hover:text-gray-900">
                                    <ArrowLeft className="w-5 h-5" />
                                </Link>
                                <div>
                                    <div className="flex items-center gap-2">
                                        <h1 className="text-2xl font-bold">
                                            {formatOrderNumber(order.order_number)}
                                        </h1>
                                        <button
                                            onClick={handleCopyOrderNumber}
                                            className="text-gray-400 hover:text-gray-600"
                                            title="Copy order number"
                                        >
                                            <Copy className="w-4 h-4" />
                                        </button>
                                    </div>
                                    <div className="flex items-center gap-4 text-sm text-gray-500 mt-1">
                                        <span>{getTypeLabel(order.type)}</span>
                                        <span>•</span>
                                        <span>{getOrderAge(order)}</span>
                                        {order.priority === 'high' && (
                                            <>
                                                <span>•</span>
                                                <Badge variant="destructive" className="text-xs">
                                                    <AlertCircle className="w-3 h-3 mr-1" />
                                                    High Priority
                                                </Badge>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex items-center gap-2">
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    onClick={handleRefresh}
                                    disabled={isRefreshing}
                                >
                                    <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                </Button>
                                <Button variant="ghost" size="icon">
                                    <Share2 className="w-4 h-4" />
                                </Button>
                                <Button variant="ghost" size="icon">
                                    <MoreVertical className="w-4 h-4" />
                                </Button>
                            </div>
                        </div>

                        {/* Order Details Card */}
                        <Card>
                            <CardHeader className="px-6 pt-6">
                                <div className="flex items-center justify-between">
                                    <CardTitle>Order Details</CardTitle>
                                    <Badge className={getStatusColor(order.status)}>
                                        {getStatusLabel(order.status)}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6 space-y-4">
                                {/* Customer Information */}
                                <div>
                                    <h3 className="font-medium mb-3">Customer Information</h3>
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="flex items-start gap-3">
                                            <User className="w-4 h-4 text-gray-400 mt-0.5" />
                                            <div>
                                                <p className="font-medium">{order.customer_name || 'Walk-in Customer'}</p>
                                                <p className="text-sm text-gray-500">Customer</p>
                                            </div>
                                        </div>
                                        {order.customer_phone && (
                                            <div className="flex items-start gap-3">
                                                <Phone className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="font-medium">{formatPhone(order.customer_phone)}</p>
                                                    <p className="text-sm text-gray-500">Phone</p>
                                                </div>
                                            </div>
                                        )}
                                        {order.customer_email && (
                                            <div className="flex items-start gap-3">
                                                <Mail className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="font-medium">{order.customer_email}</p>
                                                    <p className="text-sm text-gray-500">Email</p>
                                                </div>
                                            </div>
                                        )}
                                        {showTableInfo && order.table_number && (
                                            <div className="flex items-start gap-3">
                                                <Calendar className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="font-medium">Table {order.table_number}</p>
                                                    <p className="text-sm text-gray-500">Seating</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Delivery Address */}
                                {showDeliveryInfo && order.delivery_address && (
                                    <>
                                        <Separator />
                                        <div>
                                            <h3 className="font-medium mb-3">Delivery Information</h3>
                                            <div className="flex items-start gap-3">
                                                <MapPin className="w-4 h-4 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="font-medium">Delivery Address</p>
                                                    <p className="text-sm text-gray-600 mt-1">
                                                        {order.delivery_address}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </>
                                )}

                                {/* Notes */}
                                {(order.notes || order.special_instructions) && (
                                    <>
                                        <Separator />
                                        <div>
                                            <h3 className="font-medium mb-3">Notes & Instructions</h3>
                                            {order.notes && (
                                                <div className="mb-3">
                                                    <p className="text-sm font-medium text-gray-500">Order Notes</p>
                                                    <p className="text-sm mt-1">{order.notes}</p>
                                                </div>
                                            )}
                                            {order.special_instructions && (
                                                <div className="p-3 bg-yellow-50 rounded-lg">
                                                    <div className="flex items-start gap-2">
                                                        <ChefHat className="w-4 h-4 text-yellow-600 mt-0.5" />
                                                        <div>
                                                            <p className="text-sm font-medium text-yellow-800">
                                                                Kitchen Instructions
                                                            </p>
                                                            <p className="text-sm text-yellow-700 mt-1">
                                                                {order.special_instructions}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Order Items */}
                        <Card>
                            <CardHeader className="px-6 pt-6">
                                <div className="flex items-center justify-between">
                                    <CardTitle>Order Items</CardTitle>
                                    <span className="text-sm text-gray-500">
                                        {order.items.length} {order.items.length === 1 ? 'item' : 'items'}
                                    </span>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                <div className="space-y-4">
                                    {order.items.map((item, index) => (
                                        <div key={item.id}>
                                            {index > 0 && <Separator className="mb-4" />}
                                            <div className="flex items-start justify-between">
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-3">
                                                        <span className="font-medium">
                                                            {item.quantity}x {item.item_name}
                                                        </span>
                                                        <Badge 
                                                            variant="outline"
                                                            className={getKitchenStatusColor(item.kitchen_status)}
                                                        >
                                                            {getKitchenStatusLabel(item.kitchen_status)}
                                                        </Badge>
                                                        {item.course && (
                                                            <Badge variant="secondary" className="text-xs">
                                                                {item.course}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                    {item.modifiers && item.modifiers.length > 0 && (
                                                        <div className="mt-2 text-sm text-gray-600">
                                                            <span className="font-medium">Modifiers:</span>{' '}
                                                            {item.modifiers.map((mod, idx) => (
                                                                <span key={idx}>
                                                                    {mod.modifier_name} (+{formatCurrency(mod.price)})
                                                                    {idx < item.modifiers.length - 1 && ', '}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    )}
                                                    {item.notes && (
                                                        <p className="mt-2 text-sm text-gray-500 italic">
                                                            Note: {item.notes}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="text-right">
                                                    <p className="font-medium">{formatCurrency(item.total_price)}</p>
                                                    <p className="text-sm text-gray-500">
                                                        {formatCurrency(item.unit_price)} each
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Financial Summary */}
                                <Separator className="my-4" />
                                <div className="space-y-2">
                                    <div className="flex justify-between text-sm">
                                        <span>Subtotal</span>
                                        <span>{formatCurrency(order.subtotal)}</span>
                                    </div>
                                    {order.tax_amount > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span>Tax (19%)</span>
                                            <span>{formatCurrency(order.tax_amount)}</span>
                                        </div>
                                    )}
                                    {order.tip_amount > 0 && (
                                        <div className="flex justify-between text-sm">
                                            <span>Tip</span>
                                            <span>{formatCurrency(order.tip_amount)}</span>
                                        </div>
                                    )}
                                    {order.discount_amount > 0 && (
                                        <div className="flex justify-between text-sm text-green-600">
                                            <span>Discount</span>
                                            <span>-{formatCurrency(order.discount_amount)}</span>
                                        </div>
                                    )}
                                    <Separator />
                                    <div className="flex justify-between font-semibold text-lg">
                                        <span>Total</span>
                                        <span>{formatCurrency(order.total_amount)}</span>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Payment History */}
                        <Card>
                            <CardHeader className="px-6 pt-6">
                                <div className="flex items-center justify-between">
                                    <CardTitle>Payment Information</CardTitle>
                                    <Badge className={getPaymentStatusColor(order.payment_status)}>
                                        {getPaymentStatusLabel(order.payment_status)}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="p-6">
                                {!isPaid && (
                                    <div className="mb-4 p-4 bg-yellow-50 rounded-lg">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center gap-2">
                                                <DollarSign className="w-5 h-5 text-yellow-600" />
                                                <div>
                                                    <p className="font-medium text-yellow-800">Payment Required</p>
                                                    <p className="text-sm text-yellow-700">
                                                        Remaining amount: {formatCurrency(remainingAmount)}
                                                    </p>
                                                </div>
                                            </div>
                                            <Link href={`/orders/${order.id}/payment`}>
                                                <Button size="sm" variant="default">
                                                    Process Payment
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                )}
                                <PaymentHistory payments={payments} />
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Status Timeline */}
                        <Card>
                            <CardHeader className="px-6 pt-6">
                                <CardTitle>Order Timeline</CardTitle>
                            </CardHeader>
                            <CardContent className="p-6">
                                <OrderTimeline order={order} statusHistory={statusHistory} />
                            </CardContent>
                        </Card>

                        {/* Quick Actions */}
                        <Card>
                            <CardHeader className="px-6 pt-6">
                                <CardTitle>Quick Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="p-6 space-y-2">
                                {/* Status Update Actions */}
                                {order.status === 'draft' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('place')}
                                    >
                                        <Package className="w-4 h-4 mr-2" />
                                        Place Order
                                    </Button>
                                )}
                                {order.status === 'placed' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('confirm')}
                                    >
                                        <CheckCircle className="w-4 h-4 mr-2" />
                                        Confirm Order
                                    </Button>
                                )}
                                {order.status === 'confirmed' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('start-preparing')}
                                    >
                                        <ChefHat className="w-4 h-4 mr-2" />
                                        Start Preparing
                                    </Button>
                                )}
                                {order.status === 'preparing' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('mark-ready')}
                                    >
                                        <CheckCircle className="w-4 h-4 mr-2" />
                                        Mark as Ready
                                    </Button>
                                )}
                                {order.status === 'ready' && order.type === 'delivery' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('start-delivery')}
                                    >
                                        <Truck className="w-4 h-4 mr-2" />
                                        Start Delivery
                                    </Button>
                                )}
                                {order.status === 'ready' && order.type !== 'delivery' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('complete')}
                                    >
                                        <CheckCircle className="w-4 h-4 mr-2" />
                                        Complete Order
                                    </Button>
                                )}
                                {order.status === 'delivering' && (
                                    <Button 
                                        className="w-full"
                                        onClick={() => handleStatusUpdate('mark-delivered')}
                                    >
                                        <CheckCircle className="w-4 h-4 mr-2" />
                                        Mark as Delivered
                                    </Button>
                                )}

                                <Separator />

                                {/* Other Actions */}
                                {canEdit && (
                                    <Link href={`/orders/${order.id}/edit`} className="block">
                                        <Button variant="outline" className="w-full">
                                            <Edit className="w-4 h-4 mr-2" />
                                            Edit Order
                                        </Button>
                                    </Link>
                                )}

                                <Link href={`/orders/${order.id}/receipt`} className="block">
                                    <Button variant="outline" className="w-full">
                                        <Printer className="w-4 h-4 mr-2" />
                                        Print Receipt
                                    </Button>
                                </Link>

                                <Button variant="outline" className="w-full">
                                    <FileText className="w-4 h-4 mr-2" />
                                    View Invoice
                                </Button>

                                <Button variant="outline" className="w-full">
                                    <Download className="w-4 h-4 mr-2" />
                                    Export PDF
                                </Button>

                                {canCancel && (
                                    <>
                                        <Separator />
                                        <Link href={`/orders/${order.id}/cancel`} className="block">
                                            <Button variant="destructive" className="w-full">
                                                <XCircle className="w-4 h-4 mr-2" />
                                                Cancel Order
                                            </Button>
                                        </Link>
                                    </>
                                )}
                            </CardContent>
                        </Card>

                        {/* Order Info */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Order Information</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Order Number</span>
                                    <span className="font-medium">{order.order_number}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Location</span>
                                    <span className="font-medium">{location?.name || 'N/A'}</span>
                                </div>
                                {order.waiter && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Waiter</span>
                                        <span className="font-medium">{order.waiter.name}</span>
                                    </div>
                                )}
                                <div className="flex justify-between">
                                    <span className="text-gray-500">Created</span>
                                    <span className="font-medium">
                                        {new Date(order.created_at).toLocaleString()}
                                    </span>
                                </div>
                                {order.scheduled_at && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500">Scheduled For</span>
                                        <span className="font-medium">
                                            {new Date(order.scheduled_at).toLocaleString()}
                                        </span>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}