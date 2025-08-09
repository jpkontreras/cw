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
}

export function AvailableItemCard({
  item,
  isSelected,
  onSelect,
}: AvailableItemCardProps) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `available-${item.id}`,
    data: {
      type: 'available-item',
      item: item,
    },
  });

  // Don't apply transform to library items - they should stay in place
  const style = {};

  return (
    <div
      className={cn(
        'group relative rounded-lg border bg-white transition-all hover:shadow-md',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
        isDragging && 'opacity-50',
      )}
    >
      <div className="p-2.5">
        <div className="flex gap-2.5 items-center">
          {/* Drag Handle - Only this is draggable */}
          <div
            ref={setNodeRef}
            {...attributes} 
            {...listeners}
            style={style}
            className="flex-shrink-0 cursor-move rounded p-1 transition-colors hover:bg-gray-100"
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
              {item.price ? formatCurrency(item.price) : 'No price'}
            </div>
            <Checkbox 
              checked={isSelected} 
              onCheckedChange={onSelect}
              className="h-5 w-5"
            />
          </div>
        </div>
      </div>
    </div>
  );
}