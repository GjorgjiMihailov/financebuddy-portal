<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { Plus, Search, X, Eye, Trash2, FileText, RefreshCw } from '@lucide/vue';
import { ref, computed, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Link } from '@inertiajs/vue3';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Материјално', href: '/sales-invoices' },
            { title: 'Излезни фактури', href: '/sales-invoices' },
        ],
    },
});

type Kontragent = { id: number; name: string; edb: string; is_vat_payer: boolean };
type Item = { id: number; code: string; name: string; unit: string; price_without_vat: string; vat_category: string; is_service: boolean };
type Warehouse = { id: number; name: string };
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
    currency: string;
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
    warehouses: Warehouse[];
    filters:    { status?: string };
}>();

const page = usePage();
const currentCompanyId = computed(() => String((page.props as any).current_company?.id ?? ''));

const STATUS_LABELS: Record<string, string> = { draft: 'Нацрт', sent: 'Испратена', booked: 'Книжена' };
const STATUS_VARIANT: Record<string, 'default' | 'secondary' | 'outline'> = {
    draft: 'outline', sent: 'default', booked: 'secondary',
};

const CURRENCIES = ['MKD', 'EUR', 'USD', 'CHF', 'GBP'];

// Filters
const statusFilter = ref(props.filters.status ?? '');
function applyFilters() {
    router.get('/sales-invoices', {
        ...(statusFilter.value ? { status: statusFilter.value } : {}),
    }, { preserveState: true, replace: true });
}

// Create Dialog
const showCreate    = ref(false);
const kontragenti   = ref<Kontragent[]>([]);
const items         = ref<Item[]>([]);
const loadingK      = ref(false);
const loadingRate   = ref(false);

const createForm = useForm({
    company_id:     '',
    warehouse_id:   '',
    kontragent_id:  '',
    client_name:    '',
    invoice_number: '',
    date:           new Date().toISOString().split('T')[0],
    date_of_supply: '',
    due_date:       '',
    currency:       'MKD',
    exchange_rate:  '',
    notes:          '',
    status:         'draft',
    lines:          [] as InvoiceLine[],
});

watch(() => createForm.company_id, async (id) => {
    if (!id) { kontragenti.value = []; items.value = []; return; }
    loadingK.value = true;
    try {
        const [kr, ir] = await Promise.all([
            fetch(`/companies/${id}/kontragenti?type=client`).then(r => r.json()),
            fetch(`/companies/${id}/items`).then(r => r.json()),
        ]);
        kontragenti.value = kr;
        items.value       = ir;
    } finally { loadingK.value = false; }
});

async function fetchRate() {
    if (!createForm.currency || createForm.currency === 'MKD') {
        createForm.exchange_rate = '';
        return;
    }
    loadingRate.value = true;
    try {
        const date = createForm.date || new Date().toISOString().split('T')[0];
        const res  = await fetch(`/api/exchange-rate?currency=${createForm.currency}&date=${date}`).then(r => r.json());
        if (res.rate) {
            createForm.exchange_rate = String(res.rate);
        }
    } finally {
        loadingRate.value = false;
    }
}

watch(() => createForm.currency, (cur) => {
    if (cur === 'MKD') createForm.exchange_rate = '';
    else fetchRate();
});

function addLine() {
    createForm.lines.push({
        item_id: '', description: '', quantity: '1', unit: 'бр',
        unit_price: '0', vat_rate: '18',
        line_total_ex_vat: 0, vat_amount: 0, line_total_inc_vat: 0,
    });
}

function removeLine(idx: number) { createForm.lines.splice(idx, 1); }

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
    const line  = createForm.lines[idx];
    const qty   = parseFloat(line.quantity)   || 0;
    const price = parseFloat(line.unit_price) || 0;
    const vat   = parseFloat(line.vat_rate)   || 0;
    const exVat = Math.round(qty * price * 100) / 100;
    const vatAmt = Math.round(exVat * vat / 100 * 100) / 100;
    line.line_total_ex_vat  = exVat;
    line.vat_amount         = vatAmt;
    line.line_total_inc_vat = Math.round((exVat + vatAmt) * 100) / 100;
}

const totals = computed(() => {
    let sub = 0, vat = 0;
    for (const l of createForm.lines) { sub += l.line_total_ex_vat; vat += l.vat_amount; }
    return { sub: Math.round(sub * 100) / 100, vat: Math.round(vat * 100) / 100, total: Math.round((sub + vat) * 100) / 100 };
});

function openCreate() {
    createForm.reset();
    createForm.company_id = currentCompanyId.value;
    createForm.date       = new Date().toISOString().split('T')[0];
    createForm.currency   = 'MKD';
    createForm.status     = 'draft';
    kontragenti.value     = [];
    items.value           = [];
    showCreate.value      = true;
    // pre-load kontragenti/items
    if (currentCompanyId.value) {
        loadingK.value = true;
        Promise.all([
            fetch(`/companies/${currentCompanyId.value}/kontragenti?type=client`).then(r => r.json()),
            fetch(`/companies/${currentCompanyId.value}/items`).then(r => r.json()),
        ]).then(([kr, ir]) => {
            kontragenti.value = kr; items.value = ir;
        }).finally(() => { loadingK.value = false; });
    }
}

function submitCreate(status: 'draft' | 'sent' = 'draft') {
    createForm.status = status;
    createForm.post('/sales-invoices', {
        onSuccess: () => { showCreate.value = false; },
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
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-40"><SelectValue placeholder="Сите статуси" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">Сите статуси</SelectItem>
                    <SelectItem value="draft">Нацрт</SelectItem>
                    <SelectItem value="sent">Испратена</SelectItem>
                    <SelectItem value="booked">Книжена</SelectItem>
                </SelectContent>
            </Select>
            <Button v-if="statusFilter" variant="ghost" size="icon" @click="statusFilter=''; applyFilters()">
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
                        <th class="px-4 py-3 text-right font-medium text-muted-foreground">Вкупно (со ДДВ)</th>
                        <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="invoices.data.length === 0">
                        <td colspan="6" class="py-16 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-3 size-10 opacity-30" />
                            Нема излезни фактури
                        </td>
                    </tr>
                    <tr v-for="inv in invoices.data" :key="inv.id" class="border-b last:border-0 hover:bg-muted/30">
                        <td class="px-4 py-3 font-mono font-medium">{{ inv.invoice_number }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ inv.date }}</td>
                        <td class="px-4 py-3">{{ inv.kontragent?.name ?? inv.client_name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right font-mono font-semibold">
                            {{ fmt(inv.total_amount) }}
                            <span v-if="inv.currency !== 'MKD'" class="ml-1 text-xs text-muted-foreground">{{ inv.currency }}</span>
                        </td>
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
                                    variant="ghost" size="icon"
                                    class="text-destructive hover:text-destructive"
                                    @click="deleteInvoice(inv)" title="Избриши"
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
                v-for="link in invoices.links" :key="link.label"
                :variant="link.active ? 'default' : 'outline'" size="sm"
                :disabled="!link.url" v-html="link.label"
                @click="link.url && router.visit(link.url, { preserveScroll: true })"
            />
        </div>

    </div>

    <!-- Create Dialog -->
    <Dialog v-model:open="showCreate">
        <DialogContent class="max-w-5xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Нова излезна фактура</DialogTitle>
            </DialogHeader>

            <div class="grid gap-5 py-2">

                <!-- Row 1: Клиент + Магацин -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Клиент</Label>
                        <Select v-model="createForm.kontragent_id" :disabled="loadingK">
                            <SelectTrigger>
                                <SelectValue :placeholder="loadingK ? 'Вчитување…' : 'Избери клиент'" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— Без контрагент —</SelectItem>
                                <SelectItem v-for="k in kontragenti" :key="k.id" :value="String(k.id)">
                                    {{ k.name }} ({{ k.edb }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Магацин</Label>
                        <Select v-model="createForm.warehouse_id">
                            <SelectTrigger><SelectValue placeholder="Без магацин (нема движење)" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="">— Без магацин —</SelectItem>
                                <SelectItem v-for="w in warehouses" :key="w.id" :value="String(w.id)">{{ w.name }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <!-- Row 2: Број + Датуми -->
                <div class="grid grid-cols-4 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Број на фактура *</Label>
                        <Input v-model="createForm.invoice_number" placeholder="2024-0001" />
                        <p v-if="createForm.errors.invoice_number" class="text-xs text-destructive">{{ createForm.errors.invoice_number }}</p>
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Датум на издавање *</Label>
                        <Input v-model="createForm.date" type="date" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Датум на промет</Label>
                        <Input v-model="createForm.date_of_supply" type="date" />
                    </div>
                    <div class="grid gap-1.5">
                        <Label>Датум на валута</Label>
                        <Input v-model="createForm.due_date" type="date" />
                    </div>
                </div>

                <!-- Row 3: Валута + Курс -->
                <div class="grid grid-cols-3 gap-4">
                    <div class="grid gap-1.5">
                        <Label>Валута</Label>
                        <Select v-model="createForm.currency">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in ['MKD','EUR','USD','CHF','GBP']" :key="c" :value="c">{{ c }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div v-if="createForm.currency !== 'MKD'" class="col-span-2 grid gap-1.5">
                        <Label>Средишен курс НБРМ (1 {{ createForm.currency }} = ? MKD)</Label>
                        <div class="flex gap-2">
                            <Input v-model="createForm.exchange_rate" type="number" step="0.0001" min="0" placeholder="61.6140" />
                            <Button type="button" variant="outline" size="icon" :disabled="loadingRate" @click="fetchRate" title="Земи курс од НБРМ">
                                <RefreshCw class="size-4" :class="{ 'animate-spin': loadingRate }" />
                            </Button>
                        </div>
                        <p v-if="createForm.errors.exchange_rate" class="text-xs text-destructive">{{ createForm.errors.exchange_rate }}</p>
                    </div>
                </div>

                <!-- Ставки -->
                <div>
                    <div class="mb-2 flex items-center justify-between">
                        <Label class="text-base font-semibold">Ставки</Label>
                        <Button type="button" variant="outline" size="sm" @click="addLine">
                            <Plus class="mr-1.5 size-3.5" />Додај ставка
                        </Button>
                    </div>

                    <p v-if="createForm.lines.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                        Додај ставки на фактурата.
                    </p>

                    <div v-else class="rounded-lg border">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b bg-muted/50">
                                    <th class="px-2 py-2 text-left font-medium text-muted-foreground w-52">Артикл / Опис</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-20">Кол.</th>
                                    <th class="px-2 py-2 text-left font-medium text-muted-foreground w-14">ЈМ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Цена</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-16">ДДВ%</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Вк. без ДДВ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-20">ДДВ</th>
                                    <th class="px-2 py-2 text-right font-medium text-muted-foreground w-24">Вкупно</th>
                                    <th class="px-2 py-2 w-8"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(line, idx) in createForm.lines" :key="idx" class="border-b last:border-0">
                                    <td class="px-2 py-1.5">
                                        <div class="grid gap-1">
                                            <Select v-model="line.item_id" @update:model-value="onItemSelect(idx, $event)">
                                                <SelectTrigger class="h-7 text-xs"><SelectValue placeholder="Артикл" /></SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">— Без артикл —</SelectItem>
                                                    <SelectItem v-for="item in items" :key="item.id" :value="String(item.id)">
                                                        {{ item.code }} — {{ item.name }}
                                                        <span v-if="item.is_service" class="ml-1 text-muted-foreground">(услуга)</span>
                                                    </SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <Input v-model="line.description" class="h-7 text-xs" placeholder="Опис" />
                                        </div>
                                    </td>
                                    <td class="px-2 py-1.5"><Input v-model="line.quantity" type="number" step="0.001" min="0.001" class="h-7 text-right text-xs" @input="recalcLine(idx)" /></td>
                                    <td class="px-2 py-1.5"><Input v-model="line.unit" class="h-7 text-xs" /></td>
                                    <td class="px-2 py-1.5"><Input v-model="line.unit_price" type="number" step="0.0001" min="0" class="h-7 text-right text-xs" @input="recalcLine(idx)" /></td>
                                    <td class="px-2 py-1.5">
                                        <Select v-model="line.vat_rate" @update:model-value="recalcLine(idx)">
                                            <SelectTrigger class="h-7 text-xs"><SelectValue /></SelectTrigger>
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
                                        <button type="button" class="text-destructive hover:opacity-70" @click="removeLine(idx)">
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
                            <span class="font-mono">{{ fmt(totals.sub) }} {{ createForm.currency }}</span>
                        </div>
                        <div class="flex justify-between py-1 text-sm">
                            <span class="text-muted-foreground">ДДВ:</span>
                            <span class="font-mono">{{ fmt(totals.vat) }} {{ createForm.currency }}</span>
                        </div>
                        <div class="mt-1 flex justify-between border-t pt-2 text-base font-semibold">
                            <span>ВКУПНО:</span>
                            <span class="font-mono">{{ fmt(totals.total) }} {{ createForm.currency }}</span>
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
