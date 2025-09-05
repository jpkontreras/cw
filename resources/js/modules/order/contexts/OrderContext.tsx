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
  orderUuid: string | null;
  sessionStatus: 'initializing' | 'active' | 'recovered' | 'error' | 'idle';
  lastSavedAt: Date | null;
  
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
  initializeOrderSession: (orderType?: 'dine_in' | 'takeout' | 'delivery') => Promise<{ success: boolean; uuid?: string; error?: string }>;
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
  trackEvent: (eventType: string, data: any) => Promise<void>;
  saveDraftOrder: (autoSaved?: boolean) => Promise<void>;
  
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
  initialSessionUuid?: string;
}

export const OrderProvider: React.FC<OrderProviderProps> = ({ 
  children, 
  initialPopularItems = [],
  initialSessionUuid
}) => {
  // State Management
  const [orderUuid, setOrderUuid] = useState<string | null>(initialSessionUuid || null);
  const [sessionStatus, setSessionStatus] = useState<'initializing' | 'active' | 'recovered' | 'error' | 'idle'>(
    initialSessionUuid ? 'active' : 'idle'
  );
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
  const [lastSavedAt, setLastSavedAt] = useState<Date | null>(null);
  
  // Refs
  const searchDebounceRef = useRef<NodeJS.Timeout | null>(null);
  const searchIdRef = useRef<string | null>(null);
  const autoSaveIntervalRef = useRef<NodeJS.Timeout | null>(null);
  const sessionActivityRef = useRef<NodeJS.Timeout | null>(null);
  
  // Check for existing session on mount (but don't create new one)
  useEffect(() => {
    // Only try to recover if we don't already have a session UUID
    if (!initialSessionUuid) {
      checkAndRecoverExistingSession();
    }
    
    // Cleanup on unmount
    return () => {
      if (autoSaveIntervalRef.current) {
        clearInterval(autoSaveIntervalRef.current);
      }
      if (sessionActivityRef.current) {
        clearTimeout(sessionActivityRef.current);
      }
    };
  }, []);
  
  // Set up auto-save when session becomes active
  useEffect(() => {
    if (orderUuid && (sessionStatus === 'active' || sessionStatus === 'recovered')) {
      // Clear any existing interval
      if (autoSaveIntervalRef.current) {
        clearInterval(autoSaveIntervalRef.current);
      }
      
      // Set up auto-save every 30 seconds
      autoSaveIntervalRef.current = setInterval(() => {
        if (orderItems.length > 0) {
          saveDraftOrder(true);
        }
      }, 30000);
    }
    
    return () => {
      if (autoSaveIntervalRef.current) {
        clearInterval(autoSaveIntervalRef.current);
      }
    };
  }, [orderUuid, sessionStatus]);
  
  // Check for existing session in localStorage
  const checkExistingSession = async (): Promise<string | null> => {
    const storedSession = localStorage.getItem('order_session');
    if (!storedSession) return null;
    
    try {
      const session = JSON.parse(storedSession);
      const sessionAge = Date.now() - new Date(session.timestamp).getTime();
      
      // Session older than 2 hours, ignore it
      if (sessionAge > 2 * 60 * 60 * 1000) {
        localStorage.removeItem('order_session');
        return null;
      }
      
      // Try to recover the session
      const response = await axios.get(`/orders/session/${session.uuid}/state`);
      if (response.data.success && !response.data.data.is_expired) {
        return session.uuid;
      }
    } catch (error) {
      console.error('Failed to recover session:', error);
      localStorage.removeItem('order_session');
    }
    
    return null;
  };
  
  // Check and recover existing session (called on mount)
  const checkAndRecoverExistingSession = async () => {
    try {
      const existingUuid = await checkExistingSession();
      
      if (existingUuid) {
        // Try to recover existing session
        const response = await axios.post(`/orders/session/${existingUuid}/recover`);
        if (response.data.success) {
          setOrderUuid(existingUuid);
          setSessionStatus('recovered');
          
          // Restore cart items and customer info
          const sessionData = response.data.data;
          if (sessionData.cart_items) {
            setOrderItems(sessionData.cart_items.map((item: any) => ({
              id: item.itemId,
              name: item.itemName,
              price: item.unitPrice,
              quantity: item.quantity,
              category: item.category,
              modifiers: item.modifiers,
              notes: item.notes,
            })));
          }
          
          if (sessionData.customer_info) {
            setCustomerInfo(prev => ({ ...prev, ...sessionData.customer_info }));
          }
          
          if (sessionData.serving_type) {
            setCustomerInfo(prev => ({ ...prev, orderType: sessionData.serving_type }));
          }
        } else {
          // Session expired or invalid, clean up
          localStorage.removeItem('order_session');
        }
      }
    } catch (error) {
      console.error('Failed to recover session:', error);
      localStorage.removeItem('order_session');
    }
  };
  
  // Initialize NEW order session (called explicitly by user action)
  const initializeOrderSession = async (orderType?: 'dine_in' | 'takeout' | 'delivery') => {
    try {
      // Don't create duplicate sessions
      if (orderUuid && (sessionStatus === 'active' || sessionStatus === 'recovered')) {
        return { success: true, uuid: orderUuid };
      }
      
      // Create new session using web route
      const response = await axios.post('/orders/session/start', {
        location_id: 1, // TODO: Get from user context
        platform: 'web',
        source: 'web',
        order_type: orderType,
      });
      
      if (response.data.success) {
        const uuid = response.data.data.uuid || response.data.data.order_uuid;
        setOrderUuid(uuid);
        setSessionStatus('active');
        
        // Store in localStorage
        localStorage.setItem('order_session', JSON.stringify({
          uuid: uuid,
          timestamp: new Date().toISOString(),
        }));
        
        // If order type was provided, track it
        if (orderType) {
          setCustomerInfo(prev => ({ ...prev, orderType }));
          await trackEvent('serving_type', {
            type: orderType,
          });
        }
        
        return { success: true, uuid };
      }
      
      return { success: false, error: 'Failed to create session' };
    } catch (error) {
      console.error('Failed to initialize order session:', error);
      setSessionStatus('error');
      return { success: false, error: error.message };
    }
  };
  
  // Track event to backend
  const trackEvent = useCallback(async (eventType: string, data: any) => {
    if (!orderUuid) return;
    
    try {
      await axios.post(`/orders/session/${orderUuid}/track`, {
        event_type: eventType,
        data: data,
      });
    } catch (error) {
      console.error(`Failed to track ${eventType} event:`, error);
    }
  }, [orderUuid]);
  
  // Save draft order
  const saveDraftOrder = useCallback(async (autoSaved: boolean = false) => {
    if (!orderUuid) return;
    
    try {
      await axios.post(`/orders/session/${orderUuid}/save-draft`, {
        auto_saved: autoSaved,
      });
      setLastSavedAt(new Date());
    } catch (error) {
      console.error('Failed to save draft:', error);
    }
  }, [orderUuid]);
  
  // Calculate active filters count
  const activeFiltersCount = Object.values(searchFilters).filter(value => 
    value !== undefined && value !== null && value !== ''
  ).length;

  // Load favorites and recent searches from backend (only when we have a session)
  useEffect(() => {
    // Only load search data when we have an active session
    if (!orderUuid) return;
    
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
  }, [orderUuid]);

  // Load popular items from server (only when we have a session)
  useEffect(() => {
    if (!orderUuid) return; // Only load when we have a session
    
    if (popularItems.length === 0) {
      axios.get('/items/search/popular', { params: { limit: 12 } })
        .then(response => {
          setPopularItems(response.data.items || response.data.data || []);
        })
        .catch(error => {
          console.error('Error loading popular items:', error);
        });
    }
  }, [popularItems.length, orderUuid]);

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
          
          // Track search event
          if (orderUuid && query.trim()) {
            trackEvent('search', {
              query: query,
              filters: filters,
              results_count: items.length,
              search_id: searchIdRef.current,
            });
          }
        }
      } catch (error) {
        console.error('Search error:', error);
        setSearchResults([]);
      } finally {
        setIsSearching(false);
      }
    }, 300);
  }, [favoriteItems, orderUuid, trackEvent]);

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
  const addItemToOrder = async (item: SearchResult) => {
    recordSearchSelection(item);
    
    // Add to recent items (optimistic update)
    setRecentItems(prev => {
      const filtered = prev.filter(i => i.id !== item.id);
      return [item, ...filtered].slice(0, 10);
    });
    
    // Update local state
    const existingItem = orderItems.find(orderItem => orderItem.id === item.id);
    const newQuantity = existingItem ? existingItem.quantity + 1 : 1;
    
    setOrderItems(prevItems => {
      if (existingItem) {
        return prevItems.map(orderItem =>
          orderItem.id === item.id
            ? { ...orderItem, quantity: orderItem.quantity + 1 }
            : orderItem
        );
      }
      
      return [...prevItems, { ...item, quantity: 1 }];
    });
    
    // Track to backend if session is active
    if (orderUuid && (sessionStatus === 'active' || sessionStatus === 'recovered')) {
      try {
        await axios.post(`/orders/session/${orderUuid}/cart/add`, {
          item_id: item.id,
          item_name: item.name,
          quantity: newQuantity === 1 ? 1 : 1, // Always add 1 at a time
          unit_price: item.price,
          category: item.category,
          source: isSearchMode ? 'search' : 'browse',
        });
      } catch (error) {
        console.error('Failed to track cart addition:', error);
      }
    }
    
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

  // Process Order (convert session to confirmed order)
  const processOrder = async () => {
    // If we have a session, convert it to an order
    if (orderUuid && (sessionStatus === 'active' || sessionStatus === 'recovered')) {
      try {
        // Track customer info and payment method before conversion
        await trackEvent('customer_info', {
          fields: {
            name: customerInfo.name,
            phone: customerInfo.phone,
            orderType: customerInfo.orderType,
            tableNumber: customerInfo.tableNumber,
            address: customerInfo.address,
          },
          is_complete: true,
        });
        
        if (customerInfo.paymentMethod) {
          await trackEvent('payment_method', {
            payment_method: customerInfo.paymentMethod,
          });
        }
        
        // Convert session to order
        const response = await axios.post(`/orders/session/${orderUuid}/convert`, {
          payment_method: customerInfo.paymentMethod || 'cash',
        });
        
        if (response.data.success) {
          // Clear local storage
          localStorage.removeItem('order_session');
          
          // Navigate to success page or order details
          router.visit(`/orders/${orderUuid}`);
          return;
        }
      } catch (error) {
        console.error('Failed to convert session to order:', error);
        // Fall back to legacy flow
      }
    }
    
    // Legacy flow if session conversion fails or not available
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
        name: item.name, // Include item name
        unitPrice: item.price * 100, // Convert to cents (backend expects minor units)
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
    orderUuid,
    sessionStatus,
    lastSavedAt,
    
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
    initializeOrderSession,
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
    trackEvent,
    saveDraftOrder,
    
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