import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { formatCurrency } from '@/lib/utils';
import { Link } from '@inertiajs/react';
import { AlertTriangle, Package } from 'lucide-react';

interface ItemCardProps {
  item: {
    id: number;
    name: string;
    description: string | null;
    base_price: number;
    is_active: boolean;
    stock_quantity: number | null;
    low_stock_threshold: number | null;
    category?: {
      id: number;
      name: string;
    };
  };
  showStock?: boolean;
}

export function ItemCard({ item, showStock = false }: ItemCardProps) {
  const isLowStock =
    showStock && item.stock_quantity !== null && item.low_stock_threshold !== null && item.stock_quantity <= item.low_stock_threshold;

  return (
    <Link href={`/items/${item.id}`}>
      <Card className="h-full cursor-pointer transition-shadow hover:shadow-lg">
        <CardHeader className="pb-3">
          <div className="flex items-start justify-between">
            <div className="space-y-1">
              <h3 className="leading-none font-semibold tracking-tight">{item.name}</h3>
              {item.category && <p className="text-sm text-muted-foreground">{item.category.name}</p>}
            </div>
            <Badge variant={item.is_active ? 'default' : 'secondary'}>{item.is_active ? 'Active' : 'Inactive'}</Badge>
          </div>
        </CardHeader>
        <CardContent>
          {item.description && <p className="mb-3 line-clamp-2 text-sm text-muted-foreground">{item.description}</p>}

          <div className="flex items-center justify-between">
            <p className="text-lg font-semibold">{formatCurrency(item.base_price)}</p>

            {showStock && item.stock_quantity !== null && (
              <div className="flex items-center gap-1">
                {isLowStock ? (
                  <>
                    <AlertTriangle className="h-4 w-4 text-destructive" />
                    <span className="text-sm font-medium text-destructive">{item.stock_quantity} left</span>
                  </>
                ) : (
                  <>
                    <Package className="h-4 w-4 text-muted-foreground" />
                    <span className="text-sm text-muted-foreground">{item.stock_quantity} in stock</span>
                  </>
                )}
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </Link>
  );
}
