import { Breadcrumbs } from '@/components/breadcrumbs';
import { Separator } from '@/components/ui/separator';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {
  return (
    <header className="flex h-12 shrink-0 items-center gap-2 border-b border-sidebar-border/50 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-8">
      <div className="flex items-center">
        <SidebarTrigger className="-ml-1" />
        <Separator orientation="vertical" className="mx-1 h-5 bg-border" />
        <Breadcrumbs breadcrumbs={breadcrumbs} />
      </div>
    </header>
  );
}
