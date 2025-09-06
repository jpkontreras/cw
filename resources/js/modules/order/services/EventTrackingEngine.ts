import { v4 as uuid } from 'uuid';

export interface TrackedEvent {
  id: string;
  type: EventType;
  sessionId: string;
  timestamp: number;
  data: Record<string, any>;
  retries: number;
  status: 'pending' | 'sending' | 'sent' | 'failed';
}

export enum EventType {
  // Critical Business Events
  SESSION_STARTED = 'session_started',
  SESSION_HYDRATE = 'session_hydrate', // Request full state from server
  ITEM_ADDED = 'item_added',
  ITEM_REMOVED = 'item_removed',
  ITEM_MODIFIED = 'item_modified',
  ORDER_CONFIRMED = 'order_confirmed',
  ORDER_CANCELLED = 'order_cancelled',
  
  // User Intent Signals
  SERVING_TYPE_CHANGED = 'serving_type_changed',
  CUSTOMER_INFO_PROVIDED = 'customer_info_provided',
  PAYMENT_METHOD_SELECTED = 'payment_method_selected',
  DELIVERY_ADDRESS_SET = 'delivery_address_set',
  
  // Navigation & Discovery
  CATEGORY_SELECTED = 'category_selected',
  SEARCH_PERFORMED = 'search_performed',
  ITEM_VIEWED = 'item_viewed',
  
  // Abandonment & Recovery
  SESSION_ABANDONED = 'session_abandoned',
  SESSION_RECOVERED = 'session_recovered',
  DRAFT_SAVED = 'draft_saved',
}

interface EventBatch {
  sessionId: string;
  sessionToken: string;
  events: Array<{
    id: string;
    type: EventType;
    timestamp: number;
    data: Record<string, any>;
  }>;
}

interface EngineConfig {
  endpoint: string;
  sessionId: string;
  sessionToken: string;
  batchInterval: number;
  maxRetries: number;
  maxBatchSize: number;
  debounceDelay: number;
  onError?: (error: Error) => void;
  onStateUpdate?: (state: any) => void; // Callback when server sends state updates
}

export class EventTrackingEngine {
  private queue: TrackedEvent[] = [];
  private config: EngineConfig;
  private syncTimer: NodeJS.Timeout | null = null;
  private debounceTimers: Map<string, NodeJS.Timeout> = new Map();
  private isOnline: boolean = navigator.onLine;
  private isSyncing: boolean = false;
  private storageKey: string;

  constructor(config: Partial<EngineConfig>) {
    this.config = {
      endpoint: '/orders/session/events/batch',
      sessionId: '',
      sessionToken: '',
      batchInterval: 5000, // 5 seconds
      maxRetries: 3,
      maxBatchSize: 20,
      debounceDelay: 500, // 500ms for quantity changes
      ...config,
    };
    
    // Update endpoint to use single sync endpoint
    if (this.config.sessionId) {
      this.config.endpoint = `/orders/session/${this.config.sessionId}/sync`;
    }
    
    this.storageKey = `event_queue_${this.config.sessionId}`;
    
    // Setup event listeners
    this.setupEventListeners();
    
    // Load any pending events from storage
    this.loadQueueFromStorage();
    
    // Start sync timer
    this.startSyncTimer();
  }

  private setupEventListeners(): void {
    // Listen for online/offline
    window.addEventListener('online', () => {
      this.isOnline = true;
      this.syncEvents();
    });
    
    window.addEventListener('offline', () => {
      this.isOnline = false;
    });
    
    // Sync before page unload
    window.addEventListener('beforeunload', () => {
      this.syncEvents(); // Try to sync before leaving
    });
    
    // Save queue to storage on visibility change
    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.saveQueueToStorage();
      }
    });
  }

  /**
   * Track an event - handles deduplication and batching
   */
  public track(type: EventType, data: Record<string, any>): void {
    console.log(`[EventTracker] Tracking event: ${type}`, data);
    
    // For quantity changes, debounce
    if (type === EventType.ITEM_MODIFIED) {
      console.log('[EventTracker] Debouncing ITEM_MODIFIED event');
      this.debounceEvent(type, data);
      return;
    }
    
    // For critical events, send immediately
    if (this.isCriticalEvent(type)) {
      console.log('[EventTracker] Critical event, sending immediately');
      this.addEventAndSync(type, data, true);
      return;
    }
    
    // For normal events, add to queue
    console.log('[EventTracker] Adding to queue for batch sync');
    this.addEventAndSync(type, data, false);
  }

  private debounceEvent(type: EventType, data: Record<string, any>): void {
    const key = `${type}_${data.itemId || 'global'}`;
    
    // Clear existing timer
    if (this.debounceTimers.has(key)) {
      clearTimeout(this.debounceTimers.get(key)!);
    }
    
    // Set new timer
    const timer = setTimeout(() => {
      this.addEventAndSync(type, data, false);
      this.debounceTimers.delete(key);
    }, this.config.debounceDelay);
    
    this.debounceTimers.set(key, timer);
  }

  private addEventAndSync(type: EventType, data: Record<string, any>, immediate: boolean): void {
    const event: TrackedEvent = {
      id: uuid(),
      type,
      sessionId: this.config.sessionId,
      timestamp: Date.now(),
      data: this.sanitizeData(data), // Remove sensitive data
      retries: 0,
      status: 'pending',
    };
    
    // Check for duplicates
    const isDuplicate = this.queue.some(
      e => e.type === type && 
      JSON.stringify(e.data) === JSON.stringify(event.data) &&
      Math.abs(e.timestamp - event.timestamp) < 1000 // Within 1 second
    );
    
    if (!isDuplicate) {
      this.queue.push(event);
      this.saveQueueToStorage();
      
      if (immediate) {
        this.syncEvents();
      }
    }
  }

  /**
   * Sanitize data to prevent tampering
   * Never send prices, totals, or sensitive data
   */
  private sanitizeData(data: Record<string, any>): Record<string, any> {
    const sanitized = { ...data };
    
    // Remove sensitive fields
    const sensitiveFields = ['price', 'total', 'subtotal', 'discount', 'tax', 'cost'];
    sensitiveFields.forEach(field => {
      delete sanitized[field];
    });
    
    // Only keep IDs and quantities for items
    if (sanitized.itemId) {
      const allowedFields = [
        'itemId', 'quantity', 'modifiers', 'notes', 
        'previousQuantity', 'newQuantity', 'source'
      ];
      Object.keys(sanitized).forEach(key => {
        if (!allowedFields.includes(key)) {
          delete sanitized[key];
        }
      });
    }
    
    return sanitized;
  }

  private isCriticalEvent(type: EventType): boolean {
    return [
      EventType.ORDER_CONFIRMED,
      EventType.ORDER_CANCELLED,
      EventType.SESSION_ABANDONED,
    ].includes(type);
  }

  private startSyncTimer(): void {
    this.syncTimer = setInterval(() => {
      if (this.queue.filter(e => e.status === 'pending').length > 0) {
        this.syncEvents();
      }
    }, this.config.batchInterval);
  }

  /**
   * Sync events to server with retry logic
   */
  private async syncEvents(): Promise<void> {
    if (!this.isOnline || this.isSyncing) {
      console.log('[EventTracker] Skip sync - offline or already syncing');
      return;
    }
    
    const pendingEvents = this.queue.filter(e => e.status === 'pending');
    if (pendingEvents.length === 0) {
      console.log('[EventTracker] No pending events to sync');
      return;
    }
    
    console.log(`[EventTracker] Syncing ${pendingEvents.length} events to server`);
    this.isSyncing = true;
    
    try {
      // Take batch of events
      const batch = pendingEvents.slice(0, this.config.maxBatchSize);
      
      // Mark as sending
      batch.forEach(e => e.status = 'sending');
      
      // Prepare payload for sync endpoint
      const payload = {
        events: batch.map(e => ({
          id: e.id,
          type: e.type,
          timestamp: e.timestamp,
          data: e.data, // Already sanitized, only contains IDs and quantities
        })),
      };
      
      // Get CSRF token from page
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      if (!csrfToken) {
        console.error('[EventTracker] No CSRF token found');
        await this.handleSyncFailure(batch, new Error('No CSRF token'));
        return;
      }
      
      // Send using fetch API
      const response = await fetch(this.config.endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Session-Token': this.config.sessionToken,
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });
      
      if (response.ok) {
        const result = await response.json();
        
        // Mark processed events as sent and remove from queue
        if (result.processed && Array.isArray(result.processed)) {
          batch.forEach(e => {
            if (result.processed.includes(e.id)) {
              e.status = 'sent';
              const index = this.queue.indexOf(e);
              if (index > -1) {
                this.queue.splice(index, 1);
              }
            }
          });
        }
        
        // Handle any errors for specific events
        if (result.errors && result.errors.length > 0) {
          console.warn('[EventTracker] Some events had errors:', result.errors);
        }
        
        // Notify about state changes (prices from server, etc.)
        if (result.state && this.config.onStateUpdate) {
          this.config.onStateUpdate(result.state);
        }
        
        this.saveQueueToStorage();
        console.log(`[EventTracker] Successfully synced ${result.processed?.length || 0} events`);
      } else {
        await this.handleSyncFailure(batch, new Error(`HTTP ${response.status}`));
      }
    } catch (error) {
      // Handle network error
      console.error('[EventTracker] Sync failed:', error);
      const pendingBatch = this.queue.filter(e => e.status === 'sending');
      await this.handleSyncFailure(pendingBatch, error as Error);
    } finally {
      this.isSyncing = false;
    }
  }

  private async handleSyncFailure(batch: TrackedEvent[], error: Error): Promise<void> {
    batch.forEach(event => {
      event.status = 'pending';
      event.retries++;
      
      // Remove events that exceeded max retries
      if (event.retries >= this.config.maxRetries) {
        event.status = 'failed';
        const index = this.queue.indexOf(event);
        if (index > -1) {
          this.queue.splice(index, 1);
        }
        
        // Log failed event
        console.error(`Event ${event.id} failed after ${this.config.maxRetries} retries`, event);
      }
    });
    
    this.saveQueueToStorage();
    
    // Call error handler if provided
    if (this.config.onError) {
      this.config.onError(error);
    }
    
    // Exponential backoff for next retry
    const nextRetryDelay = Math.min(
      this.config.batchInterval * Math.pow(2, batch[0]?.retries || 1),
      60000 // Max 1 minute
    );
    
    setTimeout(() => this.syncEvents(), nextRetryDelay);
  }

  private saveQueueToStorage(): void {
    try {
      // Only save minimal data needed for recovery
      const minimalQueue = this.queue
        .filter(e => e.status === 'pending')
        .map(e => ({
          id: e.id,
          type: e.type,
          timestamp: e.timestamp,
          data: e.data,
          retries: e.retries,
        }));
      
      localStorage.setItem(this.storageKey, JSON.stringify(minimalQueue));
    } catch (error) {
      console.error('Failed to save queue to storage', error);
    }
  }

  private loadQueueFromStorage(): void {
    try {
      const stored = localStorage.getItem(this.storageKey);
      if (stored) {
        const minimalQueue = JSON.parse(stored);
        this.queue = minimalQueue.map((e: any) => ({
          ...e,
          sessionId: this.config.sessionId,
          status: 'pending' as const,
        }));
      }
    } catch (error) {
      console.error('Failed to load queue from storage', error);
      localStorage.removeItem(this.storageKey);
    }
  }

  /**
   * Force sync all pending events
   */
  public async flush(): Promise<void> {
    await this.syncEvents();
  }

  /**
   * Clear all pending events
   */
  public clear(): void {
    this.queue = [];
    localStorage.removeItem(this.storageKey);
  }

  /**
   * Destroy the engine and cleanup
   */
  public destroy(): void {
    if (this.syncTimer) {
      clearInterval(this.syncTimer);
    }
    
    this.debounceTimers.forEach(timer => clearTimeout(timer));
    this.debounceTimers.clear();
    
    // Final sync attempt
    this.syncEvents();
  }

  /**
   * Get current queue size
   */
  public getQueueSize(): number {
    return this.queue.filter(e => e.status === 'pending').length;
  }

  /**
   * Update session token (for re-authentication)
   */
  public updateSessionToken(token: string): void {
    this.config.sessionToken = token;
  }
}