import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import {
  BarChart3,
  Building2,
  Calendar,
  ChefHat,
  DollarSign,
  FileText,
  Gift,
  GitBranch,
  LayoutGrid,
  List,
  MapPin,
  Monitor,
  Package,
  PackageCheck,
  Plus,
  Settings,
  Shield,
  ShoppingCart,
  SlidersHorizontal,
  Tag,
  Users,
  Utensils,
  Wrench,
} from 'lucide-react';
import AppLogo from './app-logo';
import { Activity } from 'lucide-react';

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
        title: 'New Order',
        href: '/orders/new',
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
    title: 'Offers',
    href: '/offers',
    icon: Gift,
    items: [
      {
        title: 'All Offers',
        href: '/offers',
        icon: List,
      },
      {
        title: 'Create Offer',
        href: '/offers/create',
        icon: Plus,
      },
      {
        title: 'Analytics',
        href: '/offers/analytics',
        icon: BarChart3,
      },
    ],
  },
  {
    title: 'Staff',
    href: '/staff',
    icon: Users,
    items: [
      {
        title: 'All Staff',
        href: '/staff',
        icon: List,
      },
      {
        title: 'Add Staff Member',
        href: '/staff/create',
        icon: Plus,
      },
      {
        title: 'Schedule',
        href: '/staff/schedule',
        icon: Calendar,
      },
      {
        title: 'Attendance',
        href: '/staff/attendance',
        icon: Monitor,
      },
      {
        title: 'Roles & Permissions',
        href: '/staff/roles',
        icon: Shield,
      },
      {
        title: 'Reports',
        href: '/staff/reports',
        icon: FileText,
      },
    ],
  },
  {
    title: 'Businesses',
    href: '/businesses',
    icon: Building2,
    items: [
      {
        title: 'All Businesses',
        href: '/businesses',
        icon: List,
      },
      {
        title: 'Current Business',
        href: '/businesses/current',
        icon: Building2,
      },
      {
        title: 'Business Settings',
        href: '/businesses/settings',
        icon: Settings,
      },
      {
        title: 'Team Members',
        href: '/businesses/users',
        icon: Users,
      },
    ],
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
    title: 'System Settings',
    href: '/system-settings',
    icon: Wrench,
    items: [
      {
        title: 'General Settings',
        href: '/system-settings',
        icon: Settings,
      },
      {
        title: 'Categories & Tags',
        href: '/taxonomies',
        icon: Tag,
      },
    ],
  },
];

const footerNavItems: NavItem[] = [];

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
