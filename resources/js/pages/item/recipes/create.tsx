import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import PageLayout from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { 
  Plus,
  Trash,
  ArrowLeft,
  Save,
  X
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface Ingredient {
  item_id: number | null;
  quantity: number;
  unit: string;
  notes: string;
}

interface Instruction {
  step_number: number;
  instruction: string;
  duration_minutes: number | null;
}

interface PageProps {
  items: Array<{ id: number; name: string; unit?: string }>;
  units: Array<{ value: string; label: string }>;
  difficulty_levels: Array<{ value: string; label: string }>;
}

export default function CreateRecipe({ 
  items,
  units,
  difficulty_levels
}: PageProps) {
  const [ingredients, setIngredients] = useState<Ingredient[]>([]);
  const [instructions, setInstructions] = useState<Instruction[]>([]);

  const { data, setData, post, processing, errors } = useForm({
    name: '',
    item_id: null as number | null,
    description: '',
    yield_quantity: 1,
    yield_unit: 'portion',
    prep_time_minutes: 15,
    cook_time_minutes: 30,
    difficulty: 'medium',
    ingredients: [] as Ingredient[],
    instructions: [] as Instruction[],
  });

  const addIngredient = () => {
    const newIngredient: Ingredient = {
      item_id: null,
      quantity: 1,
      unit: 'g',
      notes: '',
    };
    setIngredients([...ingredients, newIngredient]);
  };

  const updateIngredient = (index: number, field: keyof Ingredient, value: any) => {
    const updated = [...ingredients];
    updated[index] = { ...updated[index], [field]: value };
    setIngredients(updated);
    setData('ingredients', updated);
  };

  const removeIngredient = (index: number) => {
    const updated = ingredients.filter((_, i) => i !== index);
    setIngredients(updated);
    setData('ingredients', updated);
  };

  const addInstruction = () => {
    const newInstruction: Instruction = {
      step_number: instructions.length + 1,
      instruction: '',
      duration_minutes: null,
    };
    setInstructions([...instructions, newInstruction]);
  };

  const updateInstruction = (index: number, field: keyof Instruction, value: any) => {
    const updated = [...instructions];
    updated[index] = { ...updated[index], [field]: value };
    setInstructions(updated);
    setData('instructions', updated);
  };

  const removeInstruction = (index: number) => {
    const updated = instructions.filter((_, i) => i !== index);
    // Renumber steps
    updated.forEach((inst, i) => {
      inst.step_number = i + 1;
    });
    setInstructions(updated);
    setData('instructions', updated);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/recipes', {
      onSuccess: () => {
        router.visit('/recipes');
      },
    });
  };

  return (
    <AppLayout>
      <Head title="Create Recipe" />
      
      <PageLayout>
        <PageLayout.Header
          title="Create Recipe"
          subtitle="Define ingredients and instructions for your recipe"
          actions={
            <PageLayout.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/recipes')}
              >
                <ArrowLeft className="mr-2 h-4 w-4" />
                Back to Recipes
              </Button>
            </PageLayout.Actions>
          }
        />

        <div className="mx-auto max-w-5xl px-6 py-8">
          <form onSubmit={handleSubmit} className="space-y-8">
          <Card>
            <CardHeader className="pb-6">
              <CardTitle>Basic Information</CardTitle>
              <CardDescription>
                Enter the basic details for your recipe
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-8">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="name">Recipe Name *</Label>
                  <Input
                    id="name"
                    value={data.name}
                    onChange={(e) => setData('name', e.target.value)}
                    placeholder="e.g., Classic Empanada Filling"
                    className={errors.name ? 'border-red-500' : ''}
                  />
                  {errors.name && (
                    <p className="text-sm text-red-500">{errors.name}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="item_id">For Item *</Label>
                  <Select
                    value={data.item_id?.toString() || ''}
                    onValueChange={(value) => setData('item_id', parseInt(value))}
                  >
                    <SelectTrigger className={errors.item_id ? 'border-red-500' : ''}>
                      <SelectValue placeholder="Select item" />
                    </SelectTrigger>
                    <SelectContent>
                      {items.map((item) => (
                        <SelectItem key={item.id} value={item.id.toString()}>
                          {item.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.item_id && (
                    <p className="text-sm text-red-500">{errors.item_id}</p>
                  )}
                </div>
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={data.description}
                  onChange={(e) => setData('description', e.target.value)}
                  placeholder="Brief description of the recipe..."
                  rows={3}
                />
              </div>

              <div className="grid grid-cols-2 md:grid-cols-5 gap-6">
                <div className="space-y-2">
                  <Label htmlFor="yield_quantity">Yield Quantity *</Label>
                  <Input
                    id="yield_quantity"
                    type="number"
                    min="1"
                    value={data.yield_quantity}
                    onChange={(e) => setData('yield_quantity', parseInt(e.target.value))}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="yield_unit">Unit *</Label>
                  <Select
                    value={data.yield_unit}
                    onValueChange={(value) => setData('yield_unit', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {units.map((unit) => (
                        <SelectItem key={unit.value} value={unit.value}>
                          {unit.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>

                <div className="space-y-2">
                  <Label htmlFor="prep_time">Prep Time (min)</Label>
                  <Input
                    id="prep_time"
                    type="number"
                    min="0"
                    value={data.prep_time_minutes}
                    onChange={(e) => setData('prep_time_minutes', parseInt(e.target.value))}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="cook_time">Cook Time (min)</Label>
                  <Input
                    id="cook_time"
                    type="number"
                    min="0"
                    value={data.cook_time_minutes}
                    onChange={(e) => setData('cook_time_minutes', parseInt(e.target.value))}
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="difficulty">Difficulty</Label>
                  <Select
                    value={data.difficulty}
                    onValueChange={(value) => setData('difficulty', value)}
                  >
                    <SelectTrigger>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      {difficulty_levels.map((level) => (
                        <SelectItem key={level.value} value={level.value}>
                          {level.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </div>
              </div>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-6">
              <CardTitle>Ingredients</CardTitle>
              <CardDescription>
                Add ingredients to build your recipe. Each ingredient should be an item from your inventory.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {ingredients.length > 0 ? (
                <div className="space-y-4">
                  {ingredients.map((ingredient, index) => (
                    <div key={index} className="flex gap-4 items-end p-4 rounded-lg border bg-muted/30">
                      <div className="flex-1">
                    <Label htmlFor={`ingredient-${index}`}>Ingredient</Label>
                    <Select
                      value={ingredient.item_id?.toString() || ''}
                      onValueChange={(value) => updateIngredient(index, 'item_id', parseInt(value))}
                    >
                      <SelectTrigger id={`ingredient-${index}`}>
                        <SelectValue placeholder="Select ingredient" />
                      </SelectTrigger>
                      <SelectContent>
                        {items.map((item) => (
                          <SelectItem key={item.id} value={item.id.toString()}>
                            {item.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="w-24">
                    <Label htmlFor={`quantity-${index}`}>Quantity</Label>
                    <Input
                      id={`quantity-${index}`}
                      type="number"
                      min="0"
                      step="0.01"
                      value={ingredient.quantity}
                      onChange={(e) => updateIngredient(index, 'quantity', parseFloat(e.target.value))}
                    />
                  </div>

                  <div className="w-32">
                    <Label htmlFor={`unit-${index}`}>Unit</Label>
                    <Select
                      value={ingredient.unit}
                      onValueChange={(value) => updateIngredient(index, 'unit', value)}
                    >
                      <SelectTrigger id={`unit-${index}`}>
                        <SelectValue />
                      </SelectTrigger>
                      <SelectContent>
                        {units.map((unit) => (
                          <SelectItem key={unit.value} value={unit.value}>
                            {unit.label}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  </div>

                  <div className="flex-1">
                    <Label htmlFor={`notes-${index}`}>Notes</Label>
                    <Input
                      id={`notes-${index}`}
                      value={ingredient.notes}
                      onChange={(e) => updateIngredient(index, 'notes', e.target.value)}
                      placeholder="Optional notes"
                    />
                  </div>

                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    onClick={() => removeIngredient(index)}
                  >
                    <Trash className="h-4 w-4" />
                  </Button>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <div className="rounded-full bg-muted p-3 mb-4">
                    <Plus className="h-6 w-6 text-muted-foreground" />
                  </div>
                  <p className="text-sm text-muted-foreground mb-4">
                    No ingredients added yet. Start by adding your first ingredient.
                  </p>
                </div>
              )}

              <Button
                type="button"
                variant="outline"
                onClick={addIngredient}
                className="w-full"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Ingredient
              </Button>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="pb-6">
              <CardTitle>Instructions</CardTitle>
              <CardDescription>
                Add step-by-step instructions for preparing this recipe.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {instructions.length > 0 ? (
                <div className="space-y-4">
                  {instructions.map((instruction, index) => (
                    <div key={index} className="p-4 rounded-lg border bg-muted/30">
                      <div className="flex items-center gap-2 mb-3">
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-medium">
                          {instruction.step_number}
                        </div>
                        <span className="font-medium">Step {instruction.step_number}</span>
                        <Button
                          type="button"
                          variant="ghost"
                          size="icon"
                          className="ml-auto h-8 w-8"
                          onClick={() => removeInstruction(index)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                      <div className="space-y-3">
                        <Textarea
                          value={instruction.instruction}
                          onChange={(e) => updateInstruction(index, 'instruction', e.target.value)}
                          placeholder="Describe this step..."
                          rows={3}
                          className="w-full"
                        />
                        <div className="flex items-center gap-2">
                          <Label htmlFor={`duration-${index}`} className="text-sm text-muted-foreground">
                            Duration (optional):
                          </Label>
                          <Input
                            id={`duration-${index}`}
                            type="number"
                            min="0"
                            value={instruction.duration_minutes || ''}
                            onChange={(e) => updateInstruction(index, 'duration_minutes', e.target.value ? parseInt(e.target.value) : null)}
                            placeholder="Minutes"
                            className="w-24"
                          />
                          <span className="text-sm text-muted-foreground">minutes</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <div className="rounded-full bg-muted p-3 mb-4">
                    <Plus className="h-6 w-6 text-muted-foreground" />
                  </div>
                  <p className="text-sm text-muted-foreground mb-4">
                    No instructions added yet. Add step-by-step instructions for preparing this recipe.
                  </p>
                </div>
              )}

              <Button
                type="button"
                variant="outline"
                onClick={addInstruction}
                className="w-full"
              >
                <Plus className="mr-2 h-4 w-4" />
                Add Step
              </Button>
            </CardContent>
          </Card>

          <div className="flex justify-end gap-4 pt-6">
            <Button
              type="button"
              variant="outline"
              size="lg"
              onClick={() => router.visit('/recipes')}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              size="lg"
              disabled={processing}
            >
              <Save className="mr-2 h-4 w-4" />
              Create Recipe
            </Button>
          </div>
        </form>
        </div>
      </PageLayout>
    </AppLayout>
  );
}