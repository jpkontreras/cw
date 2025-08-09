import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Package, Plus } from 'lucide-react';
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
  return (
    <div
      className={cn(
        'group cursor-move rounded-lg border bg-white p-2 transition-all hover:shadow-md',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
      )}
      draggable
      onDragStart={(e) => {
        e.dataTransfer.setData('item', JSON.stringify(item));
      }}
    >
      <div className="grid grid-cols-[auto_44px_1fr_auto] gap-x-2 gap-y-0.5">
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