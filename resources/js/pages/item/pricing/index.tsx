import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/currency-input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { ItemSelector } from '@/modules/item';
import { 
  DollarSign, 
  Clock, 
  Calendar,
  MapPin,
  Users,
  Tag,
  TrendingUp,
  TrendingDown,
  Plus,
  MoreHorizontal,
  AlertCircle,
  Settings,
  Percent,
  Calculator,
  Timer,
  Target,
  ChartBar,
  Edit,
  Copy,
  Trash,
  Power
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate, formatTime } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';

interface PriceRule {
  id: number;
  name: string;
  type: 'percentage_discount' | 'fixed_discount' | 'override' | 'multiplier';
  value: number;
  priority: number;
  startDate: string | null;
  endDate: string | null;
  timeStart: string | null;
  timeEnd: string | null;
  daysOfWeek: number[] | null;
  isActive: boolean;
  conditions: {
    itemIds?: number[];
    categoryIds?: number[];
    locationIds?: number[];
    customerGroupIds?: number[];
    minQuantity?: number;
    maxQuantity?: number;
  };
  appliedCount: number;
  totalDiscountAmount: number;
}

interface PriceSimulation {
  itemName: string;
  basePrice: number;
  finalPrice: number;
  appliedRules: Array<{
    ruleName: string;
    adjustment: number;
  }>;
}

interface PageProps {
  priceRules: PriceRule[];
  pagination: any;
  metadata: any;
  ruleTypes: Array<{ value: string; label: string }>;
  activeRulesCount: number;
  totalDiscountGiven: number;
  avgDiscountPercentage: number;
  upcomingRules: PriceRule[];
  expiringRules: PriceRule[];
  features: {
    timeBasedPricing: boolean;
    locationPricing: boolean;
    customerGroupPricing: boolean;
    quantityPricing: boolean;
  };
  items: Array<{ id: number; name: string; basePrice: number }>;
  categories: Array<{ id: number; name: string }>;
  locations: Array<{ id: number; name: string }>;
  customerGroups: Array<{ id: number; name: string }>;
}

export default function PricingIndex({ 
  priceRules, 
  pagination, 
  metadata,
  ruleTypes,
  activeRulesCount,
  totalDiscountGiven,
  avgDiscountPercentage,
  upcomingRules,
  expiringRules,
  features,
  items,
  categories,
  locations,
  customerGroups
}: PageProps) {
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [editingRule, setEditingRule] = useState<PriceRule | null>(null);
  const [simulationDialogOpen, setSimulationDialogOpen] = useState(false);
  const [simulationResults, setSimulationResults] = useState<PriceSimulation[]>([]);

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    type: 'percentage_discount',
    value: null as number | null,  // Store as number (integer for fixed amounts, float for percentages)
    priority: '0',
    start_date: '',
    end_date: '',
    time_start: '',
    time_end: '',
    days_of_week: [] as number[],
    is_active: true,
    item_ids: [] as number[],
    category_ids: [] as number[],
    location_ids: [] as number[],
    customer_group_ids: [] as number[],
    min_quantity: '',
    max_quantity: '',
  });

  const { data: simData, setData: setSimData } = useForm({
    item_id: '',
    location_id: '',
    customer_group_id: '',
    quantity: '1',
    date: new Date().toISOString().split('T')[0],
    time: new Date().toTimeString().split(' ')[0].substring(0, 5),
  });

  const columns: ColumnDef<PriceRule>[] = [
    {
      accessorKey: 'name',
      header: 'Rule Name',
      cell: ({ row }) => {
        const rule = row.original;
        return (
          <div className="flex flex-col">
            <span className="font-medium">{rule.name}</span>
            <div className="flex items-center gap-2 mt-1">
              <Badge variant="outline" className="text-xs">
                Priority: {rule.priority}
              </Badge>
              {rule.appliedCount > 0 && (
                <span className="text-xs text-muted-foreground">
                  Applied {rule.appliedCount} times
                </span>
              )}
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'type',
      header: 'Type',
      cell: ({ row }) => {
        const rule = row.original;
        const typeIcons = {
          percentage_discount: Percent,
          fixed_discount: DollarSign,
          override: Calculator,
          multiplier: TrendingUp,
        };
        const Icon = typeIcons[rule.type];
        return (
          <div className="flex items-center gap-2">
            <Icon className="h-4 w-4 text-muted-foreground" />
            <span className="capitalize">
              {rule.type.replace('_', ' ')}
            </span>
          </div>
        );
      },
    },
    {
      accessorKey: 'value',
      header: 'Value',
      cell: ({ row }) => {
        const rule = row.original;
        if (rule.type === 'percentage_discount') {
          return <span className="font-medium">-{rule.value}%</span>;
        } else if (rule.type === 'fixed_discount') {
          return <span className="font-medium">-{formatCurrency(rule.value)}</span>;
        } else if (rule.type === 'override') {
          return <span className="font-medium">{formatCurrency(rule.value)}</span>;
        } else {
          return <span className="font-medium">×{rule.value}</span>;
        }
      },
    },
    {
      id: 'conditions',
      header: 'Conditions',
      cell: ({ row }) => {
        const rule = row.original;
        const conditions = [];
        
        if (rule.conditions.itemIds?.length) {
          conditions.push(`${rule.conditions.itemIds.length} items`);
        }
        if (rule.conditions.categoryIds?.length) {
          conditions.push(`${rule.conditions.categoryIds.length} categories`);
        }
        if (rule.conditions.locationIds?.length) {
          conditions.push(`${rule.conditions.locationIds.length} locations`);
        }
        if (rule.conditions.minQuantity) {
          conditions.push(`Min qty: ${rule.conditions.minQuantity}`);
        }
        
        return (
          <div className="flex flex-wrap gap-1">
            {conditions.map((cond, i) => (
              <Badge key={i} variant="secondary" className="text-xs">
                {cond}
              </Badge>
            ))}
            {conditions.length === 0 && (
              <span className="text-xs text-muted-foreground">All items</span>
            )}
          </div>
        );
      },
    },
    {
      id: 'schedule',
      header: 'Schedule',
      cell: ({ row }) => {
        const rule = row.original;
        const hasDateRange = rule.startDate || rule.endDate;
        const hasTimeRange = rule.timeStart && rule.timeEnd;
        const hasDays = rule.daysOfWeek && rule.daysOfWeek.length > 0;
        
        return (
          <div className="space-y-1 text-sm">
            {hasDateRange && (
              <div className="flex items-center gap-1">
                <Calendar className="h-3 w-3 text-muted-foreground" />
                <span className="text-xs">
                  {rule.startDate ? formatDate(rule.startDate) : 'Start'} - 
                  {rule.endDate ? formatDate(rule.endDate) : 'End'}
                </span>
              </div>
            )}
            {hasTimeRange && (
              <div className="flex items-center gap-1">
                <Clock className="h-3 w-3 text-muted-foreground" />
                <span className="text-xs">
                  {formatTime(rule.timeStart)} - {formatTime(rule.timeEnd)}
                </span>
              </div>
            )}
            {!hasDateRange && !hasTimeRange && !hasDays && (
              <span className="text-xs text-muted-foreground">Always active</span>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'total_discount_amount',
      header: 'Impact',
      cell: ({ row }) => {
        const rule = row.original;
        return (
          <div className="text-right">
            <div className="font-medium">
              {formatCurrency(rule.totalDiscountAmount)}
            </div>
            <span className="text-xs text-muted-foreground">
              discount given
            </span>
          </div>
        );
      },
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.isActive ? 'success' : 'secondary'}>
          {row.original.isActive ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const rule = row.original;
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleEdit(rule)}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleDuplicate(rule)}>
                <Copy className="mr-2 h-4 w-4" />
                Duplicate
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleToggleActive(rule)}>
                <Power className="mr-2 h-4 w-4" />
                {rule.isActive ? 'Deactivate' : 'Activate'}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                className="text-destructive"
                onClick={() => handleDelete(rule.id)}
              >
                <Trash className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const handleEdit = (rule: PriceRule) => {
    setEditingRule(rule);
    setData({
      name: rule.name,
      type: rule.type,
      value: rule.value.toString(),
      priority: rule.priority.toString(),
      startDate: rule.startDate || '',
      endDate: rule.endDate || '',
      timeStart: rule.timeStart || '',
      timeEnd: rule.timeEnd || '',
      daysOfWeek: rule.daysOfWeek || [],
      isActive: rule.isActive,
      itemIds: rule.conditions.itemIds || [],
      categoryIds: rule.conditions.categoryIds || [],
      locationIds: rule.conditions.locationIds || [],
      customerGroupIds: rule.conditions.customerGroupIds || [],
      minQuantity: rule.conditions.minQuantity?.toString() || '',
      maxQuantity: rule.conditions.maxQuantity?.toString() || '',
    });
    setCreateDialogOpen(true);
  };

  const handleDuplicate = (rule: PriceRule) => {
    setEditingRule(null);
    setData({
      name: `${rule.name} (Copy)`,
      type: rule.type,
      value: rule.value.toString(),
      priority: rule.priority.toString(),
      startDate: rule.startDate || '',
      endDate: rule.endDate || '',
      timeStart: rule.timeStart || '',
      timeEnd: rule.timeEnd || '',
      daysOfWeek: rule.daysOfWeek || [],
      is_active: false,
      itemIds: rule.conditions.itemIds || [],
      categoryIds: rule.conditions.categoryIds || [],
      locationIds: rule.conditions.locationIds || [],
      customerGroupIds: rule.conditions.customerGroupIds || [],
      minQuantity: rule.conditions.minQuantity?.toString() || '',
      maxQuantity: rule.conditions.maxQuantity?.toString() || '',
    });
    setCreateDialogOpen(true);
  };

  const handleToggleActive = (rule: PriceRule) => {
    router.put(`/pricing/${rule.id}/toggle-active`);
  };

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this pricing rule?')) {
      router.delete(`/pricing/${id}`);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const url = editingRule ? `/pricing/${editingRule.id}` : '/pricing';
    const method = editingRule ? put : post;
    
    method(url, {
      onSuccess: () => {
        setCreateDialogOpen(false);
        setEditingRule(null);
        reset();
      },
    });
  };

  const runSimulation = (e: React.FormEvent) => {
    e.preventDefault();
    post('/pricing/simulate', {
      data: simData,
      onSuccess: (page: any) => {
        setSimulationResults(page.props.simulationResults);
      },
    });
  };

  const statsCards = [
    {
      title: 'Active Rules',
      value: activeRulesCount,
      icon: Target,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: 'Total Discounts',
      value: formatCurrency(totalDiscountGiven),
      icon: TrendingDown,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: 'Avg Discount',
      value: `${avgDiscountPercentage.toFixed(1)}%`,
      icon: Percent,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
    {
      title: 'Expiring Soon',
      value: expiringRules.length,
      icon: AlertCircle,
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
      alert: expiringRules.length > 0,
    },
  ];

  const dayOptions = [
    { value: 0, label: 'Sunday' },
    { value: 1, label: 'Monday' },
    { value: 2, label: 'Tuesday' },
    { value: 3, label: 'Wednesday' },
    { value: 4, label: 'Thursday' },
    { value: 5, label: 'Friday' },
    { value: 6, label: 'Saturday' },
  ];

  // Check if pricing rules are empty
  const isEmpty = priceRules.length === 0;

  return (
    <AppLayout>
      <Head title="Pricing Rules" />
      
      <Page>
        <Page.Header
          title="Pricing Rules"
          subtitle="Manage dynamic pricing, discounts, and special offers"
          actions={
            !isEmpty && (
              <Page.Actions>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setSimulationDialogOpen(true)}
                >
                  <Calculator className="mr-2 h-4 w-4" />
                  Price Simulator
                </Button>
                <Button
                  size="sm"
                  onClick={() => {
                    setEditingRule(null);
                    reset();
                    setCreateDialogOpen(true);
                  }}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  New Rule
                </Button>
              </Page.Actions>
            )
          }
        />
        
        <Page.Content>
          {isEmpty ? (
            <EmptyState
              icon={DollarSign}
              title="No pricing rules yet"
              description="Create dynamic pricing rules to offer discounts, set special prices for locations or times, and manage promotional pricing."
              actions={
                <Button onClick={() => {
                  setEditingRule(null);
                  reset();
                  setCreateDialogOpen(true);
                }}>
                  <Plus className="mr-2 h-4 w-4" />
                  Create First Rule
                </Button>
              }
              helpText={
                <>
                  Learn about <a href="#" className="text-primary hover:underline">pricing strategies</a>
                </>
              }
            />
          ) : (
            <>
              {/* Stats Cards */}
              <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
                {statsCards.map((stat, index) => {
                  const Icon = stat.icon;
                  return (
                    <Card key={index}>
                      <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <CardTitle className="text-sm font-medium">
                          {stat.title}
                        </CardTitle>
                        <div className={cn('p-2 rounded-lg', stat.bgColor)}>
                          <Icon className={cn('h-4 w-4', stat.color)} />
                        </div>
                      </CardHeader>
                      <CardContent>
                        <div className="text-2xl font-bold">{stat.value}</div>
                        {stat.alert && (
                          <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                            Review expiring rules
                          </p>
                        )}
                      </CardContent>
                    </Card>
                  );
                })}
              </div>

              <Tabs defaultValue="all" className="w-full">
            <TabsList>
              <TabsTrigger value="all">All Rules</TabsTrigger>
              <TabsTrigger value="upcoming">Upcoming</TabsTrigger>
              <TabsTrigger value="expiring">Expiring Soon</TabsTrigger>
            </TabsList>

            <TabsContent value="all" className="mt-6">
              <InertiaDataTable
                columns={columns}
                data={priceRules}
                pagination={pagination}
                filters={metadata?.filters}
              />
            </TabsContent>

            <TabsContent value="upcoming" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Upcoming Rules</CardTitle>
                  <CardDescription>
                    Rules that will become active in the future
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {upcomingRules.length > 0 ? (
                    <div className="space-y-4">
                      {upcomingRules.map((rule) => (
                        <div key={rule.id} className="flex items-center justify-between p-4 border rounded-lg">
                          <div>
                            <h4 className="font-medium">{rule.name}</h4>
                            <p className="text-sm text-muted-foreground mt-1">
                              Starts {formatDate(rule.startDate!)}
                            </p>
                          </div>
                          <Button
                            size="sm"
                            variant="outline"
                            onClick={() => handleEdit(rule)}
                          >
                            <Edit className="mr-2 h-4 w-4" />
                            Edit
                          </Button>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <Timer className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                      <p className="text-muted-foreground">No upcoming rules scheduled</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="expiring" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Expiring Soon</CardTitle>
                  <CardDescription>
                    Rules expiring in the next 7 days
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {expiringRules.length > 0 ? (
                    <div className="space-y-4">
                      {expiringRules.map((rule) => (
                        <div key={rule.id} className="flex items-center justify-between p-4 border rounded-lg border-amber-200 dark:border-amber-900">
                          <div>
                            <h4 className="font-medium">{rule.name}</h4>
                            <p className="text-sm text-amber-600 dark:text-amber-400 mt-1">
                              Expires {formatDate(rule.endDate!)}
                            </p>
                          </div>
                          <div className="flex gap-2">
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => handleEdit(rule)}
                            >
                              Extend
                            </Button>
                            <Button
                              size="sm"
                              variant="outline"
                              onClick={() => handleDuplicate(rule)}
                            >
                              Renew
                            </Button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <Calendar className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                      <p className="text-muted-foreground">No rules expiring soon</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
            </>
          )}
        </Page.Content>
      </Page>

      {/* Create/Edit Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent className="max-w-2xl max-h-[90vh] overflow-y-auto">
          <form onSubmit={handleSubmit}>
            <DialogHeader>
              <DialogTitle>
                {editingRule ? 'Edit Pricing Rule' : 'Create Pricing Rule'}
              </DialogTitle>
              <DialogDescription>
                Configure pricing rules for automatic discounts and special offers
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-6 my-6">
              {/* Basic Info */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Basic Information</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">
                      Rule Name <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="e.g., Weekend 20% Off"
                      className={errors.name ? 'border-destructive' : ''}
                    />
                    {errors.name && (
                      <p className="text-sm text-destructive">{errors.name}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="priority">Priority</Label>
                    <Input
                      id="priority"
                      type="number"
                      value={data.priority}
                      onChange={(e) => setData('priority', e.target.value)}
                      placeholder="0"
                    />
                    <p className="text-xs text-muted-foreground">
                      Higher priority rules are applied first
                    </p>
                  </div>
                </div>
              </div>

              <Separator />

              {/* Rule Type and Value */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Pricing Adjustment</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="type">
                      Rule Type <span className="text-destructive">*</span>
                    </Label>
                    <Select
                      value={data.type}
                      onValueChange={(value) => setData('type', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select type" />
                      </SelectTrigger>
                      <SelectContent>
                        {ruleTypes.map((type) => (
                          <SelectItem key={type.value} value={type.value}>
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="value">
                      Value <span className="text-destructive">*</span>
                    </Label>
                    {data.type === 'percentage_discount' ? (
                      <div className="relative">
                        <Input
                          id="value"
                          type="number"
                          step="0.01"
                          value={data.value || ''}
                          onChange={(e) => setData('value', parseFloat(e.target.value) || null)}
                          placeholder="20"
                          className={cn('pr-8', errors.value ? 'border-destructive' : '')}
                        />
                        <span className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                          %
                        </span>
                      </div>
                    ) : (
                      <CurrencyInput
                        id="value"
                        value={data.value}
                        onChange={(value) => setData('value', value)}
                        showSymbol={true}
                        placeholder="0.00"
                        className={errors.value ? 'border-destructive' : ''}
                      />
                    )}
                    {errors.value && (
                      <p className="text-sm text-destructive">{errors.value}</p>
                    )}
                  </div>
                </div>
              </div>

              <Separator />

              {/* Schedule */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Schedule</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="start_date">Start Date</Label>
                    <Input
                      id="start_date"
                      type="date"
                      value={data.startDate}
                      onChange={(e) => setData('start_date', e.target.value)}
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="end_date">End Date</Label>
                    <Input
                      id="end_date"
                      type="date"
                      value={data.endDate}
                      onChange={(e) => setData('end_date', e.target.value)}
                    />
                  </div>
                </div>
                
                {features.timeBasedPricing && (
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="time_start">Start Time</Label>
                      <Input
                        id="time_start"
                        type="time"
                        value={data.timeStart}
                        onChange={(e) => setData('time_start', e.target.value)}
                      />
                    </div>
                    
                    <div className="space-y-2">
                      <Label htmlFor="time_end">End Time</Label>
                      <Input
                        id="time_end"
                        type="time"
                        value={data.timeEnd}
                        onChange={(e) => setData('time_end', e.target.value)}
                      />
                    </div>
                  </div>
                )}
              </div>

              <Separator />

              {/* Conditions */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Conditions</h3>
                
                <div className="space-y-2">
                  <Label>Apply to Items</Label>
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder="All items" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="all">All items</SelectItem>
                      <SelectItem value="specific">Specific items</SelectItem>
                      <SelectItem value="category">By category</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                
                {features.locationPricing && (
                  <div className="space-y-2">
                    <Label>Locations</Label>
                    <Select>
                      <SelectTrigger>
                        <SelectValue placeholder="All locations" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All locations</SelectItem>
                        {locations.map((location) => (
                          <SelectItem key={location.id} value={location.id.toString()}>
                            {location.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>
                )}
                
                {features.quantityPricing && (
                  <div className="grid gap-4 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="min_quantity">Min Quantity</Label>
                      <Input
                        id="min_quantity"
                        type="number"
                        value={data.min_quantity}
                        onChange={(e) => setData('min_quantity', e.target.value)}
                        placeholder="1"
                      />
                    </div>
                    
                    <div className="space-y-2">
                      <Label htmlFor="max_quantity">Max Quantity</Label>
                      <Input
                        id="max_quantity"
                        type="number"
                        value={data.max_quantity}
                        onChange={(e) => setData('max_quantity', e.target.value)}
                        placeholder="No limit"
                      />
                    </div>
                  </div>
                )}
              </div>

              <Separator />

              {/* Status */}
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is_active">Active</Label>
                  <p className="text-sm text-muted-foreground">
                    Rule will be applied immediately when active
                  </p>
                </div>
                <Switch
                  id="is_active"
                  checked={data.is_active}
                  onCheckedChange={(checked) => setData('is_active', checked)}
                />
              </div>
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCreateDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                {editingRule ? 'Update Rule' : 'Create Rule'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Price Simulation Dialog */}
      <Dialog open={simulationDialogOpen} onOpenChange={setSimulationDialogOpen}>
        <DialogContent>
          <form onSubmit={runSimulation}>
            <DialogHeader>
              <DialogTitle>Price Simulator</DialogTitle>
              <DialogDescription>
                Test how pricing rules will affect item prices
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4 my-4">
              <div className="space-y-2">
                <Label>Item</Label>
                <ItemSelector
                  value={simData.item_id ? parseInt(simData.item_id) : undefined}
                  onValueChange={(value) => setData('item_id', value?.toString() || '')}
                  items={items}
                  showPrice={true}
                  showSku={false}
                />
              </div>
              
              {features.location_pricing && (
                <div className="space-y-2">
                  <Label>Location</Label>
                  <Select
                    value={simData.location_id}
                    onValueChange={(value) => setSimData('location_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select location" />
                    </SelectTrigger>
                    <SelectContent>
                      {locations.map((location) => (
                        <SelectItem key={location.id} value={location.id.toString()}>
                          {location.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
              
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="sim-quantity">Quantity</Label>
                  <Input
                    id="sim-quantity"
                    type="number"
                    value={simData.quantity}
                    onChange={(e) => setSimData('quantity', e.target.value)}
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="sim-date">Date</Label>
                  <Input
                    id="sim-date"
                    type="date"
                    value={simData.date}
                    onChange={(e) => setSimData('date', e.target.value)}
                  />
                </div>
              </div>
              
              {simulationResults.length > 0 && (
                <>
                  <Separator />
                  <div className="space-y-3">
                    <h4 className="text-sm font-medium">Simulation Results</h4>
                    {simulationResults.map((result, index) => (
                      <div key={index} className="p-4 border rounded-lg space-y-2">
                        <div className="flex justify-between items-start">
                          <span className="font-medium">{result.item_name}</span>
                          <Badge variant="success">
                            {formatCurrency(result.final_price)}
                          </Badge>
                        </div>
                        <div className="space-y-1 text-sm">
                          <div className="flex justify-between text-muted-foreground">
                            <span>Base Price</span>
                            <span>{formatCurrency(result.base_price)}</span>
                          </div>
                          {result.applied_rules.map((rule, i) => (
                            <div key={i} className="flex justify-between">
                              <span>{rule.rule_name}</span>
                              <span className="text-red-600">
                                -{formatCurrency(Math.abs(rule.adjustment))}
                              </span>
                            </div>
                          ))}
                        </div>
                      </div>
                    ))}
                  </div>
                </>
              )}
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setSimulationDialogOpen(false)}>
                Close
              </Button>
              <Button type="submit" disabled={processing}>
                Run Simulation
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}