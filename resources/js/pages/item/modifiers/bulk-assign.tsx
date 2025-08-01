import { useState } from 'react';
import { Head, router, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Package, AlertCircle, CheckCircle } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ModifierGroup {
  id: number;
  name: string;
  description: string | null;
  modifiers_count: number;
  is_active: boolean;
}

interface Item {
  id: number;
  name: string;
  sku: string | null;
  category: string;
  has_modifiers: boolean;
}

interface PageProps {
  modifier_groups: ModifierGroup[];
  items: Item[];
}

export default function BulkAssignModifiers({ modifier_groups, items }: PageProps) {
  const [selectedGroups, setSelectedGroups] = useState<number[]>([]);
  const [selectedItems, setSelectedItems] = useState<number[]>([]);
  
  const { data, setData, post, processing } = useForm({
    modifier_group_ids: [] as number[],
    item_ids: [] as number[],
    action: 'add' as 'add' | 'replace',
  });

  const handleGroupToggle = (groupId: number) => {
    setSelectedGroups(prev => {
      const newSelection = prev.includes(groupId)
        ? prev.filter(id => id !== groupId)
        : [...prev, groupId];
      
      setData('modifier_group_ids', newSelection);
      return newSelection;
    });
  };

  const handleItemToggle = (itemId: number) => {
    setSelectedItems(prev => {
      const newSelection = prev.includes(itemId)
        ? prev.filter(id => id !== itemId)
        : [...prev, itemId];
      
      setData('item_ids', newSelection);
      return newSelection;
    });
  };

  const toggleAllGroups = () => {
    const activeGroups = modifier_groups.filter(g => g.is_active).map(g => g.id);
    if (selectedGroups.length === activeGroups.length) {
      setSelectedGroups([]);
      setData('modifier_group_ids', []);
    } else {
      setSelectedGroups(activeGroups);
      setData('modifier_group_ids', activeGroups);
    }
  };

  const toggleAllItems = () => {
    const allItemIds = items.map(item => item.id);
    if (selectedItems.length === items.length) {
      setSelectedItems([]);
      setData('item_ids', []);
    } else {
      setSelectedItems(allItemIds);
      setData('item_ids', allItemIds);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post('/modifiers/bulk-assign');
  };

  const activeGroups = modifier_groups.filter(g => g.is_active);

  return (
    <AppLayout>
      <Head title="Bulk Assign Modifiers" />
      <Page>
        <Page.Header
          title="Bulk Assign Modifiers"
          subtitle="Assign modifier groups to multiple items at once"
          actions={
            <Button variant="ghost" size="sm" onClick={() => router.visit('/modifiers')}>
              <ArrowLeft className="h-4 w-4 mr-2" />
              Back to Modifiers
            </Button>
          }
        />
        
        <Page.Content>
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Action Selection */}
            <Card>
              <CardHeader>
                <CardTitle>Assignment Action</CardTitle>
                <CardDescription>
                  Choose how to apply the selected modifier groups to items
                </CardDescription>
              </CardHeader>
              <CardContent>
                <RadioGroup
                  value={data.action}
                  onValueChange={(value) => setData('action', value as 'add' | 'replace')}
                >
                  <div className="flex items-center space-x-2">
                    <RadioGroupItem value="add" id="add" />
                    <Label htmlFor="add" className="cursor-pointer">
                      <span className="font-medium">Add to existing</span>
                      <span className="block text-sm text-muted-foreground">
                        Keep current modifier groups and add the selected ones
                      </span>
                    </Label>
                  </div>
                  <div className="flex items-center space-x-2 mt-3">
                    <RadioGroupItem value="replace" id="replace" />
                    <Label htmlFor="replace" className="cursor-pointer">
                      <span className="font-medium">Replace all</span>
                      <span className="block text-sm text-muted-foreground">
                        Remove all current modifier groups and add only the selected ones
                      </span>
                    </Label>
                  </div>
                </RadioGroup>
              </CardContent>
            </Card>

            <div className="grid gap-6 lg:grid-cols-2">
              {/* Modifier Groups Selection */}
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <div>
                      <CardTitle>Select Modifier Groups</CardTitle>
                      <CardDescription>
                        {selectedGroups.length} of {activeGroups.length} active groups selected
                      </CardDescription>
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={toggleAllGroups}
                    >
                      {selectedGroups.length === activeGroups.length ? 'Deselect All' : 'Select All'}
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="max-h-96 overflow-y-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="w-12"></TableHead>
                          <TableHead>Group Name</TableHead>
                          <TableHead className="text-right">Modifiers</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {modifier_groups.map((group) => (
                          <TableRow
                            key={group.id}
                            className={cn(
                              "cursor-pointer",
                              !group.is_active && "opacity-50"
                            )}
                            onClick={() => group.is_active && handleGroupToggle(group.id)}
                          >
                            <TableCell>
                              <Checkbox
                                checked={selectedGroups.includes(group.id)}
                                disabled={!group.is_active}
                                onCheckedChange={() => handleGroupToggle(group.id)}
                                onClick={(e) => e.stopPropagation()}
                              />
                            </TableCell>
                            <TableCell>
                              <div>
                                <div className="font-medium">{group.name}</div>
                                {group.description && (
                                  <div className="text-xs text-muted-foreground">
                                    {group.description}
                                  </div>
                                )}
                              </div>
                            </TableCell>
                            <TableCell className="text-right">
                              <Badge variant="secondary">
                                {group.modifiers_count} options
                              </Badge>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                </CardContent>
              </Card>

              {/* Items Selection */}
              <Card>
                <CardHeader>
                  <div className="flex items-center justify-between">
                    <div>
                      <CardTitle>Select Items</CardTitle>
                      <CardDescription>
                        {selectedItems.length} of {items.length} items selected
                      </CardDescription>
                    </div>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={toggleAllItems}
                    >
                      {selectedItems.length === items.length ? 'Deselect All' : 'Select All'}
                    </Button>
                  </div>
                </CardHeader>
                <CardContent className="p-0">
                  <div className="max-h-96 overflow-y-auto">
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead className="w-12"></TableHead>
                          <TableHead>Item Name</TableHead>
                          <TableHead>Category</TableHead>
                          <TableHead className="text-right">Status</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {items.map((item) => (
                          <TableRow
                            key={item.id}
                            className="cursor-pointer"
                            onClick={() => handleItemToggle(item.id)}
                          >
                            <TableCell>
                              <Checkbox
                                checked={selectedItems.includes(item.id)}
                                onCheckedChange={() => handleItemToggle(item.id)}
                                onClick={(e) => e.stopPropagation()}
                              />
                            </TableCell>
                            <TableCell>
                              <div>
                                <div className="font-medium">{item.name}</div>
                                {item.sku && (
                                  <div className="text-xs text-muted-foreground">
                                    SKU: {item.sku}
                                  </div>
                                )}
                              </div>
                            </TableCell>
                            <TableCell>
                              <Badge variant="outline">{item.category}</Badge>
                            </TableCell>
                            <TableCell className="text-right">
                              {item.has_modifiers ? (
                                <CheckCircle className="h-4 w-4 text-green-600 ml-auto" />
                              ) : (
                                <span className="text-xs text-muted-foreground">No modifiers</span>
                              )}
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  </div>
                </CardContent>
              </Card>
            </div>

            {/* Summary and Actions */}
            <Card>
              <CardContent className="pt-6">
                <div className="space-y-4">
                  {selectedGroups.length === 0 || selectedItems.length === 0 ? (
                    <Alert>
                      <AlertCircle className="h-4 w-4" />
                      <AlertDescription>
                        Please select at least one modifier group and one item to proceed.
                      </AlertDescription>
                    </Alert>
                  ) : (
                    <Alert>
                      <Package className="h-4 w-4" />
                      <AlertDescription>
                        You are about to {data.action === 'replace' ? 'replace all modifier groups for' : 'add modifier groups to'}{' '}
                        <strong>{selectedItems.length} item{selectedItems.length !== 1 ? 's' : ''}</strong> with{' '}
                        <strong>{selectedGroups.length} modifier group{selectedGroups.length !== 1 ? 's' : ''}</strong>.
                      </AlertDescription>
                    </Alert>
                  )}

                  <div className="flex justify-end gap-3">
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => router.visit('/modifiers')}
                      disabled={processing}
                    >
                      Cancel
                    </Button>
                    <Button
                      type="submit"
                      disabled={processing || selectedGroups.length === 0 || selectedItems.length === 0}
                    >
                      {processing ? 'Processing...' : 'Apply Changes'}
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