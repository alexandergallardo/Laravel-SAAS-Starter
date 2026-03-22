import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { resolveUrl } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';

// Map href patterns to tour data attributes
const getTourAttribute = (href: string): string | undefined => {
    if (href === '/usage') return 'nav-usage';
    if (href === '/dashboard') return 'nav-dashboard';
    return undefined;
};

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();
    const { t } = useTranslations();
    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{t('navigation.platform', 'Platform')}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => {
                    const tourAttr = getTourAttribute(item.href);
                    return (
                        <SidebarMenuItem key={item.title} {...(tourAttr ? { 'data-tour': tourAttr } : {})}>
                            <SidebarMenuButton
                                asChild
                                isActive={page.url.startsWith(
                                    resolveUrl(item.href),
                                )}
                                tooltip={{ children: item.title }}
                            >
                                <Link href={item.href} prefetch>
                                    {item.icon && <item.icon />}
                                    <span>{item.title}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
