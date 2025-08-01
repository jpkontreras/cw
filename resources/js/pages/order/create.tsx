import { BottomActionBar, MenuItemCard, ViewModeToggle, type ViewMode } from '@/components/modules/order';
import { PageContent, PageHeader, PageLayout } from '@/components/page';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/empty-state';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { CreateOrderRequest, OrderType } from '@/types/modules/order';
import { ORDER_TYPE_CONFIG } from '@/types/modules/order/constants';
import { calculateTax, calculateTotal, formatCurrency } from '@/types/modules/order/utils';
import { Head, useForm } from '@inertiajs/react';
import { Clock, CreditCard, DollarSign, Package, Plus, ShoppingBag, Truck, Utensils } from 'lucide-react';
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
    tableNumber: null,
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
    if (!data.customerName) return false;
    if (data.type === 'delivery' && (!data.customerPhone || !data.deliveryAddress)) return false;
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

      <PageLayout className="flex flex-col h-full overflow-hidden">
        <PageHeader title="New Order" description="Select items from the menu" showBackButton backHref="/orders">
          {currentStep === 'menu' && <ViewModeToggle value={viewMode} onChange={setViewMode} />}
        </PageHeader>

        <div className="flex-1 flex flex-col min-h-0">
          <PageContent noPadding className="flex-1 overflow-y-auto">
            <form onSubmit={handleSubmit}>
              <div>
              {currentStep === 'menu' ? (
                /* Step 1: Menu Selection */
                <div className="bg-white px-4 sm:px-6 lg:px-8 pb-4">
                  <div className="mx-auto max-w-[1400px]">
                    <div className="space-y-12 pt-6 pb-4">
                      {items.length === 0 ? (
                        <EmptyState
                          icon={ShoppingBag}
                          title="Start building your menu"
                          description="Add delicious items to your menu to start accepting orders. Your customers are waiting!"
                          actions={
                            <>
                              <Button size="lg" asChild>
                                <a href="/items/create" className="inline-flex items-center gap-2">
                                  <Plus className="h-5 w-5" />
                                  Add your first item
                                </a>
                              </Button>
                              <Button variant="outline" size="lg" asChild>
                                <a href="/items">Browse items</a>
                              </Button>
                            </>
                          }
                          helpText={
                            <>
                              Need help? Check out our <a href="#" className="text-primary hover:underline">menu setup guide</a>
                            </>
                          }
                        />
                      ) : Object.entries(
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
                          <div className="mb-6">
                            <div className="flex items-baseline justify-between">
                              <div className="flex items-baseline gap-3">
                                <h2 className="text-2xl font-bold tracking-tight text-gray-900">{category}</h2>
                                <span className="text-base text-gray-500">{categoryItems.length} items</span>
                              </div>
                              {categoryIndex === 0 && <p className="hidden text-sm text-gray-500 lg:block">Fresh & delicious options</p>}
                            </div>
                            <div className="mt-3 h-0.5 w-20 rounded-full bg-gradient-to-r from-primary to-primary/40"></div>
                          </div>

                          {/* Items Grid */}
                          <div
                            className={cn(
                              viewMode === 'compact' && 'space-y-3',
                              viewMode === 'standard' && 'grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 items-start',
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
                <div className="px-4 py-6 sm:px-6 lg:px-8">
                  <div className="mx-auto max-w-[1440px]">
                    <Card className="p-8">
                      <div className="space-y-6">
                      {/* Order Type Selection */}
                      <div className="space-y-3">
                        <h3 className="text-base font-medium">Order Type</h3>
                        <div className="grid grid-cols-3 gap-3">
                          {(['dine_in', 'takeout', 'delivery'] as OrderType[]).map((type) => {
                            const Icon = getTypeIcon(type);
                            const config = ORDER_TYPE_CONFIG[type];
                            return (
                              <button
                                key={type}
                                type="button"
                                onClick={() => setData('type', type)}
                                className={cn(
                                  'relative flex items-center justify-center gap-3 rounded-lg border-2 px-4 py-3 text-base font-medium transition-all',
                                  data.type === type
                                    ? 'border-gray-900 bg-gray-900 text-white'
                                    : 'border-gray-300 bg-white text-gray-700 hover:border-gray-400',
                                )}
                              >
                                <Icon className="h-5 w-5" />
                                <span>{config.label}</span>
                              </button>
                            );
                          })}
                        </div>
                      </div>

                      <Separator className="my-6" />

                      {/* Customer Information */}
                      <div className="space-y-4">
                        <h3 className="text-base font-medium">Customer Information</h3>
                        <div className="space-y-3">
                          <div>
                            <Label htmlFor="customerName" className="text-sm font-medium mb-2 block">Name *</Label>
                            <Input
                              id="customerName"
                              value={data.customerName || ''}
                              onChange={(e) => setData('customerName', e.target.value)}
                              placeholder="Customer name"
                              className="h-11 text-base"
                            />
                          </div>
                          {data.type === 'delivery' && (
                            <>
                              <div>
                                <Label htmlFor="customerPhone" className="text-sm font-medium mb-2 block">
                                  Phone *
                                </Label>
                                <Input
                                  id="customerPhone"
                                  value={data.customerPhone || ''}
                                  onChange={(e) => setData('customerPhone', e.target.value)}
                                  placeholder="+56 9 1234 5678"
                                  className="h-11 text-base"
                                />
                              </div>
                              <div>
                                <Label htmlFor="deliveryAddress" className="text-sm font-medium mb-2 block">
                                  Delivery Address *
                                </Label>
                                <Textarea
                                  id="deliveryAddress"
                                  value={data.deliveryAddress || ''}
                                  onChange={(e) => setData('deliveryAddress', e.target.value)}
                                  placeholder="Street address"
                                  rows={3}
                                  className="resize-none text-base"
                                />
                              </div>
                            </>
                          )}
                        </div>
                      </div>

                      <Separator className="my-6" />

                      {/* Special Instructions */}
                      <div className="space-y-3">
                        <h3 className="text-base font-medium">Special Instructions</h3>
                        <Textarea
                          id="specialInstructions"
                          value={data.specialInstructions || ''}
                          onChange={(e) => setData('specialInstructions', e.target.value)}
                          placeholder="Any special requests..."
                          rows={4}
                          className="resize-none text-base"
                        />
                      </div>

                      <Separator className="my-6" />

                      {/* Payment Method */}
                      <div className="space-y-3">
                        <h3 className="text-base font-medium">Payment Method</h3>
                        <div className="grid grid-cols-2 gap-3">
                          <Button type="button" variant="outline" className="h-12 gap-3 text-base" disabled>
                            <DollarSign className="h-5 w-5" />
                            <span>Cash</span>
                          </Button>
                          <Button type="button" variant="outline" className="h-12 gap-3 text-base" disabled>
                            <CreditCard className="h-5 w-5" />
                            <span>Card</span>
                          </Button>
                        </div>
                        <p className="text-sm text-muted-foreground">Payment collected after confirmation</p>
                      </div>

                      <Separator className="my-6" />

                      {/* Order Summary */}
                      <div className="rounded-lg border bg-gray-50 p-6 space-y-4">
                        <div className="flex items-center justify-between">
                          <h3 className="text-base font-medium">Order Summary</h3>
                          <span className="text-sm text-muted-foreground">{data.items.reduce((sum: number, item: any) => sum + item.quantity, 0)} items</span>
                        </div>
                        
                        {/* Item list */}
                        <div className="space-y-2 max-h-40 overflow-y-auto">
                          {data.items.map((orderItem: any, index: number) => {
                            const menuItem = items.find((i) => i.id === orderItem.item_id);
                            if (!menuItem) return null;
                            return (
                              <div key={`${orderItem.item_id}-${index}`} className="flex justify-between text-sm">
                                <span className="text-gray-600">
                                  {orderItem.quantity}× {menuItem.name}
                                </span>
                                <span className="font-medium tabular-nums">{formatCurrency(menuItem.price * orderItem.quantity)}</span>
                              </div>
                            );
                          })}
                        </div>

                        <div className="border-t pt-3 space-y-2">
                          <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Subtotal</span>
                            <span className="tabular-nums">{formatCurrency(subtotal)}</span>
                          </div>
                          <div className="flex justify-between text-sm">
                            <span className="text-gray-600">Tax</span>
                            <span className="tabular-nums">{formatCurrency(tax)}</span>
                          </div>
                          <div className="flex justify-between text-lg font-semibold pt-2 border-t">
                            <span>Total</span>
                            <span className="tabular-nums">{formatCurrency(total)}</span>
                          </div>
                        </div>
                        
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                          <Clock className="h-4 w-4" />
                          <span>15-20 min</span>
                        </div>
                      </div>
                      </div>
                    </Card>
                  </div>
                </div>
              )}
              </div>
            </form>
          </PageContent>
          
          {/* Bottom Action Bar - Sticky at bottom */}
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
        </div>
      </PageLayout>
    </AppLayout>
  );
}
