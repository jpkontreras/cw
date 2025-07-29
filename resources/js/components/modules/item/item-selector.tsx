import { useState, useEffect } from 'react';
import { Check, ChevronsUpDown, Search, Package } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { Badge } from '@/components/ui/badge';
import { formatCurrency } from '@/lib/format';

interface Item {
  id: number;
  name: string;
  sku: string | null;
  type: 'product' | 'service' | 'combo';
  base_price: number;
  is_available: boolean;
  variants?: Array<{
    id: number;
    name: string;
    sku: string | null;
    price: number;
  }>;
}

interface ItemSelectorProps {
  value?: number;
  onValueChange: (value: number | undefined) => void;
  items?: Item[];
  onSearch?: (query: string) => Promise<Item[]>;
  placeholder?: string;
  includeVariants?: boolean;
  showPrice?: boolean;
  showSku?: boolean;
  filterAvailable?: boolean;
  className?: string;
  disabled?: boolean;
}

export function ItemSelector({
  value,
  onValueChange,
  items: initialItems = [],
  onSearch,
  placeholder = "Select item...",
  includeVariants = false,
  showPrice = true,
  showSku = true,
  filterAvailable = true,
  className,
  disabled = false,
}: ItemSelectorProps) {
  const [open, setOpen] = useState(false);
  const [items, setItems] = useState<Item[]>(initialItems);
  const [searching, setSearching] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  useEffect(() => {
    setItems(initialItems);
  }, [initialItems]);

  const handleSearch = async (query: string) => {
    setSearchQuery(query);
    
    if (onSearch && query.length > 0) {
      setSearching(true);
      try {
        const results = await onSearch(query);
        setItems(results);
      } catch (error) {
        console.error('Search error:', error);
      } finally {
        setSearching(false);
      }
    } else if (query.length === 0) {
      setItems(initialItems);
    }
  };

  const filteredItems = filterAvailable 
    ? items.filter(item => item.is_available)
    : items;

  const selectedItem = items.find(item => item.id === value);

  const typeStyles = {
    product: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    service: 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
    combo: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn("w-full justify-between", className)}
          disabled={disabled}
        >
          {selectedItem ? (
            <div className="flex items-center gap-2 truncate">
              <Package className="h-4 w-4 shrink-0" />
              <span className="truncate">{selectedItem.name}</span>
              {showSku && selectedItem.sku && (
                <Badge variant="secondary" className="text-xs shrink-0">
                  {selectedItem.sku}
                </Badge>
              )}
            </div>
          ) : (
            <span className="text-muted-foreground">{placeholder}</span>
          )}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[400px] p-0" align="start">
        <Command shouldFilter={false}>
          <CommandInput 
            placeholder="Search items..." 
            onValueChange={handleSearch}
            value={searchQuery}
          />
          <CommandList>
            <CommandEmpty>
              {searching ? "Searching..." : "No items found."}
            </CommandEmpty>
            <CommandGroup>
              {filteredItems.map((item) => (
                <CommandItem
                  key={item.id}
                  value={item.id.toString()}
                  onSelect={() => {
                    onValueChange(item.id === value ? undefined : item.id);
                    setOpen(false);
                  }}
                >
                  <Check
                    className={cn(
                      "mr-2 h-4 w-4",
                      value === item.id ? "opacity-100" : "opacity-0"
                    )}
                  />
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <span className="font-medium">{item.name}</span>
                      <Badge 
                        variant="secondary" 
                        className={cn('text-xs', typeStyles[item.type])}
                      >
                        {item.type}
                      </Badge>
                    </div>
                    <div className="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                      {showSku && item.sku && (
                        <span>SKU: {item.sku}</span>
                      )}
                      {showPrice && (
                        <span>{formatCurrency(item.base_price)}</span>
                      )}
                    </div>
                  </div>
                </CommandItem>
              ))}
              
              {includeVariants && filteredItems.map((item) => 
                item.variants?.map((variant) => (
                  <CommandItem
                    key={`${item.id}-${variant.id}`}
                    value={`${item.id}-${variant.id}`}
                    onSelect={() => {
                      // Handle variant selection
                      setOpen(false);
                    }}
                    className="pl-8"
                  >
                    <Check
                      className={cn(
                        "mr-2 h-4 w-4",
                        false ? "opacity-100" : "opacity-0"
                      )}
                    />
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <span>{item.name} - {variant.name}</span>
                      </div>
                      <div className="flex items-center gap-3 text-xs text-muted-foreground mt-0.5">
                        {showSku && variant.sku && (
                          <span>SKU: {variant.sku}</span>
                        )}
                        {showPrice && (
                          <span>{formatCurrency(variant.price)}</span>
                        )}
                      </div>
                    </div>
                  </CommandItem>
                ))
              )}
            </CommandGroup>
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}