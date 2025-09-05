import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/currency-input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import PageLayout from '@/layouts/page-layout';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/format';
import { Head, Link, useForm } from '@inertiajs/react';
import {
  AlertCircle,
  AlertTriangle,
  ArrowLeft,
  ArrowRight,
  Calculator,
  Calendar,
  CheckCircle,
  CheckCircle2,
  ChevronDown,
  Coffee,
  CreditCard,
  DollarSign,
  Edit,
  FileText,
  Hash,
  History,
  Home,
  Info,
  Loader2,
  Minus,
  Package,
  Phone,
  Plus,
  Save,
  Search,
  ShoppingBag,
  ShoppingCart,
  Sparkles,
  Tag,
  Trash2,
  TrendingDown,
  TrendingUp,
  User,
  Users,
  Utensils,
  XCircle,
} from 'lucide-react';
import { useState } from 'react';

interface OrderItem {
  id: number;
  itemId: number;
  itemName: string;
  quantity: number;
  unitPrice: number;
  totalPrice: number;
  notes?: string;
  modifiers?: any[];
  status: string;
  isModified?: boolean;
  isNew?: boolean;
  isRemoved?: boolean;
}

interface OrderData {
  id: number;
  uuid: string;
  orderNumber: string;
  status: string;
  type: string;
  customerName?: string;
  customerPhone?: string;
  customerEmail?: string;
  deliveryAddress?: string;
  tableNumber?: string;
  notes?: string;
  specialInstructions?: string;
  subtotal: number;
  discount: number;
  tax: number;
  tip: number;
  total: number;
  items: OrderItem[];
  paymentMethod?: string;
  paymentStatus: string;
  createdAt: string;
  updatedAt: string;
  modificationCount?: number;
  lastModifiedAt?: string;
  lastModifiedBy?: string;
  metadata?: {
    modifications?: any[];
    priceAdjustments?: any[];
  };
  canBeModified: () => boolean;
}

interface EditOrderProps {
  order: OrderData;
  permissions?: {
    canModify: boolean;
    canAddItems: boolean;
    canRemoveItems: boolean;
    canAdjustPrice: boolean;
    canCancel: boolean;
    requiresAuthorization: boolean;
  };
}

export default function EditOrder({ order: initialOrder, permissions = {} }: EditOrderProps) {
  const [order, setOrder] = useState<OrderData>(initialOrder);
  const [searchQuery, setSearchQuery] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [searchResults, setSearchResults] = useState<any[]>([]);
  const [modificationReason, setModificationReason] = useState('');
  const [priceAdjustment, setPriceAdjustment] = useState({
    type: 'discount',
    amount: 0,
    reason: '',
  });
  const [showPriceAdjustment, setShowPriceAdjustment] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [showModificationHistory, setShowModificationHistory] = useState(false);

  // Track changes
  const [itemsToAdd, setItemsToAdd] = useState<OrderItem[]>([]);
  const [itemsToRemove, setItemsToRemove] = useState<number[]>([]);
  const [itemsToModify, setItemsToModify] = useState<OrderItem[]>([]);

  const form = useForm({
    items: order.items,
    modificationReason: '',
    priceAdjustment: null,
  });

  // Calculate if there are any changes
  const hasChanges = itemsToAdd.length > 0 || itemsToRemove.length > 0 || itemsToModify.length > 0;

  // Calculate new totals
  const calculateNewTotal = () => {
    // Start with the original order total
    let total = order.total || 0;

    // Subtract removed items
    itemsToRemove.forEach((itemId) => {
      const item = order.items.find((i) => i.id === itemId);
      if (item) {
        total -= item.totalPrice || 0;
      }
    });

    // Adjust for modified items (subtract old, add new)
    itemsToModify.forEach((modifiedItem) => {
      const originalItem = order.items.find((i) => i.id === modifiedItem.id);
      if (originalItem) {
        total -= originalItem.totalPrice || 0;
        total += (modifiedItem.quantity || 1) * (modifiedItem.unitPrice || 0);
      }
    });

    // Add new items
    itemsToAdd.forEach((item) => {
      total += (item.quantity || 1) * (item.unitPrice || 0);
    });

    // Apply price adjustment if any
    if (showPriceAdjustment && priceAdjustment.amount > 0) {
      // Check if amounts are already in the correct unit
      const adjustmentAmount = priceAdjustment.amount;
      if (priceAdjustment.type === 'discount') {
        total -= adjustmentAmount;
      } else if (priceAdjustment.type === 'surcharge') {
        total += adjustmentAmount;
      }
    }

    return total;
  };

  const priceDifference = calculateNewTotal() - order.total;

  // Handle item search
  const handleSearch = async (query: string) => {
    setSearchQuery(query);
    if (query.length < 2) {
      setSearchResults([]);
      return;
    }

    setIsSearching(true);
    try {
      // Use axios to search items - matching the OrderContext implementation
      const response = await fetch(`/items/search?q=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });
      const data = await response.json();
      setSearchResults(data.items || data.data || []);
    } catch (error) {
      console.error('Search error:', error);
    } finally {
      setIsSearching(false);
    }
  };

  // Add item to order
  const handleAddItem = (item: any) => {
    const newItem: OrderItem = {
      id: -Date.now(), // Temporary negative ID for new items
      itemId: item.id,
      itemName: item.name || item.itemName,
      quantity: 1,
      unitPrice: item.price || item.unitPrice || 0,
      totalPrice: item.price || item.unitPrice || 0,
      status: 'pending',
      isNew: true,
    };

    setItemsToAdd([...itemsToAdd, newItem]);
    setSearchQuery('');
    setSearchResults([]);
  };

  // Remove item from order
  const handleRemoveItem = (itemId: number) => {
    if (itemId < 0) {
      // Remove from items to add
      setItemsToAdd(itemsToAdd.filter((item) => item.id !== itemId));
    } else {
      // Add to items to remove
      setItemsToRemove([...itemsToRemove, itemId]);
    }
  };

  // Modify item quantity
  const handleQuantityChange = (itemId: number, newQuantity: number) => {
    if (newQuantity <= 0) {
      handleRemoveItem(itemId);
      return;
    }

    if (itemId < 0) {
      // Modify new item
      setItemsToAdd(
        itemsToAdd.map((item) => (item.id === itemId ? { ...item, quantity: newQuantity, totalPrice: newQuantity * item.unitPrice } : item)),
      );
    } else {
      // Modify existing item
      const existingItem = order.items.find((item) => item.id === itemId);
      if (existingItem) {
        const modifiedItem = {
          ...existingItem,
          quantity: newQuantity,
          totalPrice: newQuantity * existingItem.unitPrice,
          isModified: true,
        };

        setItemsToModify([...itemsToModify.filter((item) => item.id !== itemId), modifiedItem]);
      }
    }
  };

  // Submit modifications
  const handleSubmit = () => {
    if (!hasChanges && !showPriceAdjustment) {
      alert('No changes to save');
      return;
    }

    if (!modificationReason) {
      alert('Please provide a reason for the modification');
      return;
    }

    const data = {
      itemsToAdd: itemsToAdd.map((item) => ({
        item_id: item.itemId,
        quantity: item.quantity,
        unit_price: item.unitPrice,
        notes: item.notes,
        modifiers: item.modifiers,
      })),
      itemsToRemove: itemsToRemove,
      itemsToModify: itemsToModify.map((item) => ({
        item_id: item.itemId,
        quantity: item.quantity,
        notes: item.notes,
        modifiers: item.modifiers,
      })),
      reason: modificationReason,
      priceAdjustment: showPriceAdjustment ? priceAdjustment : null,
    };

    setIsSaving(true);
    form
      .transform(() => data)
      .put(`/orders/${order.id}`, {
        onSuccess: () => {
          // Will redirect to order show page on success
        },
        onError: (errors) => {
          console.error('Error updating order:', errors);
          setIsSaving(false);
        },
        onFinish: () => {
          setIsSaving(false);
        },
      });
  };

  // Cancel order
  const handleCancel = () => {
    if (!confirm('Are you sure you want to cancel this order?')) {
      return;
    }

    const reason = prompt('Please provide a reason for cancellation:');
    if (!reason) {
      return;
    }

    form
      .transform(() => ({ reason }))
      .delete(`/orders/${order.id}`, {
        onSuccess: () => {
          // Will redirect to orders list on success
        },
      });
  };

  // Get status color and icon
  const getStatusDisplay = (status: string) => {
    const displays: Record<string, { color: string; icon: any; bg: string }> = {
      draft: { color: 'text-gray-700', icon: FileText, bg: 'bg-gray-50' },
      placed: { color: 'text-blue-700', icon: ShoppingBag, bg: 'bg-blue-50' },
      confirmed: { color: 'text-indigo-700', icon: CheckCircle, bg: 'bg-indigo-50' },
      preparing: { color: 'text-yellow-700', icon: Coffee, bg: 'bg-yellow-50' },
      ready: { color: 'text-green-700', icon: CheckCircle2, bg: 'bg-green-50' },
      delivered: { color: 'text-emerald-700', icon: Package, bg: 'bg-emerald-50' },
      completed: { color: 'text-teal-700', icon: CheckCircle, bg: 'bg-teal-50' },
      cancelled: { color: 'text-red-700', icon: XCircle, bg: 'bg-red-50' },
    };
    return displays[status] || displays.draft;
  };

  const statusDisplay = getStatusDisplay(order.status);
  const StatusIcon = statusDisplay.icon;

  // Get order type icon
  const getOrderTypeIcon = (type: string) => {
    const icons: Record<string, any> = {
      dine_in: Utensils,
      takeout: ShoppingBag,
      delivery: Home,
      catering: Users,
    };
    return icons[type] || Package;
  };

  const OrderTypeIcon = getOrderTypeIcon(order.type);

  return (
    <AppLayout>
      <Head title={`Edit Order #${order.orderNumber}`} />

      <PageLayout.Header
        title={
          <div className="flex items-center gap-3">
            <Edit className="h-5 w-5 text-gray-500" />
            <span>Edit Order #{order.orderNumber}</span>
          </div>
        }
        subtitle={`Modify items and details for order #${order.orderNumber}`}
        actions={
          <div className="flex items-center gap-3">
            <Link href={`/orders/${order.id}`}>
              <Button variant="outline" size="sm">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Order
              </Button>
            </Link>
            {permissions.canCancel && (
              <Button variant="outline" onClick={handleCancel} className="border-red-200 text-red-600 hover:border-red-300 hover:bg-red-50" size="sm">
                <XCircle className="mr-2 h-4 w-4" />
                Cancel Order
              </Button>
            )}
            <Button
              onClick={handleSubmit}
              disabled={(!hasChanges && !showPriceAdjustment) || isSaving}
              className="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg hover:from-indigo-700 hover:to-purple-700"
              size="sm"
            >
              {isSaving ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Saving...
                </>
              ) : (
                <>
                  <Save className="mr-2 h-4 w-4" />
                  Save Changes
                </>
              )}
            </Button>
          </div>
        }
      />

      <PageLayout.Content>
        <div className="container mx-auto max-w-7xl">
          <div className="grid gap-6 lg:grid-cols-3">
            {/* Main Content */}
            <div className="space-y-6 lg:col-span-2">
              {/* Order Status Card */}
              <Card className="overflow-hidden shadow-sm transition-shadow hover:shadow-md">
                <CardHeader className="pb-6">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                      <StatusIcon className="h-5 w-5 text-gray-500" />
                      <div>
                        <CardTitle className="text-xl">Order Details</CardTitle>
                        <div className="mt-2 flex items-center gap-3">
                          <Badge className={cn('font-semibold', statusDisplay.bg, statusDisplay.color)}>
                            {order.status.charAt(0).toUpperCase() + order.status.slice(1)}
                          </Badge>
                          {order.modificationCount && order.modificationCount > 0 && (
                            <div className="flex items-center gap-1.5 rounded-md bg-amber-50 px-2 py-1 text-sm font-medium text-amber-700">
                              <History className="h-3.5 w-3.5" />
                              Modified {order.modificationCount}x
                            </div>
                          )}
                        </div>
                      </div>
                    </div>
                    <div className="text-right">
                      <p className="text-xs tracking-wider text-gray-500 uppercase">Order ID</p>
                      <p className="text-xl font-bold text-gray-900">#{order.orderNumber}</p>
                    </div>
                  </div>
                </CardHeader>

                <CardContent className="pt-6">
                  <div className="grid grid-cols-2 gap-6">
                    <div className="space-y-3">
                      <div>
                        <div className="mb-1 flex items-center gap-2 text-sm text-gray-500">
                          <User className="h-4 w-4" />
                          <span>Customer</span>
                        </div>
                        <p className="font-medium text-gray-900">{order.customerName || 'Walk-in Customer'}</p>
                        {order.customerPhone && (
                          <p className="mt-1 flex items-center gap-1 text-sm text-gray-600">
                            <Phone className="h-3 w-3" />
                            {order.customerPhone}
                          </p>
                        )}
                      </div>
                    </div>

                    <div className="space-y-3">
                      <div>
                        <div className="mb-1 flex items-center gap-2 text-sm text-gray-500">
                          <OrderTypeIcon className="h-4 w-4" />
                          <span>Service Type</span>
                        </div>
                        <p className="font-medium text-gray-900 capitalize">{order.type?.replace('_', ' ') || 'Dine In'}</p>
                        {order.tableNumber && (
                          <p className="mt-1 flex items-center gap-1 text-sm text-gray-600">
                            <Hash className="h-3 w-3" />
                            Table {order.tableNumber}
                          </p>
                        )}
                      </div>
                    </div>

                    <div className="space-y-3">
                      <div>
                        <div className="mb-1 flex items-center gap-2 text-sm text-gray-500">
                          <CreditCard className="h-4 w-4" />
                          <span>Payment Status</span>
                        </div>
                        <Badge
                          className={cn(
                            'font-medium',
                            order.paymentStatus === 'paid'
                              ? 'bg-green-100 text-green-700'
                              : order.paymentStatus === 'pending'
                                ? 'bg-amber-100 text-amber-700'
                                : order.paymentStatus === 'partial'
                                  ? 'bg-orange-100 text-orange-700'
                                  : 'bg-purple-100 text-purple-700',
                          )}
                        >
                          {order.paymentStatus.charAt(0).toUpperCase() + order.paymentStatus.slice(1)}
                        </Badge>
                      </div>
                    </div>

                    <div className="space-y-3">
                      <div>
                        <div className="mb-1 flex items-center gap-2 text-sm text-gray-500">
                          <Calendar className="h-4 w-4" />
                          <span>Created</span>
                        </div>
                        <p className="font-medium text-gray-900">{new Date(order.createdAt).toLocaleDateString()}</p>
                        <p className="text-sm text-gray-600">{new Date(order.createdAt).toLocaleTimeString()}</p>
                      </div>
                    </div>
                  </div>

                  {order.notes && (
                    <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                      <div className="flex items-start gap-3">
                        <FileText className="mt-0.5 h-4 w-4 text-amber-600" />
                        <div className="flex-1">
                          <p className="text-sm font-semibold text-amber-900">Order Notes</p>
                          <p className="mt-1 text-sm text-amber-700">{order.notes}</p>
                        </div>
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Order Items Card */}
              <Card className="overflow-hidden shadow-sm transition-shadow hover:shadow-md">
                <CardHeader className="pb-6">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <ShoppingCart className="h-5 w-5 text-gray-500" />
                      <div>
                        <CardTitle className="text-xl">Order Items</CardTitle>
                        <CardDescription className="mt-1">Add, remove, or modify items in this order</CardDescription>
                      </div>
                    </div>
                    <div className="flex items-center gap-2">
                      {itemsToAdd.length > 0 && (
                        <Badge className="bg-green-100 text-green-700">
                          <Plus className="mr-1 h-3 w-3" />
                          {itemsToAdd.length} new
                        </Badge>
                      )}
                      {itemsToRemove.length > 0 && (
                        <Badge className="bg-red-100 text-red-700">
                          <Minus className="mr-1 h-3 w-3" />
                          {itemsToRemove.length} removed
                        </Badge>
                      )}
                      {itemsToModify.length > 0 && (
                        <Badge className="bg-blue-100 text-blue-700">
                          <Tag className="mr-1 h-3 w-3" />
                          {itemsToModify.length} modified
                        </Badge>
                      )}
                    </div>
                  </div>
                </CardHeader>

                <CardContent className="pt-6">
                  {/* Add Items Search */}
                  {permissions.canAddItems && (
                    <div className="border-b pb-6">
                      <div className="relative">
                        <Search className="absolute top-1/2 left-3 h-5 w-5 -translate-y-1/2 text-gray-400" />
                        <Input
                          type="text"
                          placeholder="Search products to add..."
                          value={searchQuery}
                          onChange={(e) => handleSearch(e.target.value)}
                          className="h-11 pl-10"
                        />
                        {isSearching && (
                          <div className="absolute top-1/2 right-3 -translate-y-1/2">
                            <Loader2 className="h-5 w-5 animate-spin text-gray-400" />
                          </div>
                        )}
                      </div>

                      {searchResults.length > 0 && (
                        <div className="mt-3 max-h-64 divide-y overflow-y-auto rounded-lg border shadow-md">
                          {searchResults.map((item) => (
                            <button
                              key={item.id}
                              className="flex w-full items-center justify-between p-3 transition-colors hover:bg-gray-50"
                              onClick={() => handleAddItem(item)}
                            >
                              <span className="font-medium text-gray-900">{item.name || item.itemName}</span>
                              <span className="text-sm font-semibold text-emerald-600">
                                +{formatCurrency((item.price || item.unitPrice || 0))}
                              </span>
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  )}

                  {/* Items List */}
                  <div className="mt-6 space-y-3">
                    {/* Existing Items */}
                    {order.items.map((item) => {
                      const isRemoved = itemsToRemove.includes(item.id);
                      const modifiedItem = itemsToModify.find((m) => m.id === item.id);
                      const currentItem = modifiedItem || item;

                      return (
                        <div
                          key={item.id}
                          className={cn(
                            'group relative rounded-lg border p-4 transition-all',
                            isRemoved && 'border-red-200 bg-red-50 opacity-60',
                            modifiedItem && !isRemoved && 'border-blue-200 bg-blue-50',
                            !isRemoved && !modifiedItem && 'bg-white hover:shadow-sm',
                          )}
                        >
                          <div className="flex items-center gap-4">
                            <div className="flex-1">
                              <p className={cn('font-medium text-gray-900', isRemoved && 'line-through')}>{item.itemName}</p>
                              {item.notes && <p className="mt-0.5 text-sm text-gray-500">{item.notes}</p>}
                              <p className="mt-1 text-sm text-gray-600">{formatCurrency((currentItem.unitPrice || 0))} each</p>
                            </div>

                            {!isRemoved && (
                              <div className="flex items-center gap-3">
                                <div className="flex items-center rounded-lg border bg-white">
                                  <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 rounded-r-none"
                                    onClick={() => handleQuantityChange(item.id, (currentItem.quantity || 1) - 1)}
                                    disabled={!permissions.canModify}
                                  >
                                    <Minus className="h-3 w-3" />
                                  </Button>
                                  <span className="min-w-[50px] px-4 text-center text-sm font-medium">{currentItem.quantity || 1}</span>
                                  <Button
                                    size="icon"
                                    variant="ghost"
                                    className="h-8 w-8 rounded-l-none"
                                    onClick={() => handleQuantityChange(item.id, (currentItem.quantity || 1) + 1)}
                                    disabled={!permissions.canModify}
                                  >
                                    <Plus className="h-3 w-3" />
                                  </Button>
                                </div>

                                <div className="min-w-[80px] text-right">
                                  <p className="font-semibold text-gray-900">
                                    {formatCurrency(((currentItem.quantity || 1) * (currentItem.unitPrice || 0)))}
                                  </p>
                                </div>
                              </div>
                            )}

                            {permissions.canRemoveItems && (
                              <Button
                                size="icon"
                                variant="ghost"
                                className={cn('h-8 w-8', isRemoved ? 'text-gray-600' : 'text-red-600 hover:bg-red-50')}
                                onClick={() => {
                                  if (isRemoved) {
                                    setItemsToRemove(itemsToRemove.filter((id) => id !== item.id));
                                  } else {
                                    handleRemoveItem(item.id);
                                  }
                                }}
                              >
                                {isRemoved ? <XCircle className="h-4 w-4" /> : <Trash2 className="h-4 w-4" />}
                              </Button>
                            )}
                          </div>
                        </div>
                      );
                    })}

                    {/* New Items */}
                    {itemsToAdd.map((item) => (
                      <div key={item.id} className="group relative rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm">
                        <div className="absolute -top-2 left-3">
                          <Badge className="bg-green-600 text-xs text-white">
                            <Sparkles className="mr-1 h-3 w-3" />
                            NEW
                          </Badge>
                        </div>

                        <div className="mt-2 flex items-center gap-4">
                          <div className="flex-1">
                            <p className="font-medium text-gray-900">{item.itemName}</p>
                            <p className="mt-1 text-sm text-gray-600">{formatCurrency((item.unitPrice || 0))} each</p>
                          </div>

                          <div className="flex items-center gap-3">
                            <div className="flex items-center rounded-lg border bg-white">
                              <Button
                                size="icon"
                                variant="ghost"
                                className="h-8 w-8 rounded-r-none"
                                onClick={() => handleQuantityChange(item.id, (item.quantity || 1) - 1)}
                              >
                                <Minus className="h-3 w-3" />
                              </Button>
                              <span className="min-w-[50px] px-4 text-center text-sm font-medium">{item.quantity || 1}</span>
                              <Button
                                size="icon"
                                variant="ghost"
                                className="h-8 w-8 rounded-l-none"
                                onClick={() => handleQuantityChange(item.id, (item.quantity || 1) + 1)}
                              >
                                <Plus className="h-3 w-3" />
                              </Button>
                            </div>

                            <div className="min-w-[80px] text-right">
                              <p className="font-semibold text-gray-900">{formatCurrency(((item.quantity || 1) * (item.unitPrice || 0)))}</p>
                            </div>

                            <Button
                              size="icon"
                              variant="ghost"
                              className="h-8 w-8 text-red-600 hover:bg-red-50"
                              onClick={() => handleRemoveItem(item.id)}
                            >
                              <Trash2 className="h-4 w-4" />
                            </Button>
                          </div>
                        </div>
                      </div>
                    ))}

                    {order.items.length === 0 && itemsToAdd.length === 0 && (
                      <div className="py-12 text-center">
                        <div className="mb-3 inline-block rounded-full bg-gray-100 p-3">
                          <ShoppingCart className="h-10 w-10 text-gray-400" />
                        </div>
                        <p className="font-medium text-gray-600">No items in this order</p>
                        <p className="mt-1 text-sm text-gray-500">Search and add items using the search bar above</p>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>

              {/* Modification Reason */}
              {hasChanges && (
                <Card className="border-amber-200 bg-amber-50/30">
                  <CardHeader>
                    <div className="flex items-center gap-2">
                      <AlertTriangle className="h-5 w-5 text-amber-600" />
                      <CardTitle className="text-amber-900">Modification Reason Required</CardTitle>
                    </div>
                    <CardDescription className="text-amber-700">Please provide a reason for these changes</CardDescription>
                  </CardHeader>
                  <CardContent>
                    <Textarea
                      placeholder="Enter reason for modification (e.g., customer request, item unavailable, etc.)"
                      value={modificationReason}
                      onChange={(e) => setModificationReason(e.target.value)}
                      className="min-h-[100px] bg-white"
                      required
                    />
                  </CardContent>
                </Card>
              )}
            </div>

            {/* Sidebar */}
            <div className="space-y-6">
              {/* Price Summary */}
              <Card className={cn('sticky top-6 shadow-sm', hasChanges && 'ring-2 ring-purple-400 ring-offset-2')}>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <DollarSign className="h-5 w-5 text-gray-500" />
                      <CardTitle className="text-lg">Price Summary</CardTitle>
                    </div>
                    {hasChanges && (
                      <Badge className="bg-purple-600 text-xs text-white">
                        <Calculator className="mr-1 h-3 w-3" />
                        Updated
                      </Badge>
                    )}
                  </div>
                </CardHeader>
                <CardContent className="space-y-4 pt-6">
                  <div className="space-y-4">
                    <div className="flex items-center justify-between border-b pb-4">
                      <span className="text-gray-600">Current Total</span>
                      <span className="text-xl font-bold text-gray-900">{formatCurrency(order.total)}</span>
                    </div>

                    {hasChanges && (
                      <>
                        <div className="flex items-center justify-between">
                          <span className="flex items-center gap-2 text-gray-600">
                            New Total
                            <ArrowRight className="h-4 w-4 text-gray-400" />
                          </span>
                          <span className="text-xl font-bold text-purple-600">{formatCurrency(calculateNewTotal())}</span>
                        </div>
                        <div
                          className={cn(
                            'flex items-center justify-between rounded-lg p-3',
                            priceDifference >= 0 ? 'border border-green-200 bg-green-50' : 'border border-red-200 bg-red-50',
                          )}
                        >
                          <div className="flex items-center gap-2">
                            {priceDifference >= 0 ? (
                              <TrendingUp className="h-4 w-4 text-green-600" />
                            ) : (
                              <TrendingDown className="h-4 w-4 text-red-600" />
                            )}
                            <span className={cn('font-medium', priceDifference >= 0 ? 'text-green-700' : 'text-red-700')}>Difference</span>
                          </div>
                          <span className={cn('font-bold', priceDifference >= 0 ? 'text-green-700' : 'text-red-700')}>
                            {priceDifference >= 0 ? '+' : '-'}
                            {formatCurrency(Math.abs(priceDifference))}
                          </span>
                        </div>
                      </>
                    )}
                  </div>

                  {permissions.canAdjustPrice && (
                    <>
                      <Separator />
                      <Button variant="outline" className="w-full" onClick={() => setShowPriceAdjustment(!showPriceAdjustment)}>
                        <DollarSign className="mr-2 h-4 w-4" />
                        Adjust Price
                      </Button>
                    </>
                  )}

                  {showPriceAdjustment && (
                    <div className="space-y-3 rounded-lg bg-gray-50 p-4">
                      <div>
                        <Label>Type</Label>
                        <select
                          className="mt-1 w-full rounded-lg border px-3 py-2"
                          value={priceAdjustment.type}
                          onChange={(e) =>
                            setPriceAdjustment({
                              ...priceAdjustment,
                              type: e.target.value,
                            })
                          }
                        >
                          <option value="discount">Discount</option>
                          <option value="surcharge">Surcharge</option>
                          <option value="correction">Correction</option>
                        </select>
                      </div>

                      <div>
                        <Label>Amount</Label>
                        <CurrencyInput
                          value={priceAdjustment.amount}
                          onChange={(value) =>
                            setPriceAdjustment({
                              ...priceAdjustment,
                              amount: value || 0,
                            })
                          }
                          showSymbol={true}
                          className="mt-1"
                        />
                      </div>

                      <div>
                        <Label>Reason</Label>
                        <Input
                          value={priceAdjustment.reason}
                          onChange={(e) =>
                            setPriceAdjustment({
                              ...priceAdjustment,
                              reason: e.target.value,
                            })
                          }
                          placeholder="Enter reason..."
                          className="mt-1"
                        />
                      </div>
                    </div>
                  )}
                </CardContent>
              </Card>

              {/* Warnings */}
              {permissions.requiresAuthorization && (
                <Alert className="border-amber-200 bg-amber-50">
                  <AlertCircle className="h-4 w-4 text-amber-600" />
                  <AlertDescription className="text-amber-800">Some modifications may require manager authorization</AlertDescription>
                </Alert>
              )}

              {order.paymentStatus !== 'pending' && (
                <Alert className="border-blue-200 bg-blue-50">
                  <Info className="h-4 w-4 text-blue-600" />
                  <AlertDescription className="text-blue-800">
                    This order has payments. Modifications will require payment reconciliation.
                  </AlertDescription>
                </Alert>
              )}

              {/* Modification History */}
              {order.metadata?.modifications && order.metadata.modifications.length > 0 && (
                <Card>
                  <CardHeader
                    className="cursor-pointer transition-colors hover:bg-gray-50"
                    onClick={() => setShowModificationHistory(!showModificationHistory)}
                  >
                    <div className="flex items-center justify-between">
                      <div className="flex items-center gap-2">
                        <History className="h-5 w-5 text-gray-600" />
                        <CardTitle className="text-base">Modification History</CardTitle>
                        <Badge variant="outline" className="text-xs">
                          {order.metadata.modifications.length}
                        </Badge>
                      </div>
                      <ChevronDown className={cn('h-4 w-4 text-gray-400 transition-transform', showModificationHistory && 'rotate-180')} />
                    </div>
                  </CardHeader>
                  {showModificationHistory && (
                    <CardContent>
                      <div className="space-y-3">
                        {order.metadata.modifications.map((mod, index) => (
                          <div key={index} className="space-y-2 rounded-lg bg-gray-50 p-3">
                            <div className="flex items-center justify-between text-xs">
                              <span className="text-gray-500">{new Date(mod.timestamp).toLocaleString()}</span>
                              <span className="font-medium text-gray-700">{mod.modified_by}</span>
                            </div>
                            <p className="text-sm text-gray-600">{mod.reason}</p>
                            <div className="flex gap-2">
                              {mod.added_count > 0 && (
                                <Badge variant="outline" className="text-xs">
                                  +{mod.added_count} added
                                </Badge>
                              )}
                              {mod.removed_count > 0 && (
                                <Badge variant="outline" className="text-xs">
                                  -{mod.removed_count} removed
                                </Badge>
                              )}
                              {mod.modified_count > 0 && (
                                <Badge variant="outline" className="text-xs">
                                  {mod.modified_count} modified
                                </Badge>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  )}
                </Card>
              )}
            </div>
          </div>
        </div>
      </PageLayout.Content>
    </AppLayout>
  );
}
