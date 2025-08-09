import { EmptyState } from '@/components/empty-state';
import { FlashMessages } from '@/components/flash-messages';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ValidationErrors } from '@/components/validation-errors';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import {
  AddSectionSheet,
  EditItemDialog,
  EditSectionDialog,
  ItemLibrarySidebar,
  MenuSectionCard,
  type AvailableItem,
  type MenuBuilderPageProps,
  type MenuItem,
  type MenuSection,
} from '@/modules/menu';
import {
  closestCenter,
  DndContext,
  DragEndEvent,
  DragOverEvent,
  DragOverlay,
  DragStartEvent,
  KeyboardSensor,
  PointerSensor,
  pointerWithin,
  rectIntersection,
  UniqueIdentifier,
  useSensor,
  useSensors,
} from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ChefHat, Eye, FileText, Layers, Package, Plus, Save, SquarePen } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function MenuBuilder({ menu, allMenus, structure, availableItems }: MenuBuilderPageProps) {
  const [sections, setSections] = useState<MenuSection[]>(structure.sections);
  const [selectedAvailableItems, setSelectedAvailableItems] = useState<Set<number>>(new Set());
  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editingSection, setEditingSection] = useState<MenuSection | null>(null);
  const [editingItem, setEditingItem] = useState<{ item: MenuItem; sectionId: number } | null>(null);
  const [showSectionDialog, setShowSectionDialog] = useState(false);
  const [, setIsLibraryCollapsed] = useState(false);
  const [activeId, setActiveId] = useState<UniqueIdentifier | null>(null);
  const [activeDraggedItem, setActiveDraggedItem] = useState<AvailableItem | null>(null);
  const [activeDraggedItems, setActiveDraggedItems] = useState<AvailableItem[]>([]);
  const [overId, setOverId] = useState<UniqueIdentifier | null>(null);
  const [droppedSuccessfully, setDroppedSuccessfully] = useState(false);

  const sensors = useSensors(
    useSensor(PointerSensor, {
      activationConstraint: {
        distance: 10, // Increased from 5 to allow scrolling
        delay: 150, // Increased delay to distinguish from scrolling
        tolerance: 5,
      },
    }),
    useSensor(KeyboardSensor, {
      coordinateGetter: sortableKeyboardCoordinates,
    }),
  );

  const handleSave = async () => {
    if (!menu) {
      toast.error('Please select a menu first');
      return;
    }

    setIsSaving(true);

    // Prepare sections data for submission
    const sectionsData = sections.map((section) => ({
      id: section.id,
      name: section.name,
      description: section.description || null,
      icon: section.icon || null,
      isActive: section.isActive,
      isFeatured: section.isFeatured,
      isCollapsed: section.isCollapsed || false,
      sortOrder: section.sortOrder,
      items: section.items.map((item) => ({
        id: item.id,
        itemId: item.itemId,
        displayName: item.displayName || null,
        displayDescription: item.displayDescription || null,
        priceOverride: item.priceOverride || null,
        isFeatured: item.isFeatured,
        isRecommended: item.isRecommended,
        isNew: item.isNew,
        isSeasonal: item.isSeasonal,
        sortOrder: item.sortOrder,
      })),
      children: section.children
        ? section.children.map((child) => ({
            ...child,
            children: [], // Flatten for now, handle recursion on backend
          }))
        : [],
    }));

    try {
      await router.post(
        `/menu/${menu.id}/builder/save`,
        {
          sections: sectionsData as any, // Type assertion for Inertia compatibility
        },
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            toast.success('Menu saved successfully');
            setHasChanges(false);
          },
          onError: (errors) => {
            // Handle validation errors
            console.error('Save errors:', errors);

            // Check if errors is an object with field-specific errors
            if (typeof errors === 'object' && errors !== null) {
              // Display each validation error
              Object.entries(errors).forEach(([field, messages]) => {
                if (Array.isArray(messages)) {
                  messages.forEach((message) => {
                    toast.error(`${field}: ${message}`);
                  });
                } else if (typeof messages === 'string') {
                  toast.error(`${field}: ${messages}`);
                }
              });
            } else {
              // Fallback for generic error
              toast.error('Failed to save menu. Please check your input and try again.');
            }
          },
          onFinish: () => {
            setIsSaving(false);
          },
        },
      );
    } catch (error) {
      console.error('Unexpected error during save:', error);
      toast.error('An unexpected error occurred while saving');
      setIsSaving(false);
    }
  };

  const handleMenuChange = (menuId: string) => {
    if (hasChanges) {
      if (!confirm('You have unsaved changes. Do you want to continue?')) {
        return;
      }
    }

    // Navigate to the selected menu's builder
    router.visit(`/menu/${menuId}/builder`, {
      preserveState: false,
      preserveScroll: false,
    });
  };

  const handleDragStart = (event: DragStartEvent) => {
    const { active } = event;
    setActiveId(active.id);
    setDroppedSuccessfully(false); // Reset on new drag

    // Check if dragging available items from library
    if (active.data.current && (active.data.current.type === 'available-item' || active.data.current.type === 'available-items-multi')) {
      setActiveDraggedItem(active.data.current.item);
      setActiveDraggedItems(active.data.current.items || [active.data.current.item]);
    }
  };

  const handleDragOver = (event: DragOverEvent) => {
    const { active, over } = event;

    // Only set overId for sections when dragging available items
    if (
      active.data.current &&
      (active.data.current.type === 'available-item' || active.data.current.type === 'available-items-multi') &&
      over?.id.toString().startsWith('section-')
    ) {
      setOverId(over.id);
    } else if (!active.data.current || !active.data.current.type) {
      // For regular sorting
      setOverId(over?.id || null);
    } else {
      setOverId(null);
    }
  };

  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;

    // Check if this was a successful drop of available items (single or multi)
    const isSingleItemDrop =
      over && active.data.current && active.data.current.type === 'available-item' && over.id.toString().startsWith('section-');
    const isMultiItemDrop =
      over && active.data.current && active.data.current.type === 'available-items-multi' && over.id.toString().startsWith('section-');

    if (isSingleItemDrop || isMultiItemDrop) {
      setDroppedSuccessfully(true);
      const sectionId = parseInt(over.id.toString().replace('section-', ''));

      if (isMultiItemDrop) {
        // Handle multiple items
        const items = active.data.current!.items as AvailableItem[];
        handleAddMultipleItemsToSection(sectionId, items);
        // Clear selection after successful multi-drop
        setSelectedAvailableItems(new Set());
      } else {
        // Handle single item
        const item = active.data.current!.item as AvailableItem;
        handleAddItemToSection(sectionId, item);
      }

      // Immediately clean up for successful drops
      setActiveId(null);
      setActiveDraggedItem(null);
      setActiveDraggedItems([]);
      setOverId(null);
      setTimeout(() => setDroppedSuccessfully(false), 50);
      return;
    }

    // For cancelled drags or sorting, clean up normally
    setActiveId(null);
    setActiveDraggedItem(null);
    setActiveDraggedItems([]);
    setOverId(null);

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

  const handleAddSection = (sectionData: Partial<MenuSection>) => {
    const newSection: MenuSection = {
      id: Date.now(),
      name: sectionData.name || 'New Section',
      description: sectionData.description || '',
      icon: sectionData.icon,
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
  };

  const handleAddMultipleItemsToSection = (sectionId: number, items: AvailableItem[]) => {
    const newItems: MenuItem[] = items.map((item, index) => ({
      id: Date.now() + index, // Ensure unique IDs
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
    }));

    setSections(
      sections.map((section) => {
        if (section.id === sectionId) {
          return {
            ...section,
            items: [...section.items, ...newItems],
          };
        }
        return section;
      }),
    );

    setHasChanges(true);
    toast.success(`Added ${items.length} item${items.length > 1 ? 's' : ''} to section`);
  };

  const handleBulkAssign = (sectionId: number, itemIds: number[]) => {
    const itemsToAdd = availableItems.filter((item) => itemIds.includes(item.id));

    // Create all new items at once
    const newItems: MenuItem[] = itemsToAdd.map((item, index) => ({
      id: Date.now() + index, // Ensure unique IDs by adding index
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
    }));

    // Update sections in a single state update
    setSections(
      sections.map((section) => {
        if (section.id === sectionId) {
          return {
            ...section,
            items: [...section.items, ...newItems],
          };
        }
        return section;
      }),
    );

    setHasChanges(true);
    toast.success(`Added ${itemsToAdd.length} items to section`);
    setSelectedAvailableItems(new Set());
  };

  const handleSelectAvailableItem = (itemId: number) => {
    const newSelected = new Set(selectedAvailableItems);
    if (newSelected.has(itemId)) {
      newSelected.delete(itemId);
    } else {
      newSelected.add(itemId);
    }
    setSelectedAvailableItems(newSelected);
  };

  const handleBulkSelectItems = (itemIds: number[], selected: boolean) => {
    if (selected) {
      // Add all items to selection at once
      setSelectedAvailableItems(new Set([...selectedAvailableItems, ...itemIds]));
    } else {
      // Remove all items from selection at once
      const newSelected = new Set(selectedAvailableItems);
      itemIds.forEach((id) => newSelected.delete(id));
      setSelectedAvailableItems(newSelected);
    }
  };

  const handleEditSection = (section: MenuSection) => {
    setSections(sections.map((s) => (s.id === section.id ? section : s)));
    setHasChanges(true);
  };

  const handleEditItem = (item: MenuItem, sectionId: number) => {
    setSections(
      sections.map((section) => {
        if (section.id === sectionId) {
          return {
            ...section,
            items: section.items.map((i) => (i.id === item.id ? item : i)),
          };
        }
        return section;
      }),
    );
    setHasChanges(true);
  };

  const handleDeleteSection = (sectionId: number) => {
    if (confirm('Delete this section?')) {
      setSections(sections.filter((s) => s.id !== sectionId));
      setHasChanges(true);
    }
  };

  const handleDuplicateSection = (section: MenuSection) => {
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
  };

  const handleToggleSectionCollapse = (sectionId: number) => {
    setSections(sections.map((s) => (s.id === sectionId ? { ...s, isCollapsed: !s.isCollapsed } : s)));
  };

  const handleEditItemClick = (item: MenuItem, sectionId: number) => {
    setEditingItem({ item, sectionId });
  };

  const handleDeleteItem = (sectionId: number, itemId: number) => {
    setSections(
      sections.map((s) => {
        if (s.id === sectionId) {
          return {
            ...s,
            items: s.items.filter((i) => i.id !== itemId),
          };
        }
        return s;
      }),
    );
    setHasChanges(true);
  };

  const handleDuplicateItem = (sectionId: number, item: MenuItem) => {
    const duplicate = {
      ...item,
      id: Date.now(),
      displayName: item.displayName ? `${item.displayName} (Copy)` : undefined,
    };
    setSections(
      sections.map((s) => {
        if (s.id === sectionId) {
          return {
            ...s,
            items: [...s.items, duplicate],
          };
        }
        return s;
      }),
    );
    setHasChanges(true);
  };

  const handleCloseEditSection = () => {
    setEditingSection(null);
    setHasChanges(true);
  };

  const handleCloseEditItem = () => {
    setEditingItem(null);
    setHasChanges(true);
  };

  return (
    <AppLayout>
      <Head title={menu ? `Menu Builder - ${menu.name}` : 'Menu Builder'} />
      <Page>
        <Page.Header
          title="Menu Builder"
          actions={
            <div className="flex items-center gap-2">
              {menu && (
                <>
                  <Button variant="outline" size="sm" asChild>
                    <Link href={`/menu/${menu.id}`}>
                      <ArrowLeft className="mr-2 h-4 w-4" />
                      Back to Details
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
                </>
              )}
            </div>
          }
        />

        <DndContext
          sensors={sensors}
          collisionDetection={(args) => {
            // Use composed collision detection for available items being dragged to sections
            if (
              args.active.data.current &&
              (args.active.data.current.type === 'available-item' || args.active.data.current.type === 'available-items-multi')
            ) {
              // First try pointerWithin for precision
              const pointerCollisions = pointerWithin(args);
              const pointerSectionCollisions = pointerCollisions.filter((collision) => collision.id?.toString().startsWith('section-'));

              if (pointerSectionCollisions.length > 0) {
                return pointerSectionCollisions;
              }

              // Fall back to rectIntersection for small drag handles
              const rectCollisions = rectIntersection(args);
              const rectSectionCollisions = rectCollisions.filter((collision) => collision.id?.toString().startsWith('section-'));

              return rectSectionCollisions;
            }
            // Use closestCenter for sorting
            return closestCenter(args);
          }}
          onDragStart={handleDragStart}
          onDragOver={handleDragOver}
          onDragEnd={handleDragEnd}
        >
          <Page.SplitContent
            sidebar={
              menu
                ? {
                    position: 'right',
                    defaultSize: 35,
                    minSize: 20,
                    maxSize: 50,
                    collapsedSize: 8, // Restored to original size
                    defaultCollapsed: false,
                    onCollapsedChange: setIsLibraryCollapsed,
                    resizable: true,
                    showToggle: false,
                    renderContent: (collapsed, toggleCollapse) => (
                      <ItemLibrarySidebar
                        availableItems={availableItems}
                        sections={sections}
                        selectedAvailableItems={selectedAvailableItems}
                        onSelectItem={handleSelectAvailableItem}
                        onBulkSelect={handleBulkSelectItems}
                        onBulkAssign={handleBulkAssign}
                        isCollapsed={collapsed}
                        onToggleCollapsed={() => toggleCollapse()}
                      />
                    ),
                  }
                : undefined
            }
          >
            {/* Main content - Menu Sections */}
            <div className="flex h-full flex-col bg-gradient-to-br from-gray-50 to-gray-100/50">
              {/* Menu Selector Header */}
              <div className="flex-shrink-0 bg-white shadow-sm">
                <div className="px-3 py-2">
                  <div className="flex items-center justify-between">
                    <div className="flex items-start">
                      {allMenus.length > 0 ? (
                        <Select value={menu?.id.toString() || ''} onValueChange={handleMenuChange}>
                          <SelectTrigger className="h-8 w-[240px] border-gray-200 bg-gray-50 font-medium transition-colors hover:bg-white">
                            <div className="flex items-center gap-2">
                              <ChefHat className="h-4 w-4 text-gray-500" />
                              <SelectValue placeholder="Select a menu to edit" />
                            </div>
                          </SelectTrigger>
                          <SelectContent className="w-[240px]">
                            {allMenus.map((m) => (
                              <SelectItem key={m.id} value={m.id.toString()}>
                                <div className="flex w-full items-center gap-2">
                                  <span className="flex-1 truncate">{m.name}</span>
                                  {menu?.id === m.id && (
                                    <div className="flex items-center">
                                      <SquarePen className="h-3.5 w-3.5 text-green-600" />
                                    </div>
                                  )}
                                </div>
                              </SelectItem>
                            ))}
                            <div className="mt-1 border-t px-2 pt-2 pb-1">
                              <p className="flex items-center gap-1 text-xs text-muted-foreground">
                                <SquarePen className="h-3 w-3 text-green-600" />
                                <span>Currently editing</span>
                              </p>
                            </div>
                          </SelectContent>
                        </Select>
                      ) : (
                        <div className="text-sm text-muted-foreground">No menus available</div>
                      )}
                    </div>
                    <div className="flex items-center gap-2">
                      {menu && hasChanges && (
                        <Badge variant="outline" className="border-orange-200 bg-orange-50 text-xs text-orange-700">
                          Unsaved changes
                        </Badge>
                      )}
                      {menu && (
                        <Button onClick={() => setShowSectionDialog(true)} className="h-8">
                          <Plus className="mr-2 h-4 w-4" />
                          Add Section
                        </Button>
                      )}
                    </div>
                  </div>
                </div>
              </div>

              <div className="relative min-h-0 flex-1">
                <div className="absolute inset-0 overflow-y-auto">
                  {/* Display validation errors and flash messages at the top of the content area */}
                  {menu && (
                    <div className="w-full space-y-2 px-8 pt-3">
                      <FlashMessages />
                      <ValidationErrors />
                    </div>
                  )}

                  {allMenus.length === 0 ? (
                    <div className="flex h-full items-center justify-center p-8">
                      <EmptyState
                        icon={FileText}
                        title="No menus available"
                        description="Create your first menu to start building its structure and adding items."
                        actions={
                          <Button asChild>
                            <Link href="/menu/create">
                              <Plus className="mr-2 h-4 w-4" />
                              Create First Menu
                            </Link>
                          </Button>
                        }
                      />
                    </div>
                  ) : !menu ? (
                    <div className="flex h-full items-center justify-center p-8">
                      <EmptyState
                        icon={FileText}
                        title="Select a menu"
                        description="Choose a menu from the dropdown above to start organizing its sections and items."
                      />
                    </div>
                  ) : (
                    <div className="p-8">
                      {/* Sections Header */}
                      {sections.length > 0 && (
                        <div className="mb-6">
                          <h2 className="flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <Layers className="h-4 w-4 text-gray-500" />
                            Menu Sections
                          </h2>
                        </div>
                      )}

                      <SortableContext items={sections.map((s) => `section-${s.id}`)} strategy={verticalListSortingStrategy}>
                        {sections.length === 0 ? (
                          <div className="flex min-h-[400px] items-center justify-center">
                            <div className="max-w-sm text-center">
                              <div className="mx-auto mb-6 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-gray-100 to-gray-200 shadow-sm">
                                <Package className="h-10 w-10 text-gray-500" />
                              </div>
                              <h3 className="mb-2 text-lg font-semibold text-gray-900">Build your menu structure</h3>
                              <p className="mb-6 text-sm text-gray-600">
                                Organize your items into sections like "Appetizers", "Main Courses", or "Beverages"
                              </p>
                              <Button onClick={() => setShowSectionDialog(true)} className="shadow-sm">
                                <Plus className="mr-2 h-4 w-4" />
                                Create First Section
                              </Button>
                            </div>
                          </div>
                        ) : (
                          <div className="space-y-4">
                            {sections.map((section) => (
                              <MenuSectionCard
                                key={section.id}
                                section={section}
                                isDraggedOver={overId === `section-${section.id}` && !!activeDraggedItem}
                                onEdit={() => setEditingSection(section)}
                                onDelete={() => handleDeleteSection(section.id)}
                                onDuplicate={() => handleDuplicateSection(section)}
                                onToggleCollapse={() => handleToggleSectionCollapse(section.id)}
                                onEditItem={(item) => handleEditItemClick(item, section.id)}
                                onDeleteItem={(itemId) => handleDeleteItem(section.id, itemId)}
                                onDuplicateItem={(item) => handleDuplicateItem(section.id, item)}
                              />
                            ))}
                          </div>
                        )}
                      </SortableContext>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </Page.SplitContent>
          <DragOverlay
            dropAnimation={
              droppedSuccessfully
                ? {
                    duration: 0,
                  }
                : {
                    duration: 200,
                    easing: 'cubic-bezier(0.18, 0.67, 0.6, 1.22)',
                  }
            }
            style={{
              zIndex: 9999,
            }}
          >
            {activeId ? (
              <div className={cn('pointer-events-none', droppedSuccessfully && 'opacity-0 transition-opacity duration-100')}>
                {typeof activeId === 'string' && activeId.startsWith('section-') && (
                  <div className="rounded-lg bg-white p-4 opacity-90 shadow-2xl">
                    <div className="font-medium">Moving section...</div>
                  </div>
                )}
                {typeof activeId === 'string' && activeId.startsWith('item-') && (
                  <div className="rounded-lg bg-white p-3 opacity-90 shadow-2xl">
                    <div className="text-sm">Moving item...</div>
                  </div>
                )}
                {activeDraggedItem && (
                  // Multi-item drag with stacked cards effect
                  <div
                    className={cn(
                      'relative scale-105 transform transition-transform',
                      activeId?.toString().includes('collapsed') && '-translate-x-32',
                    )}
                  >
                    {/* Stacked cards behind main card for multi-select */}
                    {activeDraggedItems.length > 1 && (
                      <>
                        {/* Third card (furthest back) */}
                        {activeDraggedItems.length > 2 && (
                          <div className="absolute top-4 left-4 h-[82px] w-[260px] rounded-xl border border-gray-200 bg-gray-100 p-3 shadow-lg" />
                        )}
                        {/* Second card */}
                        <div className="absolute top-2 left-2 h-[82px] w-[260px] rounded-xl border border-gray-300 bg-white p-3 shadow-xl" />
                      </>
                    )}

                    {/* Main card (front) */}
                    <div className="relative w-[260px] rounded-xl border-2 border-gray-900 bg-white p-3 shadow-2xl">
                      <div className="flex items-center gap-3">
                        {activeDraggedItem.imageUrl ? (
                          <img
                            src={activeDraggedItem.imageUrl}
                            alt={activeDraggedItem.name}
                            className="h-14 w-14 flex-shrink-0 rounded-lg object-cover"
                          />
                        ) : (
                          <div className="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-lg bg-gray-100">
                            <Package className="h-7 w-7 text-gray-500" />
                          </div>
                        )}
                        <div className="min-w-0 flex-1">
                          <div className="truncate text-sm font-semibold text-gray-900">
                            {activeDraggedItems.length > 1 ? (
                              <span className="flex items-center gap-2">
                                <span className="rounded bg-gray-900 px-1.5 py-0.5 text-xs font-bold text-white">{activeDraggedItems.length}</span>
                                <span>items selected</span>
                              </span>
                            ) : (
                              activeDraggedItem.name
                            )}
                          </div>
                          <div className="mt-1 text-xs text-gray-600">
                            {activeDraggedItems.length > 1 ? (
                              <div className="space-y-0.5">
                                {activeDraggedItems.slice(0, 2).map((item, idx) => (
                                  <div key={idx} className="truncate">
                                    • {item.name}
                                  </div>
                                ))}
                                {activeDraggedItems.length > 2 && <div className="text-gray-500">and {activeDraggedItems.length - 2} more...</div>}
                              </div>
                            ) : (
                              <>
                                {activeDraggedItem.price ? formatCurrency(activeDraggedItem.price) : 'No price'}
                                {activeDraggedItem.category && <div className="mt-1 font-medium text-gray-600">{activeDraggedItem.category}</div>}
                              </>
                            )}
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            ) : null}
          </DragOverlay>
        </DndContext>
      </Page>

      {/* Dialogs */}
      <AddSectionSheet open={showSectionDialog} onOpenChange={setShowSectionDialog} onAddSection={handleAddSection} />

      <EditSectionDialog section={editingSection} onClose={handleCloseEditSection} onSave={handleEditSection} />

      <EditItemDialog item={editingItem} onClose={handleCloseEditItem} onSave={handleEditItem} />
    </AppLayout>
  );
}
