import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
  BookOpen,
  ChefHat,
  FileText,
  Folder,
  LayoutGrid,
  List,
  MapPin,
  Monitor,
  Package,
  Plus,
  Settings,
  ShoppingCart,
  Tag,
  Users,
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
  },
  {
    title: 'Items',
    href: '/items',
    icon: Package,
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
