<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Package, Users, Warehouse } from '@lucide/vue';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

// Главно
const mainNavItems: NavItem[] = [
    {
        title: 'Контролна табла',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

// Материјално сметководство (placeholder - Фаза 1)
const materialNavItems: NavItem[] = [
    {
        title: 'Клиенти',
        href: '/companies',
        icon: Users,
    },
    {
        title: 'Магацини',
        href: '/warehouses',
        icon: Warehouse,
    },
    {
        title: 'Артикли',
        href: '/items',
        icon: Package,
    },
];
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
            <NavMain :items="mainNavItems" label="Главно" />
            <NavMain :items="materialNavItems" label="Материјално сметководство" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
