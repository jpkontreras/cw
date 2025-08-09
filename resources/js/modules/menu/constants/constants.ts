import { Beer, Cake, Coffee, IceCream, Pizza, Salad, Soup, Utensils, Wine } from 'lucide-react';

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
} as const;

export const SECTION_TEMPLATES = [
  { name: 'Appetizers', icon: 'appetizers', description: 'Starters and small plates' },
  { name: 'Main Courses', icon: 'mains', description: 'Primary dishes' },
  { name: 'Desserts', icon: 'desserts', description: 'Sweet endings' },
  { name: 'Beverages', icon: 'beverages', description: 'Drinks and refreshments' },
] as const;