import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Plus, X } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Modifier {
  id: string;
  name: string;
  price: number;
  category?: string;
  selected?: boolean;
}

interface AiModifierGroupProps {
  title: string;
  modifiers: Modifier[];
  onToggle: (modifier: Modifier) => void;
  maxSelections?: number;
  required?: boolean;
}

export function AiModifierGroup({
  title,
  modifiers,
  onToggle,
  maxSelections,
  required = false
}: AiModifierGroupProps) {
  const groupedModifiers = modifiers.reduce((acc, mod) => {
    const category = mod.category || 'Other';
    if (!acc[category]) acc[category] = [];
    acc[category].push(mod);
    return acc;
  }, {} as Record<string, Modifier[]>);

  const selectedCount = modifiers.filter(m => m.selected).length;

  return (
    <div className="space-y-3">
      <div className="flex items-center justify-between">
        <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
          {title}
          {required && <span className="text-red-500 ml-1">*</span>}
        </h3>
        {maxSelections && (
          <Badge variant="secondary" className="text-xs">
            {selectedCount}/{maxSelections} selected
          </Badge>
        )}
      </div>

      <div className="space-y-3">
        {Object.entries(groupedModifiers).map(([category, items]) => (
          <div key={category}>
            {Object.keys(groupedModifiers).length > 1 && (
              <p className="text-xs font-medium text-muted-foreground mb-2">{category}</p>
            )}
            <div className="flex flex-wrap gap-2">
              {items.map((modifier) => (
                <Button
                  key={modifier.id}
                  variant={modifier.selected ? "default" : "outline"}
                  size="sm"
                  className={cn(
                    "h-auto py-1.5 px-3 font-normal",
                    modifier.selected && "pr-2"
                  )}
                  onClick={() => onToggle(modifier)}
                  disabled={!modifier.selected && maxSelections !== undefined && selectedCount >= maxSelections}
                >
                  {modifier.selected ? (
                    <>
                      <X className="h-3 w-3 mr-1" />
                      {modifier.name}
                    </>
                  ) : (
                    <>
                      <Plus className="h-3 w-3 mr-1" />
                      {modifier.name}
                    </>
                  )}
                  {modifier.price > 0 && (
                    <Badge variant="secondary" className="ml-2 px-1 py-0 text-xs">
                      +${modifier.price}
                    </Badge>
                  )}
                </Button>
              ))}
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}