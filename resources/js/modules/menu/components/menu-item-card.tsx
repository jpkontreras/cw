import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Copy, Edit2, GripVertical, MoreVertical, Package, Star, Trash2 } from 'lucide-react';
import { type MenuItem } from '../types';

interface MenuItemCardProps {
  item: MenuItem;
  isSelected: boolean;
  onSelect: () => void;
  onEdit: () => void;
  onDelete: () => void;
  onDuplicate: () => void;
}

export function MenuItemCard({
  item,
  isSelected,
  onSelect,
  onEdit,
  onDelete,
  onDuplicate,
}: MenuItemCardProps) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ 
    id: `item-${item.id}` 
  });

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
        isDragging && 'opacity-50 scale-[1.02]',
        isSelected && 'border-blue-500 bg-blue-50/50 shadow-sm',
      )}
    >
      <Checkbox checked={isSelected} onCheckedChange={onSelect} />

      <button
        type="button"
        {...attributes} 
        {...listeners} 
        className="cursor-move rounded p-1 transition-colors hover:bg-gray-100 touch-none"
        title="Drag to reorder item"
        style={{ touchAction: 'none' }}
      >
        <GripVertical className="h-4 w-4 text-gray-400 transition-colors group-hover:text-gray-600" />
      </button>

      {item.baseItem?.imageUrl ? (
        <img 
          src={item.baseItem.imageUrl} 
          alt={item.displayName || item.baseItem?.name} 
          className="h-12 w-12 rounded object-cover" 
        />
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
          <p className="truncate text-xs text-muted-foreground">
            {item.displayDescription || item.baseItem?.description}
          </p>
        )}
      </div>

      <div className="text-right">
        <div className="text-sm font-medium">
          {formatCurrency(item.priceOverride || item.baseItem?.price || 0)}
        </div>
        {item.priceOverride && item.baseItem?.price && (
          <div className="text-xs text-muted-foreground line-through">
            {formatCurrency(item.baseItem.price)}
          </div>
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