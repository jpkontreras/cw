import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { formatCurrency } from '../utils/utils';
import { Clock, Flame, Info, Leaf, Minus, Plus, Star, TrendingUp } from 'lucide-react';
import { useState } from 'react';
import { MenuPlaceholder } from './menu-placeholder';
import type { ViewMode } from './view-mode-toggle';

interface MenuItem {
  id: number;
  name: string;
  price: number;
  category: string;
  description?: string;
  image?: string;
  preparationTime?: number;
  spicyLevel?: number;
  isVegetarian?: boolean;
  calories?: number;
  rating?: number;
  modifiers?: Array<{
    id: number;
    name: string;
    price: number;
    description?: string;
  }>;
}

interface Props {
  item: MenuItem;
  viewMode: ViewMode;
  quantity: number;
  selectedModifiers: number[];
  notes?: string;
  isActive: boolean;
  onAdd: () => void;
  onUpdateQuantity: (quantity: number) => void;
  onToggleModifier: (modifierId: number) => void;
  onUpdateNotes: (notes: string) => void;
  onClick: () => void;
}

export function MenuItemCard({
  item,
  viewMode,
  quantity,
  selectedModifiers,
  notes = '',
  isActive,
  onAdd,
  onUpdateQuantity,
  onToggleModifier,
  onUpdateNotes,
  onClick,
}: Props) {
  const [showNotes, setShowNotes] = useState(false);

  // Render spicy level indicators
  const renderSpicyLevel = () => {
    if (!item.spicyLevel) return null;
    return (
      <div className="flex items-center gap-0.5">
        {[...Array(3)].map((_, i) => (
          <Flame key={i} className={cn('h-3 w-3', i < (item.spicyLevel || 0) ? 'fill-orange-500 text-orange-500' : 'text-gray-300')} />
        ))}
      </div>
    );
  };

  // Compact View
  if (viewMode === 'compact') {
    return (
      <div
        className={cn(
          'group flex items-center gap-4 rounded-xl border-2 bg-white p-4 transition-all duration-200',
          quantity > 0 ? 'border-primary/20 bg-primary/5' : 'border-gray-100 hover:border-gray-200 hover:shadow-sm',
        )}
      >
        {/* Image */}
        <div className="group h-16 w-16 flex-shrink-0 overflow-hidden rounded-lg">
          {item.image ? (
            <img src={item.image} alt={item.name} className="h-full w-full object-cover" />
          ) : (
            <MenuPlaceholder size="sm" variant={item.id} />
          )}
        </div>

        {/* Content */}
        <div className="min-w-0 flex-1">
          <div className="flex items-start justify-between gap-2">
            <div className="flex-1">
              <h4 className="truncate font-medium text-gray-900">{item.name}</h4>
              <div className="mt-0.5 flex items-center gap-3">
                <span className="text-lg font-semibold text-primary">{formatCurrency(item.price)}</span>
                {item.spicyLevel && renderSpicyLevel()}
                {item.isVegetarian && <Leaf className="h-4 w-4 text-green-600" />}
              </div>
            </div>
          </div>
        </div>

        {/* Actions */}
        <div className="flex-shrink-0">
          {quantity > 0 ? (
            <div className="flex items-center gap-1">
              <Button
                type="button"
                size="icon"
                variant="outline"
                className="h-8 w-8"
                onClick={(e) => {
                  e.stopPropagation();
                  onUpdateQuantity(quantity - 1);
                }}
              >
                <Minus className="h-3 w-3" />
              </Button>
              <span className="w-8 text-center font-medium">{quantity}</span>
              <Button
                type="button"
                size="icon"
                variant="outline"
                className="h-8 w-8"
                onClick={(e) => {
                  e.stopPropagation();
                  onUpdateQuantity(quantity + 1);
                }}
              >
                <Plus className="h-3 w-3" />
              </Button>
            </div>
          ) : (
            <Button
              type="button"
              size="sm"
              onClick={(e) => {
                e.stopPropagation();
                onAdd();
              }}
            >
              <Plus className="mr-1 h-3 w-3" />
              Add
            </Button>
          )}
        </div>
      </div>
    );
  }

  // Standard View
  if (viewMode === 'standard') {
    return (
      <Card
        className={cn(
          'group relative cursor-pointer overflow-hidden py-0 transition-all duration-300',
          isActive && 'shadow-xl ring-2 ring-primary z-20',
          quantity > 0 && !isActive && 'border-primary/30 shadow-md',
          !quantity && 'hover:-translate-y-1 hover:shadow-lg',
        )}
        onClick={onClick}
      >
        {/* Image Section */}
        <div className="group relative h-48 overflow-hidden bg-gray-100">
          {item.image ? (
            <img src={item.image} alt={item.name} className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" />
          ) : (
            <MenuPlaceholder size="lg" variant={item.id} className="h-full w-full scale-110" />
          )}

          {/* Gradient overlay for better badge visibility */}
          <div className="absolute inset-0 bg-gradient-to-b from-black/40 via-transparent to-black/20" />

          {/* Badges */}
          <div className="absolute top-3 left-3 flex flex-wrap gap-2">
            {item.spicyLevel && (
              <Badge className="border-0 bg-orange-500 px-2 py-0.5 text-xs font-medium text-white shadow-sm">{renderSpicyLevel()}</Badge>
            )}
            {item.isVegetarian && (
              <Badge className="border-0 bg-green-600 px-2 py-0.5 text-xs font-medium text-white shadow-sm">
                <Leaf className="mr-1 h-3 w-3" />
                Veg
              </Badge>
            )}
          </div>

          {/* Rating */}
          {item.rating && (
            <div className="absolute top-3 right-3">
              <Badge className="border-0 bg-white px-2 py-0.5 text-xs font-semibold text-gray-900 shadow-sm">
                <Star className="mr-0.5 h-3 w-3 fill-yellow-500 text-yellow-500" />
                {item.rating.toFixed(1)}
              </Badge>
            </div>
          )}

          {/* Popular indicator */}
          {item.rating && item.rating >= 4.5 && (
            <div className="absolute bottom-3 left-3">
              <Badge className="border-0 bg-gradient-to-r from-purple-600 to-pink-600 px-2.5 py-0.5 text-xs font-medium text-white shadow-sm">
                <TrendingUp className="mr-1 h-3 w-3" />
                Popular
              </Badge>
            </div>
          )}
        </div>

        {/* Content */}
        <CardContent className="flex flex-col p-0">
          <div className="px-4 pt-3 pb-3">
            <h3 className="text-lg leading-tight font-semibold text-gray-900">{item.name}</h3>
            <div className="mt-1">
              <span className="text-2xl font-bold text-gray-900">{formatCurrency(item.price)}</span>
            </div>
            {item.description && <p className="mt-2 line-clamp-2 text-sm leading-relaxed text-gray-600">{item.description}</p>}

            {/* Meta info */}
            {(item.preparationTime || item.calories) && (
              <div className="mt-2 flex items-center gap-4 text-sm text-gray-500">
                {item.preparationTime && (
                  <div className="flex items-center gap-1">
                    <Clock className="h-3.5 w-3.5" />
                    <span>{item.preparationTime} min</span>
                  </div>
                )}
                {item.calories && (
                  <div className="flex items-center gap-1">
                    <Info className="h-3.5 w-3.5" />
                    <span>{item.calories} cal</span>
                  </div>
                )}
              </div>
            )}
          </div>

          {/* Actions */}
          <div className="mt-auto">
            {quantity > 0 ? (
              <div className="flex items-center">
                <button
                  type="button"
                  className="flex h-12 flex-1 items-center justify-center bg-gray-100 text-gray-700 transition-all hover:bg-gray-200"
                  onClick={(e) => {
                    e.stopPropagation();
                    onUpdateQuantity(quantity - 1);
                  }}
                >
                  <Minus className="h-4 w-4" />
                </button>
                <span className="flex h-12 min-w-[3rem] items-center justify-center bg-gray-50 text-lg font-semibold tabular-nums">{quantity}</span>
                <button
                  type="button"
                  className="flex h-12 flex-1 items-center justify-center bg-gray-100 text-gray-700 transition-all hover:bg-gray-200"
                  onClick={(e) => {
                    e.stopPropagation();
                    onUpdateQuantity(quantity + 1);
                  }}
                >
                  <Plus className="h-4 w-4" />
                </button>
              </div>
            ) : (
              <button
                type="button"
                className="flex h-12 w-full items-center justify-center gap-2 bg-gray-900 text-white transition-colors hover:bg-gray-800"
                onClick={(e) => {
                  e.stopPropagation();
                  onAdd();
                }}
              >
                <Plus className="h-4 w-4" />
                <span className="text-sm font-medium">Add to Order</span>
              </button>
            )}
          </div>

          {/* Modifiers - Only show when item is added and expanded */}
          {quantity > 0 && item.modifiers && item.modifiers.length > 0 && isActive && (
            <div className="space-y-2 border-t px-4 pt-3">
              <p className="text-sm font-medium text-gray-700">Customize:</p>
              <div className="space-y-2">
                {item.modifiers.map((modifier) => (
                  <label
                    key={modifier.id}
                    className="group/mod flex cursor-pointer items-start gap-3 rounded-lg p-2 transition-colors hover:bg-gray-50"
                    onClick={(e) => e.stopPropagation()}
                  >
                    <Checkbox
                      checked={selectedModifiers.includes(modifier.id)}
                      onCheckedChange={() => onToggleModifier(modifier.id)}
                      className="mt-0.5"
                    />
                    <div className="flex-1">
                      <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">{modifier.name}</span>
                        <span className="text-sm font-medium text-primary">+{formatCurrency(modifier.price)}</span>
                      </div>
                      {modifier.description && <p className="mt-0.5 text-xs text-gray-500">{modifier.description}</p>}
                    </div>
                  </label>
                ))}
              </div>
            </div>
          )}

          {/* Special Instructions */}
          {quantity > 0 && isActive && (
            <div className="px-4 pt-2 pb-3" onClick={(e) => e.stopPropagation()}>
              <Button type="button" variant="ghost" size="sm" className="w-full justify-start text-sm" onClick={() => setShowNotes(!showNotes)}>
                {showNotes ? 'Hide' : 'Add'} special instructions
              </Button>
              {showNotes && (
                <Input
                  placeholder="e.g., no onions, extra sauce..."
                  value={notes}
                  onChange={(e) => onUpdateNotes(e.target.value)}
                  className="mt-2 text-sm"
                />
              )}
            </div>
          )}
        </CardContent>
      </Card>
    );
  }

  // Return standard view as default
  return null; // This should not happen as we removed detailed mode
}
