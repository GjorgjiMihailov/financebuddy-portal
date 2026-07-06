<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    FileText,
    Package,
    Users,
    Settings2,
    Building2,
    UserCog,
    ChevronsUpDown,
    CalendarRange,
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
const isAdmin      = computed(() => roles.value.includes('admin'));
const isAccountant = computed(() => roles.value.includes('accountant'));
const isStaff      = computed(() => isAdmin.value || isAccountant.value);

const currentCompany = computed(() => (page.props as any).current_company as { id: number; name: string } | null);
const currentYear = computed(() => (page.props as any).current_year as number | null);

function switchCompany() {
    router.post('/clear-company');
}

function switchYear() {
    router.post('/clear-year');
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
const dashboardItems: NavItem[] = [
    { title: 'Контролна табла', href: dashboard(), icon: LayoutGrid },
];

// ── Финансии ──────────────────────────────────────────────────────────────────
const financesItems: NavItem[] = [
    {
        title: 'Финансии',
        href: '/documents',
        icon: FileText,
        isActive: ['/documents', '/journal-entries', '/reports'].some(p => isCurrentOrParentUrl(p)),
    },
];

// ── Материјално ────────────────────────────────────────────────────────────────
const materijalnoItems = computed<NavItem[]>(() => [{
    title: 'Материјално',
    href: '/kooperanti',
    icon: Package,
    isActive: ['/kooperanti', '/warehouses', '/items', '/purchase-invoices', '/sales-invoices']
        .some(p => isCurrentOrParentUrl(p)),
}]);

// ── Плати и ЧР ───────────────────────────────────────────────────────────────
const hrItems: NavItem[] = [
    {
        title: 'Плати и ЧР',
        href: '/employees',
        icon: Users,
        isActive: ['/employees', '/payroll', '/hr'].some(p => isCurrentOrParentUrl(p)),
    },
];

// ── Подесувања ───────────────────────────────────────────────────────────────
const settingsItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Подесувања',
            href: '/settings/accounts',
            icon: Settings2,
            isActive: isCurrentOrParentUrl('/settings'),
        },
    ];
    if (isAdmin.value) {
        items.push({
            title: 'Компании',
            href: '/companies',
            icon: Building2,
            isActive: isCurrentOrParentUrl('/companies'),
        });
    }
    return items;
});

// ── Администрација ────────────────────────────────────────────────────────────
const adminItems: NavItem[] = [
    {
        title: 'Корисници',
        href: '/users',
        icon: UserCog,
        isActive: isCurrentOrParentUrl('/users'),
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

        <!-- Company switcher — само за admin/accountant -->
        <div v-if="isStaff && currentCompany" class="mx-2 mb-1 rounded-lg border bg-sidebar-accent/40 px-3 py-2">
            <div class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Активен обврзник</div>
            <div class="mt-0.5 flex items-center justify-between gap-1">
                <span class="truncate text-sm font-medium">{{ currentCompany.name }}</span>
                <button
                    type="button"
                    @click="switchCompany"
                    class="ml-1 shrink-0 rounded p-0.5 text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-foreground"
                    title="Промени обврзник"
                >
                    <ChevronsUpDown class="size-3.5" />
                </button>
            </div>
        </div>

        <!-- Year switcher — само за admin/accountant -->
        <div v-if="isStaff && currentYear" class="mx-2 mb-1 rounded-lg border bg-sidebar-accent/40 px-3 py-2">
            <div class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">Работна година</div>
            <div class="mt-0.5 flex items-center justify-between gap-1">
                <span class="flex items-center gap-1.5 truncate text-sm font-medium">
                    <CalendarRange class="size-3.5 text-muted-foreground" />
                    {{ currentYear }}
                </span>
                <button
                    type="button"
                    @click="switchYear"
                    class="ml-1 shrink-0 rounded p-0.5 text-muted-foreground transition-colors hover:bg-sidebar-accent hover:text-foreground"
                    title="Промени година"
                >
                    <ChevronsUpDown class="size-3.5" />
                </button>
            </div>
        </div>

        <SidebarContent>
            <NavMain :items="dashboardItems" />
            <NavMain :items="financesItems" />
            <NavMain v-if="isStaff" :items="materijalnoItems" />
            <NavMain v-if="isStaff" :items="hrItems" />
            <NavMain v-if="isStaff" :items="settingsItems" label="Подесувања" />
            <NavMain v-if="isAdmin" :items="adminItems" label="Систем" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>