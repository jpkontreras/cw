import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { 
  X,
  ShoppingCart, 
  Package,
  UserCheck,
  CreditCard,
  Percent,
  DollarSign,
  FileText,
  MessageSquare,
  ArrowRight,
  ChevronRight,
  Plus,
  Trash2,
  Check,
  AlertCircle,
  Clock,
  Info
} from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

interface OrderActionRecorderProps {
  isOpen: boolean;
  onClose: () => void;
  orderUuid: string;
  orderId?: number;
  orderStatus: string;
  orderTotal?: number;
  onActionRecorded?: () => void;
}

interface ItemFormData {
  itemId: number;
  quantity: number;
  modifiers: Array<{ name: string; price: number }>;
  notes: string;
}

type ActionFormData = {
  eventType: string;
  items: ItemFormData[];
  promotionId: number | null;
  tipAmount: number;
  tipPercentage: number | null;
  customerName: string;
  customerPhone: string;
  customerEmail: string;
  note: string;
  specialInstructions: string;
  paymentMethod: string;
  newStatus: string;
  reason: string;
  [key: string]: string | number | boolean | null | ItemFormData[] | undefined; // Allow index signature for Inertia form compatibility
};

const actionCategories = [
  {
    id: 'items',
    title: 'Order Items',
    description: 'Manage items in the order',
    icon: ShoppingCart,
    color: 'blue',
    actions: [
      {
        value: 'add_items',
        label: 'Add Items',
        description: 'Add new items to the order',
        icon: Plus,
        allowedStatuses: ['draft', 'pending', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
      {
        value: 'modify_items',
        label: 'Modify Items',
        description: 'Change quantities or remove items',
        icon: Package,
        allowedStatuses: ['draft', 'pending', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
    ]
  },
  {
    id: 'customer',
    title: 'Customer',
    description: 'Customer information and preferences',
    icon: UserCheck,
    color: 'orange',
    actions: [
      {
        value: 'update_customer',
        label: 'Update Info',
        description: 'Name, phone, email',
        icon: UserCheck,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
      {
        value: 'special_instructions',
        label: 'Special Instructions',
        description: 'Cooking or delivery notes',
        icon: FileText,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
    ]
  },
  {
    id: 'payment',
    title: 'Payment & Pricing',
    description: 'Payment methods and adjustments',
    icon: DollarSign,
    color: 'green',
    actions: [
      {
        value: 'set_payment_method',
        label: 'Payment Method',
        description: 'Cash, card, transfer',
        icon: CreditCard,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
      {
        value: 'apply_promotion',
        label: 'Apply Discount',
        description: 'Promotions and offers',
        icon: Percent,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
      {
        value: 'add_tip',
        label: 'Add Tip',
        description: 'Gratuity amount',
        icon: DollarSign,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
    ]
  },
  {
    id: 'status',
    title: 'Order Status',
    description: 'Update order progress',
    icon: Clock,
    color: 'indigo',
    actions: [
      {
        value: 'change_status',
        label: 'Update Status',
        description: 'Move to next stage',
        icon: ArrowRight,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'preparing', 'ready', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
      {
        value: 'add_note',
        label: 'Internal Note',
        description: 'Staff communication',
        icon: MessageSquare,
        allowedStatuses: ['draft', 'pending', 'confirmed', 'preparing', 'ready', 'started', 'items_added', 'items_validated', 'price_calculated', 'promotions_calculated'],
      },
    ]
  },
];

export function OrderActionRecorder({ 
  isOpen, 
  onClose, 
  orderUuid, 
  orderId,
  orderStatus, 
  orderTotal = 0,
  onActionRecorded 
}: OrderActionRecorderProps) {
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [selectedAction, setSelectedAction] = useState<string | null>(null);
  
  // Helper function to get next available statuses
  const getNextStatuses = (currentStatus: string) => {
    const statusFlow: Record<string, Array<{value: string, label: string, color: string}>> = {
      'draft': [
        { value: 'pending', label: 'Pending', color: 'bg-yellow-500' },
        { value: 'started', label: 'Started', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'started': [
        { value: 'items_added', label: 'Items Added', color: 'bg-blue-500' },
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'items_added': [
        { value: 'items_validated', label: 'Items Validated', color: 'bg-blue-500' },
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'items_validated': [
        { value: 'price_calculated', label: 'Price Calculated', color: 'bg-blue-500' },
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'price_calculated': [
        { value: 'promotions_calculated', label: 'Promotions Applied', color: 'bg-blue-500' },
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'promotions_calculated': [
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'pending': [
        { value: 'confirmed', label: 'Confirmed', color: 'bg-blue-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'confirmed': [
        { value: 'preparing', label: 'Preparing', color: 'bg-orange-500' },
        { value: 'cancelled', label: 'Cancelled', color: 'bg-red-500' },
      ],
      'preparing': [
        { value: 'ready', label: 'Ready', color: 'bg-green-500' },
      ],
      'ready': [
        { value: 'completed', label: 'Completed', color: 'bg-gray-500' },
        { value: 'delivered', label: 'Delivered', color: 'bg-green-500' },
      ],
      'delivering': [
        { value: 'delivered', label: 'Delivered', color: 'bg-green-500' },
      ],
      'delivered': [
        { value: 'completed', label: 'Completed', color: 'bg-gray-500' },
      ],
    };
    
    return statusFlow[currentStatus] || [];
  };
  
  const form = useForm<ActionFormData>({
    eventType: '',
    items: [],
    promotionId: null,
    tipAmount: 0,
    tipPercentage: null,
    customerName: '',
    customerPhone: '',
    customerEmail: '',
    note: '',
    specialInstructions: '',
    paymentMethod: '',
    newStatus: '',
    reason: '',
  });

  // Reset when closing
  useEffect(() => {
    if (!isOpen) {
      setSelectedCategory(null);
      setSelectedAction(null);
      form.reset();
    }
  }, [isOpen, form]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedAction) {
      toast.error('Please select an action');
      return;
    }
    
    let endpoint = '';
    
    if (orderId) {
      endpoint = `/orders/${orderId}/events/add`;
    } else {
      // API endpoints for mobile/external clients
      switch (selectedAction) {
        case 'add_items':
          endpoint = `/api/orders/flow/${orderUuid}/items`;
          break;
        // ... other cases
        default:
          toast.error('Action not implemented');
          return;
      }
    }
    
    // Update eventType before submission
    form.setData('eventType', selectedAction);
    
    form.post(endpoint, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Action recorded successfully');
        onClose();
        form.reset();
        setSelectedCategory(null);
        setSelectedAction(null);
        onActionRecorded?.();
      },
      onError: (errors: Record<string, string>) => {
        toast.error('Failed to record action');
        console.error(errors);
      },
    });
  };

  const renderActionForm = () => {
    if (!selectedAction) return null;

    switch (selectedAction) {
      case 'add_items':
        return (
          <div className="space-y-4">
            <div className="flex items-center justify-between mb-4">
              <Label className="text-base font-medium">Items to Add</Label>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => {
                  form.setData('items', [
                    ...form.data.items,
                    { itemId: 0, quantity: 1, modifiers: [], notes: '' }
                  ]);
                }}
              >
                <Plus className="h-4 w-4 mr-1" />
                Add Item
              </Button>
            </div>
            
            <div className="space-y-3">
              {form.data.items.map((item, index) => (
                <div key={index} className="bg-gray-50 p-4 rounded-lg space-y-3">
                  <div className="flex justify-between items-start">
                    <div className="flex-1 grid grid-cols-2 gap-3">
                      <div>
                        <Label className="text-sm">Item ID</Label>
                        <Input
                          type="number"
                          value={item.itemId}
                          onChange={(e) => {
                            const newItems = [...form.data.items];
                            newItems[index].itemId = parseInt(e.target.value);
                            form.setData('items', newItems);
                          }}
                          className="mt-1"
                        />
                      </div>
                      <div>
                        <Label className="text-sm">Quantity</Label>
                        <Input
                          type="number"
                          min="1"
                          value={item.quantity}
                          onChange={(e) => {
                            const newItems = [...form.data.items];
                            newItems[index].quantity = parseInt(e.target.value);
                            form.setData('items', newItems);
                          }}
                          className="mt-1"
                        />
                      </div>
                    </div>
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      onClick={() => {
                        form.setData('items', form.data.items.filter((_, i) => i !== index));
                      }}
                      className="ml-2 hover:bg-red-50 hover:text-red-600 transition-colors"
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                  <div>
                    <Label className="text-sm">Notes</Label>
                    <Input
                      placeholder="Special instructions..."
                      value={item.notes}
                      onChange={(e) => {
                        const newItems = [...form.data.items];
                        newItems[index].notes = e.target.value;
                        form.setData('items', newItems);
                      }}
                      className="mt-1"
                    />
                  </div>
                </div>
              ))}
              
              {form.data.items.length === 0 && (
                <div className="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                  <Package className="h-12 w-12 mx-auto mb-3 opacity-50" />
                  <p>No items added yet</p>
                  <p className="text-sm mt-1">Click "Add Item" to get started</p>
                </div>
              )}
            </div>
          </div>
        );

      case 'set_payment_method':
        return (
          <div className="space-y-4">
            <div>
              <Label className="text-base font-medium mb-3 block">Select Payment Method</Label>
              <div className="grid grid-cols-2 gap-3">
                {['cash', 'credit_card', 'debit_card', 'bank_transfer', 'mobile_payment'].map((method) => (
                  <button
                    key={method}
                    type="button"
                    onClick={() => form.setData('paymentMethod', method)}
                    className={cn(
                      "p-4 rounded-lg border-2 text-left transition-all duration-200",
                      "hover:border-gray-300 hover:shadow-md hover:scale-[1.02]",
                      form.data.paymentMethod === method 
                        ? "border-blue-500 bg-blue-50 shadow-md scale-[1.02]" 
                        : "border-gray-200"
                    )}
                  >
                    <CreditCard className={cn(
                      "h-5 w-5 mb-2",
                      form.data.paymentMethod === method ? "text-blue-600" : "text-gray-600"
                    )} />
                    <p className="font-medium text-sm">
                      {method.split('_').map(word => 
                        word.charAt(0).toUpperCase() + word.slice(1)
                      ).join(' ')}
                    </p>
                  </button>
                ))}
              </div>
            </div>
          </div>
        );

      case 'update_customer':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="customerName">Customer Name</Label>
              <Input
                id="customerName"
                placeholder="Enter customer name"
                value={form.data.customerName}
                onChange={(e) => form.setData('customerName', e.target.value)}
                className="mt-1"
              />
            </div>
            <div>
              <Label htmlFor="customerPhone">Phone Number</Label>
              <Input
                id="customerPhone"
                type="tel"
                placeholder="+56 9 1234 5678"
                value={form.data.customerPhone}
                onChange={(e) => form.setData('customerPhone', e.target.value)}
                className="mt-1"
              />
            </div>
            <div>
              <Label htmlFor="customerEmail">Email (Optional)</Label>
              <Input
                id="customerEmail"
                type="email"
                placeholder="customer@example.com"
                value={form.data.customerEmail}
                onChange={(e) => form.setData('customerEmail', e.target.value)}
                className="mt-1"
              />
            </div>
          </div>
        );

      case 'add_tip':
        return (
          <div className="space-y-4">
            <div>
              <Label className="text-base font-medium mb-3 block">Tip Amount</Label>
              <div className="space-y-3">
                <div className="flex gap-2">
                  <div className="flex-1">
                    <Input
                      type="number"
                      step="0.01"
                      min="0"
                      placeholder="0.00"
                      value={form.data.tipAmount}
                      onChange={(e) => form.setData('tipAmount', parseFloat(e.target.value))}
                      className="text-lg"
                    />
                  </div>
                  <Select
                    value={form.data.tipPercentage?.toString() || ''}
                    onValueChange={(value) => {
                      const percentage = parseInt(value);
                      form.setData('tipPercentage', percentage);
                      const tipAmount = (orderTotal * percentage) / 100;
                      form.setData('tipAmount', Math.round(tipAmount * 100) / 100);
                    }}
                  >
                    <SelectTrigger className="w-32">
                      <SelectValue placeholder="Quick %" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="10">10%</SelectItem>
                      <SelectItem value="15">15%</SelectItem>
                      <SelectItem value="18">18%</SelectItem>
                      <SelectItem value="20">20%</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                {form.data.tipAmount > 0 && (
                  <div className="bg-green-50 p-3 rounded-lg">
                    <p className="text-sm text-green-800">
                      Tip: ${form.data.tipAmount.toFixed(2)}
                      {form.data.tipPercentage && ` (${form.data.tipPercentage}%)`}
                    </p>
                    <p className="text-xs text-green-600 mt-1">
                      Total with tip: ${(orderTotal + form.data.tipAmount).toFixed(2)}
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>
        );

      case 'special_instructions':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="specialInstructions" className="text-base font-medium">
                Special Instructions
              </Label>
              <Textarea
                id="specialInstructions"
                rows={6}
                placeholder="E.g., No onions, extra spicy, deliver to back door, call when arriving..."
                value={form.data.specialInstructions}
                onChange={(e) => form.setData('specialInstructions', e.target.value)}
                className="mt-2"
              />
              <p className="text-sm text-gray-500 mt-2">
                These instructions will be visible to kitchen and delivery staff
              </p>
            </div>
          </div>
        );

      case 'add_note':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="note" className="text-base font-medium">Internal Note</Label>
              <Textarea
                id="note"
                rows={6}
                placeholder="Add internal note for staff (not visible to customer)..."
                value={form.data.note}
                onChange={(e) => form.setData('note', e.target.value)}
                className="mt-2"
              />
              <p className="text-sm text-gray-500 mt-2">
                This note is only visible to staff members
              </p>
            </div>
          </div>
        );
        
      case 'change_status':
        return (
          <div className="space-y-4">
            <div>
              <Label className="text-base font-medium mb-3 block">Change Order Status</Label>
              <div className="space-y-3">
                <div className="bg-blue-50 p-3 rounded-lg">
                  <p className="text-sm font-medium text-blue-900">Current Status</p>
                  <p className="text-lg text-blue-700 mt-1 capitalize">{orderStatus}</p>
                </div>
                
                <div>
                  <Label htmlFor="newStatus" className="text-sm">New Status</Label>
                  <Select
                    value={form.data.newStatus}
                    onValueChange={(value) => form.setData('newStatus', value)}
                  >
                    <SelectTrigger className="mt-1">
                      <SelectValue placeholder="Select new status" />
                    </SelectTrigger>
                    <SelectContent>
                      {getNextStatuses(orderStatus).map((status) => (
                        <SelectItem key={status.value} value={status.value}>
                          <div className="flex items-center gap-2">
                            <span className={cn(
                              "w-2 h-2 rounded-full",
                              status.color
                            )} />
                            <span>{status.label}</span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
                
                <div>
                  <Label htmlFor="reason" className="text-sm">Reason (Optional)</Label>
                  <Textarea
                    id="reason"
                    rows={3}
                    placeholder="Explain why the status is being changed..."
                    value={form.data.reason}
                    onChange={(e) => form.setData('reason', e.target.value)}
                    className="mt-1"
                  />
                </div>
              </div>
            </div>
          </div>
        );
        
      case 'apply_promotion':
        return (
          <div className="space-y-4">
            <div>
              <Label className="text-base font-medium mb-3 block">Apply Promotion</Label>
              <div className="space-y-3">
                <div>
                  <Label htmlFor="promotionId" className="text-sm">Promotion Code</Label>
                  <Input
                    id="promotionId"
                    placeholder="Enter promotion code"
                    className="mt-1"
                  />
                </div>
                
                <div className="bg-amber-50 border border-amber-200 p-4 rounded-lg">
                  <div className="flex items-start gap-3">
                    <Percent className="h-5 w-5 text-amber-600 mt-0.5" />
                    <div className="flex-1">
                      <p className="font-medium text-amber-900">Available Promotions</p>
                      <ul className="mt-2 space-y-1 text-sm text-amber-700">
                        <li>• SUMMER20 - 20% off entire order</li>
                        <li>• FREEDELIVERY - Free delivery on orders over $30</li>
                        <li>• COMBO10 - $10 off combo meals</li>
                      </ul>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );
        
      case 'modify_items':
        return (
          <div className="space-y-4">
            <div>
              <Label className="text-base font-medium mb-3 block">Modify Order Items</Label>
              <div className="bg-gray-50 p-4 rounded-lg">
                <p className="text-sm text-gray-600 mb-3">Current items in order:</p>
                <div className="space-y-2">
                  {/* Placeholder for current items - would be loaded from order data */}
                  <div className="flex items-center justify-between p-3 bg-white rounded border">
                    <div>
                      <p className="font-medium">Empanada de Pino</p>
                      <p className="text-sm text-gray-500">Quantity: 2</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                      <Input
                        type="number"
                        min="0"
                        defaultValue="2"
                        className="w-20"
                      />
                    </div>
                  </div>
                  <div className="flex items-center justify-between p-3 bg-white rounded border">
                    <div>
                      <p className="font-medium">Bebida Coca-Cola 500ml</p>
                      <p className="text-sm text-gray-500">Quantity: 1</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                      <Input
                        type="number"
                        min="0"
                        defaultValue="1"
                        className="w-20"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        );

      default:
        return null;
    }
  };

  // Get available categories and actions based on order status
  const availableCategories = actionCategories.map(category => ({
    ...category,
    actions: category.actions.filter(action => 
      action.allowedStatuses.includes(orderStatus)
    )
  })).filter(category => category.actions.length > 0);

  const selectedCategoryData = availableCategories.find(c => c.id === selectedCategory);
  const selectedActionData = selectedCategoryData?.actions.find(a => a.value === selectedAction);

  return (
    <>
      {/* Backdrop */}
      <div
        className={cn(
          "fixed inset-0 bg-black/50 z-40 transition-opacity duration-200",
          isOpen ? "opacity-100 pointer-events-auto" : "opacity-0 pointer-events-none"
        )}
        onClick={onClose}
      />
      
      {/* Panel */}
      <div
        className={cn(
          "fixed right-0 top-0 h-full w-full max-w-5xl bg-white shadow-2xl z-50 flex flex-col",
          "transition-all duration-300 ease-out",
          isOpen ? "translate-x-0 opacity-100" : "translate-x-full opacity-0"
        )}
      >
            {/* Header */}
            <div className="flex items-center justify-between px-6 py-4 border-b">
              <div>
                <h2 className="text-xl font-semibold">Record Order Action</h2>
                <p className="text-sm text-gray-500 mt-1">
                  Select an action to update order {orderUuid.slice(0, 8)}...
                </p>
              </div>
              <Button
                variant="ghost"
                size="icon"
                onClick={onClose}
              >
                <X className="h-5 w-5" />
              </Button>
            </div>
            
            {/* Content */}
            <div className="flex-1 overflow-hidden flex">
              {/* Categories Sidebar */}
              <div className="w-80 border-r bg-gradient-to-b from-gray-50 to-white overflow-y-auto">
                <div className="p-4">
                  <h3 className="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">
                    Action Categories
                  </h3>
                  <div className="space-y-2">
                    {availableCategories.map((category) => {
                      const Icon = category.icon;
                      const isSelected = selectedCategory === category.id;
                      
                      return (
                        <button
                          key={category.id}
                          onClick={() => {
                            setSelectedCategory(category.id);
                            setSelectedAction(null);
                          }}
                          className={cn(
                            "w-full text-left p-3 rounded-lg transition-all duration-200",
                            "hover:bg-white hover:shadow-sm hover:scale-[1.02]",
                            isSelected && "bg-white shadow-sm ring-1 ring-blue-200 scale-[1.02]"
                          )}
                        >
                          <div className="flex items-start gap-3">
                            <div className={cn(
                              "p-2 rounded-lg",
                              isSelected ? "bg-blue-100" : "bg-gray-100"
                            )}>
                              <Icon className={cn(
                                "h-5 w-5",
                                isSelected ? "text-blue-600" : "text-gray-600"
                              )} />
                            </div>
                            <div className="flex-1">
                              <p className="font-medium">{category.title}</p>
                              <p className="text-sm text-gray-500 mt-0.5">
                                {category.description}
                              </p>
                              <div className="flex items-center gap-2 mt-2">
                                <span className="text-xs text-gray-400">
                                  {category.actions.length} actions available
                                </span>
                                <ChevronRight className="h-3 w-3 text-gray-400" />
                              </div>
                            </div>
                          </div>
                        </button>
                      );
                    })}
                  </div>
                </div>
              </div>
              
              {/* Main Content */}
              <div className="flex-1 overflow-y-auto">
                <form onSubmit={handleSubmit} className="h-full flex flex-col">
                  {!selectedCategory ? (
                    <div className="flex-1 flex items-center justify-center p-8">
                      <div className="text-center max-w-sm">
                        <AlertCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                          Select a Category
                        </h3>
                        <p className="text-gray-500">
                          Choose a category from the left to see available actions for this order
                        </p>
                      </div>
                    </div>
                  ) : !selectedAction ? (
                    <div className="flex-1 p-6">
                      <h3 className="text-lg font-medium mb-4">
                        {selectedCategoryData?.title} Actions
                      </h3>
                      <div className="grid gap-3">
                        {selectedCategoryData?.actions.map((action) => {
                          const Icon = action.icon;
                          
                          return (
                            <button
                              key={action.value}
                              type="button"
                              onClick={() => setSelectedAction(action.value)}
                              className={cn(
                                "p-4 rounded-lg border-2 text-left transition-all duration-200",
                                "hover:border-blue-300 hover:bg-blue-50 hover:shadow-md hover:scale-[1.02]",
                                "border-gray-200 bg-white"
                              )}
                            >
                              <div className="flex items-start gap-3">
                                <div className="p-2 bg-gray-100 rounded-lg">
                                  <Icon className="h-5 w-5 text-gray-600" />
                                </div>
                                <div className="flex-1">
                                  <p className="font-medium">{action.label}</p>
                                  <p className="text-sm text-gray-500 mt-0.5">
                                    {action.description}
                                  </p>
                                </div>
                                <ChevronRight className="h-5 w-5 text-gray-400 mt-3" />
                              </div>
                            </button>
                          );
                        })}
                      </div>
                    </div>
                  ) : (
                    <div className="flex-1 p-6">
                      {/* Action Header */}
                      <div className="flex items-center gap-3 mb-6">
                        <button
                          type="button"
                          onClick={() => setSelectedAction(null)}
                          className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                          <ChevronRight className="h-5 w-5 rotate-180" />
                        </button>
                        <div className="flex-1">
                          <h3 className="text-lg font-medium">
                            {selectedActionData?.label}
                          </h3>
                          <p className="text-sm text-gray-500">
                            {selectedActionData?.description}
                          </p>
                        </div>
                      </div>
                      
                      {/* Action Form */}
                      <div className="max-w-2xl">
                        {renderActionForm()}
                      </div>
                    </div>
                  )}
                  
                  {/* Footer */}
                  {selectedAction && (
                    <div className="border-t px-6 py-4 bg-gray-50">
                      <div className="flex justify-between items-center">
                        <div className="flex items-center gap-2 text-sm text-gray-500">
                          <Info className="h-4 w-4" />
                          <span>This action will be recorded in the order's event log</span>
                        </div>
                        <div className="flex gap-3">
                          <Button
                            type="button"
                            variant="outline"
                            onClick={onClose}
                          >
                            Cancel
                          </Button>
                          <Button
                            type="submit"
                            disabled={form.processing}
                          >
                            {form.processing ? (
                              <>
                                <svg className="h-4 w-4 mr-2 animate-spin" viewBox="0 0 24 24">
                                  <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" />
                                  <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                Recording...
                              </>
                            ) : (
                              <>
                                <Check className="h-4 w-4 mr-2" />
                                Record Action
                              </>
                            )}
                          </Button>
                        </div>
                      </div>
                    </div>
                  )}
                </form>
              </div>
            </div>
      </div>
    </>
  );
}