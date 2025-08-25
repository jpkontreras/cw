import React, { useState, useMemo } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { 
  Card, 
  CardContent, 
  CardDescription, 
  CardHeader, 
  CardTitle 
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Tabs,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { 
  Plus,
  Search,
  Edit,
  Trash2,
  ChevronRight,
  ChevronDown,
  Tag,
  Leaf,
  AlertTriangle,
  Globe,
  Flame,
  DollarSign,
  Users,
  Hash,
  MoreVertical,
  Package,
  ShoppingCart,
  Calendar,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { EmptyState } from '@/components/empty-state';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface TaxonomyType {
  value: string;
  label: string;
  description: string;
  isHierarchical: boolean;
  icon?: React.ComponentType<{ className?: string }>;
  color?: string;
}

interface Taxonomy {
  id: number;
  name: string;
  slug: string;
  type: string;
  parentId: number | null;
  metadata?: {
    icon?: string;
    color?: string;
    description?: string;
  };
  sortOrder: number;
  isActive: boolean;
  children?: Taxonomy[];
  itemCount?: number;
}

interface Props {
  taxonomies: Taxonomy[];
  types: TaxonomyType[];
  selectedType?: string;
  stats?: {
    totalTaxonomies: number;
    activeItems: number;
    recentlyAdded: number;
  };
}

const typeIcons: Record<string, React.ComponentType<{ className?: string }>> = {
  'item_category': Package,
  'menu_section': ShoppingCart,
  'dietary_label': Leaf,
  'allergen': AlertTriangle,
  'cuisine_type': Globe,
  'preparation_method': Flame,
  'spice_level': Flame,
  'price_range': DollarSign,
  'customer_segment': Users,
  'general_tag': Tag,
  'seasonal_tag': Calendar,
  'feature_tag': Tag,
};

// Color mapping for taxonomy types (currently unused but may be useful later)
// const typeColors: Record<string, string> = {
//   'item_category': 'bg-blue-100 text-blue-700 border-blue-200',
//   'menu_section': 'bg-purple-100 text-purple-700 border-purple-200',
//   'dietary_label': 'bg-green-100 text-green-700 border-green-200',
//   'allergen': 'bg-red-100 text-red-700 border-red-200',
//   'cuisine_type': 'bg-indigo-100 text-indigo-700 border-indigo-200',
//   'preparation_method': 'bg-orange-100 text-orange-700 border-orange-200',
//   'spice_level': 'bg-yellow-100 text-yellow-700 border-yellow-200',
//   'price_range': 'bg-emerald-100 text-emerald-700 border-emerald-200',
//   'customer_segment': 'bg-pink-100 text-pink-700 border-pink-200',
//   'general_tag': 'bg-gray-100 text-gray-700 border-gray-200',
// };

function TaxonomyTree({ 
  taxonomies, 
  level = 0,
  onEdit,
  onDelete 
}: { 
  taxonomies: Taxonomy[]; 
  level?: number;
  onEdit: (taxonomy: Taxonomy) => void;
  onDelete: (taxonomy: Taxonomy) => void;
}) {
  const [expanded, setExpanded] = useState<Record<number, boolean>>({});

  const toggleExpand = (id: number) => {
    setExpanded(prev => ({ ...prev, [id]: !prev[id] }));
  };

  return (
    <div className={cn("space-y-1", level > 0 && "ml-8")}>
      {taxonomies.map((taxonomy) => (
        <div key={taxonomy.id}>
          <div className={cn(
            "flex items-center justify-between p-3 rounded-lg hover:bg-muted/50 transition-colors",
            level === 0 && "border"
          )}>
            <div className="flex items-center gap-2 flex-1">
              {taxonomy.children && taxonomy.children.length > 0 && (
                <button
                  onClick={() => toggleExpand(taxonomy.id)}
                  className="p-0.5 hover:bg-muted rounded"
                >
                  {expanded[taxonomy.id] ? (
                    <ChevronDown className="h-4 w-4" />
                  ) : (
                    <ChevronRight className="h-4 w-4" />
                  )}
                </button>
              )}
              {!taxonomy.children?.length && <div className="w-5" />}
              
              <div className="flex items-center gap-3">
                {taxonomy.metadata?.icon && (
                  <div 
                    className="w-8 h-8 rounded-md flex items-center justify-center"
                    style={{ backgroundColor: taxonomy.metadata.color + '20' }}
                  >
                    <Hash className="h-4 w-4" style={{ color: taxonomy.metadata.color }} />
                  </div>
                )}
                <div>
                  <div className="font-medium">{taxonomy.name}</div>
                  {taxonomy.metadata?.description && (
                    <div className="text-xs text-muted-foreground">{taxonomy.metadata.description}</div>
                  )}
                </div>
              </div>
              
              <div className="ml-auto flex items-center gap-2">
                {taxonomy.itemCount !== undefined && (
                  <Badge variant="secondary" className="text-xs">
                    {taxonomy.itemCount} items
                  </Badge>
                )}
                <Badge variant={taxonomy.isActive ? "default" : "secondary"}>
                  {taxonomy.isActive ? 'Active' : 'Inactive'}
                </Badge>
              </div>
            </div>
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="sm" className="ml-2">
                  <MoreVertical className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => onEdit(taxonomy)}>
                  <Edit className="mr-2 h-4 w-4" />
                  Edit
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem 
                  onClick={() => onDelete(taxonomy)}
                  className="text-destructive"
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  Delete
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          
          {expanded[taxonomy.id] && taxonomy.children && taxonomy.children.length > 0 && (
            <TaxonomyTree 
              taxonomies={taxonomy.children} 
              level={level + 1}
              onEdit={onEdit}
              onDelete={onDelete}
            />
          )}
        </div>
      ))}
    </div>
  );
}

function TaxonomyList({ 
  taxonomies,
  onEdit,
  onDelete 
}: { 
  taxonomies: Taxonomy[];
  onEdit: (taxonomy: Taxonomy) => void;
  onDelete: (taxonomy: Taxonomy) => void;
}) {
  return (
    <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
      {taxonomies.map((taxonomy) => (
        <Card key={taxonomy.id} className="hover:shadow-md transition-shadow">
          <CardHeader className="pb-3">
            <div className="flex items-start justify-between">
              <div className="flex items-center gap-2">
                {taxonomy.metadata?.icon && (
                  <div 
                    className="w-8 h-8 rounded-md flex items-center justify-center"
                    style={{ backgroundColor: taxonomy.metadata.color + '20' }}
                  >
                    <Hash className="h-4 w-4" style={{ color: taxonomy.metadata.color }} />
                  </div>
                )}
                <div>
                  <CardTitle className="text-base">{taxonomy.name}</CardTitle>
                  <CardDescription className="text-xs mt-1">
                    {taxonomy.slug}
                  </CardDescription>
                </div>
              </div>
              
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => onEdit(taxonomy)}>
                    <Edit className="mr-2 h-4 w-4" />
                    Edit
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem 
                    onClick={() => onDelete(taxonomy)}
                    className="text-destructive"
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </CardHeader>
          {taxonomy.metadata?.description && (
            <CardContent>
              <p className="text-sm text-muted-foreground">
                {taxonomy.metadata.description}
              </p>
              <div className="flex items-center gap-2 mt-3">
                <Badge variant={taxonomy.isActive ? "default" : "secondary"}>
                  {taxonomy.isActive ? 'Active' : 'Inactive'}
                </Badge>
                {taxonomy.itemCount !== undefined && (
                  <Badge variant="outline" className="text-xs">
                    {taxonomy.itemCount} items
                  </Badge>
                )}
              </div>
            </CardContent>
          )}
        </Card>
      ))}
    </div>
  );
}

function CreateTaxonomyDialog({
  open,
  onOpenChange,
  types,
  selectedType,
  onSubmit,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  types: TaxonomyType[];
  selectedType: string;
  onSubmit: (data: {
    name: string;
    type: string;
    description: string;
    icon: string;
    color: string;
    isActive: boolean;
  }) => void;
}) {
  const [formData, setFormData] = useState({
    name: '',
    type: selectedType,
    description: '',
    icon: '',
    color: '#000000',
    isActive: true,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit(formData);
    onOpenChange(false);
    setFormData({
      name: '',
      type: selectedType,
      description: '',
      icon: '',
      color: '#000000',
      isActive: true,
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[500px]">
        <form onSubmit={handleSubmit}>
          <DialogHeader>
            <DialogTitle>Create New Taxonomy</DialogTitle>
            <DialogDescription>
              Add a new category, tag, or classification to your system.
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4 py-4">
            <div className="space-y-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="e.g., Vegetarian, Breakfast, Spicy"
                required
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="type">Type</Label>
              <Select
                value={formData.type}
                onValueChange={(value) => setFormData({ ...formData, type: value })}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {types.map((type) => (
                    <SelectItem key={type.value} value={type.value}>
                      <div className="flex items-center gap-2">
                        {React.createElement(typeIcons[type.value] || Tag, { className: "h-4 w-4" })}
                        {type.label}
                      </div>
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="description">Description (Optional)</Label>
              <Textarea
                id="description"
                value={formData.description}
                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                placeholder="Brief description of this taxonomy"
                rows={3}
              />
            </div>
            
            <div className="grid grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="icon">Icon (Optional)</Label>
                <Input
                  id="icon"
                  value={formData.icon}
                  onChange={(e) => setFormData({ ...formData, icon: e.target.value })}
                  placeholder="e.g., leaf, fire"
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="color">Color</Label>
                <div className="flex gap-2">
                  <Input
                    id="color"
                    type="color"
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                    className="w-16 h-9 p-1"
                  />
                  <Input
                    value={formData.color}
                    onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                    placeholder="#000000"
                    className="flex-1"
                  />
                </div>
              </div>
            </div>
            
            <div className="flex items-center justify-between">
              <Label htmlFor="active" className="flex items-center gap-2">
                Active Status
              </Label>
              <Switch
                id="active"
                checked={formData.isActive}
                onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
              />
            </div>
          </div>
          
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
              Cancel
            </Button>
            <Button type="submit">Create Taxonomy</Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

function TaxonomiesContent({ taxonomies, types, selectedType, stats }: Props) {
  const [currentType, setCurrentType] = useState(selectedType || 'item_category');
  const [searchQuery, setSearchQuery] = useState('');
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [viewMode, setViewMode] = useState<'tree' | 'grid'>('tree');
  
  const currentTypeInfo = types.find(t => t.value === currentType) || types[0];
  const isHierarchical = currentTypeInfo?.isHierarchical || false;
  
  const filteredTaxonomies = useMemo(() => {
    let filtered = taxonomies.filter(t => t.type === currentType);
    
    if (searchQuery) {
      filtered = filtered.filter(t => 
        t.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        t.slug.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }
    
    return filtered;
  }, [taxonomies, currentType, searchQuery]);
  
  const handleCreateTaxonomy = (data: {
    name: string;
    type: string;
    description: string;
    icon: string;
    color: string;
    isActive: boolean;
  }) => {
    router.post('/taxonomies', {
      ...data,
      slug: data.name.toLowerCase().replace(/\s+/g, '-'),
      metadata: {
        description: data.description,
        icon: data.icon,
        color: data.color,
      },
    });
  };
  
  const handleEditTaxonomy = (taxonomy: Taxonomy) => {
    router.get(`/taxonomies/${taxonomy.id}/edit`);
  };
  
  const handleDeleteTaxonomy = (taxonomy: Taxonomy) => {
    if (confirm(`Are you sure you want to delete "${taxonomy.name}"?`)) {
      router.delete(`/taxonomies/${taxonomy.id}`);
    }
  };
  
  const handleTypeChange = (type: string) => {
    setCurrentType(type);
    router.get('/taxonomies', { type }, { preserveState: true });
  };

  return (
    <>
      <Page.Header
        title="Categories & Tags"
        subtitle="Manage taxonomies for items, menus, and other system entities"
        actions={
          <Button onClick={() => setCreateDialogOpen(true)}>
            <Plus className="mr-2 h-4 w-4" />
            Add Taxonomy
          </Button>
        }
      />

      <Page.Content>
        <div className="space-y-6">
          {/* Stats Cards */}
          {stats && (
            <div className="grid gap-4 md:grid-cols-4">
              <Card className="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">Total Taxonomies</CardTitle>
                  <Tag className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.totalTaxonomies || 0}</div>
                  <p className="text-xs text-muted-foreground">Across all types</p>
                </CardContent>
              </Card>
              <Card className="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">Active Items</CardTitle>
                  <Package className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.activeItems || 0}</div>
                  <p className="text-xs text-muted-foreground">Currently in use</p>
                </CardContent>
              </Card>
              <Card className="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">Recently Added</CardTitle>
                  <Plus className="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{stats.recentlyAdded || 0}</div>
                  <p className="text-xs text-muted-foreground">Last 7 days</p>
                </CardContent>
              </Card>
              <Card className="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                  <CardTitle className="text-sm font-medium text-muted-foreground">Current Type</CardTitle>
                  {React.createElement(typeIcons[currentType] || Tag, { className: "h-4 w-4 text-muted-foreground" })}
                </CardHeader>
                <CardContent>
                  <div className="text-2xl font-bold">{filteredTaxonomies.length}</div>
                  <p className="text-xs text-muted-foreground">{currentTypeInfo?.label}</p>
                </CardContent>
              </Card>
            </div>
          )}
          
          {/* Main Content Card */}
          <Card className="flex-1 min-h-0">
            <CardHeader className="border-b">
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-4">
                  <Select value={currentType} onValueChange={handleTypeChange}>
                    <SelectTrigger className="w-[250px]">
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {types.map((type) => {
                        const Icon = typeIcons[type.value] || Tag;
                        return (
                          <SelectItem key={type.value} value={type.value}>
                            <div className="flex items-center gap-2">
                              <Icon className="h-4 w-4" />
                              <span>{type.label}</span>
                            </div>
                          </SelectItem>
                        );
                      })}
                    </SelectContent>
                  </Select>
                  
                  <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                      placeholder="Search taxonomies..."
                      value={searchQuery}
                      onChange={(e) => setSearchQuery(e.target.value)}
                      className="pl-9 w-[300px]"
                    />
                  </div>
                </div>
                
                {isHierarchical && (
                  <Tabs value={viewMode} onValueChange={(v) => setViewMode(v as 'tree' | 'grid')}>
                    <TabsList>
                      <TabsTrigger value="tree">Tree View</TabsTrigger>
                      <TabsTrigger value="grid">Grid View</TabsTrigger>
                    </TabsList>
                  </Tabs>
                )}
              </div>
              
              {currentTypeInfo && (
                <CardDescription className="mt-2">
                  {currentTypeInfo.description}
                </CardDescription>
              )}
            </CardHeader>
            
            <CardContent className="p-6">
              {filteredTaxonomies.length === 0 ? (
                <EmptyState
                  icon={Tag}
                  title="No taxonomies found"
                  description={searchQuery 
                    ? "Try adjusting your search query" 
                    : `No ${currentTypeInfo?.label.toLowerCase()} have been created yet`}
                  actions={
                    <Button onClick={() => setCreateDialogOpen(true)}>
                      <Plus className="mr-2 h-4 w-4" />
                      Create First Taxonomy
                    </Button>
                  }
                />
              ) : (
                <>
                  {isHierarchical && viewMode === 'tree' ? (
                    <TaxonomyTree
                      taxonomies={filteredTaxonomies.filter(t => !t.parentId)}
                      onEdit={handleEditTaxonomy}
                      onDelete={handleDeleteTaxonomy}
                    />
                  ) : (
                    <TaxonomyList
                      taxonomies={filteredTaxonomies}
                      onEdit={handleEditTaxonomy}
                      onDelete={handleDeleteTaxonomy}
                    />
                  )}
                </>
              )}
            </CardContent>
          </Card>
        </div>
        
        <CreateTaxonomyDialog
          open={createDialogOpen}
          onOpenChange={setCreateDialogOpen}
          types={types}
          selectedType={currentType}
          onSubmit={handleCreateTaxonomy}
        />
      </Page.Content>
    </>
  );
}

export default function Taxonomies(props: Props) {
  return (
    <AppLayout>
      <Head title="Categories & Tags" />
      <Page>
        <TaxonomiesContent {...props} />
      </Page>
    </AppLayout>
  );
}