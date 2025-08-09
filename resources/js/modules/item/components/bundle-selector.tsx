import React, { useState, useCallback } from 'react';
import { cn } from '@/lib/utils';
import { Search, Plus, X, Package } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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

interface Item {
  id: number;
  name: string;
  price: number;
  sku?: string;
}

interface BundleItem extends Item {
  quantity: number;
}

interface BundleSelectorProps {
  availableItems?: Item[];
  selectedItems: BundleItem[];
  onItemsChange: (items: BundleItem[]) => void;
  className?: string;
}

export function BundleSelector({
  availableItems = [],
  selectedItems,
  onItemsChange,
  className
}: BundleSelectorProps) {
  const [open, setOpen] = useState(false);
  const [searchValue, setSearchValue] = useState('');

  const filteredItems = availableItems.filter(item => 
    item.name.toLowerCase().includes(searchValue.toLowerCase()) &&
    !selectedItems.some(selected => selected.id === item.id)
  );

  const addItem = (item: Item) => {
    onItemsChange([...selectedItems, { ...item, quantity: 1 }]);
    setSearchValue('');
    setOpen(false);
  };

  const removeItem = (id: number) => {
    onItemsChange(selectedItems.filter(item => item.id !== id));
  };

  const updateQuantity = (id: number, quantity: number) => {
    if (quantity < 1) return;
    onItemsChange(
      selectedItems.map(item =>
        item.id === id ? { ...item, quantity } : item
      )
    );
  };

  const totalPrice = selectedItems.reduce(
    (sum, item) => sum + item.price * item.quantity,
    0
  );

  return (
    <div className={cn("space-y-4", className)}>
      <div className="flex items-center justify-between">
        <h4 className="text-sm font-medium">Bundle Items</h4>
        <Popover open={open} onOpenChange={setOpen}>
          <PopoverTrigger asChild>
            <Button variant="outline" size="sm">
              <Plus className="mr-2 h-4 w-4" />
              Add Item
            </Button>
          </PopoverTrigger>
          <PopoverContent className="w-[300px] p-0" align="end">
            <Command>
              <CommandInput
                placeholder="Search items..."
                value={searchValue}
                onValueChange={setSearchValue}
              />
              <CommandList>
                <CommandEmpty>No items found.</CommandEmpty>
                <CommandGroup>
                  {filteredItems.map((item) => (
                    <CommandItem
                      key={item.id}
                      value={item.name}
                      onSelect={() => addItem(item)}
                    >
                      <div className="flex items-center justify-between w-full">
                        <span>{item.name}</span>
                        <span className="text-sm text-muted-foreground">
                          ${item.price.toFixed(2)}
                        </span>
                      </div>
                    </CommandItem>
                  ))}
                </CommandGroup>
              </CommandList>
            </Command>
          </PopoverContent>
        </Popover>
      </div>

      {selectedItems.length === 0 ? (
        <div className="border-2 border-dashed rounded-lg p-8">
          <div className="flex flex-col items-center justify-center text-center">
            <div className="rounded-full bg-muted p-3 mb-4">
              <Package className="h-6 w-6 text-muted-foreground" />
            </div>
            <h3 className="text-sm font-medium mb-1">No items in bundle yet</h3>
            <p className="text-sm text-muted-foreground">
              Use the search box above to add items to this bundle
            </p>
          </div>
        </div>
      ) : (
        <div className="space-y-2">
          {selectedItems.map((item) => (
            <div
              key={item.id}
              className="flex items-center gap-4 p-3 border rounded-lg bg-card"
            >
              <div className="flex-1">
                <div className="font-medium">{item.name}</div>
                {item.sku && (
                  <div className="text-xs text-muted-foreground">SKU: {item.sku}</div>
                )}
              </div>
              
              <div className="flex items-center gap-2">
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="h-8 w-8 p-0"
                  onClick={() => updateQuantity(item.id, item.quantity - 1)}
                >
                  -
                </Button>
                <span className="w-12 text-center font-medium">
                  {item.quantity}
                </span>
                <Button
                  type="button"
                  variant="outline"
                  size="sm"
                  className="h-8 w-8 p-0"
                  onClick={() => updateQuantity(item.id, item.quantity + 1)}
                >
                  +
                </Button>
              </div>

              <div className="text-sm font-medium">
                ${(item.price * item.quantity).toFixed(2)}
              </div>

              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="h-8 w-8 p-0"
                onClick={() => removeItem(item.id)}
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          ))}
          
          <div className="flex justify-between items-center pt-4 border-t">
            <span className="text-sm text-muted-foreground">
              Total bundle value:
            </span>
            <span className="text-lg font-semibold">
              ${totalPrice.toFixed(2)}
            </span>
          </div>
        </div>
      )}
    </div>
  );
}