import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';
import type { FilterResetHandler, FilterValue } from './types';

interface FilterResetProps {
  onReset: FilterResetHandler;
  activeCount: number;
  label?: string;
  className?: string;
  showIcon?: boolean;
}

export function FilterReset({ 
  onReset, 
  activeCount, 
  label = 'Reset',
  className,
  showIcon = true
}: FilterResetProps) {
  if (activeCount === 0) {
    return null;
  }

  return (
    <Button
      variant="ghost"
      onClick={onReset}
      className={cn('h-10 px-3', className)}
      size="sm"
    >
      {showIcon && <X className="mr-1 h-3.5 w-3.5" />}
      {label}
      <Badge variant="secondary" className="ml-2">
        {activeCount}
      </Badge>
    </Button>
  );
}

export function countActiveFilters(
  values: Record<string, FilterValue>,
  ignoreKeys: string[] = []
): number {
  return Object.entries(values).filter(([key, value]) => {
    if (ignoreKeys.includes(key)) return false;
    if (value === undefined || value === null || value === '') return false;
    if (Array.isArray(value) && value.length === 0) return false;
    if (value === 'all') return false;
    return true;
  }).length;
}