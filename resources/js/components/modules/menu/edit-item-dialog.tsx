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
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { type MenuItem } from './types';

interface EditItemDialogProps {
  item: { item: MenuItem; sectionId: number } | null;
  onClose: () => void;
  onSave: (item: MenuItem, sectionId: number) => void;
}

export function EditItemDialog({ item, onClose, onSave }: EditItemDialogProps) {
  if (!item) return null;

  const handleChange = (field: keyof MenuItem, value: any) => {
    onSave({
      ...item.item,
      [field]: value,
    }, item.sectionId);
  };

  return (
    <Dialog open={!!item} onOpenChange={() => onClose()}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit Menu Item</DialogTitle>
        </DialogHeader>

        <div className="space-y-4">
          <div>
            <Label htmlFor="item-name">Display Name</Label>
            <Input
              id="item-name"
              value={item.item.displayName || item.item.baseItem?.name || ''}
              onChange={(e) => handleChange('displayName', e.target.value)}
              placeholder={item.item.baseItem?.name}
            />
          </div>

          <div>
            <Label htmlFor="item-description">Display Description</Label>
            <Textarea
              id="item-description"
              value={item.item.displayDescription || ''}
              onChange={(e) => handleChange('displayDescription', e.target.value)}
              placeholder={item.item.baseItem?.description}
            />
          </div>

          <div>
            <Label htmlFor="item-price">Price Override</Label>
            <Input
              id="item-price"
              type="number"
              step="0.01"
              value={item.item.priceOverride || ''}
              onChange={(e) => handleChange('priceOverride', e.target.value ? parseFloat(e.target.value) : undefined)}
              placeholder={item.item.baseItem?.price?.toString()}
            />
          </div>

          <Separator />

          <div className="space-y-2">
            <Label>Item Badges</Label>

            <div className="flex items-center space-x-2">
              <Switch
                id="item-featured"
                checked={item.item.isFeatured}
                onCheckedChange={(checked) => handleChange('isFeatured', checked)}
              />
              <Label htmlFor="item-featured">Featured</Label>
            </div>

            <div className="flex items-center space-x-2">
              <Switch
                id="item-new"
                checked={item.item.isNew}
                onCheckedChange={(checked) => handleChange('isNew', checked)}
              />
              <Label htmlFor="item-new">New</Label>
            </div>

            <div className="flex items-center space-x-2">
              <Switch
                id="item-recommended"
                checked={item.item.isRecommended}
                onCheckedChange={(checked) => handleChange('isRecommended', checked)}
              />
              <Label htmlFor="item-recommended">Recommended</Label>
            </div>

            <div className="flex items-center space-x-2">
              <Switch
                id="item-seasonal"
                checked={item.item.isSeasonal}
                onCheckedChange={(checked) => handleChange('isSeasonal', checked)}
              />
              <Label htmlFor="item-seasonal">Seasonal</Label>
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={onClose}>
            Cancel
          </Button>
          <Button onClick={onClose}>
            Save Changes
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}