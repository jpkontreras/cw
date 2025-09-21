import React from 'react';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { ChevronRight, Plus } from 'lucide-react';

interface QuickAction {
  label: string;
  action: () => void;
  type: 'primary' | 'secondary';
}

interface AiQuickActionsProps {
  actions: QuickAction[];
}

export function AiQuickActions({ actions }: AiQuickActionsProps) {
  if (!actions || actions.length === 0) return null;

  return (
    <Card className="border-0 shadow-sm bg-gradient-to-r from-primary/5 to-primary/10">
      <div className="p-3">
        <p className="text-xs font-medium text-muted-foreground mb-2">Quick Actions</p>
        <div className="flex flex-wrap gap-2">
          {actions.map((action, idx) => (
            <Button
              key={idx}
              size="sm"
              variant={action.type === 'primary' ? 'default' : 'outline'}
              className="h-7 text-xs"
              onClick={action.action}
            >
              {action.type === 'primary' ? (
                <Plus className="h-3 w-3 mr-1" />
              ) : (
                <ChevronRight className="h-3 w-3 mr-1" />
              )}
              {action.label}
            </Button>
          ))}
        </div>
      </div>
    </Card>
  );
}