<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Printer, Send, BookCheck, AlertTriangle, Trash2, Loader2 } from '@lucide/vue';
import { formatDate } from '@/lib/formatDate';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { ref, computed } from 'vue';

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
    item: { id: number; code: string; name: string; is_service: boolean } | null;
};
type Company = {
    id: number; name: string; tax_id: string; embs: string | null;
    vat_number: string | null; is_vat_registered: boolean;
    address: string | null; phone: string | null; email: string | null;
    bank_name: string | null; bank_account: string | null;
    logo_path: string | null;
};
type Invoice = {
    id: number;
    invoice_number: string;
    date: string;
    date_of_supply: string | null;
    due_date: string | null;
    status: string;
    subtotal: string;
    vat_total: string;
    total_amount: string;
    currency: string;
    exchange_rate: string | null;
    notes: string | null;
    warehouse: { id: number; name: string } | null;
    company: Company;
    kontragent: { id: number; name: string; edb: string; address: string | null } | null;
    client_name: string | null;
    creator: { id: number; name: string };
    lines: Line[];
};

const props = defineProps<{ invoice: Invoice }>();

const STATUS_LABELS: Record<string, string> = {
    draft: 'Нацрт', sent: 'Испратена', booked: 'Книжена',
};

const sending = ref(false);

function send() {
    if (!confirm('Испрати ја фактурата и намали ги залихите?')) return;
    sending.value = true;
    router.post(`/sales-invoices/${props.invoice.id}/send`, {}, {
        onFinish: () => { sending.value = false; },
    });
}

// ── Booking dialog ────────────────────────────────────────────────────────────
const showBook       = ref(false);
const bookEntryId    = ref<string>('');
const bookEntries    = ref<{ id: number; label: string; sequence_number: number }[]>([]);
const loadingBook    = ref(false);
const submittingBook = ref(false);

async function openBookDialog() {
    bookEntryId.value = '';
    bookEntries.value = [];
    showBook.value    = true;
    loadingBook.value = true;
    try {
        const d = new Date(props.invoice.date);
        const year = d.getFullYear();
        const month = d.getMonth() + 1;
        const res = await fetch(`/api/journal-entries/for-booking?group_code=30&year=${year}&month=${month}`);
        if (res.ok) {
            const data = await res.json();
            bookEntries.value = data.entries;
            bookEntryId.value = data.suggestedId ? String(data.suggestedId) : '';
        }
    } finally {
        loadingBook.value = false;
    }
}

function submitBook() {
    submittingBook.value = true;
    const payload = bookEntryId.value ? { journal_entry_id: parseInt(bookEntryId.value) } : {};
    router.post(`/sales-invoices/${props.invoice.id}/book`, payload, {
        onSuccess: () => { showBook.value = false; },
        onFinish: () => { submittingBook.value = false; },
    });
}

function fmt(v: string | number) {
    return Number(v).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtQty(v: string) {
    return parseFloat(v).toLocaleString('mk-MK', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
}

const cur = props.invoice.currency ?? 'MKD';

function printPage() { window.print(); }
const rate = props.invoice.exchange_rate ? parseFloat(props.invoice.exchange_rate) : null;

const page = usePage();
const isAdmin = computed(() => (page.props.auth as any)?.user?.roles?.includes('admin') ?? false);

function deleteInvoice() {
    if (!confirm(`Избриши излезна фактура ${props.invoice.invoice_number}?`)) return;
    router.delete(`/sales-invoices/${props.invoice.id}`);
}
</script>

<template>
    <Head :title="`Фактура ${invoice.invoice_number}`" />

    <div class="p-6 print:p-0">

        <!-- Action bar (hidden on print) -->
        <div class="mb-6 flex items-center justify-between print:hidden">
            <Button variant="ghost" as-child>
                <Link href="/sales-invoices">
                    <ArrowLeft class="mr-2 size-4" />Назад
                </Link>
            </Button>
            <div class="flex items-center gap-2">
                <Badge>{{ STATUS_LABELS[invoice.status] }}</Badge>

                <Button
                    v-if="invoice.status === 'draft'"
                    variant="default"
                    :disabled="sending"
                    @click="send"
                >
                    <Send class="mr-2 size-4" />
                    {{ sending ? 'Испраќање…' : 'Испрати' }}
                </Button>
                <Button
                    v-if="invoice.status === 'sent'"
                    variant="secondary"
                    @click="openBookDialog"
                >
                    <BookCheck class="mr-2 size-4" />Книжи
                </Button>
                <Button variant="outline" @click="printPage">
                    <Printer class="mr-2 size-4" />Печати / PDF
                </Button>
                <Button v-if="isAdmin" variant="destructive" @click="deleteInvoice">
                    <Trash2 class="mr-2 size-4" />Бриши
                </Button>
            </div>
        </div>

        <!-- A4 Invoice -->
        <div class="mx-auto max-w-4xl rounded-lg border bg-white shadow-sm print:shadow-none print:border-0 print:max-w-none print:rounded-none">
            <div class="p-10 print:p-8">

                <!-- ═══ HEADER: Logo (left) + Invoice number (right) ═══ -->
                <div class="mb-8 flex items-start justify-between">
                    <!-- Logo + Issuer -->
                    <div class="flex items-start gap-4">
                        <div v-if="invoice.company.logo_path" class="flex-shrink-0">
                            <img :src="`/storage/${invoice.company.logo_path}`" alt="Лого" class="h-16 w-auto object-contain" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">{{ invoice.company.name }}</h2>
                            <p v-if="invoice.company.address" class="mt-0.5 text-sm text-gray-600">{{ invoice.company.address }}</p>
                            <p class="mt-0.5 text-sm text-gray-600">ЕДБ: {{ invoice.company.tax_id }}</p>
                            <p v-if="invoice.company.embs" class="text-sm text-gray-600">ЕМБС: {{ invoice.company.embs }}</p>
                            <p v-if="invoice.company.vat_number" class="text-sm text-gray-600">ДДВ бр: {{ invoice.company.vat_number }}</p>
                            <p v-if="invoice.company.bank_name || invoice.company.bank_account" class="mt-1 text-sm text-gray-600">
                                <span v-if="invoice.company.bank_name">{{ invoice.company.bank_name }}</span>
                                <span v-if="invoice.company.bank_name && invoice.company.bank_account"> · </span>
                                <span v-if="invoice.company.bank_account" class="font-mono">{{ invoice.company.bank_account }}</span>
                            </p>
                        </div>
                    </div>

                    <!-- Invoice number block -->
                    <div class="text-right">
                        <h1 class="text-3xl font-bold tracking-wide text-gray-900">ФАКТУРА</h1>
                        <p class="mt-1 font-mono text-xl font-semibold text-primary">{{ invoice.invoice_number }}</p>
                        <div class="mt-3 grid gap-0.5 text-sm text-gray-600">
                            <div class="flex items-center justify-end gap-2">
                                <span class="text-gray-500">Датум на издавање:</span>
                                <span class="font-medium text-gray-800">{{ formatDate(invoice.date) }}</span>
                            </div>
                            <div v-if="invoice.date_of_supply" class="flex items-center justify-end gap-2">
                                <span class="text-gray-500">Датум на промет:</span>
                                <span class="font-medium text-gray-800">{{ formatDate(invoice.date_of_supply) }}</span>
                            </div>
                            <div v-if="invoice.due_date" class="flex items-center justify-end gap-2">
                                <span class="text-gray-500">Датум на валута:</span>
                                <span class="font-medium text-gray-800">{{ formatDate(invoice.due_date) }}</span>
                            </div>
                            <div v-if="cur !== 'MKD'" class="flex items-center justify-end gap-2">
                                <span class="text-gray-500">Валута:</span>
                                <span class="font-medium text-gray-800">{{ cur }}<span v-if="rate"> · Курс: {{ rate.toFixed(4) }} MKD</span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ═══ PARTIES ═══ -->
                <div class="mb-8 grid grid-cols-2 gap-6">
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Издавач</p>
                        <p class="font-semibold text-gray-800">{{ invoice.company.name }}</p>
                        <p v-if="invoice.company.address" class="text-sm text-gray-600">{{ invoice.company.address }}</p>
                        <p class="text-sm text-gray-600">ЕДБ: {{ invoice.company.tax_id }}</p>
                    </div>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-gray-400">Примач</p>
                        <template v-if="invoice.kontragent">
                            <p class="font-semibold text-gray-800">{{ invoice.kontragent.name }}</p>
                            <p class="text-sm text-gray-600">ЕДБ: {{ invoice.kontragent.edb }}</p>
                            <p v-if="invoice.kontragent.address" class="text-sm text-gray-600">{{ invoice.kontragent.address }}</p>
                        </template>
                        <p v-else class="font-semibold text-gray-800">{{ invoice.client_name ?? '—' }}</p>
                    </div>
                </div>

                <!-- ═══ LINES TABLE ═══ -->
                <table class="mb-8 w-full text-sm">
                    <thead>
                        <tr class="border-y-2 border-gray-200 bg-gray-50">
                            <th class="py-2.5 pl-3 text-left font-semibold text-gray-700">#</th>
                            <th class="py-2.5 text-left font-semibold text-gray-700">Назив / Опис</th>
                            <th class="py-2.5 text-right font-semibold text-gray-700">Кол.</th>
                            <th class="py-2.5 text-left font-semibold text-gray-700 pl-1">ЈМ</th>
                            <th class="py-2.5 text-right font-semibold text-gray-700">Ед. цена</th>
                            <th class="py-2.5 text-right font-semibold text-gray-700">ДДВ%</th>
                            <th class="py-2.5 text-right font-semibold text-gray-700">Осн. за ДДВ</th>
                            <th class="py-2.5 text-right font-semibold text-gray-700">ДДВ износ</th>
                            <th class="py-2.5 pr-3 text-right font-semibold text-gray-700">Вкупно</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(line, idx) in invoice.lines"
                            :key="line.id"
                            class="border-b border-gray-100"
                        >
                            <td class="py-2 pl-3 text-muted-foreground">{{ idx + 1 }}</td>
                            <td class="py-2 pr-4">
                                <span class="font-medium text-gray-800">{{ line.description }}</span>
                                <span v-if="line.item?.is_service" class="ml-1.5 rounded bg-blue-50 px-1.5 py-0.5 text-[10px] text-blue-600">услуга</span>
                            </td>
                            <td class="py-2 text-right font-mono">{{ fmtQty(line.quantity) }}</td>
                            <td class="py-2 pl-1 text-muted-foreground">{{ line.unit }}</td>
                            <td class="py-2 text-right font-mono">{{ fmt(line.unit_price) }}</td>
                            <td class="py-2 text-right text-muted-foreground">{{ line.vat_rate }}%</td>
                            <td class="py-2 text-right font-mono">{{ fmt(line.line_total_ex_vat) }}</td>
                            <td class="py-2 text-right font-mono text-muted-foreground">{{ fmt(line.vat_amount) }}</td>
                            <td class="py-2 pr-3 text-right font-mono font-semibold">{{ fmt(line.line_total_inc_vat) }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- ═══ TOTALS ═══ -->
                <div class="mb-10 flex justify-end">
                    <div class="w-80 rounded-lg border border-gray-200 overflow-hidden">
                        <div class="flex justify-between px-4 py-2 text-sm">
                            <span class="text-gray-500">Вкупно без ДДВ:</span>
                            <span class="font-mono font-medium">{{ fmt(invoice.subtotal) }} {{ cur }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-100 px-4 py-2 text-sm">
                            <span class="text-gray-500">ДДВ вкупно:</span>
                            <span class="font-mono font-medium">{{ fmt(invoice.vat_total) }} {{ cur }}</span>
                        </div>
                        <div v-if="cur !== 'MKD' && rate" class="flex justify-between border-t border-gray-100 bg-gray-50 px-4 py-2 text-xs text-muted-foreground">
                            <span>Еквивалент во MKD:</span>
                            <span class="font-mono">{{ fmt(Number(invoice.total_amount) * rate) }} MKD</span>
                        </div>
                        <div class="flex justify-between border-t-2 border-gray-900 bg-gray-50 px-4 py-3 text-base font-bold">
                            <span>ВКУПНО ЗА ПЛАЌАЊЕ:</span>
                            <span class="font-mono">{{ fmt(invoice.total_amount) }} {{ cur }}</span>
                        </div>
                    </div>
                </div>

                <!-- ═══ NOTES ═══ -->
                <div v-if="invoice.notes" class="mb-8 rounded-lg border border-dashed border-gray-200 bg-gray-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Напомена</p>
                    <p class="mt-1 text-sm text-gray-700">{{ invoice.notes }}</p>
                </div>

                <!-- ═══ WAREHOUSE INFO ═══ -->
                <div v-if="invoice.warehouse" class="mb-8 text-xs text-gray-400 print:hidden">
                    Магацин: {{ invoice.warehouse.name }}
                </div>

                <!-- ═══ SIGNATURES ═══ -->
                <div class="mt-12 grid grid-cols-2 gap-16 border-t border-gray-200 pt-8">
                    <div>
                        <p class="mb-16 text-xs font-bold uppercase tracking-wider text-gray-600">ИЗДАЛ</p>
                        <div class="border-t border-gray-400 pt-1">
                            <p class="text-[10px] text-gray-400">Потпис и печат</p>
                        </div>
                    </div>
                    <div>
                        <p class="mb-16 text-xs font-bold uppercase tracking-wider text-gray-600">ПРИМИЛ</p>
                        <div class="border-t border-gray-400 pt-1">
                            <p class="text-[10px] text-gray-400">Потпис и печат</p>
                        </div>
                    </div>
                </div>

                <!-- ═══ FOOTER ═══ -->
                <div class="mt-6 border-t border-gray-100 pt-4 text-[10px] text-gray-400">
                    <div class="flex items-center justify-between">
                        <span>Составил: {{ invoice.creator.name }}</span>
                        <span>Документот е изработен во системот FinanceBuddy.mk</span>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Book Dialog -->
    <Dialog v-model:open="showBook">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Книжи излезна фактура</DialogTitle>
            </DialogHeader>
            <div class="grid gap-4 py-1">
                <div class="rounded-lg bg-muted/40 px-3 py-2 text-sm">
                    <p class="font-mono font-semibold">{{ invoice.invoice_number }}</p>
                    <p class="text-muted-foreground">{{ invoice.kontragent?.name ?? invoice.client_name ?? '—' }}</p>
                    <p class="mt-1 font-semibold">{{ fmt(invoice.total_amount) }} {{ cur }}</p>
                </div>
                <div class="grid gap-1.5">
                    <Label>Книговодствен налог</Label>
                    <div v-if="loadingBook" class="flex h-9 items-center gap-2 text-sm text-muted-foreground">
                        <Loader2 class="size-4 animate-spin" />Вчитување…
                    </div>
                    <Select v-else v-model="bookEntryId">
                        <SelectTrigger>
                            <SelectValue placeholder="— Автоматски (месечен налог) —" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">— Автоматски (месечен налог) —</SelectItem>
                            <SelectItem v-for="e in bookEntries" :key="e.id" :value="String(e.id)">
                                {{ e.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p class="text-xs text-muted-foreground">
                        Ако не изберете, системот автоматски го наоѓа или создава месечниот налог.
                    </p>
                </div>
            </div>
            <DialogFooter>
                <Button variant="outline" @click="showBook = false">Откажи</Button>
                <Button :disabled="submittingBook" @click="submitBook">
                    <Loader2 v-if="submittingBook" class="mr-2 size-4 animate-spin" />
                    {{ submittingBook ? 'Книжење…' : 'Книжи' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
