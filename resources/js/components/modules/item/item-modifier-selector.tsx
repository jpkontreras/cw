import { useState, useEffect } from 'react';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { 
  Info,
  AlertCircle,
  Check,
  Plus,
  Minus,
  DollarSign,
  Settings,
  ChevronDown,
  ChevronUp
} from 'lucide-react';
import { formatCurrency } from '@/lib/format';

interface Modifier {
  id: number;
  name: string;
  price_adjustment: number;
  is_available: boolean;
  description?: string;
  image_url?: string;
  max_quantity?: number;
}

interface ModifierGroup {
  id: number;
  name: string;
  description?: string;
  min_selections: number;
  max_selections: number | null;
  is_required: boolean;
  modifiers: Modifier[];
}

interface SelectedModifier {
  groupId: number;
  modifierId: number;
  quantity: number;
}

interface ItemModifierSelectorProps {
  modifierGroups: ModifierGroup[];
  selectedModifiers?: SelectedModifier[];
  onModifiersChange?: (modifiers: SelectedModifier[]) => void;
  onPriceChange?: (totalAdjustment: number) => void;
  showPrices?: boolean;
  showDescriptions?: boolean;
  showImages?: boolean;
  variant?: 'default' | 'compact' | 'card';
  className?: string;
}

export function ItemModifierSelector({
  modifierGroups,
  selectedModifiers = [],
  onModifiersChange,
  onPriceChange,
  showPrices = true,
  showDescriptions = true,
  showImages = false,
  variant = 'default',
  className,
}: ItemModifierSelectorProps) {
  const [selections, setSelections] = useState<SelectedModifier[]>(selectedModifiers);
  const [expandedGroups, setExpandedGroups] = useState<number[]>([]);
  const [errors, setErrors] = useState<Record<number, string>>({});

  // Calculate total price adjustment
  useEffect(() => {
    const totalAdjustment = selections.reduce((total, selection) => {
      const group = modifierGroups.find(g => g.id === selection.groupId);
      const modifier = group?.modifiers.find(m => m.id === selection.modifierId);
      return total + (modifier?.price_adjustment || 0) * selection.quantity;
    }, 0);
    
    onPriceChange?.(totalAdjustment);
  }, [selections, modifierGroups, onPriceChange]);

  // Validate selections
  const validateGroup = (groupId: number) => {
    const group = modifierGroups.find(g => g.id === groupId);
    if (!group) return '';

    const groupSelections = selections.filter(s => s.groupId === groupId);
    const totalQuantity = groupSelections.reduce((sum, s) => sum + s.quantity, 0);

    if (group.is_required && totalQuantity < group.min_selections) {
      return `Please select at least ${group.min_selections} option${group.min_selections > 1 ? 's' : ''}`;
    }

    if (group.max_selections && totalQuantity > group.max_selections) {
      return `Please select no more than ${group.max_selections} option${group.max_selections > 1 ? 's' : ''}`;
    }

    return '';
  };

  // Update errors when selections change
  useEffect(() => {
    const newErrors: Record<number, string> = {};
    modifierGroups.forEach(group => {
      const error = validateGroup(group.id);
      if (error) {
        newErrors[group.id] = error;
      }
    });
    setErrors(newErrors);
  }, [selections, modifierGroups]);

  const handleSingleSelection = (groupId: number, modifierId: number) => {
    const newSelections = selections.filter(s => s.groupId !== groupId);
    newSelections.push({ groupId, modifierId, quantity: 1 });
    setSelections(newSelections);
    onModifiersChange?.(newSelections);
  };

  const handleMultipleSelection = (groupId: number, modifierId: number, checked: boolean) => {
    let newSelections = [...selections];
    
    if (checked) {
      const existing = newSelections.find(s => s.groupId === groupId && s.modifierId === modifierId);
      if (existing) {
        existing.quantity = 1;
      } else {
        newSelections.push({ groupId, modifierId, quantity: 1 });
      }
    } else {
      newSelections = newSelections.filter(s => !(s.groupId === groupId && s.modifierId === modifierId));
    }
    
    setSelections(newSelections);
    onModifiersChange?.(newSelections);
  };

  const handleQuantityChange = (groupId: number, modifierId: number, delta: number) => {
    const newSelections = [...selections];
    const selection = newSelections.find(s => s.groupId === groupId && s.modifierId === modifierId);
    
    if (selection) {
      selection.quantity = Math.max(0, selection.quantity + delta);
      if (selection.quantity === 0) {
        const index = newSelections.indexOf(selection);
        newSelections.splice(index, 1);
      }
    } else if (delta > 0) {
      newSelections.push({ groupId, modifierId, quantity: 1 });
    }
    
    setSelections(newSelections);
    onModifiersChange?.(newSelections);
  };

  const toggleGroupExpansion = (groupId: number) => {
    setExpandedGroups(prev => 
      prev.includes(groupId) 
        ? prev.filter(id => id !== groupId)
        : [...prev, groupId]
    );
  };

  const isSelected = (groupId: number, modifierId: number) => {
    return selections.some(s => s.groupId === groupId && s.modifierId === modifierId);
  };

  const getQuantity = (groupId: number, modifierId: number) => {
    const selection = selections.find(s => s.groupId === groupId && s.modifierId === modifierId);
    return selection?.quantity || 0;
  };

  if (variant === 'compact') {
    return (
      <div className={cn('space-y-3', className)}>
        {modifierGroups.map((group) => {
          const error = errors[group.id];
          const isExpanded = expandedGroups.includes(group.id);
          const selectedCount = selections.filter(s => s.groupId === group.id).reduce((sum, s) => sum + s.quantity, 0);
          
          return (
            <div key={group.id} className="border rounded-lg">
              <button
                className="w-full px-4 py-3 flex items-center justify-between hover:bg-muted/50 transition-colors"
                onClick={() => toggleGroupExpansion(group.id)}
              >
                <div className="flex items-center gap-2">
                  <span className="font-medium">{group.name}</span>
                  {group.is_required && (
                    <Badge variant="secondary" className="text-xs">Required</Badge>
                  )}
                  {selectedCount > 0 && (
                    <Badge variant="outline" className="text-xs">
                      {selectedCount} selected
                    </Badge>
                  )}
                </div>
                {isExpanded ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />}
              </button>
              
              {isExpanded && (
                <div className="px-4 pb-3 space-y-2">
                  {error && (
                    <Alert variant="destructive" className="py-2">
                      <AlertCircle className="h-4 w-4" />
                      <AlertDescription className="text-xs">{error}</AlertDescription>
                    </Alert>
                  )}
                  
                  {group.modifiers.filter(m => m.is_available).map((modifier) => {
                    const selected = isSelected(group.id, modifier.id);
                    const quantity = getQuantity(group.id, modifier.id);
                    
                    return (
                      <label
                        key={modifier.id}
                        className={cn(
                          "flex items-center justify-between p-2 rounded-md cursor-pointer transition-colors",
                          selected ? "bg-primary/10" : "hover:bg-muted/50"
                        )}
                      >
                        <div className="flex items-center gap-2">
                          {group.max_selections === 1 ? (
                            <RadioGroup value={selected ? modifier.id.toString() : ''}>
                              <RadioGroupItem
                                value={modifier.id.toString()}
                                onClick={() => handleSingleSelection(group.id, modifier.id)}
                              />
                            </RadioGroup>
                          ) : (
                            <Checkbox
                              checked={selected}
                              onCheckedChange={(checked) => 
                                handleMultipleSelection(group.id, modifier.id, checked as boolean)
                              }
                            />
                          )}
                          <span className="text-sm">{modifier.name}</span>
                        </div>
                        {showPrices && modifier.price_adjustment !== 0 && (
                          <span className="text-sm font-medium">
                            {modifier.price_adjustment > 0 ? '+' : ''}
                            {formatCurrency(modifier.price_adjustment)}
                          </span>
                        )}
                      </label>
                    );
                  })}
                </div>
              )}
            </div>
          );
        })}
      </div>
    );
  }

  if (variant === 'card') {
    return (
      <div className={cn('space-y-4', className)}>
        {modifierGroups.map((group) => {
          const error = errors[group.id];
          
          return (
            <Card key={group.id}>
              <CardHeader>
                <div className="flex items-center justify-between">
                  <CardTitle className="text-lg">{group.name}</CardTitle>
                  <div className="flex items-center gap-2">
                    {group.is_required && (
                      <Badge variant="destructive" className="text-xs">Required</Badge>
                    )}
                    <Badge variant="outline" className="text-xs">
                      {group.min_selections === group.max_selections && group.max_selections
                        ? `Select ${group.min_selections}`
                        : `Select ${group.min_selections}${group.max_selections ? `-${group.max_selections}` : '+'}`
                      }
                    </Badge>
                  </div>
                </div>
                {group.description && (
                  <CardDescription>{group.description}</CardDescription>
                )}
              </CardHeader>
              <CardContent className="space-y-3">
                {error && (
                  <Alert variant="destructive">
                    <AlertCircle className="h-4 w-4" />
                    <AlertDescription>{error}</AlertDescription>
                  </Alert>
                )}
                
                <div className="grid gap-3 sm:grid-cols-2">
                  {group.modifiers.filter(m => m.is_available).map((modifier) => {
                    const selected = isSelected(group.id, modifier.id);
                    const quantity = getQuantity(group.id, modifier.id);
                    
                    return (
                      <div
                        key={modifier.id}
                        className={cn(
                          "border rounded-lg p-4 cursor-pointer transition-all",
                          selected 
                            ? "border-primary bg-primary/5 shadow-sm" 
                            : "hover:border-gray-300 hover:shadow-sm"
                        )}
                        onClick={() => {
                          if (group.max_selections === 1) {
                            handleSingleSelection(group.id, modifier.id);
                          } else {
                            handleMultipleSelection(group.id, modifier.id, !selected);
                          }
                        }}
                      >
                        <div className="space-y-3">
                          {showImages && modifier.image_url && (
                            <img
                              src={modifier.image_url}
                              alt={modifier.name}
                              className="w-full h-24 object-cover rounded-md"
                            />
                          )}
                          
                          <div className="flex items-start justify-between">
                            <div className="flex-1">
                              <h4 className="font-medium">{modifier.name}</h4>
                              {showDescriptions && modifier.description && (
                                <p className="text-sm text-muted-foreground mt-1">
                                  {modifier.description}
                                </p>
                              )}
                            </div>
                            {selected && (
                              <Check className="h-5 w-5 text-primary shrink-0" />
                            )}
                          </div>
                          
                          <div className="flex items-center justify-between">
                            {showPrices && modifier.price_adjustment !== 0 && (
                              <span className="text-sm font-medium">
                                {modifier.price_adjustment > 0 ? '+' : ''}
                                {formatCurrency(modifier.price_adjustment)}
                              </span>
                            )}
                            
                            {modifier.max_quantity && modifier.max_quantity > 1 && selected && (
                              <div className="flex items-center gap-1">
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="h-7 w-7 p-0"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    handleQuantityChange(group.id, modifier.id, -1);
                                  }}
                                >
                                  <Minus className="h-3 w-3" />
                                </Button>
                                <span className="w-8 text-center text-sm font-medium">
                                  {quantity}
                                </span>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="h-7 w-7 p-0"
                                  onClick={(e) => {
                                    e.stopPropagation();
                                    if (quantity < modifier.max_quantity) {
                                      handleQuantityChange(group.id, modifier.id, 1);
                                    }
                                  }}
                                  disabled={quantity >= modifier.max_quantity}
                                >
                                  <Plus className="h-3 w-3" />
                                </Button>
                              </div>
                            )}
                          </div>
                        </div>
                      </div>
                    );
                  })}
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    );
  }

  // Default variant
  return (
    <div className={cn('space-y-6', className)}>
      {modifierGroups.map((group, groupIndex) => {
        const error = errors[group.id];
        
        return (
          <div key={group.id}>
            {groupIndex > 0 && <Separator className="mb-6" />}
            
            <div className="space-y-4">
              <div>
                <div className="flex items-center justify-between mb-2">
                  <h3 className="font-medium">{group.name}</h3>
                  <div className="flex items-center gap-2">
                    {group.is_required && (
                      <Badge variant="destructive" className="text-xs">Required</Badge>
                    )}
                    <span className="text-sm text-muted-foreground">
                      {group.min_selections === group.max_selections && group.max_selections
                        ? `Select ${group.min_selections}`
                        : `Select ${group.min_selections}${group.max_selections ? `-${group.max_selections}` : '+'}`
                      }
                    </span>
                  </div>
                </div>
                {group.description && (
                  <p className="text-sm text-muted-foreground">{group.description}</p>
                )}
              </div>
              
              {error && (
                <Alert variant="destructive">
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>{error}</AlertDescription>
                </Alert>
              )}
              
              <div className="space-y-2">
                {group.max_selections === 1 ? (
                  <RadioGroup
                    value={selections.find(s => s.groupId === group.id)?.modifierId.toString() || ''}
                    onValueChange={(value) => handleSingleSelection(group.id, parseInt(value))}
                  >
                    {group.modifiers.filter(m => m.is_available).map((modifier) => (
                      <label
                        key={modifier.id}
                        className="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-muted/50 transition-colors"
                      >
                        <div className="flex items-center gap-3">
                          <RadioGroupItem value={modifier.id.toString()} />
                          <div>
                            <span className="font-medium">{modifier.name}</span>
                            {showDescriptions && modifier.description && (
                              <p className="text-sm text-muted-foreground">{modifier.description}</p>
                            )}
                          </div>
                        </div>
                        {showPrices && modifier.price_adjustment !== 0 && (
                          <span className="font-medium">
                            {modifier.price_adjustment > 0 ? '+' : ''}
                            {formatCurrency(modifier.price_adjustment)}
                          </span>
                        )}
                      </label>
                    ))}
                  </RadioGroup>
                ) : (
                  <div className="space-y-2">
                    {group.modifiers.filter(m => m.is_available).map((modifier) => {
                      const selected = isSelected(group.id, modifier.id);
                      const quantity = getQuantity(group.id, modifier.id);
                      
                      return (
                        <label
                          key={modifier.id}
                          className="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-muted/50 transition-colors"
                        >
                          <div className="flex items-center gap-3">
                            <Checkbox
                              checked={selected}
                              onCheckedChange={(checked) => 
                                handleMultipleSelection(group.id, modifier.id, checked as boolean)
                              }
                            />
                            <div>
                              <span className="font-medium">{modifier.name}</span>
                              {showDescriptions && modifier.description && (
                                <p className="text-sm text-muted-foreground">{modifier.description}</p>
                              )}
                            </div>
                          </div>
                          <div className="flex items-center gap-3">
                            {showPrices && modifier.price_adjustment !== 0 && (
                              <span className="font-medium">
                                {modifier.price_adjustment > 0 ? '+' : ''}
                                {formatCurrency(modifier.price_adjustment)}
                              </span>
                            )}
                            {modifier.max_quantity && modifier.max_quantity > 1 && selected && (
                              <div className="flex items-center gap-1">
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="h-7 w-7 p-0"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    handleQuantityChange(group.id, modifier.id, -1);
                                  }}
                                >
                                  <Minus className="h-3 w-3" />
                                </Button>
                                <span className="w-8 text-center text-sm font-medium">
                                  {quantity}
                                </span>
                                <Button
                                  size="sm"
                                  variant="outline"
                                  className="h-7 w-7 p-0"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    if (quantity < modifier.max_quantity) {
                                      handleQuantityChange(group.id, modifier.id, 1);
                                    }
                                  }}
                                  disabled={quantity >= modifier.max_quantity}
                                >
                                  <Plus className="h-3 w-3" />
                                </Button>
                              </div>
                            )}
                          </div>
                        </label>
                      );
                    })}
                  </div>
                )}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}

export function ModifierSummary({
  modifierGroups,
  selectedModifiers,
  className,
}: {
  modifierGroups: ModifierGroup[];
  selectedModifiers: SelectedModifier[];
  className?: string;
}) {
  if (selectedModifiers.length === 0) {
    return null;
  }

  const groupedSelections = selectedModifiers.reduce((acc, selection) => {
    if (!acc[selection.groupId]) {
      acc[selection.groupId] = [];
    }
    acc[selection.groupId].push(selection);
    return acc;
  }, {} as Record<number, SelectedModifier[]>);

  return (
    <div className={cn('space-y-2', className)}>
      {Object.entries(groupedSelections).map(([groupId, selections]) => {
        const group = modifierGroups.find(g => g.id === parseInt(groupId));
        if (!group) return null;
        
        return (
          <div key={groupId} className="space-y-1">
            <p className="text-sm font-medium text-muted-foreground">{group.name}:</p>
            <div className="pl-4 space-y-1">
              {selections.map((selection) => {
                const modifier = group.modifiers.find(m => m.id === selection.modifierId);
                if (!modifier) return null;
                
                return (
                  <div key={selection.modifierId} className="flex items-center justify-between text-sm">
                    <span>
                      {modifier.name}
                      {selection.quantity > 1 && ` (×${selection.quantity})`}
                    </span>
                    {modifier.price_adjustment !== 0 && (
                      <span className="font-medium">
                        {modifier.price_adjustment > 0 ? '+' : ''}
                        {formatCurrency(modifier.price_adjustment * selection.quantity)}
                      </span>
                    )}
                  </div>
                );
              })}
            </div>
          </div>
        );
      })}
    </div>
  );
}