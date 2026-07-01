<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Printer } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Излезни фактури', href: '/sales-invoices' },
            { title: 'Преглед', href: '#' },
        ],
    },
});

type Line = {
    id: number;
    description: string;
    quantity: string;
    unit: string;
    unit_price: string;
    vat_rate: string;
    line_total_ex_vat: string;
    vat_amount: string;
    line_total_inc_vat: string;
};
type Invoice = {
    id: number;
    invoice_number: string;
    date: string;
    due_date: string | null;
    status: string;
    subtotal: string;
    vat_total: string;
    total_amount: string;
    notes: string | null;
    company: { id: number; name: string };
    kontragent: { id: number; name: string; edb: string; address: string | null } | null;
    client_name: string | null;
    creator: { id: number; name: string };
    lines: Line[];
};

defineProps<{ invoice: Invoice }>();

const STATUS_LABELS: Record<string, string> = {
    draft:  'Нацрт',
    sent:   'Испратена',
    booked: 'Книжена',
};

function fmt(v: string | number) {
    return Number(v).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtQty(v: string) {
    return parseFloat(v).toLocaleString('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
}
</script>

<template>
    <Head :title="`Фактура ${invoice.invoice_number}`" />

    <div class="p-6 print:p-0">

        <!-- Action bar (hidden on print) -->
        <div class="mb-6 flex items-center justify-between print:hidden">
            <Button variant="ghost" as-child>
                <Link href="/sales-invoices">
                    <ArrowLeft class="mr-2 size-4" />
                    Назад
                </Link>
            </Button>
            <Button variant="outline" @click="window.print()">
                <Printer class="mr-2 size-4" />
                Печати / PDF
            </Button>
        </div>

        <!-- A4 Invoice -->
        <div class="mx-auto max-w-3xl rounded-lg border bg-white p-10 shadow-sm print:shadow-none print:border-0">

            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div>
                    <p class="text-xs text-muted-foreground">Компанија — издавач</p>
                    <h2 class="text-xl font-bold">{{ invoice.company.name }}</h2>
                </div>
                <div class="text-right">
                    <h1 class="text-2xl font-bold text-primary">ФАКТУРА</h1>
                    <p class="mt-1 font-mono text-lg font-semibold">{{ invoice.invoice_number }}</p>
                    <Badge class="mt-1">{{ STATUS_LABELS[invoice.status] }}</Badge>
                </div>
            </div>

            <!-- Dates + Client -->
            <div class="mb-8 grid grid-cols-2 gap-8">
                <div class="rounded-lg bg-muted/40 p-4">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Клиент</p>
                    <template v-if="invoice.kontragent">
                        <p class="font-semibold">{{ invoice.kontragent.name }}</p>
                        <p class="text-sm text-muted-foreground">ЕДБ: {{ invoice.kontragent.edb }}</p>
                        <p v-if="invoice.kontragent.address" class="text-sm text-muted-foreground">{{ invoice.kontragent.address }}</p>
                    </template>
                    <p v-else class="font-semibold">{{ invoice.client_name ?? '—' }}</p>
                </div>
                <div class="rounded-lg bg-muted/40 p-4">
                    <div class="grid gap-1 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Датум на издавање:</span>
                            <span class="font-medium">{{ invoice.date }}</span>
                        </div>
                        <div v-if="invoice.due_date" class="flex justify-between">
                            <span class="text-muted-foreground">Датум на валута:</span>
                            <span class="font-medium">{{ invoice.due_date }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lines table -->
            <table class="mb-8 w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-muted-foreground/20">
                        <th class="pb-2 text-left font-semibold">Опис</th>
                        <th class="pb-2 text-right font-semibold">Кол.</th>
                        <th class="pb-2 text-left font-semibold">ЈМ</th>
                        <th class="pb-2 text-right font-semibold">Цена</th>
                        <th class="pb-2 text-right font-semibold">ДДВ%</th>
                        <th class="pb-2 text-right font-semibold">Вк. без ДДВ</th>
                        <th class="pb-2 text-right font-semibold">ДДВ</th>
                        <th class="pb-2 text-right font-semibold">Вкупно</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in invoice.lines" :key="line.id" class="border-b border-muted/40">
                        <td class="py-2 pr-4">{{ line.description }}</td>
                        <td class="py-2 text-right font-mono">{{ fmtQty(line.quantity) }}</td>
                        <td class="py-2 pl-1 text-muted-foreground">{{ line.unit }}</td>
                        <td class="py-2 text-right font-mono">{{ fmt(line.unit_price) }}</td>
                        <td class="py-2 text-right text-muted-foreground">{{ line.vat_rate }}%</td>
                        <td class="py-2 text-right font-mono">{{ fmt(line.line_total_ex_vat) }}</td>
                        <td class="py-2 text-right font-mono text-muted-foreground">{{ fmt(line.vat_amount) }}</td>
                        <td class="py-2 text-right font-mono font-semibold">{{ fmt(line.line_total_inc_vat) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="flex justify-end">
                <div class="w-72">
                    <div class="flex justify-between border-t py-1.5 text-sm">
                        <span class="text-muted-foreground">Вкупно без ДДВ:</span>
                        <span class="font-mono">{{ fmt(invoice.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between py-1.5 text-sm">
                        <span class="text-muted-foreground">ДДВ:</span>
                        <span class="font-mono">{{ fmt(invoice.vat_total) }}</span>
                    </div>
                    <div class="flex justify-between border-t-2 pt-2 text-base font-bold">
                        <span>ВКУПНО ЗА ПЛАЌАЊЕ:</span>
                        <span class="font-mono">{{ fmt(invoice.total_amount) }} MKD</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="invoice.notes" class="mt-8 border-t pt-4">
                <p class="text-xs font-medium text-muted-foreground">Напомена:</p>
                <p class="mt-1 text-sm">{{ invoice.notes }}</p>
            </div>

            <!-- Footer -->
            <div class="mt-12 flex justify-between border-t pt-4 text-xs text-muted-foreground">
                <span>Документот е изработен во системот FinanceBuddy.mk</span>
                <span>Составил: {{ invoice.creator.name }}</span>
            </div>

        </div>
    </div>
</template>
