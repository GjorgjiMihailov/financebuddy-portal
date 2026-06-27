<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, FileText, LayoutGrid, UserCog } from '@lucide/vue';
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.roles?.includes('admin') ?? false);

const mainNavItems: NavItem[] = [
    {
        title: 'Контролна табла',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const portalNavItems: NavItem[] = [
    {
        title: 'Компании',
        href: '/companies',
        icon: Building2,
    },
    {
        title: 'Документи',
        href: '/documents',
        icon: FileText,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Корисници',
        href: '/users',
        icon: UserCog,
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
            <NavMain :items="portalNavItems" label="Портал" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Администрација" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
