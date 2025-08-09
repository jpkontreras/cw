import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { useDraggable } from '@dnd-kit/core';
import { GripVertical, Package, Plus } from 'lucide-react';
import { type AvailableItem } from '../types';

interface AvailableItemCardProps {
  item: AvailableItem;
  isSelected: boolean;
  onSelect: () => void;
  onQuickAdd: () => void;
}

export function AvailableItemCard({
  item,
  isSelected,
  onSelect,
  onQuickAdd,
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
      ref={setNodeRef}
      style={style}
      className={cn(
        'group rounded-lg border bg-white p-2 transition-all hover:shadow-md',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
        isDragging && 'opacity-50',
      )}
    >
      <div className="grid grid-cols-[20px_auto_44px_1fr_auto] gap-x-2 gap-y-0.5">
        {/* Drag Handle */}
        <button
          type="button"
          {...attributes} 
          {...listeners} 
          className="row-span-2 self-center cursor-move rounded p-0.5 transition-colors hover:bg-gray-100 touch-none"
          title="Drag to add to section"
          style={{ touchAction: 'none' }}
        >
          <GripVertical className="h-4 w-4 text-gray-400 transition-colors group-hover:text-gray-600" />
        </button>
        
        {/* Checkbox */}
        <div className="row-span-2 self-center">
          <Checkbox checked={isSelected} onCheckedChange={onSelect} />
        </div>

        {/* Image */}
        <div className="row-span-2 self-center">
          {item.imageUrl ? (
            <img src={item.imageUrl} alt={item.name} className="h-11 w-11 rounded object-cover" />
          ) : (
            <div className="flex h-11 w-11 items-center justify-center rounded bg-gray-100">
              <Package className="h-5 w-5 text-gray-400" />
            </div>
          )}
        </div>

        {/* Content - spans all rows */}
        <div className="row-span-2">
          <div className="break-words text-sm font-medium leading-snug">
            {item.name}
          </div>
          {item.description && (
            <p className="break-words text-xs text-muted-foreground leading-snug">
              {item.description}
            </p>
          )}
          {item.category && (
            <Badge variant="outline" className="mt-0.5 inline-block text-xs">
              {item.category}
            </Badge>
          )}
        </div>

        {/* Price and Action - stacked vertically */}
        <div className="row-span-2 flex flex-col items-end justify-center gap-0.5">
          <div className="whitespace-nowrap text-sm font-semibold text-gray-900">
            {item.price ? formatCurrency(item.price) : 'No price'}
          </div>
          <Button 
            size="sm" 
            variant="outline" 
            className="h-6 px-2 text-xs" 
            onClick={onQuickAdd}
          >
            <Plus className="mr-0.5 h-3 w-3" />
            Add
          </Button>
        </div>
      </div>
    </div>
  );
}