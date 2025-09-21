import React from 'react';
import { Card } from '@/components/ui/card';
import { Check, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';

interface VariantOption {
  name: string;
  description?: string;
  price?: number;
  selected?: boolean;
}

interface AiVariantSelectorProps {
  title: string;
  options: VariantOption[];
  onSelect: (option: VariantOption) => void;
  multiple?: boolean;
}

export function AiVariantSelector({ title, options, onSelect }: AiVariantSelectorProps) {
  return (
    <div className="space-y-3">
      <h3 className="text-sm font-semibold text-muted-foreground uppercase tracking-wide">{title}</h3>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        {options.map((option) => (
          <Card
            key={option.name}
            className={cn(
              "relative cursor-pointer transition-all hover:shadow-md",
              "border-2",
              option.selected ? "border-primary bg-primary/5" : "border-transparent hover:border-gray-300"
            )}
            onClick={() => onSelect(option)}
          >
            <div className="p-4">
              <div className="flex items-start justify-between">
                <div className="flex-1">
                  <h4 className="font-medium text-sm">{option.name}</h4>
                  {option.description && (
                    <p className="text-xs text-muted-foreground mt-1">{option.description}</p>
                  )}
                  {option.price !== undefined && (
                    <p className="text-sm font-semibold text-primary mt-2">
                      CLP {option.price.toLocaleString()}
                    </p>
                  )}
                </div>
                <div className={cn(
                  "w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all",
                  option.selected
                    ? "bg-primary border-primary"
                    : "border-gray-300 hover:border-primary"
                )}>
                  {option.selected ? (
                    <Check className="h-3 w-3 text-primary-foreground" />
                  ) : (
                    <Plus className="h-3 w-3 text-gray-400" />
                  )}
                </div>
              </div>
            </div>
          </Card>
        ))}
      </div>
    </div>
  );
}