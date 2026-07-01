<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const page = usePage();
const { isCurrentOrParentUrl } = useCurrentUrl();

const roles = computed(() => (page.props.auth as any)?.user?.roles ?? []);
const isAdmin = computed(() => roles.value.includes('admin'));
const isAccountant = computed(() => roles.value.includes('accountant'));
const isCompanyAdmin = computed(() => roles.value.includes('company_admin'));
const isStaff = computed(() => isAdmin.value || isAccountant.value);

type Tab = { title: string; href: string };
type Section = { prefixes: string[]; tabs: Tab[] };

const sections = computed((): Section[] => {
    const list: Section[] = [
        {
            prefixes: ['/documents', '/journal-entries', '/reports'],
            tabs: isCompanyAdmin.value
                ? [
                    { title: 'Документи', href: '/documents' },
                    { title: 'Извештаи', href: '/reports' },
                  ]
                : [
                    { title: 'Документи', href: '/documents' },
                    { title: 'Книжења', href: '/journal-entries' },
                    { title: 'Извештаи', href: '/reports' },
                  ],
        },
    ];

    if (isStaff.value) {
        list.push({
            prefixes: ['/companies', '/warehouses', '/items', '/purchase-invoices', '/sales-invoices'],
            tabs: [
                { title: 'Компании', href: '/companies' },
                { title: 'Магацини', href: '/warehouses' },
                { title: 'Артикли', href: '/items' },
                { title: 'Влезни фактури', href: '/purchase-invoices' },
                { title: 'Излезни фактури', href: '/sales-invoices' },
            ],
        });
        list.push({
            prefixes: ['/employees', '/payroll', '/hr'],
            tabs: [
                { title: 'Вработени', href: '/employees' },
                { title: 'Плати', href: '/payroll' },
                { title: 'Човечки ресурси', href: '/hr' },
            ],
        });
        list.push({
            prefixes: ['/settings/accounts'],
            tabs: [
                { title: 'Конта', href: '/settings/accounts' },
            ],
        });
    }

    if (isAdmin.value) {
        list.push({
            prefixes: ['/users'],
            tabs: [{ title: 'Корисници', href: '/users' }],
        });
    }

    return list;
});

const currentTabs = computed((): Tab[] => {
    const url = page.url;
    const section = sections.value.find(s =>
        s.prefixes.some(p => url === p || url.startsWith(p + '/') || url.startsWith(p + '?'))
    );
    if (!section || section.tabs.length < 2) return [];
    return section.tabs;
});
</script>

<template>
    <nav
        v-if="currentTabs.length > 0"
        class="flex shrink-0 items-center gap-1 border-b px-4 md:px-4"
    >
        <Link
            v-for="tab in currentTabs"
            :key="tab.href"
            :href="tab.href"
            class="relative flex items-center px-3 py-3 text-sm font-medium transition-colors"
            :class="isCurrentOrParentUrl(tab.href)
                ? 'text-foreground after:absolute after:inset-x-0 after:bottom-0 after:h-[2px] after:bg-primary'
                : 'text-muted-foreground hover:text-foreground'"
        >
            {{ tab.title }}
        </Link>
    </nav>
</template>
