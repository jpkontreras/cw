import { usePage } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { CheckCircle2, XCircle, Info, AlertTriangle, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

interface FlashMessages {
  success?: string;
  error?: string;
  warning?: string;
  info?: string;
}

interface VisibleStates {
  success: boolean;
  error: boolean;
  warning: boolean;
  info: boolean;
}

export function FlashMessages() {
  const { flash } = usePage().props as { flash: FlashMessages };
  const [visible, setVisible] = useState<VisibleStates>({
    success: true,
    error: true,
    warning: true,
    info: true,
  });

  useEffect(() => {
    if (flash) {
      // Reset visibility for any new messages
      setVisible({
        success: !!flash.success,
        error: !!flash.error,
        warning: !!flash.warning,
        info: !!flash.info,
      });

      // Auto-hide success messages after 5 seconds
      if (flash.success) {
        const timer = setTimeout(() => {
          setVisible(prev => ({ ...prev, success: false }));
        }, 5000);
        return () => clearTimeout(timer);
      }
    }
  }, [flash]);

  if (!flash || Object.keys(flash).length === 0) {
    return null;
  }

  const hideMessage = (type: keyof VisibleStates) => {
    setVisible(prev => ({ ...prev, [type]: false }));
  };

  return (
    <div className="space-y-3">
      {flash.success && visible.success && (
        <Alert className="relative border-green-200 bg-green-50/50 backdrop-blur-sm py-2.5">
          <div className="flex items-center gap-2.5">
            <div className="flex-shrink-0">
              <div className="flex h-6 w-6 items-center justify-center rounded-full bg-green-100">
                <CheckCircle2 className="h-3.5 w-3.5 text-green-600" />
              </div>
            </div>
            <AlertDescription className="flex-1 text-green-800 font-medium text-xs">
              {flash.success}
            </AlertDescription>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => hideMessage('success')}
              className="flex-shrink-0 -mr-1.5 -my-1 h-5 w-5 rounded-full hover:bg-green-100 text-green-600 p-0"
            >
              <X className="h-3 w-3" />
              <span className="sr-only">Dismiss</span>
            </Button>
          </div>
        </Alert>
      )}

      {flash.error && visible.error && (
        <Alert className="relative border-red-200 bg-red-50/50 backdrop-blur-sm py-2.5">
          <div className="flex items-center gap-2.5">
            <div className="flex-shrink-0">
              <div className="flex h-6 w-6 items-center justify-center rounded-full bg-red-100">
                <XCircle className="h-3.5 w-3.5 text-red-600" />
              </div>
            </div>
            <AlertDescription className="flex-1 text-red-800 font-medium text-xs">
              {flash.error}
            </AlertDescription>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => hideMessage('error')}
              className="flex-shrink-0 -mr-1.5 -my-1 h-5 w-5 rounded-full hover:bg-red-100 text-red-600 p-0"
            >
              <X className="h-3 w-3" />
              <span className="sr-only">Dismiss</span>
            </Button>
          </div>
        </Alert>
      )}

      {flash.warning && visible.warning && (
        <Alert className="relative border-yellow-200 bg-yellow-50/50 backdrop-blur-sm py-2.5">
          <div className="flex items-center gap-2.5">
            <div className="flex-shrink-0">
              <div className="flex h-6 w-6 items-center justify-center rounded-full bg-yellow-100">
                <AlertTriangle className="h-3.5 w-3.5 text-yellow-600" />
              </div>
            </div>
            <AlertDescription className="flex-1 text-yellow-800 font-medium text-xs">
              {flash.warning}
            </AlertDescription>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => hideMessage('warning')}
              className="flex-shrink-0 -mr-1.5 -my-1 h-5 w-5 rounded-full hover:bg-yellow-100 text-yellow-600 p-0"
            >
              <X className="h-3 w-3" />
              <span className="sr-only">Dismiss</span>
            </Button>
          </div>
        </Alert>
      )}

      {flash.info && visible.info && (
        <Alert className="relative border-blue-200 bg-blue-50/50 backdrop-blur-sm py-2.5">
          <div className="flex items-center gap-2.5">
            <div className="flex-shrink-0">
              <div className="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100">
                <Info className="h-3.5 w-3.5 text-blue-600" />
              </div>
            </div>
            <AlertDescription className="flex-1 text-blue-800 font-medium text-xs">
              {flash.info}
            </AlertDescription>
            <Button
              variant="ghost"
              size="sm"
              onClick={() => hideMessage('info')}
              className="flex-shrink-0 -mr-1.5 -my-1 h-5 w-5 rounded-full hover:bg-blue-100 text-blue-600 p-0"
            >
              <X className="h-3 w-3" />
              <span className="sr-only">Dismiss</span>
            </Button>
          </div>
        </Alert>
      )}
    </div>
  );
}