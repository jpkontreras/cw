import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import type { Order, OrderItem, PaymentTransaction } from '@/types/modules/order';
import { formatCurrency, formatOrderNumber, getPaymentStatusColor, getPaymentStatusLabel } from '@/types/modules/order/utils';
import { Head, Link, router, useForm } from '@inertiajs/react';
import {
  AlertCircle,
  ArrowLeft,
  Banknote,
  Calculator,
  Check,
  CreditCard,
  DollarSign,
  Plus,
  Receipt,
  Smartphone,
  Split,
  User,
  Users,
  X,
} from 'lucide-react';
import { useMemo, useState } from 'react';

interface PaymentPageProps {
  order: Order;
  payments: PaymentTransaction[];
  remainingAmount: number;
  suggestedTip: number;
  paymentMethods: Array<{
    id: string;
    name: string;
    icon: string;
    enabled: boolean;
  }>;
}

interface SplitAssignment {
  userId: string;
  name: string;
  items: string[];
  subtotal: number;
  tax: number;
  tip: number;
  total: number;
  paid: boolean;
  paymentMethod?: string;
}

// Payment Method Card
const PaymentMethodCard = ({ method, selected, onSelect }: { method: any; selected: boolean; onSelect: () => void }) => {
  const icons: Record<string, any> = {
    cash: Banknote,
    card: CreditCard,
    transfer: Smartphone,
    other: DollarSign,
  };
  const Icon = icons[method.icon] || DollarSign;

  return (
    <Card
      className={`cursor-pointer transition-all ${
        selected ? 'ring-2 ring-primary' : 'hover:shadow-md'
      } ${!method.enabled ? 'cursor-not-allowed opacity-50' : ''}`}
      onClick={() => method.enabled && onSelect()}
    >
      <CardContent className="p-6">
        <div className="flex flex-col items-center gap-3">
          <Icon className="h-8 w-8" />
          <span className="font-medium">{method.name}</span>
          {selected && (
            <div className="flex h-5 w-5 items-center justify-center rounded-full bg-primary">
              <Check className="h-3 w-3 text-white" />
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
};

// Split Bill Item Assignment
const SplitItemAssignment = ({
  item,
  assignments,
  people,
  onAssign,
}: {
  item: OrderItem;
  assignments: Record<string, string[]>;
  people: SplitAssignment[];
  onAssign: (itemId: string, personId: string) => void;
}) => {
  const assignedTo = people.find((p) => assignments[p.userId]?.includes(item.id));

  return (
    <div className="flex items-center justify-between rounded-lg p-3 hover:bg-gray-50">
      <div className="flex-1">
        <p className="font-medium">
          {item.quantity}x {item.itemName}
        </p>
        {item.modifiers && item.modifiers.length > 0 && (
          <p className="text-sm text-gray-500">{item.modifiers.map((m) => m.modifierName).join(', ')}</p>
        )}
        <p className="mt-1 text-sm font-medium">{formatCurrency(item.totalPrice)}</p>
      </div>
      <Select value={assignedTo?.userId || ''} onValueChange={(value) => onAssign(item.id, value)}>
        <SelectTrigger className="w-40">
          <SelectValue placeholder="Assign to..." />
        </SelectTrigger>
        <SelectContent>
          {people.map((person) => (
            <SelectItem key={person.userId} value={person.userId}>
              {person.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>
    </div>
  );
};

export default function PaymentPage({
  order,
  payments = [],
  remainingAmount,
  suggestedTip = 10,
  paymentMethods = [
    { id: 'cash', name: 'Cash', icon: 'cash', enabled: true },
    { id: 'card', name: 'Credit/Debit Card', icon: 'card', enabled: true },
    { id: 'transfer', name: 'Bank Transfer', icon: 'transfer', enabled: true },
    { id: 'other', name: 'Other', icon: 'other', enabled: true },
  ],
}: PaymentPageProps) {
  const [paymentMode, setPaymentMode] = useState<'full' | 'split'>('full');
  const [selectedMethod, setSelectedMethod] = useState('cash');
  const [tipPercentage, setTipPercentage] = useState(suggestedTip);
  const [customTipAmount, setCustomTipAmount] = useState('');
  const [useCustomTip, setUseCustomTip] = useState(false);
  const [splitPeople, setSplitPeople] = useState<SplitAssignment[]>([
    { userId: '1', name: 'Person 1', items: [], subtotal: 0, tax: 0, tip: 0, total: 0, paid: false },
  ]);
  const [itemAssignments, setItemAssignments] = useState<Record<string, string[]>>({});
  const [processing, setProcessing] = useState(false);

  const { data, setData, post, errors } = useForm({
    payment_method: selectedMethod,
    amount: remainingAmount,
    tip_amount: 0,
    reference_number: '',
    notes: '',
    split_payments: [] as any[],
  });

  // Calculate tip amount
  const tipAmount = useMemo(() => {
    if (useCustomTip && customTipAmount) {
      return parseFloat(customTipAmount);
    }
    return (order.subtotal * tipPercentage) / 100;
  }, [order.subtotal, tipPercentage, useCustomTip, customTipAmount]);

  // Calculate total with tip
  const totalWithTip = useMemo(() => {
    return order.totalAmount + tipAmount;
  }, [order.totalAmount, tipAmount]);

  // Update form data when payment method or amounts change
  useMemo(() => {
    setData((prev) => ({
      ...prev,
      payment_method: selectedMethod,
      amount: totalWithTip,
      tip_amount: tipAmount,
    }));
  }, [selectedMethod, totalWithTip, tipAmount, setData]);

  // Handle adding a person to split
  const handleAddPerson = () => {
    const newPerson: SplitAssignment = {
      userId: `${splitPeople.length + 1}`,
      name: `Person ${splitPeople.length + 1}`,
      items: [],
      subtotal: 0,
      tax: 0,
      tip: 0,
      total: 0,
      paid: false,
    };
    setSplitPeople([...splitPeople, newPerson]);
  };

  // Handle removing a person from split
  const handleRemovePerson = (userId: string) => {
    if (splitPeople.length <= 1) return;

    // Remove person and reassign their items
    const removedPerson = splitPeople.find((p) => p.userId === userId);
    if (removedPerson && itemAssignments[userId]) {
      const newAssignments = { ...itemAssignments };
      delete newAssignments[userId];
      setItemAssignments(newAssignments);
    }

    setSplitPeople(splitPeople.filter((p) => p.userId !== userId));
  };

  // Handle item assignment
  const handleItemAssignment = (itemId: string, personId: string) => {
    const newAssignments = { ...itemAssignments };

    // Remove item from previous assignment
    Object.keys(newAssignments).forEach((userId) => {
      newAssignments[userId] = (newAssignments[userId] || []).filter((id) => id !== itemId);
    });

    // Add to new person
    if (!newAssignments[personId]) {
      newAssignments[personId] = [];
    }
    newAssignments[personId].push(itemId);

    setItemAssignments(newAssignments);

    // Recalculate split amounts
    recalculateSplits(newAssignments);
  };

  // Recalculate split amounts
  const recalculateSplits = (assignments: Record<string, string[]>) => {
    const updatedPeople = splitPeople.map((person) => {
      const personItems = assignments[person.userId] || [];
      const items = order.items.filter((item) => personItems.includes(item.id));

      const subtotal = items.reduce((sum, item) => sum + item.totalPrice, 0);
      const taxRate = order.taxAmount / order.subtotal;
      const tax = subtotal * taxRate;
      const tipShare = (subtotal / order.subtotal) * tipAmount;

      return {
        ...person,
        items: personItems,
        subtotal,
        tax,
        tip: tipShare,
        total: subtotal + tax + tipShare,
      };
    });

    setSplitPeople(updatedPeople);
  };

  // Handle split equal
  const handleSplitEqual = () => {
    const itemsPerPerson = Math.ceil(order.items.length / splitPeople.length);
    const newAssignments: Record<string, string[]> = {};

    order.items.forEach((item, index) => {
      const personIndex = Math.floor(index / itemsPerPerson);
      const person = splitPeople[Math.min(personIndex, splitPeople.length - 1)];

      if (!newAssignments[person.userId]) {
        newAssignments[person.userId] = [];
      }
      newAssignments[person.userId].push(item.id);
    });

    setItemAssignments(newAssignments);
    recalculateSplits(newAssignments);
  };

  // Handle payment submission
  const handleSubmitPayment = () => {
    setProcessing(true);

    if (paymentMode === 'split') {
      // Prepare split payment data
      const splitPayments = splitPeople.map((person) => ({
        name: person.name,
        items: person.items,
        amount: person.total,
        payment_method: person.paymentMethod || selectedMethod,
      }));

      setData('split_payments', splitPayments);
    }

    post(`/orders/${order.id}/payment/process`, {
      preserveScroll: true,
      onSuccess: () => {
        // Redirect to receipt or order detail
        router.visit(`/orders/${order.id}/receipt`);
      },
      onError: () => {
        setProcessing(false);
      },
    });
  };

  // Check if all items are assigned (for split mode)
  const allItemsAssigned = useMemo(() => {
    const assignedItems = Object.values(itemAssignments).flat();
    return assignedItems.length === order.items.length;
  }, [itemAssignments, order.items]);

  return (
    <AppLayout>
      <Head title={`Payment - ${formatOrderNumber(order.orderNumber)}`} />
      <Page>
        <Page.Header
          title={
            <div className="flex items-center gap-4">
              <Link href={`/orders/${order.id}`} className="text-gray-600 hover:text-gray-900">
                <ArrowLeft className="h-5 w-5" />
              </Link>
              <div>
                <span>Process Payment</span>
              </div>
            </div>
          }
          subtitle={`Order ${formatOrderNumber(order.orderNumber)}`}
          actions={
            <Badge className={getPaymentStatusColor(order.paymentStatus)}>
              {getPaymentStatusLabel(order.paymentStatus)}
            </Badge>
          }
        />

        <Page.Content>
          <div className="max-w-6xl mx-auto">
            <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
              {/* Main Payment Section */}
              <div className="space-y-6 lg:col-span-2">
                {/* Payment Mode Tabs */}
                <Tabs value={paymentMode} onValueChange={(v) => setPaymentMode(v as 'full' | 'split')}>
                  <TabsList className="grid w-full grid-cols-2">
                    <TabsTrigger value="full">
                      <User className="mr-2 h-4 w-4" />
                      Full Payment
                    </TabsTrigger>
                    <TabsTrigger value="split">
                      <Users className="mr-2 h-4 w-4" />
                      Split Bill
                    </TabsTrigger>
                  </TabsList>

                  {/* Full Payment Tab */}
                  <TabsContent value="full" className="space-y-6">
                    {/* Payment Methods */}
                    <Card>
                      <CardHeader className="px-6 pt-6">
                        <CardTitle>Payment Method</CardTitle>
                        <CardDescription>Select how the customer wants to pay</CardDescription>
                      </CardHeader>
                      <CardContent className="p-6">
                        <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                      {paymentMethods.map((method) => (
                        <PaymentMethodCard
                          key={method.id}
                          method={method}
                          selected={selectedMethod === method.id}
                          onSelect={() => setSelectedMethod(method.id)}
                        />
                      ))}
                    </div>

                    {/* Additional fields for card payment */}
                    {selectedMethod === 'card' && (
                      <div className="mt-6 space-y-4">
                        <div>
                          <Label htmlFor="reference">Reference Number</Label>
                          <Input
                            id="reference"
                            placeholder="Transaction reference"
                            value={data.reference_number}
                            onChange={(e) => setData('reference_number', e.target.value)}
                          />
                        </div>
                      </div>
                    )}
                  </CardContent>
                </Card>

                {/* Tip Selection */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Add Tip</CardTitle>
                    <CardDescription>Select a suggested amount or enter custom</CardDescription>
                  </CardHeader>
                  <CardContent className="space-y-4 p-6">
                    <RadioGroup
                      value={useCustomTip ? 'custom' : tipPercentage.toString()}
                      onValueChange={(value) => {
                        if (value === 'custom') {
                          setUseCustomTip(true);
                        } else {
                          setUseCustomTip(false);
                          setTipPercentage(parseInt(value));
                        }
                      }}
                    >
                      <div className="grid grid-cols-4 gap-4">
                        {[0, 10, 15, 20].map((percentage) => (
                          <div key={percentage} className="flex items-center space-x-2">
                            <RadioGroupItem value={percentage.toString()} id={`tip-${percentage}`} />
                            <Label htmlFor={`tip-${percentage}`} className="cursor-pointer">
                              {percentage}% ({formatCurrency((order.subtotal * percentage) / 100)})
                            </Label>
                          </div>
                        ))}
                      </div>
                      <div className="mt-4 flex items-center space-x-2">
                        <RadioGroupItem value="custom" id="tip-custom" />
                        <Label htmlFor="tip-custom" className="cursor-pointer">
                          Custom Amount
                        </Label>
                      </div>
                    </RadioGroup>

                    {useCustomTip && (
                      <div className="flex items-center gap-2">
                        <span className="text-gray-500">$</span>
                        <Input
                          type="number"
                          placeholder="0.00"
                          value={customTipAmount}
                          onChange={(e) => setCustomTipAmount(e.target.value)}
                          className="max-w-32"
                        />
                      </div>
                    )}
                  </CardContent>
                </Card>
              </TabsContent>

              {/* Split Payment Tab */}
              <TabsContent value="split" className="space-y-6">
                {/* Split Options */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <div className="flex items-center justify-between">
                      <div>
                        <CardTitle>Split Between</CardTitle>
                        <CardDescription>Assign items to each person</CardDescription>
                      </div>
                      <div className="flex items-center gap-2">
                        <Button variant="outline" size="sm" onClick={handleSplitEqual}>
                          <Split className="mr-2 h-4 w-4" />
                          Split Equal
                        </Button>
                        <Button variant="outline" size="sm" onClick={handleAddPerson}>
                          <Plus className="mr-2 h-4 w-4" />
                          Add Person
                        </Button>
                      </div>
                    </div>
                  </CardHeader>
                  <CardContent className="p-6">
                    {/* People List */}
                    <div className="mb-6 space-y-4">
                      {splitPeople.map((person) => (
                        <div key={person.userId} className="flex items-center gap-4 rounded-lg bg-gray-50 p-3">
                          <Input
                            value={person.name}
                            onChange={(e) => {
                              const updated = splitPeople.map((p) => (p.userId === person.userId ? { ...p, name: e.target.value } : p));
                              setSplitPeople(updated);
                            }}
                            className="max-w-40"
                          />
                          <div className="flex-1">
                            <p className="text-sm text-gray-500">{person.items.length} items</p>
                          </div>
                          <p className="font-medium">{formatCurrency(person.total)}</p>
                          {splitPeople.length > 1 && (
                            <Button variant="ghost" size="icon" onClick={() => handleRemovePerson(person.userId)}>
                              <X className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      ))}
                    </div>

                    <Separator className="my-6" />

                    {/* Item Assignment */}
                    <div className="space-y-3">
                      <h4 className="font-medium">Assign Items</h4>
                      {order.items.map((item) => (
                        <SplitItemAssignment
                          key={item.id}
                          item={item}
                          assignments={itemAssignments}
                          people={splitPeople}
                          onAssign={handleItemAssignment}
                        />
                      ))}
                    </div>

                    {!allItemsAssigned && (
                      <Alert className="mt-4">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>Please assign all items before processing payment</AlertDescription>
                      </Alert>
                    )}
                  </CardContent>
                </Card>

                {/* Split Summary */}
                {allItemsAssigned && (
                  <Card>
                    <CardHeader className="px-6 pt-6">
                      <CardTitle>Split Summary</CardTitle>
                    </CardHeader>
                    <CardContent className="p-6">
                      <div className="space-y-4">
                        {splitPeople.map((person) => (
                          <div key={person.userId} className="space-y-2">
                            <div className="flex items-center justify-between">
                              <h4 className="font-medium">{person.name}</h4>
                              <Badge variant={person.paid ? 'default' : 'secondary'}>{person.paid ? 'Paid' : 'Pending'}</Badge>
                            </div>
                            <div className="space-y-1 text-sm">
                              <div className="flex justify-between">
                                <span className="text-gray-500">Subtotal</span>
                                <span>{formatCurrency(person.subtotal)}</span>
                              </div>
                              <div className="flex justify-between">
                                <span className="text-gray-500">Tax</span>
                                <span>{formatCurrency(person.tax)}</span>
                              </div>
                              <div className="flex justify-between">
                                <span className="text-gray-500">Tip</span>
                                <span>{formatCurrency(person.tip)}</span>
                              </div>
                              <Separator />
                              <div className="flex justify-between font-medium">
                                <span>Total</span>
                                <span>{formatCurrency(person.total)}</span>
                              </div>
                            </div>
                          </div>
                        ))}
                      </div>
                    </CardContent>
                  </Card>
                )}
                  </TabsContent>
                </Tabs>

                {/* Notes */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Payment Notes</CardTitle>
                  </CardHeader>
                  <CardContent className="p-6">
                    <Input placeholder="Add any notes about this payment..." value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                  </CardContent>
                </Card>
              </div>

              {/* Order Summary Sidebar */}
              <div className="space-y-6">
                {/* Order Summary */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle>Order Summary</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-4 p-6">
                    {/* Items */}
                    <div className="space-y-2">
                      {order.items.map((item) => (
                        <div key={item.id} className="flex justify-between text-sm">
                          <span>
                            {item.quantity}x {item.item_name}
                          </span>
                          <span>{formatCurrency(item.total_price)}</span>
                        </div>
                      ))}
                    </div>

                    <Separator />

                    {/* Totals */}
                    <div className="space-y-2">
                      <div className="flex justify-between text-sm">
                        <span>Subtotal</span>
                        <span>{formatCurrency(order.subtotal)}</span>
                      </div>
                      <div className="flex justify-between text-sm">
                        <span>Tax (19%)</span>
                        <span>{formatCurrency(order.taxAmount)}</span>
                      </div>
                      {order.discountAmount > 0 && (
                        <div className="flex justify-between text-sm text-green-600">
                          <span>Discount</span>
                          <span>-{formatCurrency(order.discountAmount)}</span>
                        </div>
                      )}
                      <div className="flex justify-between text-sm">
                        <span>Tip</span>
                        <span>{formatCurrency(tipAmount)}</span>
                      </div>
                      <Separator />
                      <div className="flex justify-between text-lg font-semibold">
                        <span>Total</span>
                        <span>{formatCurrency(totalWithTip)}</span>
                      </div>
                    </div>

                    {/* Previous Payments */}
                    {payments.length > 0 && (
                      <>
                        <Separator />
                        <div className="space-y-2">
                          <h4 className="text-sm font-medium">Previous Payments</h4>
                          {payments.map((payment) => (
                            <div key={payment.id} className="flex justify-between text-sm">
                              <span>{payment.method}</span>
                              <span className="text-green-600">-{formatCurrency(payment.amount)}</span>
                            </div>
                          ))}
                        </div>
                        <div className="flex justify-between font-semibold">
                          <span>Remaining</span>
                          <span>{formatCurrency(remainingAmount + tipAmount)}</span>
                        </div>
                      </>
                    )}
                  </CardContent>
                </Card>

                {/* Action Buttons */}
                <Card>
                  <CardContent className="space-y-3 p-6">
                    <Button
                      className="w-full"
                      size="lg"
                      onClick={handleSubmitPayment}
                      disabled={processing || (paymentMode === 'split' && !allItemsAssigned)}
                    >
                      {processing ? (
                        <>Processing...</>
                      ) : (
                        <>
                          <CreditCard className="mr-2 h-5 w-5" />
                          Process Payment
                        </>
                      )}
                    </Button>
                    <Button variant="outline" className="w-full" onClick={() => router.visit(`/orders/${order.id}`)}>
                      Cancel
                    </Button>
                  </CardContent>
                </Card>

                {/* Quick Actions */}
                <Card>
                  <CardHeader className="px-6 pt-6">
                    <CardTitle className="text-sm">Quick Actions</CardTitle>
                  </CardHeader>
                  <CardContent className="space-y-2 p-6">
                    <Button variant="outline" size="sm" className="w-full">
                      <Calculator className="mr-2 h-4 w-4" />
                      Open Calculator
                    </Button>
                    <Button variant="outline" size="sm" className="w-full">
                      <Receipt className="mr-2 h-4 w-4" />
                      Print Receipt
                    </Button>
                  </CardContent>
                </Card>
              </div>
            </div>
          </div>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}
