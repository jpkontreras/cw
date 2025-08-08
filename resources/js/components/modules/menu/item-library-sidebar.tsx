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
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatCurrency } from '@/lib/format';
import { cn } from '@/lib/utils';
import { Layers, Package, PanelRightClose, Plus, Search } from 'lucide-react';
import { useMemo, useState } from 'react';
import { toast } from 'sonner';
import { AvailableItemCard } from './available-item-card';
import { type AvailableItem, type MenuSection } from './types';

interface ItemLibrarySidebarProps {
  availableItems: AvailableItem[];
  sections: MenuSection[];
  selectedAvailableItems: Set<number>;
  onSelectItem: (itemId: number) => void;
  onBulkAssign: (sectionId: number, itemIds: number[]) => void;
  onQuickAdd: (item: AvailableItem) => void;
  isCollapsed: boolean;
  onToggleCollapsed: (collapsed: boolean) => void;
}

export function ItemLibrarySidebar({
  availableItems,
  sections,
  selectedAvailableItems,
  onSelectItem,
  onBulkAssign,
  onQuickAdd,
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

  const handleQuickAdd = (item: AvailableItem) => {
    if (sections.length > 0) {
      onQuickAdd(item);
    } else {
      toast.error('Please add a section first');
    }
  };

  if (isCollapsed) {
    return (
      <div className="flex h-full flex-col overflow-hidden py-2">
        <div className="mb-2 flex-shrink-0 px-2 text-center">
          <Button variant="ghost" size="icon" className="mb-1 h-8 w-8" onClick={() => onToggleCollapsed(false)}>
            <PanelRightClose className="h-4 w-4 rotate-180" />
          </Button>
          <Badge variant="secondary" className="text-xs">
            {filteredItems.length}
          </Badge>
        </div>

        <div className="relative h-full px-0.5">
          <div className="absolute inset-0 overflow-y-scroll">
            <ScrollArea>
              <div className="space-y-2">
                {filteredItems.map((item) => (
                  <Popover key={item.id}>
                    <PopoverTrigger asChild>
                      <div
                        className={cn(
                          'group relative cursor-move rounded-lg p-1 transition-colors hover:bg-gray-100',
                          selectedAvailableItems.has(item.id) && 'bg-blue-100',
                        )}
                        draggable
                        onDragStart={(e) => {
                          e.dataTransfer.setData('item', JSON.stringify(item));
                        }}
                        onClick={() => onSelectItem(item.id)}
                      >
                        {item.imageUrl ? (
                          <img src={item.imageUrl} alt={item.name} className="mx-auto h-10 w-10 rounded object-cover" />
                        ) : (
                          <div className="mx-auto flex h-10 w-10 items-center justify-center rounded bg-gray-200">
                            <Package className="h-6 w-6 text-gray-500" />
                          </div>
                        )}
                        {selectedAvailableItems.has(item.id) && <div className="absolute top-0 right-0 h-2 w-2 rounded-full bg-blue-500" />}
                      </div>
                    </PopoverTrigger>
                    <PopoverContent side="left" className="w-64">
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
                        <Button size="sm" className="w-full" onClick={() => handleQuickAdd(item)}>
                          <Plus className="mr-2 h-3 w-3" />
                          Add to Menu
                        </Button>
                      </div>
                    </PopoverContent>
                  </Popover>
                ))}
              </div>
            </ScrollArea>
          </div>
        </div>

        <div className="min-h-0 flex-1 overflow-y-auto px-1">
          <div className="space-y-1"></div>
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
          <ScrollArea>
            <div className="space-y-2">
              {filteredItems.map((item) => (
                <AvailableItemCard
                  key={item.id}
                  item={item}
                  isSelected={selectedAvailableItems.has(item.id)}
                  onSelect={() => onSelectItem(item.id)}
                  onQuickAdd={() => handleQuickAdd(item)}
                />
              ))}
            </div>
          </ScrollArea>
        </div>
      </div>
    </div>
  );
}
