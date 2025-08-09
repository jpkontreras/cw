import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { FolderPlus } from 'lucide-react';
import { SECTION_ICONS, SECTION_TEMPLATES } from '../constants';

interface AddSectionDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onAddSection: (template?: typeof SECTION_TEMPLATES[0]) => void;
}

export function AddSectionDialog({ open, onOpenChange, onAddSection }: AddSectionDialogProps) {
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add Section</DialogTitle>
          <DialogDescription>Choose a template or create a custom section</DialogDescription>
        </DialogHeader>

        <div className="grid grid-cols-2 gap-3">
          <Button 
            variant="outline" 
            className="h-24 flex-col" 
            onClick={() => onAddSection()}
          >
            <FolderPlus className="mb-2 h-8 w-8" />
            <span>Custom Section</span>
          </Button>

          {SECTION_TEMPLATES.map((template) => {
            const Icon = SECTION_ICONS[template.icon as keyof typeof SECTION_ICONS];
            return (
              <Button 
                key={template.name} 
                variant="outline" 
                className="h-24 flex-col" 
                onClick={() => onAddSection(template)}
              >
                <Icon className="mb-2 h-8 w-8" />
                <span className="text-xs">{template.name}</span>
              </Button>
            );
          })}
        </div>
      </DialogContent>
    </Dialog>
  );
}