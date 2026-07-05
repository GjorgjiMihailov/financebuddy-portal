<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatNumber } from '@/lib/formatNumber';
import ReportHeader from '@/components/reports/ReportHeader.vue';
import ReportToolbar from '@/components/reports/ReportToolbar.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Извештаи', href: '/reports' },
            { title: 'Кумулатив', href: '/reports/cumulative' },
        ],
    },
});

type CompanyRow = { kontragent_id: number | null; name: string; debit: number; credit: number; balance: number };
type Account = { code: string; name: string; companies: CompanyRow[]; debit: number; credit: number; balance: number };

const props = defineProps<{
    company: { id: number; name: string } | null;
    from: string;
    to: string;
    accounts: Account[];
    grandTotal: { debit: number; credit: number };
}>();

const from = ref(props.from);
const to = ref(props.to);

function submit() {
    router.get('/reports/cumulative', { from: from.value, to: to.value }, { preserveState: true, preserveScroll: true });
}

function fmt(n: number): string { return formatNumber(n); }
</script>

<template>
    <Head title="Кумулатив по аналитички конта и фирми" />

    <div class="p-6 print:p-0">
        <ReportToolbar v-model:from="from" v-model:to="to" @submit="submit" />

        <div class="overflow-x-auto rounded-lg border bg-white p-6 print:rounded-none print:border-0 print:p-2">
            <ReportHeader title="Кумулатив по аналитички конта и фирми" :company="company" :from="from" :to="to" />

            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b">
                        <th class="px-2 py-1 text-left font-semibold">Фирма</th>
                        <th class="px-2 py-1 text-left font-semibold">Име на фирма</th>
                        <th class="px-2 py-1 text-right font-semibold">Долгува</th>
                        <th class="px-2 py-1 text-right font-semibold">Побарува</th>
                        <th class="px-2 py-1 text-right font-semibold">Салдо</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="acc in accounts" :key="acc.code">
                        <tr class="bg-muted/40">
                            <td colspan="5" class="px-2 py-1 font-semibold">{{ acc.code }} &nbsp; {{ acc.name }}</td>
                        </tr>
                        <tr v-for="c in acc.companies" :key="c.kontragent_id ?? 0" class="border-b border-dotted">
                            <td class="px-2 py-1">{{ c.kontragent_id ?? 0 }}</td>
                            <td class="px-2 py-1">{{ c.name }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(c.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(c.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(c.balance) }}</td>
                        </tr>
                        <tr class="border-b-2 font-semibold text-red-800">
                            <td colspan="2" class="px-2 py-1">Вкупно {{ acc.code }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(acc.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(acc.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(acc.balance) }}</td>
                        </tr>
                    </template>

                    <tr v-if="accounts.length === 0">
                        <td colspan="5" class="py-12 text-center text-muted-foreground">Нема податоци за избраниот период</td>
                    </tr>
                </tbody>
                <tfoot v-if="accounts.length > 0">
                    <tr class="border-t-2 font-bold">
                        <td colspan="2" class="px-2 py-2">Вкупно:</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.debit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.credit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.debit - grandTotal.credit) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
