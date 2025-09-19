import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { CheckCircle, AlertCircle, ScanLine } from 'lucide-react';

interface BarcodeScannerProps {
    onScanSuccess?: (orderNumber: string) => void;
    onScanError?: (error: string) => void;
}

export default function BarcodeScanner({ onScanSuccess, onScanError }: BarcodeScannerProps) {
    const [barcode, setBarcode] = useState('');
    const [scanning, setScanning] = useState(false);
    const [status, setStatus] = useState<'idle' | 'success' | 'error'>('idle');
    const [message, setMessage] = useState('');

    const handleScan = async () => {
        if (!barcode.trim()) {
            setStatus('error');
            setMessage('Please enter or scan a barcode');
            return;
        }

        setScanning(true);
        setStatus('idle');

        try {
            const response = await fetch('/api/es-order/slip/scan', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ barcode: barcode.trim() }),
            });

            const data = await response.json();

            if (response.ok && data.success) {
                setStatus('success');
                setMessage(`Order ${data.order_number} marked as ready!`);
                setBarcode('');

                if (onScanSuccess) {
                    onScanSuccess(data.order_number);
                }

                // Auto-reset after 3 seconds for next scan
                setTimeout(() => {
                    setStatus('idle');
                    setMessage('');
                }, 3000);
            } else {
                setStatus('error');
                setMessage(data.message || 'Failed to scan order');

                if (onScanError) {
                    onScanError(data.message || 'Failed to scan order');
                }
            }
        } catch (error) {
            setStatus('error');
            setMessage('Network error - please try again');

            if (onScanError) {
                onScanError('Network error');
            }
        } finally {
            setScanning(false);
        }
    };

    const handleKeyPress = (e: React.KeyboardEvent) => {
        if (e.key === 'Enter') {
            handleScan();
        }
    };

    return (
        <div className="p-6 bg-white rounded-lg shadow-sm">
            <div className="flex items-center mb-4">
                <ScanLine className="w-6 h-6 mr-2 text-gray-600" />
                <h3 className="text-lg font-semibold">Scan Order Slip</h3>
            </div>

            <div className="space-y-4">
                <div className="flex gap-2">
                    <Input
                        type="text"
                        placeholder="Scan or enter order number..."
                        value={barcode}
                        onChange={(e) => setBarcode(e.target.value)}
                        onKeyPress={handleKeyPress}
                        disabled={scanning}
                        className="flex-1"
                        autoFocus
                    />
                    <Button
                        onClick={handleScan}
                        disabled={scanning || !barcode.trim()}
                        variant="default"
                    >
                        {scanning ? 'Scanning...' : 'Mark Ready'}
                    </Button>
                </div>

                {status !== 'idle' && (
                    <div className={`flex items-center p-3 rounded-md ${
                        status === 'success' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                    }`}>
                        {status === 'success' ? (
                            <CheckCircle className="w-5 h-5 mr-2" />
                        ) : (
                            <AlertCircle className="w-5 h-5 mr-2" />
                        )}
                        <span className="text-sm font-medium">{message}</span>
                    </div>
                )}

                <div className="text-xs text-gray-500">
                    Scan the barcode on the order slip or type the order number manually
                </div>
            </div>
        </div>
    );
}