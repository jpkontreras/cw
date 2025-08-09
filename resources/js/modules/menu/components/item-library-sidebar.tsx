import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { useDraggable } from '@dnd-kit/core';
import { GripVertical, Layers, Package, PanelRightClose, Plus, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AvailableItemCard } from './available-item-card';
import { type AvailableItem, type MenuSection } from '../types';

// Component for item thumbnail display
function ItemThumbnail({ item, size = 'default', className }: { item: AvailableItem; size?: 'default' | 'small' | 'auto'; className?: string }) {
  const dimensions = size === 'small' ? 'h-10 w-10' : size === 'auto' ? 'w-full aspect-square' : 'h-12 w-12';
  const iconSize = size === 'small' ? 'h-6 w-6' : size === 'auto' ? 'h-8 w-8' : 'h-7 w-7';

  if (item.imageUrl) {
    return <img src={item.imageUrl} alt={item.name} className={cn('rounded object-cover', dimensions, className)} />;
  }

  return (
    <div className={cn('flex items-center justify-center rounded bg-gray-200', dimensions, className)}>
      <Package className={cn('text-gray-500', iconSize)} />
    </div>
  );
}

// Component for item details in popover
function ItemDetails({ item }: { item: AvailableItem }) {
  return (
    <div className="space-y-2">
      <div className="text-sm font-medium">{item.name}</div>
      {item.description && <p className="text-xs text-muted-foreground">{item.description}</p>}
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium">{formatCurrency(item.price)}</span>
        {item.category && (
          <Badge variant="outline" className="text-xs">
            {item.category}
          </Badge>
        )}
      </div>
    </div>
  );
}

// Component for item list
function ItemList({
  items,
  selectedItems,
  onSelectItem,
}: {
  items: AvailableItem[];
  selectedItems: Set<number>;
  onSelectItem: (id: number) => void;
}) {
  return (
    <div className="space-y-2">
      {items.map((item) => (
        <AvailableItemCard
          key={item.id}
          item={item}
          isSelected={selectedItems.has(item.id)}
          onSelect={() => onSelectItem(item.id)}
        />
      ))}
    </div>
  );
}

interface ItemLibrarySidebarProps {
  availableItems: AvailableItem[];
  sections: MenuSection[];
  selectedAvailableItems: Set<number>;
  onSelectItem: (itemId: number) => void;
  onBulkAssign: (sectionId: number, itemIds: number[]) => void;
  isCollapsed: boolean;
  onToggleCollapsed: (collapsed: boolean) => void;
}

export function ItemLibrarySidebar({
  availableItems,
  sections,
  selectedAvailableItems,
  onSelectItem,
  onBulkAssign,
  isCollapsed,
  onToggleCollapsed,
}: ItemLibrarySidebarProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [isSearchExpanded, setIsSearchExpanded] = useState(false);

  const categories = useMemo(() => {
    const cats = new Set(availableItems.map((item) => item.category).filter(Boolean));
    return Array.from(cats);
  }, [availableItems]);

  const filteredItems = useMemo(() => {
    return availableItems.filter((item) => {
      const matchesSearch =
        item.name.toLowerCase().includes(searchQuery.toLowerCase()) || item.description?.toLowerCase().includes(searchQuery.toLowerCase());
      const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
      return matchesSearch && matchesCategory && item.isActive;
    });
  }, [availableItems, searchQuery, selectedCategory]);

  const handleSelectAll = () => {
    if (selectedAvailableItems.size === filteredItems.length) {
      // Deselect all
      filteredItems.forEach((item) => onSelectItem(item.id));
    } else {
      // Select all
      filteredItems.forEach((item) => {
        if (!selectedAvailableItems.has(item.id)) {
          onSelectItem(item.id);
        }
      });
    }
  };

  const handleBulkAssignToSection = (sectionId: number) => {
    const itemIds = Array.from(selectedAvailableItems);
    onBulkAssign(sectionId, itemIds);
  };

  if (isCollapsed) {
    return (
      <div className="flex h-full flex-col bg-gray-50">
        <div className="flex-shrink-0 border-b bg-white">
          <Button
            variant="ghost"
            size="sm"
            className="flex h-10 w-full items-center justify-center hover:bg-gray-100"
            onClick={() => onToggleCollapsed(false)}
            aria-label="Expand sidebar"
          >
            <PanelRightClose className="h-4 w-4 rotate-180" />
          </Button>
        </div>

        <div className="relative h-full bg-white">
          <div className="absolute inset-0 overflow-y-auto p-3">
            {availableItems.length === 0 ? (
              <div className="flex h-full items-center justify-center">
                <Package className="h-5 w-5 text-gray-400" />
              </div>
            ) : filteredItems.length === 0 ? (
              <div className="flex h-full items-center justify-center">
                <Search className="h-5 w-5 text-gray-400" />
              </div>
            ) : (
              <div className="flex flex-col items-center gap-2">
                {filteredItems.map((item) => (
                  <CollapsedItemCard 
                    key={item.id} 
                    item={item} 
                    isSelected={selectedAvailableItems.has(item.id)}
                    onSelect={() => onSelectItem(item.id)}
                  />
                ))}
              </div>
            )}
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="flex h-full flex-col overflow-hidden">
      <div className="flex-shrink-0 border-b bg-white px-4 py-3">
        <div className="mb-1 flex items-center justify-between">
          <h3 className="text-lg font-semibold">Item Library</h3>
          <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => onToggleCollapsed(true)}>
            <PanelRightClose className="h-4 w-4" />
          </Button>
        </div>
        <p className="mb-3 text-xs text-muted-foreground">Drag items to sections or use bulk assign</p>

        <div className="flex items-center gap-2">
          {!isSearchExpanded ? (
            <>
              <Select value={selectedCategory} onValueChange={setSelectedCategory}>
                <SelectTrigger className="flex-1">
                  <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">All Categories</SelectItem>
                  {categories.filter(Boolean).map((cat) => (
                    <SelectItem key={cat} value={cat!}>
                      {cat}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <Button variant="outline" size="icon" onClick={() => setIsSearchExpanded(true)}>
                <Search className="h-4 w-4" />
              </Button>
            </>
          ) : (
            <div className="relative flex-1">
              <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 transform text-gray-400" />
              <Input
                placeholder="Search items..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pr-9 pl-9"
                autoFocus
                onBlur={() => {
                  if (!searchQuery) {
                    setIsSearchExpanded(false);
                  }
                }}
              />
              <Button
                variant="ghost"
                size="icon"
                className="absolute top-1/2 right-1 h-6 w-6 -translate-y-1/2"
                onClick={() => {
                  setSearchQuery('');
                  setIsSearchExpanded(false);
                }}
              >
                ×
              </Button>
            </div>
          )}

          {selectedAvailableItems.size > 0 && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" size="sm">
                  <Layers className="mr-2 h-4 w-4" />
                  {selectedAvailableItems.size}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuLabel>Assign to Section</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {sections.map((section) => (
                  <DropdownMenuItem key={section.id} onClick={() => handleBulkAssignToSection(section.id)}>
                    {section.name}
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>

        <div className="mt-2 flex items-center justify-between text-xs">
          <span className="text-muted-foreground">{filteredItems.length} items</span>
          <Button variant="ghost" size="sm" onClick={handleSelectAll} className="h-6 px-2 text-xs">
            {selectedAvailableItems.size === filteredItems.length ? 'Deselect All' : 'Select All'}
          </Button>
        </div>
      </div>
      <div className="relative h-full">
        <div className="absolute inset-0 overflow-y-scroll p-2">
          {availableItems.length === 0 ? (
            <div className="flex h-full items-center justify-center">
              <div className="text-center">
                <Package className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 className="mb-2 text-sm font-medium">No items available</h3>
                <p className="text-xs text-muted-foreground mb-4">Create items to add them to your menu</p>
                <Button size="sm" asChild>
                  <Link href="/items/create">
                    <Plus className="mr-2 h-3 w-3" />
                    Create Item
                  </Link>
                </Button>
              </div>
            </div>
          ) : filteredItems.length === 0 ? (
            <div className="flex h-full items-center justify-center">
              <div className="text-center">
                <Search className="mx-auto mb-4 h-12 w-12 text-gray-400" />
                <h3 className="mb-2 text-sm font-medium">No items found</h3>
                <p className="text-xs text-muted-foreground">Try adjusting your search or category filter</p>
              </div>
            </div>
          ) : (
            <ScrollArea>
              <ItemList items={filteredItems} selectedItems={selectedAvailableItems} onSelectItem={onSelectItem} />
            </ScrollArea>
          )}
        </div>
      </div>
    </div>
  );
}

// Collapsed view item card with drag support
function CollapsedItemCard({ 
  item, 
  isSelected,
  onSelect,
}: { 
  item: AvailableItem; 
  isSelected: boolean;
  onSelect: () => void;
}) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `available-collapsed-${item.id}`,
    data: {
      type: 'available-item',
      item: item,
    },
  });

  // Don't apply transform to library items - they should stay in place
  const style = {};

  return (
    <Popover>
      <PopoverTrigger asChild>
        <div
          ref={setNodeRef}
          style={style}
          {...attributes} 
          {...listeners}
          className={cn(
            'group relative h-12 w-12 cursor-move rounded-lg transition-all',
            'hover:ring-2 hover:ring-blue-400 hover:ring-offset-1',
            isSelected && 'ring-2 ring-blue-500 ring-offset-1',
            isDragging && 'opacity-50',
          )}
          title={item.name}
        >
          {/* Item image/icon */}
          {item.imageUrl ? (
            <img 
              src={item.imageUrl} 
              alt={item.name} 
              className="h-full w-full rounded-lg object-cover" 
            />
          ) : (
            <div className="flex h-full w-full items-center justify-center rounded-lg bg-gray-100">
              <Package className="h-6 w-6 text-gray-400" />
            </div>
          )}
          
          {/* Selection indicator */}
          {isSelected && (
            <div className="absolute -top-1 -right-1 h-4 w-4 bg-blue-500 rounded-full flex items-center justify-center">
              <svg className="h-2.5 w-2.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
              </svg>
            </div>
          )}

          {/* Drag indicator on hover */}
          <div className="absolute inset-0 rounded-lg bg-black/0 group-hover:bg-black/5 transition-colors flex items-center justify-center">
            <GripVertical className="h-4 w-4 text-white drop-shadow opacity-0 group-hover:opacity-100 transition-opacity" />
          </div>
        </div>
      </PopoverTrigger>
      <PopoverContent side="left" className="w-64">
        <ItemDetails item={item} />
        <div className="mt-3 pt-3 border-t">
          <Button
            size="sm"
            variant={isSelected ? "outline" : "default"}
            className="w-full"
            onClick={(e) => {
              e.stopPropagation();
              onSelect();
            }}
          >
            {isSelected ? 'Deselect' : 'Select for bulk assign'}
          </Button>
        </div>
      </PopoverContent>
    </Popover>
  );
}
