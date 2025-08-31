import React, { useState, useCallback, useRef, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import { 
  Search, Plus, Minus, X, Clock, User, MapPin, Phone, CreditCard, 
  ChefHat, Zap, Hash, TrendingUp, ShoppingBag, Coffee, Pizza, 
  Salad, Sandwich, Sparkles, Star, Timer, Users, Home, Car,
  ChevronRight, Filter, History, Heart, Info, Check
} from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { motion, AnimatePresence } from 'framer-motion';
import { 
  Command, 
  CommandEmpty, 
  CommandGroup, 
  CommandInput, 
  CommandItem, 
  CommandList,
  CommandSeparator 
} from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Skeleton } from '@/components/ui/skeleton';

interface OrderItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  category?: string;
  image?: string;
  preparationTime?: number;
  notes?: string;
  modifiers?: Array<{ id: number; name: string; price: number }>;
}

interface SearchResult {
  id: number;
  name: string;
  price: number;
  category: string;
  image?: string;
  preparationTime?: number;
  isPopular?: boolean;
  orderFrequency?: number;
  matchReason?: 'exact' | 'fuzzy' | 'category' | 'recent';
  searchScore?: number;
}

interface CustomerInfo {
  name: string;
  phone: string;
  email?: string;
  orderType: 'dine_in' | 'takeout' | 'delivery';
  tableNumber?: string;
  address?: string;
  notes?: string;
}

export default function CreateOrderV2() {
  // State Management
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string | null>(null);
  const [customerInfo, setCustomerInfo] = useState<CustomerInfo>({
    name: '',
    phone: '',
    orderType: 'dine_in',
  });
  const [showCommandPalette, setShowCommandPalette] = useState(false);
  const [recentSearches, setRecentSearches] = useState<string[]>(['empanada', 'completo', 'bebida']);
  const [popularItems, setPopularItems] = useState<SearchResult[]>([]);
  const [searchSuggestions, setSearchSuggestions] = useState<string[]>([]);
  
  // Refs
  const searchInputRef = useRef<HTMLInputElement>(null);
  const searchDebounceRef = useRef<NodeJS.Timeout>();

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      // CMD/CTRL + K for search focus
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        searchInputRef.current?.focus();
      }
      // ESC to clear search
      if (e.key === 'Escape' && searchQuery) {
        setSearchQuery('');
        setSearchResults([]);
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [searchQuery]);

  // Auto-focus search on mount
  useEffect(() => {
    searchInputRef.current?.focus();
    // Load popular items
    loadPopularItems();
  }, []);

  // Load popular items from backend
  const loadPopularItems = async () => {
    try {
      const response = await fetch('/items/search/popular?limit=12');
      if (response.ok) {
        const data = await response.json();
        setPopularItems(data.items || []);
      } else {
        // Fallback to mock data
        setPopularItems([
          { id: 1, name: 'Empanada de Pino', price: 2200, category: 'Empanadas', preparationTime: 15, isPopular: true, orderFrequency: 156 },
          { id: 2, name: 'Completo Italiano', price: 4500, category: 'Completos', preparationTime: 10, isPopular: true, orderFrequency: 134 },
          { id: 3, name: 'Bebida 350ml', price: 1500, category: 'Bebidas', preparationTime: 0, orderFrequency: 289 },
          { id: 4, name: 'Papas Fritas', price: 2500, category: 'Acompañamientos', preparationTime: 8, orderFrequency: 198 },
          { id: 5, name: 'Empanada de Queso', price: 2000, category: 'Empanadas', preparationTime: 15, orderFrequency: 122 },
          { id: 6, name: 'Churrasco Italiano', price: 5500, category: 'Sandwiches', preparationTime: 12, orderFrequency: 87 },
        ]);
      }
    } catch (error) {
      console.error('Failed to load popular items:', error);
    }
  };

  // Enhanced search with debouncing
  const performSearch = useCallback(async (query: string) => {
    if (query.length < 2) {
      setSearchResults([]);
      setSearchSuggestions([]);
      return;
    }

    // Clear previous debounce
    if (searchDebounceRef.current) {
      clearTimeout(searchDebounceRef.current);
    }

    searchDebounceRef.current = setTimeout(async () => {
      setIsSearching(true);
      try {
        // Use web route for search
        const [searchResponse, suggestionsResponse] = await Promise.all([
          fetch(`/items/search/?q=${encodeURIComponent(query)}`),
          fetch(`/items/search/suggestions?q=${encodeURIComponent(query)}`)
        ]);
        
        if (searchResponse.ok) {
          const data = await searchResponse.json();
          console.log('Search results:', data); // Debug log
          setSearchResults(data.items || []);
        } else {
          console.error('Search failed:', searchResponse.status, searchResponse.statusText);
          // Fallback to mock data
          setSearchResults(getMockSearchResults(query));
        }
        
        if (suggestionsResponse.ok) {
          const suggestionsData = await suggestionsResponse.json();
          setSearchSuggestions(suggestionsData.suggestions || []);
        }
      } catch (error) {
        console.error('Search error:', error);
        // Use mock data on error
        setSearchResults(getMockSearchResults(query));
      } finally {
        setIsSearching(false);
      }
    }, 300); // 300ms debounce
  }, []);

  // Mock search results
  const getMockSearchResults = (query: string): SearchResult[] => {
    const allItems = [
      { id: 1, name: 'Empanada de Pino', price: 2200, category: 'Empanadas', preparationTime: 15, isPopular: true, orderFrequency: 450, matchReason: 'fuzzy' as const },
      { id: 2, name: 'Empanada de Queso', price: 2000, category: 'Empanadas', preparationTime: 15, orderFrequency: 380, matchReason: 'fuzzy' as const },
      { id: 3, name: 'Completo Italiano', price: 4500, category: 'Completos', preparationTime: 10, isPopular: true, orderFrequency: 520, matchReason: 'fuzzy' as const },
      { id: 4, name: 'Churrasco Italiano', price: 5500, category: 'Sandwiches', preparationTime: 12, orderFrequency: 290, matchReason: 'category' as const },
      { id: 5, name: 'Papas Fritas', price: 2500, category: 'Acompañamientos', preparationTime: 8, orderFrequency: 650, matchReason: 'fuzzy' as const },
      { id: 6, name: 'Bebida 350ml', price: 1500, category: 'Bebidas', preparationTime: 0, orderFrequency: 890, matchReason: 'recent' as const },
    ];

    const q = query.toLowerCase();
    return allItems.filter(item => 
      item.name.toLowerCase().includes(q) ||
      item.category.toLowerCase().includes(q)
    ).map(item => ({
      ...item,
      searchScore: item.name.toLowerCase().includes(q) ? 100 : 50
    }));
  };

  // Add item to order with animation
  const addItemToOrder = async (item: SearchResult) => {
    const existingItem = orderItems.find(oi => oi.id === item.id);
    
    if (existingItem) {
      setOrderItems(orderItems.map(oi => 
        oi.id === item.id 
          ? { ...oi, quantity: oi.quantity + 1 }
          : oi
      ));
    } else {
      setOrderItems([...orderItems, { 
        ...item, 
        quantity: 1,
        notes: ''
      }]);
    }

    // Record selection for learning - disabled for now
    // TODO: Fix CSRF issue with POST request
    // try {
    //   await fetch('/items/search/select', {
    //     method: 'POST',
    //     headers: {
    //       'Content-Type': 'application/json',
    //     },
    //     body: JSON.stringify({
    //       search_id: crypto.randomUUID(),
    //       item_id: item.id,
    //     }),
    //   });
    // } catch (error) {
    //   console.error('Failed to record selection:', error);
    // }

    // Add to recent searches
    if (!recentSearches.includes(item.name)) {
      setRecentSearches([item.name, ...recentSearches.slice(0, 4)]);
    }

    // Clear search
    setSearchQuery('');
    setSearchResults([]);
    searchInputRef.current?.focus();
  };

  // Update item quantity
  const updateItemQuantity = (itemId: number, delta: number) => {
    setOrderItems(orderItems.map(item => {
      if (item.id === itemId) {
        const newQuantity = Math.max(0, item.quantity + delta);
        return newQuantity > 0 ? { ...item, quantity: newQuantity } : null;
      }
      return item;
    }).filter(Boolean) as OrderItem[]);
  };

  // Remove item from order
  const removeItem = (itemId: number) => {
    setOrderItems(orderItems.filter(item => item.id !== itemId));
  };

  // Add item notes
  const updateItemNotes = (itemId: number, notes: string) => {
    setOrderItems(orderItems.map(item => 
      item.id === itemId ? { ...item, notes } : item
    ));
  };

  // Calculations
  const calculateSubtotal = () => {
    return orderItems.reduce((total, item) => {
      const itemTotal = item.price * item.quantity;
      const modifierTotal = (item.modifiers || []).reduce((sum, mod) => sum + mod.price, 0) * item.quantity;
      return total + itemTotal + modifierTotal;
    }, 0);
  };

  const calculateTax = () => Math.round(calculateSubtotal() * 0.19);
  const calculateTotal = () => calculateSubtotal() + calculateTax();
  const calculatePreparationTime = () => {
    return Math.max(...orderItems.map(item => item.preparationTime || 0), 10);
  };
  const getTotalItems = () => orderItems.reduce((sum, item) => sum + item.quantity, 0);

  // Quick category filters with better icons and colors
  const categories = [
    { id: 'all', name: 'Todo', icon: Sparkles, color: 'from-gray-400 to-gray-600' },
    { id: 'popular', name: 'Populares', icon: TrendingUp, color: 'from-purple-400 to-purple-600' },
    { id: 'empanadas', name: 'Empanadas', icon: ShoppingBag, color: 'from-orange-400 to-orange-600' },
    { id: 'sandwiches', name: 'Sandwiches', icon: Sandwich, color: 'from-green-400 to-green-600' },
    { id: 'beverages', name: 'Bebidas', icon: Coffee, color: 'from-blue-400 to-blue-600' },
    { id: 'sides', name: 'Acompañamientos', icon: Pizza, color: 'from-red-400 to-red-600' },
    { id: 'salads', name: 'Ensaladas', icon: Salad, color: 'from-teal-400 to-teal-600' },
  ];

  // Process order
  const processOrder = () => {
    if (orderItems.length === 0) return;
    
    const orderData = {
      items: orderItems,
      customer: customerInfo,
      subtotal: calculateSubtotal(),
      tax: calculateTax(),
      total: calculateTotal(),
      preparationTime: calculatePreparationTime(),
    };

    console.log('Processing order:', orderData);
    // router.post('/orders', orderData);
  };

  return (
    <AppLayout>
      <Head title="Nueva Orden - Sistema Inteligente" />
      
      <div className="flex h-[calc(100vh-3.5rem)] bg-gradient-to-br from-slate-50 via-white to-slate-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900">
        {/* Main Content Area */}
        <div className="flex-1 flex flex-col overflow-hidden">
          {/* Enhanced Header */}
          <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center gap-4">
                <div className="p-2 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg">
                  <ShoppingBag className="h-6 w-6 text-white" />
                </div>
                <div>
                  <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Nueva Orden</h1>
                  <p className="text-sm text-gray-500 dark:text-gray-400">Sistema de búsqueda inteligente</p>
                </div>
              </div>
              
              {/* Quick Stats */}
              <div className="flex items-center gap-6">
                <div className="text-center">
                  <div className="text-2xl font-bold text-gray-900 dark:text-white">
                    {getTotalItems()}
                  </div>
                  <div className="text-xs text-gray-500 uppercase tracking-wider">Items</div>
                </div>
                <div className="text-center">
                  <div className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-1">
                    <Timer className="h-4 w-4" />
                    {calculatePreparationTime()}m
                  </div>
                  <div className="text-xs text-gray-500 uppercase tracking-wider">Tiempo</div>
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => setShowCommandPalette(true)}
                  className="hidden md:flex items-center gap-2"
                >
                  <span className="text-xs">⌘K</span>
                  <span>Comandos</span>
                </Button>
              </div>
            </div>

            {/* Smart Search Bar with Enhancements */}
            <div className="relative">
              <div className="relative group">
                <div className="absolute inset-0 bg-gradient-to-r from-blue-400 via-purple-400 to-pink-400 rounded-2xl blur opacity-20 group-hover:opacity-30 transition-opacity" />
                <div className="relative flex items-center">
                  <Search className="absolute left-4 h-5 w-5 text-gray-400 z-10" />
                  <Input
                    ref={searchInputRef}
                    type="text"
                    placeholder="Buscar productos... (ej: emp pino, comp ital, pap fri)"
                    value={searchQuery}
                    onChange={(e) => {
                      setSearchQuery(e.target.value);
                      performSearch(e.target.value);
                    }}
                    className="w-full pl-12 pr-32 h-14 text-base bg-white dark:bg-gray-800 border-2 border-gray-200 dark:border-gray-700 focus:border-blue-500 dark:focus:border-blue-400 rounded-2xl shadow-sm transition-all focus:shadow-lg"
                    autoComplete="off"
                    autoFocus
                  />
                  <div className="absolute right-2 flex items-center gap-2">
                    {searchQuery && (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => {
                          setSearchQuery('');
                          setSearchResults([]);
                          searchInputRef.current?.focus();
                        }}
                        className="h-8 px-2"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                    <Badge variant="secondary" className="hidden md:flex">
                      {isSearching ? (
                        <span className="flex items-center gap-1">
                          <span className="animate-pulse">●</span> Buscando...
                        </span>
                      ) : (
                        'Listo'
                      )}
                    </Badge>
                  </div>
                </div>
              </div>

              {/* Enhanced Search Results Dropdown */}
              <AnimatePresence>
                {(searchResults.length > 0 || searchSuggestions.length > 0) && (
                  <motion.div
                    initial={{ opacity: 0, y: -10, scale: 0.95 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    exit={{ opacity: 0, y: -10, scale: 0.95 }}
                    transition={{ duration: 0.2 }}
                    className="absolute z-50 w-full mt-2 bg-white dark:bg-gray-800 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                  >
                    <ScrollArea className="max-h-[400px]">
                      {/* Search Suggestions */}
                      {searchSuggestions.length > 0 && (
                        <div className="p-2 border-b border-gray-100 dark:border-gray-700">
                          <p className="text-xs text-gray-500 px-2 mb-1">Sugerencias</p>
                          <div className="flex flex-wrap gap-1">
                            {searchSuggestions.map((suggestion, idx) => (
                              <Badge
                                key={idx}
                                variant="secondary"
                                className="cursor-pointer hover:bg-gray-200 dark:hover:bg-gray-600"
                                onClick={() => {
                                  setSearchQuery(suggestion);
                                  performSearch(suggestion);
                                }}
                              >
                                {suggestion}
                              </Badge>
                            ))}
                          </div>
                        </div>
                      )}

                      {/* Search Results */}
                      {searchResults.map((item, index) => (
                        <motion.button
                          key={item.id}
                          initial={{ opacity: 0, x: -20 }}
                          animate={{ opacity: 1, x: 0 }}
                          transition={{ delay: index * 0.05 }}
                          onClick={() => addItemToOrder(item)}
                          className="w-full px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 flex items-center justify-between transition-all group"
                        >
                          <div className="flex items-center gap-4">
                            {/* Item Image/Icon */}
                            <div className="relative">
                              <div className="w-14 h-14 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 rounded-xl flex items-center justify-center overflow-hidden">
                                {item.image ? (
                                  <img src={item.image} alt={item.name} className="w-full h-full object-cover" />
                                ) : (
                                  <ShoppingBag className="h-6 w-6 text-gray-400" />
                                )}
                              </div>
                              {item.isPopular && (
                                <div className="absolute -top-1 -right-1 w-5 h-5 bg-yellow-400 rounded-full flex items-center justify-center">
                                  <Star className="h-3 w-3 text-white fill-white" />
                                </div>
                              )}
                            </div>

                            {/* Item Details */}
                            <div className="text-left">
                              <div className="flex items-center gap-2">
                                <span className="font-medium text-gray-900 dark:text-white">
                                  {item.name}
                                </span>
                                {item.matchReason && (
                                  <Badge variant="outline" className="text-xs">
                                    {item.matchReason === 'exact' && '✨ Exacto'}
                                    {item.matchReason === 'fuzzy' && '🔍 Similar'}
                                    {item.matchReason === 'recent' && '🕐 Reciente'}
                                    {item.matchReason === 'category' && '📁 Categoría'}
                                  </Badge>
                                )}
                              </div>
                              <div className="flex items-center gap-3 mt-1">
                                <span className="text-sm text-gray-500">{item.category}</span>
                                {item.preparationTime && (
                                  <>
                                    <span className="text-gray-300">•</span>
                                    <span className="text-sm text-gray-500 flex items-center gap-1">
                                      <Clock className="h-3 w-3" />
                                      {item.preparationTime}min
                                    </span>
                                  </>
                                )}
                                {item.orderFrequency && (
                                  <>
                                    <span className="text-gray-300">•</span>
                                    <span className="text-sm text-gray-500">
                                      {item.orderFrequency}x hoy
                                    </span>
                                  </>
                                )}
                              </div>
                            </div>
                          </div>

                          {/* Price and Action */}
                          <div className="flex items-center gap-3">
                            <div className="text-right">
                              <div className="text-lg font-bold text-gray-900 dark:text-white">
                                ${item.price.toLocaleString()}
                              </div>
                            </div>
                            <div className="p-2.5 bg-blue-500 text-white rounded-xl group-hover:bg-blue-600 transition-colors">
                              <Plus className="h-5 w-5" />
                            </div>
                          </div>
                        </motion.button>
                      ))}
                    </ScrollArea>
                  </motion.div>
                )}
              </AnimatePresence>
            </div>
          </div>

          {/* Content Area */}
          <div className="flex-1 overflow-hidden flex flex-col p-6">
            {/* Category Pills */}
            <div className="mb-6">
              <ScrollArea className="w-full">
                <div className="flex gap-3 pb-2">
                  {categories.map((category) => {
                    const Icon = category.icon;
                    const isSelected = selectedCategory === category.id;
                    return (
                      <motion.button
                        key={category.id}
                        whileHover={{ scale: 1.05 }}
                        whileTap={{ scale: 0.95 }}
                        onClick={() => setSelectedCategory(isSelected ? null : category.id)}
                        className={cn(
                          "flex items-center gap-2 px-5 py-2.5 rounded-2xl transition-all whitespace-nowrap shadow-sm",
                          isSelected
                            ? "bg-gradient-to-r text-white shadow-lg"
                            : "bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:shadow-md"
                        )}
                        style={isSelected ? {
                          backgroundImage: `linear-gradient(to right, var(--tw-gradient-stops))`,
                          '--tw-gradient-from': category.color.split(' ')[0].replace('from-', ''),
                          '--tw-gradient-to': category.color.split(' ')[2].replace('to-', ''),
                        } : {}}
                      >
                        <Icon className="h-4 w-4" />
                        <span className="font-medium">{category.name}</span>
                        {category.id === 'popular' && (
                          <Badge className="bg-white/20 text-white border-0">Hot</Badge>
                        )}
                      </motion.button>
                    );
                  })}
                </div>
              </ScrollArea>
            </div>

            {/* Main Content - Popular Items or Category View */}
            {!searchQuery && (
              <ScrollArea className="flex-1">
                <div className="space-y-6">
                  {/* Recent Searches */}
                  {recentSearches.length > 0 && (
                    <div>
                      <div className="flex items-center gap-2 mb-3">
                        <History className="h-4 w-4 text-gray-400" />
                        <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300">Búsquedas Recientes</h3>
                      </div>
                      <div className="flex flex-wrap gap-2">
                        {recentSearches.map((search, idx) => (
                          <Button
                            key={idx}
                            variant="outline"
                            size="sm"
                            onClick={() => {
                              setSearchQuery(search);
                              performSearch(search);
                            }}
                            className="rounded-full"
                          >
                            {search}
                          </Button>
                        ))}
                      </div>
                    </div>
                  )}

                  {/* Popular Items Grid */}
                  <div>
                    <div className="flex items-center justify-between mb-4">
                      <div className="flex items-center gap-2">
                        <TrendingUp className="h-5 w-5 text-purple-500" />
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                          Más Vendidos Hoy
                        </h3>
                      </div>
                      <Button variant="ghost" size="sm">
                        Ver todos
                        <ChevronRight className="h-4 w-4 ml-1" />
                      </Button>
                    </div>

                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                      {popularItems.map((item, index) => (
                        <motion.div
                          key={item.id}
                          initial={{ opacity: 0, y: 20 }}
                          animate={{ opacity: 1, y: 0 }}
                          transition={{ delay: index * 0.05 }}
                          whileHover={{ y: -4 }}
                          className="group cursor-pointer"
                          onClick={() => addItemToOrder(item)}
                        >
                          <Card className="overflow-hidden border-2 border-transparent hover:border-blue-200 dark:hover:border-blue-800 transition-all hover:shadow-xl">
                            {/* Item Image */}
                            <div className="aspect-square bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-600 relative overflow-hidden">
                              <div className="absolute inset-0 flex items-center justify-center">
                                <ShoppingBag className="h-12 w-12 text-gray-400" />
                              </div>
                              {item.isPopular && (
                                <Badge className="absolute top-2 left-2 bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0">
                                  <Zap className="h-3 w-3 mr-1" />
                                  Popular
                                </Badge>
                              )}
                              <div className="absolute bottom-2 right-2 bg-black/50 text-white px-2 py-1 rounded-lg text-xs flex items-center gap-1">
                                <Clock className="h-3 w-3" />
                                {item.preparationTime}m
                              </div>
                            </div>

                            {/* Item Details */}
                            <div className="p-4">
                              <h4 className="font-semibold text-gray-900 dark:text-white mb-1 line-clamp-1">
                                {item.name}
                              </h4>
                              <p className="text-sm text-gray-500 mb-2">{item.category}</p>
                              
                              <div className="flex items-center justify-between">
                                <div>
                                  <div className="text-xl font-bold text-blue-600 dark:text-blue-400">
                                    ${item.price.toLocaleString()}
                                  </div>
                                  <div className="text-xs text-gray-500">
                                    {item.orderFrequency}x vendido
                                  </div>
                                </div>
                                <Button
                                  size="sm"
                                  className="opacity-0 group-hover:opacity-100 transition-opacity bg-blue-500 hover:bg-blue-600 text-white"
                                >
                                  <Plus className="h-4 w-4" />
                                </Button>
                              </div>
                            </div>
                          </Card>
                        </motion.div>
                      ))}
                    </div>
                  </div>
                </div>
              </ScrollArea>
            )}
          </div>
        </div>

        {/* Enhanced Order Summary Sidebar */}
        <div className="w-[420px] bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col shadow-xl">
          {/* Order Type Selector */}
          <div className="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Tipo de Orden</h2>
            <RadioGroup 
              value={customerInfo.orderType} 
              onValueChange={(value) => setCustomerInfo({ ...customerInfo, orderType: value as any })}
            >
              <div className="grid grid-cols-3 gap-3">
                <Label className="cursor-pointer">
                  <RadioGroupItem value="dine_in" className="sr-only" />
                  <div className={cn(
                    "flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all",
                    customerInfo.orderType === 'dine_in' 
                      ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                      : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                  )}>
                    <Home className="h-5 w-5" />
                    <span className="text-sm font-medium">Local</span>
                  </div>
                </Label>
                <Label className="cursor-pointer">
                  <RadioGroupItem value="takeout" className="sr-only" />
                  <div className={cn(
                    "flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all",
                    customerInfo.orderType === 'takeout' 
                      ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                      : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                  )}>
                    <ShoppingBag className="h-5 w-5" />
                    <span className="text-sm font-medium">Llevar</span>
                  </div>
                </Label>
                <Label className="cursor-pointer">
                  <RadioGroupItem value="delivery" className="sr-only" />
                  <div className={cn(
                    "flex flex-col items-center gap-2 p-3 rounded-xl border-2 transition-all",
                    customerInfo.orderType === 'delivery' 
                      ? "border-blue-500 bg-blue-50 dark:bg-blue-900/20" 
                      : "border-gray-200 dark:border-gray-700 hover:border-gray-300"
                  )}>
                    <Car className="h-5 w-5" />
                    <span className="text-sm font-medium">Delivery</span>
                  </div>
                </Label>
              </div>
            </RadioGroup>

            {/* Dynamic Customer Info Fields */}
            <div className="mt-4 space-y-3">
              <div className="flex items-center gap-3">
                <User className="h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Nombre del cliente"
                  value={customerInfo.name}
                  onChange={(e) => setCustomerInfo({ ...customerInfo, name: e.target.value })}
                  className="flex-1 h-10"
                />
              </div>
              
              <div className="flex items-center gap-3">
                <Phone className="h-4 w-4 text-gray-400" />
                <Input
                  placeholder="Teléfono"
                  value={customerInfo.phone}
                  onChange={(e) => setCustomerInfo({ ...customerInfo, phone: e.target.value })}
                  className="flex-1 h-10"
                />
              </div>

              {customerInfo.orderType === 'dine_in' && (
                <div className="flex items-center gap-3">
                  <Hash className="h-4 w-4 text-gray-400" />
                  <Input
                    placeholder="Número de mesa"
                    value={customerInfo.tableNumber || ''}
                    onChange={(e) => setCustomerInfo({ ...customerInfo, tableNumber: e.target.value })}
                    className="flex-1 h-10"
                  />
                </div>
              )}

              {customerInfo.orderType === 'delivery' && (
                <div className="flex items-center gap-3">
                  <MapPin className="h-4 w-4 text-gray-400" />
                  <Input
                    placeholder="Dirección de entrega"
                    value={customerInfo.address || ''}
                    onChange={(e) => setCustomerInfo({ ...customerInfo, address: e.target.value })}
                    className="flex-1 h-10"
                  />
                </div>
              )}
            </div>
          </div>

          {/* Order Items with Enhanced UI */}
          <ScrollArea className="flex-1">
            <div className="p-6">
              <div className="flex items-center justify-between mb-4">
                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                  Productos
                </h3>
                <Badge variant="secondary">
                  {getTotalItems()} {getTotalItems() === 1 ? 'item' : 'items'}
                </Badge>
              </div>
              
              {orderItems.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-12 text-center">
                  <div className="w-20 h-20 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                    <ShoppingBag className="h-10 w-10 text-gray-400" />
                  </div>
                  <p className="text-gray-500 dark:text-gray-400 font-medium">Orden vacía</p>
                  <p className="text-sm text-gray-400 mt-1">Usa la búsqueda para agregar productos</p>
                  <Button 
                    variant="outline" 
                    size="sm" 
                    className="mt-4"
                    onClick={() => searchInputRef.current?.focus()}
                  >
                    Comenzar a buscar
                  </Button>
                </div>
              ) : (
                <div className="space-y-3">
                  <AnimatePresence>
                    {orderItems.map((item) => (
                      <motion.div
                        key={item.id}
                        layout
                        initial={{ opacity: 0, x: 20, scale: 0.9 }}
                        animate={{ opacity: 1, x: 0, scale: 1 }}
                        exit={{ opacity: 0, x: -20, scale: 0.9 }}
                        className="group"
                      >
                        <Card className="p-4 hover:shadow-md transition-all">
                          <div className="flex items-start justify-between mb-3">
                            <div className="flex-1">
                              <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                {item.name}
                                {item.modifiers && item.modifiers.length > 0 && (
                                  <Badge variant="secondary" className="text-xs">
                                    +{item.modifiers.length}
                                  </Badge>
                                )}
                              </h4>
                              <p className="text-sm text-gray-500 mt-1">
                                ${item.price.toLocaleString()} c/u
                              </p>
                            </div>
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => removeItem(item.id)}
                              className="opacity-0 group-hover:opacity-100 transition-opacity -mt-1 -mr-2"
                            >
                              <X className="h-4 w-4" />
                            </Button>
                          </div>

                          {/* Quantity Controls */}
                          <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => updateItemQuantity(item.id, -1)}
                                className="h-8 w-8 p-0"
                              >
                                <Minus className="h-3 w-3" />
                              </Button>
                              <span className="w-12 text-center font-semibold text-gray-900 dark:text-white">
                                {item.quantity}
                              </span>
                              <Button
                                variant="outline"
                                size="sm"
                                onClick={() => updateItemQuantity(item.id, 1)}
                                className="h-8 w-8 p-0"
                              >
                                <Plus className="h-3 w-3" />
                              </Button>
                            </div>
                            <div className="text-lg font-bold text-gray-900 dark:text-white">
                              ${(item.price * item.quantity).toLocaleString()}
                            </div>
                          </div>

                          {/* Item Notes */}
                          <div className="mt-3">
                            <Input
                              placeholder="Agregar nota (ej: sin cebolla)"
                              value={item.notes || ''}
                              onChange={(e) => updateItemNotes(item.id, e.target.value)}
                              className="h-8 text-sm"
                            />
                          </div>
                        </Card>
                      </motion.div>
                    ))}
                  </AnimatePresence>
                </div>
              )}
            </div>
          </ScrollArea>

          {/* Enhanced Total Section */}
          <div className="border-t border-gray-200 dark:border-gray-700 p-6 bg-gradient-to-t from-gray-50 to-white dark:from-gray-900 dark:to-gray-800">
            <div className="space-y-3 mb-4">
              <div className="flex justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-400">Subtotal</span>
                <span className="font-medium text-gray-900 dark:text-white">
                  ${calculateSubtotal().toLocaleString()}
                </span>
              </div>
              <div className="flex justify-between text-sm">
                <span className="text-gray-600 dark:text-gray-400">IVA (19%)</span>
                <span className="font-medium text-gray-900 dark:text-white">
                  ${calculateTax().toLocaleString()}
                </span>
              </div>
              <div className="flex justify-between text-xl font-bold pt-3 border-t border-gray-200 dark:border-gray-700">
                <span className="text-gray-900 dark:text-white">Total</span>
                <span className="text-blue-600 dark:text-blue-400">
                  ${calculateTotal().toLocaleString()}
                </span>
              </div>
            </div>

            <div className="grid grid-cols-2 gap-3">
              <Button
                variant="outline"
                size="lg"
                onClick={() => {
                  setOrderItems([]);
                  setCustomerInfo({
                    name: '',
                    phone: '',
                    orderType: 'dine_in',
                  });
                }}
                className="h-12"
              >
                Limpiar
              </Button>
              <Button
                size="lg"
                className="h-12 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white shadow-lg"
                disabled={orderItems.length === 0}
                onClick={processOrder}
              >
                <Check className="h-5 w-5 mr-2" />
                Procesar
              </Button>
            </div>
          </div>
        </div>
      </div>

      {/* Command Palette */}
      <AnimatePresence>
        {showCommandPalette && (
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
            onClick={() => setShowCommandPalette(false)}
          >
            <motion.div
              initial={{ scale: 0.9, y: 20 }}
              animate={{ scale: 1, y: 0 }}
              exit={{ scale: 0.9, y: 20 }}
              onClick={(e) => e.stopPropagation()}
              className="w-full max-w-2xl"
            >
              <Command className="rounded-2xl shadow-2xl">
                <CommandInput placeholder="Buscar comando..." />
                <CommandList>
                  <CommandEmpty>No se encontraron comandos</CommandEmpty>
                  <CommandGroup heading="Acciones Rápidas">
                    <CommandItem onSelect={() => {
                      searchInputRef.current?.focus();
                      setShowCommandPalette(false);
                    }}>
                      <Search className="h-4 w-4 mr-2" />
                      Enfocar búsqueda
                    </CommandItem>
                    <CommandItem onSelect={() => {
                      setOrderItems([]);
                      setShowCommandPalette(false);
                    }}>
                      <X className="h-4 w-4 mr-2" />
                      Limpiar orden
                    </CommandItem>
                    <CommandItem onSelect={() => {
                      processOrder();
                      setShowCommandPalette(false);
                    }}>
                      <CreditCard className="h-4 w-4 mr-2" />
                      Procesar orden
                    </CommandItem>
                  </CommandGroup>
                  <CommandSeparator />
                  <CommandGroup heading="Filtros">
                    {categories.map((category) => {
                      const Icon = category.icon;
                      return (
                        <CommandItem
                          key={category.id}
                          onSelect={() => {
                            setSelectedCategory(category.id);
                            setShowCommandPalette(false);
                          }}
                        >
                          <Icon className="h-4 w-4 mr-2" />
                          {category.name}
                        </CommandItem>
                      );
                    })}
                  </CommandGroup>
                </CommandList>
              </Command>
            </motion.div>
          </motion.div>
        )}
      </AnimatePresence>
    </AppLayout>
  );
}