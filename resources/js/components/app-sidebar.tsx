import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
  BookOpen,
  Building2,
  ChefHat,
  DollarSign,
  FileText,
  Folder,
  GitBranch,
  LayoutGrid,
  List,
  MapPin,
  Monitor,
  Package,
  PackageCheck,
  Plus,
  Settings,
  ShoppingCart,
  SlidersHorizontal,
  Tag,
  Users,
  Utensils,
} from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
    icon: LayoutGrid,
  },
  {
    title: 'Orders',
    href: '/orders',
    icon: ShoppingCart,
    items: [
      {
        title: 'All Orders',
        href: '/orders',
        icon: List,
      },
      {
        title: 'Create Order',
        href: '/orders/create',
        icon: Plus,
      },
      {
        title: 'Operations Center',
        href: '/orders/operations',
        icon: Monitor,
      },
      {
        title: 'Kitchen Display',
        href: '/orders/kitchen',
        icon: ChefHat,
      },
    ],
  },
  {
    title: 'Menu',
    href: '/menu',
    icon: FileText,
    items: [
      {
        title: 'All Menus',
        href: '/menu',
        icon: List,
      },
      {
        title: 'Create Menu',
        href: '/menu/create',
        icon: Plus,
      },
      {
        title: 'Menu Builder',
        href: '/menu/builder',
        icon: SlidersHorizontal,
      },
    ],
  },
  {
    title: 'Items',
    href: '/items',
    icon: Package,
    items: [
      {
        title: 'All Items',
        href: '/items',
        icon: List,
      },
      {
        title: 'Create Item',
        href: '/items/create',
        icon: Plus,
      },
      {
        title: 'Inventory',
        href: '/inventory',
        icon: PackageCheck,
      },
      {
        title: 'Modifiers',
        href: '/modifiers',
        icon: Utensils,
      },
      {
        title: 'Pricing',
        href: '/pricing',
        icon: DollarSign,
      },
      {
        title: 'Recipes',
        href: '/recipes',
        icon: ChefHat,
      },
    ],
  },
  {
    title: 'Staff',
    href: '/staff',
    icon: Users,
  },
  {
    title: 'Locations',
    href: '/locations',
    icon: MapPin,
    items: [
      {
        title: 'All Locations',
        href: '/locations',
        icon: List,
      },
      {
        title: 'Create Location',
        href: '/locations/create',
        icon: Plus,
      },
      {
        title: 'Location Types',
        href: '/locations/types',
        icon: Building2,
      },
      {
        title: 'Location Hierarchy',
        href: '/locations/hierarchy',
        icon: GitBranch,
      },
      {
        title: 'Location Settings',
        href: '/locations/settings',
        icon: SlidersHorizontal,
      },
    ],
  },
  {
    title: 'Offers',
    href: '/offers',
    icon: Tag,
  },
  {
    title: 'Settings',
    href: '/settings',
    icon: Settings,
  },
];

const footerNavItems: NavItem[] = [
  {
    title: 'Repository',
    href: 'https://github.com/laravel/react-starter-kit',
    icon: Folder,
  },
  {
    title: 'Documentation',
    href: 'https://laravel.com/docs/starter-kits#react',
    icon: BookOpen,
  },
];

export function AppSidebar() {
  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href="/dashboard" prefetch>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <NavMain items={mainNavItems} />
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItems} className="mt-auto" />
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
