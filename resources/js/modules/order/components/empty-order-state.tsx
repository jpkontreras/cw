import React from 'react';
import { Button } from '@/components/ui/button';
import { ShoppingBag, Search } from 'lucide-react';

interface EmptyOrderStateProps {
  onStartSearch: () => void;
}

export const EmptyOrderState: React.FC<EmptyOrderStateProps> = ({ onStartSearch }) => {
  return (
    <div className="flex flex-col items-center justify-center py-24">
      <div className="w-32 h-32 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-800 dark:to-gray-700 rounded-full flex items-center justify-center mb-6">
        <ShoppingBag className="h-16 w-16 text-gray-400" />
      </div>
      <h3 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
        Tu orden está vacía
      </h3>
      <p className="text-gray-500 dark:text-gray-400 text-center max-w-sm mb-6">
        Comienza agregando productos usando la barra de búsqueda superior
      </p>
      <Button
        size="lg"
        onClick={onStartSearch}
        className="gap-2"
      >
        <Search className="h-4 w-4" />
        Buscar Productos
      </Button>
    </div>
  );
};