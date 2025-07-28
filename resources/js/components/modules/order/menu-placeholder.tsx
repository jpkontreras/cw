import { cn } from '@/lib/utils';
import { ChefHat, Coffee, Pizza, Salad, Sandwich, Utensils } from 'lucide-react';

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
    xl: 'w-24 h-24',
  };

  const iconSizeClasses = {
    sm: 'w-6 h-6',
    md: 'w-8 h-8',
    lg: 'w-12 h-12',
    xl: 'w-16 h-16',
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
    'from-indigo-100 via-blue-50 to-purple-100',
  ];
  const gradient = gradients[variant % gradients.length];

  return (
    <div className={cn('relative h-full w-full overflow-hidden bg-gray-50', className)}>
      {/* Animated gradient background */}
      <div className={cn('absolute inset-0 animate-pulse bg-gradient-to-br opacity-60', gradient)} />

      {/* Subtle pattern overlay */}
      <div className="absolute inset-0">
        <div
          className="h-full w-full opacity-[0.03]"
          style={{
            backgroundImage: `
                        repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(0,0,0,.05) 35px, rgba(0,0,0,.05) 70px),
                        repeating-linear-gradient(-45deg, transparent, transparent 35px, rgba(0,0,0,.03) 35px, rgba(0,0,0,.03) 70px)
                    `,
          }}
        />
      </div>

      {/* Floating shapes for visual interest */}
      <div className="absolute inset-0 overflow-hidden">
        <div className="absolute top-1/4 left-1/4 h-32 w-32 animate-pulse rounded-full bg-white/20 blur-3xl" />
        <div className="absolute right-1/4 bottom-1/4 h-40 w-40 animate-pulse rounded-full bg-white/20 blur-3xl delay-1000" />
      </div>

      {/* Icon container */}
      <div className="relative flex h-full w-full items-center justify-center">
        <div className="transform rounded-2xl bg-white/90 p-4 shadow-lg backdrop-blur-sm transition-transform group-hover:scale-105">
          <Icon className={cn('text-gray-400 transition-colors group-hover:text-gray-500', iconSizeClasses[size])} />
        </div>
      </div>

      {/* Subtle inner shadow for depth */}
      <div className="pointer-events-none absolute inset-0 shadow-inner" />
    </div>
  );
}
