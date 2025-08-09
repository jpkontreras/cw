import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { useDraggable } from '@dnd-kit/core';
import { GripVertical, Package } from 'lucide-react';
import { type AvailableItem } from '../types';

interface AvailableItemCardProps {
  item: AvailableItem;
  isSelected: boolean;
  onSelect: () => void;
  selectedItems: AvailableItem[];
}

export function AvailableItemCard({
  item,
  isSelected,
  onSelect,
  selectedItems,
}: AvailableItemCardProps) {
  // If this item is selected and we're dragging, include all selected items
  const itemsToMove = isSelected ? selectedItems : [item];
  
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `available-${item.id}`,
    data: {
      type: isSelected ? 'available-items-multi' : 'available-item',
      item: item,
      items: itemsToMove,
      count: itemsToMove.length,
    },
  });

  // Don't apply transform - let DragOverlay handle the visual feedback
  const style = {};

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={cn(
        'group relative rounded-lg border bg-white transition-all hover:shadow-md touch-none',
        isSelected && 'ring-2 ring-gray-900 ring-offset-1',
        isDragging && 'opacity-50',
      )}
    >
      <div 
        {...attributes} 
        {...listeners}
        className="p-2.5 cursor-move"
      >
        <div className="flex gap-2.5 items-center pointer-events-none">
          {/* Drag Handle Visual Indicator */}
          <div
            className="flex-shrink-0 rounded p-1 transition-colors group-hover:bg-gray-100"
            title="Drag to add to section"
          >
            <GripVertical className="h-4 w-4 text-gray-400" />
          </div>

          {/* Image */}
          <div className="flex-shrink-0">
            {item.imageUrl ? (
              <img src={item.imageUrl} alt={item.name} className="h-11 w-11 rounded-lg object-cover" />
            ) : (
              <div className="flex h-11 w-11 items-center justify-center rounded-lg bg-gray-100">
                <Package className="h-5 w-5 text-gray-400" />
              </div>
            )}
          </div>

          {/* Content */}
          <div className="flex-1 min-w-0">
            <div className="font-medium text-sm leading-tight">
              {item.name}
            </div>
            {item.description && (
              <p className="text-xs text-muted-foreground mt-0.5 line-clamp-1">
                {item.description}
              </p>
            )}
            {item.category && (
              <Badge variant="outline" className="mt-1 text-xs">
                {item.category}
              </Badge>
            )}
          </div>

          {/* Price and Checkbox */}
          <div className="flex flex-col items-end gap-2">
            <div className="text-sm font-semibold text-gray-900 whitespace-nowrap">
              {item.basePrice !== null && item.basePrice !== undefined ? formatCurrency(item.basePrice) : 'No price'}
            </div>
            <div 
              className="pointer-events-auto"
              onClick={(e) => {
                e.stopPropagation();
                e.preventDefault();
              }}
            >
              <Checkbox 
                checked={isSelected} 
                onCheckedChange={onSelect}
                className="h-5 w-5"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}