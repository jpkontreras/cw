import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { PageLayout, PageHeader, PageContent } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { router } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { 
    Edit, 
    XCircle, 
    CheckCircle, 
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
import type { OrderDetailPageProps, Order, PaymentTransaction } from '@/types/modules/order';
import { 
    getStatusColor, 
    getStatusLabel,
    getTypeLabel,
    formatCurrency,
    formatOrderNumber,
    formatPhone,
    getOrderAge,
    getKitchenStatusLabel,
    getKitchenStatusColor
} from '@/types/modules/order/utils';
import { PAYMENT_METHOD_CONFIG } from '@/types/modules/order/constants';

// Order Timeline Component
const OrderTimeline = ({ 
    order
}: { 
    order: Order;
}) => {
    const statuses = ['placed', 'confirmed', 'preparing', 'ready', 'delivering', 'delivered', 'completed'];
    const currentStatusIndex = statuses.indexOf(order.status);

    return (
        <div className="relative">
            {statuses.map((status, index) => {
                const isPast = index <= currentStatusIndex;
                const isCurrent = status === order.status;
                const timestamp = order[`${status}At` as keyof Order] as string | undefined;

                return (
                    <div key={status} className="flex items-start mb-6 last:mb-0">
                        {/* Timeline line */}
                        {index < statuses.length - 1 && (
                            <div 
                                className={`absolute left-4 w-0.5 ${
                                    isPast ? 'bg-primary' : 'bg-gray-200'
                                }`}
                                style={{ 
                                    top: '32px',
                                    height: 'calc(100% - 12px)'
                                }}
                            />
                        )}

                        {/* Status circle */}
                        <div className={`relative z-10 w-8 h-8 rounded-full flex items-center justify-center ${
                            isPast ? 'bg-primary text-white' : 'bg-gray-200 text-gray-400'
                        } ${isCurrent ? 'ring-4 ring-primary/20' : ''}`}>
                            {isPast ? (
                                <CheckCircle className="w-4 h-4" />
                            ) : (
                                <span className="text-xs font-medium">{index + 1}</span>
                            )}
                        </div>

                        {/* Status info */}
                        <div className="ml-4 flex-1 min-w-0">
                            <div className="flex items-start justify-between gap-2">
                                <div className="flex-1">
                                    <h4 className={`font-medium ${isPast ? 'text-gray-900' : 'text-gray-400'}`}>
                                        {getStatusLabel(status as any)}
                                    </h4>
                                    {timestamp && (
                                        <p className="text-sm text-gray-500 mt-1">
                                            {new Date(timestamp).toLocaleString('en-US', {
                                                day: 'numeric',
                                                month: 'short',
                                                year: 'numeric',
                                                hour: 'numeric',
                                                minute: '2-digit',
                                                hour12: true
                                            })}
                                        </p>
                                    )}
                                </div>
                                {isCurrent && (
                                    <Badge className="text-xs">
                                        Current
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}

            {/* Cancelled status (if applicable) */}
            {order.status === 'cancelled' && (
                <div className="flex items-start mt-6">
                    <div className="relative z-10 w-8 h-8 rounded-full bg-red-500 text-white flex items-center justify-center">
                        <XCircle className="w-4 h-4" />
                    </div>
                    <div className="ml-4 flex-1">
                        <h4 className="font-medium text-red-600">Cancelled</h4>
                        {order.cancelledAt && (
                            <p className="text-sm text-gray-500 mt-1">
                                {new Date(order.cancelledAt).toLocaleString('en-US', {
                                    day: 'numeric',
                                    month: 'short',
                                    year: 'numeric',
                                    hour: 'numeric',
                                    minute: '2-digit',
                                    hour12: true
                                })}
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
            <div className="text-center py-8 text-gray-500">
                <CreditCard className="mx-auto h-12 w-12 text-gray-300 mb-3" />
                <p className="text-base font-medium">No payment transactions yet</p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            {payments.map((payment) => {
                const methodConfig = PAYMENT_METHOD_CONFIG[payment.method as keyof typeof PAYMENT_METHOD_CONFIG];
                return (
                    <div key={payment.id} className="flex items-center justify-between p-4 rounded-lg bg-gray-50 border border-gray-200">
                        <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-lg ${
                                payment.status === 'completed' ? 'bg-green-100' : 'bg-gray-100'
                            }`}>
                                <CreditCard className="w-5 h-5" />
                            </div>
                            <div>
                                <p className="font-medium text-gray-900">{methodConfig?.label || payment.method}</p>
                                <p className="text-sm text-gray-500">
                                    {payment.referenceNumber || 'No reference'}
                                </p>
                            </div>
                        </div>
                        <div className="text-right">
                            <p className="text-lg font-semibold text-gray-900">{formatCurrency(payment.amount)}</p>
                            <Badge 
                                variant={payment.status === 'completed' ? 'default' : 'secondary'}
                                className="text-xs px-2 py-0.5"
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
    isPaid,
    remainingAmount
}: OrderDetailPageProps) {
    const [isRefreshing, setIsRefreshing] = useState(false);
    
    // Use the order data directly since it's now properly typed
    const order = orderData as Order;

    const handleStatusUpdate = (action: string) => {
        router.post(`/orders/${order.id}/${action}`, {}, {
            preserveScroll: true,
        });
    };

    const handleRefresh = () => {
        setIsRefreshing(true);
        router.reload({
            onFinish: () => setIsRefreshing(false),
        });
    };

    const handleCopyOrderNumber = () => {
        navigator.clipboard.writeText(order.orderNumber || '');
        // TODO: Show toast notification
    };

    const canEdit = ['draft', 'placed'].includes(order.status);
    const canCancel = ['draft', 'placed', 'confirmed'].includes(order.status);
    const showDeliveryInfo = order.type === 'delivery';
    const showTableInfo = order.type === 'dine_in';

    const orderNumber = formatOrderNumber(order.orderNumber);

    const headerDescription = `${getTypeLabel(order.type)} • ${getOrderAge(order)}`;

    return (
        <AppLayout>
            <Head title={`Order ${formatOrderNumber(order.orderNumber)}`} />
            
            <PageLayout>
                <PageHeader
                    title={orderNumber}
                    description={headerDescription}
                >
                    {/* Primary Action */}
                    {order.status === 'draft' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('place')}
                        >
                            <Package className="w-3.5 h-3.5 mr-1.5" />
                            Place Order
                        </Button>
                    )}
                    {order.status === 'placed' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('confirm')}
                        >
                            <CheckCircle className="w-3.5 h-3.5 mr-1.5" />
                            Confirm Order
                        </Button>
                    )}
                    {order.status === 'confirmed' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('start-preparing')}
                        >
                            <ChefHat className="w-3.5 h-3.5 mr-1.5" />
                            Start Preparing
                        </Button>
                    )}
                    {order.status === 'preparing' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('mark-ready')}
                        >
                            <CheckCircle className="w-3.5 h-3.5 mr-1.5" />
                            Mark as Ready
                        </Button>
                    )}
                    {order.status === 'ready' && order.type === 'delivery' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('start-delivery')}
                        >
                            <Truck className="w-3.5 h-3.5 mr-1.5" />
                            Start Delivery
                        </Button>
                    )}
                    {order.status === 'ready' && order.type !== 'delivery' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('complete')}
                        >
                            <CheckCircle className="w-3.5 h-3.5 mr-1.5" />
                            Complete Order
                        </Button>
                    )}
                    {order.status === 'delivering' && (
                        <Button 
                            size="sm"
                            onClick={() => handleStatusUpdate('mark-delivered')}
                        >
                            <CheckCircle className="w-3.5 h-3.5 mr-1.5" />
                            Mark as Delivered
                        </Button>
                    )}
                    
                    {/* Secondary Actions */}
                    {canEdit && (
                        <Link href={`/orders/${order.id}/edit`}>
                            <Button variant="outline" size="sm">
                                <Edit className="w-3.5 h-3.5 mr-1.5" />
                                Edit
                            </Button>
                        </Link>
                    )}
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                                <MoreVertical className="w-4 h-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" className="w-48">
                            <DropdownMenuItem onClick={handleCopyOrderNumber}>
                                <Copy className="w-3.5 h-3.5 mr-2" />
                                Copy Order Number
                            </DropdownMenuItem>
                            <DropdownMenuItem onClick={handleRefresh} disabled={isRefreshing}>
                                <RefreshCw className={`w-3.5 h-3.5 mr-2 ${isRefreshing ? 'animate-spin' : ''}`} />
                                Refresh
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                <Share2 className="w-3.5 h-3.5 mr-2" />
                                Share
                            </DropdownMenuItem>
                            <DropdownMenuSeparator />
                            <Link href={`/orders/${order.id}/receipt`}>
                                <DropdownMenuItem>
                                    <Printer className="w-3.5 h-3.5 mr-2" />
                                    Print Receipt
                                </DropdownMenuItem>
                            </Link>
                            <DropdownMenuItem>
                                <FileText className="w-3.5 h-3.5 mr-2" />
                                View Invoice
                            </DropdownMenuItem>
                            <DropdownMenuItem>
                                <Download className="w-3.5 h-3.5 mr-2" />
                                Export PDF
                            </DropdownMenuItem>
                            {canCancel && (
                                <>
                                    <DropdownMenuSeparator />
                                    <Link href={`/orders/${order.id}/cancel`}>
                                        <DropdownMenuItem variant="destructive">
                                            <XCircle className="w-3.5 h-3.5 mr-2" />
                                            Cancel Order
                                        </DropdownMenuItem>
                                    </Link>
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                </PageHeader>

                <PageContent>
                    {/* Status Badges */}
                    <div className="flex flex-wrap items-center gap-3 mb-6">
                        <Badge className={`${getStatusColor(order.status)} px-4 py-1.5 text-sm font-medium`}>
                            {getStatusLabel(order.status)}
                        </Badge>
                        
                        {order.priority === 'high' && (
                            <Badge variant="destructive" className="px-4 py-1.5 text-sm font-medium">
                                <AlertCircle className="w-4 h-4 mr-1.5" />
                                High Priority
                            </Badge>
                        )}
                        
                        {order.paymentStatus === 'paid' && (
                            <Badge className="bg-green-500 hover:bg-green-600 px-4 py-1.5 text-sm font-medium">
                                <CheckCircle className="w-4 h-4 mr-1.5" />
                                Paid
                            </Badge>
                        )}
                    </div>
                    
                    <Card className="overflow-hidden">
                        <Tabs defaultValue="details" className="w-full">
                            <div className="border-b bg-gray-50/50">
                                <TabsList className="w-full justify-start rounded-none bg-transparent p-0 h-auto">
                                    <TabsTrigger 
                                        value="details" 
                                        className="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-white px-6 py-3 text-sm font-medium"
                                    >
                                        Details
                                    </TabsTrigger>
                                    <TabsTrigger 
                                        value="items" 
                                        className="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-white px-6 py-3 text-sm font-medium"
                                    >
                                        Items
                                    </TabsTrigger>
                                    <TabsTrigger 
                                        value="payment" 
                                        className="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-white px-6 py-3 text-sm font-medium"
                                    >
                                        Payment
                                    </TabsTrigger>
                                    <TabsTrigger 
                                        value="info" 
                                        className="rounded-none border-b-2 border-transparent data-[state=active]:border-primary data-[state=active]:bg-white px-6 py-3 text-sm font-medium"
                                    >
                                        Info
                                    </TabsTrigger>
                                </TabsList>
                            </div>
                                
                            <TabsContent value="details" className="m-0 p-0">
                                <div className="grid grid-cols-1 lg:grid-cols-3">
                                    {/* Main Content */}
                                    <div className="lg:col-span-2 p-6 space-y-8">
                                        {/* Customer Information */}
                                        <div>
                                            <h3 className="text-lg font-semibold mb-4">Customer Information</h3>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                <div className="flex items-start gap-3">
                                                    <div className="p-2 bg-gray-100 rounded-lg">
                                                        <User className="w-5 h-5 text-gray-600" />
                                                    </div>
                                                    <div>
                                                        <p className="font-medium text-gray-900">{order.customerName || 'Walk-in Customer'}</p>
                                                        <p className="text-sm text-gray-500">Customer</p>
                                                    </div>
                                                </div>
                                                {order.customerPhone && (
                                                    <div className="flex items-start gap-3">
                                                        <div className="p-2 bg-gray-100 rounded-lg">
                                                            <Phone className="w-5 h-5 text-gray-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900">{formatPhone(order.customerPhone)}</p>
                                                            <p className="text-sm text-gray-500">Phone</p>
                                                        </div>
                                                    </div>
                                                )}
                                                {order.customerEmail && (
                                                    <div className="flex items-start gap-3">
                                                        <div className="p-2 bg-gray-100 rounded-lg">
                                                            <Mail className="w-5 h-5 text-gray-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900">{order.customerEmail}</p>
                                                            <p className="text-sm text-gray-500">Email</p>
                                                        </div>
                                                    </div>
                                                )}
                                                {showTableInfo && order.tableNumber && (
                                                    <div className="flex items-start gap-3">
                                                        <div className="p-2 bg-gray-100 rounded-lg">
                                                            <Calendar className="w-5 h-5 text-gray-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900">Table {order.tableNumber}</p>
                                                            <p className="text-sm text-gray-500">Seating</p>
                                                        </div>
                                                    </div>
                                                )}
                                                </div>
                                            </div>

                                        {/* Delivery Address */}
                                        {showDeliveryInfo && order.deliveryAddress && (
                                            <>
                                                <Separator />
                                                <div>
                                                    <h3 className="text-lg font-semibold mb-4">Delivery Information</h3>
                                                    <div className="flex items-start gap-3">
                                                        <div className="p-2 bg-gray-100 rounded-lg">
                                                            <MapPin className="w-5 h-5 text-gray-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900">Delivery Address</p>
                                                            <p className="text-gray-600 mt-1">
                                                                {order.deliveryAddress}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </>
                                            )}

                                        {/* Notes */}
                                        {(order.notes || order.specialInstructions) && (
                                            <>
                                                <Separator />
                                                <div>
                                                    <h3 className="text-lg font-semibold mb-4">Notes & Instructions</h3>
                                                    {order.notes && (
                                                        <div className="mb-4">
                                                            <p className="font-medium text-gray-700 mb-2">Order Notes</p>
                                                            <p className="text-gray-600">{order.notes}</p>
                                                        </div>
                                                    )}
                                                    {order.specialInstructions && (
                                                        <div className="p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                                            <div className="flex items-start gap-3">
                                                                <ChefHat className="w-5 h-5 text-amber-600 mt-0.5" />
                                                                <div>
                                                                    <p className="font-medium text-amber-900">
                                                                        Kitchen Instructions
                                                                    </p>
                                                                    <p className="text-amber-800 mt-1">
                                                                        {order.specialInstructions}
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            </>
                                            )}
                                        </div>

                                    {/* Timeline Sidebar */}
                                    <div className="lg:col-span-1 bg-gray-50 p-6 border-l">
                                        <h3 className="text-lg font-semibold mb-6">Order Timeline</h3>
                                        <OrderTimeline order={order} />
                                    </div>
                                </div>
                            </TabsContent>

                            <TabsContent value="items" className="m-0 p-8">
                                <div className="max-w-4xl mx-auto">
                                    <div className="flex items-center justify-between mb-6">
                                        <h3 className="text-lg font-semibold">Order Items</h3>
                                        <span className="text-sm text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                            {order.items?.length || 0} {order.items?.length === 1 ? 'item' : 'items'}
                                        </span>
                                    </div>
                                    
                                    {!order.items || order.items.length === 0 ? (
                                        <div className="text-center py-12 text-gray-500">
                                            <Package className="mx-auto h-12 w-12 text-gray-300 mb-3" />
                                            <p className="text-base font-medium">No items in this order</p>
                                        </div>
                                    ) : (
                                        <div className="space-y-3">
                                            {order.items.map((item: any, index: number) => (
                                                <div key={item.id}>
                                                    {index > 0 && <Separator className="my-3" />}
                                                    <div className="flex items-start justify-between">
                                                        <div className="flex-1">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-base font-medium text-gray-900">
                                                                    {item.quantity}x {item.itemName}
                                                                </span>
                                                                <Badge 
                                                                    variant="outline"
                                                                    className={getKitchenStatusColor(item.kitchenStatus)}
                                                                >
                                                                    {getKitchenStatusLabel(item.kitchenStatus)}
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
                                                                    {item.modifiers.map((mod: any, idx: number) => (
                                                                        <span key={idx}>
                                                                            {mod.modifierName} (+{formatCurrency(mod.price)})
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
                                                            <p className="text-lg font-semibold text-gray-900">{formatCurrency(item.totalPrice)}</p>
                                                            <p className="text-sm text-gray-500">
                                                                {formatCurrency(item.unitPrice)} each
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}

                                    {/* Financial Summary */}
                                    {order.items && order.items.length > 0 && (
                                        <>
                                            <Separator className="my-6" />
                                            <div className="space-y-3">
                                                <div className="flex justify-between text-base">
                                                    <span className="text-gray-600">Subtotal</span>
                                                    <span className="font-medium">{formatCurrency(order.subtotal)}</span>
                                                </div>
                                                {order.taxAmount > 0 && (
                                                    <div className="flex justify-between text-base">
                                                        <span className="text-gray-600">Tax (19%)</span>
                                                        <span className="font-medium">{formatCurrency(order.taxAmount)}</span>
                                                    </div>
                                                )}
                                                {order.tipAmount > 0 && (
                                                    <div className="flex justify-between text-base">
                                                        <span className="text-gray-600">Tip</span>
                                                        <span className="font-medium">{formatCurrency(order.tipAmount)}</span>
                                                    </div>
                                                )}
                                                {order.discountAmount > 0 && (
                                                    <div className="flex justify-between text-base text-green-600">
                                                        <span>Discount</span>
                                                        <span className="font-medium">-{formatCurrency(order.discountAmount)}</span>
                                                    </div>
                                                )}
                                                <Separator className="my-3" />
                                                <div className="flex justify-between text-lg font-bold">
                                                    <span>Total</span>
                                                    <span className="text-xl">{formatCurrency(order.totalAmount)}</span>
                                                </div>
                                            </div>
                                        </>
                                    )}
                                </div>
                            </TabsContent>

                            <TabsContent value="payment" className="m-0 p-8">
                                <div className="max-w-3xl mx-auto">
                                    <h3 className="text-lg font-semibold mb-6">Payment Information</h3>
                                    
                                    {!isPaid && (
                                        <div className="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <DollarSign className="w-5 h-5 text-amber-600" />
                                                    <div>
                                                        <p className="font-medium text-amber-900">Payment Required</p>
                                                        <p className="text-sm text-amber-800">
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
                                </div>
                            </TabsContent>
                                
                            <TabsContent value="info" className="m-0 p-8">
                                <div className="max-w-3xl mx-auto space-y-8">
                                    {/* Basic Information */}
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Basic Information</h3>
                                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Order Number</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">{order.orderNumber}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Order Type</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">{getTypeLabel(order.type)}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Location</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">{location?.name || 'N/A'}</dd>
                                                </div>
                                                {user && (
                                                    <div>
                                                        <dt className="text-sm font-medium text-gray-500">Served By</dt>
                                                        <dd className="mt-1 text-sm text-gray-900">{user.name}</dd>
                                                    </div>
                                                )}
                                            </dl>
                                        </div>
                                        
                                        <Separator />
                                        
                                    {/* Timestamps */}
                                    <div>
                                        <h3 className="text-lg font-semibold mb-4">Timestamps</h3>
                                            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div>
                                                    <dt className="text-sm font-medium text-gray-500">Created</dt>
                                                    <dd className="mt-1 text-sm text-gray-900">
                                                        {order.createdAt ? new Date(order.createdAt).toLocaleString() : 'N/A'}
                                                    </dd>
                                                </div>
                                                {order.scheduledAt && (
                                                    <div>
                                                        <dt className="text-sm font-medium text-gray-500">Scheduled For</dt>
                                                        <dd className="mt-1 text-sm text-gray-900">
                                                            {new Date(order.scheduledAt).toLocaleString()}
                                                        </dd>
                                                    </div>
                                                )}
                                                {order.completedAt && (
                                                    <div>
                                                        <dt className="text-sm font-medium text-gray-500">Completed</dt>
                                                        <dd className="mt-1 text-sm text-gray-900">
                                                            {new Date(order.completedAt).toLocaleString()}
                                                        </dd>
                                                    </div>
                                                )}
                                            </dl>
                                        </div>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </Card>
                </PageContent>
            </PageLayout>
        </AppLayout>
    );
}