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

export interface SearchFilters {
  category?: string;
  min_price?: number;
  max_price?: number;
  is_available?: boolean;
  in_stock?: boolean;
}

interface OrderContextType {
  // State
  orderItems: OrderItem[];
  customerInfo: CustomerInfo;
  searchQuery: string;
  viewMode: 'list' | 'grid';
  favoriteItems: SearchResult[];
  recentSearches: string[];
  recentItems: SearchResult[];
  isSearchMode: boolean;
  searchResults: SearchResult[];
  isSearching: boolean;
  popularItems: SearchResult[];
  searchFilters: SearchFilters;
  activeFiltersCount: number;
  
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
  setSearchFilters: React.Dispatch<React.SetStateAction<SearchFilters>>;
  
  // Methods
  addItemToOrder: (item: SearchResult) => void;
  removeItemFromOrder: (itemId: number) => void;
  updateItemQuantity: (itemId: number, quantity: number) => void;
  updateItemNotes: (itemId: number, notes: string) => void;
  toggleFavorite: (item: SearchResult) => void;
  addToRecentSearches: (search: string) => void;
  processOrder: () => void;
  clearOrder: () => void;
  performSearch: (query: string, filters?: SearchFilters) => Promise<void>;
  handleCategorySelect: (category: string) => void;
  recordSearchSelection: (item: SearchResult) => void;
  updateSearchFilter: (key: keyof SearchFilters, value: any) => void;
  clearSearchFilters: () => void;
  
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
  const [recentItems, setRecentItems] = useState<SearchResult[]>([]);
  const [isSearchMode, setIsSearchMode] = useState(false);
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [popularItems, setPopularItems] = useState<SearchResult[]>(initialPopularItems);
  const [searchFilters, setSearchFilters] = useState<SearchFilters>({});
  
  // Refs
  const searchDebounceRef = useRef<NodeJS.Timeout | null>(null);
  const searchIdRef = useRef<string | null>(null);
  
  // Calculate active filters count
  const activeFiltersCount = Object.values(searchFilters).filter(value => 
    value !== undefined && value !== null && value !== ''
  ).length;

  // Load favorites and recent searches from backend
  useEffect(() => {
    const loadUserData = async () => {
      try {
        // Load favorites from backend
        const favoritesResponse = await axios.get('/items/favorites');
        if (favoritesResponse.data?.items) {
          setFavoriteItems(favoritesResponse.data.items);
        }
        
        // Load recent items from backend (not searches)
        const recentResponse = await axios.get('/items/search/recent');
        if (recentResponse.data?.items) {
          setRecentItems(recentResponse.data.items);
        } else if (recentResponse.data?.searches) {
          // Fallback for legacy
          setRecentSearches(recentResponse.data.searches);
        }
      } catch (error) {
        console.error('Error loading user data:', error);
        // Fallback to localStorage if backend fails
        const storedFavorites = localStorage.getItem('favoriteItems');
        const storedSearches = localStorage.getItem('recentSearches');
        
        if (storedFavorites) {
          try {
            setFavoriteItems(JSON.parse(storedFavorites));
          } catch (e) {
            console.error('Error loading localStorage favorites:', e);
          }
        }
        
        if (storedSearches) {
          try {
            setRecentSearches(JSON.parse(storedSearches));
          } catch (e) {
            console.error('Error loading localStorage searches:', e);
          }
        }
      }
    };
    
    loadUserData();
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

  // Toggle favorite with backend sync
  const toggleFavorite = async (item: SearchResult) => {
    try {
      // Optimistic UI update
      setFavoriteItems(prev => {
        const isFavorite = prev.some(fav => fav.id === item.id);
        const newFavorites = isFavorite 
          ? prev.filter(fav => fav.id !== item.id)
          : [...prev, { ...item, isFavorite: true }];
        
        // Also save to localStorage as backup
        localStorage.setItem('favoriteItems', JSON.stringify(newFavorites));
        return newFavorites;
      });
      
      // Sync with backend
      const response = await axios.post('/items/favorites/toggle', { item_id: item.id });
      
      // If backend disagrees, update to match
      if (response.data?.is_favorite !== undefined) {
        setFavoriteItems(prev => {
          if (response.data.is_favorite) {
            // Make sure item is in favorites
            if (!prev.some(fav => fav.id === item.id)) {
              const updated = [...prev, { ...item, isFavorite: true }];
              localStorage.setItem('favoriteItems', JSON.stringify(updated));
              return updated;
            }
          } else {
            // Make sure item is not in favorites
            const updated = prev.filter(fav => fav.id !== item.id);
            localStorage.setItem('favoriteItems', JSON.stringify(updated));
            return updated;
          }
          return prev;
        });
      }
    } catch (error) {
      console.error('Error toggling favorite:', error);
      // On error, just keep the optimistic update
    }
  };

  // Add to recent searches - the backend will track this via search API
  const addToRecentSearches = (search: string) => {
    if (!search.trim()) return;
    
    setRecentSearches(prev => {
      const filtered = prev.filter(s => s !== search);
      const newSearches = [search, ...filtered].slice(0, 10); // Show last 10 as requested
      
      // Still save to localStorage as backup
      localStorage.setItem('recentSearches', JSON.stringify(newSearches));
      return newSearches;
    });
    
    // The search itself is recorded when performSearch is called
  };
  
  // Perform search function with server API
  const performSearch = useCallback(async (query: string, filters: SearchFilters = {}) => {
    if (query.length < 2 && Object.keys(filters).length === 0) {
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
          params: { 
            q: query,
            ...filters
          }
        });
        
        if (response.data) {
          // Extract items and mark favorites
          let items = response.data.items || response.data.data || [];
          
          // Mark which items are favorites
          items = items.map((item: SearchResult) => ({
            ...item,
            isFavorite: item.isFavorite || favoriteItems.some(fav => fav.id === item.id)
          }));
          
          setSearchResults(items);
          searchIdRef.current = response.data.searchId || response.data.meta?.searchId || null;
        }
      } catch (error) {
        console.error('Search error:', error);
        setSearchResults([]);
      } finally {
        setIsSearching(false);
      }
    }, 300);
  }, [favoriteItems]);

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
    setSearchFilters(prev => ({ ...prev, category }));
    setIsSearchMode(true);
    performSearch(searchQuery, { ...searchFilters, category });
  };
  
  // Update search filter
  const updateSearchFilter = (key: keyof SearchFilters, value: any) => {
    setSearchFilters(prev => {
      const updated = { ...prev };
      if (value === null || value === undefined || value === '') {
        delete updated[key];
      } else {
        updated[key] = value;
      }
      return updated;
    });
  };
  
  // Clear all search filters
  const clearSearchFilters = () => {
    setSearchFilters({});
  };

  // Order Item Management
  const addItemToOrder = (item: SearchResult) => {
    recordSearchSelection(item);
    
    // Add to recent items (optimistic update)
    setRecentItems(prev => {
      const filtered = prev.filter(i => i.id !== item.id);
      return [item, ...filtered].slice(0, 10);
    });
    
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
    recentItems,
    isSearchMode,
    searchResults,
    isSearching,
    popularItems,
    searchFilters,
    activeFiltersCount,
    
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
    setSearchFilters,
    
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
    updateSearchFilter,
    clearSearchFilters,
    
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