import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Plus, Trash2, GripVertical, AlertCircle, ArrowLeft } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Link } from '@inertiajs/react';

interface Modifier {
  name: string;
  price_adjustment: number;
  is_available: boolean;
  display_order: number;
}

interface PageProps {
  parent_groups: Array<{ id: number; name: string }>;
  items: Array<{ id: number; name: string; allow_modifiers: boolean }>;
}

export default function CreateModifierGroup({ parent_groups = [], items = [] }: PageProps) {
  const [modifiers, setModifiers] = useState<Modifier[]>([]);
  
  const { data, setData, post, processing, errors } = useForm({
    name: '',
    display_name: '',
    description: '',
    parent_group_id: '',
    min_selections: 0,
    max_selections: null as number | null,
    is_required: false,
    is_active: true,
    display_order: 0,
    item_ids: [] as number[],
    modifiers: [] as Modifier[],
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/modifiers', {
      data: {
        ...data,
        modifiers,
      },
    });
  };

  const addModifier = () => {
    setModifiers([
      ...modifiers,
      {
        name: '',
        price_adjustment: 0,
        is_available: true,
        display_order: modifiers.length,
      },
    ]);
  };

  const updateModifier = (index: number, field: keyof Modifier, value: any) => {
    const updated = [...modifiers];
    updated[index] = { ...updated[index], [field]: value };
    setModifiers(updated);
  };

  const removeModifier = (index: number) => {
    setModifiers(modifiers.filter((_, i) => i !== index));
  };

  return (
    <AppLayout>
      <Head title="Create Modifier Group" />
      <Page>
        <Page.Header
          title="Create Modifier Group"
          subtitle="Configure options that customers can select to customize items"
          actions={
            <Link href="/modifiers">
              <Button variant="ghost" size="sm">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Back to Modifiers
              </Button>
            </Link>
          }
        />
        <Page.Content>
          <form onSubmit={handleSubmit} className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Basic Information</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <Label htmlFor="name">Group Name *</Label>
                  <Input
                    id="name"
                    placeholder="e.g., Size, Toppings, Sauce"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    error={errors.name}
                  />
                  {errors.name && (
                    <p className="text-sm text-destructive">{errors.name}</p>
                  )}
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="display_name">Display Name</Label>
                  <Input
                    id="display_name"
                    placeholder="Customer-facing name (optional)"
                    value={data.display_name || ''}
                    onChange={(e) => setData('display_name', e.target.value)}
                  />
                  <p className="text-xs text-muted-foreground">
                    Leave empty to use the group name
                  </p>
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  placeholder="Brief description for staff reference"
                  value={data.description || ''}
                  onChange={(e) => setData('description', e.target.value)}
                  rows={3}
                />
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Selection Rules</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="grid gap-4 md:grid-cols-3">
                <div className="space-y-2">
                  <Label htmlFor="min_selections">Min Selections *</Label>
                  <Input
                    id="min_selections"
                    type="number"
                    min="0"
                    value={data.min_selections}
                    onChange={(e) => setData('min_selections', parseInt(e.target.value) || 0)}
                    error={errors.min_selections}
                  />
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="max_selections">Max Selections</Label>
                  <Input
                    id="max_selections"
                    type="number"
                    min="0"
                    placeholder="No limit"
                    value={data.max_selections || ''}
                    onChange={(e) => setData('max_selections', e.target.value ? parseInt(e.target.value) : null)}
                  />
                  <p className="text-xs text-muted-foreground">
                    Leave empty for unlimited
                  </p>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="display_order">Display Order</Label>
                  <Input
                    id="display_order"
                    type="number"
                    value={data.display_order}
                    onChange={(e) => setData('display_order', parseInt(e.target.value) || 0)}
                  />
                </div>
              </div>

              <div className="flex items-center space-x-2">
                <Switch
                  id="is_required"
                  checked={data.is_required}
                  onCheckedChange={(checked) => setData('is_required', checked)}
                />
                <Label htmlFor="is_required" className="cursor-pointer">
                  Required
                  <span className="block text-sm font-normal text-muted-foreground">
                    Customer must make a selection from this group
                  </span>
                </Label>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>Modifier Options</CardTitle>
              <CardDescription>
                Add modifier options that customers can select. Each option can adjust the item price.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4">
              {modifiers.length === 0 ? (
                <Alert>
                  <AlertCircle className="h-4 w-4" />
                  <AlertDescription>
                    Add modifier options that customers can select. Each option can adjust the item price.
                  </AlertDescription>
                </Alert>
              ) : (
                <div className="space-y-3">
                  {modifiers.map((modifier, index) => (
                    <div
                      key={index}
                      className="flex items-center gap-3 rounded-lg border p-3"
                    >
                      <GripVertical className="h-4 w-4 text-muted-foreground" />
                      
                      <div className="flex-1 grid gap-3 md:grid-cols-3">
                        <Input
                          placeholder="Option name"
                          value={modifier.name}
                          onChange={(e) => updateModifier(index, 'name', e.target.value)}
                        />
                        
                        <div className="flex items-center gap-2">
                          <span className="text-sm text-muted-foreground">$</span>
                          <Input
                            type="number"
                            step="0.01"
                            placeholder="0.00"
                            value={modifier.price_adjustment}
                            onChange={(e) => updateModifier(index, 'price_adjustment', parseFloat(e.target.value) || 0)}
                          />
                        </div>
                        
                        <div className="flex items-center gap-2">
                          <Switch
                            checked={modifier.is_available}
                            onCheckedChange={(checked) => updateModifier(index, 'is_available', checked)}
                          />
                          <Label className="text-sm">Available</Label>
                        </div>
                      </div>
                      
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        onClick={() => removeModifier(index)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  ))}
                </div>
              )}
              
              <Button
                type="button"
                variant="outline"
                onClick={addModifier}
                className="w-full"
              >
                <Plus className="h-4 w-4 mr-2" />
                Add Option
              </Button>
            </CardContent>
          </Card>

          <Card>
            <CardContent className="pt-6">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-2">
                  <Switch
                    id="is_active"
                    checked={data.is_active}
                    onCheckedChange={(checked) => setData('is_active', checked)}
                  />
                  <Label htmlFor="is_active" className="cursor-pointer">
                    Active
                    <span className="block text-sm font-normal text-muted-foreground">
                      Group will be available for selection
                    </span>
                  </Label>
                </div>
                
                <div className="flex gap-3">
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => router.visit('/modifiers')}
                    disabled={processing}
                  >
                    Cancel
                  </Button>
                  <Button type="submit" disabled={processing}>
                    Create Group
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        </form>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}