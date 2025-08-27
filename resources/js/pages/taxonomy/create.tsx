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

interface CreateTaxonomyProps {
  types: TaxonomyType[];
  selectedType?: string;
  selectedParent?: number;
  parentOptions?: Array<{
    id: number;
    name: string;
  }>;
}

function CreateTaxonomyContent({ types, selectedType, selectedParent, parentOptions }: CreateTaxonomyProps) {
  console.log('CreateTaxonomy props:', { selectedType, selectedParent, parentOptions });
  
  const [formData, setFormData] = useState({
    name: '',
    slug: '',
    type: selectedType || types[0]?.value || '',
    parentId: selectedParent ? selectedParent.toString() : '',
    metadata: {
      description: '',
      icon: '',
      color: '#000000',
    },
    sortOrder: 0,
    isActive: true,
  });

  const [errors, setErrors] = useState<Record<string, string>>({});
  const [processing, setProcessing] = useState(false);

  const currentType = types.find(t => t.value === formData.type);
  const isHierarchical = currentType?.isHierarchical || false;

  // Generate slug from name
  const generateSlug = (name: string): string => {
    return name
      .toLowerCase()
      .trim()
      .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
      .replace(/\s+/g, '-') // Replace spaces with hyphens
      .replace(/-+/g, '-'); // Replace multiple hyphens with single hyphen
  };

  // Update slug when name changes
  const handleNameChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const name = e.target.value;
    const slug = generateSlug(name);
    setFormData({ ...formData, name, slug });
  };

  // Handle type change by navigating to the same page with the new type
  const handleTypeChange = (value: string) => {
    setFormData({ ...formData, type: value });
    // Navigate to the same page with the new type to get fresh parent options
    // Keep parent if it was selected from URL parameter
    const params = new URLSearchParams();
    params.set('type', value);
    if (selectedParent) {
      params.set('parent', selectedParent.toString());
    }
    router.visit(`/taxonomies/create?${params.toString()}`, {
      preserveState: true,
      preserveScroll: true,
      only: ['parentOptions', 'selectedType'], // Only reload parent options and type
    });
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setProcessing(true);
    
    // Prepare data for submission
    const dataToSubmit = {
      ...formData,
      // Convert parentId to number or null
      parentId: formData.parentId && formData.parentId !== '' && formData.parentId !== 'none' 
        ? parseInt(formData.parentId) 
        : null,
    };
    
    console.log('Submitting taxonomy with data:', dataToSubmit);
    console.log('Parent ID being sent:', dataToSubmit.parentId);
    
    router.post('/taxonomies', dataToSubmit, {
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
        title="Create Taxonomy"
        subtitle="Add a new category or tag"
        actions={
          <div className="flex gap-2">
            <Link href="/taxonomies">
              <Button variant="outline">Cancel</Button>
            </Link>
            <Button type="button" onClick={handleSubmit} disabled={processing}>
              <Save className="mr-2 h-4 w-4" />
              {processing ? 'Creating...' : 'Create Taxonomy'}
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
                Enter the basic details for your taxonomy
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-2">
                <Label htmlFor="name">Name *</Label>
                <Input
                  id="name"
                  value={formData.name}
                  onChange={handleNameChange}
                  placeholder="e.g., Vegetarian, Breakfast, Spicy"
                  required
                  className={errors.name ? 'border-destructive' : ''}
                />
                {errors.name && (
                  <p className="text-sm text-destructive">{errors.name}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="slug">Slug</Label>
                <Input
                  id="slug"
                  value={formData.slug}
                  onChange={(e) => setFormData({ ...formData, slug: e.target.value })}
                  placeholder="auto-generated-from-name"
                  className={errors.slug ? 'border-destructive' : ''}
                />
                <p className="text-xs text-muted-foreground">
                  URL-friendly identifier. Auto-generated from name if left empty.
                </p>
                {errors.slug && (
                  <p className="text-sm text-destructive">{errors.slug}</p>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="type">Type *</Label>
                <Select
                  value={formData.type}
                  onValueChange={handleTypeChange}
                  disabled={!!selectedParent}
                >
                  <SelectTrigger id="type" disabled={!!selectedParent}>
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
                    {selectedParent && (
                      <span className="block mt-1 text-orange-600">
                        Type is locked when creating a child taxonomy
                      </span>
                    )}
                  </p>
                )}
                {errors.type && (
                  <p className="text-sm text-destructive">{errors.type}</p>
                )}
              </div>

              {isHierarchical && (
                <div className="space-y-2">
                  <Label htmlFor="parent">Parent Category {selectedParent ? '(Pre-selected)' : '(Optional)'}</Label>
                  <Select
                    value={formData.parentId || "none"}
                    onValueChange={(value) => setFormData({ ...formData, parentId: value === "none" ? '' : value })}
                  >
                    <SelectTrigger id="parent" className={selectedParent ? 'ring-2 ring-primary' : ''}>
                      <SelectValue placeholder="No parent (top-level)" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="none">No parent (top-level)</SelectItem>
                      {parentOptions && parentOptions.map((parent) => (
                        <SelectItem key={parent.id} value={parent.id.toString()}>
                          {parent.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {selectedParent && (
                    <p className="text-xs text-primary">
                      Creating as a child of the selected parent taxonomy.
                    </p>
                  )}
                  {!selectedParent && (!parentOptions || parentOptions.length === 0) && (
                    <p className="text-xs text-muted-foreground">
                      No existing {currentType?.label.toLowerCase() || 'taxonomies'} available. This will be the first one.
                    </p>
                  )}
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

          {/* Form Actions */}
          <div className="flex justify-end gap-2">
            <Link href="/taxonomies">
              <Button type="button" variant="outline">
                Cancel
              </Button>
            </Link>
            <Button type="submit" disabled={processing}>
              <Save className="mr-2 h-4 w-4" />
              {processing ? 'Creating...' : 'Create Taxonomy'}
            </Button>
          </div>
        </form>
      </Page.Content>
    </>
  );
}

export default function CreateTaxonomy(props: CreateTaxonomyProps) {
  return (
    <AppLayout>
      <Head title="Create Taxonomy" />
      <Page>
        <CreateTaxonomyContent {...props} />
      </Page>
    </AppLayout>
  );
}