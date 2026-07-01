<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Search, X, Eye, Trash2, FileText } from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Материјално', href: '/sales-invoices' },
            { title: 'Излезни фактури', href: '/sales-invoices' },
        ],
    },
});

type Kontragent = { id: number; name: string; edb: string; is_vat_payer: boolean; type: string };
type Item = { id: number; code: string; name: string; unit: string; price_without_vat: string; vat_category: string };
type Company = { id: number; name: string };
type InvoiceLine = {
    item_id: string;
    description: string;
    quantity: string;
    unit: string;
    unit_price: string;
    vat_rate: string;
    line_total_ex_vat: number;
    vat_amount: number;
    line_total_inc_vat: number;
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
    company: Company;
    kontragent: Kontragent | null;
    client_name: string | null;
};
type Paginated = {
    data: Invoice[];
    total: number;
    last_page: number;
    links: { url: string | null; label: string; active: boolean }[];
};

const props = defineProps<{
    invoices:   Paginated;
    companies:  Company[];
    filters:    { company_id?: string; status?: string };
}>();

const STATUS_LABELS: Record<string, string> = {
    draft:  'Нацрт',
    sent:   'Испратена',
    booked: 'Книжена',
};
const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    draft:  'outline',
    sent:   'default',
    booked: 'secondary',
};

// ─── Filters ─────────────────────────────────────────────────────────────────
const companyFilter = ref(props.filters.company_id ?? '');
const statusFilter  = ref(props.filters.status ?? '');

function applyFilters() {
    router.get('/sales-invoices', {
        ...(companyFilter.value ? { company_id: companyFilter.value } : {}),
        ...(statusFilter.value ? { status: statusFilter.value } : {}),
    }, { preserveState: true, replace: true });
}

const hasFilters = computed(() => companyFilter.value || statusFilter.value);

// ─── Create Dialog ────────────────────────────────────────────────────────────
const showCreate    = ref(false);
const kontragenti   = ref<Kontragent[]>([]);
const items         = ref<Item[]>([]);
const loadingKontragenti = ref(false);

// Form
const createForm = useForm({
    company_id:     '',
    kontragent_id:  '',
    client_name:    '',
    invoice_number: '',
    date:           new Date().toISOString().split('T')[0],
    due_date:       '',
    notes:          '',
    status:         'draft',
    lines:          [] as InvoiceLine[],
});

// Load контрагенти при промена на компанија
watch(() => createForm.company_id, async (companyId) => {
    if (!companyId) {
        kontragenti.value = [];
        items.value = [];
        return;
    }
    loadingKontragenti.value = true;
    try {
        const [kRes, iRes] = await Promise.all([
            fetch(`/companies/${companyId}/kontragenti?type=client`).then(r => r.json()),
            fetch(`/companies/${companyId}/items`).then(r => r.json()),
        ]);
        kontragenti.value = kRes;
        items.value       = iRes;
    } finally {
        loadingKontragenti.value = false;
    }
});

function addLine() {
    createForm.lines.push({
        item_id:           '',
        description:       '',
        quantity:          '1',
        unit:              'бр',
        unit_price:        '0',
        vat_rate:          '18',
        line_total_ex_vat: 0,
        vat_amount:        0,
        line_total_inc_vat: 0,
    });
}

function removeLine(idx: number) {
    createForm.lines.splice(idx, 1);
}

function onItemSelect(idx: number, itemId: string) {
    const item = items.value.find(i => String(i.id) === itemId);
    if (!item) return;
    const line = createForm.lines[idx];
    line.description = item.name;
    line.unit        = item.unit;
    line.unit_price  = item.price_without_vat;
    line.vat_rate    = item.vat_category;
    recalcLine(idx);
}

function recalcLine(idx: number) {
    const line = createForm.lines[idx];
    const qty  = parseFloat(line.quantity)   || 0;
    const price= parseFloat(line.unit_price) || 0;
    const vat  = parseFloat(line.vat_rate)   || 0;
    const exVat  = Math.round(qty * price * 100) / 100;
    const vatAmt = Math.round(exVat * vat / 100 * 100) / 100;
    line.line_total_ex_vat  = exVat;
    line.vat_amount         = vatAmt;
    line.line_total_inc_vat = Math.round((exVat + vatAmt) * 100) / 100;
}

const totals = computed(() => {
    let sub = 0, vat = 0;
    for (const l of createForm.lines) {
        sub += l.line_total_ex_vat;
        vat += l.vat_amount;
    }
    return { sub: Math.round(sub * 100) / 100, vat: Math.round(vat * 100) / 100, total: Math.round((sub + vat) * 100) / 100 };
});

function openCreate() {
    createForm.reset();
    createForm.date = new Date().toISOString().split('T')[0];
    createForm.status = 'draft';
    kontragenti.value = [];
    items.value = [];
    showCreate.value = true;
}

function submitCreate(status: 'draft' | 'sent' = 'draft') {
    createForm.status = status;
    createForm.post('/sales-invoices', {
        onSuccess: () => {
            showCreate.value = false;
        },
    });
}

function fmt(v: string | number) {
    return Number(v).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function deleteInvoice(inv: Invoice) {
    if (!confirm(`Избриши фактура ${inv.invoice_number}?`)) return;
    router.delete(`/sales-invoices/${inv.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Излезни фактури" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Излезни фактури</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ invoices.total }} вкупно</p>
            </div>
            <Button @click="openCreate">
                <Plus class="mr-2 size-4" />
                Нова фактура
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-end gap-3">
            <Select v-model="companyFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-56"><SelectValue placeholder="Сите компании" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите компании</SelectItem>
                    <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Сите статуси" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите статуси</SelectItem>
                    <SelectItem value="draft">Нацрт</SelectItem>
                    <SelectItem value="sent">Испратена</SelectItem>
                    <SelectItem value="booked">Книжена</SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasFilters" variant="ghost" size="icon" @click="companyFilter=''; statusFilter=''; applyFilters()">
                <X class="size-4" />
            </Button>
        </div>

        <!-- Table -->
        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Број</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Датум</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Клиент</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Вкупно (со ДДВ)</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="7" class="py-16 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-3 size-10 opacity-30" />
                            Нема излезни фактури
                        </td>
                    </tr>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono font-medium">{{ inv.invoice_number }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ inv.date }}</td>
                        <td class="px-4 py-3">{{ inv.kontragent?.name ?? inv.client_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ inv.company.name }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">{{ fmt(inv.total_amount) }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="STATUS_VARIANT[inv.status]" class="text-xs">
                                {{ STATUS_LABELS[inv.status] }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-1">
                                <Button variant="ghost" size="icon" as-child title="Преглед">
                                    <Link :href="`/sales-invoices/${inv.id}`"><Eye class="size-4" /></Link>
                                </Button>
                                <Button
                                    v-if="inv.status === 'draft'"
                                    variant="ghost"
                                    size="icon"
                                    class="text-destructive hover:text-destructive"
                                    @click="deleteInvoice(inv)"
                                    title="Избриши"
                                >
                                    <Trash2 class="size-4" />
                                </Button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in invoices.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && router.visit(link.url, { preserveScroll: true })"
            />
        </div>

    </div>

    <!-- ─── Create Dialog ──────────────────────────────────────────────────── -->
    <Dialog v-model:open="showCreate">
        <DialogContent class="max-w-5xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Нова излезна фактура</DialogTitle>
            </DialogHeader>

            <div class="grid gap-5 py-2">

                <!-- Row 1: Компанија + Клиент -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Компанија *</Label>
                        <Select v-model="createForm.company_id">
                            <SelectTrigger>
                                <SelectValue placeholder="Избери компанија" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in companies" :key="c.id" :value="String(c.id)">{{ c.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="createForm.errors.company_id" class="text-xs text-destructive">{{ createForm.errors.company_id }}</p>
                    </div>

                    <div class="grid gap-1.5">
                        <Label>Клиент</Label>
                        <Select v-model="createForm.kontragent_id" :disabled="!createForm.company_id || loadingKontragenti">
                            <SelectTrigger>
                                <SelectValue :placeholder="loadingKontragenti ? 'Вчитување…' : 'Избери клиент'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— Без контрагент —</SelectItem>
                                <SelectItem v-for="k in kontragenti" :key="k.id" :value="String(k.id)">
                                    {{ k.name }} ({{ k.edb }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Row 2: Број + Датуми -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Број на фактура *</Label>
                        <Input v-model="createForm.invoice_number" placeholder="2024-0001" />
                        <p v-if="createForm.errors.invoice_number" class="text-xs text-destructive">{{ createForm.errors.invoice_number }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Датум на издавање *</Label>
                        <Input v-model="createForm.date" type="date" />
                        <p v-if="createForm.errors.date" class="text-xs text-destructive">{{ createForm.errors.date }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Датум на валута</Label>
                        <Input v-model="createForm.due_date" type="date" />
                    </div>
                </div>

                <!-- Ставки -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <Label class="text-base font-semibold">Ставки</Label>
                        <Button type="button" variant="outline" size="sm" @click="addLine" :disabled="!createForm.company_id">
                            <Plus class="mr-1.5 size-3.5" />
                            Додај ставка
                        </Button>
                    </div>

                    <p v-if="createForm.lines.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Прво избери компанија, потоа додај ставки.
                    </p>

                    <div v-else class="rounded-lg border">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-2 py-2 text-left font-medium text-muted-foreground w-48">Артикл / Опис</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-20">Кол.</th>
                                    <th class="px-2 py-2 text-left font-medium text-muted-foreground w-14">ЈМ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Цена (без ДДВ)</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-16">ДДВ%</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Вк. без ДДВ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-20">ДДВ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Вк. со ДДВ</th>
                                    <th class="px-2 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in createForm.lines" :key="idx" class="border-b last:border-0">
                                    <td class="px-2 py-1.5">
                                        <div class="grid gap-1">
                                            <Select v-model="line.item_id" @update:model-value="onItemSelect(idx, $event)">
                                                <SelectTrigger class="h-7 text-xs">
                                                    <SelectValue placeholder="Артикл" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">— Без артикл —</SelectItem>
                                                    <SelectItem v-for="item in items" :key="item.id" :value="String(item.id)">
                                                        {{ item.code }} — {{ item.name }}
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Input v-model="line.description" class="h-7 text-xs" placeholder="Опис на ставката" />
                                        </div>
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <Input v-model="line.quantity" type="number" step="0.001" min="0.001" class="h-7 text-right text-xs" @input="recalcLine(idx)" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <Input v-model="line.unit" class="h-7 text-xs" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <Input v-model="line.unit_price" type="number" step="0.0001" min="0" class="h-7 text-right text-xs" @input="recalcLine(idx)" />
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <Select v-model="line.vat_rate" @update:model-value="recalcLine(idx)">
                                            <SelectTrigger class="h-7 text-xs">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="18">18%</SelectItem>
                                                <SelectItem value="5">5%</SelectItem>
                                                <SelectItem value="0">0%</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </td>
                                    <td class="px-2 py-1.5 text-right font-mono">{{ fmt(line.line_total_ex_vat) }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono text-muted-foreground">{{ fmt(line.vat_amount) }}</td>
                                    <td class="px-2 py-1.5 text-right font-mono font-semibold">{{ fmt(line.line_total_inc_vat) }}</td>
                                    <td class="px-2 py-1.5 text-center">
                                        <button type="button" class="text-destructive hover:opacity-70" @click="removeLine(idx)" title="Отстрани">
                                            <X class="size-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals -->
                <div v-if="createForm.lines.length > 0" class="flex justify-end">
                    <div class="w-72 rounded-lg border p-4">
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-muted-foreground">Вкупно без ДДВ:</span>
                            <span class="font-mono">{{ fmt(totals.sub) }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-muted-foreground">ДДВ:</span>
                            <span class="font-mono">{{ fmt(totals.vat) }}</span>
                        </div>
                        <div class="mt-1 flex justify-between border-t pt-2 text-base font-semibold">
                            <span>Вкупно со ДДВ:</span>
                            <span class="font-mono">{{ fmt(totals.total) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div class="grid gap-1.5">
                    <Label>Белешка</Label>
                    <Input v-model="createForm.notes" placeholder="Опционална белешка…" />
                </div>

                <p v-if="createForm.errors.lines" class="text-xs text-destructive">{{ createForm.errors.lines }}</p>
            </div>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="showCreate = false">Откажи</Button>
                <Button variant="secondary" :disabled="createForm.processing" @click="submitCreate('draft')">
                    {{ createForm.processing ? 'Зачувување…' : 'Зачувај нацрт' }}
                </Button>
                <Button :disabled="createForm.processing" @click="submitCreate('sent')">
                    Зачувај и испрати
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
