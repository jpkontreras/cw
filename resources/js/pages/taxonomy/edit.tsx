import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { ArrowLeft, Save } from 'lucide-react';

interface TaxonomyType {
  value: string;
  label: string;
  description: string;
  isHierarchical: boolean;
}

interface Taxonomy {
  id: number;
  name: string;
  slug: string;
  type: string;
  parentId: number | null;
  metadata?: {
    description?: string;
    icon?: string;
    color?: string;
  };
  sortOrder: number;
  isActive: boolean;
}

interface EditTaxonomyProps {
  taxonomy: Taxonomy;
  types: TaxonomyType[];
  parentTaxonomies?: Array<{
    id: number;
    name: string;
  }>;
}

function EditTaxonomyContent({ taxonomy, types, parentTaxonomies }: EditTaxonomyProps) {
  const [formData, setFormData] = useState({
    name: taxonomy.name,
    type: taxonomy.type,
    parentId: taxonomy.parentId?.toString() || '',
    metadata: {
      description: taxonomy.metadata?.description || '',
      icon: taxonomy.metadata?.icon || '',
      color: taxonomy.metadata?.color || '#000000',
    },
    sortOrder: taxonomy.sortOrder || 0,
    isActive: taxonomy.isActive,
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [processing, setProcessing] = useState(false);

  const currentType = types.find(t => t.value === formData.type);
  const isHierarchical = currentType?.isHierarchical || false;

  // Filter out the current taxonomy and its children from parent options
  const availableParents = parentTaxonomies?.filter(p => p.id !== taxonomy.id);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setProcessing(true);
    
    router.put(`/taxonomies/${taxonomy.id}`, formData, {
      preserveState: false,
      onSuccess: () => {
        // Will redirect on success
      },
      onError: (errors) => {
        setErrors(errors);
        setProcessing(false);
      },
      onFinish: () => {
        setProcessing(false);
      },
    });
  };

  return (
    <>
      <Page.Header
        title="Edit Taxonomy"
        subtitle={`Editing "${taxonomy.name}"`}
        actions={
          <div className="flex gap-2">
            <Link href="/taxonomies">
              <Button variant="outline">Cancel</Button>
            </Link>
            <Button onClick={handleSubmit} disabled={processing}>
              <Save className="mr-2 h-4 w-4" />
              {processing ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        }
      />

      <Page.Content>
        <form onSubmit={handleSubmit} className="max-w-3xl space-y-6">
          {/* Basic Information */}
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>
                Update the basic details for your taxonomy
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Name *</Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                  placeholder="e.g., Vegetarian, Breakfast, Spicy"
                  required
                  className={errors.name ? 'border-destructive' : ''}
                />
                {errors.name && (
                  <p className="text-sm text-destructive">{errors.name}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="type">Type *</Label>
                <Select
                  value={formData.type}
                  onValueChange={(value) => setFormData({ ...formData, type: value })}
                  disabled // Type cannot be changed after creation
                >
                  <SelectTrigger id="type">
                    <SelectValue placeholder="Select a type" />
                  </SelectTrigger>
                  <SelectContent>
                    {types.map((type) => (
                      <SelectItem key={type.value} value={type.value}>
                        {type.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {currentType && (
                  <p className="text-xs text-muted-foreground">
                    {currentType.description}
                  </p>
                )}
                <p className="text-xs text-muted-foreground">
                  Type cannot be changed after creation
                </p>
                {errors.type && (
                  <p className="text-sm text-destructive">{errors.type}</p>
                )}
              </div>

              {isHierarchical && availableParents && availableParents.length > 0 && (
                <div className="space-y-2">
                  <Label htmlFor="parent">Parent (Optional)</Label>
                  <Select
                    value={formData.parentId}
                    onValueChange={(value) => setFormData({ ...formData, parentId: value })}
                  >
                    <SelectTrigger id="parent">
                      <SelectValue placeholder="No parent (top-level)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="">No parent (top-level)</SelectItem>
                      {availableParents.map((parent) => (
                        <SelectItem key={parent.id} value={parent.id.toString()}>
                          {parent.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              )}
            </CardContent>
          </Card>

          {/* Additional Details */}
          <Card>
            <CardHeader>
              <CardTitle>Additional Details</CardTitle>
              <CardDescription>
                Optional metadata and settings
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={formData.metadata.description}
                  onChange={(e) => setFormData({
                    ...formData,
                    metadata: { ...formData.metadata, description: e.target.value }
                  })}
                  placeholder="Brief description of this taxonomy"
                  rows={3}
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label htmlFor="icon">Icon</Label>
                  <Input
                    id="icon"
                    value={formData.metadata.icon}
                    onChange={(e) => setFormData({
                      ...formData,
                      metadata: { ...formData.metadata, icon: e.target.value }
                    })}
                    placeholder="e.g., leaf, fire"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="color">Color</Label>
                  <div className="flex gap-2">
                    <Input
                      id="color"
                      type="color"
                      value={formData.metadata.color}
                      onChange={(e) => setFormData({
                        ...formData,
                        metadata: { ...formData.metadata, color: e.target.value }
                      })}
                      className="w-16 h-9 p-1"
                    />
                    <Input
                      value={formData.metadata.color}
                      onChange={(e) => setFormData({
                        ...formData,
                        metadata: { ...formData.metadata, color: e.target.value }
                      })}
                      placeholder="#000000"
                      className="flex-1"
                    />
                  </div>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="sortOrder">Sort Order</Label>
                <Input
                  id="sortOrder"
                  type="number"
                  value={formData.sortOrder}
                  onChange={(e) => setFormData({ ...formData, sortOrder: parseInt(e.target.value) || 0 })}
                  placeholder="0"
                />
                <p className="text-xs text-muted-foreground">
                  Lower numbers appear first
                </p>
              </div>
            </CardContent>
          </Card>

          {/* Status */}
          <Card>
            <CardHeader>
              <CardTitle>Status</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <Label htmlFor="active">Active</Label>
                  <p className="text-sm text-muted-foreground">
                    Make this taxonomy available for use
                  </p>
                </div>
                <Switch
                  id="active"
                  checked={formData.isActive}
                  onCheckedChange={(checked) => setFormData({ ...formData, isActive: checked })}
                />
              </div>
            </CardContent>
          </Card>

          {/* Danger Zone */}
          <Card className="border-destructive/50">
            <CardHeader>
              <CardTitle className="text-destructive">Danger Zone</CardTitle>
              <CardDescription>
                Irreversible actions for this taxonomy
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between">
                <div className="space-y-0.5">
                  <p className="font-medium">Delete this taxonomy</p>
                  <p className="text-sm text-muted-foreground">
                    Once deleted, this taxonomy cannot be recovered
                  </p>
                </div>
                <Button
                  type="button"
                  variant="destructive"
                  onClick={() => {
                    if (confirm('Are you sure you want to delete this taxonomy? This action cannot be undone.')) {
                      router.delete(`/taxonomies/${taxonomy.id}`);
                    }
                  }}
                >
                  Delete Taxonomy
                </Button>
              </div>
            </CardContent>
          </Card>

          {/* Form Actions */}
          <div className="flex justify-end gap-2">
            <Link href="/taxonomies">
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </Link>
            <Button type="submit" disabled={processing}>
              <Save className="mr-2 h-4 w-4" />
              {processing ? 'Saving...' : 'Save Changes'}
            </Button>
          </div>
        </form>
      </Page.Content>
    </>
  );
}

export default function EditTaxonomy(props: EditTaxonomyProps) {
  return (
    <AppLayout>
      <Head title={`Edit ${props.taxonomy.name}`} />
      <Page>
        <EditTaxonomyContent {...props} />
      </Page>
    </AppLayout>
  );
}