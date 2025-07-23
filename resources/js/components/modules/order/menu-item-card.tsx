import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { 
    Plus, 
    Minus, 
    Clock,
    Flame,
    Leaf,
    Info,
    Star,
    TrendingUp
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency } from '@/types/modules/order/utils';
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
    onClick
}: Props) {
    const [showNotes, setShowNotes] = useState(false);
    
    // Calculate total price including modifiers
    const modifierPrice = selectedModifiers.reduce((sum, modId) => {
        const modifier = item.modifiers?.find(m => m.id === modId);
        return sum + (modifier?.price || 0);
    }, 0);
    const totalPrice = (item.price + modifierPrice) * (quantity || 1);

    // Render spicy level indicators
    const renderSpicyLevel = () => {
        if (!item.spicyLevel) return null;
        return (
            <div className="flex items-center gap-0.5">
                {[...Array(3)].map((_, i) => (
                    <Flame 
                        key={i} 
                        className={cn(
                            "w-3 h-3",
                            i < item.spicyLevel 
                                ? "text-orange-500 fill-orange-500" 
                                : "text-gray-300"
                        )} 
                    />
                ))}
            </div>
        );
    };

    // Compact View
    if (viewMode === 'compact') {
        return (
            <div className={cn(
                "group flex items-center gap-4 p-4 bg-white rounded-xl border-2 transition-all duration-200",
                quantity > 0 
                    ? "border-primary/20 bg-primary/5" 
                    : "border-gray-100 hover:border-gray-200 hover:shadow-sm"
            )}>
                {/* Image */}
                <div className="w-16 h-16 rounded-lg overflow-hidden flex-shrink-0 group">
                    {item.image ? (
                        <img 
                            src={item.image} 
                            alt={item.name}
                            className="w-full h-full object-cover"
                        />
                    ) : (
                        <MenuPlaceholder size="sm" variant={item.id} />
                    )}
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between gap-2">
                        <div className="flex-1">
                            <h4 className="font-medium text-gray-900 truncate">{item.name}</h4>
                            <div className="flex items-center gap-3 mt-0.5">
                                <span className="text-lg font-semibold text-primary">
                                    {formatCurrency(item.price)}
                                </span>
                                {item.spicyLevel && renderSpicyLevel()}
                                {item.isVegetarian && (
                                    <Leaf className="w-4 h-4 text-green-600" />
                                )}
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
                                <Minus className="w-3 h-3" />
                            </Button>
                            <span className="w-8 text-center font-medium">
                                {quantity}
                            </span>
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
                                <Plus className="w-3 h-3" />
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
                            <Plus className="w-3 h-3 mr-1" />
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
                    "group overflow-hidden transition-all duration-200 cursor-pointer",
                    isActive && "ring-2 ring-primary shadow-lg",
                    quantity > 0 && "border-primary/20 bg-primary/5"
                )}
                onClick={onClick}
            >
                {/* Image Section */}
                <div className="relative h-48 group overflow-hidden">
                    {item.image ? (
                        <img 
                            src={item.image} 
                            alt={item.name}
                            className="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                    ) : (
                        <MenuPlaceholder size="lg" variant={item.id} className="scale-110" />
                    )}
                    
                    {/* Gradient overlay for better badge visibility */}
                    <div className="absolute inset-0 bg-gradient-to-b from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                    
                    {/* Badges */}
                    <div className="absolute top-2 left-2 flex flex-wrap gap-1">
                        {item.spicyLevel && (
                            <Badge className="bg-orange-500/90 backdrop-blur-sm text-white border-0 shadow-md">
                                {renderSpicyLevel()}
                            </Badge>
                        )}
                        {item.isVegetarian && (
                            <Badge className="bg-green-600/90 backdrop-blur-sm text-white border-0 shadow-md">
                                <Leaf className="w-3 h-3 mr-1" />
                                Veg
                            </Badge>
                        )}
                    </div>
                    
                    {/* Rating */}
                    {item.rating && (
                        <div className="absolute top-2 right-2">
                            <Badge className="bg-white/90 backdrop-blur-sm text-gray-900 border-0 shadow-md">
                                <Star className="w-3 h-3 mr-1 fill-yellow-500 text-yellow-500" />
                                <span className="font-semibold">{item.rating.toFixed(1)}</span>
                            </Badge>
                        </div>
                    )}
                    
                    {/* Popular indicator */}
                    {item.rating && item.rating >= 4.5 && (
                        <div className="absolute bottom-2 left-2">
                            <Badge className="bg-gradient-to-r from-purple-600 to-pink-600 text-white border-0 shadow-md">
                                <TrendingUp className="w-3 h-3 mr-1" />
                                Popular
                            </Badge>
                        </div>
                    )}
                </div>

                {/* Content */}
                <CardContent className="p-4 space-y-3">
                    <div>
                        <h3 className="font-semibold text-lg text-gray-900">{item.name}</h3>
                        {item.description && (
                            <p className="text-sm text-gray-600 mt-1 line-clamp-2">
                                {item.description}
                            </p>
                        )}
                    </div>

                    {/* Meta info */}
                    <div className="flex items-center gap-4 text-sm text-gray-500">
                        {item.preparationTime && (
                            <div className="flex items-center gap-1">
                                <Clock className="w-3.5 h-3.5" />
                                <span>{item.preparationTime} min</span>
                            </div>
                        )}
                        {item.calories && (
                            <div className="flex items-center gap-1">
                                <Info className="w-3.5 h-3.5" />
                                <span>{item.calories} cal</span>
                            </div>
                        )}
                    </div>

                    {/* Price and Actions */}
                    <div className="flex items-center justify-between pt-2">
                        <span className="text-2xl font-bold text-primary">
                            {formatCurrency(item.price)}
                        </span>
                        
                        {quantity > 0 ? (
                            <div className="flex items-center gap-2">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    className="h-9 w-9"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        onUpdateQuantity(quantity - 1);
                                    }}
                                >
                                    <Minus className="w-4 h-4" />
                                </Button>
                                <span className="w-8 text-center font-semibold text-lg">
                                    {quantity}
                                </span>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    className="h-9 w-9"
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        onUpdateQuantity(quantity + 1);
                                    }}
                                >
                                    <Plus className="w-4 h-4" />
                                </Button>
                            </div>
                        ) : (
                            <Button
                                type="button"
                                onClick={(e) => {
                                    e.stopPropagation();
                                    onAdd();
                                }}
                            >
                                <Plus className="w-4 h-4 mr-2" />
                                Add to Order
                            </Button>
                        )}
                    </div>

                    {/* Modifiers - Only show when item is added and expanded */}
                    {quantity > 0 && item.modifiers && item.modifiers.length > 0 && isActive && (
                        <div className="pt-3 border-t space-y-2">
                            <p className="text-sm font-medium text-gray-700">Customize:</p>
                            <div className="space-y-2">
                                {item.modifiers.map(modifier => (
                                    <label
                                        key={modifier.id}
                                        className="flex items-start gap-3 cursor-pointer group/mod hover:bg-gray-50 p-2 rounded-lg transition-colors"
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
                                                <span className="text-sm font-medium text-primary">
                                                    +{formatCurrency(modifier.price)}
                                                </span>
                                            </div>
                                            {modifier.description && (
                                                <p className="text-xs text-gray-500 mt-0.5">
                                                    {modifier.description}
                                                </p>
                                            )}
                                        </div>
                                    </label>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Special Instructions */}
                    {quantity > 0 && isActive && (
                        <div className="pt-2" onClick={(e) => e.stopPropagation()}>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="w-full justify-start text-sm"
                                onClick={() => setShowNotes(!showNotes)}
                            >
                                {showNotes ? 'Hide' : 'Add'} special instructions
                            </Button>
                            {showNotes && (
                                <Input
                                    placeholder="e.g., no onions, extra sauce..."
                                    value={notes}
                                    onChange={e => onUpdateNotes(e.target.value)}
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
    return null;  // This should not happen as we removed detailed mode
}