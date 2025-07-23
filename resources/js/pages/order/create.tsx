import { Head, useForm, Link } from '@inertiajs/react';
import { FormEventHandler, useState, useMemo } from 'react';
import AppLayout from '@/layouts/app-layout';
import { PageLayout, PageHeader, PageContent } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
    PlusCircle, 
    Trash2, 
    ChevronRight, 
    ChevronLeft,
    ShoppingCart,
    User,
    MapPin,
    CreditCard,
    Check,
    Utensils,
    ShoppingBag,
    Truck,
    Calendar,
    Phone,
    Mail
} from 'lucide-react';
import type { CreateOrderRequest, OrderType } from '@/types/modules/order';
import { ORDER_TYPE_CONFIG, TAX_RATE, TIP_SUGGESTIONS } from '@/types/modules/order/constants';
import { calculateSubtotal, calculateTax, calculateTotal, formatCurrency } from '@/types/modules/order/utils';

interface Props {
    locations: Array<{ id: number; name: string }>;
    tables?: Array<{ id: number; number: number; available: boolean }>;
    items?: Array<{
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
}

type OrderStep = 'details' | 'items' | 'summary' | 'payment';

const STEPS: Array<{ id: OrderStep; label: string; icon: any }> = [
    { id: 'details', label: 'Order Details', icon: ShoppingCart },
    { id: 'items', label: 'Add Items', icon: Utensils },
    { id: 'summary', label: 'Review Order', icon: Check },
    { id: 'payment', label: 'Payment', icon: CreditCard },
];

export default function CreateOrder({ locations, tables = [], items = [] }: Props) {
    const [currentStep, setCurrentStep] = useState<OrderStep>('details');
    const [selectedModifiers, setSelectedModifiers] = useState<Record<number, number[]>>({});
    
    const { data, setData, post, processing, errors } = useForm<CreateOrderRequest>({
        location_id: locations[0]?.id || 1,
        type: 'dine_in' as OrderType,
        table_number: undefined,
        customer_name: '',
        customer_phone: '',
        customer_email: '',
        delivery_address: '',
        items: [],
        notes: '',
        special_instructions: '',
    });

    // Calculate order totals
    const subtotal = useMemo(() => {
        return data.items.reduce((sum, item) => {
            const menuItem = items.find(i => i.id === item.item_id);
            if (!menuItem) return sum;
            
            let itemTotal = menuItem.price * item.quantity;
            
            // Add modifier prices
            const modifiers = selectedModifiers[item.item_id] || [];
            modifiers.forEach(modId => {
                const modifier = menuItem.modifiers?.find(m => m.id === modId);
                if (modifier) {
                    itemTotal += modifier.price * item.quantity;
                }
            });
            
            return sum + itemTotal;
        }, 0);
    }, [data.items, items, selectedModifiers]);

    const tax = calculateTax(subtotal);
    const total = calculateTotal(subtotal, tax);

    const handleSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        
        // Add modifiers to items before submitting
        const itemsWithModifiers = data.items.map(item => ({
            ...item,
            modifiers: selectedModifiers[item.item_id] || [],
        }));
        
        post('/orders', {
            data: { ...data, items: itemsWithModifiers },
        });
    };

    const nextStep = () => {
        const stepIndex = STEPS.findIndex(s => s.id === currentStep);
        if (stepIndex < STEPS.length - 1) {
            setCurrentStep(STEPS[stepIndex + 1].id);
        }
    };

    const prevStep = () => {
        const stepIndex = STEPS.findIndex(s => s.id === currentStep);
        if (stepIndex > 0) {
            setCurrentStep(STEPS[stepIndex - 1].id);
        }
    };

    const canProceed = () => {
        switch (currentStep) {
            case 'details':
                if (data.type === 'delivery' && !data.delivery_address) return false;
                if (data.type === 'dine_in' && !data.table_number) return false;
                return true;
            case 'items':
                return data.items.length > 0;
            case 'summary':
                return true;
            case 'payment':
                return true;
            default:
                return false;
        }
    };

    const addItem = (itemId: number) => {
        const existingItem = data.items.find(i => i.item_id === itemId);
        if (existingItem) {
            updateItemQuantity(itemId, existingItem.quantity + 1);
        } else {
            setData('items', [...data.items, {
                item_id: itemId,
                quantity: 1,
                notes: '',
            }]);
        }
    };

    const removeItem = (itemId: number) => {
        setData('items', data.items.filter(item => item.item_id !== itemId));
        // Clean up modifiers
        const newModifiers = { ...selectedModifiers };
        delete newModifiers[itemId];
        setSelectedModifiers(newModifiers);
    };

    const updateItemQuantity = (itemId: number, quantity: number) => {
        if (quantity <= 0) {
            removeItem(itemId);
        } else {
            setData('items', data.items.map(item => 
                item.item_id === itemId ? { ...item, quantity } : item
            ));
        }
    };

    const updateItemNotes = (itemId: number, notes: string) => {
        setData('items', data.items.map(item => 
            item.item_id === itemId ? { ...item, notes } : item
        ));
    };

    const toggleModifier = (itemId: number, modifierId: number) => {
        const current = selectedModifiers[itemId] || [];
        const newModifiers = current.includes(modifierId)
            ? current.filter(id => id !== modifierId)
            : [...current, modifierId];
        
        setSelectedModifiers({
            ...selectedModifiers,
            [itemId]: newModifiers,
        });
    };

    const getTypeIcon = (type: OrderType) => {
        switch (type) {
            case 'dine_in': return Utensils;
            case 'takeout': return ShoppingBag;
            case 'delivery': return Truck;
            case 'catering': return Calendar;
        }
    };

    const breadcrumbs = [
        { label: 'Orders', href: '/orders' },
        { label: 'Create Order' }
    ];

    return (
        <AppLayout>
            <Head title="Create Order" />
            
            <PageLayout>
                <PageHeader
                    title="Create New Order"
                    description="Fill in the details to create a new order"
                    breadcrumbs={breadcrumbs}
                >
                    <Link href="/orders">
                        <Button variant="outline">
                            <ChevronLeft className="w-4 h-4 mr-2" />
                            Cancel
                        </Button>
                    </Link>
                </PageHeader>

                <PageContent>
                    <form onSubmit={handleSubmit}>

                    {/* Progress Steps */}
                    <div className="mb-8 flex justify-center">
                        <div className="flex items-center justify-between max-w-2xl w-full">
                            {STEPS.map((step, index) => {
                                const Icon = step.icon;
                                const isActive = step.id === currentStep;
                                const isCompleted = STEPS.findIndex(s => s.id === currentStep) > index;
                                
                                return (
                                    <div key={step.id} className="flex items-center flex-1">
                                        <button
                                            type="button"
                                            onClick={() => setCurrentStep(step.id)}
                                            className={`flex flex-col items-center w-full ${
                                                isActive ? 'text-primary' : isCompleted ? 'text-green-600' : 'text-gray-400'
                                            }`}
                                            disabled={!isCompleted && !isActive}
                                        >
                                            <div className={`w-10 h-10 rounded-full flex items-center justify-center ${
                                                isActive ? 'bg-primary text-white' : 
                                                isCompleted ? 'bg-green-600 text-white' : 
                                                'bg-gray-200'
                                            }`}>
                                                {isCompleted && !isActive ? (
                                                    <Check className="w-5 h-5" />
                                                ) : (
                                                    <Icon className="w-5 h-5" />
                                                )}
                                            </div>
                                            <span className="text-sm mt-2">{step.label}</span>
                                        </button>
                                        {index < STEPS.length - 1 && (
                                            <div className={`flex-1 h-0.5 mx-4 ${
                                                isCompleted ? 'bg-green-600' : 'bg-gray-200'
                                            }`} />
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    </div>

                    {/* Step Content */}
                    <div className="mb-8">
                        {currentStep === 'details' && (
                            <Card>
                                <CardHeader className="px-6 pt-6">
                                    <CardTitle>Order Details</CardTitle>
                                    <CardDescription>Choose the order type and provide customer information</CardDescription>
                                </CardHeader>
                                <CardContent className="p-6 space-y-6">
                                    {/* Location Selection */}
                                    <div>
                                        <Label htmlFor="location">Location</Label>
                                        <Select
                                            value={data.location_id.toString()}
                                            onValueChange={(value) => setData('location_id', parseInt(value))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select location" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {locations.map(location => (
                                                    <SelectItem key={location.id} value={location.id.toString()}>
                                                        {location.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Order Type */}
                                    <div>
                                        <Label>Order Type</Label>
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2">
                                            {Object.entries(ORDER_TYPE_CONFIG).map(([type, config]) => {
                                                const Icon = getTypeIcon(type as OrderType);
                                                return (
                                                    <button
                                                        key={type}
                                                        type="button"
                                                        onClick={() => setData('type', type as OrderType)}
                                                        className={`p-4 rounded-lg border-2 transition-colors ${
                                                            data.type === type 
                                                                ? 'border-primary bg-primary/10' 
                                                                : 'border-gray-200 hover:border-gray-300'
                                                        }`}
                                                    >
                                                        <Icon className="w-6 h-6 mx-auto mb-2" />
                                                        <span className="text-sm font-medium">{config.label}</span>
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    {/* Table Number (for dine-in) */}
                                    {data.type === 'dine_in' && (
                                        <div>
                                            <Label htmlFor="table">Table Number</Label>
                                            <Select
                                                value={data.table_number?.toString() || ''}
                                                onValueChange={(value) => setData('table_number', parseInt(value))}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select table" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {tables.filter(t => t.available).map(table => (
                                                        <SelectItem key={table.id} value={table.number.toString()}>
                                                            Table {table.number}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    )}

                                    {/* Customer Information */}
                                    <Separator />
                                    <div className="space-y-4">
                                        <h3 className="font-medium">Customer Information</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <Label htmlFor="customerName">Name</Label>
                                                <Input
                                                    id="customerName"
                                                    value={data.customer_name || ''}
                                                    onChange={e => setData('customer_name', e.target.value)}
                                                    placeholder="John Doe"
                                                />
                                            </div>
                                            <div>
                                                <Label htmlFor="customerPhone">
                                                    <Phone className="w-4 h-4 inline mr-1" />
                                                    Phone
                                                </Label>
                                                <Input
                                                    id="customerPhone"
                                                    value={data.customer_phone || ''}
                                                    onChange={e => setData('customer_phone', e.target.value)}
                                                    placeholder="+56 9 1234 5678"
                                                />
                                            </div>
                                        </div>
                                        <div>
                                            <Label htmlFor="customerEmail">
                                                <Mail className="w-4 h-4 inline mr-1" />
                                                Email
                                            </Label>
                                            <Input
                                                id="customerEmail"
                                                type="email"
                                                value={data.customer_email || ''}
                                                onChange={e => setData('customer_email', e.target.value)}
                                                placeholder="john@example.com"
                                            />
                                        </div>
                                    </div>

                                    {/* Delivery Address (for delivery) */}
                                    {data.type === 'delivery' && (
                                        <div>
                                            <Label htmlFor="deliveryAddress">
                                                <MapPin className="w-4 h-4 inline mr-1" />
                                                Delivery Address
                                            </Label>
                                            <Textarea
                                                id="deliveryAddress"
                                                value={data.delivery_address || ''}
                                                onChange={e => setData('delivery_address', e.target.value)}
                                                placeholder="Street address, apartment number, etc."
                                                rows={3}
                                                required
                                            />
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        )}

                        {currentStep === 'items' && (
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                                {/* Menu Items */}
                                <div className="lg:col-span-2">
                                    <Card>
                                        <CardHeader className="px-6 pt-6">
                                            <CardTitle>Menu Items</CardTitle>
                                            <CardDescription>Select items to add to the order</CardDescription>
                                        </CardHeader>
                                        <CardContent className="p-6">
                                            {/* Group items by category */}
                                            {Object.entries(
                                                items.reduce((acc, item) => {
                                                    if (!acc[item.category]) acc[item.category] = [];
                                                    acc[item.category].push(item);
                                                    return acc;
                                                }, {} as Record<string, typeof items>)
                                            ).map(([category, categoryItems]) => (
                                                <div key={category} className="mb-6">
                                                    <h3 className="font-semibold text-lg mb-3">{category}</h3>
                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        {categoryItems.map(item => {
                                                            const orderItem = data.items.find(i => i.item_id === item.id);
                                                            const quantity = orderItem?.quantity || 0;
                                                            
                                                            return (
                                                                <div
                                                                    key={item.id}
                                                                    className={`p-4 rounded-lg border ${
                                                                        quantity > 0 ? 'border-primary bg-primary/5' : 'border-gray-200'
                                                                    }`}
                                                                >
                                                                    <div className="flex justify-between items-start mb-2">
                                                                        <div>
                                                                            <h4 className="font-medium">{item.name}</h4>
                                                                            <p className="text-sm text-gray-600">
                                                                                {formatCurrency(item.price)}
                                                                            </p>
                                                                        </div>
                                                                        {quantity > 0 && (
                                                                            <Badge>{quantity}</Badge>
                                                                        )}
                                                                    </div>
                                                                    
                                                                    {/* Quantity controls */}
                                                                    <div className="flex items-center gap-2 mt-3">
                                                                        <Button
                                                                            type="button"
                                                                            size="sm"
                                                                            variant={quantity > 0 ? "outline" : "default"}
                                                                            onClick={() => addItem(item.id)}
                                                                            className="flex-1"
                                                                        >
                                                                            {quantity > 0 ? 'Add More' : 'Add to Order'}
                                                                        </Button>
                                                                        {quantity > 0 && (
                                                                            <div className="flex items-center gap-1">
                                                                                <Button
                                                                                    type="button"
                                                                                    size="sm"
                                                                                    variant="outline"
                                                                                    onClick={() => updateItemQuantity(item.id, quantity - 1)}
                                                                                >
                                                                                    -
                                                                                </Button>
                                                                                <span className="w-8 text-center">{quantity}</span>
                                                                                <Button
                                                                                    type="button"
                                                                                    size="sm"
                                                                                    variant="outline"
                                                                                    onClick={() => updateItemQuantity(item.id, quantity + 1)}
                                                                                >
                                                                                    +
                                                                                </Button>
                                                                            </div>
                                                                        )}
                                                                    </div>

                                                                    {/* Modifiers */}
                                                                    {quantity > 0 && item.modifiers && item.modifiers.length > 0 && (
                                                                        <div className="mt-3 pt-3 border-t">
                                                                            <p className="text-sm font-medium mb-2">Modifiers:</p>
                                                                            <div className="space-y-1">
                                                                                {item.modifiers.map(modifier => {
                                                                                    const isSelected = (selectedModifiers[item.id] || []).includes(modifier.id);
                                                                                    return (
                                                                                        <label
                                                                                            key={modifier.id}
                                                                                            className="flex items-center gap-2 text-sm cursor-pointer"
                                                                                        >
                                                                                            <input
                                                                                                type="checkbox"
                                                                                                checked={isSelected}
                                                                                                onChange={() => toggleModifier(item.id, modifier.id)}
                                                                                                className="rounded"
                                                                                            />
                                                                                            <span>{modifier.name}</span>
                                                                                            <span className="text-gray-500">
                                                                                                +{formatCurrency(modifier.price)}
                                                                                            </span>
                                                                                        </label>
                                                                                    );
                                                                                })}
                                                                            </div>
                                                                        </div>
                                                                    )}

                                                                    {/* Item notes */}
                                                                    {quantity > 0 && (
                                                                        <div className="mt-3">
                                                                            <Input
                                                                                placeholder="Special instructions..."
                                                                                value={orderItem?.notes || ''}
                                                                                onChange={e => updateItemNotes(item.id, e.target.value)}
                                                                                className="text-sm"
                                                                            />
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                </div>
                                            ))}
                                        </CardContent>
                                    </Card>
                                </div>

                                {/* Order Summary Sidebar */}
                                <div>
                                    <Card className="sticky top-4">
                                        <CardHeader>
                                            <CardTitle>Current Order</CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            {data.items.length === 0 ? (
                                                <p className="text-gray-500 text-center py-4">No items added</p>
                                            ) : (
                                                <div className="space-y-3">
                                                    {data.items.map(orderItem => {
                                                        const menuItem = items.find(i => i.id === orderItem.item_id);
                                                        if (!menuItem) return null;
                                                        
                                                        return (
                                                            <div key={orderItem.item_id} className="flex justify-between items-start">
                                                                <div className="flex-1">
                                                                    <p className="font-medium">
                                                                        {orderItem.quantity}x {menuItem.name}
                                                                    </p>
                                                                    {orderItem.notes && (
                                                                        <p className="text-sm text-gray-500">{orderItem.notes}</p>
                                                                    )}
                                                                </div>
                                                                <div className="text-right">
                                                                    <p>{formatCurrency(menuItem.price * orderItem.quantity)}</p>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => removeItem(orderItem.item_id)}
                                                                        className="text-xs text-red-600 hover:underline"
                                                                    >
                                                                        Remove
                                                                    </button>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                    
                                                    <Separator />
                                                    
                                                    <div className="space-y-2 text-sm">
                                                        <div className="flex justify-between">
                                                            <span>Subtotal</span>
                                                            <span>{formatCurrency(subtotal)}</span>
                                                        </div>
                                                        <div className="flex justify-between">
                                                            <span>Tax ({(TAX_RATE * 100).toFixed(0)}%)</span>
                                                            <span>{formatCurrency(tax)}</span>
                                                        </div>
                                                        <div className="flex justify-between font-semibold text-base">
                                                            <span>Total</span>
                                                            <span>{formatCurrency(total)}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>
                        )}

                        {currentStep === 'summary' && (
                            <Card>
                                <CardHeader className="px-6 pt-6">
                                    <CardTitle>Order Summary</CardTitle>
                                    <CardDescription>Review your order before submitting</CardDescription>
                                </CardHeader>
                                <CardContent className="p-6 space-y-6">
                                    {/* Order Type and Location */}
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-sm text-gray-500">Order Type</p>
                                            <p className="font-medium">{ORDER_TYPE_CONFIG[data.type].label}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm text-gray-500">Location</p>
                                            <p className="font-medium">
                                                {locations.find(l => l.id === data.location_id)?.name}
                                            </p>
                                        </div>
                                        {data.table_number && (
                                            <div>
                                                <p className="text-sm text-gray-500">Table</p>
                                                <p className="font-medium">Table {data.table_number}</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Customer Info */}
                                    {(data.customer_name || data.customer_phone || data.customer_email) && (
                                        <>
                                            <Separator />
                                            <div>
                                                <h3 className="font-medium mb-3">Customer Information</h3>
                                                <div className="space-y-2 text-sm">
                                                    {data.customer_name && (
                                                        <p><span className="text-gray-500">Name:</span> {data.customer_name}</p>
                                                    )}
                                                    {data.customer_phone && (
                                                        <p><span className="text-gray-500">Phone:</span> {data.customer_phone}</p>
                                                    )}
                                                    {data.customer_email && (
                                                        <p><span className="text-gray-500">Email:</span> {data.customer_email}</p>
                                                    )}
                                                    {data.delivery_address && (
                                                        <p><span className="text-gray-500">Delivery Address:</span> {data.delivery_address}</p>
                                                    )}
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    {/* Order Items */}
                                    <Separator />
                                    <div>
                                        <h3 className="font-medium mb-3">Order Items</h3>
                                        <div className="space-y-3">
                                            {data.items.map(orderItem => {
                                                const menuItem = items.find(i => i.id === orderItem.item_id);
                                                if (!menuItem) return null;
                                                
                                                return (
                                                    <div key={orderItem.item_id} className="flex justify-between">
                                                        <div>
                                                            <p className="font-medium">
                                                                {orderItem.quantity}x {menuItem.name}
                                                            </p>
                                                            {orderItem.notes && (
                                                                <p className="text-sm text-gray-500">{orderItem.notes}</p>
                                                            )}
                                                        </div>
                                                        <p>{formatCurrency(menuItem.price * orderItem.quantity)}</p>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>

                                    {/* Special Instructions */}
                                    <div>
                                        <Label htmlFor="specialInstructions">Special Instructions for Kitchen</Label>
                                        <Textarea
                                            id="specialInstructions"
                                            value={data.special_instructions || ''}
                                            onChange={e => setData('special_instructions', e.target.value)}
                                            placeholder="Any special preparation instructions..."
                                            rows={3}
                                        />
                                    </div>

                                    {/* Order Notes */}
                                    <div>
                                        <Label htmlFor="notes">Order Notes</Label>
                                        <Textarea
                                            id="notes"
                                            value={data.notes || ''}
                                            onChange={e => setData('notes', e.target.value)}
                                            placeholder="Additional notes about the order..."
                                            rows={3}
                                        />
                                    </div>

                                    {/* Total */}
                                    <Separator />
                                    <div className="space-y-2">
                                        <div className="flex justify-between">
                                            <span>Subtotal</span>
                                            <span>{formatCurrency(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Tax</span>
                                            <span>{formatCurrency(tax)}</span>
                                        </div>
                                        <div className="flex justify-between text-lg font-semibold">
                                            <span>Total</span>
                                            <span>{formatCurrency(total)}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {currentStep === 'payment' && (
                            <Card>
                                <CardHeader className="px-6 pt-6">
                                    <CardTitle>Payment Information</CardTitle>
                                    <CardDescription>This order will be created with pending payment status</CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="text-center py-8">
                                        <CreditCard className="w-16 h-16 mx-auto text-gray-400 mb-4" />
                                        <p className="text-lg font-medium mb-2">Order Total: {formatCurrency(total)}</p>
                                        <p className="text-gray-600">
                                            Payment will be processed separately after order creation
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Error Messages */}
                    {Object.keys(errors).length > 0 && (
                        <Card className="mb-6 border-red-200 bg-red-50">
                            <CardContent className="p-6">
                                <p className="text-red-600 font-medium mb-2">Please fix the following errors:</p>
                                <ul className="list-disc list-inside space-y-1">
                                    {Object.entries(errors).map(([field, error]) => (
                                        <li key={field} className="text-red-600 text-sm">{error}</li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}

                    {/* Navigation Buttons */}
                    <div className="flex justify-between">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={prevStep}
                            disabled={currentStep === STEPS[0].id}
                        >
                            <ChevronLeft className="w-4 h-4 mr-2" />
                            Previous
                        </Button>

                        {currentStep !== STEPS[STEPS.length - 1].id ? (
                            <Button
                                type="button"
                                onClick={nextStep}
                                disabled={!canProceed()}
                            >
                                Next
                                <ChevronRight className="w-4 h-4 ml-2" />
                            </Button>
                        ) : (
                            <Button
                                type="submit"
                                disabled={processing || !canProceed()}
                            >
                                {processing ? 'Creating Order...' : 'Create Order'}
                            </Button>
                        )}
                    </div>
                </form>
                </PageContent>
            </PageLayout>
        </AppLayout>
    );
}