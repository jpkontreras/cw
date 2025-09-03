import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { CollapsedSidebar, EmptyOrderState, ExpandedSidebar, OrderItemsView, SearchInput, SearchView } from '@/modules/order';
import { OrderProvider, useOrder, type SearchResult } from '@/modules/order/contexts/OrderContext';
import { Grid3x3, List, ShoppingBag } from 'lucide-react';
import React, { useRef } from 'react';

interface CreateOrderProps {
  popularItems?: SearchResult[];
}

const CreateOrderContent: React.FC = () => {
  const {
    orderItems,
    customerInfo,
    searchQuery,
    viewMode,
    isSearchMode,
    searchResults,
    isSearching,
    favoriteItems,
    recentSearches,
    popularItems,
    setOrderItems,
    setCustomerInfo,
    setSearchQuery,
    setViewMode,
    setIsSearchMode,
    addItemToOrder,
    removeItemFromOrder,
    updateItemQuantity,
    updateItemNotes,
    toggleFavorite,
    addToRecentSearches,
    processOrder,
    handleCategorySelect,
    getTotalItems,
    calculateSubtotal,
    calculateTax,
    calculateTotal,
  } = useOrder();

  const containerRef = useRef<HTMLDivElement>(null);
  const searchInputRef = useRef<HTMLInputElement>(null);

  return (
    <AppLayout>
      <Page>
        <Page.Header
          title="Nueva Orden"
          actions={
            <div className="flex items-center gap-3">
              <Badge className="border-0 bg-gradient-to-r from-green-500 to-green-600 text-white">
                <ShoppingBag className="mr-1 h-3.5 w-3.5" />
                {getTotalItems()} items
              </Badge>
              {orderItems.length > 0 && !isSearchMode && (
                <Button variant="outline" size="sm" onClick={() => setViewMode(viewMode === 'grid' ? 'list' : 'grid')} className="gap-2">
                  {viewMode === 'grid' ? (
                    <>
                      <List className="h-4 w-4" />
                      <span className="hidden sm:inline">Vista Lista</span>
                    </>
                  ) : (
                    <>
                      <Grid3x3 className="h-4 w-4" />
                      <span className="hidden sm:inline">Vista Cuadrícula</span>
                    </>
                  )}
                </Button>
              )}
            </div>
          }
        />

        <Page.SplitContent
          sidebar={{
            position: 'right',
            defaultSize: 30, // 30% of the width
            minSize: 30, // Minimum 30%
            maxSize: 45, // Maximum 45%
            collapsedSize: 10, // 10% when collapsed
            defaultCollapsed: false,
            resizable: true,
            showToggle: false,
            title: 'Checkout',
            renderContent: (collapsed: boolean, toggleCollapse: () => void) =>
              collapsed ? (
                <CollapsedSidebar
                  orderItems={orderItems}
                  getTotalItems={getTotalItems}
                  calculateTotal={calculateTotal}
                  setOrderItems={setOrderItems}
                  processOrder={processOrder}
                  toggleCollapse={toggleCollapse}
                />
              ) : (
                <ExpandedSidebar
                  customerInfo={customerInfo}
                  setCustomerInfo={setCustomerInfo}
                  orderItems={orderItems}
                  setOrderItems={setOrderItems}
                  toggleCollapse={toggleCollapse}
                  processOrder={processOrder}
                  calculateSubtotal={calculateSubtotal}
                  calculateTax={calculateTax}
                  calculateTotal={calculateTotal}
                />
              ),
          }}
        >
          <div className="flex h-full flex-col bg-gradient-to-br from-gray-50 to-gray-100/50">
            {/* Search Bar Section */}
            <div className="flex-shrink-0 bg-white">
              <div className="px-6 py-4">
                <SearchInput searchQuery={searchQuery} setSearchQuery={setSearchQuery} isSearchMode={isSearchMode} setIsSearchMode={setIsSearchMode} />
              </div>
            </div>

            {/* Main Content Area */}
            <div className="relative min-h-0 flex-1">
              <div className="absolute inset-0 overflow-y-auto" ref={containerRef}>
                <div className="p-6">
                  {isSearchMode ? (
                    <SearchView
                      searchQuery={searchQuery}
                      searchResults={searchResults}
                      isSearching={isSearching}
                      favoriteItems={favoriteItems}
                      recentSearches={recentSearches}
                      popularItems={popularItems}
                      onAddItem={addItemToOrder}
                      onToggleFavorite={toggleFavorite}
                      onSearch={(query) => {
                        setSearchQuery(query);
                        addToRecentSearches(query);
                      }}
                      onCategorySelect={handleCategorySelect}
                    />
                  ) : (
                    <div className="mx-auto max-w-7xl">
                      {/* Order Items or Empty State */}
                      {orderItems.length > 0 ? (
                        <>
                          {/* Order Summary */}
                          <div className="mb-6">
                            <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Tu Orden</h2>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{getTotalItems()} productos seleccionados</p>
                          </div>

                          <OrderItemsView
                            items={orderItems}
                            viewMode={viewMode}
                            onUpdateQuantity={(itemId, delta) => {
                              const item = orderItems.find((i) => i.id === itemId);
                              if (item) {
                                updateItemQuantity(itemId, item.quantity + delta);
                              }
                            }}
                            onUpdateNotes={updateItemNotes}
                            onRemoveItem={removeItemFromOrder}
                          />
                        </>
                      ) : (
                        <EmptyOrderState
                          onStartSearch={() => {
                            setIsSearchMode(true);
                            searchInputRef.current?.focus();
                          }}
                        />
                      )}
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        </Page.SplitContent>
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
