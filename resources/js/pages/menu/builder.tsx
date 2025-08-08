import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Head, Link, router } from '@inertiajs/react';
import {
  ArrowLeft,
  Beer,
  Cake,
  ChevronDown,
  ChevronRight,
  Coffee,
  Copy,
  Edit2,
  Eye,
  FolderPlus,
  GripVertical,
  IceCream,
  Layers,
  MoreVertical,
  Package,
  PanelRightClose,
  Pizza,
  Plus,
  Salad,
  Save,
  Search,
  Soup,
  Star,
  Trash2,
  Utensils,
  Wine,
} from 'lucide-react';
import { useMemo, useState } from 'react';
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
  isSelected,
  onSelect,
  onEdit,
  onDelete,
  onDuplicate,
}: {
  item: MenuItem;
  isSelected: boolean;
  onSelect: () => void;
  onEdit: () => void;
  onDelete: () => void;
  onDuplicate: () => void;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: `item-${item.id}` });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        'group flex items-center gap-3 rounded-lg border bg-white p-3 transition-all hover:shadow-md',
        isDragging && 'opacity-50',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
      )}
    >
      <Checkbox checked={isSelected} onCheckedChange={onSelect} />

      <div {...attributes} {...listeners} className="cursor-move">
        <GripVertical className="h-4 w-4 text-gray-400" />
      </div>

      {item.baseItem?.imageUrl ? (
        <img src={item.baseItem.imageUrl} alt={item.displayName || item.baseItem?.name} className="h-12 w-12 rounded object-cover" />
      ) : (
        <div className="flex h-12 w-12 items-center justify-center rounded bg-gray-100">
          <Package className="h-6 w-6 text-gray-400" />
        </div>
      )}

      <div className="min-w-0 flex-1">
        <div className="flex items-center gap-2">
          <span className="text-sm font-medium">{item.displayName || item.baseItem?.name}</span>
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
          <p className="truncate text-xs text-muted-foreground">{item.displayDescription || item.baseItem?.description}</p>
        )}
      </div>

      <div className="text-right">
        <div className="text-sm font-medium">{formatCurrency(item.priceOverride || item.baseItem?.price || 0)}</div>
        {item.priceOverride && item.baseItem?.price && (
          <div className="text-xs text-muted-foreground line-through">{formatCurrency(item.baseItem.price)}</div>
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
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: `section-${section.id}` });

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  const Icon = section.icon ? SECTION_ICONS[section.icon as keyof typeof SECTION_ICONS] : Utensils;

  return (
    <div ref={setNodeRef} style={style} className={cn('mb-4', isDragging && 'opacity-50')}>
      <Card className="shadow-sm transition-shadow hover:shadow-md">
        <CardHeader className="bg-white pb-3">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <div {...attributes} {...listeners} className="cursor-move">
                <GripVertical className="h-5 w-5 text-gray-400" />
              </div>

              <button onClick={onToggleCollapse} className="rounded p-1 hover:bg-gray-100">
                {section.isCollapsed ? <ChevronRight className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
              </button>

              <Icon className="h-5 w-5 text-gray-600" />

              <div>
                <CardTitle className="text-base font-semibold">{section.name}</CardTitle>
                {section.description && <CardDescription className="text-xs">{section.description}</CardDescription>}
              </div>
            </div>

            <div className="flex items-center gap-2">
              <Badge variant="outline">{section.items.length} items</Badge>

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
          <CardContent className="pt-0">
            {section.items.length === 0 ? (
              <div
                className="rounded-lg border-2 border-dashed py-8 text-center"
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
                <Package className="mx-auto mb-2 h-8 w-8 text-gray-400" />
                <p className="text-sm text-muted-foreground">Drag items here to add them</p>
              </div>
            ) : (
              <SortableContext items={section.items.map((i) => `item-${i.id}`)} strategy={verticalListSortingStrategy}>
                <div className="space-y-2">
                  {section.items.map((item) => (
                    <ItemCard
                      key={item.id}
                      item={item}
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
        'group cursor-move rounded-lg border bg-white p-3 transition-all hover:shadow-md',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
      )}
      draggable
      onDragStart={(e) => {
        e.dataTransfer.setData('item', JSON.stringify(item));
      }}
    >
      <div className="flex items-start gap-3 py-1">
        <Checkbox checked={isSelected} onCheckedChange={onSelect} />

        {item.imageUrl ? (
          <img src={item.imageUrl} alt={item.name} className="h-12 w-12 rounded object-cover" />
        ) : (
          <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded bg-gray-100">
            <Package className="h-6 w-6 text-gray-400" />
          </div>
        )}

        <div className="min-w-0 flex-1">
          <div className="text-sm font-medium">{item.name}</div>
          {item.description && <p className="mt-0.5 truncate text-xs text-muted-foreground">{item.description}</p>}
          {item.category && (
            <Badge variant="outline" className="mt-1 text-xs">
              {item.category}
            </Badge>
          )}
        </div>

        <div className="text-right">
          <div className="text-sm font-medium">{formatCurrency(item.price)}</div>
          <Button size="sm" variant="ghost" className="h-6 px-2 text-xs opacity-0 group-hover:opacity-100" onClick={onQuickAdd}>
            <Plus className="h-3 w-3" />
          </Button>
        </div>
      </div>
    </div>
  );
}

export default function MenuBuilder({ menu, structure, availableItems }: PageProps) {
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
  const [isLibraryCollapsed, setIsLibraryCollapsed] = useState(false);
  const [isSearchExpanded, setIsSearchExpanded] = useState(false);

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Menus', href: '/menus' },
    { title: menu.name, href: `/menus/${menu.id}` },
    { title: 'Manage', current: true },
  ];

  const categories = useMemo(() => {
    const cats = new Set(availableItems.map((item) => item.category).filter(Boolean));
    return Array.from(cats);
  }, [availableItems]);

  const filteredItems = useMemo(() => {
    return availableItems.filter((item) => {
      const matchesSearch =
        item.name.toLowerCase().includes(searchQuery.toLowerCase()) || item.description?.toLowerCase().includes(searchQuery.toLowerCase());
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
    }),
  );

  const handleSave = async () => {
    setIsSaving(true);

    try {
      await router.post(
        `/menus/${menu.id}/builder/save`,
        {
          sections: sections,
        },
        {
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
        },
      );
    } catch {
      setIsSaving(false);
    }
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    if (!over) return;

    // Handle section reordering
    if (active.id.toString().startsWith('section-') && over.id.toString().startsWith('section-')) {
      const oldIndex = sections.findIndex((s) => `section-${s.id}` === active.id);
      const newIndex = sections.findIndex((s) => `section-${s.id}` === over.id);

      if (oldIndex !== -1 && newIndex !== -1) {
        setSections(arrayMove(sections, oldIndex, newIndex));
        setHasChanges(true);
      }
    }

    // Handle item reordering within a section
    if (active.id.toString().startsWith('item-') && over.id.toString().startsWith('item-')) {
      const activeItemId = parseInt(active.id.toString().replace('item-', ''));
      const overItemId = parseInt(over.id.toString().replace('item-', ''));

      setSections((prevSections) => {
        return prevSections.map((section) => {
          const activeItemIndex = section.items.findIndex((i) => i.id === activeItemId);
          const overItemIndex = section.items.findIndex((i) => i.id === overItemId);

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

  const handleAddSection = (template?: (typeof SECTION_TEMPLATES)[0]) => {
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

    setSections(
      sections.map((section) => {
        if (section.id === sectionId) {
          return {
            ...section,
            items: [...section.items, newItem],
          };
        }
        return section;
      }),
    );

    setHasChanges(true);
    toast.success(`Added ${item.name} to section`);
  };

  const handleBulkAssign = (sectionId: number) => {
    const itemsToAdd = availableItems.filter((item) => selectedAvailableItems.has(item.id));

    itemsToAdd.forEach((item) => {
      handleAddItemToSection(sectionId, item);
    });

    setSelectedAvailableItems(new Set());
  };

  const handleSelectAllItems = () => {
    if (selectedAvailableItems.size === filteredItems.length) {
      setSelectedAvailableItems(new Set());
    } else {
      setSelectedAvailableItems(new Set(filteredItems.map((i) => i.id)));
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
              <Button size="sm" onClick={handleSave} disabled={!hasChanges || isSaving}>
                <Save className="mr-2 h-4 w-4" />
                {isSaving ? 'Saving...' : 'Save'}
              </Button>
            </div>
          }
        />

        <Page.SplitContent
          sidebar={{
            position: 'right',
            defaultSize: 35,
            minSize: 20,
            maxSize: 50,
            collapsed: isLibraryCollapsed,
            onToggle: setIsLibraryCollapsed,
            resizable: true,
            showToggle: false,
            renderExpanded: () => (
              <div className="flex h-full flex-col">
                {/* Header with title and instruction */}
                <div className="border-b bg-white px-4 py-3">
                  <div className="mb-1 flex items-center justify-between">
                    <h3 className="text-lg font-semibold">Item Library</h3>
                    <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => setIsLibraryCollapsed(!isLibraryCollapsed)}>
                      <PanelRightClose className="h-4 w-4" />
                    </Button>
                  </div>
                  <p className="mb-3 text-xs text-muted-foreground">Drag items to sections or use bulk assign</p>

                  {/* Filters and Controls */}
                  <div className="flex items-center gap-2">
                    {!isSearchExpanded ? (
                      <>
                        <Select value={selectedCategory} onValueChange={setSelectedCategory}>
                          <SelectTrigger className="flex-1">
                            <SelectValue placeholder="All Categories" />
                          </SelectTrigger>
                          <SelectContent>
                            <SelectItem value="all">All Categories</SelectItem>
                            {categories.filter(Boolean).map((cat) => (
                              <SelectItem key={cat} value={cat!}>
                                {cat}
                              </SelectItem>
                            ))}
                          </SelectContent>
                        </Select>
                        <Button variant="outline" size="icon" onClick={() => setIsSearchExpanded(true)}>
                          <Search className="h-4 w-4" />
                        </Button>
                      </>
                    ) : (
                      <div className="relative flex-1">
                        <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
                        <Input
                          placeholder="Search items..."
                          value={searchQuery}
                          onChange={(e) => setSearchQuery(e.target.value)}
                          className="pr-9 pl-9"
                          autoFocus
                          onBlur={() => {
                            if (!searchQuery) {
                              setIsSearchExpanded(false);
                            }
                          }}
                        />
                        <Button
                          variant="ghost"
                          size="icon"
                          className="absolute top-1/2 right-1 h-6 w-6 -translate-y-1/2"
                          onClick={() => {
                            setSearchQuery('');
                            setIsSearchExpanded(false);
                          }}
                        >
                          ×
                        </Button>
                      </div>
                    )}

                    {selectedAvailableItems.size > 0 && (
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="outline" size="sm">
                            <Layers className="mr-2 h-4 w-4" />
                            {selectedAvailableItems.size}
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent>
                          <DropdownMenuLabel>Assign to Section</DropdownMenuLabel>
                          <DropdownMenuSeparator />
                          {sections.map((section) => (
                            <DropdownMenuItem key={section.id} onClick={() => handleBulkAssign(section.id)}>
                              {section.name}
                            </DropdownMenuItem>
                          ))}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
                  </div>

                  {/* Item Count and Select All */}
                  <div className="mt-2 flex items-center justify-between text-xs">
                    <span className="text-muted-foreground">{filteredItems.length} items</span>
                    <Button variant="ghost" size="sm" onClick={handleSelectAllItems} className="h-6 px-2 text-xs">
                      {selectedAvailableItems.size === filteredItems.length ? 'Deselect All' : 'Select All'}
                    </Button>
                  </div>
                </div>

                {/* Items List - ScrollArea with full height */}
                <ScrollArea>
                  <div className="space-y-2 p-2">
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
            ),
            renderCollapsed: () => (
              <div className="flex h-full flex-col py-2">
                {/* Expand button at top */}
                <div className="mb-2 px-2 text-center">
                  <Button variant="ghost" size="icon" className="mb-1 h-8 w-8" onClick={() => setIsLibraryCollapsed(false)}>
                    <PanelRightClose className="h-4 w-4 rotate-180" />
                  </Button>
                  <Badge variant="secondary" className="text-xs">
                    {filteredItems.length}
                  </Badge>
                </div>
                <ScrollArea className="flex-1">
                  <div className="px-1">
                    <div className="space-y-1">
                      {filteredItems.map((item) => (
                        <Popover key={item.id}>
                          <PopoverTrigger asChild>
                            <div
                              className={cn(
                                'group relative cursor-move rounded-lg p-1 transition-colors hover:bg-gray-100',
                                selectedAvailableItems.has(item.id) && 'bg-blue-100',
                              )}
                              draggable
                              onDragStart={(e) => {
                                e.dataTransfer.setData('item', JSON.stringify(item));
                              }}
                              onClick={() => {
                                const newSelected = new Set(selectedAvailableItems);
                                if (newSelected.has(item.id)) {
                                  newSelected.delete(item.id);
                                } else {
                                  newSelected.add(item.id);
                                }
                                setSelectedAvailableItems(newSelected);
                              }}
                            >
                              {item.imageUrl ? (
                                <img src={item.imageUrl} alt={item.name} className="mx-auto h-10 w-10 rounded object-cover" />
                              ) : (
                                <div className="mx-auto flex h-10 w-10 items-center justify-center rounded bg-gray-200">
                                  <Package className="h-6 w-6 text-gray-500" />
                                </div>
                              )}
                              {selectedAvailableItems.has(item.id) && <div className="absolute top-0 right-0 h-2 w-2 rounded-full bg-blue-500" />}
                            </div>
                          </PopoverTrigger>
                          <PopoverContent side="left" className="w-64">
                            <div className="space-y-2">
                              <div className="text-sm font-medium">{item.name}</div>
                              {item.description && <p className="text-xs text-muted-foreground">{item.description}</p>}
                              <div className="flex items-center justify-between">
                                <span className="text-sm font-medium">{formatCurrency(item.price)}</span>
                                {item.category && (
                                  <Badge variant="outline" className="text-xs">
                                    {item.category}
                                  </Badge>
                                )}
                              </div>
                              <Button
                                size="sm"
                                className="w-full"
                                onClick={() => {
                                  if (sections.length > 0) {
                                    handleAddItemToSection(sections[0].id, item);
                                  } else {
                                    toast.error('Please add a section first');
                                  }
                                }}
                              >
                                <Plus className="mr-2 h-3 w-3" />
                                Add to Menu
                              </Button>
                            </div>
                          </PopoverContent>
                        </Popover>
                      ))}
                    </div>
                  </div>
                </ScrollArea>
              </div>
            ),
          }}
        >
          {/* Main content - Menu Sections */}
          <div className="flex h-full flex-col bg-gray-50/50">
            <div className="flex-shrink-0 p-6">
              <div className="mb-6 flex items-center justify-between">
                <div>
                  <h3 className="text-lg font-semibold">Menu Sections</h3>
                  <p className="mt-1 text-sm text-muted-foreground">Organize your menu items into sections</p>
                </div>
                <Button size="sm" onClick={() => setShowSectionDialog(true)}>
                  <Plus className="mr-2 h-4 w-4" />
                  Add Section
                </Button>
              </div>
            </div>

            <div className="flex-1 overflow-y-auto px-6 pb-6">
              <DndContext sensors={sensors} collisionDetection={closestCenter} onDragEnd={handleDragEnd}>
                <SortableContext items={sections.map((s) => `section-${s.id}`)} strategy={verticalListSortingStrategy}>
                  {sections.length === 0 ? (
                    <Card className="border-2 border-dashed bg-white/50 p-12">
                      <div className="text-center">
                        <Package className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                        <h3 className="mb-2 text-lg font-medium">No sections yet</h3>
                        <p className="mb-4 text-muted-foreground">Start by adding a section to your menu</p>
                        <Button onClick={() => setShowSectionDialog(true)}>
                          <Plus className="mr-2 h-4 w-4" />
                          Add First Section
                        </Button>
                      </div>
                    </Card>
                  ) : (
                    <div className="space-y-4">
                      {sections.map((section) => (
                        <SectionCard
                          key={section.id}
                          section={section}
                          onEdit={() => {
                            setEditingSection(section);
                          }}
                          onDelete={() => {
                            if (confirm('Delete this section?')) {
                              setSections(sections.filter((s) => s.id !== section.id));
                              setHasChanges(true);
                            }
                          }}
                          onDuplicate={() => {
                            const duplicate = {
                              ...section,
                              id: Date.now(),
                              name: `${section.name} (Copy)`,
                              items: section.items.map((item) => ({
                                ...item,
                                id: Date.now() + Math.random(),
                              })),
                            };
                            setSections([...sections, duplicate]);
                            setHasChanges(true);
                          }}
                          onToggleCollapse={() => {
                            setSections(sections.map((s) => (s.id === section.id ? { ...s, isCollapsed: !s.isCollapsed } : s)));
                          }}
                          onAddItem={(item) => handleAddItemToSection(section.id, item)}
                          onEditItem={(item) => {
                            setEditingItem({ item, sectionId: section.id });
                          }}
                          onDeleteItem={(itemId) => {
                            setSections(
                              sections.map((s) => {
                                if (s.id === section.id) {
                                  return {
                                    ...s,
                                    items: s.items.filter((i) => i.id !== itemId),
                                  };
                                }
                                return s;
                              }),
                            );
                            setHasChanges(true);
                          }}
                          onDuplicateItem={(item) => {
                            const duplicate = {
                              ...item,
                              id: Date.now(),
                              displayName: item.displayName ? `${item.displayName} (Copy)` : undefined,
                            };
                            setSections(
                              sections.map((s) => {
                                if (s.id === section.id) {
                                  return {
                                    ...s,
                                    items: [...s.items, duplicate],
                                  };
                                }
                                return s;
                              }),
                            );
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
                      ))}
                    </div>
                  )}
                </SortableContext>
              </DndContext>
            </div>
          </div>
        </Page.SplitContent>
      </Page>

      {/* Add Section Dialog */}
      <Dialog open={showSectionDialog} onOpenChange={setShowSectionDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Add Section</DialogTitle>
            <DialogDescription>Choose a template or create a custom section</DialogDescription>
          </DialogHeader>

          <div className="grid grid-cols-2 gap-3">
            <Button variant="outline" className="h-24 flex-col" onClick={() => handleAddSection()}>
              <FolderPlus className="mb-2 h-8 w-8" />
              <span>Custom Section</span>
            </Button>

            {SECTION_TEMPLATES.map((template) => {
              const Icon = SECTION_ICONS[template.icon as keyof typeof SECTION_ICONS];
              return (
                <Button key={template.name} variant="outline" className="h-24 flex-col" onClick={() => handleAddSection(template)}>
                  <Icon className="mb-2 h-8 w-8" />
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
                  onChange={(e) =>
                    setEditingSection({
                      ...editingSection,
                      name: e.target.value,
                    })
                  }
                />
              </div>

              <div>
                <Label htmlFor="section-description">Description</Label>
                <Textarea
                  id="section-description"
                  value={editingSection.description || ''}
                  onChange={(e) =>
                    setEditingSection({
                      ...editingSection,
                      description: e.target.value,
                    })
                  }
                />
              </div>
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setEditingSection(null)}>
                Cancel
              </Button>
              <Button
                onClick={() => {
                  setSections(sections.map((s) => (s.id === editingSection.id ? editingSection : s)));
                  setHasChanges(true);
                  setEditingSection(null);
                }}
              >
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
                  onChange={(e) =>
                    setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        displayName: e.target.value,
                      },
                    })
                  }
                  placeholder={editingItem.item.baseItem?.name}
                />
              </div>

              <div>
                <Label htmlFor="item-description">Display Description</Label>
                <Textarea
                  id="item-description"
                  value={editingItem.item.displayDescription || ''}
                  onChange={(e) =>
                    setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        displayDescription: e.target.value,
                      },
                    })
                  }
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
                  onChange={(e) =>
                    setEditingItem({
                      ...editingItem,
                      item: {
                        ...editingItem.item,
                        priceOverride: e.target.value ? parseFloat(e.target.value) : undefined,
                      },
                    })
                  }
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
                    onCheckedChange={(checked) =>
                      setEditingItem({
                        ...editingItem,
                        item: {
                          ...editingItem.item,
                          isFeatured: checked,
                        },
                      })
                    }
                  />
                  <Label htmlFor="item-featured">Featured</Label>
                </div>

                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-new"
                    checked={editingItem.item.isNew}
                    onCheckedChange={(checked) =>
                      setEditingItem({
                        ...editingItem,
                        item: {
                          ...editingItem.item,
                          isNew: checked,
                        },
                      })
                    }
                  />
                  <Label htmlFor="item-new">New</Label>
                </div>

                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-recommended"
                    checked={editingItem.item.isRecommended}
                    onCheckedChange={(checked) =>
                      setEditingItem({
                        ...editingItem,
                        item: {
                          ...editingItem.item,
                          isRecommended: checked,
                        },
                      })
                    }
                  />
                  <Label htmlFor="item-recommended">Recommended</Label>
                </div>

                <div className="flex items-center space-x-2">
                  <Switch
                    id="item-seasonal"
                    checked={editingItem.item.isSeasonal}
                    onCheckedChange={(checked) =>
                      setEditingItem({
                        ...editingItem,
                        item: {
                          ...editingItem.item,
                          isSeasonal: checked,
                        },
                      })
                    }
                  />
                  <Label htmlFor="item-seasonal">Seasonal</Label>
                </div>
              </div>
            </div>

            <DialogFooter>
              <Button variant="outline" onClick={() => setEditingItem(null)}>
                Cancel
              </Button>
              <Button
                onClick={() => {
                  setSections(
                    sections.map((section) => {
                      if (section.id === editingItem.sectionId) {
                        return {
                          ...section,
                          items: section.items.map((i) => (i.id === editingItem.item.id ? editingItem.item : i)),
                        };
                      }
                      return section;
                    }),
                  );
                  setHasChanges(true);
                  setEditingItem(null);
                }}
              >
                Save Changes
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      )}
    </AppLayout>
  );
}
