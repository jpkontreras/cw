import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { PageHeader } from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Plus, Search, Filter, MoreVertical, Edit, Trash2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
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
import { formatCurrency } from '@/lib/utils';

interface ItemData {
  id: number;
  name: string;
  description: string | null;
  category_id: number | null;
  sku: string | null;
  barcode: string | null;
  base_price: number;
  cost: number;
  is_active: boolean;
  is_compound: boolean;
  stock_quantity: number | null;
  low_stock_threshold: number | null;
  created_at: string;
  updated_at: string;
}

interface PageProps {
  items: {
    data: ItemData[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  categories: Array<{ id: number; name: string }>;
  features: {
    variants: boolean;
    modifiers: boolean;
    recipes: boolean;
    location_pricing: boolean;
    inventory_tracking: boolean;
  };
}

export default function ItemIndex({ items, categories, features }: PageProps) {
  const [search, setSearch] = useState('');

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    router.get('/items', { search }, { preserveState: true });
  };

  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this item?')) {
      router.delete(`/items/${id}`);
    }
  };

  return (
    <AppLayout>
      <Head title="Items" />

      <div className="space-y-6">
        <PageHeader
          title="Items"
          description="Manage your product catalog"
          action={
            <Button asChild>
              <Link href="/items/create">
                <Plus className="mr-2 h-4 w-4" />
                Add Item
              </Link>
            </Button>
          }
        />

        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>All Items</CardTitle>
              <div className="flex items-center gap-2">
                <form onSubmit={handleSearch} className="flex items-center gap-2">
                  <div className="relative">
                    <Search className="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                    <Input
                      placeholder="Search items..."
                      value={search}
                      onChange={(e) => setSearch(e.target.value)}
                      className="pl-8 w-[250px]"
                    />
                  </div>
                </form>
                <Button variant="outline" size="icon">
                  <Filter className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Name</TableHead>
                  <TableHead>SKU</TableHead>
                  <TableHead>Category</TableHead>
                  <TableHead>Price</TableHead>
                  <TableHead>Cost</TableHead>
                  {features.inventory_tracking && <TableHead>Stock</TableHead>}
                  <TableHead>Status</TableHead>
                  <TableHead className="w-[50px]"></TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {items.data.map((item) => {
                  const category = categories.find(c => c.id === item.category_id);
                  return (
                    <TableRow key={item.id}>
                      <TableCell className="font-medium">
                        <div>
                          <div>{item.name}</div>
                          {item.description && (
                            <div className="text-sm text-muted-foreground">{item.description}</div>
                          )}
                        </div>
                      </TableCell>
                      <TableCell>{item.sku || '-'}</TableCell>
                      <TableCell>{category?.name || '-'}</TableCell>
                      <TableCell>{formatCurrency(item.base_price)}</TableCell>
                      <TableCell>{formatCurrency(item.cost)}</TableCell>
                      {features.inventory_tracking && (
                        <TableCell>
                          {item.stock_quantity !== null ? (
                            <div className="flex items-center gap-2">
                              <span>{item.stock_quantity}</span>
                              {item.low_stock_threshold && item.stock_quantity <= item.low_stock_threshold && (
                                <Badge variant="destructive" className="text-xs">Low Stock</Badge>
                              )}
                            </div>
                          ) : (
                            '-'
                          )}
                        </TableCell>
                      )}
                      <TableCell>
                        <Badge variant={item.is_active ? 'default' : 'secondary'}>
                          {item.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon">
                              <MoreVertical className="h-4 w-4" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                              <Link href={`/items/${item.id}`}>
                                View Details
                              </Link>
                            </DropdownMenuItem>
                            <DropdownMenuItem asChild>
                              <Link href={`/items/${item.id}/edit`}>
                                <Edit className="mr-2 h-4 w-4" />
                                Edit
                              </Link>
                            </DropdownMenuItem>
                            {features.variants && (
                              <DropdownMenuItem asChild>
                                <Link href={`/items/${item.id}/variants`}>
                                  Manage Variants
                                </Link>
                              </DropdownMenuItem>
                            )}
                            {features.modifiers && (
                              <DropdownMenuItem asChild>
                                <Link href={`/items/${item.id}/modifiers`}>
                                  Manage Modifiers
                                </Link>
                              </DropdownMenuItem>
                            )}
                            <DropdownMenuSeparator />
                            <DropdownMenuItem
                              className="text-destructive"
                              onSelect={() => handleDelete(item.id)}
                            >
                              <Trash2 className="mr-2 h-4 w-4" />
                              Delete
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}