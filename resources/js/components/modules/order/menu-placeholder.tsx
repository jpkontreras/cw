import { ChefHat, Utensils, Coffee, Pizza, Salad, Sandwich } from 'lucide-react';
import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    size?: 'sm' | 'md' | 'lg' | 'xl';
    variant?: number;
}

export function MenuPlaceholder({ className, size = 'md', variant = 0 }: Props) {
    const sizeClasses = {
        sm: 'w-8 h-8',
        md: 'w-12 h-12',
        lg: 'w-16 h-16',
        xl: 'w-24 h-24'
    };
    
    const iconSizeClasses = {
        sm: 'w-6 h-6',
        md: 'w-8 h-8',
        lg: 'w-12 h-12',
        xl: 'w-16 h-16'
    };
    
    // Different icons for variety
    const icons = [ChefHat, Utensils, Coffee, Pizza, Salad, Sandwich];
    const Icon = icons[variant % icons.length];
    
    // Different gradient variations
    const gradients = [
        'from-orange-100 via-amber-50 to-yellow-100',
        'from-green-100 via-emerald-50 to-teal-100',
        'from-blue-100 via-sky-50 to-cyan-100',
        'from-purple-100 via-violet-50 to-pink-100',
        'from-red-100 via-rose-50 to-pink-100',
        'from-indigo-100 via-blue-50 to-purple-100'
    ];
    const gradient = gradients[variant % gradients.length];
    
    return (
        <div className={cn("w-full h-full relative overflow-hidden bg-gray-50", className)}>
            {/* Animated gradient background */}
            <div className={cn(
                "absolute inset-0 bg-gradient-to-br opacity-60 animate-pulse",
                gradient
            )} />
            
            {/* Subtle pattern overlay */}
            <div className="absolute inset-0">
                <div className="w-full h-full opacity-[0.03]" style={{
                    backgroundImage: `
                        repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(0,0,0,.05) 35px, rgba(0,0,0,.05) 70px),
                        repeating-linear-gradient(-45deg, transparent, transparent 35px, rgba(0,0,0,.03) 35px, rgba(0,0,0,.03) 70px)
                    `,
                }} />
            </div>
            
            {/* Floating shapes for visual interest */}
            <div className="absolute inset-0 overflow-hidden">
                <div className="absolute top-1/4 left-1/4 w-32 h-32 bg-white/20 rounded-full blur-3xl animate-pulse" />
                <div className="absolute bottom-1/4 right-1/4 w-40 h-40 bg-white/20 rounded-full blur-3xl animate-pulse delay-1000" />
            </div>
            
            {/* Icon container */}
            <div className="relative w-full h-full flex items-center justify-center">
                <div className="bg-white/90 backdrop-blur-sm rounded-2xl p-4 shadow-lg transform transition-transform group-hover:scale-105">
                    <Icon className={cn(
                        "text-gray-400 transition-colors group-hover:text-gray-500",
                        iconSizeClasses[size]
                    )} />
                </div>
            </div>
            
            {/* Subtle inner shadow for depth */}
            <div className="absolute inset-0 shadow-inner pointer-events-none" />
        </div>
    );
}