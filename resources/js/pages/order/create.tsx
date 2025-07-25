import { BottomActionBar, MenuItemCard, ViewModeToggle, type ViewMode } from '@/components/modules/order';
import { PageContent, PageHeader, PageLayout } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { CreateOrderRequest, OrderType } from '@/types/modules/order';
import { ORDER_TYPE_CONFIG } from '@/types/modules/order/constants';
import { calculateTax, calculateTotal, formatCurrency } from '@/types/modules/order/utils';
import { Head, useForm } from '@inertiajs/react';
import { Clock, CreditCard, DollarSign, Leaf, MapPin, Package, Phone, Receipt, ShoppingBag, Truck, Utensils } from 'lucide-react';
import { FormEventHandler, useEffect, useMemo, useState } from 'react';

interface Props {
  locations: Array<{ id: number; name: string }>;
  tables?: Array<{ id: number; number: number; available: boolean }>;
  items?: Array<{
    id: number;
    name: string;
    price: number;
    category: string;
    description?: string;
    image?: string;
    spicyLevel?: number;
    isVegetarian?: boolean;
    preparationTime?: number;
    calories?: number;
    rating?: number;
    modifiers?: Array<{
      id: number;
      name: string;
      price: number;
      description?: string;
    }>;
  }>;
}

type OrderStep = 'menu' | 'details';

export default function CreateOrder({ locations, tables = [], items = [] }: Props) {
  const [currentStep, setCurrentStep] = useState<OrderStep>('menu');
  const [selectedModifiers, setSelectedModifiers] = useState<Record<number, number[]>>({});

  const [activeCard, setActiveCard] = useState<number | null>(null);
  const [editingQuantity, setEditingQuantity] = useState<string | null>(null);
  const [orderItemModifiers, setOrderItemModifiers] = useState<Record<string, number[]>>({});
  const [viewMode, setViewMode] = useState<ViewMode>(() => {
    const saved = localStorage.getItem('orderViewMode');
    return (saved as ViewMode) || 'standard';
  });

  useEffect(() => {
    localStorage.setItem('orderViewMode', viewMode);
  }, [viewMode]);

  const { data, setData, post, processing, errors } = useForm<CreateOrderRequest>({
    userId: null,
    locationId: locations[0]?.id || 1,
    type: 'dine_in' as OrderType,
    tableNumber: tables.find((t) => t.available)?.number || null,
    customerName: '',
    customerPhone: '',
    customerEmail: '',
    deliveryAddress: '',
    items: [],
    notes: '',
    specialInstructions: '',
    offerCodes: null,
    metadata: null,
  });

  // Calculate order totals
  const subtotal = useMemo(() => {
    return data.items.reduce((sum: number, item: any) => {
      const menuItem = items.find((i) => i.id === item.item_id);
      if (!menuItem) return sum;

      let itemTotal = menuItem.price * item.quantity;

      // Add modifier prices
      const modifiers = selectedModifiers[item.item_id] || [];
      modifiers.forEach((modId) => {
        const modifier = menuItem.modifiers?.find((m) => m.id === modId);
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
    const itemsWithModifiers = data.items.map((item: any) => ({
      ...item,
      modifiers: selectedModifiers[item.item_id] || [],
    }));

    const postData = { ...data, items: itemsWithModifiers } as CreateOrderRequest;
    post('/orders', {
      preserveScroll: true,
    });
  };

  const canProceedToDetails = () => {
    return data.items.length > 0;
  };

  const canPlaceOrder = () => {
    if (!data.customerName || !data.customerPhone) return false;
    if (data.type === 'delivery' && !data.deliveryAddress) return false;
    if (data.type === 'dine_in' && !data.tableNumber) return false;
    return true;
  };

  // Helper function to create unique key for item configuration
  const getItemConfigKey = (itemId: number, modifiers: number[] = [], notes: string = '') => {
    return `${itemId}-${modifiers.sort().join(',')}-${notes}`;
  };

  const addItem = (itemId: number) => {
    const currentModifiers = selectedModifiers[itemId] || [];
    const currentNotes = '';

    // Create a unique key for this order item
    const orderItemKey = `item-${itemId}-${Date.now()}-${Math.random()}`;

    // Always create a new line item - this allows different configurations to be separate
    const newOrderItem = {
      item_id: itemId,
      quantity: 1,
      notes: currentNotes,
      orderItemKey, // Add unique key
    };

    setData('items', [...data.items, newOrderItem]);

    // Store modifiers for this specific order item
    if (currentModifiers.length > 0) {
      setOrderItemModifiers({
        ...orderItemModifiers,
        [orderItemKey]: currentModifiers,
      });
    }

    setActiveCard(itemId);
  };

  const removeItem = (itemId: number) => {
    setData(
      'items',
      data.items.filter((item: any) => item.item_id !== itemId),
    );
    // Clean up modifiers
    const newModifiers = { ...selectedModifiers };
    delete newModifiers[itemId];
    setSelectedModifiers(newModifiers);
  };

  const updateItemQuantity = (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeItem(itemId);
    } else {
      setData(
        'items',
        data.items.map((item: any) => (item.item_id === itemId ? { ...item, quantity } : item)),
      );
    }
  };

  const updateItemNotes = (itemId: number, notes: string) => {
    setData(
      'items',
      data.items.map((item: any) => (item.item_id === itemId ? { ...item, notes } : item)),
    );
  };

  // Functions that work with array indices for order summary
  const updateItemQuantityByIndex = (index: number, quantity: number) => {
    console.log('updateItemQuantityByIndex called:', index, quantity, data.items.length);
    if (quantity <= 0) {
      removeItemByIndex(index);
    } else {
      const newItems = [...data.items];
      if (newItems[index]) {
        newItems[index] = { ...newItems[index], quantity };
        setData('items', newItems);
        console.log('Updated items:', newItems);
      } else {
        console.error('Invalid index:', index, 'for items:', newItems);
      }
    }
  };

  const removeItemByIndex = (index: number) => {
    console.log('removeItemByIndex called:', index);
    const newItems = [...data.items];
    if (newItems[index]) {
      newItems.splice(index, 1);
      setData('items', newItems);
      setEditingQuantity(null);
      console.log('Removed item, new items:', newItems);
    } else {
      console.error('Invalid index for removal:', index);
    }
  };

  const toggleModifier = (itemId: number, modifierId: number) => {
    const current = selectedModifiers[itemId] || [];
    const newModifiers = current.includes(modifierId) ? current.filter((id) => id !== modifierId) : [...current, modifierId];

    setSelectedModifiers({
      ...selectedModifiers,
      [itemId]: newModifiers,
    });
  };

  const getTypeIcon = (type: OrderType) => {
    switch (type) {
      case 'dine_in':
        return Utensils;
      case 'takeout':
        return Package;
      case 'delivery':
        return Truck;
      default:
        return ShoppingBag;
    }
  };

  // Removed OrderSummary component - now using BottomActionBar instead

  return (
    <AppLayout>
      <Head title="Create Order" />

      <PageLayout>
        <PageHeader title="New Order" description="Select items from the menu" showBackButton backHref="/orders">
          {currentStep === 'menu' && <ViewModeToggle value={viewMode} onChange={setViewMode} />}
        </PageHeader>

        <PageContent noPadding className="relative pb-24">
          <form onSubmit={handleSubmit}>
            <div className="min-h-[calc(100vh-160px)]">
              {currentStep === 'menu' ? (
                /* Step 1: Menu Selection */
                <div className="bg-gray-50/50 px-4 sm:px-6 lg:px-8">
                  <div className="mx-auto max-w-7xl">
                    <div className="space-y-16 pt-8">
                      {Object.entries(
                        items.reduce(
                          (acc, item) => {
                            if (!acc[item.category]) acc[item.category] = [];
                            acc[item.category].push(item);
                            return acc;
                          },
                          {} as Record<string, typeof items>,
                        ),
                      ).map(([category, categoryItems], categoryIndex) => (
                        <div key={category} className="relative">
                          {/* Category Header */}
                          <div className="mb-10">
                            <div className="mb-4 flex items-center justify-between">
                              <div className="flex items-center gap-4">
                                <h2 className="text-4xl font-bold text-gray-900">{category}</h2>
                                <div className="hidden items-center gap-2 sm:flex">
                                  <Badge variant="outline" className="text-sm font-medium">
                                    {categoryItems.length} {categoryItems.length === 1 ? 'item' : 'items'}
                                  </Badge>
                                  {categoryItems.filter((item) => item.isVegetarian).length > 0 && (
                                    <Badge variant="outline" className="border-green-300 text-sm font-medium text-green-700">
                                      <Leaf className="mr-1 h-3 w-3" />
                                      {categoryItems.filter((item) => item.isVegetarian).length} Veg
                                    </Badge>
                                  )}
                                </div>
                              </div>

                              {/* Category description or special note */}
                              {categoryIndex === 0 && <p className="hidden text-sm text-gray-500 lg:block">Fresh & delicious options</p>}
                            </div>
                            <div className="relative">
                              <div className="absolute inset-0 flex items-center" aria-hidden="true">
                                <div className="w-full border-t-2 border-gray-200"></div>
                              </div>
                              <div className="relative flex justify-start">
                                <span className="bg-gray-50 pr-3">
                                  <div className="h-2 w-32 rounded-full bg-gradient-to-r from-primary to-primary/60"></div>
                                </span>
                              </div>
                            </div>
                          </div>

                          {/* Items Grid */}
                          <div
                            className={cn(
                              viewMode === 'compact' && 'space-y-3',
                              viewMode === 'standard' && 'grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3',
                            )}
                          >
                            {categoryItems.map((item: any) => {
                              const orderItem = data.items.find((i: any) => i.item_id === item.id);
                              const quantity = orderItem?.quantity || 0;
                              const itemModifiers = selectedModifiers[item.id] || [];

                              return (
                                <MenuItemCard
                                  key={item.id}
                                  item={{
                                    ...item,
                                    spicyLevel: item.spicyLevel || 0,
                                    isVegetarian: item.isVegetarian || false,
                                    preparationTime: item.preparationTime || 15,
                                    calories: item.calories,
                                    rating: item.rating || 4.5,
                                  }}
                                  viewMode={viewMode}
                                  quantity={quantity}
                                  selectedModifiers={itemModifiers}
                                  notes={orderItem?.notes || ''}
                                  isActive={quantity > 0 && activeCard === item.id}
                                  onAdd={() => addItem(item.id)}
                                  onUpdateQuantity={(qty) => updateItemQuantity(item.id, qty)}
                                  onToggleModifier={(modId) => toggleModifier(item.id, modId)}
                                  onUpdateNotes={(notes) => updateItemNotes(item.id, notes)}
                                  onClick={() => quantity > 0 && setActiveCard(activeCard === item.id ? null : item.id)}
                                />
                              );
                            })}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ) : (
                /* Step 2: Customer Details & Checkout */
                <div className="px-4 py-8 sm:px-6 lg:px-8">
                  <div className="mx-auto max-w-3xl">
                    <div className="space-y-6">
                      {/* Order Type Card */}
                      <Card>
                        <CardHeader>
                          <CardTitle>Order Type</CardTitle>
                        </CardHeader>
                        <CardContent>
                          <div className="grid grid-cols-3 gap-4">
                            {(['dine_in', 'takeout', 'delivery'] as OrderType[]).map((type) => {
                              const Icon = getTypeIcon(type);
                              const config = ORDER_TYPE_CONFIG[type];
                              return (
                                <button
                                  key={type}
                                  type="button"
                                  onClick={() => setData('type', type)}
                                  className={cn(
                                    'relative flex flex-col items-center gap-3 rounded-lg border-2 p-6 transition-all',
                                    data.type === type
                                      ? 'border-primary bg-primary/5 text-primary shadow-sm'
                                      : 'border-input hover:border-muted-foreground hover:bg-muted/50',
                                  )}
                                >
                                  <Icon className="h-8 w-8" />
                                  <span className="font-medium">{config.label}</span>
                                </button>
                              );
                            })}
                          </div>
                        </CardContent>
                      </Card>

                      {/* Customer Information Card */}
                      <Card>
                        <CardHeader>
                          <CardTitle>Customer Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                          <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                              <Label htmlFor="customerName">Name *</Label>
                              <Input
                                id="customerName"
                                value={data.customerName || ''}
                                onChange={(e) => setData('customerName', e.target.value)}
                                placeholder="Customer name"
                                className="h-11"
                                required
                              />
                            </div>
                            <div className="space-y-2">
                              <Label htmlFor="customerPhone">
                                <Phone className="mr-1 inline h-4 w-4" />
                                Phone *
                              </Label>
                              <Input
                                id="customerPhone"
                                value={data.customerPhone || ''}
                                onChange={(e) => setData('customerPhone', e.target.value)}
                                placeholder="+56 9 1234 5678"
                                className="h-11"
                                required
                              />
                            </div>
                          </div>

                          {/* Table Selection for Dine In */}
                          {data.type === 'dine_in' && tables.length > 0 && (
                            <div className="space-y-2 pt-2">
                              <Label>Table Number *</Label>
                              <div className="grid grid-cols-6 gap-2 sm:grid-cols-8">
                                {tables
                                  .filter((t) => t.available)
                                  .map((table) => (
                                    <button
                                      key={table.id}
                                      type="button"
                                      onClick={() => setData('tableNumber', table.number)}
                                      className={cn(
                                        'h-12 rounded-lg border-2 text-sm font-medium transition-all',
                                        data.tableNumber === table.number
                                          ? 'border-primary bg-primary text-primary-foreground'
                                          : 'border-input hover:border-muted-foreground hover:bg-muted/50',
                                      )}
                                    >
                                      {table.number}
                                    </button>
                                  ))}
                              </div>
                            </div>
                          )}

                          {/* Delivery Address */}
                          {data.type === 'delivery' && (
                            <div className="space-y-2 pt-2">
                              <Label htmlFor="deliveryAddress">
                                <MapPin className="mr-1 inline h-4 w-4" />
                                Delivery Address *
                              </Label>
                              <Textarea
                                id="deliveryAddress"
                                value={data.deliveryAddress || ''}
                                onChange={(e) => setData('deliveryAddress', e.target.value)}
                                placeholder="Street address, apartment, suite, floor, etc."
                                rows={3}
                                className="resize-none"
                                required
                              />
                            </div>
                          )}
                        </CardContent>
                      </Card>

                      {/* Special Instructions Card */}
                      <Card>
                        <CardHeader>
                          <CardTitle>Special Instructions</CardTitle>
                        </CardHeader>
                        <CardContent>
                          <Textarea
                            value={data.specialInstructions || ''}
                            onChange={(e) => setData('specialInstructions', e.target.value)}
                            placeholder="Any special requests, allergies, or dietary requirements..."
                            rows={4}
                            className="resize-none"
                          />
                        </CardContent>
                      </Card>

                      {/* Payment Method Card */}
                      <Card>
                        <CardHeader>
                          <CardTitle>Payment Method</CardTitle>
                        </CardHeader>
                        <CardContent>
                          <div className="grid grid-cols-2 gap-4">
                            <Button type="button" variant="outline" className="h-24 flex-col gap-3" disabled>
                              <DollarSign className="h-6 w-6" />
                              <span>Cash</span>
                            </Button>
                            <Button type="button" variant="outline" className="h-24 flex-col gap-3" disabled>
                              <CreditCard className="h-6 w-6" />
                              <span>Card</span>
                            </Button>
                          </div>
                          <p className="mt-4 text-center text-sm text-muted-foreground">Payment will be collected after order confirmation</p>
                        </CardContent>
                      </Card>

                      {/* Order Review Card */}
                      <Card>
                        <CardHeader>
                          <CardTitle className="flex items-center gap-2">
                            <Receipt className="h-5 w-5" />
                            Order Review
                          </CardTitle>
                        </CardHeader>
                        <CardContent>
                          <div className="space-y-4">
                            {/* Items Summary */}
                            <div>
                              <h4 className="mb-2 font-medium">Items ({data.items.reduce((sum: number, item: any) => sum + item.quantity, 0)})</h4>
                              <div className="space-y-2">
                                {data.items.map((orderItem: any, index: number) => {
                                  const menuItem = items.find((i) => i.id === orderItem.item_id);
                                  if (!menuItem) return null;

                                  const itemModifiers = selectedModifiers[orderItem.item_id] || [];
                                  const modifierPrice = itemModifiers.reduce((sum, modId) => {
                                    const mod = menuItem.modifiers?.find((m) => m.id === modId);
                                    return sum + (mod?.price || 0);
                                  }, 0);
                                  const itemTotal = (menuItem.price + modifierPrice) * orderItem.quantity;

                                  return (
                                    <div key={`${orderItem.item_id}-${index}`} className="flex justify-between text-sm">
                                      <span className="text-muted-foreground">
                                        {orderItem.quantity}× {menuItem.name}
                                      </span>
                                      <span className="font-medium tabular-nums">{formatCurrency(itemTotal)}</span>
                                    </div>
                                  );
                                })}
                              </div>
                            </div>

                            <Separator />

                            {/* Totals */}
                            <div className="space-y-2">
                              <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span className="tabular-nums">{formatCurrency(subtotal)}</span>
                              </div>
                              <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Tax (19%)</span>
                                <span className="tabular-nums">{formatCurrency(tax)}</span>
                              </div>
                              <Separator />
                              <div className="flex items-baseline justify-between">
                                <span className="font-semibold">Total</span>
                                <span className="text-xl font-bold text-primary tabular-nums">{formatCurrency(total)}</span>
                              </div>
                            </div>

                            {/* Estimated Time */}
                            <div className="flex items-center gap-2 rounded-lg bg-muted/50 p-3">
                              <Clock className="h-4 w-4 text-muted-foreground" />
                              <span className="text-sm">
                                Estimated preparation time: <strong>15-20 minutes</strong>
                              </span>
                            </div>
                          </div>
                        </CardContent>
                      </Card>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </form>
        </PageContent>
      </PageLayout>

      {/* Bottom Action Bar - Outside of PageLayout for proper positioning */}
      <BottomActionBar
        data={data}
        items={items}
        locations={locations}
        currentStep={currentStep}
        subtotal={subtotal}
        tax={tax}
        total={total}
        canProceedToDetails={canProceedToDetails()}
        canPlaceOrder={canPlaceOrder()}
        processing={processing}
        onContinueToDetails={() => setCurrentStep('details')}
        onPlaceOrder={(e: any) => handleSubmit(e)}
        onUpdateQuantity={updateItemQuantityByIndex}
        onRemoveItem={removeItemByIndex}
        selectedModifiers={selectedModifiers}
      />
    </AppLayout>
  );
}
