import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import PageLayout from '@/layouts/page-layout';
import { InertiaDataTable } from '@/components/data-table';
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
import { ItemSelector } from '@/components/modules/item/item-selector';
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
  const [createGroupDialogOpen, setCreateGroupDialogOpen] = useState(false);
  const [editingGroup, setEditingGroup] = useState<ModifierGroup | null>(null);
  const [assignItemsDialogOpen, setAssignItemsDialogOpen] = useState(false);
  const [selectedGroup, setSelectedGroup] = useState<ModifierGroup | null>(null);
  const [expandedGroups, setExpandedGroups] = useState<number[]>([]);
  const [modifiers, setModifiers] = useState<Modifier[]>([]);

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
      id: 'expand',
      cell: ({ row }) => {
        const group = row.original;
        const isExpanded = expandedGroups.includes(group.id);
        return (
          <Button
            variant="ghost"
            size="sm"
            className="h-8 w-8 p-0"
            onClick={() => toggleGroupExpansion(group.id)}
          >
            {isExpanded ? (
              <ChevronDown className="h-4 w-4" />
            ) : (
              <ChevronRight className="h-4 w-4" />
            )}
          </Button>
        );
      },
    },
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
    setEditingGroup(group);
    setData({
      name: group.name,
      description: group.description || '',
      display_name: group.display_name || '',
      min_selections: group.min_selections.toString(),
      max_selections: group.max_selections?.toString() || '',
      is_required: group.is_required,
      display_order: group.display_order.toString(),
      is_active: group.is_active,
    });
    setModifiers(group.modifiers);
    setCreateGroupDialogOpen(true);
  };

  const handleDuplicate = (group: ModifierGroup) => {
    setEditingGroup(null);
    setData({
      name: `${group.name} (Copy)`,
      description: group.description || '',
      display_name: group.display_name ? `${group.display_name} (Copy)` : '',
      min_selections: group.min_selections.toString(),
      max_selections: group.max_selections?.toString() || '',
      is_required: group.is_required,
      display_order: group.display_order.toString(),
      is_active: false,
    });
    setModifiers(group.modifiers.map(({ id, ...mod }) => mod));
    setCreateGroupDialogOpen(true);
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

  const addModifier = () => {
    setModifiers([
      ...modifiers,
      {
        name: '',
        price_adjustment: 0,
        is_available: true,
        display_order: modifiers.length,
        sku: '',
        cost: 0,
      },
    ]);
  };

  const updateModifier = (index: number, field: keyof Modifier, value: any) => {
    const updated = [...modifiers];
    updated[index] = { ...updated[index], [field]: value };
    setModifiers(updated);
  };

  const removeModifier = (index: number) => {
    const updated = modifiers.filter((_, i) => i !== index);
    // Reorder remaining modifiers
    updated.forEach((mod, i) => {
      mod.display_order = i;
    });
    setModifiers(updated);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (modifiers.length === 0) {
      alert('Please add at least one modifier option');
      return;
    }
    
    const formData = {
      ...data,
      modifiers,
    };
    
    const url = editingGroup ? `/modifiers/groups/${editingGroup.id}` : '/modifiers/groups';
    const method = editingGroup ? put : post;
    
    method(url, {
      data: formData,
      onSuccess: () => {
        setCreateGroupDialogOpen(false);
        setEditingGroup(null);
        setModifiers([]);
        reset();
      },
    });
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

  return (
    <>
      <Head title="Modifiers" />
      
      <PageLayout>
        <PageLayout.Header
          title="Modifiers"
          subtitle="Manage product customization options and modifier groups"
          actions={
            <PageLayout.Actions>
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
                onClick={() => {
                  setEditingGroup(null);
                  setModifiers([]);
                  reset();
                  setCreateGroupDialogOpen(true);
                }}
              >
                <Plus className="mr-2 h-4 w-4" />
                New Group
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
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
              <Card>
                <CardContent className="p-0">
                  {/* Custom table with expandable rows */}
                  <div className="w-full">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="w-12"></TableHead>
                          <TableHead>Modifier Group</TableHead>
                          <TableHead>Requirements</TableHead>
                          <TableHead>Modifiers</TableHead>
                          <TableHead>Applied To</TableHead>
                          <TableHead>Order</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead></TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {modifier_groups.map((group) => (
                          <>
                            <TableRow key={group.id}>
                              <TableCell>
                                <Button
                                  variant="ghost"
                                  size="sm"
                                  className="h-8 w-8 p-0"
                                  onClick={() => toggleGroupExpansion(group.id)}
                                >
                                  {expandedGroups.includes(group.id) ? (
                                    <ChevronDown className="h-4 w-4" />
                                  ) : (
                                    <ChevronRight className="h-4 w-4" />
                                  )}
                                </Button>
                              </TableCell>
                              <TableCell>
                                <div className="flex flex-col">
                                  <span className="font-medium">{group.name}</span>
                                  {group.display_name && group.display_name !== group.name && (
                                    <span className="text-xs text-muted-foreground">Display: {group.display_name}</span>
                                  )}
                                  {group.description && (
                                    <span className="text-xs text-muted-foreground line-clamp-1">{group.description}</span>
                                  )}
                                </div>
                              </TableCell>
                              <TableCell>
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
                              </TableCell>
                              <TableCell>
                                <div className="flex items-center gap-2">
                                  <Layers className="h-4 w-4 text-muted-foreground" />
                                  <span>{group.modifiers.length} total</span>
                                  {group.modifiers.filter(m => m.is_available).length < group.modifiers.length && (
                                    <span className="text-xs text-muted-foreground">
                                      ({group.modifiers.filter(m => m.is_available).length} active)
                                    </span>
                                  )}
                                </div>
                              </TableCell>
                              <TableCell>
                                <div className="flex items-center gap-2">
                                  <Package className="h-4 w-4 text-muted-foreground" />
                                  <span>{group.items_count} {group.items_count === 1 ? 'item' : 'items'}</span>
                                </div>
                              </TableCell>
                              <TableCell>
                                <div className="flex items-center gap-1">
                                  <Hash className="h-3 w-3 text-muted-foreground" />
                                  <span>{group.display_order}</span>
                                </div>
                              </TableCell>
                              <TableCell>
                                <Badge variant={group.is_active ? 'success' : 'secondary'}>
                                  {group.is_active ? 'Active' : 'Inactive'}
                                </Badge>
                              </TableCell>
                              <TableCell>
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
                              </TableCell>
                            </TableRow>
                            {expandedGroups.includes(group.id) && (
                              <TableRow>
                                <TableCell colSpan={8} className="bg-muted/30 p-4">
                                  <div className="space-y-2">
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
                                        {group.modifiers.map((modifier) => (
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
                                </TableCell>
                              </TableRow>
                            )}
                          </>
                        ))}
                      </TableBody>
                    </Table>
                    
                    {modifier_groups.length === 0 && (
                      <div className="text-center py-12">
                        <Settings className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                        <p className="text-muted-foreground">No modifier groups created yet</p>
                        <Button
                          className="mt-4"
                          onClick={() => {
                            setEditingGroup(null);
                            setModifiers([]);
                            reset();
                            setCreateGroupDialogOpen(true);
                          }}
                        >
                          <Plus className="mr-2 h-4 w-4" />
                          Create First Group
                        </Button>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
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
        </PageLayout.Content>
      </PageLayout>

      {/* Create/Edit Group Dialog */}
      <Dialog open={createGroupDialogOpen} onOpenChange={setCreateGroupDialogOpen}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          <form onSubmit={handleSubmit}>
            <DialogHeader>
              <DialogTitle>
                {editingGroup ? 'Edit Modifier Group' : 'Create Modifier Group'}
              </DialogTitle>
              <DialogDescription>
                Configure options that customers can select to customize items
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-6 my-6">
              {/* Basic Info */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Basic Information</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">
                      Group Name <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="e.g., Size, Toppings, Sauce"
                      className={errors.name ? 'border-destructive' : ''}
                    />
                    {errors.name && (
                      <p className="text-sm text-destructive">{errors.name}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="display_name">Display Name</Label>
                    <Input
                      id="display_name"
                      value={data.display_name}
                      onChange={(e) => setData('display_name', e.target.value)}
                      placeholder="Customer-facing name (optional)"
                    />
                  </div>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="description">Description</Label>
                  <Textarea
                    id="description"
                    value={data.description}
                    onChange={(e) => setData('description', e.target.value)}
                    placeholder="Brief description for staff reference"
                    rows={2}
                  />
                </div>
              </div>

              <Separator />

              {/* Selection Rules */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Selection Rules</h3>
                <div className="grid gap-4 md:grid-cols-3">
                  <div className="space-y-2">
                    <Label htmlFor="min_selections">
                      Min Selections <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="min_selections"
                      type="number"
                      min="0"
                      value={data.min_selections}
                      onChange={(e) => setData('min_selections', e.target.value)}
                      className={errors.min_selections ? 'border-destructive' : ''}
                    />
                    {errors.min_selections && (
                      <p className="text-sm text-destructive">{errors.min_selections}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="max_selections">Max Selections</Label>
                    <Input
                      id="max_selections"
                      type="number"
                      min="0"
                      value={data.max_selections}
                      onChange={(e) => setData('max_selections', e.target.value)}
                      placeholder="No limit"
                    />
                    <p className="text-xs text-muted-foreground">
                      Leave empty for unlimited
                    </p>
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="display_order">Display Order</Label>
                    <Input
                      id="display_order"
                      type="number"
                      value={data.display_order}
                      onChange={(e) => setData('display_order', e.target.value)}
                    />
                  </div>
                </div>
                
                <div className="flex items-center justify-between">
                  <div className="space-y-0.5">
                    <Label htmlFor="is_required">Required</Label>
                    <p className="text-sm text-muted-foreground">
                      Customer must make a selection from this group
                    </p>
                  </div>
                  <Switch
                    id="is_required"
                    checked={data.is_required}
                    onCheckedChange={(checked) => setData('is_required', checked)}
                  />
                </div>
              </div>

              <Separator />

              {/* Modifier Options */}
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-medium">Modifier Options</h3>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addModifier}
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Option
                  </Button>
                </div>
                
                {modifiers.length === 0 ? (
                  <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                      Add modifier options that customers can select. Each option can adjust the item price.
                    </AlertDescription>
                  </Alert>
                ) : (
                  <div className="space-y-3">
                    {modifiers.map((modifier, index) => (
                      <div key={index} className="flex gap-3 items-start p-3 border rounded-lg">
                        <div className="cursor-move p-1">
                          <GripVertical className="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div className="flex-1 grid gap-3 md:grid-cols-4">
                          <div>
                            <Input
                              value={modifier.name}
                              onChange={(e) => updateModifier(index, 'name', e.target.value)}
                              placeholder="Option name"
                            />
                          </div>
                          <div>
                            <div className="relative">
                              <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                $
                              </span>
                              <Input
                                type="number"
                                step="0.01"
                                value={modifier.price_adjustment}
                                onChange={(e) => updateModifier(index, 'price_adjustment', parseFloat(e.target.value) || 0)}
                                placeholder="0.00"
                                className="pl-8"
                              />
                            </div>
                          </div>
                          {features.modifier_inventory && (
                            <div>
                              <Input
                                value={modifier.sku || ''}
                                onChange={(e) => updateModifier(index, 'sku', e.target.value)}
                                placeholder="SKU"
                              />
                            </div>
                          )}
                          <div className="flex items-center gap-2">
                            <Switch
                              checked={modifier.is_available}
                              onCheckedChange={(checked) => updateModifier(index, 'is_available', checked)}
                            />
                            <Label className="text-sm">Available</Label>
                          </div>
                        </div>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => removeModifier(index)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <Separator />

              {/* Status */}
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="is_active">Active</Label>
                  <p className="text-sm text-muted-foreground">
                    Group will be available for selection
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
              <Button type="button" variant="outline" onClick={() => setCreateGroupDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                {editingGroup ? 'Update Group' : 'Create Group'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

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
    </>
  );
}