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
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { cn } from '@/lib/utils';
import { useEffect, useState } from 'react';
import { SECTION_ICONS } from '../constants';
import { type MenuSection } from '../types';

interface EditSectionDialogProps {
  section: MenuSection | null;
  onClose: () => void;
  onSave: (section: MenuSection) => void;
}

export function EditSectionDialog({ section, onClose, onSave }: EditSectionDialogProps) {
  const [formData, setFormData] = useState<MenuSection | null>(null);

  useEffect(() => {
    if (section) {
      setFormData({ ...section });
    }
  }, [section]);

  if (!section || !formData) return null;

  const handleSave = () => {
    if (formData) {
      onSave(formData);
      onClose();
    }
  };

  const handleCancel = () => {
    setFormData(null);
    onClose();
  };

  return (
    <Dialog open={!!section} onOpenChange={(open) => !open && handleCancel()}>
      <DialogContent className="sm:max-w-[500px]">
        <DialogHeader>
          <DialogTitle>Edit Section</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div>
            <Label htmlFor="section-name">Section Name</Label>
            <Input
              id="section-name"
              value={formData.name}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  name: e.target.value,
                })
              }
              placeholder="e.g., Main Courses"
              className="mt-1.5"
            />
          </div>

          <div>
            <Label htmlFor="section-description">Description</Label>
            <Textarea
              id="section-description"
              value={formData.description || ''}
              onChange={(e) =>
                setFormData({
                  ...formData,
                  description: e.target.value,
                })
              }
              placeholder="Brief description of this section..."
              className="mt-1.5"
              rows={3}
            />
          </div>

          <div>
            <Label>Icon</Label>
            <div className="mt-2 grid grid-cols-9 gap-2">
              {Object.entries(SECTION_ICONS).map(([key, Icon]) => (
                <button
                  key={key}
                  type="button"
                  onClick={() => setFormData({ ...formData, icon: key })}
                  className={cn(
                    "flex h-10 w-10 items-center justify-center rounded-lg border-2 transition-colors",
                    formData.icon === key
                      ? "border-blue-500 bg-blue-50"
                      : "border-gray-200 hover:border-gray-300"
                  )}
                >
                  <Icon className={cn(
                    "h-5 w-5",
                    formData.icon === key ? "text-blue-600" : "text-gray-500"
                  )} />
                </button>
              ))}
            </div>
          </div>

          <div className="space-y-3">
            <div className="flex items-center justify-between">
              <Label htmlFor="section-active" className="font-normal">
                Active
                <span className="block text-xs text-gray-500 mt-0.5">
                  Show this section in the menu
                </span>
              </Label>
              <Switch
                id="section-active"
                checked={formData.isActive}
                onCheckedChange={(checked) =>
                  setFormData({ ...formData, isActive: checked })
                }
              />
            </div>

            <div className="flex items-center justify-between">
              <Label htmlFor="section-featured" className="font-normal">
                Featured
                <span className="block text-xs text-gray-500 mt-0.5">
                  Highlight this section
                </span>
              </Label>
              <Switch
                id="section-featured"
                checked={formData.isFeatured}
                onCheckedChange={(checked) =>
                  setFormData({ ...formData, isFeatured: checked })
                }
              />
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleCancel}>
            Cancel
          </Button>
          <Button onClick={handleSave}>
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}