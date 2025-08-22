import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Head, Link, router } from '@inertiajs/react';
import { 
  ArrowLeft, 
  Clock, 
  DollarSign, 
  Hash, 
  Percent, 
  Save, 
  Users, 
  ShoppingBag,
  Package,
  Coffee,
  Sun,
  Award,
  UserCheck,
  Info,
  ArrowRight
} from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { cn } from '@/lib/utils';

interface CreateOfferProps {
  types: Array<{ value: string; label: string }>;
  recurringSchedules: Array<{ value: string; label: string }>;
  customerSegments: Array<{ value: string; label: string }>;
  daysOfWeek: Array<{ value: string; label: string }>;
}

// Offer type configurations with icons and descriptions
const offerTypeConfigs = {
  percentage: {
    icon: Percent,
    title: 'Percentage Discount',
    description: 'Classic percentage off the total price',
    examples: '20% off, 50% off sale items',
  },
  fixed: {
    icon: DollarSign,
    title: 'Fixed Amount',
    description: 'Flat discount amount off the total',
    examples: '$5 off, $10 discount',
  },
  buy_x_get_y: {
    icon: ShoppingBag,
    title: 'Buy X Get Y',
    description: 'Bundle deals and BOGO offers',
    examples: 'Buy 2 get 1 free, 3 for 2',
  },
  combo: {
    icon: Package,
    title: 'Combo Deal',
    description: 'Special price for item combinations',
    examples: 'Meal deals, product bundles',
  },
  happy_hour: {
    icon: Coffee,
    title: 'Happy Hour',
    description: 'Time-based promotional pricing',
    examples: '3-6 PM drinks special',
  },
  early_bird: {
    icon: Sun,
    title: 'Early Bird',
    description: 'Rewards for early customers',
    examples: 'Before 11 AM special',
  },
  loyalty: {
    icon: Award,
    title: 'Loyalty Reward',
    description: 'Exclusive offers for repeat customers',
    examples: 'VIP member discount',
  },
  staff: {
    icon: UserCheck,
    title: 'Staff Discount',
    description: 'Special pricing for employees',
    examples: 'Employee 25% discount',
  },
};

function CreateOfferContent({ customerSegments, daysOfWeek }: CreateOfferProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [selectedType, setSelectedType] = useState<string>('');
  const [currentStep, setCurrentStep] = useState<'type' | 'details'>('type');
  
  const [formData, setFormData] = useState({
    // Essential
    name: '',
    type: '',
    value: 0,
    description: '',
    
    // Optional but prominent
    code: '',
    isActive: true,
    
    // Type-specific
    maxDiscount: null as number | null,
    autoApply: false,
    isStackable: false,
    
    // Schedule
    startsAt: '',
    endsAt: '',
    validDays: [] as string[],
    validTimeStart: '',
    validTimeEnd: '',
    
    // Conditions
    minimumAmount: null as number | null,
    minimumQuantity: null as number | null,
    priority: 0,
    
    // Limits
    usageLimit: null as number | null,
    usagePerCustomer: null as number | null,
    
    // Targeting
    locationIds: [] as number[],
    targetItemIds: [] as number[],
    targetCategoryIds: [] as number[],
    excludedItemIds: [] as number[],
    customerSegments: [] as string[],
    
    // Type-specific conditions
    buyQuantity: 2,
    getQuantity: 1,
    discountPercent: 100,
    comboItems: [] as Array<{ id: number; name: string; price: number }>,
  });

  const handleTypeSelection = (type: string) => {
    setSelectedType(type);
    setFormData(prev => ({
      ...prev,
      type,
      // Set smart defaults based on type
      autoApply: type === 'happy_hour' || type === 'early_bird',
      value: type === 'buy_x_get_y' ? 100 : type === 'staff' ? 25 : 0,
    }));
    setCurrentStep('details');
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!formData.name || !formData.type) {
      toast.error('Please fill in all required fields');
      return;
    }

    // Validate based on type
    if (selectedType === 'happy_hour' || selectedType === 'early_bird') {
      if (!formData.validTimeStart || !formData.validTimeEnd) {
        toast.error('Please set the time window for this offer');
        return;
      }
      if (formData.validDays.length === 0) {
        toast.error('Please select at least one day for this offer');
        return;
      }
    }

    if ((selectedType === 'percentage' || selectedType === 'fixed') && formData.value <= 0) {
      toast.error('Please enter a valid discount value');
      return;
    }
    
    setIsSubmitting(true);
    
    router.post('/offers', formData, {
      onSuccess: () => {
        toast.success('Offer created successfully');
      },
      onError: (errors) => {
        toast.error('Failed to create offer');
        console.error(errors);
      },
      onFinish: () => {
        setIsSubmitting(false);
      },
    });
  };

  const generatePromoCode = () => {
    const code = Math.random().toString(36).substring(2, 8).toUpperCase();
    setFormData(prev => ({ ...prev, code }));
  };

  // Render type-specific form fields
  const renderTypeSpecificFields = () => {
    switch (selectedType) {
      case 'percentage':
      case 'fixed':
        return (
          <>
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Discount Configuration</CardTitle>
                <CardDescription>
                  Set the discount value and any conditions
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-6">
                <div className="grid gap-6 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="value" className="flex items-center gap-1">
                      {selectedType === 'percentage' ? 'Discount Percentage' : 'Discount Amount'}
                      <span className="text-destructive">*</span>
                    </Label>
                    <div className="relative">
                      <div className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">
                        {selectedType === 'percentage' ? <Percent className="h-4 w-4" /> : <DollarSign className="h-4 w-4" />}
                      </div>
                      <Input
                        id="value"
                        type="number"
                        step="0.01"
                        min="0"
                        max={selectedType === 'percentage' ? 100 : undefined}
                        value={formData.value}
                        onChange={(e) => setFormData({ ...formData, value: parseFloat(e.target.value) || 0 })}
                        className="pl-10 text-lg font-semibold"
                        placeholder="0"
                        required
                      />
                    </div>
                    <p className="text-xs text-muted-foreground">
                      {selectedType === 'percentage' ? 'Enter value between 0-100' : 'Enter the discount amount'}
                    </p>
                  </div>

                  {selectedType === 'percentage' && (
                    <div className="space-y-2">
                      <Label htmlFor="maxDiscount">Maximum Discount Cap</Label>
                      <div className="relative">
                        <div className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">
                          <DollarSign className="h-4 w-4" />
                        </div>
                        <Input
                          id="maxDiscount"
                          type="number"
                          step="0.01"
                          value={formData.maxDiscount || ''}
                          onChange={(e) => setFormData({ 
                            ...formData, 
                            maxDiscount: e.target.value ? parseFloat(e.target.value) : null 
                          })}
                          className="pl-10"
                          placeholder="No limit"
                        />
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Prevents excessive discounts on large orders
                      </p>
                    </div>
                  )}
                </div>

                <div className="space-y-4 pt-4 border-t">
                  <h4 className="text-sm font-medium">Minimum Requirements</h4>
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="minimumAmount" className="text-sm text-muted-foreground">
                        Minimum Order Amount
                      </Label>
                      <div className="relative">
                        <div className="absolute top-1/2 left-3 -translate-y-1/2 text-muted-foreground">
                          <DollarSign className="h-3 w-3" />
                        </div>
                        <Input
                          id="minimumAmount"
                          type="number"
                          step="0.01"
                          value={formData.minimumAmount || ''}
                          onChange={(e) => setFormData({ 
                            ...formData, 
                            minimumAmount: e.target.value ? parseFloat(e.target.value) : null 
                          })}
                          className="pl-8"
                          placeholder="No minimum"
                        />
                      </div>
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="minimumQuantity" className="text-sm text-muted-foreground">
                        Minimum Items
                      </Label>
                      <Input
                        id="minimumQuantity"
                        type="number"
                        value={formData.minimumQuantity || ''}
                        onChange={(e) => setFormData({ 
                          ...formData, 
                          minimumQuantity: e.target.value ? parseInt(e.target.value) : null 
                        })}
                        placeholder="No minimum"
                      />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </>
        );

      case 'buy_x_get_y':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="text-base">Bundle Configuration</CardTitle>
              <CardDescription>Set up your BOGO or bundle deal</CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 md:grid-cols-7 items-center">
                <div className="col-span-3 space-y-2">
                  <Label className="text-sm font-medium">Customer Buys</Label>
                  <Input
                    type="number"
                    min="1"
                    value={formData.buyQuantity}
                    onChange={(e) => setFormData({ ...formData, buyQuantity: parseInt(e.target.value) || 1 })}
                    className="text-center text-2xl font-bold h-16"
                  />
                  <p className="text-xs text-muted-foreground text-center">items at full price</p>
                </div>
                
                <div className="flex items-center justify-center">
                  <ArrowRight className="h-6 w-6 text-muted-foreground" />
                </div>
                
                <div className="col-span-3 space-y-2">
                  <Label className="text-sm font-medium">Customer Gets</Label>
                  <Input
                    type="number"
                    min="1"
                    value={formData.getQuantity}
                    onChange={(e) => setFormData({ ...formData, getQuantity: parseInt(e.target.value) || 1 })}
                    className="text-center text-2xl font-bold h-16"
                  />
                  <p className="text-xs text-muted-foreground text-center">items discounted</p>
                </div>
              </div>

              <div className="space-y-2">
                <Label>Discount on Bonus Items</Label>
                <div className="relative">
                  <div className="absolute top-1/2 left-3 -translate-y-1/2">
                    <Percent className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input
                    type="number"
                    min="0"
                    max="100"
                    value={formData.discountPercent}
                    onChange={(e) => setFormData({ ...formData, discountPercent: parseFloat(e.target.value) || 0 })}
                    className="pl-10"
                    placeholder="100"
                  />
                </div>
                <p className="text-xs text-muted-foreground">
                  Set to 100% for completely free items
                </p>
              </div>

              <div className="rounded-lg bg-muted/50 p-4">
                <p className="text-sm font-medium mb-1">Customer sees:</p>
                <p className="text-lg">
                  Buy {formData.buyQuantity} and get {formData.getQuantity} {formData.discountPercent === 100 ? 'FREE' : `at ${formData.discountPercent}% off`}
                </p>
              </div>
            </CardContent>
          </Card>
        );

      case 'happy_hour':
      case 'early_bird':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <Clock className="h-4 w-4" />
                Time Window Configuration
              </CardTitle>
              <CardDescription>
                {selectedType === 'happy_hour' 
                  ? 'Set when your happy hour runs'
                  : 'Configure your early bird special hours'}
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="validTimeStart" className="font-medium">
                    Starts At
                    <span className="text-destructive ml-1">*</span>
                  </Label>
                  <Input
                    id="validTimeStart"
                    type="time"
                    value={formData.validTimeStart}
                    onChange={(e) => setFormData({ ...formData, validTimeStart: e.target.value })}
                    className="text-lg"
                    required
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="validTimeEnd" className="font-medium">
                    Ends At
                    <span className="text-destructive ml-1">*</span>
                  </Label>
                  <Input
                    id="validTimeEnd"
                    type="time"
                    value={formData.validTimeEnd}
                    onChange={(e) => setFormData({ ...formData, validTimeEnd: e.target.value })}
                    className="text-lg"
                    required
                  />
                </div>
              </div>

              <div className="space-y-2">
                <Label className="font-medium">
                  Active Days
                  <span className="text-destructive ml-1">*</span>
                </Label>
                <div className="flex flex-wrap gap-2">
                  {daysOfWeek.map((day) => (
                    <button
                      key={day.value}
                      type="button"
                      onClick={() => {
                        if (formData.validDays.includes(day.value)) {
                          setFormData({ 
                            ...formData, 
                            validDays: formData.validDays.filter(d => d !== day.value) 
                          });
                        } else {
                          setFormData({ 
                            ...formData, 
                            validDays: [...formData.validDays, day.value] 
                          });
                        }
                      }}
                      className={cn(
                        'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                        formData.validDays.includes(day.value)
                          ? 'bg-primary text-primary-foreground'
                          : 'bg-muted hover:bg-muted/80'
                      )}
                    >
                      {day.label}
                    </button>
                  ))}
                </div>
                <p className="text-xs text-muted-foreground">
                  Select all days when this offer should be active
                </p>
              </div>

              <div className="space-y-2">
                <Label htmlFor="value" className="flex items-center gap-1">
                  Discount Percentage
                  <span className="text-destructive">*</span>
                </Label>
                <div className="relative">
                  <div className="absolute top-1/2 left-3 -translate-y-1/2">
                    <Percent className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input
                    id="value"
                    type="number"
                    step="1"
                    min="0"
                    max="100"
                    value={formData.value}
                    onChange={(e) => setFormData({ ...formData, value: parseFloat(e.target.value) || 0 })}
                    className="pl-10 text-lg font-semibold"
                    placeholder="15"
                    required
                  />
                </div>
              </div>

              <div className="rounded-lg bg-blue-50 dark:bg-blue-950/20 p-4">
                <p className="text-sm">
                  <Info className="inline h-3 w-3 mr-1" />
                  This offer will automatically activate during the specified time window on selected days
                </p>
              </div>
            </CardContent>
          </Card>
        );

      case 'loyalty':
      case 'staff':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="text-base flex items-center gap-2">
                <Users className="h-4 w-4" />
                {selectedType === 'loyalty' ? 'Loyalty Program Setup' : 'Staff Discount Configuration'}
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="value" className="flex items-center gap-1">
                  Discount Percentage
                  <span className="text-destructive">*</span>
                </Label>
                <div className="relative">
                  <div className="absolute top-1/2 left-3 -translate-y-1/2">
                    <Percent className="h-4 w-4 text-muted-foreground" />
                  </div>
                  <Input
                    id="value"
                    type="number"
                    step="1"
                    min="0"
                    max="100"
                    value={formData.value}
                    onChange={(e) => setFormData({ ...formData, value: parseFloat(e.target.value) || 0 })}
                    className="pl-10 text-lg font-semibold"
                    placeholder={selectedType === 'staff' ? '25' : '10'}
                    required
                  />
                </div>
              </div>

              {selectedType === 'loyalty' && (
                <div className="space-y-2">
                  <Label className="font-medium">Customer Segments</Label>
                  <div className="flex flex-wrap gap-2">
                    {customerSegments.map((segment) => (
                      <button
                        key={segment.value}
                        type="button"
                        onClick={() => {
                          if (formData.customerSegments.includes(segment.value)) {
                            setFormData({ 
                              ...formData, 
                              customerSegments: formData.customerSegments.filter(s => s !== segment.value) 
                            });
                          } else {
                            setFormData({ 
                              ...formData, 
                              customerSegments: [...formData.customerSegments, segment.value] 
                            });
                          }
                        }}
                        className={cn(
                          'px-4 py-2 rounded-lg text-sm font-medium transition-all',
                          formData.customerSegments.includes(segment.value)
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted hover:bg-muted/80'
                        )}
                      >
                        {segment.label}
                      </button>
                    ))}
                  </div>
                  <p className="text-xs text-muted-foreground">
                    Select which customer groups can use this offer
                  </p>
                </div>
              )}

              <div className="rounded-lg bg-muted/50 p-4">
                <p className="text-sm">
                  <Info className="inline h-3 w-3 mr-1" />
                  {selectedType === 'staff' 
                    ? 'This discount will automatically apply when staff members are identified at checkout'
                    : 'Only customers in the selected segments will see and can use this offer'}
                </p>
              </div>
            </CardContent>
          </Card>
        );

      default:
        return null;
    }
  };

  if (currentStep === 'type') {
    return (
      <>
        <Page.Header
          title="Create Offer"
          subtitle="Choose the type of promotional offer you want to create"
          actions={
            <Button variant="outline" asChild>
              <Link href="/offers">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Cancel
              </Link>
            </Button>
          }
        />

        <Page.Content>
          <div className="max-w-5xl">
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
              {Object.entries(offerTypeConfigs).map(([value, config]) => (
                <Card
                  key={value}
                  className={cn(
                    'cursor-pointer transition-all hover:shadow-md hover:border-primary/50',
                    selectedType === value && 'ring-2 ring-primary border-primary'
                  )}
                  onClick={() => handleTypeSelection(value)}
                >
                  <CardHeader>
                    <div className="flex items-center gap-3 mb-2">
                      <config.icon className="h-5 w-5 text-muted-foreground" />
                      <CardTitle className="text-base">{config.title}</CardTitle>
                    </div>
                    <CardDescription className="text-sm">
                      {config.description}
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    <p className="text-xs text-muted-foreground">
                      <span className="font-medium">Examples:</span> {config.examples}
                    </p>
                  </CardContent>
                </Card>
              ))}
            </div>
          </div>
        </Page.Content>
      </>
    );
  }

  // Details step
  const config = offerTypeConfigs[selectedType as keyof typeof offerTypeConfigs];
  
  return (
    <>
      <Page.Header
        title="Create Offer"
        subtitle={config?.title || 'Configure your offer details'}
        actions={
          <div className="flex gap-2">
            <Button 
              variant="outline" 
              onClick={() => setCurrentStep('type')}
            >
              <ArrowLeft className="mr-2 h-4 w-4" />
              Change Type
            </Button>
            <Button variant="outline" asChild>
              <Link href="/offers">
                Cancel
              </Link>
            </Button>
          </div>
        }
      />

      <Page.Content>
        <form onSubmit={handleSubmit}>
          <div className="grid max-w-4xl gap-6">
            {/* Basic Information */}
            <Card>
              <CardHeader>
                <CardTitle>Basic Information</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name" className="flex items-center gap-1">
                      Offer Name
                      <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="name"
                      placeholder={
                        selectedType === 'happy_hour' ? 'e.g., Happy Hour Special' :
                        selectedType === 'early_bird' ? 'e.g., Early Bird Discount' :
                        selectedType === 'buy_x_get_y' ? 'e.g., Buy 2 Get 1 Free' :
                        selectedType === 'staff' ? 'e.g., Staff Discount' :
                        'e.g., Weekend Special'
                      }
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      required
                    />
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="code">Promo Code</Label>
                    <div className="flex gap-2">
                      <Input
                        id="code"
                        value={formData.code}
                        onChange={(e) => setFormData({ ...formData, code: e.target.value.toUpperCase() })}
                        placeholder="AUTO"
                        className="font-mono uppercase"
                      />
                      <Button
                        type="button"
                        variant="outline"
                        size="icon"
                        onClick={generatePromoCode}
                        title="Generate random code"
                      >
                        <Hash className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="description">Description</Label>
                  <Textarea
                    id="description"
                    value={formData.description}
                    onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                    placeholder="Describe what this offer provides..."
                    rows={3}
                  />
                </div>
              </CardContent>
            </Card>

            {/* Type-specific fields */}
            {renderTypeSpecificFields()}

            {/* Activation & Schedule */}
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Activation & Schedule</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between p-3 rounded-lg border">
                  <div className="space-y-0.5">
                    <Label className="text-sm font-medium">Active Immediately</Label>
                    <p className="text-xs text-muted-foreground">
                      Enable this offer as soon as it's created
                    </p>
                  </div>
                  <Switch
                    checked={formData.isActive}
                    onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
                  />
                </div>

                {(selectedType !== 'happy_hour' && selectedType !== 'early_bird') && (
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="startsAt" className="text-sm">
                        Start Date
                      </Label>
                      <Input
                        id="startsAt"
                        type="datetime-local"
                        value={formData.startsAt}
                        onChange={(e) => setFormData({ ...formData, startsAt: e.target.value })}
                      />
                    </div>
                    <div className="space-y-2">
                      <Label htmlFor="endsAt" className="text-sm">
                        End Date
                      </Label>
                      <Input
                        id="endsAt"
                        type="datetime-local"
                        value={formData.endsAt}
                        onChange={(e) => setFormData({ ...formData, endsAt: e.target.value })}
                      />
                    </div>
                  </div>
                )}

                <div className="grid gap-4 md:grid-cols-2">
                  <div className="flex items-center justify-between p-3 rounded-lg border border-dashed">
                    <div className="space-y-0.5">
                      <Label className="text-sm font-normal">Auto Apply</Label>
                      <p className="text-xs text-muted-foreground">
                        Apply when conditions are met
                      </p>
                    </div>
                    <Switch
                      checked={formData.autoApply}
                      onCheckedChange={(checked) => setFormData({ ...formData, autoApply: checked })}
                    />
                  </div>

                  <div className="flex items-center justify-between p-3 rounded-lg border border-dashed">
                    <div className="space-y-0.5">
                      <Label className="text-sm font-normal">Stackable</Label>
                      <p className="text-xs text-muted-foreground">
                        Can combine with other offers
                      </p>
                    </div>
                    <Switch
                      checked={formData.isStackable}
                      onCheckedChange={(checked) => setFormData({ ...formData, isStackable: checked })}
                    />
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Usage Limits */}
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Usage Limits</CardTitle>
              </CardHeader>
              <CardContent className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="usageLimit" className="text-sm">
                    Total Usage Limit
                  </Label>
                  <Input
                    id="usageLimit"
                    type="number"
                    value={formData.usageLimit || ''}
                    onChange={(e) => setFormData({ 
                      ...formData, 
                      usageLimit: e.target.value ? parseInt(e.target.value) : null 
                    })}
                    placeholder="Unlimited"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="usagePerCustomer" className="text-sm">
                    Per Customer Limit
                  </Label>
                  <Input
                    id="usagePerCustomer"
                    type="number"
                    value={formData.usagePerCustomer || ''}
                    onChange={(e) => setFormData({ 
                      ...formData, 
                      usagePerCustomer: e.target.value ? parseInt(e.target.value) : null 
                    })}
                    placeholder="Unlimited"
                  />
                </div>
              </CardContent>
            </Card>

            {/* Action Buttons */}
            <div className="flex items-center justify-between border-t pt-6">
              <p className="text-sm text-muted-foreground">
                <span className="text-destructive">*</span> Required fields
              </p>
              <div className="flex gap-3">
                <Button 
                  type="button"
                  variant="outline" 
                  onClick={() => setCurrentStep('type')}
                >
                  Back
                </Button>
                <Button type="submit" disabled={isSubmitting}>
                  {isSubmitting ? (
                    <>Creating...</>
                  ) : (
                    <>
                      <Save className="mr-2 h-4 w-4" />
                      Create Offer
                    </>
                  )}
                </Button>
              </div>
            </div>
          </div>
        </form>
      </Page.Content>
    </>
  );
}

export default function CreateOffer(props: CreateOfferProps) {
  return (
    <AppLayout>
      <Head title="Create Offer" />
      <Page>
        <CreateOfferContent {...props} />
      </Page>
    </AppLayout>
  );
}