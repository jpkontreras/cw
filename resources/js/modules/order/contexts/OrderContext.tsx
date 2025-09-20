import React, { createContext, useContext, useState, useRef, useCallback, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'sonner';
import { EventTrackingEngine, EventType } from '../services/EventTrackingEngine';

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
  const [sessionLocation, setSessionLocation] = useState<any>(null); // Locked location from session
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
  const trackingEngineRef = useRef<EventTrackingEngine | null>(null);
  const [sessionToken, setSessionToken] = useState<string | null>(null);
  
  // Check for existing session on mount (but don't create new one)
  useEffect(() => {
    // If we have an initial session UUID, initialize tracking engine for it
    if (initialSessionUuid) {
      console.log('[OrderContext] Initializing tracking engine for existing session:', initialSessionUuid);
      const token = `${initialSessionUuid}-${Date.now()}`;
      setSessionToken(token);
      
      trackingEngineRef.current = new EventTrackingEngine({
        sessionId: initialSessionUuid,
        sessionToken: token,
        endpoint: '/orders/session/sync',
        onError: (error) => {
          console.error('Tracking error:', error);
        },
        onStateUpdate: (state) => {
          // Update local state with server-authoritative data
          console.log('[OrderContext] State update from server:', state);
          
          // Update cart items with server prices
          if (state.cart_items) {
            setOrderItems(state.cart_items.map((item: any) => ({
              id: item.id,
              name: item.name,
              price: item.price, // Server-provided price
              quantity: item.quantity,
              category: item.category,
              available: item.available,
            })));
          }
        }
      });
      console.log('[OrderContext] Tracking engine initialized for existing session');
      
      // Hydrate the session - get current state from server
      console.log('[OrderContext] Hydrating session from server');
      trackingEngineRef.current.track(EventType.SESSION_HYDRATE, {
        source: 'page_load',
        timestamp: Date.now()
      });
      trackingEngineRef.current.flush(); // Force immediate sync to get state
    } else {
      // Try to recover existing session from localStorage
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
      if (trackingEngineRef.current) {
        trackingEngineRef.current.destroy();
        trackingEngineRef.current = null;
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
          // Restore locked location from recovered session
          if (response.data.location) {
            setSessionLocation(response.data.location);
          }
          
          // Initialize tracking engine for recovered session
          const token = response.data.data.session_token || `${existingUuid}-${Date.now()}`;
          setSessionToken(token);
          
          if (trackingEngineRef.current) {
            trackingEngineRef.current.destroy();
          }
          
          trackingEngineRef.current = new EventTrackingEngine({
            sessionId: existingUuid,
            sessionToken: token,
            endpoint: '/orders/session/sync',
            onError: (error) => {
              console.error('Tracking error:', error);
            },
            onStateUpdate: (state) => {
              // Update local state with server-authoritative data
              console.log('[OrderContext] State update from server:', state);
              
              // Update cart items with server prices
              if (state.cart_items) {
                setOrderItems(state.cart_items.map((item: any) => ({
                  id: item.id,
                  name: item.name,
                  price: item.price, // Server-provided price
                  quantity: item.quantity,
                  category: item.category,
                  available: item.available,
                })));
              }
            }
          });
          
          // Track session recovery
          trackingEngineRef.current.track(EventType.SESSION_RECOVERED, {
            sessionAge: Date.now() - new Date(response.data.data.started_at).getTime(),
            itemsCount: response.data.data.cart_items?.length || 0
          });
          
          // Hydrate to get latest state from server
          console.log('[OrderContext] Hydrating recovered session');
          trackingEngineRef.current.track(EventType.SESSION_HYDRATE, {
            source: 'session_recovery',
            timestamp: Date.now()
          });
          trackingEngineRef.current.flush();
          
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
      
      // Create new session using web route - location will be locked server-side
      const response = await axios.post('/orders/session/start', {
        platform: 'web',
        source: 'web',
        order_type: orderType,
        // Location is determined server-side from user's current location
      });
      
      if (response.data.success) {
        const uuid = response.data.data.uuid || response.data.data.order_uuid;
        const token = response.data.data.session_token || `${uuid}-${Date.now()}`;
        
        setOrderUuid(uuid);
        setSessionToken(token);
        setSessionStatus('active');
        
        // Initialize tracking engine
        if (trackingEngineRef.current) {
          trackingEngineRef.current.destroy();
        }
        
        console.log('[OrderContext] Initializing EventTrackingEngine for session:', uuid);
        trackingEngineRef.current = new EventTrackingEngine({
          sessionId: uuid,
          sessionToken: token,
          endpoint: '/orders/session/sync',
          onError: (error) => {
            console.error('Tracking error:', error);
          },
          onStateUpdate: (state) => {
            // Update local state with server-authoritative data
            console.log('[OrderContext] State update from server:', state);
            
            // Update cart items with server prices
            if (state.cart_items) {
              setOrderItems(state.cart_items.map((item: any) => ({
                id: item.id,
                name: item.name,
                price: item.price, // Server-provided price
                quantity: item.quantity,
                category: item.category,
                available: item.available,
              })));
            }
            
            // Update session status if needed
            if (state.status) {
              setSessionStatus(state.status === 'active' ? 'active' : 'error');
            }
          }
        });
        console.log('[OrderContext] EventTrackingEngine initialized');
        
        // Track session started - location is already locked in session
        trackingEngineRef.current.track(EventType.SESSION_STARTED, {
          orderType: orderType || 'unspecified',
          locationId: response.data.data.location?.id || null, // Use session's locked location
          locationName: response.data.data.location?.name || null,
          platform: 'web'
        });
        
        // Store the locked location from session
        if (response.data.data.location) {
          setSessionLocation(response.data.data.location);
        }
        
        // Store in localStorage
        localStorage.setItem('order_session', JSON.stringify({
          uuid: uuid,
          timestamp: new Date().toISOString(),
        }));
        
        // If order type was provided, track it
        if (orderType) {
          setCustomerInfo(prev => ({ ...prev, orderType }));
          trackingEngineRef.current.track(EventType.SERVING_TYPE_CHANGED, {
            type: orderType,
            previous: null
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
  
  // Track event using the tracking engine
  const trackEvent = useCallback(async (eventType: string, data: any) => {
    if (!trackingEngineRef.current) {
      console.warn('Tracking engine not initialized');
      return;
    }
    
    // Map string event types to EventType enum
    const eventTypeMap: Record<string, EventType> = {
      'search': EventType.SEARCH_PERFORMED,
      'serving_type': EventType.SERVING_TYPE_CHANGED,
      'customer_info': EventType.CUSTOMER_INFO_PROVIDED,
      'payment_method': EventType.PAYMENT_METHOD_SELECTED,
      'category_browse': EventType.CATEGORY_SELECTED,
      'item_view': EventType.ITEM_VIEWED,
      'draft_save': EventType.DRAFT_SAVED,
    };
    
    const mappedType = eventTypeMap[eventType];
    if (mappedType) {
      trackingEngineRef.current.track(mappedType, data);
    } else {
      console.warn(`Unknown event type: ${eventType}`);
    }
  }, []);
  
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
          if (response.data.success && response.data.data) {
            setPopularItems(response.data.data.items || []);
          } else {
            setPopularItems(response.data.items || []);
          }
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
    
    // ALL operations go through tracking engine - no direct API calls!
    if (trackingEngineRef.current) {
      console.log('[OrderContext] Tracking engine exists, tracking event');
      // Track as item_added for first item, item_modified for subsequent
      if (existingItem) {
        console.log('[OrderContext] Tracking ITEM_MODIFIED for existing item');
        trackingEngineRef.current.track(EventType.ITEM_MODIFIED, {
          itemId: item.id,
          previousQuantity: existingItem.quantity,
          newQuantity: existingItem.quantity + 1,
          source: isSearchMode ? 'search' : 'browse',
        });
      } else {
        console.log('[OrderContext] Tracking ITEM_ADDED for new item');
        trackingEngineRef.current.track(EventType.ITEM_ADDED, {
          itemId: item.id,
          quantity: 1,
          source: isSearchMode ? 'search' : 'browse',
          // NEVER send price from client - server will look it up
        });
      }
      
      // Force immediate sync for better UX
      trackingEngineRef.current.flush();
    } else {
      console.log('[OrderContext] No tracking engine available!');
    }
    
    setIsSearchMode(false);
    setSearchQuery('');
    setSearchResults([]);
  };

  const removeItemFromOrder = (itemId: number) => {
    const item = orderItems.find(i => i.id === itemId);
    if (item && trackingEngineRef.current) {
      trackingEngineRef.current.track(EventType.ITEM_REMOVED, {
        itemId: itemId,
        quantity: item.quantity,
        reason: 'user_removed'
      });
    }
    setOrderItems(prevItems => prevItems.filter(item => item.id !== itemId));
  };

  const updateItemQuantity = (itemId: number, quantity: number) => {
    if (quantity <= 0) {
      removeItemFromOrder(itemId);
      return;
    }
    
    const item = orderItems.find(i => i.id === itemId);
    if (item && trackingEngineRef.current) {
      trackingEngineRef.current.track(EventType.ITEM_MODIFIED, {
        itemId: itemId,
        previousQuantity: item.quantity,
        newQuantity: quantity,
        source: 'manual_adjustment'
      });
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
    // Orders MUST go through session conversion - no fallback
    if (!orderUuid || (sessionStatus !== 'active' && sessionStatus !== 'recovered')) {
      console.error('No active order session. Cannot process order.');
      toast.error('No active order session. Please start a new order.');
      return;
    }

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
      
      // Convert session to order (the ONLY way to create an order)
      const response = await axios.post(`/orders/session/${orderUuid}/convert`, {
        payment_method: customerInfo.paymentMethod || 'cash',
        customer_name: customerInfo.name,
        customer_phone: customerInfo.phone,
        notes: customerInfo.notes,
      });
      
      if (response.data.success) {
        // Clear local storage
        localStorage.removeItem('order_session');
        
        // Show success message
        toast.success('Order created successfully');
        
        // Navigate to order details using the order_id from response (which is the UUID)
        const orderId = response.data.order_id;
        console.log('Order conversion successful, redirecting to:', `/orders/${orderId}`);
        console.log('Full API response:', response.data);
        
        // Use Inertia router for navigation
        router.visit(`/orders/${orderId}`);
      } else {
        throw new Error(response.data.error || 'Failed to convert session to order');
      }
    } catch (error: any) {
      console.error('Failed to convert session to order:', error);
      
      // Show user-friendly error message
      const errorMessage = error.response?.data?.error || error.message || 'Failed to process order. Please try again.';
      toast.error(errorMessage);
      
      // If session is not found or expired, redirect to start new order
      if (error.response?.status === 404 || errorMessage.includes('not found')) {
        router.visit('/orders/new');
      }
    }
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
    sessionLocation, // Expose locked location
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