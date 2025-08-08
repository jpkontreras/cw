import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronRight, Copy, Edit2, GripVertical, MoreVertical, Package, Trash2, Utensils } from 'lucide-react';
import { MenuItemCard } from './menu-item-card';
import { type AvailableItem, type MenuItem, type MenuSection } from './types';
import { SECTION_ICONS } from './constants';

interface MenuSectionCardProps {
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
}

export function MenuSectionCard({
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
}: MenuSectionCardProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ 
    id: `section-${section.id}` 
  });

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
                    <MenuItemCard
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