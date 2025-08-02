# Frontend Development Guide

## Component Patterns

### UI Components (shadcn/ui pattern)
```tsx
import { forwardRef } from 'react'
import { cn } from '@/lib/utils'

interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: 'default' | 'destructive' | 'outline'
}

const Button = forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant = 'default', ...props }, ref) => {
    return (
      <button
        ref={ref}
        className={cn(
          'inline-flex items-center justify-center',
          variants[variant],
          className
        )}
        {...props}
      />
    )
  }
)
```

### Inertia Page Components
```tsx
import { Head, usePage } from '@inertiajs/react'
import { PageLayout } from '@/layouts/page-layout'
import { OrderData } from '@/types/order'

interface Props {
  orders: OrderData[]
  pagination: PaginationData
  metadata: ResourceMetadata
}

export default function OrderIndex({ orders, pagination, metadata }: Props) {
  const { auth } = usePage().props
  
  return (
    <>
      <Head title="Orders" />
      <PageLayout>
        {/* Component content */}
      </PageLayout>
    </>
  )
}
```

## Empty State Implementation

### EmptyState Component Usage
All list views MUST implement empty states:

```tsx
import { EmptyState } from '@/components/empty-state'
import { Package } from 'lucide-react'

export default function InventoryIndex({ inventory }: Props) {
  const isEmpty = inventory.length === 0
  
  return (
    <PageLayout>
      <PageLayout.Header
        title="Inventory"
        subtitle="Track stock levels"
        actions={
          !isEmpty && (
            <PageLayout.Actions>
              <Button>Stock Take</Button>
            </PageLayout.Actions>
          )
        }
      />
      
      <PageLayout.Content>
        {isEmpty ? (
          <EmptyState
            icon={Package}
            title="No inventory tracked yet"
            description="Start tracking inventory for your items"
            actions={
              <Button onClick={() => router.visit('/items')}>
                Go to Items
              </Button>
            }
            helpText={
              <>
                Learn about <a href="#" className="text-primary hover:underline">
                  inventory management
                </a>
              </>
            }
          />
        ) : (
          <>
            {/* Stats cards and data table */}
          </>
        )}
      </PageLayout.Content>
    </PageLayout>
  )
}
```

### Required Empty States
- **List Views**: Orders, Items, Inventory, Pricing, Modifiers, Recipes
- **Dashboard Views**: When no data for charts/metrics
- **Search Results**: When no matches found
- **Filtered Views**: When filters yield no results

## Form Handling with Inertia

### useForm Hook
```tsx
import { useForm } from '@inertiajs/react'

export default function CreateOrder() {
  const { data, setData, post, processing, errors } = useForm({
    customer_name: '',
    items: [],
    location_id: null,
  })
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post('/orders')
  }
  
  return (
    <form onSubmit={handleSubmit}>
      <Input
        value={data.customer_name}
        onChange={e => setData('customer_name', e.target.value)}
        error={errors.customer_name}
      />
      {/* More fields */}
      <Button type="submit" disabled={processing}>
        Create Order
      </Button>
    </form>
  )
}
```

## TypeScript Best Practices

### Type Imports
```tsx
// Import types from generated TypeScript files
import type { User } from '@/types/models'
import type { PageProps } from '@/types'

// Define component-specific types
interface OrderListProps {
  orders: Order[]
  filters: FilterOptions
}
```

### Inertia Shared Data
```tsx
// In app.tsx
declare module '@inertiajs/core' {
  interface PageProps {
    auth: {
      user: User | null
    }
    flash: {
      success?: string
      error?: string
    }
  }
}
```

## Styling Guidelines

### Tailwind CSS Usage
```tsx
// Use cn() utility for conditional classes
import { cn } from '@/lib/utils'

<div className={cn(
  'rounded-lg border p-4',
  isActive && 'border-primary',
  isDisabled && 'opacity-50 cursor-not-allowed'
)} />
```

### CSS Variables
```css
/* In app.css */
:root {
  --background: 0 0% 100%;
  --foreground: 240 10% 3.9%;
  --primary: 240 5.9% 10%;
  /* ... */
}

.dark {
  --background: 240 10% 3.9%;
  --foreground: 0 0% 98%;
  /* ... */
}
```

## Navigation Patterns

### Inertia Navigation
```tsx
import { router } from '@inertiajs/react'
import { Link } from '@inertiajs/react'

// Programmatic navigation
router.visit('/orders', {
  method: 'get',
  data: { filter: 'active' },
})

// Link component
<Link href="/orders" className="text-primary hover:underline">
  View Orders
</Link>

// Form navigation
router.post('/orders', data)
```

## Data Table Patterns

### Using InertiaDataTable
```tsx
import { InertiaDataTable } from '@/components/data-table/inertia-data-table'
import { useResourceMetadata } from '@/hooks/use-resource-metadata'

export default function OrderList({ orders, pagination, metadata }: Props) {
  const { filters } = useResourceMetadata(metadata)
  
  return (
    <InertiaDataTable
      columns={columns}
      data={orders}
      pagination={pagination}
      filters={filters}
      enableRowSelection
      onRowClick={(order) => router.visit(`/orders/${order.id}`)}
    />
  )
}
```

## Component Organization

### File Structure
```
components/
├── ui/                    # Base UI components
│   ├── button.tsx
│   ├── input.tsx
│   └── card.tsx
├── modules/              # Module-specific
│   ├── order/
│   │   ├── order-card.tsx
│   │   └── order-form.tsx
│   └── inventory/
│       └── stock-badge.tsx
├── data-table/          # Data table system
└── empty-state.tsx      # Shared components
```

### Import Conventions
```tsx
// UI components
import { Button } from '@/components/ui/button'

// Module components
import { OrderCard } from '@/components/modules/order/order-card'

// Layouts
import { AppLayout } from '@/layouts/app-layout'

// Hooks
import { useFilter } from '@/hooks/use-filter'
```