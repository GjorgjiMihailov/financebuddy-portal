<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { formatDate } from '@/lib/formatDate';
import { formatNumber } from '@/lib/formatNumber';
import ReportHeader from '@/components/reports/ReportHeader.vue';
import ReportToolbar from '@/components/reports/ReportToolbar.vue';
import EntitySearchSelect from '@/components/EntitySearchSelect.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Извештаи', href: '/reports' },
            { title: 'Аналитичка картица - фирма', href: '/reports/ledger-company' },
        ],
    },
});

type Row = {
    date: string; voucher: string | null; description: string | null; closing_reference: string | null;
    debit: number; credit: number; balance: number;
};
type Section = {
    code: string; name: string; opening: { debit: number; credit: number };
    rows: Row[]; total: { debit: number; credit: number };
};

const props = defineProps<{
    company: { id: number; name: string } | null;
    from: string;
    to: string;
    kontragentId: number | null;
    kontragent: { id: number; name: string } | null;
    sections: Section[] | null;
    grandTotal: { debit: number; credit: number } | null;
}>();

const from = ref(props.from);
const to = ref(props.to);
const kontragentId = ref<number | null>(props.kontragentId);
const kontragentLabel = ref(props.kontragent ? props.kontragent.name : '');

function onKontragentSelect(item: any) { kontragentId.value = item?.id ?? null; }

function submit() {
    router.get('/reports/ledger-company', {
        from: from.value,
        to: to.value,
        kontragent_id: kontragentId.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function fmt(n: number): string { return formatNumber(n); }
</script>

<template>
    <Head title="Аналитичка картица - фирма" />

    <div class="p-6 print:p-0">
        <ReportToolbar v-model:from="from" v-model:to="to" @submit="submit">
            <div class="w-64">
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Фирма</label>
                <EntitySearchSelect endpoint="/api/partners/search" :initial-label="kontragentLabel" placeholder="Име, ЕДБ" @select="onKontragentSelect" />
            </div>
        </ReportToolbar>

        <div class="overflow-x-auto rounded-lg border bg-white p-6 print:rounded-none print:border-0 print:p-2">
            <ReportHeader title="Аналитичка картица за фирма" :company="company" :from="from" :to="to" />

            <div v-if="!kontragent" class="py-12 text-center text-muted-foreground">
                Изберете фирма за да се генерира картицата.
            </div>

            <template v-else>
                <p class="mb-3 text-sm font-medium">Фирма {{ kontragent.name }}</p>

                <template v-for="section in sections ?? []" :key="section.code">
                    <table class="mb-4 w-full border-collapse text-xs">
                        <thead>
                            <tr class="bg-muted/40">
                                <th colspan="6" class="px-2 py-1 text-left font-semibold">{{ section.code }} &nbsp; {{ section.name }}</th>
                            </tr>
                            <tr class="border-b">
                                <th class="px-2 py-1 text-left font-semibold">Датум</th>
                                <th class="px-2 py-1 text-left font-semibold">Налог</th>
                                <th class="px-2 py-1 text-left font-semibold">Опис</th>
                                <th class="px-2 py-1 text-right font-semibold">Долгува</th>
                                <th class="px-2 py-1 text-right font-semibold">Побарува</th>
                                <th class="px-2 py-1 text-right font-semibold">Салдо</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="section.opening.debit !== 0 || section.opening.credit !== 0" class="border-b bg-muted/30 italic">
                                <td class="px-2 py-1" colspan="3">Почетно салдо</td>
                                <td class="px-2 py-1 text-right">{{ fmt(section.opening.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(section.opening.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(section.opening.debit - section.opening.credit) }}</td>
                            </tr>
                            <tr v-for="(r, i) in section.rows" :key="i" class="border-b border-dotted">
                                <td class="px-2 py-1">{{ formatDate(r.date) }}</td>
                                <td class="px-2 py-1 font-mono">{{ r.voucher ?? '—' }}</td>
                                <td class="px-2 py-1">
                                    {{ r.description ?? '—' }}
                                    <span v-if="r.closing_reference" class="text-muted-foreground"> &gt; {{ r.closing_reference }}</span>
                                </td>
                                <td class="px-2 py-1 text-right">{{ fmt(r.debit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(r.credit) }}</td>
                                <td class="px-2 py-1 text-right">{{ fmt(r.balance) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 font-semibold text-red-800">
                                <td colspan="3" class="px-2 py-2">Вкупно конто {{ section.code }}</td>
                                <td class="px-2 py-2 text-right">{{ fmt(section.total.debit) }}</td>
                                <td class="px-2 py-2 text-right">{{ fmt(section.total.credit) }}</td>
                                <td class="px-2 py-2 text-right">{{ fmt(section.total.debit - section.total.credit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </template>

                <div v-if="(sections ?? []).length === 0" class="py-12 text-center text-muted-foreground">
                    Нема промет за избраниот период
                </div>

                <table v-if="grandTotal" class="w-full border-collapse text-xs">
                    <tfoot>
                        <tr class="border-t-2 font-bold">
                            <td class="w-[calc(100%-3*8rem)] px-2 py-2">Вкупно:</td>
                            <td class="w-32 px-2 py-2 text-right">{{ fmt(grandTotal.debit) }}</td>
                            <td class="w-32 px-2 py-2 text-right">{{ fmt(grandTotal.credit) }}</td>
                            <td class="w-32 px-2 py-2 text-right">{{ fmt(grandTotal.debit - grandTotal.credit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </template>
        </div>
    </div>
</template>
