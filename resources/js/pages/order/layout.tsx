import { PageContent, PageHeader } from '@/components/page-header';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import React from 'react';

type OrderLayoutTypes = {
  page: React.ReactNode;
  header: React.ReactNode;
  title?: string;
  headerTitle?: string | undefined;
  description?: string | undefined;
};

export default function OrderLayout({ page, title, headerTitle, description, header }: OrderLayoutTypes) {
  return (
    <AppLayout>
      <Head title={title} />
      <PageHeader title={headerTitle} description={description}>
        {header}
        {/* <Link href="/orders/create">
          <Button>
            <Plus className="mr-2 h-4 w-4" />
            Create Order
          </Button>
        </Link> */}
      </PageHeader>
      <PageContent>{page}</PageContent>
    </AppLayout>
  );
}
