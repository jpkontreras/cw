import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { InertiaDataTable } from '@/modules/data-table';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ItemSelector } from '@/modules/item';
import { 
  Settings,
  Plus,
  MoreHorizontal,
  AlertCircle,
  ChevronDown,
  ChevronRight,
  Edit,
  Copy,
  Trash,
  Package,
  DollarSign,
  Tag,
  Layers,
  CheckCircle,
  XCircle,
  Info,
  Power,
  X,
  GripVertical,
  Hash,
  FileText
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';

interface Modifier {
  id?: number;
  name: string;
  price_adjustment: number;
  is_available: boolean;
  display_order: number;
  sku?: string;
  cost?: number;
}

interface ModifierGroup {
  id: number;
  name: string;
  description: string | null;
  display_name: string | null;
  min_selections: number;
  max_selections: number | null;
  is_required: boolean;
  display_order: number;
  is_active: boolean;
  modifiers: Modifier[];
  items_count: number;
  created_at: string;
  updated_at: string;
}

interface PageProps {
  modifier_groups: ModifierGroup[];
  pagination: any;
  metadata: any;
  items: Array<{ id: number; name: string; allow_modifiers: boolean }>;
  stats: {
    total_groups: number;
    active_groups: number;
    total_modifiers: number;
    avg_modifiers_per_group: number;
  };
  popular_modifiers: Array<{
    id: number;
    name: string;
    group_name: string;
    times_selected: number;
    revenue_generated: number;
  }>;
  features: {
    modifier_inventory: boolean;
    modifier_pricing: boolean;
    conditional_modifiers: boolean;
    modifier_images: boolean;
  };
}

export default function ModifiersIndex({ 
  modifier_groups, 
  pagination, 
  metadata,
  items,
  stats,
  popular_modifiers,
  features
}: PageProps) {
  // Modal states removed - using separate pages for create/edit
  const [assignItemsDialogOpen, setAssignItemsDialogOpen] = useState(false);
  const [selectedGroup, setSelectedGroup] = useState<ModifierGroup | null>(null);
  const [expandedGroups, setExpandedGroups] = useState<number[]>([]);
  // Modifiers state removed - handled in create/edit pages

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    description: '',
    display_name: '',
    min_selections: '0',
    max_selections: '',
    is_required: false,
    display_order: '0',
    is_active: true,
  });

  const { data: assignData, setData: setAssignData, post: postAssign, processing: assignProcessing } = useForm({
    modifier_group_id: '',
    item_ids: [] as number[],
  });

  const columns: ColumnDef<ModifierGroup>[] = [
    {
      accessorKey: 'name',
      header: 'Modifier Group',
      cell: ({ row }) => {
        const group = row.original;
        return (
          <div className="flex flex-col">
            <span className="font-medium">{group.name}</span>
            {group.display_name && group.display_name !== group.name && (
              <span className="text-xs text-muted-foreground">Display: {group.display_name}</span>
            )}
            {group.description && (
              <span className="text-xs text-muted-foreground line-clamp-1">{group.description}</span>
            )}
          </div>
        );
      },
    },
    {
      id: 'requirements',
      header: 'Requirements',
      cell: ({ row }) => {
        const group = row.original;
        return (
          <div className="space-y-1">
            {group.is_required && (
              <Badge variant="secondary" className="text-xs">
                Required
              </Badge>
            )}
            <div className="text-sm">
              {group.min_selections === group.max_selections && group.max_selections ? (
                <span>Select exactly {group.min_selections}</span>
              ) : (
                <span>
                  Select {group.min_selections}
                  {group.max_selections ? ` to ${group.max_selections}` : '+'}
                </span>
              )}
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'modifiers',
      header: 'Modifiers',
      cell: ({ row }) => {
        const group = row.original;
        const activeCount = group.modifiers.filter(m => m.is_available).length;
        return (
          <div className="flex items-center gap-2">
            <Layers className="h-4 w-4 text-muted-foreground" />
            <span>{group.modifiers.length} total</span>
            {activeCount < group.modifiers.length && (
              <span className="text-xs text-muted-foreground">({activeCount} active)</span>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'items_count',
      header: 'Applied To',
      cell: ({ row }) => {
        const count = row.original.items_count;
        return (
          <div className="flex items-center gap-2">
            <Package className="h-4 w-4 text-muted-foreground" />
            <span>{count} {count === 1 ? 'item' : 'items'}</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'display_order',
      header: 'Order',
      cell: ({ row }) => (
        <div className="flex items-center gap-1">
          <Hash className="h-3 w-3 text-muted-foreground" />
          <span>{row.original.display_order}</span>
        </div>
      ),
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.is_active ? 'success' : 'secondary'}>
          {row.original.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const group = row.original;
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleEdit(group)}>
                <Edit className="mr-2 h-4 w-4" />
                Edit Group
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleAssignItems(group)}>
                <Package className="mr-2 h-4 w-4" />
                Assign to Items
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleDuplicate(group)}>
                <Copy className="mr-2 h-4 w-4" />
                Duplicate
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleToggleActive(group)}>
                <Power className="mr-2 h-4 w-4" />
                {group.is_active ? 'Deactivate' : 'Activate'}
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                className="text-destructive"
                onClick={() => handleDelete(group.id)}
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

  const toggleGroupExpansion = (groupId: number) => {
    setExpandedGroups(prev => 
      prev.includes(groupId) 
        ? prev.filter(id => id !== groupId)
        : [...prev, groupId]
    );
  };

  const handleEdit = (group: ModifierGroup) => {
    router.visit(`/modifiers/${group.id}/edit`);
  };

  const handleDuplicate = (group: ModifierGroup) => {
    // Redirect to create page with pre-filled data
    router.visit('/modifiers/create', {
      data: {
        duplicate_from: group.id
      }
    });
  };

  const handleAssignItems = (group: ModifierGroup) => {
    setSelectedGroup(group);
    setAssignData('modifier_group_id', group.id.toString());
    setAssignItemsDialogOpen(true);
  };

  const handleToggleActive = (group: ModifierGroup) => {
    router.put(`/modifiers/groups/${group.id}/toggle-active`);
  };

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this modifier group? This will also remove it from all items.')) {
      router.delete(`/modifiers/groups/${id}`);
    }
  };


  const handleAssignSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    postAssign('/modifiers/groups/assign-items', {
      onSuccess: () => {
        setAssignItemsDialogOpen(false);
        setAssignData('item_ids', []);
      },
    });
  };

  const statsCards = [
    {
      title: 'Total Groups',
      value: stats.total_groups,
      icon: Layers,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: 'Active Groups',
      value: stats.active_groups,
      icon: CheckCircle,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: 'Total Modifiers',
      value: stats.total_modifiers,
      icon: Settings,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
    {
      title: 'Avg per Group',
      value: stats.avg_modifiers_per_group.toFixed(1),
      icon: Tag,
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
    },
  ];

  // Check if modifier groups are empty
  const isEmpty = modifier_groups.length === 0;

  return (
    <AppLayout>
      <Head title="Modifiers" />
      
      <Page>
        <Page.Header
          title="Modifiers"
          subtitle="Manage product customization options and modifier groups"
          actions={
            !isEmpty && (
              <Page.Actions>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => router.visit('/modifiers/bulk-assign')}
                >
                  <Package className="mr-2 h-4 w-4" />
                  Bulk Assign
                </Button>
                <Button
                  size="sm"
                  onClick={() => router.visit('/modifiers/create')}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  New Group
                </Button>
              </Page.Actions>
            )
          }
        />
        
        <Page.Content>
          {isEmpty ? (
            <EmptyState
              icon={Settings}
              title="No modifier groups created yet"
              description="Create modifier groups to allow customers to customize their orders with add-ons, size options, and special preferences."
              actions={
                <Button onClick={() => router.visit('/modifiers/create')}>
                  <Plus className="mr-2 h-4 w-4" />
                  Create First Group
                </Button>
              }
              helpText={
                <>
                  Read our <a href="#" className="text-primary hover:underline">modifiers guide</a> to get started
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
                      </CardContent>
                    </Card>
                  );
                })}
              </div>

              <Tabs defaultValue="groups" className="w-full">
            <TabsList>
              <TabsTrigger value="groups">Modifier Groups</TabsTrigger>
              <TabsTrigger value="popular">Popular Modifiers</TabsTrigger>
            </TabsList>

            <TabsContent value="groups" className="mt-6">
              <InertiaDataTable
                  columns={columns}
                  data={modifier_groups}
                  pagination={pagination}
                  filters={metadata?.filters}
                  expandable={{
                    isExpanded: (row) => expandedGroups.includes(row.original.id),
                    onToggle: (row) => toggleGroupExpansion(row.original.id),
                    renderExpanded: (row) => (
                      <div className="p-4 bg-muted/30">
                        <h4 className="font-medium text-sm mb-3">Modifier Options</h4>
                        <Table>
                          <TableHeader>
                            <TableRow>
                              <TableHead>Name</TableHead>
                              <TableHead>Price Adjustment</TableHead>
                              {features.modifier_inventory && <TableHead>SKU</TableHead>}
                              {features.modifier_pricing && <TableHead>Cost</TableHead>}
                              <TableHead>Status</TableHead>
                            </TableRow>
                          </TableHeader>
                          <TableBody>
                            {row.original.modifiers.map((modifier) => (
                              <TableRow key={modifier.id}>
                                <TableCell>{modifier.name}</TableCell>
                                <TableCell>
                                  {modifier.price_adjustment > 0 && '+'}
                                  {formatCurrency(modifier.price_adjustment)}
                                </TableCell>
                                {features.modifier_inventory && (
                                  <TableCell>
                                    {modifier.sku || '—'}
                                  </TableCell>
                                )}
                                {features.modifier_pricing && (
                                  <TableCell>
                                    {modifier.cost ? formatCurrency(modifier.cost) : '—'}
                                  </TableCell>
                                )}
                                <TableCell>
                                  <Badge variant={modifier.is_available ? 'success' : 'secondary'}>
                                    {modifier.is_available ? 'Available' : 'Unavailable'}
                                  </Badge>
                                </TableCell>
                              </TableRow>
                            ))}
                          </TableBody>
                        </Table>
                      </div>
                    ),
                  }}
                />
            </TabsContent>

            <TabsContent value="popular" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Popular Modifiers</CardTitle>
                  <CardDescription>
                    Most frequently selected modifier options
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {popular_modifiers.length > 0 ? (
                    <div className="space-y-4">
                      {popular_modifiers.map((modifier, index) => (
                        <div key={modifier.id} className="flex items-center justify-between p-4 border rounded-lg">
                          <div className="flex items-center gap-4">
                            <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-medium">
                              {index + 1}
                            </div>
                            <div>
                              <h4 className="font-medium">{modifier.name}</h4>
                              <p className="text-sm text-muted-foreground">
                                From {modifier.group_name}
                              </p>
                            </div>
                          </div>
                          <div className="text-right">
                            <div className="font-medium">
                              {modifier.times_selected} selections
                            </div>
                            <p className="text-sm text-muted-foreground">
                              {formatCurrency(modifier.revenue_generated)} revenue
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <Tag className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                      <p className="text-muted-foreground">No modifier usage data available yet</p>
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

      {/* Create/Edit Group Dialog removed - using separate pages */}

      {/* Assign Items Dialog */}
      <Dialog open={assignItemsDialogOpen} onOpenChange={setAssignItemsDialogOpen}>
        <DialogContent>
          <form onSubmit={handleAssignSubmit}>
            <DialogHeader>
              <DialogTitle>Assign Modifier Group to Items</DialogTitle>
              <DialogDescription>
                {selectedGroup && `Select items that should have the "${selectedGroup.name}" modifier group`}
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-4 my-4">
              <Alert>
                <Info className="h-4 w-4" />
                <AlertDescription>
                  Only items that allow modifiers are shown. This will add the modifier group to selected items.
                </AlertDescription>
              </Alert>
              
              <div className="space-y-2">
                <Label>Select Items</Label>
                <div className="border rounded-lg max-h-64 overflow-y-auto p-4">
                  <div className="space-y-2">
                    {items.filter(item => item.allow_modifiers).map((item) => (
                      <label key={item.id} className="flex items-center space-x-2 cursor-pointer">
                        <input
                          type="checkbox"
                          checked={assignData.item_ids.includes(item.id)}
                          onChange={(e) => {
                            if (e.target.checked) {
                              setAssignData('item_ids', [...assignData.item_ids, item.id]);
                            } else {
                              setAssignData('item_ids', assignData.item_ids.filter(id => id !== item.id));
                            }
                          }}
                          className="rounded border-gray-300"
                        />
                        <span>{item.name}</span>
                      </label>
                    ))}
                  </div>
                </div>
              </div>
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAssignItemsDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={assignProcessing}>
                Assign to {assignData.item_ids.length} Items
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </AppLayout>
  );
}