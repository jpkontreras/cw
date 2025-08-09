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

export const MENU_TEMPLATES = [
  {
    name: 'Classic Restaurant',
    description: 'Traditional full-service restaurant menu structure',
    icon: 'mains',
    sections: [
      { name: 'Appetizers', icon: 'appetizers', description: 'Starters and small plates' },
      { name: 'Soups & Salads', icon: 'soups', description: 'Fresh and healthy options' },
      { name: 'Main Courses', icon: 'mains', description: 'Signature entrées' },
      { name: 'Desserts', icon: 'desserts', description: 'Sweet endings' },
      { name: 'Beverages', icon: 'beverages', description: 'Drinks and refreshments' },
    ],
  },
  {
    name: 'Fast Food',
    description: 'Quick service restaurant with combo meals',
    icon: 'combos',
    sections: [
      { name: 'Combos & Meals', icon: 'combos', description: 'Value meal deals' },
      { name: 'Burgers & Sandwiches', icon: 'mains', description: 'Main items' },
      { name: 'Sides & Extras', icon: 'sides', description: 'Fries, nuggets, and more' },
      { name: 'Beverages', icon: 'beverages', description: 'Soft drinks and shakes' },
      { name: 'Kids Menu', icon: 'kids', description: 'Smaller portions for children' },
    ],
  },
  {
    name: 'Food Truck',
    description: 'Streamlined menu for mobile service',
    icon: 'grilled',
    sections: [
      { name: 'Daily Specials', icon: 'specials', description: 'Today\'s featured items' },
      { name: 'Main Items', icon: 'mains', description: 'Signature dishes' },
      { name: 'Sides', icon: 'sides', description: 'Quick add-ons' },
      { name: 'Beverages', icon: 'beverages', description: 'Drinks to go' },
    ],
  },
  {
    name: 'Café & Coffee Shop',
    description: 'Coffee shop with light meals and pastries',
    icon: 'beverages',
    sections: [
      { name: 'Hot Beverages', icon: 'beverages', description: 'Coffee, tea, and specialty drinks' },
      { name: 'Cold Beverages', icon: 'icecream', description: 'Iced drinks and smoothies' },
      { name: 'Breakfast', icon: 'breakfast', description: 'Morning favorites' },
      { name: 'Pastries & Desserts', icon: 'desserts', description: 'Fresh baked goods' },
      { name: 'Light Meals', icon: 'salads', description: 'Sandwiches and salads' },
    ],
  },
  {
    name: 'Bar & Grill',
    description: 'Pub-style menu with drinks and grilled items',
    icon: 'beers',
    sections: [
      { name: 'Starters', icon: 'appetizers', description: 'Shareable appetizers' },
      { name: 'From the Grill', icon: 'grilled', description: 'BBQ and grilled specialties' },
      { name: 'Main Courses', icon: 'mains', description: 'Hearty entrées' },
      { name: 'Beer Selection', icon: 'beers', description: 'Draft and bottled beers' },
      { name: 'Wine & Cocktails', icon: 'wines', description: 'Wine list and mixed drinks' },
    ],
  },
  {
    name: 'Pizza Restaurant',
    description: 'Pizzeria with Italian favorites',
    icon: 'appetizers',
    sections: [
      { name: 'Antipasti', icon: 'appetizers', description: 'Italian starters' },
      { name: 'Pizza', icon: 'appetizers', description: 'Traditional and specialty pizzas' },
      { name: 'Pasta', icon: 'mains', description: 'Classic pasta dishes' },
      { name: 'Desserts', icon: 'desserts', description: 'Italian sweets' },
      { name: 'Beverages', icon: 'beverages', description: 'Soft drinks and Italian sodas' },
      { name: 'Wine Selection', icon: 'wines', description: 'Italian wines' },
    ],
  },
  {
    name: 'Seafood Restaurant',
    description: 'Fresh seafood and ocean specialties',
    icon: 'seafood',
    sections: [
      { name: 'Raw Bar', icon: 'seafood', description: 'Oysters, clams, and ceviche' },
      { name: 'Appetizers', icon: 'appetizers', description: 'Seafood starters' },
      { name: 'Fresh Catch', icon: 'seafood', description: 'Daily fish selections' },
      { name: 'Grilled & Fried', icon: 'grilled', description: 'Prepared seafood dishes' },
      { name: 'Sides', icon: 'sides', description: 'Accompaniments' },
      { name: 'Wine & Beer', icon: 'wines', description: 'Curated pairings' },
    ],
  },
  {
    name: 'Breakfast & Brunch',
    description: 'All-day breakfast and brunch menu',
    icon: 'breakfast',
    sections: [
      { name: 'Breakfast Classics', icon: 'breakfast', description: 'Traditional morning favorites' },
      { name: 'Omelets & Eggs', icon: 'breakfast', description: 'Made-to-order egg dishes' },
      { name: 'Pancakes & Waffles', icon: 'desserts', description: 'Sweet breakfast options' },
      { name: 'Healthy Options', icon: 'salads', description: 'Light and nutritious choices' },
      { name: 'Beverages', icon: 'beverages', description: 'Coffee, juice, and more' },
    ],
  },
] as const;