import { useForm } from '@inertiajs/react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CurrencyInput } from '@/components/currency-input';
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
  Image as ImageIcon,
  Layers,
  Archive,
  Sliders,
  BarChart,
  Hash,
  Clock,
  Calculator,
  Tag,
  Images,
  Trash2
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { cn } from '@/lib/utils';
import { useState, useEffect } from 'react';
import { ImageField } from '@/components/ui/image-field';
import { BundleSelector } from '@/modules/item';

interface Category {
  id: number;
  name: string;
}

interface ExistingImage {
  id: number;
  url: string;
  is_primary: boolean;
}

interface ExistingVariant {
  id?: number;
  name: string;
  price: number;
  cost: number | null;
  sku: string | null;
  track_stock: boolean;
  is_available: boolean;
  current_stock?: number;
}

interface BundleItem {
  id: number;
  name: string;
  price: number;
  sku?: string;
  quantity: number;
}

interface Item {
  id: number;
  name: string;
  description: string | null;
  type: 'product' | 'service' | 'combo';
  category_id: number | null;
  base_price?: number | null;
  basePrice?: number | null;
  base_cost?: number | null;
  baseCost?: number | null;
  sku: string | null;
  barcode: string | null;
  track_inventory?: boolean;
  trackInventory?: boolean;
  is_available?: boolean;
  isAvailable?: boolean;
  allow_modifiers?: boolean;
  allowModifiers?: boolean;
  preparation_time?: number | null;
  preparationTime?: number | null;
  available_from: string | null;
  available_until: string | null;
  images: ExistingImage[];
  variants: ExistingVariant[];
  bundle_items: BundleItem[];
  modifier_groups: number[];
  tags: string[];
  allergens: string[];
}

interface PageProps {
  item: Item;
  categories: Category[];
  item_types: Array<{ value: string; label: string }>;
  features: {
    variants: boolean;
    modifiers: boolean;
    inventory: boolean;
    multiple_images: boolean;
  };
  available_items?: Array<{
    id: number;
    name: string;
    price: number;
    sku?: string;
  }>;
}

interface Variant {
  id?: number;
  name: string;
  price_adjustment: number;
  stock_quantity: number;
  is_active: boolean;
  is_default: boolean;
  _destroy?: boolean;
}

export default function ItemEdit({ item, categories, item_types, features, available_items = [] }: PageProps) {
  // Convert existing variants to edit format
  const convertExistingVariants = (existingVariants: ExistingVariant[]): Variant[] => {
    return existingVariants.map((variant, index) => ({
      id: variant.id,
      name: variant.name,
      price_adjustment: variant.price - (item.base_price || 0),
      stock_quantity: variant.current_stock || 0,
      is_active: variant.is_available,
      is_default: index === 0,
    }));
  };

  const [variants, setVariants] = useState<Variant[]>(convertExistingVariants(item.variants));
  const [bundleItems, setBundleItems] = useState<BundleItem[]>(item.bundle_items || []);
  const [isCompoundType, setIsCompoundType] = useState(item.type === 'combo');
  const [deletedImageIds, setDeletedImageIds] = useState<number[]>([]);

  // Handle both camelCase and snake_case properties
  const basePrice = item.basePrice ?? item.base_price;
  const baseCost = item.baseCost ?? item.base_cost;
  const trackInventory = item.trackInventory ?? item.track_inventory;
  const isAvailable = item.isAvailable ?? item.is_available;
  const allowModifiers = item.allowModifiers ?? item.allow_modifiers;
  const preparationTime = item.preparationTime ?? item.preparation_time;

  const { data, setData, put, processing, errors, reset } = useForm<any>({
    name: item.name || '',
    description: item.description || '',
    type: item.type || 'product',
    category_id: item.category_id ? item.category_id.toString() : '',
    base_price: basePrice ?? null,  // Store as integer (minor units)
    base_cost: baseCost ?? null,    // Store as integer (minor units)
    sku: item.sku || '',
    barcode: item.barcode || '',
    track_inventory: trackInventory ?? true,
    is_available: isAvailable ?? true,
    allow_modifiers: allowModifiers ?? false,
    preparation_time: preparationTime ? preparationTime.toString() : '',
    available_from: item.available_from || '',
    available_until: item.available_until || '',
    image_url: item.images.find(img => img.is_primary)?.url || null,
    additional_images: [] as File[],
    variants: [] as Variant[],
    bundle_items: [] as BundleItem[],
    modifier_groups: item.modifier_groups || [],
    tags: item.tags || [],
    allergens: item.allergens || [],
    nutritional_info: {},
    deleted_image_ids: [] as number[],
  });

  // Check if item type is compound (combo)
  useEffect(() => {
    setIsCompoundType(data.type === 'combo');
  }, [data.type]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    const formData = {
      ...data,
      variants: features.variants ? variants.filter(v => !v._destroy) : [],
      bundle_items: isCompoundType ? bundleItems : [],
      // Ensure arrays are properly formatted
      tags: data.tags.filter(tag => tag.trim() !== ''),
      allergens: data.allergens.filter(allergen => allergen.trim() !== ''),
      // Handle image URL
      image_url: data.image_url,
      // Handle multiple images
      images: [...data.additional_images].filter(Boolean),
      // Include deleted image IDs
      deleted_image_ids: deletedImageIds,
    };
    
    put(`/items/${item.id}`, formData);
  };

  const addVariant = () => {
    setVariants([
      ...variants,
      {
        name: '',
        price_adjustment: 0,
        stock_quantity: 0,
        is_active: true,
        is_default: variants.length === 0,
      },
    ]);
  };

  const updateVariant = (index: number, field: keyof Variant, value: any) => {
    const updated = [...variants];
    updated[index] = { ...updated[index], [field]: value };
    setVariants(updated);
  };

  const removeVariant = (index: number) => {
    const variant = variants[index];
    if (variant.id) {
      // Mark existing variant for deletion
      const updated = [...variants];
      updated[index] = { ...updated[index], _destroy: true };
      setVariants(updated);
    } else {
      // Remove new variant
      setVariants(variants.filter((_, i) => i !== index));
    }
  };

  const deleteExistingImage = (imageId: number) => {
    setDeletedImageIds([...deletedImageIds, imageId]);
  };

  const existingImages = item.images.filter(img => !deletedImageIds.includes(img.id));

  return (
    <AppLayout>
      <Head title={`Edit ${item.name}`} />
      
      <Page>
        <Page.Header
          title="Edit Item"
          subtitle={`Editing: ${item.name}`}
          actions={
            <Page.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit(`/items/${item.id}`)}
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
                Save Changes
              </Button>
            </Page.Actions>
          }
        />
        
        <Page.Content>
          <form onSubmit={handleSubmit} className="max-w-6xl mx-auto">
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
                  <CardTitle className="flex items-center gap-2">
                    <Package className="h-5 w-5" />
                    Basic Information
                  </CardTitle>
                  <CardDescription>
                    Essential details about your item
                  </CardDescription>
                </CardHeader>
                <CardContent className="p-6">
                  <div className="grid gap-6 lg:grid-cols-3">
                    {/* Left side - 2/3 */}
                    <div className="lg:col-span-2 space-y-6">
                      {/* Item Name */}
                      <div className="space-y-2">
                        <Label htmlFor="name">
                          Item Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                          id="name"
                          value={data.name}
                          onChange={(e) => setData('name', e.target.value)}
                          placeholder="e.g., Empanada de Pino"
                          className={cn(
                            "text-lg",
                            errors.name ? 'border-destructive' : ''
                          )}
                        />
                        {errors.name && (
                          <p className="text-sm text-destructive">{errors.name}</p>
                        )}
                      </div>

                      {/* Price and Type Grid */}
                      <div className="grid gap-6 sm:grid-cols-2">
                        {/* Price */}
                        <div className="space-y-2">
                          <Label htmlFor="base_price">
                            Price
                            <span className="text-xs text-muted-foreground ml-2">(Optional)</span>
                          </Label>
                          <CurrencyInput
                            id="base_price"
                            value={data.base_price}
                            onChange={(value) => setData('base_price', value)}
                            showSymbol={true}
                            className={cn(errors.base_price ? 'border-destructive' : '')}
                            placeholder="0.00"
                          />
                          {errors.base_price && (
                            <p className="text-sm text-destructive">{errors.base_price}</p>
                          )}
                        </div>

                        {/* Item Type */}
                        <div className="space-y-2">
                          <Label htmlFor="type">
                            Item Type <span className="text-destructive">*</span>
                          </Label>
                          <Select
                            value={data.type}
                            onValueChange={(value) => setData('type', value)}
                          >
                            <SelectTrigger className={errors.type ? 'border-destructive' : ''}>
                              <SelectValue placeholder="Select type" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="product">
                                <div className="flex items-center gap-2">
                                  <Package className="h-4 w-4" />
                                  Product
                                </div>
                              </SelectItem>
                              <SelectItem value="service">
                                <div className="flex items-center gap-2">
                                  <Settings className="h-4 w-4" />
                                  Service
                                </div>
                              </SelectItem>
                              <SelectItem value="combo">
                                <div className="flex items-center gap-2">
                                  <Layers className="h-4 w-4" />
                                  Combo
                                </div>
                              </SelectItem>
                            </SelectContent>
                          </Select>
                          {errors.type && (
                            <p className="text-sm text-destructive">{errors.type}</p>
                          )}
                        </div>
                      </div>

                      {/* Description */}
                      <div className="space-y-2">
                        <Label htmlFor="description">
                          Description
                          <span className="text-xs text-muted-foreground ml-2">(Optional)</span>
                        </Label>
                        <Textarea
                          id="description"
                          value={data.description}
                          onChange={(e) => setData('description', e.target.value)}
                          placeholder="Brief description of your item..."
                          rows={3}
                          className="resize-none"
                        />
                      </div>
                    </div>

                    {/* Right side - 1/3 */}
                    <div>
                      {/* Image Field */}
                      <ImageField
                        value={data.image_url}
                        onChange={(url) => setData('image_url', url)}
                        label="Product Image"
                        error={errors.image_url}
                      />
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Bundle Configuration - Shows only for compound types */}
              {isCompoundType && (
                <Card>
                  <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                      <Layers className="h-5 w-5" />
                      Bundle Configuration
                    </CardTitle>
                    <CardDescription>
                      Configure the items that make up this bundle/combo
                    </CardDescription>
                  </CardHeader>
                  <CardContent className="p-6">
                    <BundleSelector
                      availableItems={available_items}
                      selectedItems={bundleItems}
                      onItemsChange={setBundleItems}
                    />
                  </CardContent>
                </Card>
              )}

              {/* Additional Information Tabs */}
              <Tabs defaultValue="inventory" className="w-full">
                <div className="overflow-x-auto pb-2">
                  <TabsList className="inline-flex h-10 items-center justify-start rounded-lg bg-muted p-1 text-muted-foreground w-full min-w-max">
                    <TabsTrigger value="inventory" className="gap-1.5">
                      <Archive className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Inventory</span>
                    </TabsTrigger>
                    <TabsTrigger value="availability" className="gap-1.5">
                      <Clock className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Availability</span>
                    </TabsTrigger>
                    {features.variants && (
                      <TabsTrigger value="variants" className="gap-1.5">
                        <Layers className="h-3.5 w-3.5" />
                        <span className="hidden sm:inline">Variants</span>
                      </TabsTrigger>
                    )}
                    {features.modifiers && (
                      <TabsTrigger value="modifiers" className="gap-1.5">
                        <Sliders className="h-3.5 w-3.5" />
                        <span className="hidden sm:inline">Modifiers</span>
                      </TabsTrigger>
                    )}
                    <TabsTrigger value="cost" className="gap-1.5">
                      <Calculator className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Cost</span>
                    </TabsTrigger>
                    <TabsTrigger value="media" className="gap-1.5">
                      <Images className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Media</span>
                    </TabsTrigger>
                    <TabsTrigger value="tags" className="gap-1.5">
                      <Tag className="h-3.5 w-3.5" />
                      <span className="hidden sm:inline">Tags</span>
                    </TabsTrigger>
                  </TabsList>
                </div>

                {/* Inventory Tab */}
                <TabsContent value="inventory">
                  <Card>
                    <CardHeader>
                      <CardTitle>Inventory & Stock</CardTitle>
                      <CardDescription>
                        Manage stock levels and availability
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                      <div className="grid gap-6 sm:grid-cols-2">
                        <div className="flex items-center justify-between space-x-2">
                          <div className="space-y-0.5">
                            <Label htmlFor="track_inventory">Track Stock</Label>
                            <p className="text-xs text-muted-foreground">
                              Monitor inventory levels for this item
                            </p>
                          </div>
                          <Switch
                            id="track_inventory"
                            checked={data.track_inventory}
                            onCheckedChange={(checked) => setData('track_inventory', checked)}
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

                      <Separator />

                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <Label htmlFor="barcode">Barcode</Label>
                          <Input
                            id="barcode"
                            value={data.barcode}
                            onChange={(e) => setData('barcode', e.target.value)}
                            placeholder="Enter barcode"
                          />
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>

                {/* Availability Tab */}
                <TabsContent value="availability">
                  <Card>
                    <CardHeader>
                      <CardTitle>Availability & Scheduling</CardTitle>
                      <CardDescription>
                        Set when and how this item can be ordered
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <Label htmlFor="preparation_time">
                            Preparation Time
                            <span className="text-xs text-muted-foreground ml-2">(minutes)</span>
                          </Label>
                          <div className="relative">
                            <Clock className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                            <Input
                              id="preparation_time"
                              type="number"
                              value={data.preparation_time}
                              onChange={(e) => setData('preparation_time', e.target.value)}
                              placeholder="15"
                              className="pl-10"
                            />
                          </div>
                          <p className="text-xs text-muted-foreground">
                            Average time to prepare this item
                          </p>
                        </div>

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
                      </div>

                      <Separator />

                      <div className="space-y-4">
                        <h4 className="text-sm font-medium">Schedule Availability</h4>
                        <div className="grid gap-4 sm:grid-cols-2">
                          <div className="space-y-2">
                            <Label htmlFor="available_from">Available From</Label>
                            <Input
                              id="available_from"
                              type="datetime-local"
                              value={data.available_from}
                              onChange={(e) => setData('available_from', e.target.value)}
                            />
                            <p className="text-xs text-muted-foreground">
                              Leave empty for immediate availability
                            </p>
                          </div>

                          <div className="space-y-2">
                            <Label htmlFor="available_until">Available Until</Label>
                            <Input
                              id="available_until"
                              type="datetime-local"
                              value={data.available_until}
                              onChange={(e) => setData('available_until', e.target.value)}
                            />
                            <p className="text-xs text-muted-foreground">
                              Leave empty for no end date
                            </p>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>

                {/* Variants Tab */}
                {features.variants && (
                  <TabsContent value="variants">
                    <Card>
                      <CardHeader>
                        <CardTitle>Product Variants</CardTitle>
                        <CardDescription>
                          Add size, color, or other variations of this item
                        </CardDescription>
                      </CardHeader>
                      <CardContent>
                        {variants.filter(v => !v._destroy).length === 0 ? (
                          <div className="text-center py-8">
                            <div className="rounded-full bg-muted p-3 w-fit mx-auto mb-4">
                              <Layers className="h-6 w-6 text-muted-foreground" />
                            </div>
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
                            {variants.map((variant, index) => {
                              if (variant._destroy) return null;
                              
                              return (
                                <div key={index} className="border rounded-lg p-4 space-y-4">
                                  <div className="flex justify-between items-start">
                                    <h4 className="font-medium">
                                      {variant.id ? `${variant.name} (Existing)` : `Variant ${index + 1}`}
                                    </h4>
                                    <Button
                                      type="button"
                                      variant="ghost"
                                      size="sm"
                                      onClick={() => removeVariant(index)}
                                    >
                                      <X className="h-4 w-4" />
                                    </Button>
                                  </div>
                                  
                                  <div className="grid gap-4">
                                    <div className="space-y-2">
                                      <Label>Variant Name</Label>
                                      <Input
                                        value={variant.name}
                                        onChange={(e) => updateVariant(index, 'name', e.target.value)}
                                        placeholder="e.g., Large, Medium, Small"
                                      />
                                    </div>
                                    
                                    <div className="grid gap-4 md:grid-cols-2">
                                      <div className="space-y-2">
                                        <Label>Price Adjustment</Label>
                                        <div className="flex items-center gap-2">
                                          <Select
                                            value={variant.price_adjustment >= 0 ? 'add' : 'subtract'}
                                            onValueChange={(value) => {
                                              const absValue = Math.abs(variant.price_adjustment);
                                              updateVariant(index, 'price_adjustment', value === 'add' ? absValue : -absValue);
                                            }}
                                          >
                                            <SelectTrigger className="w-24">
                                              <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                              <SelectItem value="add">+</SelectItem>
                                              <SelectItem value="subtract">−</SelectItem>
                                            </SelectContent>
                                          </Select>
                                          <CurrencyInput
                                            value={Math.abs(variant.price_adjustment)}
                                            onChange={(value) => {
                                              const absValue = value || 0;
                                              updateVariant(index, 'price_adjustment', variant.price_adjustment >= 0 ? absValue : -absValue);
                                            }}
                                                            showSymbol={true}
                                            className="flex-1"
                                          />
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                          Added to base price
                                        </p>
                                      </div>
                                      
                                      <div className="space-y-2">
                                        <Label>Stock Quantity</Label>
                                        <Input
                                          type="number"
                                          value={variant.stock_quantity}
                                          onChange={(e) => updateVariant(index, 'stock_quantity', parseInt(e.target.value) || 0)}
                                          placeholder="0"
                                        />
                                      </div>
                                    </div>
                                  </div>
                                  
                                  <div className="flex gap-6">
                                    <div className="flex items-center space-x-2">
                                      <Switch
                                        checked={variant.is_active}
                                        onCheckedChange={(checked) => updateVariant(index, 'is_active', checked)}
                                      />
                                      <Label>Active</Label>
                                    </div>
                                    
                                    <div className="flex items-center space-x-2">
                                      <Switch
                                        checked={variant.is_default}
                                        onCheckedChange={(checked) => {
                                          // Ensure only one default variant
                                          if (checked) {
                                            setVariants(variants.map((v, i) => ({
                                              ...v,
                                              is_default: i === index
                                            })));
                                          } else {
                                            updateVariant(index, 'is_default', false);
                                          }
                                        }}
                                      />
                                      <Label>Default</Label>
                                    </div>
                                  </div>
                                </div>
                              );
                            })}
                            
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
                  </TabsContent>
                )}

                {/* Modifiers Tab */}
                {features.modifiers && (
                  <TabsContent value="modifiers">
                    <Card>
                      <CardHeader>
                        <CardTitle>Item Modifiers</CardTitle>
                        <CardDescription>
                          Allow customers to customize this item
                        </CardDescription>
                      </CardHeader>
                      <CardContent>
                        <div className="space-y-6">
                          <div className="flex items-center justify-between">
                            <div className="space-y-0.5">
                              <Label htmlFor="allow_modifiers">Enable Modifiers</Label>
                              <p className="text-xs text-muted-foreground">
                                Customers can customize this item with add-ons and options
                              </p>
                            </div>
                            <Switch
                              id="allow_modifiers"
                              checked={data.allow_modifiers}
                              onCheckedChange={(checked) => setData('allow_modifiers', checked)}
                            />
                          </div>

                          {data.allow_modifiers && (
                            <div className="space-y-4 pt-4 border-t">
                              <p className="text-sm text-muted-foreground">
                                Configure modifier groups for this item
                              </p>
                              <Button
                                type="button"
                                variant="outline"
                                onClick={() => router.visit(`/items/${item.id}/modifiers`)}
                              >
                                <Sliders className="mr-2 h-4 w-4" />
                                Manage Modifier Groups
                              </Button>
                            </div>
                          )}
                        </div>
                      </CardContent>
                    </Card>
                  </TabsContent>
                )}

                {/* Cost Tab */}
                <TabsContent value="cost">
                  <Card>
                    <CardHeader>
                      <CardTitle>Cost Analysis</CardTitle>
                      <CardDescription>
                        Track costs and calculate profit margins
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                      <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                          <Label htmlFor="base_cost">Base Cost</Label>
                          <CurrencyInput
                            id="base_cost"
                            value={data.base_cost}
                            onChange={(value) => setData('base_cost', value)}
                            showSymbol={true}
                            placeholder="0.00"
                          />
                          <p className="text-xs text-muted-foreground">
                            Your cost to produce/acquire this item
                          </p>
                        </div>

                        <div className="space-y-2">
                          <Label>Profit Margin</Label>
                          <div className="p-3 bg-muted rounded-lg">
                            <div className="text-2xl font-semibold">
                              {data.base_price && data.base_cost
                                ? `${Math.round(((data.base_price - data.base_cost) / data.base_price) * 100)}%`
                                : '—'}
                            </div>
                            <p className="text-xs text-muted-foreground mt-1">
                              {data.base_price && data.base_cost
                                ? `${formatCurrency(data.base_price - data.base_cost)} profit per item`
                                : 'Set price and cost to calculate'}
                            </p>
                          </div>
                        </div>
                      </div>

                      <Separator />

                      <div className="space-y-4">
                        <h4 className="text-sm font-medium">Cost Breakdown</h4>
                        <div className="text-center py-8 border-2 border-dashed rounded-lg">
                          <Calculator className="h-8 w-8 mx-auto mb-3 text-muted-foreground" />
                          <p className="text-sm text-muted-foreground">
                            Detailed cost breakdown and recipe management
                          </p>
                          <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="mt-4"
                            onClick={() => router.visit(`/items/${item.id}/recipe`)}
                          >
                            Manage Recipe
                          </Button>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>

                {/* Media Tab */}
                <TabsContent value="media">
                  <Card>
                    <CardHeader>
                      <CardTitle>Additional Images</CardTitle>
                      <CardDescription>
                        Add multiple images to showcase your item
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="space-y-4">
                        <p className="text-sm text-muted-foreground">
                          The first image will be used as the primary display image
                        </p>
                        <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                          {/* Existing Images */}
                          {existingImages.map((image) => (
                            <div key={image.id} className="relative group">
                              <div className="aspect-square rounded-lg overflow-hidden bg-muted">
                                <img
                                  src={image.url}
                                  alt={`Product image ${image.id}`}
                                  className="w-full h-full object-cover"
                                />
                              </div>
                              <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                onClick={() => deleteExistingImage(image.id)}
                              >
                                <Trash2 className="h-3 w-3" />
                              </Button>
                              {image.is_primary && (
                                <Badge className="absolute bottom-2 left-2 text-xs">
                                  Primary
                                </Badge>
                              )}
                            </div>
                          ))}
                          
                          {/* New Images */}
                          {data.additional_images.map((image, index) => (
                            <div key={`new-${index}`} className="relative group">
                              <div className="aspect-square rounded-lg overflow-hidden bg-muted">
                                <img
                                  src={URL.createObjectURL(image)}
                                  alt={`New product image ${index + 1}`}
                                  className="w-full h-full object-cover"
                                />
                              </div>
                              <Button
                                type="button"
                                variant="destructive"
                                size="sm"
                                className="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity"
                                onClick={() => {
                                  const newImages = data.additional_images.filter((_, i) => i !== index);
                                  setData('additional_images', newImages);
                                }}
                              >
                                <X className="h-3 w-3" />
                              </Button>
                              <Badge variant="secondary" className="absolute bottom-2 left-2 text-xs">
                                New
                              </Badge>
                            </div>
                          ))}
                          
                          <label className="aspect-square rounded-lg border-2 border-dashed cursor-pointer hover:border-muted-foreground/50 transition-colors flex items-center justify-center">
                            <div className="text-center">
                              <Plus className="h-8 w-8 mx-auto mb-2 text-muted-foreground" />
                              <span className="text-xs text-muted-foreground">Add Image</span>
                            </div>
                            <input
                              type="file"
                              multiple
                              accept="image/*"
                              className="hidden"
                              onChange={(e) => {
                                if (e.target.files) {
                                  const newImages = Array.from(e.target.files);
                                  setData('additional_images', [...data.additional_images, ...newImages]);
                                }
                              }}
                            />
                          </label>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>

                {/* Tags Tab */}
                <TabsContent value="tags">
                  <Card>
                    <CardHeader>
                      <CardTitle>Tags & Categories</CardTitle>
                      <CardDescription>
                        Organize and categorize your item for better discoverability
                      </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                      <div className="space-y-2">
                        <Label>Tags</Label>
                        <div className="flex flex-wrap gap-2 mb-2">
                          {data.tags.map((tag, index) => (
                            <Badge key={index} variant="secondary" className="gap-1">
                              {tag}
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-auto p-0 ml-1"
                                onClick={() => {
                                  const newTags = data.tags.filter((_, i) => i !== index);
                                  setData('tags', newTags);
                                }}
                              >
                                <X className="h-3 w-3" />
                              </Button>
                            </Badge>
                          ))}
                        </div>
                        <Input
                          placeholder="Type a tag and press Enter"
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                              e.preventDefault();
                              const input = e.currentTarget;
                              const value = input.value.trim();
                              if (value && !data.tags.includes(value)) {
                                setData('tags', [...data.tags, value]);
                                input.value = '';
                              }
                            }
                          }}
                        />
                        <p className="text-xs text-muted-foreground">
                          Tags help customers find your item
                        </p>
                      </div>

                      <Separator />

                      <div className="space-y-2">
                        <Label>Allergens</Label>
                        <div className="flex flex-wrap gap-2 mb-2">
                          {data.allergens.map((allergen, index) => (
                            <Badge key={index} variant="destructive" className="gap-1">
                              {allergen}
                              <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-auto p-0 ml-1 hover:bg-transparent"
                                onClick={() => {
                                  const newAllergens = data.allergens.filter((_, i) => i !== index);
                                  setData('allergens', newAllergens);
                                }}
                              >
                                <X className="h-3 w-3" />
                              </Button>
                            </Badge>
                          ))}
                        </div>
                        <Input
                          placeholder="Add allergen information (e.g., nuts, dairy)"
                          onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                              e.preventDefault();
                              const input = e.currentTarget;
                              const value = input.value.trim();
                              if (value && !data.allergens.includes(value)) {
                                setData('allergens', [...data.allergens, value]);
                                input.value = '';
                              }
                            }
                          }}
                        />
                        <p className="text-xs text-muted-foreground">
                          Important for customer safety and dietary restrictions
                        </p>
                      </div>
                    </CardContent>
                  </Card>
                </TabsContent>
              </Tabs>
            </div>

            {/* Form Actions */}
            <div className="flex justify-end gap-4 mt-6">
              <Button
                type="button"
                variant="outline"
                onClick={() => router.visit(`/items/${item.id}`)}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                <Save className="mr-2 h-4 w-4" />
                Save Changes
              </Button>
            </div>
          </form>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}