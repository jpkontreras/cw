import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface LocationLayoutProps extends PropsWithChildren {
  title?: string;
}

export default function LocationLayout({ children, title }: LocationLayoutProps) {
  return (
    <AppLayout>
      {title && <Head title={title} />}
      {children}
    </AppLayout>
  );
}
