import { useState, useCallback, useRef, useEffect, useMemo } from 'react';
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuCheckboxItem,
  DropdownMenuLabel,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DndContext,
  DragEndEvent,
  DragOverlay,
  DragStartEvent,
  DragOverEvent,
  closestCenter,
  KeyboardSensor,
  PointerSensor,
  useSensor,
  useSensors,
  UniqueIdentifier,
} from '@dnd-kit/core';
import {
  arrayMove,
  SortableContext,
  sortableKeyboardCoordinates,
  verticalListSortingStrategy,
  horizontalListSortingStrategy,
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
  Leaf,
  AlertCircle,
  X,
  Check,
  Undo,
  Redo,
  Settings,
  ArrowLeft,
  Copy,
  Filter,
  Tags,
  Calendar,
  Upload,
  Download,
  History,
  Layers,
  FolderPlus,
  List,
  Grid3x3,
  LayoutGrid,
  LayoutList,
  Coffee,
  Pizza,
  Salad,
  Soup,
  IceCream,
  Wine,
  Beer,
  Cake,
  Utensils,
  Image,
  Type,
  Hash,
  Percent,
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
    category?: string;
    imageUrl?: string;
  };
}

interface MenuSection {
  id: number;
  name: string;
  description?: string;
  icon?: string;
  isActive: boolean;
  isFeatured: boolean;
  isCollapsed?: boolean;
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
  imageUrl?: string;
  tags?: string[];
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

const SECTION_ICONS = {
  appetizers: Pizza,
  mains: Utensils,
  desserts: Cake,
  beverages: Coffee,
  salads: Salad,
  soups: Soup,
  wines: Wine,
  beers: Beer,
  icecream: IceCream,
};

const SECTION_TEMPLATES = [
  { name: 'Appetizers', icon: 'appetizers', description: 'Starters and small plates' },
  { name: 'Main Courses', icon: 'mains', description: 'Primary dishes' },
  { name: 'Desserts', icon: 'desserts', description: 'Sweet endings' },
  { name: 'Beverages', icon: 'beverages', description: 'Drinks and refreshments' },
];

// Item Component for display in sections
function ItemCard({ 
  item, 
  section,
  isSelected,
  onSelect,
  onEdit,
  onDelete,
  onDuplicate,
}: {
  item: MenuItem;
  section: MenuSection;
  isSelected: boolean;
  onSelect: () => void;
  onEdit: () => void;
  onDelete: () => void;
  onDuplicate: () => void;
}) {
  const {
    attributes,
    listeners,
    setNodeRef,
    transform,
    transition,
    isDragging,
  } = useSortable({ id: `item-${item.id}` });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        "group flex items-center gap-3 p-3 bg-white border rounded-lg hover:shadow-sm transition-all",
        isDragging && "opacity-50",
        isSelected && "border-blue-500 bg-blue-50"
      )}
    >
      <Checkbox
        checked={isSelected}
        onCheckedChange={onSelect}
      />
      
      <div
        {...attributes}
        {...listeners}
        className="cursor-move"
      >
        <GripVertical className="h-4 w-4 text-gray-400" />
      </div>

      {item.baseItem?.imageUrl ? (
        <img 
          src={item.baseItem.imageUrl} 
          alt={item.displayName || item.baseItem?.name}
          className="w-12 h-12 object-cover rounded"
        />
      ) : (
        <div className="w-12 h-12 bg-gray-100 rounded flex items-center justify-center">
          <Package className="h-6 w-6 text-gray-400" />
        </div>
      )}

      <div className="flex-1 min-w-0">
        <div className="flex items-center gap-2">
          <span className="font-medium text-sm">
            {item.displayName || item.baseItem?.name}
          </span>
          {item.isFeatured && (
            <Badge variant="secondary" className="text-xs">
              <Star className="mr-1 h-3 w-3" />
              Featured
            </Badge>
          )}
          {item.isNew && (
            <Badge variant="secondary" className="text-xs">
              New
            </Badge>
          )}
        </div>
        {(item.displayDescription || item.baseItem?.description) && (
          <p className="text-xs text-muted-foreground truncate">
            {item.displayDescription || item.baseItem?.description}
          </p>
        )}
      </div>

      <div className="text-right">
        <div className="font-medium text-sm">
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
          <Button variant="ghost" size="icon" className="h-8 w-8 opacity-0 group-hover:opacity-100">
            <MoreVertical className="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onClick={onEdit}>
            <Edit2 className="mr-2 h-4 w-4" />
            Edit
          </DropdownMenuItem>
          <DropdownMenuItem onClick={onDuplicate}>
            <Copy className="mr-2 h-4 w-4" />
            Duplicate
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem onClick={onDelete} className="text-red-600">
            <Trash2 className="mr-2 h-4 w-4" />
            Remove
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}

// Section Component
function SectionCard({ 
  section, 
  onEdit, 
  onDelete,
  onDuplicate,
  onToggleCollapse,
  onAddItem,
  onEditItem,
  onDeleteItem,
  onDuplicateItem,
  selectedItems,
  onSelectItem,
}: {
  section: MenuSection;
  onEdit: () => void;
  onDelete: () => void;
  onDuplicate: () => void;
  onToggleCollapse: () => void;
  onAddItem: (item: AvailableItem) => void;
  onEditItem: (item: MenuItem) => void;
  onDeleteItem: (itemId: number) => void;
  onDuplicateItem: (item: MenuItem) => void;
  selectedItems: Set<number>;
  onSelectItem: (itemId: number) => void;
}) {
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

  const Icon = section.icon ? SECTION_ICONS[section.icon as keyof typeof SECTION_ICONS] : Utensils;

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        "mb-4",
        isDragging && "opacity-50"
      )}
    >
      <Card>
        <CardHeader className="pb-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div
                {...attributes}
                {...listeners}
                className="cursor-move"
              >
                <GripVertical className="h-5 w-5 text-gray-400" />
              </div>
              
              <button
                onClick={onToggleCollapse}
                className="hover:bg-gray-100 rounded p-1"
              >
                {section.isCollapsed ? (
                  <ChevronRight className="h-4 w-4" />
                ) : (
                  <ChevronDown className="h-4 w-4" />
                )}
              </button>

              <Icon className="h-5 w-5 text-gray-600" />
              
              <div>
                <CardTitle className="text-base font-semibold">
                  {section.name}
                </CardTitle>
                {section.description && (
                  <CardDescription className="text-xs">
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
                  <Button variant="ghost" size="icon" className="h-8 w-8">
                    <MoreVertical className="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onClick={onEdit}>
                    <Edit2 className="mr-2 h-4 w-4" />
                    Edit Section
                  </DropdownMenuItem>
                  <DropdownMenuItem onClick={onDuplicate}>
                    <Copy className="mr-2 h-4 w-4" />
                    Duplicate
                  </DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onClick={onDelete} className="text-red-600">
                    <Trash2 className="mr-2 h-4 w-4" />
                    Delete
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          </div>
        </CardHeader>
        
        {!section.isCollapsed && (
          <CardContent>
            {section.items.length === 0 ? (
              <div 
                className="py-8 text-center border-2 border-dashed rounded-lg"
                onDragOver={(e) => {
                  e.preventDefault();
                  e.currentTarget.classList.add('bg-blue-50', 'border-blue-300');
                }}
                onDragLeave={(e) => {
                  e.currentTarget.classList.remove('bg-blue-50', 'border-blue-300');
                }}
                onDrop={(e) => {
                  e.preventDefault();
                  e.currentTarget.classList.remove('bg-blue-50', 'border-blue-300');
                  const itemData = e.dataTransfer.getData('item');
                  if (itemData) {
                    const item = JSON.parse(itemData);
                    onAddItem(item);
                  }
                }}
              >
                <Package className="mx-auto h-8 w-8 text-gray-400 mb-2" />
                <p className="text-sm text-muted-foreground">
                  Drag items here to add them
                </p>
              </div>
            ) : (
              <SortableContext
                items={section.items.map(i => `item-${i.id}`)}
                strategy={verticalListSortingStrategy}
              >
                <div className="space-y-2">
                  {section.items.map((item) => (
                    <ItemCard
                      key={item.id}
                      item={item}
                      section={section}
                      isSelected={selectedItems.has(item.id)}
                      onSelect={() => onSelectItem(item.id)}
                      onEdit={() => onEditItem(item)}
                      onDelete={() => onDeleteItem(item.id)}
                      onDuplicate={() => onDuplicateItem(item)}
                    />
                  ))}
                </div>
              </SortableContext>
            )}
          </CardContent>
        )}
      </Card>
    </div>
  );
}

// Available Item Component
function AvailableItemCard({ 
  item,
  isSelected,
  onSelect,
  onQuickAdd,
}: {
  item: AvailableItem;
  isSelected: boolean;
  onSelect: () => void;
  onQuickAdd: () => void;
}) {
  return (
    <div
      className={cn(
        "group p-3 bg-white border rounded-lg hover:shadow-sm transition-all cursor-move",
        isSelected && "border-blue-500 bg-blue-50"
      )}
      draggable
      onDragStart={(e) => {
        e.dataTransfer.setData('item', JSON.stringify(item));
      }}
    >
      <div className="flex items-start gap-3">
        <Checkbox
          checked={isSelected}
          onCheckedChange={onSelect}
        />
        
        {item.imageUrl ? (
          <img 
            src={item.imageUrl} 
            alt={item.name}
            className="w-10 h-10 object-cover rounded"
          />
        ) : (
          <div className="w-10 h-10 bg-gray-100 rounded flex items-center justify-center flex-shrink-0">
            <Package className="h-5 w-5 text-gray-400" />
          </div>
        )}
        
        <div className="flex-1 min-w-0">
          <div className="font-medium text-sm">{item.name}</div>
          {item.description && (
            <p className="text-xs text-muted-foreground truncate">
              {item.description}
            </p>
          )}
          {item.category && (
            <Badge variant="outline" className="text-xs mt-1">
              {item.category}
            </Badge>
          )}
        </div>
        
        <div className="text-right">
          <div className="font-medium text-sm">
            {formatCurrency(item.price)}
          </div>
          <Button
            size="sm"
            variant="ghost"
            className="h-6 px-2 text-xs opacity-0 group-hover:opacity-100"
            onClick={onQuickAdd}
          >
            <Plus className="h-3 w-3" />
          </Button>
        </div>
      </div>
    </div>
  );
}

export default function MenuBuilder({ menu, structure, availableItems, features }: PageProps) {
  const [sections, setSections] = useState<MenuSection[]>(structure.sections);
  const [selectedItems, setSelectedItems] = useState<Set<number>>(new Set());
  const [selectedAvailableItems, setSelectedAvailableItems] = useState<Set<number>>(new Set());
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editingSection, setEditingSection] = useState<MenuSection | null>(null);
  const [editingItem, setEditingItem] = useState<{ item: MenuItem; sectionId: number } | null>(null);
  const [showSectionDialog, setShowSectionDialog] = useState(false);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Menus', href: '/menus' },
    { title: menu.name, href: `/menus/${menu.id}` },
    { title: 'Manage', current: true },
  ];

  const categories = useMemo(() => {
    const cats = new Set(availableItems.map(item => item.category).filter(Boolean));
    return Array.from(cats);
  }, [availableItems]);

  const filteredItems = useMemo(() => {
    return availableItems.filter(item => {
      const matchesSearch = item.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                           item.description?.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
      return matchesSearch && matchesCategory && item.isActive;
    });
  }, [availableItems, searchQuery, selectedCategory]);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 8,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    })
  );

  const handleSave = async () => {
    setIsSaving(true);
    
    try {
      await router.post(`/menus/${menu.id}/builder/save`, {
        sections: sections,
      }, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          toast.success('Menu saved successfully');
          setHasChanges(false);
        },
        onError: () => {
          toast.error('Failed to save menu');
        },
        onFinish: () => {
          setIsSaving(false);
        },
      });
    } catch (error) {
      setIsSaving(false);
    }
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    
    if (!over) return;
    
    // Handle section reordering
    if (active.id.toString().startsWith('section-') && over.id.toString().startsWith('section-')) {
      const oldIndex = sections.findIndex(s => `section-${s.id}` === active.id);
      const newIndex = sections.findIndex(s => `section-${s.id}` === over.id);
      
      if (oldIndex !== -1 && newIndex !== -1) {
        setSections(arrayMove(sections, oldIndex, newIndex));
        setHasChanges(true);
      }
    }
    
    // Handle item reordering within a section
    if (active.id.toString().startsWith('item-') && over.id.toString().startsWith('item-')) {
      const activeItemId = parseInt(active.id.toString().replace('item-', ''));
      const overItemId = parseInt(over.id.toString().replace('item-', ''));
      
      setSections(prevSections => {
        return prevSections.map(section => {
          const activeItemIndex = section.items.findIndex(i => i.id === activeItemId);
          const overItemIndex = section.items.findIndex(i => i.id === overItemId);
          
          if (activeItemIndex !== -1 && overItemIndex !== -1) {
            const newItems = arrayMove(section.items, activeItemIndex, overItemIndex);
            setHasChanges(true);
            return { ...section, items: newItems };
          }
          
          return section;
        });
      });
    }
  };

  const handleAddSection = (template?: typeof SECTION_TEMPLATES[0]) => {
    const newSection: MenuSection = {
      id: Date.now(),
      name: template?.name || 'New Section',
      description: template?.description || '',
      icon: template?.icon,
      isActive: true,
      isFeatured: false,
      sortOrder: sections.length,
      items: [],
    };
    
    setSections([...sections, newSection]);
    setHasChanges(true);
    setShowSectionDialog(false);
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
        category: item.category,
        imageUrl: item.imageUrl,
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

  const handleBulkAssign = (sectionId: number) => {
    const itemsToAdd = availableItems.filter(item => selectedAvailableItems.has(item.id));
    
    itemsToAdd.forEach(item => {
      handleAddItemToSection(sectionId, item);
    });
    
    setSelectedAvailableItems(new Set());
  };

  const handleSelectAllItems = () => {
    if (selectedAvailableItems.size === filteredItems.length) {
      setSelectedAvailableItems(new Set());
    } else {
      setSelectedAvailableItems(new Set(filteredItems.map(i => i.id)));
    }
  };

  return (
    <AppLayout>
      <Head title={`Menu Builder - ${menu.name}`} />
      <Page>
        <Page.Header
          title="Manage Menu"
          breadcrumbs={breadcrumbs}
          actions={
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" asChild>
                <Link href={`/menus/${menu.id}`}>
                  <ArrowLeft className="mr-2 h-4 w-4" />
                  Back
                </Link>
              </Button>
              <Button variant="outline" size="sm">
                <Eye className="mr-2 h-4 w-4" />
                Preview
              </Button>
              <Button 
                size="sm"
                onClick={handleSave}
                disabled={!hasChanges || isSaving}
              >
                <Save className="mr-2 h-4 w-4" />
                {isSaving ? 'Saving...' : 'Save'}
              </Button>
            </div>
          }
        />
        
        <Page.Content className="p-6">
          <div className="grid grid-cols-12 gap-6">
            {/* Left - Sections */}
            <div className="col-span-7">
              <div className="mb-4 flex items-center justify-between">
                <h3 className="text-lg font-semibold">Menu Sections</h3>
                <Button 
                  size="sm"
                  onClick={() => setShowSectionDialog(true)}
                >
                  <Plus className="mr-2 h-4 w-4" />
                  Add Section
                </Button>
              </div>
              
              <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
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
                          Start by adding a section to your menu
                        </p>
                        <Button onClick={() => setShowSectionDialog(true)}>
                          <Plus className="mr-2 h-4 w-4" />
                          Add First Section
                        </Button>
                      </div>
                    </Card>
                  ) : (
                    sections.map((section) => (
                      <SectionCard
                        key={section.id}
                        section={section}
                        onEdit={() => {
                          setEditingSection(section);
                        }}
                        onDelete={() => {
                          if (confirm('Delete this section?')) {
                            setSections(sections.filter(s => s.id !== section.id));
                            setHasChanges(true);
                          }
                        }}
                        onDuplicate={() => {
                          const duplicate = {
                            ...section,
                            id: Date.now(),
                            name: `${section.name} (Copy)`,
                            items: section.items.map(item => ({
                              ...item,
                              id: Date.now() + Math.random()
                            }))
                          };
                          setSections([...sections, duplicate]);
                          setHasChanges(true);
                        }}
                        onToggleCollapse={() => {
                          setSections(sections.map(s => 
                            s.id === section.id 
                              ? { ...s, isCollapsed: !s.isCollapsed }
                              : s
                          ));
                        }}
                        onAddItem={(item) => handleAddItemToSection(section.id, item)}
                        onEditItem={(item) => {
                          setEditingItem({ item, sectionId: section.id });
                        }}
                        onDeleteItem={(itemId) => {
                          setSections(sections.map(s => {
                            if (s.id === section.id) {
                              return {
                                ...s,
                                items: s.items.filter(i => i.id !== itemId)
                              };
                            }
                            return s;
                          }));
                          setHasChanges(true);
                        }}
                        onDuplicateItem={(item) => {
                          const duplicate = {
                            ...item,
                            id: Date.now(),
                            displayName: item.displayName ? `${item.displayName} (Copy)` : undefined,
                          };
                          setSections(sections.map(s => {
                            if (s.id === section.id) {
                              return {
                                ...s,
                                items: [...s.items, duplicate]
                              };
                            }
                            return s;
                          }));
                          setHasChanges(true);
                        }}
                        selectedItems={selectedItems}
                        onSelectItem={(itemId) => {
                          const newSelected = new Set(selectedItems);
                          if (newSelected.has(itemId)) {
                            newSelected.delete(itemId);
                          } else {
                            newSelected.add(itemId);
                          }
                          setSelectedItems(newSelected);
                        }}
                      />
                    ))
                  )}
                </SortableContext>
              </DndContext>
            </div>
            
            {/* Right - Item Library */}
            <div className="col-span-5">
              <div className="sticky top-6">
                <div className="mb-4">
                  <h3 className="text-lg font-semibold mb-3">Item Library</h3>
                  
                  {/* Search and Filters */}
                  <div className="space-y-2">
                    <div className="relative">
                      <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                      <Input
                        placeholder="Search items..."
                        value={searchQuery}
                        onChange={(e) => setSearchQuery(e.target.value)}
                        className="pl-9"
                      />
                    </div>
                    
                    <div className="flex gap-2">
                      <Select value={selectedCategory} onValueChange={setSelectedCategory}>
                        <SelectTrigger className="flex-1">
                          <SelectValue placeholder="All Categories" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">All Categories</SelectItem>
                          {categories.map(cat => (
                            <SelectItem key={cat} value={cat}>{cat}</SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      
                      {selectedAvailableItems.size > 0 && (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="outline">
                              <Layers className="mr-2 h-4 w-4" />
                              Assign ({selectedAvailableItems.size})
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent>
                            <DropdownMenuLabel>Assign to Section</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            {sections.map(section => (
                              <DropdownMenuItem
                                key={section.id}
                                onClick={() => handleBulkAssign(section.id)}
                              >
                                {section.name}
                              </DropdownMenuItem>
                            ))}
                          </DropdownMenuContent>
                        </DropdownMenu>
                      )}
                    </div>
                  </div>
                </div>
                
                {/* Item Count and Select All */}
                <div className="flex items-center justify-between mb-2">
                  <span className="text-sm text-muted-foreground">
                    {filteredItems.length} items
                  </span>
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleSelectAllItems}
                  >
                    {selectedAvailableItems.size === filteredItems.length ? 'Deselect All' : 'Select All'}
                  </Button>
                </div>
                
                {/* Items List */}
                <ScrollArea className="h-[calc(100vh-20rem)]">
                  <div className="space-y-2">
                    {filteredItems.map((item) => (
                      <AvailableItemCard
                        key={item.id}
                        item={item}
                        isSelected={selectedAvailableItems.has(item.id)}
                        onSelect={() => {
                          const newSelected = new Set(selectedAvailableItems);
                          if (newSelected.has(item.id)) {
                            newSelected.delete(item.id);
                          } else {
                            newSelected.add(item.id);
                          }
                          setSelectedAvailableItems(newSelected);
                        }}
                        onQuickAdd={() => {
                          if (sections.length > 0) {
                            handleAddItemToSection(sections[0].id, item);
                          } else {
                            toast.error('Please add a section first');
                          }
                        }}
                      />
                    ))}
                  </div>
                </ScrollArea>
              </div>
            </div>
          </div>
        </Page.Content>
      </Page>
      
      {/* Add Section Dialog */}
      <Dialog open={showSectionDialog} onOpenChange={setShowSectionDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Section</DialogTitle>
            <DialogDescription>
              Choose a template or create a custom section
            </DialogDescription>
          </DialogHeader>
          
          <div className="grid grid-cols-2 gap-3">
            <Button
              variant="outline"
              className="h-24 flex-col"
              onClick={() => handleAddSection()}
            >
              <FolderPlus className="h-8 w-8 mb-2" />
              <span>Custom Section</span>
            </Button>
            
            {SECTION_TEMPLATES.map(template => {
              const Icon = SECTION_ICONS[template.icon as keyof typeof SECTION_ICONS];
              return (
                <Button
                  key={template.name}
                  variant="outline"
                  className="h-24 flex-col"
                  onClick={() => handleAddSection(template)}
                >
                  <Icon className="h-8 w-8 mb-2" />
                  <span className="text-xs">{template.name}</span>
                </Button>
              );
            })}
          </div>
        </DialogContent>
      </Dialog>
      
      {/* Edit Section Dialog */}
      {editingSection && (
        <Dialog open={!!editingSection} onOpenChange={() => setEditingSection(null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit Section</DialogTitle>
            </DialogHeader>
            
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
            </div>
            
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditingSection(null)}>
                Cancel
              </Button>
              <Button onClick={() => {
                setSections(sections.map(s => 
                  s.id === editingSection.id ? editingSection : s
                ));
                setHasChanges(true);
                setEditingSection(null);
              }}>
                Save Changes
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}
      
      {/* Edit Item Dialog */}
      {editingItem && (
        <Dialog open={!!editingItem} onOpenChange={() => setEditingItem(null)}>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Edit Menu Item</DialogTitle>
            </DialogHeader>
            
            <div className="space-y-4">
              <div>
                <Label htmlFor="item-name">Display Name</Label>
                <Input
                  id="item-name"
                  value={editingItem.item.displayName || editingItem.item.baseItem?.name || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    item: {
                      ...editingItem.item,
                      displayName: e.target.value,
                    }
                  })}
                  placeholder={editingItem.item.baseItem?.name}
                />
              </div>
              
              <div>
                <Label htmlFor="item-description">Display Description</Label>
                <Textarea
                  id="item-description"
                  value={editingItem.item.displayDescription || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    item: {
                      ...editingItem.item,
                      displayDescription: e.target.value,
                    }
                  })}
                  placeholder={editingItem.item.baseItem?.description}
                />
              </div>
              
              <div>
                <Label htmlFor="item-price">Price Override</Label>
                <Input
                  id="item-price"
                  type="number"
                  step="0.01"
                  value={editingItem.item.priceOverride || ''}
                  onChange={(e) => setEditingItem({
                    ...editingItem,
                    item: {
                      ...editingItem.item,
                      priceOverride: e.target.value ? parseFloat(e.target.value) : undefined,
                    }
                  })}
                  placeholder={editingItem.item.baseItem?.price?.toString()}
                />
              </div>
              
              <Separator />
              
              <div className="space-y-2">
                <Label>Item Badges</Label>
                
                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-featured"
                    checked={editingItem.item.isFeatured}
                    onCheckedChange={(checked) => setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        isFeatured: checked,
                      }
                    })}
                  />
                  <Label htmlFor="item-featured">Featured</Label>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-new"
                    checked={editingItem.item.isNew}
                    onCheckedChange={(checked) => setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        isNew: checked,
                      }
                    })}
                  />
                  <Label htmlFor="item-new">New</Label>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-recommended"
                    checked={editingItem.item.isRecommended}
                    onCheckedChange={(checked) => setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        isRecommended: checked,
                      }
                    })}
                  />
                  <Label htmlFor="item-recommended">Recommended</Label>
                </div>
                
                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-seasonal"
                    checked={editingItem.item.isSeasonal}
                    onCheckedChange={(checked) => setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        isSeasonal: checked,
                      }
                    })}
                  />
                  <Label htmlFor="item-seasonal">Seasonal</Label>
                </div>
              </div>
            </div>
            
            <DialogFooter>
              <Button variant="outline" onClick={() => setEditingItem(null)}>
                Cancel
              </Button>
              <Button onClick={() => {
                setSections(sections.map(section => {
                  if (section.id === editingItem.sectionId) {
                    return {
                      ...section,
                      items: section.items.map(i => 
                        i.id === editingItem.item.id ? editingItem.item : i
                      ),
                    };
                  }
                  return section;
                }));
                setHasChanges(true);
                setEditingItem(null);
              }}>
                Save Changes
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </AppLayout>
  );
}