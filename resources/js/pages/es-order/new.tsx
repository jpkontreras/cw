import { useState } from 'react';
import { router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Page from '@/layouts/page-layout';
import { ArrowRight } from 'lucide-react';
import axios from 'axios';

export default function NewOrder() {
  const [isInitializingSession, setIsInitializingSession] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const handleOrderTypeSelection = async (type: 'dine_in' | 'takeout' | 'delivery') => {
    setIsInitializingSession(true);
    setError(null);
    
    try {
      // Create new session using es-order route
      const response = await axios.post('/es-order/session/start', {
        platform: 'web',
        source: 'web',
        order_type: type,
      });
      
      if (response.data.success) {
        const uuid = response.data.data.uuid;
        
        // Navigate to the es-order session page
        router.visit(`/es-order/session/${uuid}`);
      } else {
        setError('Failed to create session. Please try again.');
      }
    } catch (err: any) {
      console.error('Failed to start session:', err);
      setError(err.response?.data?.message || 'Failed to create session. Please try again.');
    } finally {
      setIsInitializingSession(false);
    }
  };

  return (
    <AppLayout containerClassName="overflow-visible">
      <Page>
        <Page.Header
          title="New Event-Sourced Order"
          subtitle="Choose your service type to get started with full event tracking"
        />
        
        <Page.Content>
          <div className="max-w-3xl mx-auto">

            {/* Error Alert */}
            {error && (
              <div className="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg flex items-start">
                <svg className="w-5 h-5 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
                </svg>
                <span>{error}</span>
              </div>
            )}

            {/* Options Grid - Event-sourced theme */}
            <div className="grid gap-4 md:grid-cols-3 mb-8">
              {/* Dine In Option */}
              <button
                onClick={() => handleOrderTypeSelection('dine_in')}
                disabled={isInitializingSession}
                className="group bg-white border border-gray-200 rounded-xl p-6 hover:border-primary hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden"
              >
                <div className="absolute top-2 right-2">
                  <span className="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">Event-Sourced</span>
                </div>
                <div className="flex flex-col items-center text-center space-y-3">
                  <div className="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                    <svg className="w-8 h-8 text-gray-700 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                  </div>
                  <div>
                    <h3 className="font-semibold text-lg text-gray-900">Dine In</h3>
                    <p className="text-sm text-gray-500 mt-1">Eat at restaurant</p>
                  </div>
                </div>
              </button>

              {/* Takeout Option */}
              <button
                onClick={() => handleOrderTypeSelection('takeout')}
                disabled={isInitializingSession}
                className="group bg-white border border-gray-200 rounded-xl p-6 hover:border-primary hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden"
              >
                <div className="absolute top-2 right-2">
                  <span className="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">Event-Sourced</span>
                </div>
                <div className="flex flex-col items-center text-center space-y-3">
                  <div className="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                    <svg className="w-8 h-8 text-gray-700 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7" />
                    </svg>
                  </div>
                  <div>
                    <h3 className="font-semibold text-lg text-gray-900">Takeout</h3>
                    <p className="text-sm text-gray-500 mt-1">Pick up order</p>
                  </div>
                </div>
              </button>

              {/* Delivery Option */}
              <button
                onClick={() => handleOrderTypeSelection('delivery')}
                disabled={isInitializingSession}
                className="group bg-white border border-gray-200 rounded-xl p-6 hover:border-primary hover:shadow-lg transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed relative overflow-hidden"
              >
                <div className="absolute top-2 right-2">
                  <span className="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded-full">Event-Sourced</span>
                </div>
                <div className="flex flex-col items-center text-center space-y-3">
                  <div className="w-16 h-16 bg-gray-100 rounded-xl flex items-center justify-center group-hover:bg-primary/10 transition-colors">
                    <svg className="w-8 h-8 text-gray-700 group-hover:text-primary transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0" />
                    </svg>
                  </div>
                  <div>
                    <h3 className="font-semibold text-lg text-gray-900">Delivery</h3>
                    <p className="text-sm text-gray-500 mt-1">Home delivery</p>
                  </div>
                </div>
              </button>
            </div>

            {/* Event Sourcing Information */}
            <div className="bg-blue-50 rounded-xl p-5 border border-blue-100">
              <div className="flex items-start space-x-3">
                <svg className="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div className="flex-1">
                  <p className="text-sm text-gray-700 leading-relaxed">
                    <span className="font-medium">Event-Sourced Architecture:</span> This order system uses event sourcing to track every action. 
                    Each change is recorded as an immutable event, providing complete audit trail, offline-first capabilities, 
                    and the ability to replay the entire order history.
                  </p>
                </div>
              </div>
            </div>

            {/* Error Message */}
            {error && (
              <div className="mt-4 bg-red-50 rounded-xl p-4 border border-red-200">
                <div className="flex items-center">
                  <svg className="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <p className="text-sm text-red-700">{error}</p>
                </div>
              </div>
            )}

            {/* Loading State */}
            {isInitializingSession && (
              <div className="mt-6">
                <div className="bg-white rounded-xl p-4 border border-gray-100">
                  <div className="flex items-center justify-center space-x-3">
                    <svg className="animate-spin h-5 w-5 text-primary" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                      <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3"></circle>
                      <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p className="text-sm text-gray-600">Initializing event-sourced session...</p>
                  </div>
                </div>
              </div>
            )}
          </div>
        </Page.Content>
      </Page>
    </AppLayout>
  );
}