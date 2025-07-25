import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Checkbox } from '@/components/ui/checkbox';
import { formatCurrency } from '@/lib/utils';

interface Modifier {
  id: number;
  name: string;
  price_adjustment: number;
  is_default: boolean;
}

interface ModifierGroup {
  id: number;
  name: string;
  min_selections: number;
  max_selections: number;
  modifiers: Modifier[];
}

interface ModifierSelectorProps {
  groups: ModifierGroup[];
  selectedModifiers: Record<number, number[]>;
  onModifierChange: (groupId: number, modifierIds: number[]) => void;
}

export function ModifierSelector({
  groups,
  selectedModifiers,
  onModifierChange,
}: ModifierSelectorProps) {
  const handleRadioChange = (groupId: number, modifierId: string) => {
    onModifierChange(groupId, [parseInt(modifierId)]);
  };

  const handleCheckboxChange = (groupId: number, modifierId: number, checked: boolean) => {
    const group = groups.find(g => g.id === groupId);
    if (!group) return;

    const current = selectedModifiers[groupId] || [];
    let updated: number[];

    if (checked) {
      if (current.length >= group.max_selections) {
        // Don't add if we're at max
        return;
      }
      updated = [...current, modifierId];
    } else {
      updated = current.filter(id => id !== modifierId);
    }

    onModifierChange(groupId, updated);
  };

  return (
    <div className="space-y-4">
      {groups.map((group) => {
        const isRadio = group.max_selections === 1;
        const isRequired = group.min_selections > 0;
        const selectedCount = selectedModifiers[group.id]?.length || 0;

        return (
          <Card key={group.id}>
            <CardHeader>
              <CardTitle className="text-base">
                {group.name}
                {isRequired && <span className="text-destructive ml-1">*</span>}
              </CardTitle>
              <CardDescription>
                {group.min_selections === group.max_selections
                  ? `Select exactly ${group.min_selections}`
                  : group.min_selections > 0
                  ? `Select ${group.min_selections} to ${group.max_selections}`
                  : `Select up to ${group.max_selections}`}
                {selectedCount > 0 && ` (${selectedCount} selected)`}
              </CardDescription>
            </CardHeader>
            <CardContent>
              {isRadio ? (
                <RadioGroup
                  value={selectedModifiers[group.id]?.[0]?.toString() || ''}
                  onValueChange={(value) => handleRadioChange(group.id, value)}
                >
                  {group.modifiers.map((modifier) => (
                    <div key={modifier.id} className="flex items-center justify-between py-2">
                      <div className="flex items-center space-x-2">
                        <RadioGroupItem value={modifier.id.toString()} id={`${group.id}-${modifier.id}`} />
                        <Label
                          htmlFor={`${group.id}-${modifier.id}`}
                          className="text-sm font-normal cursor-pointer"
                        >
                          {modifier.name}
                        </Label>
                      </div>
                      {modifier.price_adjustment !== 0 && (
                        <span className="text-sm text-muted-foreground">
                          {modifier.price_adjustment > 0 ? '+' : ''}
                          {formatCurrency(modifier.price_adjustment)}
                        </span>
                      )}
                    </div>
                  ))}
                </RadioGroup>
              ) : (
                <div className="space-y-2">
                  {group.modifiers.map((modifier) => {
                    const isChecked = selectedModifiers[group.id]?.includes(modifier.id) || false;
                    const isDisabled = !isChecked && selectedCount >= group.max_selections;

                    return (
                      <div key={modifier.id} className="flex items-center justify-between py-2">
                        <div className="flex items-center space-x-2">
                          <Checkbox
                            id={`${group.id}-${modifier.id}`}
                            checked={isChecked}
                            onCheckedChange={(checked) => 
                              handleCheckboxChange(group.id, modifier.id, checked as boolean)
                            }
                            disabled={isDisabled}
                          />
                          <Label
                            htmlFor={`${group.id}-${modifier.id}`}
                            className="text-sm font-normal cursor-pointer"
                          >
                            {modifier.name}
                          </Label>
                        </div>
                        {modifier.price_adjustment !== 0 && (
                          <span className="text-sm text-muted-foreground">
                            {modifier.price_adjustment > 0 ? '+' : ''}
                            {formatCurrency(modifier.price_adjustment)}
                          </span>
                        )}
                      </div>
                    );
                  })}
                </div>
              )}
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}