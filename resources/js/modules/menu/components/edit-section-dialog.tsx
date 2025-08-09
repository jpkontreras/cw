import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type MenuSection } from '../types';

interface EditSectionDialogProps {
  section: MenuSection | null;
  onClose: () => void;
  onSave: (section: MenuSection) => void;
}

export function EditSectionDialog({ section, onClose, onSave }: EditSectionDialogProps) {
  if (!section) return null;

  return (
    <Dialog open={!!section} onOpenChange={() => onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Section</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div>
            <Label htmlFor="section-name">Name</Label>
            <Input
              id="section-name"
              value={section.name}
              onChange={(e) =>
                onSave({
                  ...section,
                  name: e.target.value,
                })
              }
            />
          </div>

          <div>
            <Label htmlFor="section-description">Description</Label>
            <Textarea
              id="section-description"
              value={section.description || ''}
              onChange={(e) =>
                onSave({
                  ...section,
                  description: e.target.value,
                })
              }
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={() => onClose()}>
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}