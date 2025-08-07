import { useState, useCallback, useRef } from 'react';
import { Head, router, Link } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  DragStartEvent,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import {
  useSortable,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
  Save,
  Eye,
  Plus,
  GripVertical,
  Edit2,
  Trash2,
  MoreVertical,
  Search,
  ChevronRight,
  ChevronDown,
  Package,
  DollarSign,
  Clock,
  Star,
  TrendingUp,
  Sparkles,
  Sun,
  Leaf,
  AlertCircle,
  X,
  Check,
  Undo,
  Redo,
  Settings,
  ArrowLeft,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { toast } from 'sonner';

interface MenuItem {
  id: number;
  itemId: number;
  displayName?: string;
  displayDescription?: string;
  priceOverride?: number;
  isFeatured: boolean;
  isRecommended: boolean;
  isNew: boolean;
  isSeasonal: boolean;
  sortOrder: number;
  baseItem?: {
    name: string;
    description?: string;
    price: number;
    preparationTime?: number;
  };
}

interface MenuSection {
  id: number;
  name: string;
  description?: string;
  isActive: boolean;
  isFeatured: boolean;
  sortOrder: number;
  items: MenuItem[];
  children?: MenuSection[];
}

interface AvailableItem {
  id: number;
  name: string;
  description?: string;
  price: number;
  category?: string;
  isActive: boolean;
}

interface Menu {
  id: number;
  name: string;
  type: string;
  isActive: boolean;
}

interface PageProps {
  menu: Menu;
  structure: {
    sections: MenuSection[];
  };
  availableItems: AvailableItem[];
  features: {
    nutritionalInfo: boolean;
    dietaryLabels: boolean;
    allergenInfo: boolean;
    seasonalItems: boolean;
    featuredItems: boolean;
    recommendedItems: boolean;
    itemBadges: boolean;
    customImages: boolean;
  };
}

// Sortable Section Component
function SortableSection({ 
  section, 
  onEdit, 
  onDelete,
  onAddItem,
  onEditItem,
  onDeleteItem,
  depth = 0 
}: {
  section: MenuSection;
  onEdit: (section: MenuSection) => void;
  onDelete: (sectionId: number) => void;
  onAddItem: (sectionId: number, item: AvailableItem) => void;
  onEditItem: (sectionId: number, item: MenuItem) => void;
  onDeleteItem: (sectionId: number, itemId: number) => void;
  depth?: number;
}) {
  const [isExpanded, setIsExpanded] = useState(true);
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: `section-${section.id}` });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        "mb-4",
        isDragging && "opacity-50",
        depth > 0 && "ml-8"
      )}
    >
      <Card>
        <CardHeader className="py-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-2">
              <div
                {...attributes}
                {...listeners}
                className="cursor-grab hover:cursor-grabbing"
              >
                <GripVertical className="h-4 w-4 text-gray-400" />
              </div>
              
              <button
                onClick={() => setIsExpanded(!isExpanded)}
                className="hover:bg-gray-100 rounded p-1"
              >
                {isExpanded ? (
                  <ChevronDown className="h-4 w-4" />
                ) : (
                  <ChevronRight className="h-4 w-4" />
                )}
              </button>
              
              <div>
                <CardTitle className="text-base">
                  {section.name}
                  {section.isFeatured && (
                    <Badge variant="secondary" className="ml-2">
                      Featured
                    </Badge>
                  )}
                </CardTitle>
                {section.description && (
                  <CardDescription className="text-sm mt-1">
                    {section.description}
                  </CardDescription>
                )}
              </div>
            </div>
            
            <div className="flex items-center gap-2">
              <Badge variant="outline">
                {section.items.length} items
              </Badge>
              
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" size="sm">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={() => onEdit(section)}>
                    <Edit2 className="mr-2 h-4 w-4" />
                    Edit Section
                  </DropdownMenuItem>
                  <DropdownMenuItem 
                    onClick={() => onDelete(section.id)}
                    className="text-red-600"
                  >
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete Section
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </CardHeader>
        
        {isExpanded && (
          <CardContent className="pt-0">
            {section.items.length === 0 ? (
              <div className="py-8 text-center border-2 border-dashed rounded-lg">
                <Package className="mx-auto h-8 w-8 text-gray-400 mb-2" />
                <p className="text-sm text-muted-foreground">
                  No items in this section
                </p>
                <p className="text-xs text-muted-foreground mt-1">
                  Drag items here from the library
                </p>
              </div>
            ) : (
              <div className="space-y-2">
                {section.items.map((item) => (
                  <div
                    key={item.id}
                    className="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                  >
                    <div className="flex items-center gap-3">
                      <GripVertical className="h-4 w-4 text-gray-400" />
                      <div>
                        <div className="flex items-center gap-2">
                          <span className="font-medium">
                            {item.displayName || item.baseItem?.name}
                          </span>
                          {item.isFeatured && (
                            <Badge variant="secondary" className="text-xs">
                              <Star className="mr-1 h-3 w-3" />
                              Featured
                            </Badge>
                          )}
                          {item.isRecommended && (
                            <Badge variant="secondary" className="text-xs">
                              <TrendingUp className="mr-1 h-3 w-3" />
                              Recommended
                            </Badge>
                          )}
                          {item.isNew && (
                            <Badge variant="secondary" className="text-xs">
                              <Sparkles className="mr-1 h-3 w-3" />
                              New
                            </Badge>
                          )}
                          {item.isSeasonal && (
                            <Badge variant="secondary" className="text-xs">
                              <Leaf className="mr-1 h-3 w-3" />
                              Seasonal
                            </Badge>
                          )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                          {item.displayDescription || item.baseItem?.description}
                        </p>
                      </div>
                    </div>
                    
                    <div className="flex items-center gap-4">
                      <div className="text-right">
                        <div className="font-medium">
                          {formatCurrency(item.priceOverride || item.baseItem?.price || 0)}
                        </div>
                        {item.priceOverride && item.baseItem?.price && (
                          <div className="text-xs text-muted-foreground line-through">
                            {formatCurrency(item.baseItem.price)}
                          </div>
                        )}
                      </div>
                      
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm">
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onClick={() => onEditItem(section.id, item)}>
                            <Edit2 className="mr-2 h-4 w-4" />
                            Edit Item
                          </DropdownMenuItem>
                          <DropdownMenuItem 
                            onClick={() => onDeleteItem(section.id, item.id)}
                            className="text-red-600"
                          >
                            <Trash2 className="mr-2 h-4 w-4" />
                            Remove from Menu
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </div>
                ))}
              </div>
            )}
            
            {section.children && section.children.length > 0 && (
              <div className="mt-4">
                {section.children.map((child) => (
                  <SortableSection
                    key={child.id}
                    section={child}
                    onEdit={onEdit}
                    onDelete={onDelete}
                    onAddItem={onAddItem}
                    onEditItem={onEditItem}
                    onDeleteItem={onDeleteItem}
                    depth={depth + 1}
                  />
                ))}
              </div>
            )}
          </CardContent>
        )}
      </Card>
    </div>
  );
}

function MenuBuilderContent({ menu, structure, availableItems, features }: PageProps) {
  const [sections, setSections] = useState<MenuSection[]>(structure.sections);
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [activeId, setActiveId] = useState<string | null>(null);
  const [editingSection, setEditingSection] = useState<MenuSection | null>(null);
  const [editingItem, setEditingItem] = useState<MenuItem | null>(null);
  const [editingSectionId, setEditingSectionId] = useState<number | null>(null);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Menus', href: '/menus' },
    { title: menu.name, href: `/menus/${menu.id}` },
    { title: 'Builder', current: true },
  ];

  const sensors = useSensors(
    useSensor(PointerSensor),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const categories = Array.from(
    new Set(availableItems.map(item => item.category).filter(Boolean))
  );

  const filteredItems = availableItems.filter(item => {
    const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                         item.description?.toLowerCase().includes(searchQuery.toLowerCase());
    const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
    return matchesSearch && matchesCategory && item.isActive;
  });

  const handleDragStart = (event: DragStartEvent) => {
    setActiveId(event.active.id as string);
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (active.id !== over?.id) {
      setSections((items) => {
        const oldIndex = items.findIndex((i) => `section-${i.id}` === active.id);
        const newIndex = items.findIndex((i) => `section-${i.id}` === over?.id);
        
        if (oldIndex !== -1 && newIndex !== -1) {
          setHasChanges(true);
          return arrayMove(items, oldIndex, newIndex);
        }
        return items;
      });
    }
    
    setActiveId(null);
  };

  const handleSave = async () => {
    setIsSaving(true);
    
    try {
      await router.post(`/menus/${menu.id}/builder/save`, {
        sections: sections,
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Menu structure saved successfully');
          setHasChanges(false);
        },
        onError: () => {
          toast.error('Failed to save menu structure');
        },
        onFinish: () => {
          setIsSaving(false);
        },
      });
    } catch (error) {
      setIsSaving(false);
    }
  };

  const handleAddSection = () => {
    const newSection: MenuSection = {
      id: Date.now(),
      name: 'New Section',
      description: '',
      isActive: true,
      isFeatured: false,
      sortOrder: sections.length,
      items: [],
    };
    
    setSections([...sections, newSection]);
    setHasChanges(true);
    setEditingSection(newSection);
  };

  const handleEditSection = (section: MenuSection) => {
    setEditingSection(section);
  };

  const handleDeleteSection = (sectionId: number) => {
    if (confirm('Are you sure you want to delete this section and all its items?')) {
      setSections(sections.filter(s => s.id !== sectionId));
      setHasChanges(true);
    }
  };

  const handleAddItemToSection = (sectionId: number, item: AvailableItem) => {
    const newItem: MenuItem = {
      id: Date.now(),
      itemId: item.id,
      isFeatured: false,
      isRecommended: false,
      isNew: false,
      isSeasonal: false,
      sortOrder: 0,
      baseItem: {
        name: item.name,
        description: item.description,
        price: item.price,
      },
    };
    
    setSections(sections.map(section => {
      if (section.id === sectionId) {
        return {
          ...section,
          items: [...section.items, newItem],
        };
      }
      return section;
    }));
    
    setHasChanges(true);
    toast.success(`Added ${item.name} to section`);
  };

  const handleEditItem = (sectionId: number, item: MenuItem) => {
    setEditingItem(item);
    setEditingSectionId(sectionId);
  };

  const handleDeleteItem = (sectionId: number, itemId: number) => {
    setSections(sections.map(section => {
      if (section.id === sectionId) {
        return {
          ...section,
          items: section.items.filter(i => i.id !== itemId),
        };
      }
      return section;
    }));
    
    setHasChanges(true);
  };

  return (
    <>
      <Page.Header
        title={`Menu Builder: ${menu.name}`}
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href={`/menus/${menu.id}`}>
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Menu
              </Link>
            </Button>
            <Button
              variant="outline"
              onClick={() => router.visit(`/menus/${menu.id}/preview`)}
            >
              <Eye className="mr-2 h-4 w-4" />
              Preview
            </Button>
            <Button
              onClick={handleSave}
              disabled={!hasChanges || isSaving}
            >
              <Save className="mr-2 h-4 w-4" />
              {isSaving ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        }
      />
      
      <Page.Content className="p-0">
        <div className="flex h-[calc(100vh-8rem)]">
          {/* Left Panel - Menu Structure */}
          <div className="w-1/4 border-r bg-gray-50 p-4">
          <div className="mb-4">
            <h3 className="font-semibold text-sm text-gray-900 mb-2">Menu Structure</h3>
            <Button
              onClick={handleAddSection}
              className="w-full"
              size="sm"
            >
              <Plus className="mr-2 h-4 w-4" />
              Add Section
            </Button>
          </div>
          
          <ScrollArea className="h-[calc(100%-5rem)]">
            <div className="space-y-2">
              {sections.map((section) => (
                <div
                  key={section.id}
                  className="p-2 bg-white rounded border cursor-pointer hover:bg-gray-50"
                  onClick={() => document.getElementById(`section-${section.id}`)?.scrollIntoView()}
                >
                  <div className="font-medium text-sm">{section.name}</div>
                  <div className="text-xs text-muted-foreground">
                    {section.items.length} items
                  </div>
                </div>
              ))}
            </div>
          </ScrollArea>
        </div>

          {/* Center - Canvas */}
          <div className="flex-1 p-6 overflow-auto">
            <div className="max-w-4xl mx-auto">
              <div className="mb-6">
                <p className="text-muted-foreground">
                  Drag and drop to reorder sections and items
                </p>
                {hasChanges && (
                  <Badge variant="outline" className="text-orange-600 border-orange-600 mt-2">
                    <AlertCircle className="mr-1 h-3 w-3" />
                    Unsaved Changes
                  </Badge>
                )}
              </div>
            
            <DndContext
              sensors={sensors}
              collisionDetection={closestCenter}
              onDragStart={handleDragStart}
              onDragEnd={handleDragEnd}
            >
              <SortableContext
                items={sections.map(s => `section-${s.id}`)}
                strategy={verticalListSortingStrategy}
              >
                {sections.length === 0 ? (
                  <Card className="p-12">
                    <div className="text-center">
                      <Package className="mx-auto h-12 w-12 text-gray-400 mb-4" />
                      <h3 className="text-lg font-medium mb-2">No sections yet</h3>
                      <p className="text-muted-foreground mb-4">
                        Start building your menu by adding sections
                      </p>
                      <Button onClick={handleAddSection}>
                        <Plus className="mr-2 h-4 w-4" />
                        Add First Section
                      </Button>
                    </div>
                  </Card>
                ) : (
                  sections.map((section) => (
                    <div key={section.id} id={`section-${section.id}`}>
                      <SortableSection
                        section={section}
                        onEdit={handleEditSection}
                        onDelete={handleDeleteSection}
                        onAddItem={handleAddItemToSection}
                        onEditItem={handleEditItem}
                        onDeleteItem={handleDeleteItem}
                      />
                    </div>
                  ))
                )}
              </SortableContext>
              
              <DragOverlay>
                {activeId ? (
                  <div className="bg-white shadow-lg rounded-lg p-4">
                    Dragging...
                  </div>
                ) : null}
              </DragOverlay>
            </DndContext>
          </div>
        </div>

        {/* Right Panel - Item Library */}
        <div className="w-1/4 border-l bg-gray-50 p-4">
          <div className="mb-4">
            <h3 className="font-semibold text-sm text-gray-900 mb-2">Item Library</h3>
            
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
              <Input
                placeholder="Search items..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-9"
              />
            </div>
            
            {categories.length > 0 && (
              <div className="mt-2">
                <select
                  value={selectedCategory}
                  onChange={(e) => setSelectedCategory(e.target.value)}
                  className="w-full text-sm border rounded-md px-3 py-1"
                >
                  <option value="all">All Categories</option>
                  {categories.map(cat => (
                    <option key={cat} value={cat}>{cat}</option>
                  ))}
                </select>
              </div>
            )}
          </div>
          
          <ScrollArea className="h-[calc(100%-7rem)]">
            <div className="space-y-2">
              {filteredItems.map((item) => (
                <div
                  key={item.id}
                  className="p-3 bg-white rounded border cursor-move hover:shadow-md transition-shadow"
                  draggable
                  onDragStart={(e) => {
                    e.dataTransfer.setData('item', JSON.stringify(item));
                  }}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="font-medium text-sm">{item.name}</div>
                      {item.description && (
                        <div className="text-xs text-muted-foreground mt-1">
                          {item.description}
                        </div>
                      )}
                    </div>
                    <div className="text-sm font-medium">
                      {formatCurrency(item.price)}
                    </div>
                  </div>
                  
                  <div className="mt-2">
                    <Button
                      size="sm"
                      variant="outline"
                      className="w-full text-xs"
                      onClick={() => {
                        if (sections.length > 0) {
                          handleAddItemToSection(sections[0].id, item);
                        } else {
                          toast.error('Please add a section first');
                        }
                      }}
                    >
                      <Plus className="mr-1 h-3 w-3" />
                      Quick Add
                    </Button>
                  </div>
                </div>
              ))}
            </div>
            </ScrollArea>
          </div>
        </div>
      </Page.Content>

      {/* Edit Section Dialog */}
      <Dialog open={!!editingSection} onOpenChange={() => setEditingSection(null)}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Section</DialogTitle>
            <DialogDescription>
              Update section details
            </DialogDescription>
          </DialogHeader>
          
          {editingSection && (
            <div className="space-y-4">
              <div>
                <Label htmlFor="section-name">Name</Label>
                <Input
                  id="section-name"
                  value={editingSection.name}
                  onChange={(e) => setEditingSection({
                    ...editingSection,
                    name: e.target.value,
                  })}
                />
              </div>
              
              <div>
                <Label htmlFor="section-description">Description</Label>
                <Textarea
                  id="section-description"
                  value={editingSection.description || ''}
                  onChange={(e) => setEditingSection({
                    ...editingSection,
                    description: e.target.value,
                  })}
                />
              </div>
              
              <div className="flex items-center space-x-2">
                <Switch
                  id="section-featured"
                  checked={editingSection.isFeatured}
                  onCheckedChange={(checked) => setEditingSection({
                    ...editingSection,
                    isFeatured: checked,
                  })}
                />
                <Label htmlFor="section-featured">Featured Section</Label>
              </div>
            </div>
          )}
          
          <DialogFooter>
            <Button variant="outline" onClick={() => setEditingSection(null)}>
              Cancel
            </Button>
            <Button onClick={() => {
              if (editingSection) {
                setSections(sections.map(s => 
                  s.id === editingSection.id ? editingSection : s
                ));
                setHasChanges(true);
                setEditingSection(null);
              }
            }}>
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Edit Item Dialog */}
      <Dialog open={!!editingItem} onOpenChange={() => {
        setEditingItem(null);
        setEditingSectionId(null);
      }}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Edit Menu Item</DialogTitle>
            <DialogDescription>
              Customize how this item appears on the menu
            </DialogDescription>
          </DialogHeader>
          
          {editingItem && (
            <div className="space-y-4">
              <div>
                <Label htmlFor="item-name">Display Name</Label>
                <Input
                  id="item-name"
                  value={editingItem.displayName || editingItem.baseItem?.name || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    displayName: e.target.value,
                  })}
                  placeholder={editingItem.baseItem?.name}
                />
              </div>
              
              <div>
                <Label htmlFor="item-description">Display Description</Label>
                <Textarea
                  id="item-description"
                  value={editingItem.displayDescription || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    displayDescription: e.target.value,
                  })}
                  placeholder={editingItem.baseItem?.description}
                />
              </div>
              
              <div>
                <Label htmlFor="item-price">Price Override</Label>
                <Input
                  id="item-price"
                  type="number"
                  step="0.01"
                  value={editingItem.priceOverride || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    priceOverride: e.target.value ? parseFloat(e.target.value) : undefined,
                  })}
                  placeholder={editingItem.baseItem?.price?.toString()}
                />
              </div>
              
              <Separator />
              
              <div className="space-y-2">
                <Label>Item Badges</Label>
                
                {features.featuredItems && (
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="item-featured"
                      checked={editingItem.isFeatured}
                      onCheckedChange={(checked) => setEditingItem({
                        ...editingItem,
                        isFeatured: checked,
                      })}
                    />
                    <Label htmlFor="item-featured">Featured</Label>
                  </div>
                )}
                
                {features.recommendedItems && (
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="item-recommended"
                      checked={editingItem.isRecommended}
                      onCheckedChange={(checked) => setEditingItem({
                        ...editingItem,
                        isRecommended: checked,
                      })}
                    />
                    <Label htmlFor="item-recommended">Recommended</Label>
                  </div>
                )}
                
                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-new"
                    checked={editingItem.isNew}
                    onCheckedChange={(checked) => setEditingItem({
                      ...editingItem,
                      isNew: checked,
                    })}
                  />
                  <Label htmlFor="item-new">New</Label>
                </div>
                
                {features.seasonalItems && (
                  <div className="flex items-center space-x-2">
                    <Switch
                      id="item-seasonal"
                      checked={editingItem.isSeasonal}
                      onCheckedChange={(checked) => setEditingItem({
                        ...editingItem,
                        isSeasonal: checked,
                      })}
                    />
                    <Label htmlFor="item-seasonal">Seasonal</Label>
                  </div>
                )}
              </div>
            </div>
          )}
          
          <DialogFooter>
            <Button variant="outline" onClick={() => {
              setEditingItem(null);
              setEditingSectionId(null);
            }}>
              Cancel
            </Button>
            <Button onClick={() => {
              if (editingItem && editingSectionId) {
                setSections(sections.map(section => {
                  if (section.id === editingSectionId) {
                    return {
                      ...section,
                      items: section.items.map(i => 
                        i.id === editingItem.id ? editingItem : i
                      ),
                    };
                  }
                  return section;
                }));
                setHasChanges(true);
                setEditingItem(null);
                setEditingSectionId(null);
              }
            }}>
              Save Changes
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}

export default function MenuBuilder(props: PageProps) {
  return (
    <AppLayout>
      <Head title={`Menu Builder - ${props.menu.name}`} />
      <Page>
        <MenuBuilderContent {...props} />
      </Page>
    </AppLayout>
  );
}