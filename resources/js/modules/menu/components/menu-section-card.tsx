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
import { useDroppable } from '@dnd-kit/core';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronRight, Copy, Edit2, GripVertical, MoreVertical, Package, Trash2, Utensils, AlertTriangle, AlertCircle } from 'lucide-react';
import { MenuItemCard } from './menu-item-card';
import { type MenuItem, type MenuSection } from '../types';
import { SECTION_ICONS } from '../constants';

interface MenuSectionCardProps {
  section: MenuSection;
  isDraggedOver?: boolean;
  duplicateItemCount?: number;
  isDeleting?: boolean;
  onEdit: () => void;
  onDelete: () => void;
  onConfirmDelete?: () => void;
  onCancelDelete?: () => void;
  onDuplicate: () => void;
  onToggleCollapse: () => void;
  onEditItem: (item: MenuItem) => void;
  onDeleteItem: (itemId: number) => void;
  onDuplicateItem: (item: MenuItem) => void;
}

export function MenuSectionCard({
  section,
  isDraggedOver = false,
  duplicateItemCount = 0,
  isDeleting = false,
  onEdit,
  onDelete,
  onConfirmDelete,
  onCancelDelete,
  onDuplicate,
  onToggleCollapse,
  onEditItem,
  onDeleteItem,
  onDuplicateItem,
}: MenuSectionCardProps) {
  const { attributes, listeners, setNodeRef: setSortableRef, transform, transition, isDragging } = useSortable({ 
    id: `section-${section.id}` 
  });
  
  const { setNodeRef: setDroppableRef } = useDroppable({
    id: `section-${section.id}`,
  });
  
  const setNodeRef = (node: HTMLElement | null) => {
    setSortableRef(node);
    setDroppableRef(node);
  };

  const style = {
    transform: CSS.Transform.toString(transform),
    transition,
  };

  const Icon = section.icon ? SECTION_ICONS[section.icon as keyof typeof SECTION_ICONS] : Utensils;

  return (
    <div ref={setNodeRef} style={style} className={cn('mb-2 transition-all', isDragging && 'opacity-50 scale-[1.02]')}>
      <Card className={cn(
        "shadow-sm transition-all hover:shadow-md group/card",
        isDraggedOver && "ring-2 ring-gray-400 bg-gray-50/50",
        isDeleting && "ring-2 ring-red-500/20"
      )}>
        <CardHeader className="bg-white py-2">
          <div className="flex items-center justify-between">
            <div className="flex items-center gap-3">
              <button
                type="button"
                {...attributes} 
                {...listeners} 
                className="cursor-move rounded p-1 transition-colors hover:bg-gray-100 group-hover/card:text-gray-600 touch-none"
                title="Drag to reorder section"
                style={{ touchAction: 'none' }}
              >
                <GripVertical className="h-5 w-5 text-gray-400 transition-colors group-hover/card:text-gray-500" />
              </button>

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

        {/* Delete Confirmation Bar */}
        {isDeleting && (
          <div className="border-t bg-red-50 px-6 py-4 animate-in slide-in-from-top-2 duration-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-3">
                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-red-100">
                  <AlertTriangle className="h-5 w-5 text-red-600" />
                </div>
                <div>
                  <p className="font-medium text-gray-900">
                    Delete "{section.name}" section?
                  </p>
                  <p className="text-sm text-gray-600">
                    This will remove the section and all {section.items.length} item{section.items.length !== 1 ? 's' : ''} from the menu.
                  </p>
                </div>
              </div>
              <div className="flex items-center gap-2">
                <Button
                  variant="outline"
                  onClick={onCancelDelete}
                  className="min-w-[80px]"
                >
                  Cancel
                </Button>
                <Button
                  variant="destructive"
                  onClick={onConfirmDelete}
                  className="min-w-[80px]"
                >
                  Delete
                </Button>
              </div>
            </div>
          </div>
        )}

        {!section.isCollapsed && (
          <CardContent className="pt-0 pb-2">
            {isDraggedOver && duplicateItemCount > 0 && (
              <div className="mb-2 flex items-center gap-2 rounded-md bg-yellow-50 border border-yellow-200 px-3 py-2 text-sm text-yellow-800 animate-in fade-in duration-200">
                <AlertCircle className="h-4 w-4 flex-shrink-0" />
                <span>
                  {duplicateItemCount === 1 
                    ? "This item is already in this section" 
                    : `${duplicateItemCount} items are already in this section`}
                </span>
              </div>
            )}
            {section.items.length === 0 ? (
              <div className={cn(
                "rounded-lg border-2 border-dashed py-4 text-center transition-colors",
                isDraggedOver && "bg-gray-50 border-gray-400"
              )}>
                <Package className="mx-auto mb-1 h-6 w-6 text-gray-400" />
                <p className="text-xs text-muted-foreground">Drag items here to add them</p>
              </div>
            ) : (
              <SortableContext items={section.items.map((i) => `item-${i.id}`)} strategy={verticalListSortingStrategy}>
                <div className="space-y-1">
                  {section.items.map((item) => (
                    <MenuItemCard
                      key={item.id}
                      item={item}
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