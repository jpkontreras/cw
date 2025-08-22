import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Progress } from '@/components/ui/progress';
import { Separator } from '@/components/ui/separator';
import {
  ArrowLeft,
  Edit,
  Copy,
  Trash,
  MoreHorizontal,
  ToggleLeft,
  ToggleRight,
  Calendar,
  Clock,
  Users,
  ShoppingCart,
  TrendingUp,
  Hash,
  DollarSign,
  Percent,
  Package,
  AlertCircle,
  CheckCircle,
  XCircle,
  BarChart3,
  Activity,
  Target,
  Zap,
  Info,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast } from 'sonner';

interface OfferDetails {
  id: number;
  name: string;
  description?: string;
  type: string;
  value: number;
  formattedValue: string;
  code?: string;
  isActive: boolean;
  statusLabel: string;
  
  // Schedule
  startsAt?: string;
  endsAt?: string;
  validDays?: string[];
  validTimeStart?: string;
  validTimeEnd?: string;
  
  // Usage & Limits
  usageCount: number;
  usageLimit?: number;
  remainingUses?: number;
  usagePerCustomer?: number;
  
  // Conditions
  minimumAmount?: number;
  minimumQuantity?: number;
  maxDiscount?: number;
  
  // Settings
  autoApply: boolean;
  isStackable: boolean;
  priority: number;
  
  // Type-specific
  buyQuantity?: number;
  getQuantity?: number;
  discountPercent?: number;
  
  // Relations
  locations?: Array<{ id: number; name: string }>;
  targetItems?: Array<{ id: number; name: string; price: number }>;
  targetCategories?: Array<{ id: number; name: string }>;
  excludedItems?: Array<{ id: number; name: string }>;
  customerSegments?: Array<{ id: number; name: string }>;
  
  // Analytics
  totalRevenue?: number;
  totalDiscount?: number;
  averageOrderValue?: number;
  conversionRate?: number;
  
  // Recent usage
  recentUsage?: Array<{
    id: number;
    orderId: number;
    orderNumber: string;
    customerName?: string;
    discountAmount: number;
    usedAt: string;
  }>;
  
  // Metadata
  createdAt: string;
  updatedAt: string;
  createdBy?: { id: number; name: string };
}

interface ShowOfferProps {
  offer: OfferDetails;
}

function ShowOfferContent({ offer }: ShowOfferProps) {
  const [isDeleting, setIsDeleting] = useState(false);

  const handleToggleStatus = () => {
    router.post(`/offers/${offer.id}/${offer.isActive ? 'deactivate' : 'activate'}`, {}, {
      onSuccess: () => {
        toast.success(`Offer ${offer.isActive ? 'deactivated' : 'activated'} successfully`);
      },
    });
  };

  const handleDuplicate = () => {
    router.post(`/offers/${offer.id}/duplicate`, {}, {
      onSuccess: () => {
        toast.success('Offer duplicated successfully');
      },
    });
  };

  const handleDelete = () => {
    if (confirm('Are you sure you want to delete this offer? This action cannot be undone.')) {
      setIsDeleting(true);
      router.delete(`/offers/${offer.id}`, {
        onSuccess: () => {
          toast.success('Offer deleted successfully');
        },
        onFinish: () => {
          setIsDeleting(false);
        },
      });
    }
  };

  // Calculate usage percentage
  const usagePercentage = offer.usageLimit 
    ? (offer.usageCount / offer.usageLimit) * 100 
    : 0;

  // Get status color
  const getStatusColor = (status: string) => {
    switch (status) {
      case 'Active':
        return 'text-green-600 bg-green-50 dark:bg-green-950/20';
      case 'Scheduled':
        return 'text-blue-600 bg-blue-50 dark:bg-blue-950/20';
      case 'Expired':
        return 'text-red-600 bg-red-50 dark:bg-red-950/20';
      case 'Exhausted':
        return 'text-orange-600 bg-orange-50 dark:bg-orange-950/20';
      default:
        return 'text-gray-600 bg-gray-50 dark:bg-gray-950/20';
    }
  };

  // Format date for display
  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  // Format currency
  const formatCurrency = (amount: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(amount);
  };

  return (
    <>
      <Page.Header
        title={offer.name}
        subtitle={offer.description || `${offer.type.replace('_', ' ')} offer`}
        actions={
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href="/offers">
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Offers
              </Link>
            </Button>
            
            <Button asChild>
              <Link href={`/offers/${offer.id}/edit`}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </Link>
            </Button>

            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="icon">
                  <MoreHorizontal className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={handleToggleStatus}>
                  {offer.isActive ? (
                    <>
                      <ToggleLeft className="mr-2 h-4 w-4" />
                      Deactivate
                    </>
                  ) : (
                    <>
                      <ToggleRight className="mr-2 h-4 w-4" />
                      Activate
                    </>
                  )}
                </DropdownMenuItem>
                <DropdownMenuItem onClick={handleDuplicate}>
                  <Copy className="mr-2 h-4 w-4" />
                  Duplicate
                </DropdownMenuItem>
                <DropdownMenuItem asChild>
                  <Link href={`/offers/${offer.id}/analytics`}>
                    <BarChart3 className="mr-2 h-4 w-4" />
                    View Analytics
                  </Link>
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  className="text-destructive"
                  onClick={handleDelete}
                  disabled={isDeleting}
                >
                  <Trash className="mr-2 h-4 w-4" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        }
      />

      <Page.Content>
        <div className="space-y-6">
          {/* Status Banner */}
          <div className={cn(
            "rounded-lg border p-4 flex items-center justify-between",
            getStatusColor(offer.statusLabel)
          )}>
            <div className="flex items-center gap-3">
              {offer.statusLabel === 'Active' && <CheckCircle className="h-5 w-5" />}
              {offer.statusLabel === 'Scheduled' && <Clock className="h-5 w-5" />}
              {offer.statusLabel === 'Expired' && <XCircle className="h-5 w-5" />}
              {offer.statusLabel === 'Exhausted' && <AlertCircle className="h-5 w-5" />}
              <div>
                <p className="font-medium">Status: {offer.statusLabel}</p>
                {offer.statusLabel === 'Scheduled' && offer.startsAt && (
                  <p className="text-sm opacity-90">Starts {formatDate(offer.startsAt)}</p>
                )}
                {offer.statusLabel === 'Active' && offer.endsAt && (
                  <p className="text-sm opacity-90">Ends {formatDate(offer.endsAt)}</p>
                )}
              </div>
            </div>
            {offer.code && (
              <div className="text-right">
                <p className="text-sm opacity-75">Promo Code</p>
                <code className="text-lg font-mono font-bold">{offer.code}</code>
              </div>
            )}
          </div>

          {/* Key Metrics */}
          <div className="grid gap-4 md:grid-cols-4">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Discount Value</CardTitle>
                {offer.type === 'percentage' ? <Percent className="h-4 w-4 text-muted-foreground" /> : <DollarSign className="h-4 w-4 text-muted-foreground" />}
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{offer.formattedValue}</div>
                {offer.maxDiscount && (
                  <p className="text-xs text-muted-foreground">
                    Max discount: {formatCurrency(offer.maxDiscount)}
                  </p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Times Used</CardTitle>
                <Activity className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">{offer.usageCount}</div>
                {offer.usageLimit && (
                  <div className="mt-2 space-y-1">
                    <div className="flex items-center justify-between text-xs">
                      <span className="text-muted-foreground">Limit: {offer.usageLimit}</span>
                      <span className="font-medium">{Math.round(usagePercentage)}%</span>
                    </div>
                    <Progress value={usagePercentage} className="h-1" />
                  </div>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Total Savings</CardTitle>
                <TrendingUp className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {offer.totalDiscount ? formatCurrency(offer.totalDiscount) : '$0'}
                </div>
                {offer.averageOrderValue && (
                  <p className="text-xs text-muted-foreground">
                    Avg order: {formatCurrency(offer.averageOrderValue)}
                  </p>
                )}
              </CardContent>
            </Card>

            <Card>
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-sm font-medium">Conversion Rate</CardTitle>
                <Target className="h-4 w-4 text-muted-foreground" />
              </CardHeader>
              <CardContent>
                <div className="text-2xl font-bold">
                  {offer.conversionRate ? `${offer.conversionRate.toFixed(1)}%` : '0%'}
                </div>
                <p className="text-xs text-muted-foreground">
                  From eligible orders
                </p>
              </CardContent>
            </Card>
          </div>

          <Tabs defaultValue="details" className="space-y-4">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="details">Details</TabsTrigger>
              <TabsTrigger value="conditions">Conditions</TabsTrigger>
              <TabsTrigger value="usage">Recent Usage</TabsTrigger>
            </TabsList>

            {/* Details Tab */}
            <TabsContent value="details" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Offer Configuration</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Basic Info */}
                  <div>
                    <h4 className="text-sm font-medium mb-3">Basic Information</h4>
                    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div>
                        <dt className="text-sm text-muted-foreground">Type</dt>
                        <dd className="text-sm font-medium mt-1">
                          <Badge variant="secondary">{offer.type.replace('_', ' ')}</Badge>
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">Priority</dt>
                        <dd className="text-sm font-medium mt-1">{offer.priority || 'Normal'}</dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">Auto Apply</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.autoApply ? (
                            <Badge variant="outline" className="text-green-600">
                              <CheckCircle className="mr-1 h-3 w-3" />
                              Enabled
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-gray-600">
                              <XCircle className="mr-1 h-3 w-3" />
                              Disabled
                            </Badge>
                          )}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">Stackable</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.isStackable ? (
                            <Badge variant="outline" className="text-green-600">
                              <CheckCircle className="mr-1 h-3 w-3" />
                              Yes
                            </Badge>
                          ) : (
                            <Badge variant="outline" className="text-gray-600">
                              <XCircle className="mr-1 h-3 w-3" />
                              No
                            </Badge>
                          )}
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <Separator />

                  {/* Schedule */}
                  <div>
                    <h4 className="text-sm font-medium mb-3 flex items-center gap-2">
                      <Calendar className="h-4 w-4" />
                      Schedule
                    </h4>
                    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div>
                        <dt className="text-sm text-muted-foreground">Start Date</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.startsAt ? formatDate(offer.startsAt) : 'Immediately'}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">End Date</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.endsAt ? formatDate(offer.endsAt) : 'No end date'}
                        </dd>
                      </div>
                      {offer.validTimeStart && offer.validTimeEnd && (
                        <>
                          <div>
                            <dt className="text-sm text-muted-foreground">Time Window</dt>
                            <dd className="text-sm font-medium mt-1">
                              {offer.validTimeStart} - {offer.validTimeEnd}
                            </dd>
                          </div>
                          {offer.validDays && offer.validDays.length > 0 && (
                            <div>
                              <dt className="text-sm text-muted-foreground">Active Days</dt>
                              <dd className="text-sm font-medium mt-1">
                                {offer.validDays.join(', ')}
                              </dd>
                            </div>
                          )}
                        </>
                      )}
                    </dl>
                  </div>

                  {/* Type-specific Details */}
                  {offer.type === 'buy_x_get_y' && (
                    <>
                      <Separator />
                      <div>
                        <h4 className="text-sm font-medium mb-3 flex items-center gap-2">
                          <Package className="h-4 w-4" />
                          Bundle Configuration
                        </h4>
                        <div className="rounded-lg bg-muted/50 p-4">
                          <p className="text-lg font-medium">
                            Buy {offer.buyQuantity || 0} Get {offer.getQuantity || 0} 
                            {offer.discountPercent === 100 ? ' FREE' : ` at ${offer.discountPercent}% off`}
                          </p>
                        </div>
                      </div>
                    </>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Conditions Tab */}
            <TabsContent value="conditions" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Requirements & Restrictions</CardTitle>
                </CardHeader>
                <CardContent className="space-y-6">
                  {/* Minimum Requirements */}
                  <div>
                    <h4 className="text-sm font-medium mb-3">Minimum Requirements</h4>
                    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div>
                        <dt className="text-sm text-muted-foreground">Minimum Order Amount</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.minimumAmount ? formatCurrency(offer.minimumAmount) : 'No minimum'}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">Minimum Items</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.minimumQuantity || 'No minimum'}
                        </dd>
                      </div>
                    </dl>
                  </div>

                  <Separator />

                  {/* Usage Limits */}
                  <div>
                    <h4 className="text-sm font-medium mb-3">Usage Limits</h4>
                    <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                      <div>
                        <dt className="text-sm text-muted-foreground">Total Usage Limit</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.usageLimit || 'Unlimited'}
                          {offer.remainingUses !== undefined && (
                            <span className="text-muted-foreground ml-2">
                              ({offer.remainingUses} remaining)
                            </span>
                          )}
                        </dd>
                      </div>
                      <div>
                        <dt className="text-sm text-muted-foreground">Per Customer Limit</dt>
                        <dd className="text-sm font-medium mt-1">
                          {offer.usagePerCustomer || 'Unlimited'}
                        </dd>
                      </div>
                    </dl>
                  </div>

                  {/* Targeting */}
                  {(offer.locations?.length || offer.targetItems?.length || offer.targetCategories?.length || offer.customerSegments?.length) && (
                    <>
                      <Separator />
                      <div>
                        <h4 className="text-sm font-medium mb-3">Targeting</h4>
                        <div className="space-y-3">
                          {offer.locations && offer.locations.length > 0 && (
                            <div>
                              <dt className="text-sm text-muted-foreground mb-2">Locations</dt>
                              <div className="flex flex-wrap gap-2">
                                {offer.locations.map((location) => (
                                  <Badge key={location.id} variant="secondary">
                                    {location.name}
                                  </Badge>
                                ))}
                              </div>
                            </div>
                          )}
                          
                          {offer.targetCategories && offer.targetCategories.length > 0 && (
                            <div>
                              <dt className="text-sm text-muted-foreground mb-2">Categories</dt>
                              <div className="flex flex-wrap gap-2">
                                {offer.targetCategories.map((category) => (
                                  <Badge key={category.id} variant="secondary">
                                    {category.name}
                                  </Badge>
                                ))}
                              </div>
                            </div>
                          )}
                          
                          {offer.customerSegments && offer.customerSegments.length > 0 && (
                            <div>
                              <dt className="text-sm text-muted-foreground mb-2">Customer Segments</dt>
                              <div className="flex flex-wrap gap-2">
                                {offer.customerSegments.map((segment) => (
                                  <Badge key={segment.id} variant="secondary">
                                    {segment.name}
                                  </Badge>
                                ))}
                              </div>
                            </div>
                          )}
                        </div>
                      </div>
                    </>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            {/* Usage Tab */}
            <TabsContent value="usage" className="space-y-4">
              <Card>
                <CardHeader>
                  <CardTitle>Recent Usage History</CardTitle>
                  <CardDescription>
                    Last 10 times this offer was applied
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {offer.recentUsage && offer.recentUsage.length > 0 ? (
                    <div className="space-y-3">
                      {offer.recentUsage.map((usage) => (
                        <div
                          key={usage.id}
                          className="flex items-center justify-between p-3 rounded-lg border"
                        >
                          <div className="flex items-center gap-3">
                            <div className="h-10 w-10 rounded-full bg-primary/10 flex items-center justify-center">
                              <ShoppingCart className="h-5 w-5 text-primary" />
                            </div>
                            <div>
                              <Link
                                href={`/orders/${usage.orderId}`}
                                className="font-medium hover:underline"
                              >
                                Order #{usage.orderNumber}
                              </Link>
                              {usage.customerName && (
                                <p className="text-sm text-muted-foreground">
                                  {usage.customerName}
                                </p>
                              )}
                            </div>
                          </div>
                          <div className="text-right">
                            <p className="font-medium">{formatCurrency(usage.discountAmount)}</p>
                            <p className="text-xs text-muted-foreground">
                              {new Date(usage.usedAt).toLocaleDateString()}
                            </p>
                          </div>
                        </div>
                      ))}
                      
                      <div className="pt-3 border-t">
                        <Link
                          href={`/offers/${offer.id}/analytics`}
                          className="text-sm text-primary hover:underline flex items-center gap-1"
                        >
                          View full analytics
                          <BarChart3 className="h-3 w-3" />
                        </Link>
                      </div>
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <Info className="h-12 w-12 text-muted-foreground mx-auto mb-3" />
                      <p className="text-sm text-muted-foreground">
                        This offer hasn't been used yet
                      </p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>

          {/* Metadata */}
          <Card>
            <CardHeader>
              <CardTitle className="text-sm">Information</CardTitle>
            </CardHeader>
            <CardContent>
              <dl className="grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
                <div>
                  <dt className="text-muted-foreground">Created</dt>
                  <dd className="font-medium">{formatDate(offer.createdAt)}</dd>
                </div>
                <div>
                  <dt className="text-muted-foreground">Last Updated</dt>
                  <dd className="font-medium">{formatDate(offer.updatedAt)}</dd>
                </div>
                {offer.createdBy && (
                  <div>
                    <dt className="text-muted-foreground">Created By</dt>
                    <dd className="font-medium">{offer.createdBy.name}</dd>
                  </div>
                )}
              </dl>
            </CardContent>
          </Card>
        </div>
      </Page.Content>
    </>
  );
}

export default function ShowOffer(props: ShowOfferProps) {
  return (
    <AppLayout>
      <Head title={`${props.offer.name} - Offers`} />
      <Page>
        <ShowOfferContent {...props} />
      </Page>
    </AppLayout>
  );
}