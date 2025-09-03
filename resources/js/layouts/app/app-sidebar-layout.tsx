import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { type BreadcrumbItem } from '@/types';
import { cn } from '@/lib/utils';
import { type PropsWithChildren } from 'react';

interface AppSidebarLayoutProps {
  breadcrumbs?: BreadcrumbItem[];
  containerClassName?: string;
}

export default function AppSidebarLayout({ 
  children, 
  breadcrumbs = [], 
  containerClassName 
}: PropsWithChildren<AppSidebarLayoutProps>) {
  return (
    <AppShell variant="sidebar">
      <AppSidebar />
      <AppContent 
        variant="sidebar" 
        className={cn(
          "overflow-hidden",
          containerClassName
        )}
      >
        <AppSidebarHeader breadcrumbs={breadcrumbs} />
        <div className={cn(
          "flex flex-1 flex-col overflow-hidden",
          containerClassName
        )}>
          {children}
        </div>
      </AppContent>
    </AppShell>
  );
}
