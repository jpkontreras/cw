import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
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
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/lib/utils';
import { 
  Plus, 
  ShoppingCart, 
  Percent, 
  DollarSign, 
  MessageSquare,
  AlertCircle,
  Package,
  ArrowRightCircle,
  X,
  CreditCard,
  FileText,
  UserCheck
} from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

interface AddEventDialogProps {
  orderUuid: string;
  orderId?: number;
  orderStatus: string;
  onEventAdded?: () => void;
  children?: React.ReactNode;
}

interface ItemFormData {
  itemId: number;
  quantity: number;
  modifiers: Array<{ name: string; price: number }>;
  notes: string;
}

const eventTypes = [
  {
    value: 'add_items',
    label: 'Add Items',
    icon: ShoppingCart,
    description: 'Add items to the order',
    color: 'blue',
    allowedStatuses: ['draft', 'pending'],
  },
  {
    value: 'modify_items',
    label: 'Modify Items',
    icon: Package,
    description: 'Change item quantities or modifiers',
    color: 'yellow',
    allowedStatuses: ['draft', 'pending'],
  },
  {
    value: 'update_customer',
    label: 'Customer Info',
    icon: UserCheck,
    description: 'Update customer details',
    color: 'orange',
    allowedStatuses: ['draft', 'pending', 'confirmed'],
  },
  {
    value: 'set_payment_method',
    label: 'Payment Method',
    icon: CreditCard,
    description: 'Set payment type (cash, card, etc)',
    color: 'blue',
    allowedStatuses: ['draft', 'pending', 'confirmed'],
  },
  {
    value: 'apply_promotion',
    label: 'Apply Discount',
    icon: Percent,
    description: 'Apply a discount or promotion',
    color: 'purple',
    allowedStatuses: ['draft', 'pending', 'confirmed'],
  },
  {
    value: 'add_tip',
    label: 'Add Tip',
    icon: DollarSign,
    description: 'Add gratuity amount',
    color: 'green',
    allowedStatuses: ['draft', 'pending', 'confirmed'],
  },
  {
    value: 'special_instructions',
    label: 'Special Instructions',
    icon: FileText,
    description: 'Add cooking or delivery instructions',
    color: 'teal',
    allowedStatuses: ['draft', 'pending', 'confirmed'],
  },
  {
    value: 'add_note',
    label: 'Internal Note',
    icon: MessageSquare,
    description: 'Add internal staff note',
    color: 'gray',
    allowedStatuses: ['draft', 'pending', 'confirmed', 'preparing', 'ready'],
  },
  {
    value: 'change_status',
    label: 'Update Status',
    icon: ArrowRightCircle,
    description: 'Move order to next stage',
    color: 'indigo',
    allowedStatuses: ['draft', 'pending', 'confirmed', 'preparing', 'ready'],
  },
];

export function AddEventDialog({ 
  orderUuid, 
  orderId,
  orderStatus, 
  onEventAdded,
  children 
}: AddEventDialogProps) {
  const [open, setOpen] = useState(false);
  const [selectedEventType, setSelectedEventType] = useState<string | null>(null);
  
  const form = useForm({
    eventType: '',
    items: [] as ItemFormData[],
    promotionId: null as number | null,
    tipAmount: 0,
    tipPercentage: null as number | null,
    customerName: '',
    customerPhone: '',
    customerEmail: '',
    note: '',
    specialInstructions: '',
    paymentMethod: '',
    newStatus: '',
    reason: '',
  });
  
  // Filter available event types based on order status
  const availableEventTypes = eventTypes.filter(type => 
    type.allowedStatuses.includes(orderStatus)
  );
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!selectedEventType) {
      toast.error('Please select an event type');
      return;
    }
    
    // Use web route if orderId is provided, otherwise use API
    let endpoint = '';
    const requestData = {
      eventType: selectedEventType,
      ...form.data,
    };
    
    if (orderId) {
      // Use web route for event sourcing
      endpoint = `/orders/${orderId}/events/add`;
    } else {
      // Use API routes (for external/mobile clients)
      switch (selectedEventType) {
        case 'add_items':
          endpoint = `/api/orders/flow/${orderUuid}/items`;
          break;
        case 'apply_promotion':
          endpoint = `/api/orders/flow/${orderUuid}/promotion`;
          break;
        case 'add_tip':
          endpoint = `/api/orders/flow/${orderUuid}/tip`;
          break;
        case 'set_payment_method':
          endpoint = `/api/orders/flow/${orderUuid}/payment`;
          break;
        case 'update_customer':
          endpoint = `/api/orders/flow/${orderUuid}/customer`;
          break;
        case 'special_instructions':
          endpoint = `/api/orders/flow/${orderUuid}/instructions`;
          break;
        case 'add_note':
          endpoint = `/api/orders/flow/${orderUuid}/note`;
          break;
        case 'change_status':
          if (form.data.newStatus === 'confirmed') {
            endpoint = `/api/orders/flow/${orderUuid}/confirm`;
          } else if (form.data.newStatus === 'cancelled') {
            endpoint = `/api/orders/flow/${orderUuid}/cancel`;
          }
          break;
        default:
          toast.error('Event type not implemented');
          return;
      }
    }
    
    // Submit the form
    form.transform(() => requestData).post(endpoint, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Action recorded successfully');
        setOpen(false);
        form.reset();
        setSelectedEventType(null);
        onEventAdded?.();
      },
      onError: (errors) => {
        toast.error('Failed to record action');
        console.error(errors);
      },
    });
  };
  
  const renderEventForm = () => {
    if (!selectedEventType) {
      return (
        <div className="text-center py-8 text-gray-500">
          <AlertCircle className="h-12 w-12 mx-auto mb-3 opacity-50" />
          <p>Select an event type to continue</p>
        </div>
      );
    }
    
    switch (selectedEventType) {
      case 'add_items':
        return (
          <div className="space-y-4">
            <div className="space-y-2">
              <Label>Items to Add</Label>
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
            
            {form.data.items.map((item, index) => (
              <div key={index} className="p-3 border rounded-lg space-y-3">
                <div className="flex justify-between items-start">
                  <div className="flex-1 grid grid-cols-2 gap-3">
                    <div>
                      <Label>Item ID</Label>
                      <Input
                        type="number"
                        value={item.itemId}
                        onChange={(e) => {
                          const newItems = [...form.data.items];
                          newItems[index].itemId = parseInt(e.target.value);
                          form.setData('items', newItems);
                        }}
                      />
                    </div>
                    <div>
                      <Label>Quantity</Label>
                      <Input
                        type="number"
                        min="1"
                        value={item.quantity}
                        onChange={(e) => {
                          const newItems = [...form.data.items];
                          newItems[index].quantity = parseInt(e.target.value);
                          form.setData('items', newItems);
                        }}
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
                  >
                    <X className="h-4 w-4" />
                  </Button>
                </div>
                <div>
                  <Label>Notes</Label>
                  <Input
                    placeholder="Special instructions..."
                    value={item.notes}
                    onChange={(e) => {
                      const newItems = [...form.data.items];
                      newItems[index].notes = e.target.value;
                      form.setData('items', newItems);
                    }}
                  />
                </div>
              </div>
            ))}
          </div>
        );
        
      case 'apply_promotion':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="promotion">Promotion</Label>
              <Select
                value={form.data.promotionId?.toString() || ''}
                onValueChange={(value) => form.setData('promotionId', parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select a promotion" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">10% Off</SelectItem>
                  <SelectItem value="2">15% Off</SelectItem>
                  <SelectItem value="3">20% Off</SelectItem>
                  <SelectItem value="4">Free Delivery</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        );
        
      case 'add_tip':
        return (
          <div className="space-y-4">
            <div>
              <Label>Tip Amount</Label>
              <div className="flex gap-2">
                <Input
                  type="number"
                  step="0.01"
                  min="0"
                  placeholder="0.00"
                  value={form.data.tipAmount}
                  onChange={(e) => form.setData('tipAmount', parseFloat(e.target.value))}
                />
                <Select
                  value={form.data.tipPercentage?.toString() || ''}
                  onValueChange={(value) => {
                    const percentage = parseInt(value);
                    form.setData('tipPercentage', percentage);
                    // Calculate amount based on percentage if needed
                  }}
                >
                  <SelectTrigger className="w-[100px]">
                    <SelectValue placeholder="%" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="10">10%</SelectItem>
                    <SelectItem value="15">15%</SelectItem>
                    <SelectItem value="18">18%</SelectItem>
                    <SelectItem value="20">20%</SelectItem>
                  </SelectContent>
                </Select>
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
              />
            </div>
          </div>
        );
        
      case 'set_payment_method':
        return (
          <div className="space-y-4">
            <div>
              <Label>Payment Method</Label>
              <Select
                value={form.data.paymentMethod}
                onValueChange={(value) => form.setData('paymentMethod', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select payment method" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="cash">Cash</SelectItem>
                  <SelectItem value="credit_card">Credit Card</SelectItem>
                  <SelectItem value="debit_card">Debit Card</SelectItem>
                  <SelectItem value="bank_transfer">Bank Transfer</SelectItem>
                  <SelectItem value="mobile_payment">Mobile Payment</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        );
        
      case 'special_instructions':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="specialInstructions">Special Instructions</Label>
              <Textarea
                id="specialInstructions"
                rows={4}
                placeholder="E.g., No onions, extra spicy, deliver to back door..."
                value={form.data.specialInstructions}
                onChange={(e) => form.setData('specialInstructions', e.target.value)}
              />
            </div>
          </div>
        );
        
      case 'add_note':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="note">Internal Note</Label>
              <Textarea
                id="note"
                rows={4}
                placeholder="Add internal note for staff (not visible to customer)..."
                value={form.data.note}
                onChange={(e) => form.setData('note', e.target.value)}
              />
            </div>
          </div>
        );
        
      case 'change_status':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="newStatus">New Status</Label>
              <Select
                value={form.data.newStatus}
                onValueChange={(value) => form.setData('newStatus', value)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select new status" />
                </SelectTrigger>
                <SelectContent>
                  {orderStatus === 'draft' && (
                    <SelectItem value="confirmed">Confirm Order</SelectItem>
                  )}
                  {orderStatus === 'pending' && (
                    <SelectItem value="confirmed">Confirm Order</SelectItem>
                  )}
                  {orderStatus === 'confirmed' && (
                    <SelectItem value="preparing">Start Preparing</SelectItem>
                  )}
                  {orderStatus === 'preparing' && (
                    <SelectItem value="ready">Mark as Ready</SelectItem>
                  )}
                  {orderStatus === 'ready' && (
                    <SelectItem value="completed">Complete Order</SelectItem>
                  )}
                  <SelectItem value="cancelled">Cancel Order</SelectItem>
                </SelectContent>
              </Select>
            </div>
            
            {form.data.newStatus === 'cancelled' && (
              <div>
                <Label htmlFor="reason">Cancellation Reason</Label>
                <Textarea
                  id="reason"
                  rows={3}
                  placeholder="Reason for cancellation..."
                  value={form.data.reason}
                  onChange={(e) => form.setData('reason', e.target.value)}
                />
              </div>
            )}
          </div>
        );
        
      default:
        return null;
    }
  };
  
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children || (
          <Button>
            <Plus className="h-4 w-4 mr-1 sm:mr-2" />
            <span className="hidden sm:inline">Record Action</span>
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[80vh]">
        <DialogHeader>
          <DialogTitle>Record Order Action</DialogTitle>
          <DialogDescription>
            Select an action to update the order
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <ScrollArea className="h-[60vh] pr-4">
            <div className="space-y-6">
              {/* Event Type Selection */}
              <div>
                <Label>Select Action Type</Label>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                  {availableEventTypes.map((type) => {
                    const Icon = type.icon;
                    const isSelected = selectedEventType === type.value;
                    
                    return (
                      <button
                        key={type.value}
                        type="button"
                        onClick={() => setSelectedEventType(type.value)}
                        className={cn(
                          "p-3 rounded-lg border-2 text-left transition-all",
                          "hover:border-gray-300",
                          isSelected 
                            ? "border-blue-500 bg-blue-50" 
                            : "border-gray-200"
                        )}
                      >
                        <div className="flex items-start gap-3">
                          <div className={cn(
                            "p-2 rounded-lg",
                            isSelected ? "bg-blue-100" : "bg-gray-100"
                          )}>
                            <Icon className={cn(
                              "h-4 w-4",
                              isSelected ? "text-blue-600" : "text-gray-600"
                            )} />
                          </div>
                          <div className="flex-1">
                            <p className="font-medium text-sm">{type.label}</p>
                            <p className="text-xs text-gray-500 mt-1">
                              {type.description}
                            </p>
                          </div>
                        </div>
                      </button>
                    );
                  })}
                </div>
              </div>
              
              {/* Event-specific Form */}
              {renderEventForm()}
            </div>
          </ScrollArea>
          
          <div className="flex justify-end gap-3 mt-6">
            <Button
              type="button"
              variant="outline"
              onClick={() => {
                setOpen(false);
                form.reset();
                setSelectedEventType(null);
              }}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={!selectedEventType || form.processing}
            >
              {form.processing ? 'Recording...' : 'Record Action'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}