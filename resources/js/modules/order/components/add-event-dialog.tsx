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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';
import { 
  Plus, 
  ShoppingCart, 
  Percent, 
  DollarSign, 
  User, 
  MessageSquare,
  AlertCircle,
  Package,
  ArrowRightCircle,
  X,
  Check
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
    value: 'apply_promotion',
    label: 'Apply Promotion',
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
    value: 'update_customer',
    label: 'Update Customer',
    icon: User,
    description: 'Update customer information',
    color: 'orange',
    allowedStatuses: ['draft', 'pending'],
  },
  {
    value: 'add_note',
    label: 'Add Note',
    icon: MessageSquare,
    description: 'Add a note to the order',
    color: 'gray',
    allowedStatuses: ['draft', 'pending', 'confirmed', 'preparing'],
  },
  {
    value: 'change_status',
    label: 'Change Status',
    icon: ArrowRightCircle,
    description: 'Transition order status',
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
    let data = {};
    
    if (orderId) {
      // Use web route for event sourcing
      endpoint = `/orders/${orderId}/events/add`;
      data = {
        eventType: selectedEventType,
        ...form.data,
      };
    } else {
      // Use API routes (for external/mobile clients)
      switch (selectedEventType) {
        case 'add_items':
          endpoint = `/api/orders/flow/${orderUuid}/items`;
          data = { items: form.data.items };
          break;
        case 'apply_promotion':
          endpoint = `/api/orders/flow/${orderUuid}/promotion`;
          data = { promotionId: form.data.promotionId };
          break;
        case 'add_tip':
          endpoint = `/api/orders/flow/${orderUuid}/tip`;
          data = { 
            amount: form.data.tipAmount,
            percentage: form.data.tipPercentage 
          };
          break;
        case 'change_status':
          if (form.data.newStatus === 'confirmed') {
            endpoint = `/api/orders/flow/${orderUuid}/confirm`;
          } else if (form.data.newStatus === 'cancelled') {
            endpoint = `/api/orders/flow/${orderUuid}/cancel`;
            data = { reason: form.data.reason };
          }
          break;
        default:
          toast.error('Event type not implemented');
          return;
      }
    }
    
    // Submit the form
    form.post(endpoint, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success('Event added successfully');
        setOpen(false);
        form.reset();
        setSelectedEventType(null);
        onEventAdded?.();
      },
      onError: (errors) => {
        toast.error('Failed to add event');
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
                value={form.data.customerName}
                onChange={(e) => form.setData('customerName', e.target.value)}
              />
            </div>
            <div>
              <Label htmlFor="customerPhone">Phone Number</Label>
              <Input
                id="customerPhone"
                type="tel"
                value={form.data.customerPhone}
                onChange={(e) => form.setData('customerPhone', e.target.value)}
              />
            </div>
            <div>
              <Label htmlFor="customerEmail">Email</Label>
              <Input
                id="customerEmail"
                type="email"
                value={form.data.customerEmail}
                onChange={(e) => form.setData('customerEmail', e.target.value)}
              />
            </div>
          </div>
        );
        
      case 'add_note':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor="note">Note</Label>
              <Textarea
                id="note"
                rows={4}
                placeholder="Add your note here..."
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
            <Plus className="h-4 w-4 mr-2" />
            Add Event
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="max-w-2xl max-h-[80vh]">
        <DialogHeader>
          <DialogTitle>Add Order Event</DialogTitle>
          <DialogDescription>
            Add a new event to modify the order state
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <ScrollArea className="h-[60vh] pr-4">
            <div className="space-y-6">
              {/* Event Type Selection */}
              <div>
                <Label>Event Type</Label>
                <div className="grid grid-cols-2 gap-3 mt-2">
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
              {form.processing ? 'Adding...' : 'Add Event'}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}