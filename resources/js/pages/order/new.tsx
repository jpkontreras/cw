import { useState } from 'react';
import { router, Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import {
  Utensils,
  ShoppingBag,
  Truck,
  Users,
  Clock,
  MapPin,
  Phone,
  User,
  ArrowRight,
  Home,
  Hash,
  Calendar,
  AlertCircle,
  ChefHat,
  Coffee,
  Sparkles
} from 'lucide-react';
import { cn } from '@/lib/utils';

interface OrderTypeOption {
  type: 'dine_in' | 'takeout' | 'delivery';
  icon: React.ElementType;
  title: string;
  description: string;
  color: string;
  bgColor: string;
  features: string[];
  estimatedTime?: string;
}

export default function NewOrder() {
  const [isInitializingSession, setIsInitializingSession] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const orderTypes: OrderTypeOption[] = [
    {
      type: 'dine_in',
      icon: Utensils,
      title: 'Dine In',
      description: 'Eat at the restaurant',
      color: 'text-blue-600',
      bgColor: 'bg-blue-50 hover:bg-blue-100',
      features: ['Order served to table', 'Dine-in menu', 'Restaurant seating']
    },
    {
      type: 'takeout',
      icon: ShoppingBag,
      title: 'Takeout',
      description: 'Order for pickup',
      color: 'text-purple-600',
      bgColor: 'bg-purple-50 hover:bg-purple-100',
      features: ['Pickup at counter', 'Packaged to go', 'Call when ready']
    },
    {
      type: 'delivery',
      icon: Truck,
      title: 'Delivery',
      description: 'Order for delivery',
      color: 'text-green-600',
      bgColor: 'bg-green-50 hover:bg-green-100',
      features: ['Delivered to address', 'Driver assigned', 'Delivery tracking']
    }
  ];

  const handleOrderTypeSelection = (type: 'dine_in' | 'takeout' | 'delivery') => {
    // Proceed directly for all order types
    submitOrder(type);
  };

  const submitOrder = async (type: string) => {
    setIsInitializingSession(true);
    setError(null);

    router.post('/orders/start', {
      type: type,
    }, {
      preserveState: false,
      preserveScroll: false,
      onStart: () => {
        setIsInitializingSession(true);
      },
      onFinish: () => {
        setIsInitializingSession(false);
      },
      onError: (errors) => {
        console.error('Failed to start session:', errors);
        setError('Failed to create session. Please try again.');
      },
    });
  };


  return (
    <AppLayout>
      <Head title="New Order" />
      <Page>
        <Page.Header
          title="Start New Order"
          subtitle="Choose how you'd like to enjoy your meal today"
        />

        <Page.Content>
          <div className="max-w-5xl mx-auto">

            {/* Error Alert */}
            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                <AlertCircle className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" />
                <span>{error}</span>
              </div>
            )}

            {/* Order Type Cards */}
            <div className="grid gap-6 md:grid-cols-3">
              {orderTypes.map((option) => {
                const Icon = option.icon;

                return (
                  <Card
                    key={option.type}
                    className="transition-all duration-200 hover:shadow-lg"
                  >
                    <CardContent className="p-6">
                      <div className="space-y-4">
                        {/* Icon */}
                        <div className={cn(
                          "w-14 h-14 rounded-lg flex items-center justify-center",
                          option.bgColor
                        )}>
                          <Icon className={cn("h-7 w-7", option.color)} />
                        </div>

                        {/* Title and Description */}
                        <div>
                          <h3 className="font-semibold text-lg text-gray-900 mb-1">
                            {option.title}
                          </h3>
                          <p className="text-sm text-gray-600">
                            {option.description}
                          </p>
                        </div>

                        {/* Features */}
                        <div className="space-y-2 pt-2 border-t border-gray-100">
                          {option.features.map((feature, idx) => (
                            <div key={idx} className="flex items-center gap-2">
                              <div className="w-1.5 h-1.5 rounded-full bg-gray-400" />
                              <span className="text-xs text-gray-600">{feature}</span>
                            </div>
                          ))}
                        </div>

                        {/* Action Button */}
                        <Button
                          className="w-full"
                          onClick={() => handleOrderTypeSelection(option.type)}
                          disabled={isInitializingSession}
                        >
                          Select {option.title}
                          <ArrowRight className="ml-2 h-4 w-4" />
                        </Button>
                      </div>
                    </CardContent>
                  </Card>
                );
              })}
            </div>

            {/* Loading State Overlay */}
            {isInitializingSession && (
              <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
                <Card className="p-6">
                  <div className="flex items-center space-x-4">
                    <svg className="animate-spin h-8 w-8 text-primary" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" />
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <div>
                      <p className="font-medium">Initializing your order...</p>
                      <p className="text-sm text-gray-500">Please wait a moment</p>
                    </div>
                  </div>
                </Card>
              </div>
            )}
          </div>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}