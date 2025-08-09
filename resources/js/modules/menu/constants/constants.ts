import { 
  Baby, 
  Beer, 
  Cake, 
  Calendar, 
  Coffee, 
  Croissant, 
  Fish, 
  Flame, 
  IceCream, 
  Leaf, 
  Package2, 
  Pizza, 
  Salad, 
  Soup, 
  Utensils, 
  UtensilsCrossed, 
  Wine 
} from 'lucide-react';

export const SECTION_ICONS = {
  appetizers: Pizza,
  mains: Utensils,
  desserts: Cake,
  beverages: Coffee,
  salads: Salad,
  soups: Soup,
  wines: Wine,
  beers: Beer,
  icecream: IceCream,
  breakfast: Croissant,
  kids: Baby,
  specials: Calendar,
  sides: Package2,
  grilled: Flame,
  seafood: Fish,
  vegetarian: Leaf,
  combos: UtensilsCrossed,
} as const;

export const SECTION_TEMPLATE_CATEGORIES = {
  classic: 'Classic Sections',
  specialty: 'Specialty',
  drinks: 'Beverages',
} as const;

export const SECTION_TEMPLATES = [
  // Classic Sections
  { 
    name: 'Appetizers', 
    icon: 'appetizers', 
    description: 'Starters and small plates to begin the meal',
    category: 'classic' as const,
  },
  { 
    name: 'Main Courses', 
    icon: 'mains', 
    description: 'Primary dishes and entrées',
    category: 'classic' as const,
  },
  { 
    name: 'Desserts', 
    icon: 'desserts', 
    description: 'Sweet endings and treats',
    category: 'classic' as const,
  },
  { 
    name: 'Salads', 
    icon: 'salads', 
    description: 'Fresh and healthy options',
    category: 'classic' as const,
  },
  { 
    name: 'Soups', 
    icon: 'soups', 
    description: 'Warm bowls and broths',
    category: 'classic' as const,
  },
  { 
    name: 'Sides & Extras', 
    icon: 'sides', 
    description: 'Accompaniments and add-ons',
    category: 'classic' as const,
  },
  
  // Specialty Sections
  { 
    name: 'Breakfast', 
    icon: 'breakfast', 
    description: 'Morning favorites and brunch items',
    category: 'specialty' as const,
  },
  { 
    name: 'Kids Menu', 
    icon: 'kids', 
    description: 'Child-friendly portions and favorites',
    category: 'specialty' as const,
  },
  { 
    name: 'Daily Specials', 
    icon: 'specials', 
    description: 'Chef\'s features and rotating items',
    category: 'specialty' as const,
  },
  { 
    name: 'Grilled & BBQ', 
    icon: 'grilled', 
    description: 'Fire-grilled and barbecue specialties',
    category: 'specialty' as const,
  },
  { 
    name: 'Seafood', 
    icon: 'seafood', 
    description: 'Fresh fish and shellfish dishes',
    category: 'specialty' as const,
  },
  { 
    name: 'Vegetarian & Vegan', 
    icon: 'vegetarian', 
    description: 'Plant-based and dietary options',
    category: 'specialty' as const,
  },
  { 
    name: 'Combos & Meals', 
    icon: 'combos', 
    description: 'Value sets and complete meals',
    category: 'specialty' as const,
  },
  
  // Beverages
  { 
    name: 'Hot Beverages', 
    icon: 'beverages', 
    description: 'Coffee, tea, and warm drinks',
    category: 'drinks' as const,
  },
  { 
    name: 'Cold Beverages', 
    icon: 'icecream', 
    description: 'Soft drinks, juices, and cold refreshments',
    category: 'drinks' as const,
  },
  { 
    name: 'Wine Selection', 
    icon: 'wines', 
    description: 'Red, white, and sparkling wines',
    category: 'drinks' as const,
  },
  { 
    name: 'Beer & Spirits', 
    icon: 'beers', 
    description: 'Draft beers and cocktails',
    category: 'drinks' as const,
  },
] as const;