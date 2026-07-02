<script setup lang="ts">
import { Head, Link } from "@inertiajs/vue3";
import { ArrowLeft, Printer } from "@lucide/vue";
import { formatDate } from '@/lib/formatDate';
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";

defineOptions({
    layout: {
        breadcrumbs: [
            { title: "Влезни фактури", href: "/purchase-invoices" },
            { title: "Преглед", href: "#" },
        ],
    },
});

type InvoiceLine = {
    id: number;
    description: string;
    quantity: string;
    unit: string;
    unit_price: string;
    vat_rate: string;
    line_total_ex_vat: string;
    vat_amount: string;
    line_total_inc_vat: string;
    item: { code: string; name: string } | null;
};
type Invoice = {
    id: number;
    invoice_number: string;
    date: string;
    due_date: string | null;
    status: string;
    notes: string | null;
    subtotal: string;
    vat_total: string;
    total_amount: string;
    supplier_name: string | null;
    company: { name: string };
    kontragent: { name: string; edb: string; address: string | null } | null;
    creator: { name: string };
    lines: InvoiceLine[];
};

const props = defineProps<{ invoice: Invoice }>();

const STATUS_LABELS: Record<string, string> = { draft: "Нацрт", booked: "Книжена" };
const STATUS_VARIANT: Record<string, "default" | "secondary" | "outline"> = { draft: "outline", booked: "secondary" };

function fmt(v: string | number) {
    return Number(v).toLocaleString("mk-MK", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function printPage() { window.print(); }
</script>

<template>
    <Head :title="`Влезна фактура ${invoice.invoice_number}`" />

    <!-- Action bar (hidden on print) -->
    <div class="flex items-center justify-between gap-3 p-6 print:hidden">
        <Button variant="outline" as-child>
            <Link href="/purchase-invoices"><ArrowLeft class="mr-2 size-4" />Назад</Link>
        </Button>
        <div class="flex items-center gap-2">
            <Badge :variant="STATUS_VARIANT[invoice.status]">{{ STATUS_LABELS[invoice.status] }}</Badge>
            <Button @click="printPage">
                <Printer class="mr-2 size-4" />Печати / PDF
            </Button>
        </div>
    </div>

    <!-- A4 print area -->
    <div class="mx-auto max-w-4xl px-6 pb-12 print:p-0">
        <div class="rounded-xl border bg-white p-10 shadow-sm print:rounded-none print:border-0 print:shadow-none">

            <!-- Header -->
            <div class="mb-8 flex items-start justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ invoice.company.name }}</h1>
                    <p class="mt-1 text-sm text-gray-500">portal.financebuddy.mk</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium uppercase tracking-wider text-gray-400">Влезна фактура</p>
                    <p class="mt-1 text-2xl font-bold font-mono text-gray-900">{{ invoice.invoice_number }}</p>
                    <Badge :variant="STATUS_VARIANT[invoice.status]" class="mt-1 print:hidden">{{ STATUS_LABELS[invoice.status] }}</Badge>
                </div>
            </div>

            <!-- From / To / Dates -->
            <div class="mb-8 grid grid-cols-3 gap-6">
                <div class="col-span-1 rounded-lg bg-gray-50 p-4 print:border print:bg-transparent">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Добавувач</p>
                    <p class="font-semibold text-gray-900">
                        {{ invoice.kontragent?.name ?? invoice.supplier_name ?? "—" }}
                    </p>
                    <p v-if="invoice.kontragent?.edb" class="mt-0.5 text-sm text-gray-500">ЕДБ: {{ invoice.kontragent.edb }}</p>
                    <p v-if="invoice.kontragent?.address" class="mt-0.5 text-sm text-gray-500">{{ invoice.kontragent.address }}</p>
                </div>
                <div class="col-span-1 rounded-lg bg-gray-50 p-4 print:border print:bg-transparent">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Примач</p>
                    <p class="font-semibold text-gray-900">{{ invoice.company.name }}</p>
                </div>
                <div class="col-span-1 rounded-lg bg-gray-50 p-4 print:border print:bg-transparent">
                    <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Датуми</p>
                    <div class="grid grid-cols-[auto_1fr] items-center gap-x-2 gap-y-1 text-sm">
                        <span class="text-gray-500">Датум:</span><span class="font-medium">{{ formatDate(invoice.date) }}</span>
                        <span v-if="invoice.due_date" class="text-gray-500">Валута:</span>
                        <span v-if="invoice.due_date" class="font-medium">{{ formatDate(invoice.due_date) }}</span>
                    </div>
                </div>
            </div>

            <!-- Lines table -->
            <table class="mb-8 w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">#</th>
                        <th class="pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">Опис</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Кол.</th>
                        <th class="pb-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-400">ЈМ</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Цена</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">ДДВ%</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Вк. без ДДВ</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">ДДВ</th>
                        <th class="pb-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-400">Вк. со ДДВ</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(line, idx) in invoice.lines" :key="line.id" class="border-b border-gray-100">
                        <td class="py-3 pr-2 text-gray-400">{{ idx + 1 }}</td>
                        <td class="py-3 pr-4">
                            <div class="font-medium text-gray-900">{{ line.description }}</div>
                            <div v-if="line.item" class="text-xs text-gray-400">{{ line.item.code }}</div>
                        </td>
                        <td class="py-3 text-right font-mono">{{ fmt(line.quantity) }}</td>
                        <td class="py-3 pl-2 text-gray-500">{{ line.unit }}</td>
                        <td class="py-3 text-right font-mono">{{ fmt(line.unit_price) }}</td>
                        <td class="py-3 text-right text-gray-500">{{ line.vat_rate }}%</td>
                        <td class="py-3 text-right font-mono">{{ fmt(line.line_total_ex_vat) }}</td>
                        <td class="py-3 text-right font-mono text-gray-500">{{ fmt(line.vat_amount) }}</td>
                        <td class="py-3 text-right font-mono font-semibold">{{ fmt(line.line_total_inc_vat) }}</td>
                    </tr>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="mb-8 flex justify-end">
                <div class="w-80">
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-500">Вкупно без ДДВ:</span>
                        <span class="font-mono font-medium">{{ fmt(invoice.subtotal) }} MKD</span>
                    </div>
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-gray-500">ДДВ:</span>
                        <span class="font-mono font-medium">{{ fmt(invoice.vat_total) }} MKD</span>
                    </div>
                    <div class="mt-1 flex justify-between rounded-lg bg-gray-900 px-4 py-3 text-white print:rounded-none print:border print:border-gray-900 print:bg-transparent print:text-gray-900">
                        <span class="font-semibold">ВКУПНО ЗА ПЛАЌАЊЕ:</span>
                        <span class="font-mono text-lg font-bold">{{ fmt(invoice.total_amount) }} MKD</span>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div v-if="invoice.notes" class="mb-8 rounded-lg bg-gray-50 p-4 text-sm text-gray-700 print:border print:bg-transparent">
                <p class="mb-1 text-xs font-semibold uppercase tracking-wider text-gray-400">Белешка</p>
                <p>{{ invoice.notes }}</p>
            </div>

            <!-- Footer -->
            <div class="mt-12 flex items-end justify-between border-t pt-6 text-xs text-gray-400">
                <div>
                    <p>Составил: {{ invoice.creator.name }}</p>
                    <p>Датум на издавање: {{ formatDate(invoice.date) }}</p>
                </div>
                <div class="text-right">
                    <p>{{ invoice.company.name }}</p>
                    <p>portal.financebuddy.mk</p>
                </div>
            </div>

        </div>
    </div>
</template>
