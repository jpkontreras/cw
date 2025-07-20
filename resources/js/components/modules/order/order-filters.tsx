import { useState, useCallback } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Calendar } from '@/components/ui/calendar';
import { Badge } from '@/components/ui/badge';
import { 
    Search, 
    Filter, 
    X, 
    Calendar as CalendarIcon,
    RefreshCw 
} from 'lucide-react';
import { format } from 'date-fns';
import type { OrderStatus, OrderType } from '@/types/modules/order';
import { getStatusLabel, getTypeLabel } from '@/types/modules/order/utils';

export interface OrderFilterValues {
    search?: string;
    status?: OrderStatus | '';
    type?: OrderType | '';
    location_id?: string;
    date?: 'today' | 'yesterday' | 'week' | 'month' | 'custom';
    dateFrom?: Date;
    dateTo?: Date;
    payment_status?: string;
    priority?: string;
}

interface OrderFiltersProps {
    filters: OrderFilterValues;
    onFiltersChange: (filters: OrderFilterValues) => void;
    locations?: Array<{ id: number | string; name: string }>;
    showAdvanced?: boolean;
    className?: string;
}

export function OrderFilters({ 
    filters, 
    onFiltersChange,
    locations = [],
    showAdvanced = true,
    className = ''
}: OrderFiltersProps) {
    const [showFilters, setShowFilters] = useState(false);
    const [tempFilters, setTempFilters] = useState(filters);

    const statuses: Array<{ value: OrderStatus | ''; label: string }> = [
        { value: '', label: 'All Statuses' },
        { value: 'draft', label: 'Draft' },
        { value: 'placed', label: 'Placed' },
        { value: 'confirmed', label: 'Confirmed' },
        { value: 'preparing', label: 'Preparing' },
        { value: 'ready', label: 'Ready' },
        { value: 'delivering', label: 'Delivering' },
        { value: 'delivered', label: 'Delivered' },
        { value: 'completed', label: 'Completed' },
        { value: 'cancelled', label: 'Cancelled' },
        { value: 'refunded', label: 'Refunded' }
    ];

    const types: Array<{ value: OrderType | ''; label: string }> = [
        { value: '', label: 'All Types' },
        { value: 'dine_in', label: 'Dine In' },
        { value: 'takeout', label: 'Takeout' },
        { value: 'delivery', label: 'Delivery' },
        { value: 'catering', label: 'Catering' }
    ];

    const dateOptions = [
        { value: '', label: 'All Time' },
        { value: 'today', label: 'Today' },
        { value: 'yesterday', label: 'Yesterday' },
        { value: 'week', label: 'This Week' },
        { value: 'month', label: 'This Month' },
        { value: 'custom', label: 'Custom Range' }
    ];

    const handleSearchChange = useCallback((value: string) => {
        onFiltersChange({ ...filters, search: value });
    }, [filters, onFiltersChange]);

    const handleQuickFilter = useCallback((key: keyof OrderFilterValues, value: any) => {
        onFiltersChange({ ...filters, [key]: value });
    }, [filters, onFiltersChange]);

    const handleApplyFilters = () => {
        onFiltersChange(tempFilters);
        setShowFilters(false);
    };

    const handleResetFilters = () => {
        const resetFilters: OrderFilterValues = {
            search: '',
            status: '',
            type: '',
            location_id: '',
            date: '',
            payment_status: '',
            priority: ''
        };
        setTempFilters(resetFilters);
        onFiltersChange(resetFilters);
    };

    const activeFilterCount = Object.values(filters).filter(v => v && v !== '').length;

    return (
        <div className={`space-y-4 ${className}`}>
            {/* Search and Quick Filters */}
            <div className="flex flex-wrap items-center gap-3">
                {/* Search */}
                <div className="relative flex-1 min-w-[200px] max-w-md">
                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                    <Input
                        type="text"
                        placeholder="Search orders..."
                        value={filters.search || ''}
                        onChange={(e) => handleSearchChange(e.target.value)}
                        className="pl-10"
                    />
                </div>

                {/* Quick Filters */}
                <Select value={filters.status || ''} onValueChange={(v) => handleQuickFilter('status', v)}>
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map(status => (
                            <SelectItem key={status.value} value={status.value}>
                                {status.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                <Select value={filters.type || ''} onValueChange={(v) => handleQuickFilter('type', v)}>
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Type" />
                    </SelectTrigger>
                    <SelectContent>
                        {types.map(type => (
                            <SelectItem key={type.value} value={type.value}>
                                {type.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {locations.length > 0 && (
                    <Select value={filters.location_id || ''} onValueChange={(v) => handleQuickFilter('location_id', v)}>
                        <SelectTrigger className="w-48">
                            <SelectValue placeholder="Location" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Locations</SelectItem>
                            {locations.map(location => (
                                <SelectItem key={location.id} value={location.id.toString()}>
                                    {location.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                )}

                <Select value={filters.date || ''} onValueChange={(v) => handleQuickFilter('date', v)}>
                    <SelectTrigger className="w-40">
                        <SelectValue placeholder="Date" />
                    </SelectTrigger>
                    <SelectContent>
                        {dateOptions.map(option => (
                            <SelectItem key={option.value} value={option.value}>
                                {option.label}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {/* Advanced Filters Toggle */}
                {showAdvanced && (
                    <Button
                        variant="outline"
                        onClick={() => setShowFilters(!showFilters)}
                        className="relative"
                    >
                        <Filter className="w-4 h-4 mr-2" />
                        Filters
                        {activeFilterCount > 0 && (
                            <Badge variant="secondary" className="ml-2">
                                {activeFilterCount}
                            </Badge>
                        )}
                    </Button>
                )}

                {/* Reset Filters */}
                {activeFilterCount > 0 && (
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={handleResetFilters}
                        title="Reset filters"
                    >
                        <X className="w-4 h-4" />
                    </Button>
                )}
            </div>

            {/* Advanced Filters Panel */}
            {showAdvanced && showFilters && (
                <Card>
                    <CardContent className="p-6">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            {/* Payment Status */}
                            <div>
                                <Label htmlFor="payment-status">Payment Status</Label>
                                <Select 
                                    value={tempFilters.payment_status || ''} 
                                    onValueChange={(v) => setTempFilters({ ...tempFilters, payment_status: v })}
                                >
                                    <SelectTrigger id="payment-status">
                                        <SelectValue placeholder="All" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="partial">Partial</SelectItem>
                                        <SelectItem value="paid">Paid</SelectItem>
                                        <SelectItem value="refunded">Refunded</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Priority */}
                            <div>
                                <Label htmlFor="priority">Priority</Label>
                                <Select 
                                    value={tempFilters.priority || ''} 
                                    onValueChange={(v) => setTempFilters({ ...tempFilters, priority: v })}
                                >
                                    <SelectTrigger id="priority">
                                        <SelectValue placeholder="All" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">All</SelectItem>
                                        <SelectItem value="normal">Normal</SelectItem>
                                        <SelectItem value="high">High Priority</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            {/* Custom Date Range */}
                            {tempFilters.date === 'custom' && (
                                <>
                                    <div>
                                        <Label>From Date</Label>
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button variant="outline" className="w-full justify-start text-left font-normal">
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {tempFilters.dateFrom ? format(tempFilters.dateFrom, 'PPP') : 'Pick a date'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0">
                                                <Calendar
                                                    mode="single"
                                                    selected={tempFilters.dateFrom}
                                                    onSelect={(date) => setTempFilters({ ...tempFilters, dateFrom: date })}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>

                                    <div>
                                        <Label>To Date</Label>
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button variant="outline" className="w-full justify-start text-left font-normal">
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {tempFilters.dateTo ? format(tempFilters.dateTo, 'PPP') : 'Pick a date'}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0">
                                                <Calendar
                                                    mode="single"
                                                    selected={tempFilters.dateTo}
                                                    onSelect={(date) => setTempFilters({ ...tempFilters, dateTo: date })}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>
                                    </div>
                                </>
                            )}
                        </div>

                        {/* Filter Actions */}
                        <div className="flex justify-end gap-3 mt-6">
                            <Button variant="outline" onClick={() => setShowFilters(false)}>
                                Cancel
                            </Button>
                            <Button onClick={handleApplyFilters}>
                                Apply Filters
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Active Filters Display */}
            {activeFilterCount > 0 && (
                <div className="flex flex-wrap items-center gap-2">
                    <span className="text-sm text-gray-500">Active filters:</span>
                    {filters.search && (
                        <Badge variant="secondary" className="flex items-center gap-1">
                            Search: {filters.search}
                            <X 
                                className="w-3 h-3 cursor-pointer" 
                                onClick={() => handleQuickFilter('search', '')}
                            />
                        </Badge>
                    )}
                    {filters.status && (
                        <Badge variant="secondary" className="flex items-center gap-1">
                            Status: {getStatusLabel(filters.status)}
                            <X 
                                className="w-3 h-3 cursor-pointer" 
                                onClick={() => handleQuickFilter('status', '')}
                            />
                        </Badge>
                    )}
                    {filters.type && (
                        <Badge variant="secondary" className="flex items-center gap-1">
                            Type: {getTypeLabel(filters.type)}
                            <X 
                                className="w-3 h-3 cursor-pointer" 
                                onClick={() => handleQuickFilter('type', '')}
                            />
                        </Badge>
                    )}
                    {filters.location_id && locations.length > 0 && (
                        <Badge variant="secondary" className="flex items-center gap-1">
                            Location: {locations.find(l => l.id.toString() === filters.location_id)?.name}
                            <X 
                                className="w-3 h-3 cursor-pointer" 
                                onClick={() => handleQuickFilter('location_id', '')}
                            />
                        </Badge>
                    )}
                    {filters.date && (
                        <Badge variant="secondary" className="flex items-center gap-1">
                            Date: {dateOptions.find(d => d.value === filters.date)?.label}
                            <X 
                                className="w-3 h-3 cursor-pointer" 
                                onClick={() => handleQuickFilter('date', '')}
                            />
                        </Badge>
                    )}
                </div>
            )}
        </div>
    );
}