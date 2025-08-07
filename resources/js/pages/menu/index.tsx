import { useState } from 'react';
import { Head, router, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { EmptyState } from '@/components/empty-state';
import {
  Plus,
  MoreHorizontal,
  Eye,
  Edit,
  Copy,
  Trash2,
  CheckCircle,
  XCircle,
  Clock,
  MapPin,
  ChefHat,
  Coffee,
  Sun,
  Moon,
  Calendar,
  Sparkles,
  Search,
  FileText,
  FileUp,
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem } from '@/types';

interface Menu {
  id: number;
  name: string;
  slug: string;
  description?: string;
  type: 'regular' | 'breakfast' | 'lunch' | 'dinner' | 'event' | 'seasonal';
  isActive: boolean;
  isDefault: boolean;
  isCurrentlyAvailable?: boolean;
  sections?: Array<{ id: number; name: string }>;
  items?: Array<{ id: number }>;
  createdAt: string;
  updatedAt: string;
}

interface PageProps {
  menus: Menu[];
  canCreate: boolean;
}

const menuTypeIcons = {
  regular: ChefHat,
  breakfast: Coffee,
  lunch: Sun,
  dinner: Moon,
  event: Calendar,
  seasonal: Sparkles,
};

const menuTypeLabels = {
  regular: 'Regular',
  breakfast: 'Breakfast',
  lunch: 'Lunch',
  dinner: 'Dinner',
  event: 'Event',
  seasonal: 'Seasonal',
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Menus',
    href: '/menus',
  },
];


function MenuCard({ menu }: { menu: Menu }) {
  const Icon = menuTypeIcons[menu.type] || ChefHat;
  
  const handleAction = (action: string) => {
    switch (action) {
      case 'view':
        router.visit(`/menus/${menu.id}`);
        break;
      case 'edit':
        router.visit(`/menus/${menu.id}/edit`);
        break;
      case 'builder':
        router.visit(`/menus/${menu.id}/builder`);
        break;
      case 'duplicate':
        router.post(`/menus/${menu.id}/duplicate`);
        break;
      case 'delete':
        if (confirm('Are you sure you want to delete this menu?')) {
          router.delete(`/menus/${menu.id}`);
        }
        break;
    }
  };

  return (
    <Card className="group hover:shadow-md transition-shadow">
      <CardHeader>
        <div className="flex items-start justify-between">
          <div className="flex items-start gap-3">
            <div className={cn(
              "p-2 rounded-lg",
              menu.isActive ? "bg-primary/10" : "bg-muted"
            )}>
              <Icon className={cn(
                "h-5 w-5",
                menu.isActive ? "text-primary" : "text-muted-foreground"
              )} />
            </div>
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <CardTitle className="text-base">{menu.name}</CardTitle>
                {menu.isDefault && (
                  <Badge variant="secondary" className="text-xs">
                    Default
                  </Badge>
                )}
              </div>
              {menu.description && (
                <CardDescription className="text-sm">
                  {menu.description}
                </CardDescription>
              )}
            </div>
          </div>
          
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="h-8 w-8">
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => handleAction('view')}>
                <Eye className="mr-2 h-4 w-4" />
                View Menu
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleAction('builder')}>
                <Edit className="mr-2 h-4 w-4" />
                Menu Builder
              </DropdownMenuItem>
              <DropdownMenuItem onClick={() => handleAction('edit')}>
                <Edit className="mr-2 h-4 w-4" />
                Edit Settings
              </DropdownMenuItem>
              <DropdownMenuSeparator />
              <DropdownMenuItem onClick={() => handleAction('duplicate')}>
                <Copy className="mr-2 h-4 w-4" />
                Duplicate
              </DropdownMenuItem>
              <DropdownMenuItem 
                onClick={() => handleAction('delete')}
                className="text-destructive"
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </CardHeader>
      <CardContent>
        <div className="space-y-3">
          <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">Type</span>
            <Badge variant="outline">{menuTypeLabels[menu.type]}</Badge>
          </div>
          
          <div className="flex items-center justify-between text-sm">
            <span className="text-muted-foreground">Status</span>
            <div className="flex items-center gap-1">
              {menu.isActive ? (
                <>
                  <CheckCircle className="h-3 w-3 text-green-600" />
                  <span className="text-green-600">Active</span>
                </>
              ) : (
                <>
                  <XCircle className="h-3 w-3 text-destructive" />
                  <span className="text-destructive">Inactive</span>
                </>
              )}
            </div>
          </div>

          {menu.sections && (
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Sections</span>
              <span>{menu.sections.length}</span>
            </div>
          )}

          {menu.items && (
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Items</span>
              <span>{menu.items.length}</span>
            </div>
          )}

          {menu.isCurrentlyAvailable !== undefined && (
            <div className="flex items-center justify-between text-sm">
              <span className="text-muted-foreground">Available Now</span>
              <div className="flex items-center gap-1">
                {menu.isCurrentlyAvailable ? (
                  <>
                    <Clock className="h-3 w-3 text-green-600" />
                    <span className="text-green-600">Yes</span>
                  </>
                ) : (
                  <>
                    <Clock className="h-3 w-3 text-muted-foreground" />
                    <span className="text-muted-foreground">No</span>
                  </>
                )}
              </div>
            </div>
          )}
        </div>
      </CardContent>
    </Card>
  );
}

function MenuIndexContent({ menus = [], canCreate = true }: PageProps) {
  const [searchQuery, setSearchQuery] = useState('');
  const [typeFilter, setTypeFilter] = useState<string>('all');
  const [statusFilter, setStatusFilter] = useState<string>('all');

  const filteredMenus = menus.filter(menu => {
    const matchesSearch = searchQuery === '' || 
      menu.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      menu.description?.toLowerCase().includes(searchQuery.toLowerCase());
    
    const matchesType = typeFilter === 'all' || menu.type === typeFilter;
    const matchesStatus = statusFilter === 'all' || 
      (statusFilter === 'active' && menu.isActive) ||
      (statusFilter === 'inactive' && !menu.isActive);

    return matchesSearch && matchesType && matchesStatus;
  });

  return (
    <>
      <Page.Header 
        title="Menu Management" 
        breadcrumbs={breadcrumbs}
        actions={
          <div className="flex gap-2">
            <Button variant="outline" asChild>
              <Link href="/menus/import">
                <FileUp className="mr-2 h-4 w-4" />
                Import
              </Link>
            </Button>
            <Button asChild>
              <Link href="/menus/create">
                <Plus className="mr-2 h-4 w-4" />
                Create Menu
              </Link>
            </Button>
          </div>
        }
      />

      <Page.Content>
        {/* Filters */}
        <div className="flex flex-col sm:flex-row gap-4 mb-6">
          <div className="relative flex-1">
            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-muted-foreground h-4 w-4" />
            <Input
              placeholder="Search menus..."
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10"
            />
          </div>
          
          <Select value={typeFilter} onValueChange={setTypeFilter}>
            <SelectTrigger className="w-full sm:w-[180px]">
              <SelectValue placeholder="All Types" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Types</SelectItem>
              <SelectItem value="regular">Regular</SelectItem>
              <SelectItem value="breakfast">Breakfast</SelectItem>
              <SelectItem value="lunch">Lunch</SelectItem>
              <SelectItem value="dinner">Dinner</SelectItem>
              <SelectItem value="event">Event</SelectItem>
              <SelectItem value="seasonal">Seasonal</SelectItem>
            </SelectContent>
          </Select>

          <Select value={statusFilter} onValueChange={setStatusFilter}>
            <SelectTrigger className="w-full sm:w-[180px]">
              <SelectValue placeholder="All Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="all">All Status</SelectItem>
              <SelectItem value="active">Active</SelectItem>
              <SelectItem value="inactive">Inactive</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Menu Grid */}
        {filteredMenus.length === 0 ? (
          <EmptyState
            icon={FileText}
            title="No menus found"
            description={searchQuery || typeFilter !== 'all' || statusFilter !== 'all' 
              ? "Try adjusting your filters" 
              : "Create your first menu to get started"}
            actions={
              canCreate && !searchQuery && typeFilter === 'all' && statusFilter === 'all' && (
                <Button asChild>
                  <Link href="/menus/create">
                    <Plus className="mr-2 h-4 w-4" />
                    Create Menu
                  </Link>
                </Button>
              )
            }
          />
        ) : (
          <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            {filteredMenus.map(menu => (
              <MenuCard key={menu.id} menu={menu} />
            ))}
          </div>
        )}
      </Page.Content>
    </>
  );
}

export default function MenuIndex(props: PageProps) {
  return (
    <AppLayout>
      <Head title="Menus" />
      <Page>
        <MenuIndexContent {...props} />
      </Page>
    </AppLayout>
  );
}