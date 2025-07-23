import * as React from 'react';
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface BreadcrumbItem {
    label: string;
    href?: string;
}

interface PageHeaderProps {
    title: string;
    description?: string;
    children?: React.ReactNode;
    breadcrumbs?: BreadcrumbItem[];
    className?: string;
}

export function PageHeader({ 
    title, 
    description, 
    children, 
    breadcrumbs,
    className 
}: PageHeaderProps) {
    return (
        <div className={cn("bg-background border-b", className)}>
            <div className="px-4 py-4 sm:px-6 lg:px-8">
                {/* Breadcrumbs */}
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <nav className="flex mb-2" aria-label="Breadcrumb">
                        <ol className="flex items-center space-x-2 text-sm">
                            {breadcrumbs.map((crumb, index) => (
                                <li key={index} className="flex items-center">
                                    {index > 0 && (
                                        <ChevronRight className="w-3.5 h-3.5 mx-1 text-gray-400" />
                                    )}
                                    {crumb.href ? (
                                        <Link 
                                            href={crumb.href}
                                            className="text-gray-500 hover:text-gray-700 transition-colors"
                                        >
                                            {crumb.label}
                                        </Link>
                                    ) : (
                                        <span className="text-gray-900 font-medium">
                                            {crumb.label}
                                        </span>
                                    )}
                                </li>
                            ))}
                        </ol>
                    </nav>
                )}

                {/* Header content */}
                <div className="flex items-center justify-between">
                    <div className="min-w-0 flex-1">
                        <h1 className="text-2xl font-semibold text-gray-900 sm:text-3xl">
                            {title}
                        </h1>
                        {description && (
                            <p className="mt-1 text-sm text-gray-500 sm:text-base">
                                {description}
                            </p>
                        )}
                    </div>
                    {children && (
                        <div className="mt-4 flex items-center gap-2 sm:mt-0 sm:ml-6 sm:flex-shrink-0">
                            {children}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

interface PageContentProps {
    children: React.ReactNode;
    className?: string;
    noPadding?: boolean;
}

export function PageContent({ 
    children, 
    className,
    noPadding = false 
}: PageContentProps) {
    return (
        <div className={cn(
            "flex-1 overflow-hidden",
            !noPadding && "px-4 py-6 sm:px-6 lg:px-8",
            className
        )}>
            {children}
        </div>
    );
}

interface PageLayoutProps {
    children: React.ReactNode;
    className?: string;
}

export function PageLayout({ children, className }: PageLayoutProps) {
    return (
        <div className={cn("flex flex-col min-h-full", className)}>
            {children}
        </div>
    );
}