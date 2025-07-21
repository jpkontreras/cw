import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { 
    Clock, 
    MapPin, 
    Phone, 
    User, 
    DollarSign,
    Package,
    AlertCircle,
    ChefHat,
    Truck,
    Home
} from 'lucide-react';
import type { Order } from '@/types/modules/order';
import { 
    formatCurrency, 
    formatOrderNumber, 
    getStatusColor, 
    getStatusLabel, 
    getTypeLabel,
    getOrderAge 
} from '@/types/modules/order/utils';

interface OrderCardProps {
    order: Order;
    variant?: 'default' | 'compact' | 'detailed';
    showActions?: boolean;
    onAction?: (action: string, orderId: string) => void;
    className?: string;
}

export function OrderCard({ 
    order, 
    variant = 'default',
    showActions = true,
    onAction,
    className = ''
}: OrderCardProps) {
    const typeIcons = {
        dine_in: Home,
        takeout: Package,
        delivery: Truck,
        catering: ChefHat
    };
    const TypeIcon = typeIcons[order.type] || Package;

    const handleAction = (action: string) => {
        onAction?.(action, String(order.id));
    };

    if (variant === 'compact') {
        return (
            <Card className={`hover:shadow-md transition-shadow cursor-pointer ${className}`}>
                <CardContent className="p-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center gap-3">
                            <TypeIcon className="w-5 h-5 text-gray-500" />
                            <div>
                                <p className="font-medium">{formatOrderNumber(order.orderNumber)}</p>
                                <p className="text-sm text-gray-500">
                                    {order.customerName || 'Walk-in'} • {getOrderAge(order)}
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <Badge className={getStatusColor(order.status)}>
                                {getStatusLabel(order.status)}
                            </Badge>
                            <span className="font-medium">{formatCurrency(order.totalAmount)}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        );
    }

    if (variant === 'detailed') {
        return (
            <Card className={`hover:shadow-lg transition-shadow ${className}`}>
                <CardHeader>
                    <div className="flex items-start justify-between">
                        <div>
                            <CardTitle className="text-lg flex items-center gap-2">
                                {formatOrderNumber(order.orderNumber)}
                                <TypeIcon className="w-5 h-5 text-gray-500" />
                            </CardTitle>
                            <div className="flex flex-wrap items-center gap-2 mt-2">
                                <Badge variant="outline">{getTypeLabel(order.type)}</Badge>
                                {order.priority === 'high' && (
                                    <Badge variant="destructive" className="text-xs">
                                        <AlertCircle className="w-3 h-3 mr-1" />
                                        Priority
                                    </Badge>
                                )}
                                <Badge className={getStatusColor(order.status)}>
                                    {getStatusLabel(order.status)}
                                </Badge>
                            </div>
                        </div>
                        <span className="text-lg font-semibold">
                            {formatCurrency(order.totalAmount)}
                        </span>
                    </div>
                </CardHeader>
                <CardContent className="space-y-4">
                    {/* Customer Info */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="flex items-center gap-2">
                            <User className="w-4 h-4 text-gray-400" />
                            <span className="text-sm">{order.customerName || 'Walk-in'}</span>
                        </div>
                        {order.customerPhone && (
                            <div className="flex items-center gap-2">
                                <Phone className="w-4 h-4 text-gray-400" />
                                <span className="text-sm">{order.customerPhone}</span>
                            </div>
                        )}
                    </div>

                    {/* Location Info */}
                    {order.tableNumber && (
                        <div className="flex items-center gap-2">
                            <Home className="w-4 h-4 text-gray-400" />
                            <span className="text-sm">Table {order.tableNumber}</span>
                        </div>
                    )}
                    {order.deliveryAddress && (
                        <div className="flex items-start gap-2">
                            <MapPin className="w-4 h-4 text-gray-400 mt-0.5" />
                            <span className="text-sm">{order.deliveryAddress}</span>
                        </div>
                    )}

                    {/* Order Items */}
                    <div className="space-y-2">
                        <p className="text-sm font-medium">Items ({order.items?.length || 0})</p>
                        <div className="space-y-1">
                            {order.items?.slice(0, 3).map((item: any, idx: number) => (
                                <div key={idx} className="text-sm text-gray-600">
                                    {item.quantity}x {item.itemName}
                                </div>
                            ))}
                            {(order.items?.length || 0) > 3 && (
                                <p className="text-sm text-gray-500">
                                    +{(order.items?.length || 0) - 3} more items
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Time Info */}
                    <div className="flex items-center justify-between pt-3 border-t">
                        <div className="flex items-center gap-2">
                            <Clock className="w-4 h-4 text-gray-400" />
                            <span className="text-sm text-gray-500">{getOrderAge(order)}</span>
                        </div>
                        {showActions && (
                            <Button 
                                size="sm" 
                                onClick={() => handleAction('view')}
                            >
                                View Details
                            </Button>
                        )}
                    </div>
                </CardContent>
            </Card>
        );
    }

    // Default variant
    return (
        <Card className={`hover:shadow-md transition-shadow ${className}`}>
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                    <div>
                        <CardTitle className="text-base">
                            {formatOrderNumber(order.orderNumber)}
                        </CardTitle>
                        <p className="text-sm text-gray-500 mt-1">
                            {order.customerName || 'Walk-in'}
                        </p>
                    </div>
                    <Badge className={getStatusColor(order.status)}>
                        {getStatusLabel(order.status)}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent>
                <div className="space-y-3">
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-500">Type</span>
                        <div className="flex items-center gap-1">
                            <TypeIcon className="w-4 h-4" />
                            <span>{getTypeLabel(order.type)}</span>
                        </div>
                    </div>
                    {order.tableNumber && (
                        <div className="flex items-center justify-between text-sm">
                            <span className="text-gray-500">Table</span>
                            <span>{order.tableNumber}</span>
                        </div>
                    )}
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-500">Items</span>
                        <span>{order.items?.length || 0}</span>
                    </div>
                    <div className="flex items-center justify-between text-sm">
                        <span className="text-gray-500">Time</span>
                        <span>{getOrderAge(order)}</span>
                    </div>
                    <div className="flex items-center justify-between pt-3 border-t">
                        <span className="font-semibold">
                            {formatCurrency(order.totalAmount)}
                        </span>
                        {showActions && (
                            <Button 
                                size="sm" 
                                variant="outline"
                                onClick={() => handleAction('view')}
                            >
                                View
                            </Button>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}