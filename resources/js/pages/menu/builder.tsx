import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
  AddSectionDialog,
  EditItemDialog,
  EditSectionDialog,
  ItemLibrarySidebar,
  MenuSectionCard,
  SECTION_TEMPLATES,
  type AvailableItem,
  type MenuBuilderPageProps,
  type MenuItem,
  type MenuSection,
} from '@/components/modules/menu';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { closestCenter, DndContext, DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Eye, Package, Plus, Save } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';

export default function MenuBuilder({ menu, structure, availableItems }: MenuBuilderPageProps) {
  const [sections, setSections] = useState<MenuSection[]>(structure.sections);
  const [selectedItems, setSelectedItems] = useState<Set<number>>(new Set());
  const [selectedAvailableItems, setSelectedAvailableItems] = useState<Set<number>>(new Set());
  const [hasChanges, setHasChanges] = useState(false);
  const [isSaving, setIsSaving] = useState(false);
  const [editingSection, setEditingSection] = useState<MenuSection | null>(null);
  const [editingItem, setEditingItem] = useState<{ item: MenuItem; sectionId: number } | null>(null);
  const [showSectionDialog, setShowSectionDialog] = useState(false);
  const [isLibraryCollapsed, setIsLibraryCollapsed] = useState(false);

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
        `/menu/${menu.id}/builder/save`,
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

  const handleBulkAssign = (sectionId: number, itemIds: number[]) => {
    const itemsToAdd = availableItems.filter((item) => itemIds.includes(item.id));
    
    itemsToAdd.forEach((item) => {
      handleAddItemToSection(sectionId, item);
    });

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

  const handleQuickAdd = (item: AvailableItem) => {
    if (sections.length > 0) {
      handleAddItemToSection(sections[0].id, item);
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

  const handleSelectItem = (itemId: number) => {
    const newSelected = new Set(selectedItems);
    if (newSelected.has(itemId)) {
      newSelected.delete(itemId);
    } else {
      newSelected.add(itemId);
    }
    setSelectedItems(newSelected);
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
      <Head title={`Menu Builder - ${menu.name}`} />
      <Page>
        <Page.Header
          title="Manage Menu"
          actions={
            <div className="flex items-center gap-2">
              <Button variant="outline" size="sm" asChild>
                <Link href={`/menu/${menu.id}`}>
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
            collapsedSize: 8,
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
                onBulkAssign={handleBulkAssign}
                onQuickAdd={handleQuickAdd}
                isCollapsed={collapsed}
                onToggleCollapsed={() => toggleCollapse()}
              />
            ),
          }}
        >
          {/* Main content - Menu Sections */}
          <div className="flex h-full flex-col bg-gray-50/50">
            <div className="flex-shrink-0 p-6 border-b bg-white/50">
              <div className="flex items-center justify-between">
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

            <div className="flex-1 min-h-0 overflow-y-auto">
              <div className="p-6">
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
                        <MenuSectionCard
                          key={section.id}
                          section={section}
                          onEdit={() => setEditingSection(section)}
                          onDelete={() => handleDeleteSection(section.id)}
                          onDuplicate={() => handleDuplicateSection(section)}
                          onToggleCollapse={() => handleToggleSectionCollapse(section.id)}
                          onAddItem={(item) => handleAddItemToSection(section.id, item)}
                          onEditItem={(item) => handleEditItemClick(item, section.id)}
                          onDeleteItem={(itemId) => handleDeleteItem(section.id, itemId)}
                          onDuplicateItem={(item) => handleDuplicateItem(section.id, item)}
                          selectedItems={selectedItems}
                          onSelectItem={handleSelectItem}
                        />
                      ))}
                    </div>
                  )}
                  </SortableContext>
                </DndContext>
              </div>
            </div>
          </div>
        </Page.SplitContent>
      </Page>

      {/* Dialogs */}
      <AddSectionDialog
        open={showSectionDialog}
        onOpenChange={setShowSectionDialog}
        onAddSection={handleAddSection}
      />

      <EditSectionDialog
        section={editingSection}
        onClose={handleCloseEditSection}
        onSave={handleEditSection}
      />

      <EditItemDialog
        item={editingItem}
        onClose={handleCloseEditItem}
        onSave={handleEditItem}
      />
    </AppLayout>
  );
}