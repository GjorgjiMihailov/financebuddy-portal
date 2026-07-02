<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Handshake, Warehouse, Package, PackageCheck, Receipt } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const tabs = [
    { label: 'Кооперанти',     href: '/kontragenti',       icon: Handshake },
    { label: 'Магацини',       href: '/warehouses',         icon: Warehouse },
    { label: 'Артикли',        href: '/items',              icon: Package },
    { label: 'Влезни фактури', href: '/purchase-invoices',  icon: PackageCheck },
    { label: 'Излезни фактури',href: '/sales-invoices',     icon: Receipt },
];

function isActive(href: string): boolean {
    return currentUrl.value.startsWith(href);
}
</script>

<template>
    <div class="border-b bg-background">
        <nav class="-mb-px flex overflow-x-auto px-6">
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium whitespace-nowrap transition-colors"
                :class="isActive(tab.href)
                    ? 'border-primary text-foreground'
                    : 'border-transparent text-muted-foreground hover:border-border hover:text-foreground'"
            >
                <component :is="tab.icon" class="size-4" />
                {{ tab.label }}
            </Link>
        </nav>
    </div>
</template>