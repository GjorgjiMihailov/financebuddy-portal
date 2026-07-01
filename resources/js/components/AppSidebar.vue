<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FileText,
    BookMarked,
    Users,
    Settings2,
    UserCog,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();

const roles = computed(() => (page.props.auth as any)?.user?.roles ?? []);
const isAdmin = computed(() => roles.value.includes('admin'));
const isAccountant = computed(() => roles.value.includes('accountant'));
const isStaff = computed(() => isAdmin.value || isAccountant.value);

const mainNavItems: NavItem[] = [
    { title: 'Контролна табла', href: dashboard(), icon: LayoutGrid },
];

const sectionNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Финансии',
            href: '/documents',
            icon: FileText,
            isActive: ['/documents', '/journal-entries', '/reports'].some(p => isCurrentOrParentUrl(p)),
        },
    ];

    if (isStaff.value) {
        items.push({
            title: 'Материјално',
            href: '/companies',
            icon: BookMarked,
            isActive: ['/companies', '/warehouses', '/items', '/purchase-invoices', '/sales-invoices', '/kontragenti'].some(p => isCurrentOrParentUrl(p)),
        });
        items.push({
            title: 'Плати и ЧР',
            href: '/employees',
            icon: Users,
            isActive: ['/employees', '/payroll', '/hr'].some(p => isCurrentOrParentUrl(p)),
        });
        items.push({
            title: 'Подесувања',
            href: '/settings/accounts',
            icon: Settings2,
            isActive: isCurrentOrParentUrl('/settings'),
        });
    }

    if (isAdmin.value) {
        items.push({
            title: 'Администрација',
            href: '/users',
            icon: UserCog,
            isActive: isCurrentOrParentUrl('/users'),
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="sectionNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
