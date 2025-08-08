import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Package, Plus } from 'lucide-react';
import { type AvailableItem } from './types';

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
          {item.description && (
            <p className="mt-0.5 truncate text-xs text-muted-foreground">{item.description}</p>
          )}
          {item.category && (
            <Badge variant="outline" className="mt-1 text-xs">
              {item.category}
            </Badge>
          )}
        </div>

        <div className="text-right">
          <div className="text-sm font-medium">{formatCurrency(item.price)}</div>
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