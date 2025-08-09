import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { LayoutGrid, Rows3 } from 'lucide-react';

export type ViewMode = 'compact' | 'standard';

interface Props {
  value: ViewMode;
  onChange: (mode: ViewMode) => void;
  className?: string;
}

const viewModes: Array<{
  value: ViewMode;
  icon: any;
  label: string;
}> = [
  {
    value: 'compact',
    icon: Rows3,
    label: 'Compact view',
  },
  {
    value: 'standard',
    icon: LayoutGrid,
    label: 'Standard view',
  },
];

export function ViewModeToggle({ value, onChange, className }: Props) {
  return (
    <div className={cn('inline-flex rounded-lg border bg-muted p-1', className)}>
      {viewModes.map((mode) => {
        const Icon = mode.icon;
        return (
          <Button
            key={mode.value}
            type="button"
            variant="ghost"
            size="icon"
            className={cn('h-8 w-8 rounded-md', value === mode.value && 'bg-background shadow-sm')}
            onClick={() => onChange(mode.value)}
            title={mode.label}
          >
            <Icon className="h-4 w-4" />
            <span className="sr-only">{mode.label}</span>
          </Button>
        );
      })}
    </div>
  );
}
