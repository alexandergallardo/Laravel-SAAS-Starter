import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/hooks/use-translations';
import { cn, isSameUrl, resolveUrl } from '@/lib/utils';
import { edit } from '@/routes/profile';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Cookie,
    Fingerprint,
    History,
    Link2,
    Lock,
    Monitor,
    Shield,
    ShieldCheck,
    Ticket,
    User,
} from 'lucide-react';
import { type PropsWithChildren, useMemo } from 'react';
import { type SharedData } from '@/types';

interface NavSection {
    title: string;
    items: NavItem[];
}

const getNavSections = (t: (key: string, fallback: string) => string): NavSection[] => [
    {
        title: t('navigation.profile_section', 'Profile'),
        items: [
            { title: t('navigation.general', 'General'), href: edit(), icon: User },
            { title: t('navigation.connected_accounts', 'Connected Accounts'), href: '/settings/connected-accounts', icon: Link2 },
        ],
    },
    {
        title: t('navigation.security_section', 'Security'),
        items: [
            { title: t('navigation.authentication', 'Authentication'), href: '/settings/security/authentication', icon: Shield },
            { title: t('navigation.sessions', 'Sessions'), href: '/settings/sessions', icon: Monitor },
            { title: t('navigation.login_history', 'Login History'), href: '/settings/login-history', icon: History },
        ],
    },
    {
        title: t('navigation.privacy_section', 'Privacy'),
        items: [
            { title: t('navigation.cookies', 'Cookies'), href: '/settings/privacy', icon: Cookie },
            { title: t('navigation.notifications', 'Notifications'), href: '/settings/notifications', icon: Bell },
            { title: t('navigation.api_tokens', 'API Tokens'), href: '/settings/api-tokens', icon: Fingerprint },
        ],
    },
    {
        title: t('navigation.support_section', 'Support'),
        items: [
            { title: t('navigation.support_tickets', 'Support Tickets'), href: '/settings/tickets', icon: Ticket },
        ],
    },
];

interface ProfileLayoutProps extends PropsWithChildren {
    title?: string;
    description?: string;
    fullWidth?: boolean;
}

export default function ProfileLayout({
    children,
    title,
    description,
    fullWidth = false,
}: ProfileLayoutProps) {
    const { t, i18n } = useTranslations();
    const navSections = useMemo(() => getNavSections(t), [t]);

    const defaultTitle = title ?? t('settings.profile.title', 'Profile Settings');
    const defaultDescription = description ?? t('settings.profile.description', 'Manage your personal account settings');

    if (typeof window === 'undefined') {
        return null;
    }

    const currentPath = window.location.pathname;
    const RTL_LANGUAGES = ['ar', 'he', 'fa', 'ur'];
    const isRTL = RTL_LANGUAGES.includes(i18n.language);

    return (
        <div className="px-4 py-6" dir={isRTL ? 'rtl' : 'ltr'}>
            <Heading title={defaultTitle} description={defaultDescription} />

            <div className={cn('flex flex-col lg:flex-row lg:gap-12 settings-layout-container', {
                'lg:flex-row-reverse': isRTL,
            })} data-rtl={isRTL}>
                <aside className={cn('w-full max-w-xl lg:w-48 settings-layout-sidebar', {
                    'lg:order-2': isRTL,
                })} data-rtl={isRTL}>
                    <nav className="flex flex-col space-y-6">
                        {navSections.map((section) => (
                            <div key={section.title}>
                                <h4 className="mb-2 px-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase">
                                    {section.title}
                                </h4>
                                <div className="flex flex-col space-y-1">
                                    {section.items.map((item, index) => (
                                        <Button
                                            key={`${resolveUrl(item.href)}-${index}`}
                                            size="sm"
                                            variant="ghost"
                                            asChild
                                            className={cn(
                                                'w-full justify-start',
                                                {
                                                    'bg-muted': isSameUrl(
                                                        currentPath,
                                                        item.href,
                                                    ),
                                                },
                                            )}
                                        >
                                            <Link href={item.href}>
                                                {item.icon && (
                                                    <item.icon className="h-4 w-4" />
                                                )}
                                                {item.title}
                                            </Link>
                                        </Button>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className={cn('flex-1 settings-layout-content', {
                    'md:max-w-2xl': !fullWidth,
                    'lg:order-1': isRTL,
                })} data-rtl={isRTL}>
                    <section className={cn('space-y-12', {
                        'max-w-xl': !fullWidth,
                        'w-full': fullWidth,
                    })}>
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
