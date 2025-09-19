import React, { createContext, useContext, useState, useEffect, useCallback, useRef } from 'react';
import axios from 'axios';
import { debounce } from 'lodash';
import { router } from '@inertiajs/react';
import { SearchResult, OrderItem, CustomerInfo } from '../types';

interface SearchFilters {
  category?: string;
  priceRange?: string;
  availability?: string;
  dietary?: string;
}

interface OrderContextType {
  orderItems: OrderItem[];
  customerInfo: CustomerInfo;
  searchQuery: string;
  searchResults: SearchResult[];
  isSearching: boolean;
  isSearchMode: boolean;
  favoriteItems: SearchResult[];
  recentSearches: string[];
  recentItems: SearchResult[];
  popularItems: SearchResult[];
  searchFilters: SearchFilters;
  activeFiltersCount: number;
  sessionStatus: 'active' | 'saved' | 'processing' | 'completed';
  lastSavedAt: Date | null;
  orderUuid: string | null;
  sessionUuid: string | null;
  setCustomerInfo: (info: CustomerInfo) => void;
  setSearchQuery: (query: string) => void;
  setIsSearchMode: (mode: boolean) => void;
  addItemToOrder: (item: SearchResult) => void;
  removeItemFromOrder: (itemId: number) => void;
  updateItemQuantity: (itemId: number, quantity: number) => void;
  updateItemNotes: (itemId: number, notes: string) => void;
  toggleFavorite: (item: SearchResult) => void;
  addToRecentSearches: (query: string) => void;
  processOrder: () => void;
  handleCategorySelect: (category: string) => void;
  updateSearchFilter: (key: string, value: string | undefined) => void;
  clearSearchFilters: () => void;
  getTotalItems: () => number;
  calculateSubtotal: () => number;
  calculateTax: () => number;
  calculateTotal: () => number;
  saveDraftOrder: () => void;
}

const OrderContext = createContext<OrderContextType | undefined>(undefined);

export const useOrder = () => {
  const context = useContext(OrderContext);
  if (!context) {
    throw new Error('useOrder must be used within an OrderProvider');
  }
  return context;
};

interface OrderProviderProps {
  children: React.ReactNode;
  initialPopularItems?: SearchResult[];
  initialSessionUuid?: string;
}

export const OrderProvider: React.FC<OrderProviderProps> = ({ 
  children, 
  initialPopularItems = [],
  initialSessionUuid = null
}) => {
  const [orderItems, setOrderItems] = useState<OrderItem[]>([]);
  const [customerInfo, setCustomerInfo] = useState<CustomerInfo>({
    name: '',
    phone: '',
    email: '',
    address: '',
    notes: '',
    orderType: 'dine_in',
    paymentMethod: 'cash',
    tableNumber: '',
  });
  const [searchQuery, setSearchQuery] = useState('');
  const [searchResults, setSearchResults] = useState<SearchResult[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  const [isSearchMode, setIsSearchMode] = useState(false);
  const [favoriteItems, setFavoriteItems] = useState<SearchResult[]>([]);
  const [recentSearches, setRecentSearches] = useState<string[]>([]);
  const [recentItems, setRecentItems] = useState<SearchResult[]>([]);
  const [popularItems, setPopularItems] = useState<SearchResult[]>(initialPopularItems);
  const [searchFilters, setSearchFilters] = useState<SearchFilters>({});
  const [sessionStatus, setSessionStatus] = useState<'active' | 'saved' | 'processing' | 'completed'>('active');
  const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);
  const [orderUuid, setOrderUuid] = useState<string | null>(null);
  const [sessionUuid, setSessionUuid] = useState<string | null>(initialSessionUuid);
  
  const searchAbortController = useRef<AbortController | null>(null);
  const syncTimer = useRef<NodeJS.Timeout | null>(null);

  const activeFiltersCount = Object.values(searchFilters).filter(v => v !== undefined).length;

  const syncWithBackend = async () => {
    if (!sessionUuid) return;
    
    // Don't sync if session is completed or we have an order UUID
    if (sessionStatus === 'completed' || orderUuid) {
      return;
    }
    
    try {
      await axios.post(`/api/v1/orders/session/${sessionUuid}/sync`, {
        items: orderItems,
        customer_info: customerInfo,
        search_history: recentSearches,
        favorites: favoriteItems.map(item => item.id),
      });
      setLastSavedAt(new Date());
    } catch (error) {
      // Only log error if it's not a 404 (session might be converted already)
      if (axios.isAxiosError(error) && error.response?.status !== 404) {
        console.error('Failed to sync with backend:', error);
      }
    }
  };

  const debouncedSync = useCallback(
    debounce(() => {
      syncWithBackend();
    }, 2000),
    [orderItems, customerInfo, sessionUuid]
  );

  useEffect(() => {
    if (sessionUuid && orderItems.length > 0) {
      debouncedSync();
    }
  }, [orderItems, customerInfo, sessionUuid]);

  const performSearch = useCallback(
    debounce(async (query: string, filters: SearchFilters) => {
      if (query.trim() === '' && Object.keys(filters).length === 0) {
        setSearchResults([]);
        setIsSearching(false);
        return;
      }

      if (searchAbortController.current) {
        searchAbortController.current.abort();
      }

      searchAbortController.current = new AbortController();
      setIsSearching(true);

      try {
        const response = await axios.get('/api/items/search', {
          params: {
            q: query,
            ...filters,
          },
          signal: searchAbortController.current.signal,
        });

        if (response.data.success) {
          setSearchResults(response.data.data.items || []);
        }
      } catch (error: any) {
        if (error.name !== 'AbortError') {
          console.error('Search failed:', error);
          setSearchResults([]);
        }
      } finally {
        setIsSearching(false);
      }
    }, 300),
    []
  );

  useEffect(() => {
    if (searchQuery || Object.keys(searchFilters).length > 0) {
      performSearch(searchQuery, searchFilters);
    } else {
      setSearchResults([]);
    }
  }, [searchQuery, searchFilters]);

  const addItemToOrder = (item: SearchResult) => {
    setOrderItems(prev => {
      const existing = prev.find(i => i.id === item.id);
      if (existing) {
        return prev.map(i => 
          i.id === item.id 
            ? { ...i, quantity: i.quantity + 1 }
            : i
        );
      }
      return [...prev, { ...item, quantity: 1, notes: '' }];
    });
    
    setRecentItems(prev => {
      const filtered = prev.filter(i => i.id !== item.id);
      return [item, ...filtered].slice(0, 10);
    });
  };

  const removeItemFromOrder = (itemId: number) => {
    setOrderItems(prev => prev.filter(i => i.id !== itemId));
  };

  const updateItemQuantity = (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeItemFromOrder(itemId);
    } else {
      setOrderItems(prev => 
        prev.map(i => i.id === itemId ? { ...i, quantity } : i)
      );
    }
  };

  const updateItemNotes = (itemId: number, notes: string) => {
    setOrderItems(prev => 
      prev.map(i => i.id === itemId ? { ...i, notes } : i)
    );
  };

  const toggleFavorite = (item: SearchResult) => {
    setFavoriteItems(prev => {
      const isFavorite = prev.some(i => i.id === item.id);
      if (isFavorite) {
        return prev.filter(i => i.id !== item.id);
      }
      return [...prev, item];
    });
  };

  const addToRecentSearches = (query: string) => {
    if (query.trim()) {
      setRecentSearches(prev => {
        const filtered = prev.filter(q => q !== query);
        return [query, ...filtered].slice(0, 5);
      });
    }
  };

  const processOrder = async () => {
    if (!sessionUuid) return;
    
    setSessionStatus('processing');
    
    try {
      const response = await axios.post(`/api/v1/orders/session/${sessionUuid}/checkout`, {
        items: orderItems,
        customer_info: customerInfo,
      });

      if (response.data.success) {
        setOrderUuid(response.data.data.order_uuid);
        setSessionStatus('completed');
        
        router.visit(`/orders/${response.data.data.order_uuid}`);
      }
    } catch (error) {
      console.error('Failed to process order:', error);
      setSessionStatus('active');
    }
  };

  const handleCategorySelect = (category: string) => {
    updateSearchFilter('category', category);
    setIsSearchMode(true);
  };

  const updateSearchFilter = (key: string, value: string | undefined) => {
    setSearchFilters(prev => ({
      ...prev,
      [key]: value,
    }));
  };

  const clearSearchFilters = () => {
    setSearchFilters({});
  };

  const getTotalItems = () => {
    return orderItems.reduce((sum, item) => sum + item.quantity, 0);
  };

  const calculateSubtotal = () => {
    return orderItems.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  };

  const calculateTax = () => {
    return calculateSubtotal() * 0.19;
  };

  const calculateTotal = () => {
    return calculateSubtotal() + calculateTax();
  };

  const saveDraftOrder = () => {
    syncWithBackend();
  };

  useEffect(() => {
    return () => {
      if (searchAbortController.current) {
        searchAbortController.current.abort();
      }
      if (syncTimer.current) {
        clearTimeout(syncTimer.current);
      }
    };
  }, []);

  const value: OrderContextType = {
    orderItems,
    customerInfo,
    searchQuery,
    searchResults,
    isSearching,
    isSearchMode,
    favoriteItems,
    recentSearches,
    recentItems,
    popularItems,
    searchFilters,
    activeFiltersCount,
    sessionStatus,
    lastSavedAt,
    orderUuid,
    sessionUuid,
    setCustomerInfo,
    setSearchQuery,
    setIsSearchMode,
    addItemToOrder,
    removeItemFromOrder,
    updateItemQuantity,
    updateItemNotes,
    toggleFavorite,
    addToRecentSearches,
    processOrder,
    handleCategorySelect,
    updateSearchFilter,
    clearSearchFilters,
    getTotalItems,
    calculateSubtotal,
    calculateTax,
    calculateTotal,
    saveDraftOrder,
  };

  return <OrderContext.Provider value={value}>{children}</OrderContext.Provider>;
};

export type { SearchResult, OrderItem, CustomerInfo };