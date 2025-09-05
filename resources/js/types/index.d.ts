import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  url: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon | null;
  isActive?: boolean;
  items?: NavItem[];
}

export interface CurrencyConfig {
  code: string;
  precision: number;
  subunit: number;
  symbol: string;
  symbolFirst: boolean;
  decimalMark: string;
  thousandsSeparator: string;
}

export interface Business {
  id: number;
  name: string;
  currency: string;
  [key: string]: unknown;
}

export interface BusinessData {
  current: Business | null;
  businesses: Business[];
  currency: CurrencyConfig;
}

export interface SharedData {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
  business?: BusinessData;
  [key: string]: unknown;
}

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  emailVerifiedAt: string | null;
  createdAt: string;
  updatedAt: string;
  [key: string]: unknown; // This allows for additional properties...
}
