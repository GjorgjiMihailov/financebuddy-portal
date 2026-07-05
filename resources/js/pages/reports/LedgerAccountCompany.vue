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
            { title: 'Аналитичка картица - конто + фирма', href: '/reports/ledger-account-company' },
        ],
    },
});

type Row = {
    date: string; voucher: string | null; description: string | null; closing_reference: string | null;
    debit: number; credit: number; balance: number;
};

const props = defineProps<{
    company: { id: number; name: string } | null;
    from: string;
    to: string;
    accountCode: string | null;
    account: { code: string; name: string } | null;
    kontragentId: number | null;
    kontragent: { id: number; name: string } | null;
    opening: { debit: number; credit: number } | null;
    rows: Row[] | null;
    total: { debit: number; credit: number } | null;
}>();

const from = ref(props.from);
const to = ref(props.to);
const accountCode = ref(props.accountCode ?? '');
const accountLabel = ref(props.account ? `${props.account.code} — ${props.account.name}` : '');
const kontragentId = ref<number | null>(props.kontragentId);
const kontragentLabel = ref(props.kontragent ? props.kontragent.name : '');

function onAccountSelect(item: any) { accountCode.value = item?.code ?? ''; }
function onKontragentSelect(item: any) { kontragentId.value = item?.id ?? null; }

function submit() {
    router.get('/reports/ledger-account-company', {
        from: from.value,
        to: to.value,
        account_code: accountCode.value || undefined,
        kontragent_id: kontragentId.value || undefined,
    }, { preserveState: true, preserveScroll: true });
}

function fmt(n: number): string { return formatNumber(n); }
</script>

<template>
    <Head title="Аналитичка картица - конто + фирма" />

    <div class="p-6 print:p-0">
        <ReportToolbar v-model:from="from" v-model:to="to" @submit="submit">
            <div class="w-56">
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Конто</label>
                <EntitySearchSelect endpoint="/api/accounts/search" :initial-label="accountLabel" placeholder="Код или име" @select="onAccountSelect" />
            </div>
            <div class="w-56">
                <label class="mb-1 block text-xs font-medium text-muted-foreground">Фирма</label>
                <EntitySearchSelect endpoint="/api/partners/search" :initial-label="kontragentLabel" placeholder="Име, ЕДБ" @select="onKontragentSelect" />
            </div>
        </ReportToolbar>

        <div class="overflow-x-auto rounded-lg border bg-white p-6 print:rounded-none print:border-0 print:p-2">
            <ReportHeader title="Аналитичка картица" :company="company" :from="from" :to="to" />

            <div v-if="!account || !kontragent" class="py-12 text-center text-muted-foreground">
                Изберете конто и фирма за да се генерира картицата.
            </div>

            <template v-else>
                <p class="mb-3 text-sm font-medium">
                    {{ account.code }} &nbsp;{{ account.name }} &mdash; {{ kontragent.name }}
                </p>

                <table class="w-full border-collapse text-xs">
                    <thead>
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
                        <tr v-if="opening" class="border-b bg-muted/30 italic">
                            <td class="px-2 py-1" colspan="3">Почетно салдо</td>
                            <td class="px-2 py-1 text-right">{{ fmt(opening.debit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(opening.credit) }}</td>
                            <td class="px-2 py-1 text-right">{{ fmt(opening.debit - opening.credit) }}</td>
                        </tr>
                        <tr v-for="(r, i) in rows ?? []" :key="i" class="border-b border-dotted">
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
                        <tr v-if="(rows ?? []).length === 0">
                            <td colspan="6" class="py-12 text-center text-muted-foreground">Нема промет за избраниот период</td>
                        </tr>
                    </tbody>
                    <tfoot v-if="total">
                        <tr class="border-t-2 font-bold">
                            <td colspan="3" class="px-2 py-2">Вкупно:</td>
                            <td class="px-2 py-2 text-right">{{ fmt(total.debit) }}</td>
                            <td class="px-2 py-2 text-right">{{ fmt(total.credit) }}</td>
                            <td class="px-2 py-2 text-right">{{ fmt(total.debit - total.credit) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </template>
        </div>
    </div>
</template>
