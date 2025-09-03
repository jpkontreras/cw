import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { EmptyOrderState, OrderItemsView, SearchInput, SearchView, CartPopover, FilterBar } from '@/modules/order';
import { OrderProvider, useOrder, type SearchResult } from '@/modules/order/contexts/OrderContext';
import { ArrowLeft, ArrowRight, ShoppingBag } from 'lucide-react';
import React, { useState, useRef, useEffect } from 'react';

interface CreateOrderProps {
  popularItems?: SearchResult[];
}

const CreateOrderContent: React.FC = () => {
  const {
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
  } = useOrder();

  const [currentStep, setCurrentStep] = useState(0);
  const [addedItemFeedback, setAddedItemFeedback] = useState<{ id: number; name: string } | null>(null);

  // Handle adding item with feedback
  const handleAddItemWithFeedback = (item: SearchResult) => {
    addItemToOrder(item);
    setAddedItemFeedback({ id: item.id, name: item.name });
    
    // Clear feedback after animation
    setTimeout(() => {
      setAddedItemFeedback(null);
    }, 3000);
  };

  const handleGoToCheckout = () => {
    setCurrentStep(1);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleBackToProducts = () => {
    setCurrentStep(0);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const handleFinish = () => {
    processOrder();
  };

  const scrollContainerRef = useRef<HTMLDivElement>(null);

  // Ensure proper scrolling on mobile devices
  useEffect(() => {
    const container = scrollContainerRef.current;
    if (!container) return;
    
    // Reset scroll position when changing steps
    container.scrollTop = 0;
  }, [currentStep]);

  return (
    <AppLayout containerClassName="overflow-visible">
      <Page>
        {/* Toast Notification for Added Items */}
        {addedItemFeedback && (
          <div 
            className="fixed bottom-4 right-4 z-50 animate-in slide-in-from-bottom-5 fade-in duration-300"
            style={{
              animation: 'slideInUp 0.3s ease-out'
            }}
          >
            <div className="bg-primary text-primary-foreground px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 max-w-sm">
              <div className="flex-shrink-0">
                <svg 
                  className="w-6 h-6" 
                  fill="none" 
                  stroke="currentColor" 
                  viewBox="0 0 24 24"
                >
                  <path 
                    strokeLinecap="round" 
                    strokeLinejoin="round" 
                    strokeWidth={2} 
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" 
                  />
                </svg>
              </div>
              <div className="flex-1">
                <p className="font-semibold">¡Agregado al carrito!</p>
                <p className="text-sm opacity-90">{addedItemFeedback.name}</p>
              </div>
              <Badge variant="secondary" className="bg-background/10 text-primary-foreground border-0">
                {getTotalItems()} items
              </Badge>
            </div>
          </div>
        )}

        <Page.Header
          title="Nueva Orden"
          subtitle={currentStep === 0 ? "Selecciona productos para agregar a la orden" : "Revisa y confirma los detalles de la orden"}
          actions={
            <div className="flex items-center gap-3">
              
              {/* Action buttons */}
              {currentStep === 0 ? (
                <Button 
                  onClick={handleGoToCheckout}
                  size="default"
                  className="gap-2"
                  disabled={orderItems.length === 0}
                >
                  Ir al Checkout
                  <ArrowRight className="h-4 w-4" />
                </Button>
              ) : (
                <Button 
                  onClick={handleBackToProducts}
                  variant="outline"
                  size="default"
                  className="gap-2"
                >
                  <ArrowLeft className="h-4 w-4" />
                  Volver a Productos
                </Button>
              )}
            </div>
          }
        />

        {currentStep === 0 ? (
          /* Step 1: Product Selection - Main scrollable container */
          <div 
            className="flex-1 relative"
            ref={scrollContainerRef}
            style={{
              WebkitOverflowScrolling: 'touch',
              overscrollBehavior: 'contain',
              height: '100%'
            }}
          >
            {/* Search Bar with Cart - Sticky inside scrollable container */}
            <div 
              className="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-gray-100"
              style={{
                position: 'sticky',
                top: 0,
                zIndex: 20
              }}
            >
              <div className="px-4 sm:px-6 lg:px-8 py-4">
                <div className="max-w-6xl flex items-center gap-3">
                  <div className="flex-1">
                    <SearchInput 
                      searchQuery={searchQuery} 
                      setSearchQuery={setSearchQuery} 
                      isSearchMode={isSearchMode} 
                      setIsSearchMode={setIsSearchMode} 
                    />
                  </div>
                  <CartPopover
                    items={orderItems}
                    pulse={!!addedItemFeedback}
                    onUpdateQuantity={(itemId, delta) => {
                      const item = orderItems.find(i => i.id === itemId);
                      if (item) {
                        const newQuantity = item.quantity + delta;
                        if (newQuantity <= 0) {
                          removeItemFromOrder(itemId);
                        } else {
                          updateItemQuantity(itemId, newQuantity);
                        }
                      }
                    }}
                    onRemoveItem={removeItemFromOrder}
                    onGoToCheckout={handleGoToCheckout}
                  />
                </div>
              </div>
              
              {/* Filter Bar - Shows when searching or when filters are active */}
              {(isSearchMode || searchQuery || activeFiltersCount > 0) && (
                <FilterBar
                  filters={searchFilters}
                  activeCount={activeFiltersCount}
                  onFilterChange={updateSearchFilter}
                  onClearFilters={clearSearchFilters}
                  categories={['Empanadas', 'Completos', 'Pizzas', 'Ensaladas', 'Bebidas', 'Postres']}
                />
              )}
            </div>

            {/* Content Section - Scrollable content */}
            <div className="px-4 sm:px-6 lg:px-8 pb-6 pt-2">
              <div className="max-w-6xl">
                {isSearchMode || searchQuery ? (
                  // Search Results View
                  <SearchView
                    searchQuery={searchQuery}
                    searchResults={searchResults}
                    isSearching={isSearching}
                    favoriteItems={favoriteItems}
                    recentSearches={recentSearches}
                    recentItems={recentItems}
                    popularItems={popularItems}
                    orderItems={orderItems}
                    onAddItem={handleAddItemWithFeedback}
                    onUpdateQuantity={(itemId, delta) => {
                      const item = orderItems.find(i => i.id === itemId);
                      if (item) {
                        const newQuantity = item.quantity + delta;
                        if (newQuantity <= 0) {
                          removeItemFromOrder(itemId);
                        } else {
                          updateItemQuantity(itemId, newQuantity);
                        }
                      }
                    }}
                    onToggleFavorite={toggleFavorite}
                    onSearch={(query) => {
                      setSearchQuery(query);
                      addToRecentSearches(query);
                    }}
                    onCategorySelect={handleCategorySelect}
                  />
                ) : (
                  // Default Categories View when not searching
                  <SearchView
                    searchQuery=""
                    searchResults={[]}
                    isSearching={false}
                    favoriteItems={favoriteItems}
                    recentSearches={recentSearches}
                    recentItems={recentItems}
                    popularItems={popularItems}
                    orderItems={orderItems}
                    onAddItem={handleAddItemWithFeedback}
                    onUpdateQuantity={(itemId, delta) => {
                      const item = orderItems.find(i => i.id === itemId);
                      if (item) {
                        const newQuantity = item.quantity + delta;
                        if (newQuantity <= 0) {
                          removeItemFromOrder(itemId);
                        } else {
                          updateItemQuantity(itemId, newQuantity);
                        }
                      }
                    }}
                    onToggleFavorite={toggleFavorite}
                    onSearch={(query) => {
                      setSearchQuery(query);
                      setIsSearchMode(true);
                      addToRecentSearches(query);
                    }}
                    onCategorySelect={handleCategorySelect}
                  />
                )}
              </div>
            </div>
          </div>
        ) : (
          /* Step 2: Checkout & Review */
          <div className="flex flex-col min-h-screen bg-gray-50">
            <div className="flex-1 px-2 sm:px-3 lg:px-4 py-3">
              <div className="max-w-[1400px]">
                <div className="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-3 items-start">
                  {/* Left Column - Order Items */}
                  <div className="min-w-0">
                    <div className="bg-white rounded-lg shadow-sm h-fit">
                      <div className="px-4 py-3 border-b border-gray-200">
                        <h2 className="text-base font-bold text-gray-900">Resumen de tu Orden</h2>
                        <p className="text-xs text-gray-600 mt-0.5">{getTotalItems()} productos en tu carrito</p>
                      </div>
                      <div className="p-3 max-h-[calc(100vh-200px)] overflow-y-auto">
                        {orderItems.length > 0 ? (
                          <OrderItemsView
                            items={orderItems}
                            viewMode="list"
                            onUpdateQuantity={(itemId, delta) => {
                              const item = orderItems.find((i) => i.id === itemId);
                              if (item) {
                                updateItemQuantity(itemId, item.quantity + delta);
                              }
                            }}
                            onUpdateNotes={updateItemNotes}
                            onRemoveItem={removeItemFromOrder}
                          />
                        ) : (
                          <EmptyOrderState
                            onStartSearch={() => {
                              setCurrentStep(0);
                            }}
                          />
                        )}
                      </div>
                    </div>
                  </div>
                  
                  {/* Right Column - Order Details */}
                  <div className="space-y-3">
                    {/* Order Type */}
                    <div className="bg-white rounded-lg shadow-sm p-4 h-fit">
                      <h3 className="font-semibold text-gray-900 mb-2 text-sm">Tipo de Orden</h3>
                      <div className="grid grid-cols-3 gap-1.5">
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, orderType: 'dine_in' })}
                          className={`p-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.orderType === 'dine_in'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-lg">🏠</div>
                          <div className="text-xs font-medium">Local</div>
                        </button>
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, orderType: 'takeout' })}
                          className={`p-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.orderType === 'takeout'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-lg">🛍️</div>
                          <div className="text-xs font-medium">Llevar</div>
                        </button>
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, orderType: 'delivery' })}
                          className={`p-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.orderType === 'delivery'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-lg">🚚</div>
                          <div className="text-xs font-medium">Delivery</div>
                        </button>
                      </div>
                    </div>
                    
                    {/* Customer Information */}
                    <div className="bg-white rounded-lg shadow-sm p-4 h-fit">
                      <h3 className="font-semibold text-gray-900 mb-2 text-sm">Información del Cliente</h3>
                      <div className="space-y-2">
                        <input
                          type="text"
                          placeholder="Nombre del cliente"
                          value={customerInfo.name}
                          onChange={(e) => setCustomerInfo({ ...customerInfo, name: e.target.value })}
                          className="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        <input
                          type="tel"
                          placeholder="Teléfono"
                          value={customerInfo.phone}
                          onChange={(e) => setCustomerInfo({ ...customerInfo, phone: e.target.value })}
                          className="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                        />
                        {customerInfo.orderType === 'dine_in' && (
                          <input
                            type="text"
                            placeholder="Número de mesa"
                            value={customerInfo.tableNumber}
                            onChange={(e) => setCustomerInfo({ ...customerInfo, tableNumber: e.target.value })}
                            className="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                          />
                        )}
                        <textarea
                          placeholder="Instrucciones especiales (opcional)"
                          value={customerInfo.notes}
                          onChange={(e) => setCustomerInfo({ ...customerInfo, notes: e.target.value })}
                          className="w-full px-2.5 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary resize-none"
                          rows={2}
                        />
                      </div>
                    </div>
                    
                    {/* Payment Method */}
                    <div className="bg-white rounded-lg shadow-sm p-4 h-fit">
                      <h3 className="font-semibold text-gray-900 mb-2 text-sm">Método de Pago</h3>
                      <div className="grid grid-cols-3 gap-1.5">
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, paymentMethod: 'cash' })}
                          className={`py-1.5 px-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.paymentMethod === 'cash'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-xs font-medium">Efectivo</div>
                        </button>
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, paymentMethod: 'card' })}
                          className={`py-1.5 px-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.paymentMethod === 'card'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-xs font-medium">Tarjeta</div>
                        </button>
                        <button
                          onClick={() => setCustomerInfo({ ...customerInfo, paymentMethod: 'transfer' })}
                          className={`py-1.5 px-2 rounded-md border-2 text-center transition-all ${
                            customerInfo.paymentMethod === 'transfer'
                              ? 'border-primary bg-primary/5 text-primary'
                              : 'border-gray-200 hover:border-gray-300'
                          }`}
                        >
                          <div className="text-xs font-medium">Transfer</div>
                        </button>
                      </div>
                    </div>
                    
                    {/* Order Total */}
                    <div className="bg-white rounded-lg shadow-sm p-4 h-fit">
                      <h3 className="font-semibold text-gray-900 mb-2 text-sm">Total</h3>
                      <div className="space-y-1">
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-600">Subtotal</span>
                          <span className="font-medium">${calculateSubtotal().toLocaleString('es-CL')}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                          <span className="text-gray-600">IVA (19%)</span>
                          <span className="font-medium">${calculateTax().toLocaleString('es-CL')}</span>
                        </div>
                        <div className="pt-2 mt-2 border-t border-gray-200">
                          <div className="flex justify-between items-center">
                            <span className="font-semibold text-base">Total</span>
                            <span className="font-bold text-lg text-primary">${calculateTotal().toLocaleString('es-CL')}</span>
                          </div>
                        </div>
                      </div>
                      
                      <Button
                        onClick={handleFinish}
                        size="default"
                        className="w-full mt-3 h-9"
                        disabled={!customerInfo.name || !customerInfo.orderType || !customerInfo.paymentMethod}
                      >
                        Crear Orden
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )}
      </Page>
    </AppLayout>
  );
};

export default function CreateOrder({ popularItems = [] }: CreateOrderProps) {
  return (
    <OrderProvider initialPopularItems={popularItems}>
      <CreateOrderContent />
    </OrderProvider>
  );
}
