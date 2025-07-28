import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn, formatCurrency } from '@/lib/utils';
import { Check, Search } from 'lucide-react';
import { useState } from 'react';

interface Item {
  id: number;
  name: string;
  base_price: number;
  is_active: boolean;
  category?: {
    id: number;
    name: string;
  };
}

interface ItemSelectorProps {
  items: Item[];
  value?: number;
  onSelect: (itemId: number) => void;
  placeholder?: string;
  disabled?: boolean;
}

export function ItemSelector({ items, value, onSelect, placeholder = 'Select an item...', disabled = false }: ItemSelectorProps) {
  const [open, setOpen] = useState(false);
  const [search, setSearch] = useState('');

  const selectedItem = items.find((item) => item.id === value);

  const filteredItems = items.filter((item) => {
    if (!item.is_active) return false;

    const searchLower = search.toLowerCase();
    return item.name.toLowerCase().includes(searchLower) || item.category?.name.toLowerCase().includes(searchLower);
  });

  const groupedItems = filteredItems.reduce(
    (acc, item) => {
      const category = item.category?.name || 'Uncategorized';
      if (!acc[category]) {
        acc[category] = [];
      }
      acc[category].push(item);
      return acc;
    },
    {} as Record<string, Item[]>,
  );

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button variant="outline" role="combobox" aria-expanded={open} className="w-full justify-between" disabled={disabled}>
          {selectedItem ? (
            <div className="flex w-full items-center justify-between">
              <span>{selectedItem.name}</span>
              <span className="text-muted-foreground">{formatCurrency(selectedItem.base_price)}</span>
            </div>
          ) : (
            <span className="text-muted-foreground">{placeholder}</span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[400px] p-0" align="start">
        <Command>
          <div className="flex items-center border-b px-3">
            <Search className="mr-2 h-4 w-4 shrink-0 opacity-50" />
            <input
              className="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              placeholder="Search items..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />
          </div>
          <CommandList>
            {filteredItems.length === 0 && <CommandEmpty>No items found.</CommandEmpty>}
            {Object.entries(groupedItems).map(([category, categoryItems]) => (
              <CommandGroup key={category} heading={category}>
                {categoryItems.map((item) => (
                  <CommandItem
                    key={item.id}
                    value={item.id.toString()}
                    onSelect={() => {
                      onSelect(item.id);
                      setOpen(false);
                    }}
                  >
                    <Check className={cn('mr-2 h-4 w-4', value === item.id ? 'opacity-100' : 'opacity-0')} />
                    <div className="flex w-full items-center justify-between">
                      <span>{item.name}</span>
                      <span className="text-sm text-muted-foreground">{formatCurrency(item.base_price)}</span>
                    </div>
                  </CommandItem>
                ))}
              </CommandGroup>
            ))}
          </CommandList>
        </Command>
      </PopoverContent>
    </Popover>
  );
}
