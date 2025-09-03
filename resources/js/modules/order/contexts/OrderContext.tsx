import React, { createContext, useContext, useState, useRef, useCallback, useEffect } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';

// ============================================================================
// TYPES & INTERFACES
// ============================================================================

export interface OrderItem {
  id: number;
  name: string;
  price: number;
  quantity: number;
  category?: string;
  image?: string;
  description?: string;
  preparationTime?: number;
  modifiers?: Array<{ id: number; name: string; price: number }>;
  notes?: string;
}

export interface CustomerInfo {
  name: string;
  phone: string;
  orderType: 'dine_in' | 'takeout' | 'delivery';
  tableNumber?: string;
  address?: string;
  notes?: string;
  specialInstructions?: string;
  paymentMethod?: 'cash' | 'card' | 'transfer';
}

export interface SearchResult {
  id: number;
  name: string;
  price: number;
  category?: string;
  image?: string;
  description?: string;
  preparationTime?: number;
  matchReason?: 'exact' | 'fuzzy' | 'category' | 'recent';
  searchScore?: number;
}

interface OrderContextType {
  // State
  orderItems: OrderItem[];
  customerInfo: CustomerInfo;
  searchQuery: string;
  viewMode: 'list' | 'grid';
  favoriteItems: SearchResult[];
  recentSearches: string[];
  isSearchMode: boolean;
  searchResults: SearchResult[];
  isSearching: boolean;
  popularItems: SearchResult[];
  
  // Refs
  searchIdRef: React.MutableRefObject<string | null>;
  
  // State setters
  setOrderItems: React.Dispatch<React.SetStateAction<OrderItem[]>>;
  setCustomerInfo: React.Dispatch<React.SetStateAction<CustomerInfo>>;
  setSearchQuery: React.Dispatch<React.SetStateAction<string>>;
  setViewMode: React.Dispatch<React.SetStateAction<'list' | 'grid'>>;
  setIsSearchMode: React.Dispatch<React.SetStateAction<boolean>>;
  setSearchResults: React.Dispatch<React.SetStateAction<SearchResult[]>>;
  setIsSearching: React.Dispatch<React.SetStateAction<boolean>>;
  
  // Methods
  addItemToOrder: (item: SearchResult) => void;
  removeItemFromOrder: (itemId: number) => void;
  updateItemQuantity: (itemId: number, quantity: number) => void;
  updateItemNotes: (itemId: number, notes: string) => void;
  toggleFavorite: (item: SearchResult) => void;
  addToRecentSearches: (search: string) => void;
  processOrder: () => void;
  clearOrder: () => void;
  performSearch: (query: string) => Promise<void>;
  handleCategorySelect: (category: string) => void;
  recordSearchSelection: (item: SearchResult) => void;
  
  // Calculations
  getTotalItems: () => number;
  calculateSubtotal: () => number;
  calculateTax: () => number;
  calculateTotal: () => number;
  calculatePreparationTime: () => number;
}

// ============================================================================
// CONTEXT
// ============================================================================

const OrderContext = createContext<OrderContextType | undefined>(undefined);

export const useOrder = () => {
  const context = useContext(OrderContext);
  if (!context) {
    throw new Error('useOrder must be used within an OrderProvider');
  }
  return context;
};

// ============================================================================
// PROVIDER
// ============================================================================

interface OrderProviderProps {
  children: React.ReactNode;
  initialPopularItems?: SearchResult[];
}

export const OrderProvider: React.FC<OrderProviderProps> = ({ 
  children, 
  initialPopularItems = [] 
}) => {
  // State Management
  const [searchQuery, setSearchQuery] = useState('');
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);
  const [customerInfo, setCustomerInfo] = useState<CustomerInfo>({
    name: '',
    phone: '',
    orderType: 'dine_in',
  });
  const [viewMode, setViewMode] = useState<'list' | 'grid'>('list');
  const [favoriteItems, setFavoriteItems] = useState<SearchResult[]>([]);
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [isSearchMode, setIsSearchMode] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [popularItems, setPopularItems] = useState<SearchResult[]>(initialPopularItems);
  
  // Refs
  const searchDebounceRef = useRef<NodeJS.Timeout | null>(null);
  const searchIdRef = useRef<string | null>(null);

  // Load favorites and recent searches from localStorage
  useEffect(() => {
    const storedFavorites = localStorage.getItem('favoriteItems');
    const storedSearches = localStorage.getItem('recentSearches');
    
    if (storedFavorites) {
      try {
        setFavoriteItems(JSON.parse(storedFavorites));
      } catch (e) {
        console.error('Error loading favorites:', e);
      }
    }
    
    if (storedSearches) {
      try {
        setRecentSearches(JSON.parse(storedSearches));
      } catch (e) {
        console.error('Error loading recent searches:', e);
      }
    }
  }, []);

  // Load popular items from server
  useEffect(() => {
    if (popularItems.length === 0) {
      axios.get('/items/search/popular', { params: { limit: 12 } })
        .then(response => {
          setPopularItems(response.data.items || response.data.data || []);
        })
        .catch(error => {
          console.error('Error loading popular items:', error);
        });
    }
  }, [popularItems.length]);

  // Save favorites
  const toggleFavorite = (item: SearchResult) => {
    setFavoriteItems(prev => {
      const isFavorite = prev.some(fav => fav.id === item.id);
      const newFavorites = isFavorite 
        ? prev.filter(fav => fav.id !== item.id)
        : [...prev, item];
      
      localStorage.setItem('favoriteItems', JSON.stringify(newFavorites));
      return newFavorites;
    });
  };

  // Add to recent searches
  const addToRecentSearches = (search: string) => {
    if (!search.trim()) return;
    
    setRecentSearches(prev => {
      const filtered = prev.filter(s => s !== search);
      const newSearches = [search, ...filtered].slice(0, 5);
      localStorage.setItem('recentSearches', JSON.stringify(newSearches));
      return newSearches;
    });
  };
  
  // Perform search function with server API
  const performSearch = useCallback(async (query: string) => {
    if (query.length < 2) {
      setSearchResults([]);
      searchIdRef.current = null;
      return;
    }

    if (searchDebounceRef.current) {
      clearTimeout(searchDebounceRef.current);
    }

    searchDebounceRef.current = setTimeout(async () => {
      setIsSearching(true);
      try {
        const response = await axios.get('/items/search', {
          params: { q: query }
        });
        
        if (response.data) {
          setSearchResults(response.data.items || response.data.data || []);
          searchIdRef.current = response.data.searchId || response.data.meta?.searchId || null;
        }
      } catch (error) {
        console.error('Search error:', error);
        setSearchResults([]);
      } finally {
        setIsSearching(false);
      }
    }, 300);
  }, []);

  // Track search selection for learning
  const recordSearchSelection = useCallback((item: SearchResult) => {
    if (searchIdRef.current) {
      axios.post('/items/search/select', {
        search_id: searchIdRef.current,
        item_id: item.id,
        query: searchQuery
      }).catch(error => {
        console.error('Error recording search selection:', error);
      });
    }
  }, [searchQuery]);

  // Trigger search when searchQuery changes
  useEffect(() => {
    if (searchQuery) {
      performSearch(searchQuery);
    } else {
      setSearchResults([]);
    }
  }, [searchQuery, performSearch]);

  // Handle category selection
  const handleCategorySelect = (category: string) => {
    setSearchQuery(`categoria:${category}`);
  };

  // Order Item Management
  const addItemToOrder = (item: SearchResult) => {
    recordSearchSelection(item);
    
    setOrderItems(prevItems => {
      const existingItem = prevItems.find(orderItem => orderItem.id === item.id);
      
      if (existingItem) {
        return prevItems.map(orderItem =>
          orderItem.id === item.id
            ? { ...orderItem, quantity: orderItem.quantity + 1 }
            : orderItem
        );
      }
      
      return [...prevItems, { ...item, quantity: 1 }];
    });
    
    setIsSearchMode(false);
    setSearchQuery('');
    setSearchResults([]);
  };

  const removeItemFromOrder = (itemId: number) => {
    setOrderItems(prevItems => prevItems.filter(item => item.id !== itemId));
  };

  const updateItemQuantity = (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeItemFromOrder(itemId);
      return;
    }
    
    setOrderItems(prevItems =>
      prevItems.map(item =>
        item.id === itemId ? { ...item, quantity } : item
      )
    );
  };

  const updateItemNotes = (itemId: number, notes: string) => {
    setOrderItems(prevItems =>
      prevItems.map(item =>
        item.id === itemId ? { ...item, notes } : item
      )
    );
  };

  // Process Order
  const processOrder = () => {
    // Map frontend orderType to backend expected values
    const orderTypeMap: Record<string, string> = {
      'dine_in': 'dineIn',  // Backend expects camelCase per validation
      'takeout': 'takeout',
      'delivery': 'delivery',
      'catering': 'catering'
    };
    
    const orderData = {
      // Required fields
      locationId: 1, // TODO: Get from user context or settings
      type: orderTypeMap[customerInfo.orderType] || 'dineIn', // Map to backend expected format
      items: orderItems.map(item => ({
        itemId: item.id, // Changed from item_id to itemId
        quantity: item.quantity,
        notes: item.notes,
        modifiers: item.modifiers || []
      })),
      // Customer information
      customerName: customerInfo.name,
      customerPhone: customerInfo.phone,
      tableNumber: customerInfo.tableNumber,
      address: customerInfo.address,
      customerNotes: customerInfo.notes,
      specialInstructions: customerInfo.specialInstructions,
      paymentMethod: customerInfo.paymentMethod || 'cash',
      // Calculated values
      subtotal: calculateSubtotal(),
      tax: calculateTax(),
      total: calculateTotal(),
      preparationTime: calculatePreparationTime(),
    };
    
    router.post('/orders', orderData);
  };

  const clearOrder = () => {
    setOrderItems([]);
    setCustomerInfo({
      name: '',
      phone: '',
      orderType: 'dine_in',
    });
  };

  // Calculation Functions
  const getTotalItems = () => orderItems.reduce((sum, item) => sum + item.quantity, 0);
  const calculateSubtotal = () => orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  const calculateTax = () => Math.round(calculateSubtotal() * 0.19);
  const calculateTotal = () => calculateSubtotal() + calculateTax();
  const calculatePreparationTime = () => {
    if (orderItems.length === 0) return 0;
    const maxTime = Math.max(...orderItems.map(item => item.preparationTime || 15));
    return maxTime + Math.min(orderItems.length * 2, 10);
  };

  const value: OrderContextType = {
    // State
    orderItems,
    customerInfo,
    searchQuery,
    viewMode,
    favoriteItems,
    recentSearches,
    isSearchMode,
    searchResults,
    isSearching,
    popularItems,
    
    // Refs
    searchIdRef,
    
    // State setters
    setOrderItems,
    setCustomerInfo,
    setSearchQuery,
    setViewMode,
    setIsSearchMode,
    setSearchResults,
    setIsSearching,
    
    // Methods
    addItemToOrder,
    removeItemFromOrder,
    updateItemQuantity,
    updateItemNotes,
    toggleFavorite,
    addToRecentSearches,
    processOrder,
    clearOrder,
    performSearch,
    handleCategorySelect,
    recordSearchSelection,
    
    // Calculations
    getTotalItems,
    calculateSubtotal,
    calculateTax,
    calculateTotal,
    calculatePreparationTime,
  };

  return (
    <OrderContext.Provider value={value}>
      {children}
    </OrderContext.Provider>
  );
};