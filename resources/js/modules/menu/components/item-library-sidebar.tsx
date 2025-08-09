import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { useDraggable } from '@dnd-kit/core';
import { GripVertical, Layers, Package, PanelRightClose, Plus, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { AvailableItemCard } from './available-item-card';
import { type AvailableItem, type MenuSection } from '../types';


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
  // Get the actual selected items, not just their IDs
  const selectedItemsList = items.filter(item => selectedItems.has(item.id));
  
  return (
    <div className="space-y-2">
      {items.map((item) => (
        <AvailableItemCard
          key={item.id}
          item={item}
          isSelected={selectedItems.has(item.id)}
          onSelect={() => onSelectItem(item.id)}
          selectedItems={selectedItemsList}
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
  onBulkSelect: (itemIds: number[], selected: boolean) => void;
  onBulkAssign: (sectionId: number, itemIds: number[]) => void;
  isCollapsed: boolean;
  onToggleCollapsed: (collapsed: boolean) => void;
}

export function ItemLibrarySidebar({
  availableItems,
  sections,
  selectedAvailableItems,
  onSelectItem,
  onBulkSelect,
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
    // Check if all currently filtered items are selected
    const allFilteredSelected = filteredItems.every(item => selectedAvailableItems.has(item.id));
    const filteredItemIds = filteredItems.map(item => item.id);
    
    // Use bulk select for instant selection/deselection
    onBulkSelect(filteredItemIds, !allFilteredSelected);
  };

  const handleBulkAssignToSection = (sectionId: number) => {
    const itemIds = Array.from(selectedAvailableItems);
    onBulkAssign(sectionId, itemIds);
  };

  if (isCollapsed) {
    return (
      <div className="flex h-full flex-col bg-gray-50 relative">
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
                    selectedItems={filteredItems.filter(i => selectedAvailableItems.has(i.id))}
                  />
                ))}
              </div>
            )}
          </div>
        </div>

        {/* Floating action area for bulk assign */}
        {selectedAvailableItems.size > 0 && (
          <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-20">
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button 
                  size="sm" 
                  className="h-8 px-3 bg-gray-900 hover:bg-gray-800 text-white shadow-lg animate-in slide-in-from-bottom-2"
                >
                  <Layers className="h-3.5 w-3.5 mr-1" />
                  <span className="text-xs font-medium">{selectedAvailableItems.size}</span>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent side="top" align="center" className="w-64 max-h-96 overflow-y-auto">
                {/* Show selected items */}
                <div className="px-2 py-2 border-b">
                  <p className="text-xs font-medium text-muted-foreground mb-2">Selected items:</p>
                  <div className="space-y-1 max-h-32 overflow-y-auto">
                    {filteredItems
                      .filter(item => selectedAvailableItems.has(item.id))
                      .map(item => (
                        <div key={item.id} className="flex items-center gap-2 text-xs">
                          <div className="h-1.5 w-1.5 bg-gray-400 rounded-full" />
                          <span className="truncate flex-1">{item.name}</span>
                          {item.price && (
                            <span className="text-muted-foreground">
                              {formatCurrency(item.price)}
                            </span>
                          )}
                        </div>
                      ))
                    }
                  </div>
                </div>
                
                <DropdownMenuLabel className="text-xs">Assign to Section</DropdownMenuLabel>
                <DropdownMenuSeparator />
                {sections.length === 0 ? (
                  <div className="px-2 py-1.5 text-xs text-muted-foreground">No sections available</div>
                ) : (
                  sections.map((section) => (
                    <DropdownMenuItem 
                      key={section.id} 
                      onClick={() => handleBulkAssignToSection(section.id)}
                      className="text-sm"
                    >
                      <span className="truncate">{section.name}</span>
                      <Badge variant="outline" className="ml-auto text-xs">
                        {section.items.length}
                      </Badge>
                    </DropdownMenuItem>
                  ))
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        )}
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
            {filteredItems.every(item => selectedAvailableItems.has(item.id)) ? 'Deselect All' : 'Select All'}
          </Button>
        </div>
      </div>
      <div className="relative h-full">
        <div className="absolute inset-0 overflow-y-auto px-4 py-2">
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
            <ItemList items={filteredItems} selectedItems={selectedAvailableItems} onSelectItem={onSelectItem} />
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
  selectedItems,
}: { 
  item: AvailableItem; 
  isSelected: boolean;
  onSelect: () => void;
  selectedItems: AvailableItem[];
}) {
  // If this item is selected and we're dragging, include all selected items
  const itemsToMove = isSelected ? selectedItems : [item];
  
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `available-collapsed-${item.id}`,
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
    <TooltipProvider delayDuration={300}>
      <Tooltip>
        <TooltipTrigger asChild>
          <div
            ref={setNodeRef}
            style={style}
            className={cn(
              'group relative h-12 w-12 cursor-move rounded-lg transition-all touch-none',
              'hover:ring-2 hover:ring-gray-400 hover:ring-offset-1',
              isSelected && 'ring-2 ring-gray-900 ring-offset-1',
              isDragging && 'opacity-30 scale-105',
            )}
          >
            {/* Main draggable area */}
            <div
              {...attributes}
              {...listeners}
              className="relative h-full w-full"
            >
              {/* Item image/icon */}
              {item.imageUrl ? (
                <img 
                  src={item.imageUrl} 
                  alt={item.name} 
                  className="h-full w-full rounded-lg object-cover pointer-events-none" 
                />
              ) : (
                <div className="flex h-full w-full items-center justify-center rounded-lg bg-gray-100 pointer-events-none">
                  <Package className="h-6 w-6 text-gray-400" />
                </div>
              )}
              
              {/* Drag indicator overlay */}
              <div className="absolute inset-0 rounded-lg bg-black/0 group-hover:bg-black/10 transition-colors flex items-center justify-center pointer-events-none">
                <GripVertical className="h-4 w-4 text-white drop-shadow-md opacity-0 group-hover:opacity-80 transition-opacity" />
              </div>
            </div>

            {/* Selection checkbox - outside drag area */}
            <div 
              className="absolute -top-1.5 -right-1.5 z-10"
              onClick={(e) => {
                e.stopPropagation();
                e.preventDefault();
                onSelect();
              }}
            >
              <div className={cn(
                "h-5 w-5 rounded-full border-2 bg-white cursor-pointer transition-all",
                "hover:scale-110 hover:shadow-md",
                isSelected 
                  ? "bg-gray-900 border-gray-900" 
                  : "border-gray-300 hover:border-gray-600"
              )}>
                {isSelected && (
                  <svg className="h-full w-full text-white p-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                  </svg>
                )}
              </div>
            </div>
          </div>
        </TooltipTrigger>
        <TooltipContent side="left" className="max-w-xs">
          <div className="space-y-1">
            <div className="font-medium text-sm">{item.name}</div>
            {item.description && (
              <div className="text-xs text-muted-foreground line-clamp-2">{item.description}</div>
            )}
            <div className="flex items-center gap-2 pt-1">
              <span className="text-sm font-semibold text-blue-600">
                {item.price ? formatCurrency(item.price) : '—'}
              </span>
              {item.category && (
                <Badge variant="outline" className="text-xs">
                  {item.category}
                </Badge>
              )}
            </div>
            <div className="text-xs text-muted-foreground pt-1 border-t">
              Drag to section or click circle to select
            </div>
          </div>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
