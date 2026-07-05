<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BarChart2, Building2, Layers, Sigma, BookOpen, FileText, Users } from '@lucide/vue';
import { Card, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Извештаи', href: '/reports' }],
    },
});

const reports = [
    {
        title: 'Бруто биланс',
        description: 'Почетно салдо, тековни промени и крајна состојба по конто.',
        href: '/reports/gross-balance',
        icon: BarChart2,
    },
    {
        title: 'Бруто биланс по фирми',
        description: 'Исто како бруто биланс, разбиено по партнер во секое конто.',
        href: '/reports/gross-balance-by-company',
        icon: Building2,
    },
    {
        title: 'Бруто биланс по синтетики',
        description: 'Збирно по синтетичка (група) сметка наместо по аналитичко конто.',
        href: '/reports/gross-balance-synthetic',
        icon: Layers,
    },
    {
        title: 'Кумулатив по аналитички конта и фирми',
        description: 'Вкупен промет (долгува/побарува/салдо) по конто и партнер за периодот.',
        href: '/reports/cumulative',
        icon: Sigma,
    },
    {
        title: 'Аналитичка картица - конто + фирма',
        description: 'Хронолошки промет на едно конто со еден конкретен партнер.',
        href: '/reports/ledger-account-company',
        icon: FileText,
    },
    {
        title: 'Аналитичка картица - конто',
        description: 'Хронолошки промет на едно конто низ сите партнери.',
        href: '/reports/ledger-account',
        icon: BookOpen,
    },
    {
        title: 'Аналитичка картица - фирма',
        description: 'Хронолошки промет на еден партнер низ сите конта.',
        href: '/reports/ledger-company',
        icon: Users,
    },
];
</script>

<template>
    <Head title="Извештаи" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Извештаи</h1>
            <p class="mt-0.5 text-sm text-muted-foreground">
                Бруто биланс, кумулатив и аналитички картици врз основа на прокнижени книговодствени налози.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link v-for="r in reports" :key="r.href" :href="r.href">
                <Card class="h-full transition-colors hover:border-primary/50 hover:bg-muted/30">
                    <CardHeader>
                        <div class="mb-2 flex size-10 items-center justify-center rounded-lg bg-muted">
                            <component :is="r.icon" class="size-5 text-muted-foreground" />
                        </div>
                        <CardTitle class="text-base">{{ r.title }}</CardTitle>
                        <CardDescription>{{ r.description }}</CardDescription>
                    </CardHeader>
                </Card>
            </Link>
        </div>
    </div>
</template>
