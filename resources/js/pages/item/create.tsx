import { useForm } from '@inertiajs/react';
import { Head, router } from '@inertiajs/react';
import PageLayout from '@/layouts/page-layout';
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
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import { 
  AlertCircle, 
  ArrowLeft, 
  Save, 
  Plus,
  X,
  Upload,
  Package,
  DollarSign,
  Settings,
  Image as ImageIcon
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { useState } from 'react';

interface Category {
  id: number;
  name: string;
}

interface PageProps {
  categories: Category[];
  item_types: Array<{ value: string; label: string }>;
  features: {
    variants: boolean;
    modifiers: boolean;
    inventory: boolean;
    multiple_images: boolean;
  };
}

interface Variant {
  name: string;
  sku: string;
  price: number;
  cost: number;
  track_stock: boolean;
  is_available: boolean;
}

export default function ItemCreate({ categories, item_types, features }: PageProps) {
  const [variants, setVariants] = useState<Variant[]>([]);
  const [showAdvanced, setShowAdvanced] = useState(false);

  const { data, setData, post, processing, errors, reset } = useForm({
    name: '',
    description: '',
    type: 'product',
    category_id: '',
    base_price: '',
    cost: '',
    sku: '',
    barcode: '',
    track_stock: true,
    is_available: true,
    allow_modifiers: false,
    preparation_time: '',
    image: null as File | null,
    variants: [] as Variant[],
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = {
      ...data,
      variants: features.variants ? variants : [],
    };
    post('/items', {
      data: formData,
      forceFormData: true,
    });
  };

  const addVariant = () => {
    setVariants([
      ...variants,
      {
        name: '',
        sku: '',
        price: 0,
        cost: 0,
        track_stock: true,
        is_available: true,
      },
    ]);
  };

  const updateVariant = (index: number, field: keyof Variant, value: any) => {
    const updated = [...variants];
    updated[index] = { ...updated[index], [field]: value };
    setVariants(updated);
  };

  const removeVariant = (index: number) => {
    setVariants(variants.filter((_, i) => i !== index));
  };

  const handleImageChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setData('image', e.target.files[0]);
    }
  };

  return (
    <>
      <Head title="Create Item" />
      
      <PageLayout>
        <PageLayout.Header
          title="Create Item"
          subtitle="Add a new product, service, or combo to your inventory"
          actions={
            <PageLayout.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/items')}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Cancel
              </Button>
              <Button
                size="sm"
                onClick={handleSubmit}
                disabled={processing}
              >
                <Save className="mr-2 h-4 w-4" />
                Save Item
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
          <form onSubmit={handleSubmit} className="max-w-5xl mx-auto">
            {Object.keys(errors).length > 0 && (
              <Alert variant="destructive" className="mb-6">
                <AlertCircle className="h-4 w-4" />
                <AlertDescription>
                  Please correct the errors below to continue.
                </AlertDescription>
              </Alert>
            )}

            <div className="grid gap-6">
              {/* Basic Information */}
              <Card>
                <CardHeader>
                  <CardTitle>Basic Information</CardTitle>
                  <CardDescription>
                    Essential details about your item
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="name">
                        Item Name <span className="text-destructive">*</span>
                      </Label>
                      <Input
                        id="name"
                        value={data.name}
                        onChange={(e) => setData('name', e.target.value)}
                        placeholder="e.g., Empanada de Pino"
                        className={errors.name ? 'border-destructive' : ''}
                      />
                      {errors.name && (
                        <p className="text-sm text-destructive">{errors.name}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="type">
                        Type <span className="text-destructive">*</span>
                      </Label>
                      <Select
                        value={data.type}
                        onValueChange={(value) => setData('type', value)}
                      >
                        <SelectTrigger className={errors.type ? 'border-destructive' : ''}>
                          <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                          {item_types.map((type) => (
                            <SelectItem key={type.value} value={type.value}>
                              {type.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                      {errors.type && (
                        <p className="text-sm text-destructive">{errors.type}</p>
                      )}
                    </div>
                  </div>

                  <div className="space-y-2">
                    <Label htmlFor="description">Description</Label>
                    <Textarea
                      id="description"
                      value={data.description}
                      onChange={(e) => setData('description', e.target.value)}
                      placeholder="Describe your item..."
                      rows={3}
                    />
                  </div>

                  <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="category">Category</Label>
                      <Select
                        value={data.category_id}
                        onValueChange={(value) => setData('category_id', value)}
                      >
                        <SelectTrigger>
                          <SelectValue placeholder="Select category" />
                        </SelectTrigger>
                        <SelectContent>
                          {categories.map((category) => (
                            <SelectItem key={category.id} value={category.id.toString()}>
                              {category.name}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="sku">SKU</Label>
                      <Input
                        id="sku"
                        value={data.sku}
                        onChange={(e) => setData('sku', e.target.value)}
                        placeholder="e.g., EMP-001"
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Pricing */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <DollarSign className="h-5 w-5" />
                    Pricing
                  </CardTitle>
                  <CardDescription>
                    Set your base price and cost information
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="grid gap-6 md:grid-cols-2">
                    <div className="space-y-2">
                      <Label htmlFor="base_price">
                        Base Price <span className="text-destructive">*</span>
                      </Label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                          $
                        </span>
                        <Input
                          id="base_price"
                          type="number"
                          step="0.01"
                          value={data.base_price}
                          onChange={(e) => setData('base_price', e.target.value)}
                          className={cn('pl-8', errors.base_price ? 'border-destructive' : '')}
                          placeholder="0.00"
                        />
                      </div>
                      {errors.base_price && (
                        <p className="text-sm text-destructive">{errors.base_price}</p>
                      )}
                    </div>

                    <div className="space-y-2">
                      <Label htmlFor="cost">Cost</Label>
                      <div className="relative">
                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                          $
                        </span>
                        <Input
                          id="cost"
                          type="number"
                          step="0.01"
                          value={data.cost}
                          onChange={(e) => setData('cost', e.target.value)}
                          className="pl-8"
                          placeholder="0.00"
                        />
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Your cost to produce/acquire this item
                      </p>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Inventory & Settings */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Settings className="h-5 w-5" />
                    Settings
                  </CardTitle>
                  <CardDescription>
                    Configure inventory tracking and availability
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-6">
                  <div className="grid gap-6 md:grid-cols-2">
                    <div className="flex items-center justify-between space-x-2">
                      <div className="space-y-0.5">
                        <Label htmlFor="track_stock">Track Stock</Label>
                        <p className="text-xs text-muted-foreground">
                          Monitor inventory levels for this item
                        </p>
                      </div>
                      <Switch
                        id="track_stock"
                        checked={data.track_stock}
                        onCheckedChange={(checked) => setData('track_stock', checked)}
                      />
                    </div>

                    <div className="flex items-center justify-between space-x-2">
                      <div className="space-y-0.5">
                        <Label htmlFor="is_available">Available</Label>
                        <p className="text-xs text-muted-foreground">
                          Item can be ordered by customers
                        </p>
                      </div>
                      <Switch
                        id="is_available"
                        checked={data.is_available}
                        onCheckedChange={(checked) => setData('is_available', checked)}
                      />
                    </div>
                  </div>

                  {features.modifiers && (
                    <div className="flex items-center justify-between space-x-2">
                      <div className="space-y-0.5">
                        <Label htmlFor="allow_modifiers">Allow Modifiers</Label>
                        <p className="text-xs text-muted-foreground">
                          Customers can customize this item with modifiers
                        </p>
                      </div>
                      <Switch
                        id="allow_modifiers"
                        checked={data.allow_modifiers}
                        onCheckedChange={(checked) => setData('allow_modifiers', checked)}
                      />
                    </div>
                  )}

                  <div className="space-y-2">
                    <Label htmlFor="preparation_time">Preparation Time (minutes)</Label>
                    <Input
                      id="preparation_time"
                      type="number"
                      value={data.preparation_time}
                      onChange={(e) => setData('preparation_time', e.target.value)}
                      placeholder="15"
                    />
                  </div>
                </CardContent>
              </Card>

              {/* Variants */}
              {features.variants && (
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Package className="h-5 w-5" />
                      Variants
                    </CardTitle>
                    <CardDescription>
                      Add size, color, or other variations of this item
                    </CardDescription>
                  </CardHeader>
                  <CardContent>
                    {variants.length === 0 ? (
                      <div className="text-center py-6">
                        <p className="text-muted-foreground mb-4">
                          No variants added yet
                        </p>
                        <Button
                          type="button"
                          variant="outline"
                          onClick={addVariant}
                        >
                          <Plus className="mr-2 h-4 w-4" />
                          Add Variant
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-4">
                        {variants.map((variant, index) => (
                          <div key={index} className="border rounded-lg p-4 space-y-4">
                            <div className="flex justify-between items-start">
                              <h4 className="font-medium">Variant {index + 1}</h4>
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => removeVariant(index)}
                              >
                                <X className="h-4 w-4" />
                              </Button>
                            </div>
                            
                            <div className="grid gap-4 md:grid-cols-2">
                              <div className="space-y-2">
                                <Label>Variant Name</Label>
                                <Input
                                  value={variant.name}
                                  onChange={(e) => updateVariant(index, 'name', e.target.value)}
                                  placeholder="e.g., Large"
                                />
                              </div>
                              
                              <div className="space-y-2">
                                <Label>SKU</Label>
                                <Input
                                  value={variant.sku}
                                  onChange={(e) => updateVariant(index, 'sku', e.target.value)}
                                  placeholder="e.g., EMP-001-L"
                                />
                              </div>
                              
                              <div className="space-y-2">
                                <Label>Price</Label>
                                <div className="relative">
                                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                    $
                                  </span>
                                  <Input
                                    type="number"
                                    step="0.01"
                                    value={variant.price}
                                    onChange={(e) => updateVariant(index, 'price', parseFloat(e.target.value))}
                                    className="pl-8"
                                  />
                                </div>
                              </div>
                              
                              <div className="space-y-2">
                                <Label>Cost</Label>
                                <div className="relative">
                                  <span className="absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                    $
                                  </span>
                                  <Input
                                    type="number"
                                    step="0.01"
                                    value={variant.cost}
                                    onChange={(e) => updateVariant(index, 'cost', parseFloat(e.target.value))}
                                    className="pl-8"
                                  />
                                </div>
                              </div>
                            </div>
                            
                            <div className="flex gap-6">
                              <div className="flex items-center space-x-2">
                                <Switch
                                  checked={variant.track_stock}
                                  onCheckedChange={(checked) => updateVariant(index, 'track_stock', checked)}
                                />
                                <Label>Track Stock</Label>
                              </div>
                              
                              <div className="flex items-center space-x-2">
                                <Switch
                                  checked={variant.is_available}
                                  onCheckedChange={(checked) => updateVariant(index, 'is_available', checked)}
                                />
                                <Label>Available</Label>
                              </div>
                            </div>
                          </div>
                        ))}
                        
                        <Button
                          type="button"
                          variant="outline"
                          onClick={addVariant}
                          className="w-full"
                        >
                          <Plus className="mr-2 h-4 w-4" />
                          Add Another Variant
                        </Button>
                      </div>
                    )}
                  </CardContent>
                </Card>
              )}

              {/* Image Upload */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <ImageIcon className="h-5 w-5" />
                    Image
                  </CardTitle>
                  <CardDescription>
                    Upload an image for this item
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    <div className="flex items-center justify-center w-full">
                      <label
                        htmlFor="image-upload"
                        className="flex flex-col items-center justify-center w-full h-64 border-2 border-dashed rounded-lg cursor-pointer bg-muted/30 hover:bg-muted/50 transition-colors"
                      >
                        <div className="flex flex-col items-center justify-center pt-5 pb-6">
                          <Upload className="w-10 h-10 mb-3 text-muted-foreground" />
                          <p className="mb-2 text-sm text-muted-foreground">
                            <span className="font-semibold">Click to upload</span> or drag and drop
                          </p>
                          <p className="text-xs text-muted-foreground">
                            PNG, JPG or WEBP (MAX. 2MB)
                          </p>
                        </div>
                        <input
                          id="image-upload"
                          type="file"
                          className="hidden"
                          accept="image/*"
                          onChange={handleImageChange}
                        />
                      </label>
                    </div>
                    
                    {data.image && (
                      <div className="flex items-center gap-2 p-3 border rounded-lg">
                        <ImageIcon className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm flex-1">{data.image.name}</span>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => setData('image', null)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    )}
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Form Actions */}
            <div className="flex justify-end gap-4 mt-6">
              <Button
                type="button"
                variant="outline"
                onClick={() => router.visit('/items')}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                <Save className="mr-2 h-4 w-4" />
                Save Item
              </Button>
            </div>
          </form>
        </PageLayout.Content>
      </PageLayout>
    </>
  );
}