import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
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
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { 
  Package, 
  ArrowRight,
  Clock,
  CheckCircle,
  XCircle,
  Truck,
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface Location {
  id: number;
  name: string;
}

interface Transfer {
  id: number;
  item_name: string;
  quantity: number;
  from_location: string;
  to_location: string;
  status: string;
  initiated_by: string;
  initiated_at: string;
  completed_at?: string;
}

interface PageProps {
  locations: Location[];
  pending_transfers: Transfer[];
  recent_transfers: Transfer[];
}

export default function StockTransfers({ locations, pending_transfers, recent_transfers }: PageProps) {
  const { data, setData, post, processing, errors, reset } = useForm({
    item_id: '',
    variant_id: '',
    from_location_id: '',
    to_location_id: '',
    quantity: '',
    notes: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('inventory.transfer'), {
      onSuccess: () => reset(),
    });
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'completed':
        return <CheckCircle className="h-4 w-4 text-green-600" />;
      case 'cancelled':
        return <XCircle className="h-4 w-4 text-red-600" />;
      case 'in_transit':
        return <Truck className="h-4 w-4 text-blue-600" />;
      default:
        return <Clock className="h-4 w-4 text-yellow-600" />;
    }
  };

  const getStatusBadge = (status: string) => {
    const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
      completed: 'secondary',
      cancelled: 'destructive',
      in_transit: 'default',
      pending: 'outline',
    };
    
    return (
      <Badge variant={variants[status] || 'outline'}>
        {status.replace('_', ' ')}
      </Badge>
    );
  };

  return (
    <AppLayout>
      <Head title="Stock Transfers" />

      <Page
        title="Stock Transfers"
        description="Transfer inventory between locations"
      >
        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <Card className="lg:col-span-1">
            <CardHeader>
              <CardTitle>New Transfer</CardTitle>
              <CardDescription>
                Move stock between locations
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={handleSubmit} className="space-y-4">
                <div className="space-y-2">
                  <Label htmlFor="item_id">Item *</Label>
                  <Input
                    id="item_id"
                    type="number"
                    value={data.item_id}
                    onChange={(e) => setData('item_id', e.target.value)}
                    placeholder="Enter item ID"
                    required
                  />
                  {errors.item_id && (
                    <p className="text-sm text-destructive">{errors.item_id}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="variant_id">Variant ID (Optional)</Label>
                  <Input
                    id="variant_id"
                    type="number"
                    value={data.variant_id}
                    onChange={(e) => setData('variant_id', e.target.value)}
                    placeholder="Enter variant ID"
                  />
                </div>

                <div className="space-y-2">
                  <Label htmlFor="from_location_id">From Location *</Label>
                  <Select
                    value={data.from_location_id}
                    onValueChange={(value) => setData('from_location_id', value)}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select source location" />
                    </SelectTrigger>
                    <SelectContent>
                      {locations.map((location) => (
                        <SelectItem key={location.id} value={location.id.toString()}>
                          {location.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.from_location_id && (
                    <p className="text-sm text-destructive">{errors.from_location_id}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="to_location_id">To Location *</Label>
                  <Select
                    value={data.to_location_id}
                    onValueChange={(value) => setData('to_location_id', value)}
                    disabled={!data.from_location_id}
                  >
                    <SelectTrigger>
                      <SelectValue placeholder="Select destination location" />
                    </SelectTrigger>
                    <SelectContent>
                      {locations
                        .filter((loc) => loc.id.toString() !== data.from_location_id)
                        .map((location) => (
                          <SelectItem key={location.id} value={location.id.toString()}>
                            {location.name}
                          </SelectItem>
                        ))}
                    </SelectContent>
                  </Select>
                  {errors.to_location_id && (
                    <p className="text-sm text-destructive">{errors.to_location_id}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="quantity">Quantity *</Label>
                  <Input
                    id="quantity"
                    type="number"
                    step="0.01"
                    min="0.01"
                    value={data.quantity}
                    onChange={(e) => setData('quantity', e.target.value)}
                    placeholder="Enter quantity to transfer"
                    required
                  />
                  {errors.quantity && (
                    <p className="text-sm text-destructive">{errors.quantity}</p>
                  )}
                </div>

                <div className="space-y-2">
                  <Label htmlFor="notes">Notes</Label>
                  <Textarea
                    id="notes"
                    value={data.notes}
                    onChange={(e) => setData('notes', e.target.value)}
                    placeholder="Optional transfer notes..."
                    rows={3}
                  />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                  <ArrowRight className="mr-2 h-4 w-4" />
                  {processing ? 'Processing...' : 'Initiate Transfer'}
                </Button>
              </form>
            </CardContent>
          </Card>

          <Card className="lg:col-span-2">
            <CardHeader>
              <CardTitle>Transfer History</CardTitle>
              <CardDescription>
                Track pending and completed transfers
              </CardDescription>
            </CardHeader>
            <CardContent>
              <Tabs defaultValue="pending">
                <TabsList className="grid w-full grid-cols-2">
                  <TabsTrigger value="pending">
                    Pending ({pending_transfers.length})
                  </TabsTrigger>
                  <TabsTrigger value="recent">
                    Recent ({recent_transfers.length})
                  </TabsTrigger>
                </TabsList>

                <TabsContent value="pending" className="mt-4">
                  {pending_transfers.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Item</TableHead>
                          <TableHead>Route</TableHead>
                          <TableHead className="text-right">Quantity</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Initiated</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {pending_transfers.map((transfer) => (
                          <TableRow key={transfer.id}>
                            <TableCell className="font-medium">
                              {transfer.item_name}
                            </TableCell>
                            <TableCell>
                              <div className="flex items-center gap-2 text-sm">
                                <span>{transfer.from_location}</span>
                                <ArrowRight className="h-3 w-3" />
                                <span>{transfer.to_location}</span>
                              </div>
                            </TableCell>
                            <TableCell className="text-right">
                              {transfer.quantity}
                            </TableCell>
                            <TableCell>
                              <div className="flex items-center gap-2">
                                {getStatusIcon(transfer.status)}
                                {getStatusBadge(transfer.status)}
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className="text-sm">
                                <div>{transfer.initiated_by}</div>
                                <div className="text-muted-foreground">
                                  {new Date(transfer.initiated_at).toLocaleDateString()}
                                </div>
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-8 text-muted-foreground">
                      <Package className="mx-auto h-12 w-12 mb-4 opacity-30" />
                      <p>No pending transfers</p>
                    </div>
                  )}
                </TabsContent>

                <TabsContent value="recent" className="mt-4">
                  {recent_transfers.length > 0 ? (
                    <Table>
                      <TableHeader>
                        <TableRow>
                          <TableHead>Item</TableHead>
                          <TableHead>Route</TableHead>
                          <TableHead className="text-right">Quantity</TableHead>
                          <TableHead>Status</TableHead>
                          <TableHead>Completed</TableHead>
                        </TableRow>
                      </TableHeader>
                      <TableBody>
                        {recent_transfers.map((transfer) => (
                          <TableRow key={transfer.id}>
                            <TableCell className="font-medium">
                              {transfer.item_name}
                            </TableCell>
                            <TableCell>
                              <div className="flex items-center gap-2 text-sm">
                                <span>{transfer.from_location}</span>
                                <ArrowRight className="h-3 w-3" />
                                <span>{transfer.to_location}</span>
                              </div>
                            </TableCell>
                            <TableCell className="text-right">
                              {transfer.quantity}
                            </TableCell>
                            <TableCell>
                              <div className="flex items-center gap-2">
                                {getStatusIcon(transfer.status)}
                                {getStatusBadge(transfer.status)}
                              </div>
                            </TableCell>
                            <TableCell>
                              <div className="text-sm text-muted-foreground">
                                {transfer.completed_at 
                                  ? new Date(transfer.completed_at).toLocaleDateString()
                                  : '-'
                                }
                              </div>
                            </TableCell>
                          </TableRow>
                        ))}
                      </TableBody>
                    </Table>
                  ) : (
                    <div className="text-center py-8 text-muted-foreground">
                      <Package className="mx-auto h-12 w-12 mb-4 opacity-30" />
                      <p>No recent transfers</p>
                    </div>
                  )}
                </TabsContent>
              </Tabs>
            </CardContent>
          </Card>
        </div>
      </Page>
    </AppLayout>
  );
}