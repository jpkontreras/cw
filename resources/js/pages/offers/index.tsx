import { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { EmptyState } from '@/components/empty-state';
import { ColumnDef } from '@tanstack/react-table';
import {
  Plus,
  MoreHorizontal,
  Edit,
  Copy,
  Trash,
  ToggleLeft,
  ToggleRight,
  BarChart,
  Gift,
  Download,
  FileUp,
  Tag,
  Percent,
  Clock,
  TrendingUp,
} from 'lucide-react';

interface Offer {
  id: number;
  name: string;
  description?: string;
  type: string;
  value: number;
  formattedValue: string;
  code?: string;
  isActive: boolean;
  statusLabel: string;
  startsAt?: string;
  endsAt?: string;
  usageCount: number;
  usageLimit?: number;
  remainingUses?: number;
}

interface OfferStats {
  totalOffers?: number;
  activeOffers?: number;
  expiringSoon?: number;
  mostUsed?: number;
}

interface OfferIndexProps {
  offers: Offer[];
  pagination: any;
  metadata: any;
  filters: {
    search?: string;
    type?: string;
    is_active?: string;
    has_code?: string;
    expiring?: string;
    sort_by?: string;
    sort_direction?: string;
  };
  types: Array<{ value: string; label: string }>;
  stats?: OfferStats;
}

function OffersIndexContent({ offers, pagination, metadata, filters, stats }: OfferIndexProps) {
  const [selectedOffers, setSelectedOffers] = useState<number[]>([]);

  // Check if any filters are active
  const hasActiveFilters = useMemo(() => {
    const filterKeys = ['search', 'type', 'is_active', 'has_code', 'expiring'];
    return filterKeys.some(key => {
      const value = filters[key as keyof typeof filters];
      return value !== undefined && value !== null && value !== '';
    });
  }, [filters]);

  // Quick Filter cards data
  const quickFilterCards = useMemo(
    () => [
      {
        title: 'Total Offers',
        value: stats?.totalOffers || offers.length || 0,
        icon: Gift,
        color: 'text-blue-600',
        indicatorColor: 'bg-blue-500',
        filters: {},
        description: 'View all offers',
      },
      {
        title: 'Active Offers',
        value: stats?.activeOffers || 0,
        icon: Tag,
        color: 'text-green-600',
        indicatorColor: 'bg-green-500',
        filters: { is_active: '1' },
        description: 'Currently active offers',
      },
      {
        title: 'Discount Codes',
        value: stats?.mostUsed || 0,
        icon: Percent,
        color: 'text-purple-600',
        indicatorColor: 'bg-purple-500',
        filters: { has_code: '1' },
        description: 'Offers with discount codes',
      },
      {
        title: 'Expiring Soon',
        value: stats?.expiringSoon || 0,
        icon: Clock,
        color: 'text-orange-600',
        indicatorColor: 'bg-orange-500',
        filters: { expiring: '7days' },
        description: 'Expiring in next 7 days',
      },
    ],
    [stats, offers.length],
  );

  const toggleQuickFilter = (filterCard: typeof quickFilterCards[0]) => {
    const params = new URLSearchParams(window.location.search);
    
    // Check if this filter is currently active
    const isActive = Object.entries(filterCard.filters).every(([key, value]) => {
      const currentValue = params.get(key);
      return currentValue === value;
    });
    
    if (isActive) {
      // Remove the filters if active
      Object.keys(filterCard.filters).forEach((key) => {
        params.delete(key);
      });
    } else {
      // Apply the new filters if not active
      Object.entries(filterCard.filters).forEach(([key, value]) => {
        params.set(key, value);
      });
    }
    
    // Reset to first page
    params.set('page', '1');
    
    // Navigate with the new filters
    router.get(window.location.pathname, Object.fromEntries(params), {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const getStatusBadgeVariant = (status: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (status) {
      case 'Active':
        return 'default';
      case 'Scheduled':
        return 'secondary';
      case 'Expired':
      case 'Exhausted':
        return 'destructive';
      default:
        return 'secondary';
    }
  };

  const getTypeBadgeVariant = (type: string): 'default' | 'secondary' | 'destructive' | 'outline' => {
    switch (type) {
      case 'percentage':
      case 'fixed':
        return 'default';
      case 'buy_x_get_y':
      case 'combo':
        return 'secondary';
      case 'happy_hour':
      case 'early_bird':
        return 'outline';
      default:
        return 'default';
    }
  };

  const handleBulkAction = (action: string) => {
    if (selectedOffers.length === 0) return;

    router.post('/offers/bulk-action', {
      action,
      offer_ids: selectedOffers,
    });
  };

  const columns = useMemo<ColumnDef<Offer>[]>(() => [
    {
      id: 'select',
      header: () => {
        const allSelected = offers.length > 0 && offers.every(offer => selectedOffers.includes(offer.id));
        return (
          <div className="flex w-8 justify-center">
            <Checkbox
              checked={allSelected}
              onCheckedChange={(value) => {
                if (value) {
                  setSelectedOffers(offers.map(offer => offer.id));
                } else {
                  setSelectedOffers([]);
                }
              }}
              aria-label="Select all"
            />
          </div>
        );
      },
      cell: ({ row }) => (
        <div className="flex w-8 justify-center">
          <Checkbox
            checked={selectedOffers.includes(row.original.id)}
            onCheckedChange={(value) => {
              if (value) {
                setSelectedOffers([...selectedOffers, row.original.id]);
              } else {
                setSelectedOffers(selectedOffers.filter(id => id !== row.original.id));
              }
            }}
            aria-label="Select row"
          />
        </div>
      ),
      size: 32,
      enableSorting: false,
      enableHiding: false,
    },
    {
      accessorKey: 'name',
      header: 'Name',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <div>
            <span className="font-medium text-primary">
              {offer.name}
            </span>
            {offer.description && (
              <p className="text-sm text-muted-foreground">{offer.description}</p>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'type',
      header: 'Type',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <Badge variant={getTypeBadgeVariant(offer.type)}>
            {offer.type.replace('_', ' ')}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'value',
      header: 'Value',
      cell: ({ row }) => {
        const offer = row.original;
        return <span className="font-mono">{offer.formattedValue}</span>;
      },
    },
    {
      accessorKey: 'code',
      header: 'Code',
      cell: ({ row }) => {
        const offer = row.original;
        return offer.code ? (
          <code className="px-2 py-1 bg-muted rounded text-xs">
            {offer.code}
          </code>
        ) : (
          <span className="text-muted-foreground">—</span>
        );
      },
    },
    {
      accessorKey: 'statusLabel',
      header: 'Status',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <Badge variant={getStatusBadgeVariant(offer.statusLabel)}>
            {offer.statusLabel}
          </Badge>
        );
      },
    },
    {
      accessorKey: 'usageCount',
      header: 'Usage',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <div className="text-sm">
            <span>{offer.usageCount}</span>
            {offer.usageLimit && (
              <span className="text-muted-foreground">/{offer.usageLimit}</span>
            )}
            {offer.remainingUses !== null && offer.remainingUses !== undefined && (
              <p className="text-xs text-muted-foreground">
                {offer.remainingUses} remaining
              </p>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'validPeriod',
      header: 'Valid Period',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <div className="text-sm">
            {offer.startsAt && (
              <p>From: {new Date(offer.startsAt).toLocaleDateString()}</p>
            )}
            {offer.endsAt && (
              <p>To: {new Date(offer.endsAt).toLocaleDateString()}</p>
            )}
            {!offer.startsAt && !offer.endsAt && (
              <span className="text-muted-foreground">Always</span>
            )}
          </div>
        );
      },
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const offer = row.original;
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="h-8 w-8">
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem
                onClick={(e) => {
                  e.stopPropagation();
                  router.visit(`/offers/${offer.id}/edit`);
                }}
              >
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={(e) => {
                  e.stopPropagation();
                  router.post(`/offers/${offer.id}/duplicate`);
                }}
              >
                <Copy className="mr-2 h-4 w-4" />
                Duplicate
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={(e) => {
                  e.stopPropagation();
                  router.visit(`/offers/${offer.id}/analytics`);
                }}
              >
                <BarChart className="mr-2 h-4 w-4" />
                Analytics
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem
                onClick={(e) => {
                  e.stopPropagation();
                  router.post(
                    `/offers/${offer.id}/${offer.isActive ? 'deactivate' : 'activate'}`
                  );
                }}
              >
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
              <DropdownMenuSeparator />
              <DropdownMenuItem
                className="text-destructive"
                onClick={(e) => {
                  e.stopPropagation();
                  if (confirm('Are you sure you want to delete this offer?')) {
                    router.delete(`/offers/${offer.id}`);
                  }
                }}
              >
                <Trash className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
      size: 32,
    },
  ], [offers, selectedOffers]);

  return (
    <>
      <Page.Header 
        title="Offers"
        subtitle="Manage promotional offers and discounts"
        actions={
          <Link href="/offers/create">
            <Button>
              <Plus className="mr-2 h-4 w-4" />
              Create Offer
            </Button>
          </Link>
        }
      />

      <Page.Content>
        {offers.length === 0 ? (
          <EmptyState
            icon={Gift}
            title={hasActiveFilters ? "No offers found" : "No offers yet"}
            description={
              hasActiveFilters 
                ? "No offers match your current filters. Try adjusting your search criteria or clearing filters."
                : "Create promotional offers to attract customers and boost sales. Your offers will appear here once created."
            }
            actions={
              hasActiveFilters ? (
                <Button 
                  variant="outline" 
                  onClick={() => router.get(window.location.pathname)}
                >
                  Clear Filters
                </Button>
              ) : (
                <Link href="/offers/create">
                  <Button size="lg">
                    <Plus className="mr-2 h-4 w-4" />
                    Create First Offer
                  </Button>
                </Link>
              )
            }
            helpText={
              !hasActiveFilters && (
                <>
                  Learn more about <a href="#" className="text-primary hover:underline">creating effective offers</a>
                </>
              )
            }
          />
        ) : (
          <div className="space-y-6">
            {/* Quick Filters */}
            <div className="space-y-2">
              <div className="flex items-center gap-2">
                <p className="text-xs font-medium text-muted-foreground">Quick Filters</p>
                <div className="h-px flex-1 bg-border" />
              </div>
              <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
                {quickFilterCards.map((card, index) => {
                  const Icon = card.icon;
                  const isActive = Object.entries(card.filters).every(([key, value]) => {
                    const currentValue = filters[key as keyof typeof filters];
                    return currentValue === value;
                  });
                  
                  return (
                    <button
                      key={index}
                      onClick={() => toggleQuickFilter(card)}
                      className={`group relative overflow-hidden rounded-lg border px-3 py-2.5 text-left transition-all hover:shadow-sm ${
                        isActive ? 'border-primary bg-primary/5' : 'hover:border-primary/50'
                      }`}
                      title={isActive ? `Click to remove ${card.title} filter` : card.description}
                    >
                      <div className="flex items-center gap-2.5">
                        <Icon className={`h-4 w-4 ${card.color} flex-shrink-0 transition-transform group-hover:scale-110`} />
                        <div className="min-w-0 flex-1">
                          <p className="truncate text-[11px] leading-none font-medium text-muted-foreground">{card.title}</p>
                          <div className="mt-0.5 flex items-baseline gap-2">
                            <p className="text-lg leading-none font-semibold">{card.value}</p>
                          </div>
                        </div>
                      </div>
                      {isActive && (
                        <div className={`absolute bottom-0 left-0 right-0 h-0.5 ${card.indicatorColor}`} />
                      )}
                    </button>
                  );
                })}
              </div>
            </div>

            {/* Bulk Actions Bar */}
            {selectedOffers.length > 0 && (
              <div className="bg-muted/50 p-4 rounded-lg flex items-center justify-between">
                <span className="text-sm">
                  {selectedOffers.length} offer{selectedOffers.length > 1 ? 's' : ''} selected
                </span>
                <div className="flex gap-2">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleBulkAction('activate')}
                  >
                    Activate
                  </Button>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleBulkAction('deactivate')}
                  >
                    Deactivate
                  </Button>
                  <Button
                    variant="destructive"
                    size="sm"
                    onClick={() => {
                      if (confirm(`Are you sure you want to delete ${selectedOffers.length} offer(s)?`)) {
                        handleBulkAction('delete');
                      }
                    }}
                  >
                    Delete
                  </Button>
                </div>
              </div>
            )}

            {/* Data Table */}
            <InertiaDataTable
              columns={columns}
              data={offers}
              pagination={pagination}
              metadata={metadata}
              onRowClick={(offer) => router.visit(`/offers/${offer.id}`)}
            />
          </div>
        )}
      </Page.Content>
    </>
  );
}

export default function OffersIndex(props: OfferIndexProps) {
  return (
    <AppLayout>
      <Head title="Offers" />
      <Page>
        <OffersIndexContent {...props} />
      </Page>
    </AppLayout>
  );
}