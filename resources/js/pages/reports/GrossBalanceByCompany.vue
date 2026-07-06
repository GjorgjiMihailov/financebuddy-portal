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
            { title: 'Бруто биланс по фирми', href: '/reports/gross-balance-by-company' },
        ],
    },
});

type Bucket = { debit: number; credit: number; balance: number };
type CompanyRow = { kooperant_id: number | null; name: string; opening: Bucket; period: Bucket; closing: Bucket };
type Account = {
    code: string; name: string; class: number; companies: CompanyRow[];
    opening: Bucket; period: Bucket; closing: Bucket;
};
type ClassGroup = { class: number; accounts: Account[]; total: { opening: Bucket; period: Bucket; closing: Bucket } };

const props = defineProps<{
    company: { id: number; name: string } | null;
    from: string;
    to: string;
    classes: ClassGroup[];
    grandTotal: { opening: Bucket; period: Bucket; closing: Bucket };
}>();

const from = ref(props.from);
const to = ref(props.to);

function submit() {
    router.get('/reports/gross-balance-by-company', { from: from.value, to: to.value }, { preserveState: true, preserveScroll: true });
}

function fmt(n: number): string { return formatNumber(n); }
</script>

<template>
    <Head title="Бруто биланс по фирми" />

    <div class="p-6 print:p-0">
        <ReportToolbar v-model:from="from" v-model:to="to" @submit="submit" />

        <div class="overflow-x-auto rounded-lg border bg-white p-6 print:rounded-none print:border-0 print:p-2">
            <ReportHeader title="Бруто Биланс по фирми" :company="company" :from="from" :to="to" />

            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr class="border-b">
                        <th rowspan="2" class="px-2 py-1 text-left align-bottom font-semibold">Име на фирма</th>
                        <th rowspan="2" class="px-2 py-1 text-left align-bottom font-semibold">Конто</th>
                        <th colspan="3" class="border-b px-2 py-1 text-center font-semibold">Почетно салдо</th>
                        <th colspan="3" class="border-b px-2 py-1 text-center font-semibold">Тековни промени</th>
                        <th colspan="3" class="border-b px-2 py-1 text-center font-semibold">Крајна состојба</th>
                    </tr>
                    <tr class="border-b">
                        <th class="px-2 py-1 text-right font-semibold">Долгува</th>
                        <th class="px-2 py-1 text-right font-semibold">Побарува</th>
                        <th class="px-2 py-1 text-right font-semibold">Салдо</th>
                        <th class="px-2 py-1 text-right font-semibold">Долгува</th>
                        <th class="px-2 py-1 text-right font-semibold">Побарува</th>
                        <th class="px-2 py-1 text-right font-semibold">Салдо</th>
                        <th class="px-2 py-1 text-right font-semibold">Долгува</th>
                        <th class="px-2 py-1 text-right font-semibold">Побарува</th>
                        <th class="px-2 py-1 text-right font-semibold">Салдо</th>
                    </tr>
                </thead>
                <tbody>
                    <template v-for="group in classes" :key="group.class">
                        <template v-for="acc in group.accounts" :key="acc.code">
                            <tr class="bg-muted/40">
                                <td colspan="11" class="px-2 py-1 font-semibold">{{ acc.code }} &nbsp; {{ acc.name }}</td>
                            </tr>
                            <tr v-for="c in acc.companies" :key="c.kooperant_id ?? 0" class="border-b border-dotted">
                                <td class="px-2 py-1">{{ c.name }}</td>
                                <td class="px-2 py-1"></td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.opening.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.opening.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.opening.balance) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.period.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.period.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.period.balance) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.closing.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.closing.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(c.closing.balance) }}</td>
                            </tr>
                            <tr class="border-b font-semibold text-red-800">
                                <td colspan="2" class="px-2 py-1">Меѓузбир: {{ acc.code }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.opening.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.opening.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.opening.balance) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.period.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.period.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.period.balance) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.closing.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.closing.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(acc.closing.balance) }}</td>
                            </tr>
                        </template>

                        <tr class="border-b-2 font-semibold text-red-800">
                            <td colspan="2" class="px-2 py-1">Вкупно за група: {{ group.class }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.opening.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.opening.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.opening.balance) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.period.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.period.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.period.balance) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.closing.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.closing.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(group.total.closing.balance) }}</td>
                        </tr>
                    </template>

                    <tr v-if="classes.length === 0">
                        <td colspan="11" class="py-12 text-center text-muted-foreground">Нема податоци за избраниот период</td>
                    </tr>
                </tbody>
                <tfoot v-if="classes.length > 0">
                    <tr class="border-t-2 font-bold">
                        <td colspan="2" class="px-2 py-2">Вкупно:</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.opening.debit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.opening.credit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.opening.balance) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.period.debit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.period.credit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.period.balance) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.closing.debit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.closing.credit) }}</td>
                        <td class="px-2 py-2 text-right">{{ fmt(grandTotal.closing.balance) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
