<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FileText,
    BookOpen,
    BarChart2,
    Warehouse,
    Package,
    FileInput,
    FileOutput,
    Users,
    DollarSign,
    UserCheck,
    Settings2,
    UserCog,
    BookMarked,
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
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const roles = computed(() => page.props.auth?.user?.roles ?? []);
const isAdmin = computed(() => roles.value.includes('admin'));
const isAccountant = computed(() => roles.value.includes('accountant'));
const isCompanyAdmin = computed(() => roles.value.includes('company_admin'));
const isStaff = computed(() => isAdmin.value || isAccountant.value);

const mainNavItems: NavItem[] = [
    { title: 'Контролна табла', href: dashboard(), icon: LayoutGrid },
];

const financeNavItems = computed<NavItem[]>(() => {
    if (isCompanyAdmin.value) {
        return [
            { title: 'Документи', href: '/documents', icon: FileText },
            { title: 'Извештаи', href: '/reports', icon: BarChart2 },
        ];
    }
    return [
        { title: 'Документи', href: '/documents', icon: FileText },
        { title: 'Книжења', href: '/journal-entries', icon: BookOpen },
        { title: 'Извештаи', href: '/reports', icon: BarChart2 },
    ];
});

const materialNavItems: NavItem[] = [
    { title: 'Компании', href: '/companies', icon: BookMarked },
    { title: 'Магацини', href: '/warehouses', icon: Warehouse },
    { title: 'Артикли', href: '/items', icon: Package },
    { title: 'Влезни фактури', href: '/purchase-invoices', icon: FileInput },
    { title: 'Излезни фактури', href: '/sales-invoices', icon: FileOutput },
];

const hrNavItems: NavItem[] = [
    { title: 'Вработени', href: '/employees', icon: Users },
    { title: 'Плати', href: '/payroll', icon: DollarSign },
    { title: 'Човечки ресурси', href: '/hr', icon: UserCheck },
];

const settingsNavItems: NavItem[] = [
    { title: 'Конта', href: '/settings/accounts', icon: Settings2 },
];

const adminNavItems: NavItem[] = [
    { title: 'Корисници', href: '/users', icon: UserCog },
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

            <NavMain :items="financeNavItems" label="Финансии" />

            <NavMain v-if="isStaff" :items="materialNavItems" label="Материјално работење" />

            <NavMain v-if="isStaff" :items="hrNavItems" label="Плати и ЧР" />

            <NavMain v-if="isStaff" :items="settingsNavItems" label="Подесувања" />

            <NavMain v-if="isAdmin" :items="adminNavItems" label="Администрација" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
