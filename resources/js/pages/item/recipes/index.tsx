import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import PageLayout from '@/layouts/page-layout';
import { InertiaDataTable } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
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
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Separator } from '@/components/ui/separator';
import { Progress } from '@/components/ui/progress';
import { ItemSelector } from '@/components/modules/item/item-selector';
import { 
  Beaker,
  Plus,
  MoreHorizontal,
  AlertCircle,
  DollarSign,
  Package,
  TrendingUp,
  Percent,
  Edit,
  Copy,
  Trash,
  Calculator,
  FileText,
  ChefHat,
  Scale,
  Clock,
  Users,
  BarChart3,
  ArrowUpDown,
  Info,
  Download,
  Printer,
  X
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { formatCurrency, formatDate } from '@/lib/format';
import { ColumnDef } from '@tanstack/react-table';

interface RecipeIngredient {
  id?: number;
  item_id: number;
  item_name?: string;
  quantity: number;
  unit: string;
  cost_per_unit?: number;
  total_cost?: number;
  notes?: string;
}

interface RecipeStep {
  order: number;
  instruction: string;
  duration_minutes?: number;
}

interface Recipe {
  id: number;
  name: string;
  description: string | null;
  item_id: number;
  item_name: string;
  yield_quantity: number;
  yield_unit: string;
  prep_time_minutes: number | null;
  cook_time_minutes: number | null;
  total_time_minutes: number;
  difficulty: 'easy' | 'medium' | 'hard';
  instructions: RecipeStep[];
  ingredients: RecipeIngredient[];
  total_cost: number;
  cost_per_portion: number;
  profit_margin: number;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

interface PageProps {
  recipes: Recipe[];
  pagination: any;
  metadata: any;
  items: Array<{ id: number; name: string; base_price: number; unit?: string }>;
  units: Array<{ value: string; label: string }>;
  difficulty_levels: Array<{ value: string; label: string }>;
  stats: {
    total_recipes: number;
    active_recipes: number;
    avg_profit_margin: number;
    highest_margin_recipe: string | null;
  };
  high_cost_recipes: Recipe[];
  low_margin_recipes: Recipe[];
  features: {
    recipe_scaling: boolean;
    nutrition_tracking: boolean;
    allergen_tracking: boolean;
    recipe_versioning: boolean;
  };
}

export default function RecipesIndex({ 
  recipes, 
  pagination, 
  metadata,
  items,
  units,
  difficulty_levels,
  stats,
  high_cost_recipes,
  low_margin_recipes,
  features
}: PageProps) {
  const [createDialogOpen, setCreateDialogOpen] = useState(false);
  const [editingRecipe, setEditingRecipe] = useState<Recipe | null>(null);
  const [viewDialogOpen, setViewDialogOpen] = useState(false);
  const [viewingRecipe, setViewingRecipe] = useState<Recipe | null>(null);
  const [ingredients, setIngredients] = useState<RecipeIngredient[]>([]);
  const [steps, setSteps] = useState<RecipeStep[]>([]);

  const { data, setData, post, put, processing, errors, reset } = useForm({
    name: '',
    description: '',
    item_id: '',
    yield_quantity: '1',
    yield_unit: 'portion',
    prep_time_minutes: '',
    cook_time_minutes: '',
    difficulty: 'medium',
    is_active: true,
  });

  const columns: ColumnDef<Recipe>[] = [
    {
      accessorKey: 'name',
      header: 'Recipe',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <div className="flex flex-col">
            <span className="font-medium">{recipe.name}</span>
            <span className="text-xs text-muted-foreground">
              For: {recipe.item_name}
            </span>
          </div>
        );
      },
    },
    {
      id: 'yield',
      header: 'Yield',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <div className="text-sm">
            {recipe.yield_quantity} {recipe.yield_unit}
          </div>
        );
      },
    },
    {
      accessorKey: 'difficulty',
      header: 'Difficulty',
      cell: ({ row }) => {
        const difficultyColors = {
          easy: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
          medium: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
          hard: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        };
        return (
          <Badge 
            variant="secondary" 
            className={cn('capitalize', difficultyColors[row.original.difficulty])}
          >
            {row.original.difficulty}
          </Badge>
        );
      },
    },
    {
      id: 'time',
      header: 'Time',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <div className="space-y-1 text-sm">
            {recipe.prep_time_minutes && (
              <div className="flex items-center gap-1">
                <Clock className="h-3 w-3 text-muted-foreground" />
                <span>Prep: {recipe.prep_time_minutes}m</span>
              </div>
            )}
            {recipe.cook_time_minutes && (
              <div className="flex items-center gap-1">
                <ChefHat className="h-3 w-3 text-muted-foreground" />
                <span>Cook: {recipe.cook_time_minutes}m</span>
              </div>
            )}
          </div>
        );
      },
    },
    {
      accessorKey: 'ingredients',
      header: 'Ingredients',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <div className="flex items-center gap-2">
            <Package className="h-4 w-4 text-muted-foreground" />
            <span>{recipe.ingredients.length} items</span>
          </div>
        );
      },
    },
    {
      accessorKey: 'total_cost',
      header: 'Cost',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <div className="text-right">
            <div className="font-medium">{formatCurrency(recipe.total_cost)}</div>
            <div className="text-xs text-muted-foreground">
              {formatCurrency(recipe.cost_per_portion)}/portion
            </div>
          </div>
        );
      },
    },
    {
      accessorKey: 'profit_margin',
      header: 'Margin',
      cell: ({ row }) => {
        const margin = row.original.profit_margin;
        const isLow = margin < 30;
        const isHigh = margin > 70;
        
        return (
          <div className="flex items-center gap-2">
            <div className="flex-1">
              <Progress 
                value={Math.min(margin, 100)} 
                className="h-2"
                indicatorClassName={cn(
                  isLow && "bg-red-500",
                  !isLow && !isHigh && "bg-green-500",
                  isHigh && "bg-blue-500"
                )}
              />
            </div>
            <span className={cn(
              "text-sm font-medium min-w-[3rem] text-right",
              isLow && "text-red-600",
              !isLow && !isHigh && "text-green-600",
              isHigh && "text-blue-600"
            )}>
              {margin.toFixed(1)}%
            </span>
          </div>
        );
      },
    },
    {
      accessorKey: 'is_active',
      header: 'Status',
      cell: ({ row }) => (
        <Badge variant={row.original.is_active ? 'success' : 'secondary'}>
          {row.original.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      id: 'actions',
      cell: ({ row }) => {
        const recipe = row.original;
        return (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleView(recipe)}>
                <FileText className="mr-2 h-4 w-4" />
                View Recipe
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleEdit(recipe)}>
                <Edit className="mr-2 h-4 w-4" />
                Edit
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleDuplicate(recipe)}>
                <Copy className="mr-2 h-4 w-4" />
                Duplicate
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handlePrint(recipe)}>
                <Printer className="mr-2 h-4 w-4" />
                Print Recipe
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleExport(recipe)}>
                <Download className="mr-2 h-4 w-4" />
                Export PDF
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem 
                className="text-destructive"
                onClick={() => handleDelete(recipe.id)}
              >
                <Trash className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        );
      },
    },
  ];

  const handleView = (recipe: Recipe) => {
    setViewingRecipe(recipe);
    setViewDialogOpen(true);
  };

  const handleEdit = (recipe: Recipe) => {
    setEditingRecipe(recipe);
    setData({
      name: recipe.name,
      description: recipe.description || '',
      item_id: recipe.item_id.toString(),
      yield_quantity: recipe.yield_quantity.toString(),
      yield_unit: recipe.yield_unit,
      prep_time_minutes: recipe.prep_time_minutes?.toString() || '',
      cook_time_minutes: recipe.cook_time_minutes?.toString() || '',
      difficulty: recipe.difficulty,
      is_active: recipe.is_active,
    });
    setIngredients(recipe.ingredients);
    setSteps(recipe.instructions);
    setCreateDialogOpen(true);
  };

  const handleDuplicate = (recipe: Recipe) => {
    setEditingRecipe(null);
    setData({
      name: `${recipe.name} (Copy)`,
      description: recipe.description || '',
      item_id: recipe.item_id.toString(),
      yield_quantity: recipe.yield_quantity.toString(),
      yield_unit: recipe.yield_unit,
      prep_time_minutes: recipe.prep_time_minutes?.toString() || '',
      cook_time_minutes: recipe.cook_time_minutes?.toString() || '',
      difficulty: recipe.difficulty,
      is_active: false,
    });
    setIngredients(recipe.ingredients.map(({ id, ...ing }) => ing));
    setSteps(recipe.instructions);
    setCreateDialogOpen(true);
  };

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this recipe?')) {
      router.delete(`/recipes/${id}`);
    }
  };

  const handlePrint = (recipe: Recipe) => {
    router.get(`/recipes/${recipe.id}/print`);
  };

  const handleExport = (recipe: Recipe) => {
    router.get(`/recipes/${recipe.id}/export`);
  };

  const addIngredient = () => {
    setIngredients([
      ...ingredients,
      {
        item_id: 0,
        quantity: 0,
        unit: 'g',
        notes: '',
      },
    ]);
  };

  const updateIngredient = (index: number, field: keyof RecipeIngredient, value: any) => {
    const updated = [...ingredients];
    updated[index] = { ...updated[index], [field]: value };
    setIngredients(updated);
  };

  const removeIngredient = (index: number) => {
    setIngredients(ingredients.filter((_, i) => i !== index));
  };

  const addStep = () => {
    setSteps([
      ...steps,
      {
        order: steps.length + 1,
        instruction: '',
        duration_minutes: undefined,
      },
    ]);
  };

  const updateStep = (index: number, field: keyof RecipeStep, value: any) => {
    const updated = [...steps];
    updated[index] = { ...updated[index], [field]: value };
    setSteps(updated);
  };

  const removeStep = (index: number) => {
    const updated = steps.filter((_, i) => i !== index);
    // Reorder remaining steps
    updated.forEach((step, i) => {
      step.order = i + 1;
    });
    setSteps(updated);
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (ingredients.length === 0) {
      alert('Please add at least one ingredient');
      return;
    }
    
    if (steps.length === 0) {
      alert('Please add at least one instruction step');
      return;
    }
    
    const formData = {
      ...data,
      ingredients,
      instructions: steps,
    };
    
    const url = editingRecipe ? `/recipes/${editingRecipe.id}` : '/recipes';
    const method = editingRecipe ? put : post;
    
    method(url, {
      data: formData,
      onSuccess: () => {
        setCreateDialogOpen(false);
        setEditingRecipe(null);
        setIngredients([]);
        setSteps([]);
        reset();
      },
    });
  };

  const statsCards = [
    {
      title: 'Total Recipes',
      value: stats.total_recipes,
      icon: Beaker,
      color: 'text-blue-600 dark:text-blue-400',
      bgColor: 'bg-blue-100 dark:bg-blue-900/30',
    },
    {
      title: 'Active Recipes',
      value: stats.active_recipes,
      icon: ChefHat,
      color: 'text-green-600 dark:text-green-400',
      bgColor: 'bg-green-100 dark:bg-green-900/30',
    },
    {
      title: 'Avg Profit Margin',
      value: `${stats.avg_profit_margin.toFixed(1)}%`,
      icon: Percent,
      color: 'text-purple-600 dark:text-purple-400',
      bgColor: 'bg-purple-100 dark:bg-purple-900/30',
    },
    {
      title: 'Best Margin',
      value: stats.highest_margin_recipe || 'N/A',
      icon: TrendingUp,
      color: 'text-amber-600 dark:text-amber-400',
      bgColor: 'bg-amber-100 dark:bg-amber-900/30',
      small: true,
    },
  ];

  return (
    <>
      <Head title="Recipes" />
      
      <PageLayout>
        <PageLayout.Header
          title="Recipes"
          subtitle="Manage recipes, ingredients, and cost calculations"
          actions={
            <PageLayout.Actions>
              <Button
                variant="outline"
                size="sm"
                onClick={() => router.visit('/recipes/cost-analysis')}
              >
                <Calculator className="mr-2 h-4 w-4" />
                Cost Analysis
              </Button>
              <Button
                size="sm"
                onClick={() => {
                  setEditingRecipe(null);
                  setIngredients([]);
                  setSteps([]);
                  reset();
                  setCreateDialogOpen(true);
                }}
              >
                <Plus className="mr-2 h-4 w-4" />
                New Recipe
              </Button>
            </PageLayout.Actions>
          }
        />
        
        <PageLayout.Content>
          {/* Stats Cards */}
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4 mb-6">
            {statsCards.map((stat, index) => {
              const Icon = stat.icon;
              return (
                <Card key={index}>
                  <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle className="text-sm font-medium">
                      {stat.title}
                    </CardTitle>
                    <div className={cn('p-2 rounded-lg', stat.bgColor)}>
                      <Icon className={cn('h-4 w-4', stat.color)} />
                    </div>
                  </CardHeader>
                  <CardContent>
                    <div className={cn('font-bold', stat.small ? 'text-lg' : 'text-2xl')}>
                      {stat.value}
                    </div>
                  </CardContent>
                </Card>
              );
            })}
          </div>

          {/* Alerts */}
          {low_margin_recipes.length > 0 && (
            <Alert className="mb-6">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>
                <span className="font-medium">{low_margin_recipes.length} recipes</span> have profit margins below 30%.
                Consider reviewing ingredient costs or adjusting menu prices.
              </AlertDescription>
            </Alert>
          )}

          <Tabs defaultValue="all" className="w-full">
            <TabsList>
              <TabsTrigger value="all">All Recipes</TabsTrigger>
              <TabsTrigger value="high-cost">High Cost</TabsTrigger>
              <TabsTrigger value="low-margin">Low Margin</TabsTrigger>
            </TabsList>

            <TabsContent value="all" className="mt-6">
              <Card>
                <CardContent className="p-0">
                  <InertiaDataTable
                    columns={columns}
                    data={recipes}
                    pagination={pagination}
                    filters={metadata?.filters}
                  />
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="high-cost" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>High Cost Recipes</CardTitle>
                  <CardDescription>
                    Recipes with the highest production costs
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {high_cost_recipes.length > 0 ? (
                    <div className="space-y-4">
                      {high_cost_recipes.map((recipe) => (
                        <div key={recipe.id} className="flex items-center justify-between p-4 border rounded-lg">
                          <div>
                            <h4 className="font-medium">{recipe.name}</h4>
                            <p className="text-sm text-muted-foreground mt-1">
                              {recipe.ingredients.length} ingredients • {recipe.yield_quantity} {recipe.yield_unit}
                            </p>
                          </div>
                          <div className="text-right">
                            <div className="font-medium text-lg">
                              {formatCurrency(recipe.total_cost)}
                            </div>
                            <p className="text-xs text-muted-foreground">
                              {formatCurrency(recipe.cost_per_portion)}/portion
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <DollarSign className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                      <p className="text-muted-foreground">No high-cost recipes found</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>

            <TabsContent value="low-margin" className="mt-6">
              <Card>
                <CardHeader>
                  <CardTitle>Low Margin Recipes</CardTitle>
                  <CardDescription>
                    Recipes with profit margins below 30%
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  {low_margin_recipes.length > 0 ? (
                    <div className="space-y-4">
                      {low_margin_recipes.map((recipe) => (
                        <div key={recipe.id} className="flex items-center justify-between p-4 border rounded-lg border-amber-200 dark:border-amber-900">
                          <div>
                            <h4 className="font-medium">{recipe.name}</h4>
                            <p className="text-sm text-muted-foreground mt-1">
                              Cost: {formatCurrency(recipe.total_cost)} • Item: {recipe.item_name}
                            </p>
                          </div>
                          <div className="text-right">
                            <Badge variant="warning" className="mb-1">
                              {recipe.profit_margin.toFixed(1)}% margin
                            </Badge>
                            <div className="flex gap-2 mt-2">
                              <Button
                                size="sm"
                                variant="outline"
                                onClick={() => handleEdit(recipe)}
                              >
                                Optimize
                              </Button>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8">
                      <TrendingUp className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                      <p className="text-muted-foreground">All recipes have healthy profit margins</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            </TabsContent>
          </Tabs>
        </PageLayout.Content>
      </PageLayout>

      {/* Create/Edit Dialog */}
      <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
        <DialogContent className="max-w-4xl max-h-[90vh] overflow-y-auto">
          <form onSubmit={handleSubmit}>
            <DialogHeader>
              <DialogTitle>
                {editingRecipe ? 'Edit Recipe' : 'Create Recipe'}
              </DialogTitle>
              <DialogDescription>
                Define ingredients and instructions for your recipe
              </DialogDescription>
            </DialogHeader>
            
            <div className="space-y-6 my-6">
              {/* Basic Info */}
              <div className="space-y-4">
                <h3 className="text-sm font-medium">Basic Information</h3>
                <div className="grid gap-4 md:grid-cols-2">
                  <div className="space-y-2">
                    <Label htmlFor="name">
                      Recipe Name <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="name"
                      value={data.name}
                      onChange={(e) => setData('name', e.target.value)}
                      placeholder="e.g., Classic Empanada Filling"
                      className={errors.name ? 'border-destructive' : ''}
                    />
                    {errors.name && (
                      <p className="text-sm text-destructive">{errors.name}</p>
                    )}
                  </div>
                  
                  <div className="space-y-2">
                    <Label>For Item <span className="text-destructive">*</span></Label>
                    <ItemSelector
                      value={data.item_id ? parseInt(data.item_id) : undefined}
                      onValueChange={(value) => setData('item_id', value?.toString() || '')}
                      items={items}
                      showPrice={false}
                      showSku={false}
                      placeholder="Select item"
                    />
                    {errors.item_id && (
                      <p className="text-sm text-destructive">{errors.item_id}</p>
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
                    rows={2}
                  />
                </div>
                
                <div className="grid gap-4 md:grid-cols-4">
                  <div className="space-y-2">
                    <Label htmlFor="yield_quantity">
                      Yield Quantity <span className="text-destructive">*</span>
                    </Label>
                    <Input
                      id="yield_quantity"
                      type="number"
                      value={data.yield_quantity}
                      onChange={(e) => setData('yield_quantity', e.target.value)}
                      className={errors.yield_quantity ? 'border-destructive' : ''}
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="yield_unit">
                      Unit <span className="text-destructive">*</span>
                    </Label>
                    <Select
                      value={data.yield_unit}
                      onValueChange={(value) => setData('yield_unit', value)}
                    >
                      <SelectTrigger>
                        <SelectValue placeholder="Select unit" />
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
                      value={data.prep_time_minutes}
                      onChange={(e) => setData('prep_time_minutes', e.target.value)}
                      placeholder="15"
                    />
                  </div>
                  
                  <div className="space-y-2">
                    <Label htmlFor="cook_time">Cook Time (min)</Label>
                    <Input
                      id="cook_time"
                      type="number"
                      value={data.cook_time_minutes}
                      onChange={(e) => setData('cook_time_minutes', e.target.value)}
                      placeholder="30"
                    />
                  </div>
                </div>
                
                <div className="space-y-2">
                  <Label htmlFor="difficulty">Difficulty</Label>
                  <Select
                    value={data.difficulty}
                    onValueChange={(value) => setData('difficulty', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select difficulty" />
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

              <Separator />

              {/* Ingredients */}
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-medium">Ingredients</h3>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addIngredient}
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Ingredient
                  </Button>
                </div>
                
                {ingredients.length === 0 ? (
                  <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                      Add ingredients to build your recipe. Each ingredient should be an item from your inventory.
                    </AlertDescription>
                  </Alert>
                ) : (
                  <div className="space-y-3">
                    {ingredients.map((ingredient, index) => (
                      <div key={index} className="flex gap-3 items-start p-3 border rounded-lg">
                        <div className="flex-1 grid gap-3 md:grid-cols-4">
                          <div className="md:col-span-2">
                            <ItemSelector
                              value={ingredient.item_id || undefined}
                              onValueChange={(value) => updateIngredient(index, 'item_id', value || 0)}
                              items={items}
                              showPrice={false}
                              showSku={false}
                              placeholder="Select ingredient"
                            />
                          </div>
                          <div>
                            <Input
                              type="number"
                              step="0.01"
                              value={ingredient.quantity}
                              onChange={(e) => updateIngredient(index, 'quantity', parseFloat(e.target.value) || 0)}
                              placeholder="Quantity"
                            />
                          </div>
                          <div>
                            <Select
                              value={ingredient.unit}
                              onValueChange={(value) => updateIngredient(index, 'unit', value)}
                            >
                              <SelectTrigger>
                                <SelectValue placeholder="Unit" />
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
                        </div>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => removeIngredient(index)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
              </div>

              <Separator />

              {/* Instructions */}
              <div className="space-y-4">
                <div className="flex items-center justify-between">
                  <h3 className="text-sm font-medium">Instructions</h3>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addStep}
                  >
                    <Plus className="mr-2 h-4 w-4" />
                    Add Step
                  </Button>
                </div>
                
                {steps.length === 0 ? (
                  <Alert>
                    <Info className="h-4 w-4" />
                    <AlertDescription>
                      Add step-by-step instructions for preparing this recipe.
                    </AlertDescription>
                  </Alert>
                ) : (
                  <div className="space-y-3">
                    {steps.map((step, index) => (
                      <div key={index} className="flex gap-3 items-start">
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-medium shrink-0">
                          {step.order}
                        </div>
                        <div className="flex-1 space-y-2">
                          <Textarea
                            value={step.instruction}
                            onChange={(e) => updateStep(index, 'instruction', e.target.value)}
                            placeholder="Describe this step..."
                            rows={2}
                          />
                          <div className="flex items-center gap-2">
                            <Clock className="h-3 w-3 text-muted-foreground" />
                            <Input
                              type="number"
                              value={step.duration_minutes || ''}
                              onChange={(e) => updateStep(index, 'duration_minutes', parseInt(e.target.value) || undefined)}
                              placeholder="Duration (min)"
                              className="w-32"
                            />
                          </div>
                        </div>
                        <Button
                          type="button"
                          variant="ghost"
                          size="sm"
                          onClick={() => removeStep(index)}
                        >
                          <X className="h-4 w-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
            
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCreateDialogOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={processing}>
                {editingRecipe ? 'Update Recipe' : 'Create Recipe'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* View Recipe Dialog */}
      <Dialog open={viewDialogOpen} onOpenChange={setViewDialogOpen}>
        <DialogContent className="max-w-3xl max-h-[90vh] overflow-y-auto">
          {viewingRecipe && (
            <>
              <DialogHeader>
                <DialogTitle>{viewingRecipe.name}</DialogTitle>
                <DialogDescription>
                  For: {viewingRecipe.item_name} • 
                  Yield: {viewingRecipe.yield_quantity} {viewingRecipe.yield_unit}
                </DialogDescription>
              </DialogHeader>
              
              <div className="space-y-6 my-6">
                {/* Recipe Info */}
                <div className="grid gap-4 md:grid-cols-3">
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm">Total Time</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="text-2xl font-bold">
                        {viewingRecipe.total_time_minutes} min
                      </div>
                      {viewingRecipe.prep_time_minutes && (
                        <p className="text-xs text-muted-foreground">
                          Prep: {viewingRecipe.prep_time_minutes}m
                        </p>
                      )}
                      {viewingRecipe.cook_time_minutes && (
                        <p className="text-xs text-muted-foreground">
                          Cook: {viewingRecipe.cook_time_minutes}m
                        </p>
                      )}
                    </CardContent>
                  </Card>
                  
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm">Cost per Portion</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="text-2xl font-bold">
                        {formatCurrency(viewingRecipe.cost_per_portion)}
                      </div>
                      <p className="text-xs text-muted-foreground">
                        Total: {formatCurrency(viewingRecipe.total_cost)}
                      </p>
                    </CardContent>
                  </Card>
                  
                  <Card>
                    <CardHeader className="pb-3">
                      <CardTitle className="text-sm">Profit Margin</CardTitle>
                    </CardHeader>
                    <CardContent>
                      <div className="text-2xl font-bold">
                        {viewingRecipe.profit_margin.toFixed(1)}%
                      </div>
                      <Badge variant={viewingRecipe.difficulty === 'easy' ? 'success' : viewingRecipe.difficulty === 'medium' ? 'warning' : 'destructive'}>
                        {viewingRecipe.difficulty} difficulty
                      </Badge>
                    </CardContent>
                  </Card>
                </div>
                
                {/* Ingredients */}
                <div>
                  <h3 className="font-medium mb-3">Ingredients</h3>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead>Item</TableHead>
                        <TableHead>Quantity</TableHead>
                        <TableHead className="text-right">Cost</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {viewingRecipe.ingredients.map((ingredient, index) => (
                        <TableRow key={index}>
                          <TableCell>{ingredient.item_name}</TableCell>
                          <TableCell>
                            {ingredient.quantity} {ingredient.unit}
                          </TableCell>
                          <TableCell className="text-right">
                            {ingredient.total_cost ? formatCurrency(ingredient.total_cost) : '—'}
                          </TableCell>
                        </TableRow>
                      ))}
                      <TableRow>
                        <TableCell colSpan={2} className="font-medium">
                          Total Cost
                        </TableCell>
                        <TableCell className="text-right font-medium">
                          {formatCurrency(viewingRecipe.total_cost)}
                        </TableCell>
                      </TableRow>
                    </TableBody>
                  </Table>
                </div>
                
                {/* Instructions */}
                <div>
                  <h3 className="font-medium mb-3">Instructions</h3>
                  <div className="space-y-3">
                    {viewingRecipe.instructions.map((step, index) => (
                      <div key={index} className="flex gap-3">
                        <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary text-sm font-medium shrink-0">
                          {step.order}
                        </div>
                        <div className="flex-1">
                          <p className="text-sm">{step.instruction}</p>
                          {step.duration_minutes && (
                            <p className="text-xs text-muted-foreground mt-1">
                              Duration: {step.duration_minutes} minutes
                            </p>
                          )}
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              </div>
              
              <DialogFooter>
                <Button
                  variant="outline"
                  onClick={() => handlePrint(viewingRecipe)}
                >
                  <Printer className="mr-2 h-4 w-4" />
                  Print
                </Button>
                <Button
                  variant="outline"
                  onClick={() => handleExport(viewingRecipe)}
                >
                  <Download className="mr-2 h-4 w-4" />
                  Export PDF
                </Button>
                <Button onClick={() => setViewDialogOpen(false)}>
                  Close
                </Button>
              </DialogFooter>
            </>
          )}
        </DialogContent>
      </Dialog>
    </>
  );
}