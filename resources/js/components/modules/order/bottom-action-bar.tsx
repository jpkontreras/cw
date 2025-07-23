import { useState, useEffect, FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { ScrollArea } from '@/components/ui/scroll-area';
import { 
    ChevronUp, 
    ChevronDown, 
    ShoppingBag, 
    ArrowRight,
    Minus,
    Plus,
    Trash2,
    CheckCircle2,
    Clock,
    Info,
    MapPin,
    Utensils,
    Package,
    Truck
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/types/modules/order/utils';
import type { CreateOrderRequest } from '@/types/modules/order';

interface Props {
    data: CreateOrderRequest;
    items: Array<{
        id: number;
        name: string;
        price: number;
        category: string;
        modifiers?: Array<{
            id: number;
            name: string;
            price: number;
        }>;
    }>;
    locations: Array<{ id: number; name: string }>;
    currentStep: 'menu' | 'details';
    subtotal: number;
    tax: number;
    total: number;
    canProceedToDetails: boolean;
    canPlaceOrder: boolean;
    processing?: boolean;
    onContinueToDetails: () => void;
    onPlaceOrder: (e: FormEvent) => void;
    onUpdateQuantity: (index: number, quantity: number) => void;
    onRemoveItem: (index: number) => void;
    selectedModifiers?: Record<number, number[]>;
}

export function BottomActionBar({
    data,
    items,
    locations,
    currentStep,
    subtotal,
    tax,
    total,
    canProceedToDetails,
    canPlaceOrder,
    processing = false,
    onContinueToDetails,
    onPlaceOrder,
    onUpdateQuantity,
    onRemoveItem,
    selectedModifiers = {}
}: Props) {
    const [isExpanded, setIsExpanded] = useState(false);
    const [showBarInitially, setShowBarInitially] = useState(false);
    
    const itemCount = data.items.reduce((sum, item) => sum + item.quantity, 0);
    const hasItems = itemCount > 0;
    
    // Animate in the bar when first item is added
    useEffect(() => {
        if (hasItems && !showBarInitially) {
            setShowBarInitially(true);
        }
    }, [hasItems, showBarInitially]);
    
    if (!hasItems && currentStep === 'menu') {
        return null;
    }
    
    // Group items by category for display
    const categorizedItems = data.items.reduce((acc, orderItem, index) => {
        const menuItem = items.find(i => i.id === orderItem.item_id);
        if (!menuItem) return acc;
        
        const category = menuItem.category || 'Other';
        if (!acc[category]) acc[category] = [];
        acc[category].push({ orderItem, menuItem, index });
        return acc;
    }, {} as Record<string, Array<{ orderItem: any; menuItem: any; index: number }>>);
    
    const getOrderTypeIcon = (type: string) => {
        switch (type) {
            case 'dine_in': return Utensils;
            case 'takeout': return Package;
            case 'delivery': return Truck;
            default: return ShoppingBag;
        }
    };

    const OrderTypeIcon = getOrderTypeIcon(data.type);
    const currentLocation = locations.find(l => l.id === data.locationId);

    return (
        <div className={cn(
            "fixed bottom-0 left-0 right-0 z-40 transform transition-all duration-300 ease-out",
            showBarInitially ? "translate-y-0" : "translate-y-full"
        )}>
            {/* Expanded content */}
            <div className={cn(
                "bg-white border-t border-gray-200 shadow-2xl transition-all duration-300 ease-out absolute bottom-full left-0 right-0",
                isExpanded ? "translate-y-0" : "translate-y-full"
            )}>
                <div className="max-h-[70vh] flex flex-col">
                    {/* Header */}
                    <div className="px-4 sm:px-6 py-4 border-b bg-gray-50/50 flex items-center justify-between">
                        <h3 className="font-semibold text-lg flex items-center gap-2">
                            <div className="w-8 h-8 bg-primary/10 rounded-lg flex items-center justify-center">
                                <ShoppingBag className="w-4 h-4 text-primary" />
                            </div>
                            Order Summary
                        </h3>
                        <Button
                            variant="ghost"
                            size="icon"
                            className="h-9 w-9 hover:bg-gray-200 rounded-full transition-colors"
                            onClick={() => setIsExpanded(false)}
                        >
                            <ChevronDown className="w-5 h-5" />
                        </Button>
                    </div>
                    
                    {/* Items list */}
                    <ScrollArea className="flex-1 bg-gray-50/30">
                        <div className="p-4 sm:px-6 space-y-6">
                            {Object.entries(categorizedItems).map(([category, categoryItems]) => (
                                <div key={category}>
                                    <div className="flex items-center gap-2 mb-3">
                                        <div className="h-px flex-1 bg-gray-200" />
                                        <span className="text-xs font-semibold text-gray-500 uppercase tracking-wide px-2">
                                            {category}
                                        </span>
                                        <div className="h-px flex-1 bg-gray-200" />
                                    </div>
                                    <div className="space-y-2">
                                        {categoryItems.map(({ orderItem, menuItem, index }) => {
                                            const itemModifiers = selectedModifiers[orderItem.item_id] || [];
                                            const modifierPrice = itemModifiers.reduce((sum, modId) => {
                                                const mod = menuItem.modifiers?.find(m => m.id === modId);
                                                return sum + (mod?.price || 0);
                                            }, 0);
                                            const itemTotal = (menuItem.price + modifierPrice) * orderItem.quantity;
                                            
                                            return (
                                                <div key={`${orderItem.item_id}-${index}`} className="bg-white rounded-xl border border-gray-200 p-4 hover:shadow-sm transition-shadow">
                                                    <div className="flex items-start gap-3">
                                                        {/* Quantity controls */}
                                                        <div className="bg-gray-100 rounded-lg p-1 flex items-center gap-1">
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="ghost"
                                                                className="h-7 w-7 hover:bg-white"
                                                                onClick={() => onUpdateQuantity(index, orderItem.quantity - 1)}
                                                            >
                                                                <Minus className="w-3 h-3" />
                                                            </Button>
                                                            <span className="w-8 text-center font-semibold text-sm">
                                                                {orderItem.quantity}
                                                            </span>
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="ghost"
                                                                className="h-7 w-7 hover:bg-white"
                                                                onClick={() => onUpdateQuantity(index, orderItem.quantity + 1)}
                                                            >
                                                                <Plus className="w-3 h-3" />
                                                            </Button>
                                                        </div>
                                                        
                                                        {/* Item details */}
                                                        <div className="flex-1 min-w-0">
                                                            <div className="font-medium text-gray-900 truncate">{menuItem.name}</div>
                                                            {itemModifiers.length > 0 && (
                                                                <div className="flex items-center gap-1 mt-1">
                                                                    <div className="w-1 h-1 bg-primary rounded-full" />
                                                                    <div className="text-xs text-gray-600">
                                                                        {itemModifiers.map(modId => {
                                                                            const mod = menuItem.modifiers?.find(m => m.id === modId);
                                                                            return mod?.name;
                                                                        }).filter(Boolean).join(', ')}
                                                                    </div>
                                                                </div>
                                                            )}
                                                            {orderItem.notes && (
                                                                <div className="flex items-start gap-1 mt-1">
                                                                    <Info className="w-3 h-3 text-gray-400 mt-0.5 flex-shrink-0" />
                                                                    <div className="text-xs text-gray-500 italic">
                                                                        {orderItem.notes}
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                        
                                                        {/* Price and remove */}
                                                        <div className="flex items-center gap-2 flex-shrink-0">
                                                            <span className="font-semibold tabular-nums text-gray-900">
                                                                {formatCurrency(itemTotal)}
                                                            </span>
                                                            <Button
                                                                type="button"
                                                                size="icon"
                                                                variant="ghost"
                                                                className="h-8 w-8 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-full transition-colors"
                                                                onClick={() => onRemoveItem(index)}
                                                            >
                                                                <Trash2 className="w-4 h-4" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </ScrollArea>
                    
                    {/* Totals */}
                    <div className="border-t bg-white p-4 sm:px-6 space-y-3">
                        <div className="space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600">Subtotal</span>
                                <span className="tabular-nums font-medium">{formatCurrency(subtotal)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600 flex items-center gap-1">
                                    Tax
                                    <span className="text-xs text-gray-500">(19%)</span>
                                </span>
                                <span className="tabular-nums font-medium">{formatCurrency(tax)}</span>
                            </div>
                        </div>
                        <div className="h-px bg-gray-200" />
                        <div className="flex justify-between items-baseline">
                            <span className="font-semibold text-gray-900">Total Amount</span>
                            <span className="text-2xl font-bold bg-gradient-to-r from-primary to-primary/80 bg-clip-text text-transparent tabular-nums">
                                {formatCurrency(total)}
                            </span>
                        </div>
                        
                        {/* Estimated time */}
                        <div className="pt-2">
                            <div className="bg-blue-50 border border-blue-200 rounded-lg px-3 py-2 flex items-center gap-2">
                                <Clock className="w-4 h-4 text-blue-600" />
                                <span className="text-sm text-blue-900">
                                    Estimated preparation: <span className="font-semibold">15-20 minutes</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            {/* Main bar */}
            <div className="bg-white border-t border-gray-200 shadow-2xl">
                {/* Animated progress indicator */}
                {processing && (
                    <div className="absolute top-0 left-0 right-0 h-1 bg-gray-200">
                        <div className="h-full bg-primary animate-pulse" style={{ width: '60%' }} />
                    </div>
                )}
                
                <div className="px-4 sm:px-6 py-4">
                    <div className="flex items-center justify-between gap-4">
                        {/* Left side - Summary info */}
                        <button
                            type="button"
                            onClick={() => setIsExpanded(!isExpanded)}
                            className="flex items-center gap-3 flex-1 text-left group transition-all hover:scale-[1.02]"
                        >
                            <div className="flex items-center gap-3 flex-1">
                                {/* Left: Order info */}
                                <div className="flex items-center gap-3 flex-1">
                                    <div className="relative">
                                        <div className="w-14 h-14 bg-gradient-to-br from-primary/20 to-primary/10 rounded-2xl flex items-center justify-center shadow-sm group-hover:shadow-md transition-shadow">
                                            <ShoppingBag className="w-7 h-7 text-primary" />
                                            {hasItems && (
                                                <Badge 
                                                    className="absolute -top-2 -right-2 h-6 w-6 p-0 flex items-center justify-center text-xs bg-gradient-to-r from-primary to-primary/80 border-2 border-white shadow-md"
                                                    variant="default"
                                                >
                                                    {itemCount}
                                                </Badge>
                                            )}
                                        </div>
                                    </div>
                                    
                                    <div className="flex-1 text-left">
                                        <div className="text-xs text-gray-500 uppercase tracking-wider font-semibold flex items-center gap-1">
                                            <span>Order Total</span>
                                            {itemCount > 0 && (
                                                <span className="text-primary">• {itemCount} {itemCount === 1 ? 'item' : 'items'}</span>
                                            )}
                                        </div>
                                        <div className="flex items-baseline gap-2 mt-0.5">
                                            <span className="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent">
                                                {formatCurrency(total)}
                                            </span>
                                            {tax > 0 && (
                                                <span className="text-xs text-gray-500">
                                                    incl. tax
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>
                                
                                {/* Center: Location and type info */}
                                <div className="hidden lg:flex items-center gap-3">
                                    <div className="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-full">
                                        <MapPin className="w-3.5 h-3.5 text-gray-600" />
                                        <span className="text-xs font-medium text-gray-700">{currentLocation?.name || 'Select location'}</span>
                                    </div>
                                    <div className="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-full">
                                        <OrderTypeIcon className="w-3.5 h-3.5 text-gray-600" />
                                        <span className="text-xs font-medium text-gray-700">
                                            {data.type === 'dine_in' ? 'Dine In' : data.type === 'takeout' ? 'Takeout' : 'Delivery'}
                                        </span>
                                    </div>
                                </div>
                                
                                {/* Right: Expand button and time */}
                                <div className="flex items-center gap-2">
                                    {hasItems && (
                                        <div className="hidden sm:flex items-center gap-1 px-3 py-1.5 bg-gray-100 rounded-full">
                                            <Clock className="w-3.5 h-3.5 text-gray-600" />
                                            <span className="text-xs font-medium text-gray-600">15-20 min</span>
                                        </div>
                                    )}
                                    <div className="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center group-hover:bg-gray-200 transition-colors">
                                        <ChevronUp className={cn(
                                            "w-5 h-5 transition-transform duration-200 text-gray-600",
                                            isExpanded ? "rotate-180" : ""
                                        )} />
                                    </div>
                                </div>
                            </div>
                        </button>
                        
                        {/* Right side - Action button */}
                        <div className="flex-shrink-0">
                            {currentStep === 'menu' ? (
                                <Button
                                    type="button"
                                    size="lg"
                                    onClick={onContinueToDetails}
                                    disabled={!canProceedToDetails}
                                    className={cn(
                                        "relative overflow-hidden px-6 sm:px-8 py-3 text-base font-semibold transition-all",
                                        "bg-gradient-to-r from-primary to-primary/90 hover:from-primary/90 hover:to-primary/80",
                                        "text-white shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed",
                                        "transform hover:scale-105 active:scale-100"
                                    )}
                                >
                                    <span className="relative z-10 flex items-center">
                                        <span className="hidden sm:inline">Continue to&nbsp;</span>
                                        <span>Checkout</span>
                                        <ArrowRight className="w-5 h-5 ml-2 transition-transform group-hover:translate-x-1" />
                                    </span>
                                </Button>
                            ) : (
                                <Button
                                    type="button"
                                    size="lg"
                                    onClick={(e) => onPlaceOrder(e as any)}
                                    disabled={!canPlaceOrder || processing}
                                    className={cn(
                                        "relative overflow-hidden px-6 sm:px-8 py-3 text-base font-semibold transition-all",
                                        "bg-gradient-to-r from-green-600 to-green-500 hover:from-green-500 hover:to-green-400",
                                        "text-white shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed",
                                        "transform hover:scale-105 active:scale-100"
                                    )}
                                >
                                    {processing ? (
                                        <span className="flex items-center">
                                            <div className="w-5 h-5 mr-2 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                            Processing...
                                        </span>
                                    ) : (
                                        <span className="relative z-10 flex items-center">
                                            <CheckCircle2 className="w-5 h-5 mr-2" />
                                            <span className="hidden sm:inline">Place Order •</span>
                                            <span className="font-bold ml-1">{formatCurrency(total)}</span>
                                        </span>
                                    )}
                                </Button>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}