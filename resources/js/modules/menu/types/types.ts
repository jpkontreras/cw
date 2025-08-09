export interface MenuItem {
  id: number;
  itemId: number;
  displayName?: string;
  displayDescription?: string;
  priceOverride?: number;
  isFeatured: boolean;
  isRecommended: boolean;
  isNew: boolean;
  isSeasonal: boolean;
  sortOrder: number;
  baseItem?: {
    name: string;
    description?: string;
    basePrice: number | null;
    preparationTime?: number;
    category?: string;
    imageUrl?: string;
  };
}

export interface MenuSection {
  id: number;
  name: string;
  description?: string;
  icon?: string;
  isActive: boolean;
  isFeatured: boolean;
  isCollapsed?: boolean;
  sortOrder: number;
  items: MenuItem[];
  children?: MenuSection[];
}

export interface AvailableItem {
  id: number;
  name: string;
  description?: string;
  basePrice: number | null;
  category?: string;
  isActive: boolean;
  imageUrl?: string;
  tags?: string[];
}

export interface Menu {
  id: number;
  name: string;
  type: string;
  isActive: boolean;
}

export interface MenuFeatures {
  nutritionalInfo: boolean;
  dietaryLabels: boolean;
  allergenInfo: boolean;
  seasonalItems: boolean;
  featuredItems: boolean;
  recommendedItems: boolean;
  itemBadges: boolean;
  customImages: boolean;
}

export interface MenuBuilderPageProps {
  menu: Menu | null;
  allMenus: Menu[];
  structure: {
    sections: MenuSection[];
  };
  availableItems: AvailableItem[];
  features: MenuFeatures;
}