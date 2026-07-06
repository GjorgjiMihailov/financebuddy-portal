<script setup lang="ts">
import { ref, reactive, computed, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import {
    ChevronsLeft, ChevronLeft, ChevronRight, ChevronsRight,
    Plus, Trash2, Save, FilePlus, X, Loader2,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import type { JournalGroup, JournalEntry } from '@/types/journal-entry';

// ─── Types ───────────────────────────────────────────────────────────────────

type VoucherGroup = JournalGroup & { entry_count: number };

type AccountResult = {
    code: string;
    name: string;
    class: number;
    account_type: string;
};

type PartnerResult = {
    id: number;
    name: string;
    edb: string;
    embs: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
};

interface EditableLine {
    id?: number;
    sort_order: number;
    account_code: string;
    account_name: string;
    account_obj: AccountResult | null;
    kooperant_id: number | null;
    kooperant_name: string;
    kooperant_obj: PartnerResult | null;
    line_date: string;
    description: string;
    closing_reference: string;
    debit: string;
    credit: string;
}

// ─── Props ────────────────────────────────────────────────────────────────────

const props = defineProps<{
    groups: VoucherGroup[];
    initialEntry: JournalEntry | null;
    groupCode: number;
    year: number;
    companyId: number;
    position: number;
    total: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Финансии', href: '/documents' },
            { title: 'Внес на налог', href: '#' },
        ],
    },
});

// ─── State ───────────────────────────────────────────────────────────────────

const groups     = ref<VoucherGroup[]>(props.groups);
const selGroup   = ref<number>(props.groupCode);
const year       = ref<number>(props.year);
const entry      = ref<JournalEntry | null>(props.initialEntry);
const lines      = ref<EditableLine[]>([]);
const totalCount = ref<number>(props.total ?? 0);
const position   = ref<number>(props.position ?? 0);
const selectedRow = ref<number>(-1);
const isDirty    = ref<boolean>(false);
const isSaving   = ref<boolean>(false);

// Separate "form" for the header fields so they remain editable when entry is null
const form = reactive({
    entry_date:  props.initialEntry?.entry_date  ?? new Date().toISOString().slice(0, 10),
    description: props.initialEntry?.description ?? '',
    reference:   props.initialEntry?.reference   ?? '',
});

// Autocomplete state
const ac = reactive({
    show:   false,
    field:  '' as 'account' | 'partner',
    row:    -1,
    results: [] as (AccountResult | PartnerResult)[],
    idx:    -1,
    rect:   null as DOMRect | null,
});
let acTimer: ReturnType<typeof setTimeout> | null = null;

// Open invoices for the selected line's partner
const openInvoices = reactive<{ purchases: any[]; sales: any[] }>({ purchases: [], sales: [] });

// CSRF token
const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

// ─── Computed ─────────────────────────────────────────────────────────────────

const totalDebit  = computed(() => lines.value.reduce((s, l) => s + (parseFloat(l.debit)  || 0), 0));
const totalCredit = computed(() => lines.value.reduce((s, l) => s + (parseFloat(l.credit) || 0), 0));
const difference  = computed(() => Math.abs(totalDebit.value - totalCredit.value));
const isBalanced  = computed(() => difference.value < 0.005);

const voucherNumber = computed(() => {
    if (!entry.value?.group_code || !entry.value?.sequence_number) return 'Нов';
    return `${entry.value.group_code}-${String(entry.value.sequence_number).padStart(4, '0')}`;
});

const selectedLine = computed(() =>
    selectedRow.value >= 0 && selectedRow.value < lines.value.length
        ? lines.value[selectedRow.value]
        : null
);

const acStyle = computed(() => {
    if (!ac.rect) return {};
    return {
        position: 'fixed' as const,
        top:      `${ac.rect.bottom + 2}px`,
        left:     `${ac.rect.left}px`,
        width:    `${Math.max(ac.rect.width, 320)}px`,
        maxHeight: '220px',
        overflowY: 'auto' as const,
        zIndex:   9999,
    };
});

// ─── Line helpers ─────────────────────────────────────────────────────────────

function emptyLine(idx = 0): EditableLine {
    return {
        sort_order:         idx,
        account_code:       '',
        account_name:       '',
        account_obj:        null,
        kooperant_id:      null,
        kooperant_name:    '',
        kooperant_obj:     null,
        line_date:          form.entry_date,
        description:        '',
        closing_reference:  '',
        debit:              '',
        credit:             '',
    };
}

function entryToLines(e: JournalEntry | null): EditableLine[] {
    if (!e?.lines?.length) return [emptyLine(0), emptyLine(1)];
    return e.lines.map((l, i) => ({
        id:                l.id,
        sort_order:        i,
        account_code:      l.account_code,
        account_name:      l.account?.name ?? '',
        account_obj:       l.account ? {
            code:         l.account.code,
            name:         l.account.name,
            class:        l.account.class ?? 0,
            account_type: l.account.account_type ?? '',
        } : null,
        kooperant_id:     l.kooperant_id ?? null,
        kooperant_name:   l.kooperant?.name ?? '',
        kooperant_obj:    l.kooperant ? {
            id:      l.kooperant.id,
            name:    l.kooperant.name,
            edb:     l.kooperant.edb,
            embs:    l.kooperant.embs ?? null,
            address: l.kooperant.address ?? null,
            phone:   l.kooperant.phone ?? null,
            email:   l.kooperant.email ?? null,
        } : null,
        line_date:         l.line_date ?? e.entry_date ?? '',
        description:       l.description ?? '',
        closing_reference: l.closing_reference ?? '',
        debit:             parseFloat(l.debit as any) > 0 ? String(parseFloat(l.debit as any)) : '',
        credit:            parseFloat(l.credit as any) > 0 ? String(parseFloat(l.credit as any)) : '',
    }));
}

function applyEntry(e: JournalEntry | null, total?: number, pos?: number) {
    entry.value = e;
    lines.value = entryToLines(e);
    isDirty.value = false;
    selectedRow.value = -1;
    if (e) {
        form.entry_date  = e.entry_date;
        form.description = e.description;
        form.reference   = e.reference ?? '';
    }
    if (total !== undefined) totalCount.value = total;
    if (pos   !== undefined) position.value   = pos;
}

// ─── Navigation ───────────────────────────────────────────────────────────────

async function guardDirty(): Promise<boolean> {
    if (!isDirty.value) return true;
    return confirm('Имате незачувани промени. Дали сакате да продолжите без зачувување?');
}

async function navigate(dir: 'first' | 'prev' | 'next' | 'last') {
    if (!(await guardDirty())) return;
    const seq = entry.value?.sequence_number ?? '';
    const url = `/api/voucher/navigate?group_code=${selGroup.value}&year=${year.value}&company_id=${props.companyId}&direction=${dir}&current_seq=${seq}`;
    const res  = await fetch(url);
    if (!res.ok) return;
    const data = await res.json();
    applyEntry(data.entry, data.total, data.position);
}

async function selectGroup(code: number) {
    if (!(await guardDirty())) return;
    selGroup.value = code;
    const url  = `/api/voucher/navigate?group_code=${code}&year=${year.value}&company_id=${props.companyId}&direction=first`;
    const res  = await fetch(url);
    if (!res.ok) return;
    const data = await res.json();
    applyEntry(data.entry, data.total, data.position);
    if (!data.entry) {
        lines.value = [emptyLine(0), emptyLine(1)];
    }
}

// ─── New entry ────────────────────────────────────────────────────────────────

async function createNew() {
    if (!(await guardDirty())) return;
    entry.value = null;
    form.entry_date  = new Date().toISOString().slice(0, 10);
    form.description = '';
    form.reference   = '';
    lines.value = [emptyLine(0), emptyLine(1)];
    isDirty.value = false;
    selectedRow.value = -1;
    nextTick(() => {
        const el = document.querySelector<HTMLInputElement>('[data-row="0"][data-col="account"]');
        el?.focus();
    });
}

// ─── Save ─────────────────────────────────────────────────────────────────────

async function save() {
    if (isSaving.value) return;
    const validLines = lines.value.filter(l => l.account_code.trim());
    if (validLines.length === 0) {
        alert('Внесете барем една ставка со конто.');
        return;
    }

    isSaving.value = true;
    try {
        const payload = {
            group_code:  selGroup.value,
            year:        year.value,
            entry_date:  form.entry_date,
            description: form.description || '—',
            reference:   form.reference || null,
            lines: validLines.map((l, i) => ({
                account_code:      l.account_code,
                kooperant_id:     l.kooperant_id,
                line_date:         l.line_date || null,
                description:       l.description || null,
                closing_reference: l.closing_reference || null,
                debit:             parseFloat(l.debit)  || 0,
                credit:            parseFloat(l.credit) || 0,
                sort_order:        i,
            })),
        };

        const isUpdate = Boolean(entry.value?.id);
        const method   = isUpdate ? 'PUT'  : 'POST';
        const url      = isUpdate ? `/api/voucher/${entry.value!.id}` : '/api/voucher';

        const res  = await fetch(url, {
            method,
            headers: {
                'Content-Type':  'application/json',
                'X-CSRF-TOKEN':  csrf,
                'Accept':        'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!res.ok) {
            const msg = data.message ?? data.error ?? 'Грешка при зачувување.';
            alert(msg);
            return;
        }

        applyEntry(data.entry, data.total, data.position);
        isDirty.value = false;

        // Update group count in left panel
        const g = groups.value.find(g => g.code === selGroup.value);
        if (g) g.entry_count = data.total;
    } finally {
        isSaving.value = false;
    }
}

// ─── Delete ───────────────────────────────────────────────────────────────────

async function deleteEntry() {
    if (!entry.value?.id) return;
    if (!confirm(`Избриши налог ${voucherNumber.value}?`)) return;

    const res  = await fetch(`/api/voucher/${entry.value.id}`, {
        method:  'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
    });
    const data = await res.json();

    if (!res.ok) {
        alert(data.error ?? 'Грешка при бришење.');
        return;
    }

    applyEntry(data.entry, data.total, data.position);
    if (!data.entry) {
        lines.value = [emptyLine(0), emptyLine(1)];
    }

    const g = groups.value.find(g => g.code === selGroup.value);
    if (g) g.entry_count = data.total;
}

// ─── Line management ──────────────────────────────────────────────────────────

function addLine() {
    lines.value.push(emptyLine(lines.value.length));
    isDirty.value = true;
    const idx = lines.value.length - 1;
    nextTick(() => {
        const el = document.querySelector<HTMLInputElement>(`[data-row="${idx}"][data-col="account"]`);
        el?.focus();
    });
}

function removeLine(i: number) {
    if (lines.value.length <= 1) return;
    lines.value.splice(i, 1);
    if (selectedRow.value >= lines.value.length) selectedRow.value = lines.value.length - 1;
    isDirty.value = true;
}

// ─── Keyboard navigation ─────────────────────────────────────────────────────

const TABLE_COLS = ['account', 'partner', 'line_date', 'description', 'closing_ref', 'debit', 'credit'] as const;
type TableCol = typeof TABLE_COLS[number];

function focusCell(row: number, col: TableCol) {
    selectedRow.value = row;
    nextTick(() => {
        const el = document.querySelector<HTMLInputElement>(`[data-row="${row}"][data-col="${col}"]`);
        el?.focus();
        el?.select();
    });
}

function handleKeydown(e: KeyboardEvent, row: number, col: TableCol) {
    // Autocomplete navigation (must intercept Tab too, before falling through)
    if (ac.show) {
        if (e.key === 'ArrowDown') { e.preventDefault(); ac.idx = Math.min(ac.idx + 1, ac.results.length - 1); return; }
        if (e.key === 'ArrowUp')   { e.preventDefault(); ac.idx = Math.max(ac.idx - 1, 0); return; }
        if (e.key === 'Enter')     { e.preventDefault(); if (ac.idx >= 0) selectAcResult(ac.idx); return; }
        if (e.key === 'Escape')    { e.preventDefault(); closeAc(); return; }
        if (e.key === 'Tab' && ac.results.length > 0) {
            // Tab with results open → auto-select highlighted or first result, then move on
            e.preventDefault();
            selectAcResult(ac.idx >= 0 ? ac.idx : 0);
            return;
        }
        // Tab with no results → close AC and fall through to normal Tab handling
        if (e.key === 'Tab') closeAc();
    }

    const colIdx = TABLE_COLS.indexOf(col);

    if (e.key === 'Tab' && !e.shiftKey) {
        e.preventDefault();
        if (colIdx < TABLE_COLS.length - 1) {
            focusCell(row, TABLE_COLS[colIdx + 1]);
        } else if (row < lines.value.length - 1) {
            focusCell(row + 1, TABLE_COLS[0]);
        } else {
            addLine();
        }
    } else if (e.key === 'Tab' && e.shiftKey) {
        e.preventDefault();
        if (colIdx > 0) {
            focusCell(row, TABLE_COLS[colIdx - 1]);
        } else if (row > 0) {
            focusCell(row - 1, TABLE_COLS[TABLE_COLS.length - 1]);
        }
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (row < lines.value.length - 1) {
            focusCell(row + 1, col);
        } else {
            addLine();
        }
    } else if (e.key === 'ArrowUp' && !ac.show && row > 0) {
        e.preventDefault();
        focusCell(row - 1, col);
    } else if (e.key === 'ArrowDown' && !ac.show) {
        e.preventDefault();
        if (row < lines.value.length - 1) {
            focusCell(row + 1, col);
        } else {
            addLine();
        }
    }
}

// ─── Autocomplete ─────────────────────────────────────────────────────────────

function closeAc(delay = 0) {
    if (delay) {
        setTimeout(() => { ac.show = false; }, delay);
    } else {
        ac.show = false;
    }
}

function onAccountInput(e: Event, row: number) {
    const input = e.target as HTMLInputElement;
    const query = input.value;
    lines.value[row].account_code  = query;
    lines.value[row].account_name  = '';
    lines.value[row].account_obj   = null;
    isDirty.value = true;

    ac.rect  = input.getBoundingClientRect();
    ac.field = 'account';
    ac.row   = row;
    ac.idx   = -1;

    if (acTimer) clearTimeout(acTimer);
    if (!query) { ac.show = false; return; }

    acTimer = setTimeout(async () => {
        const res = await fetch(`/api/accounts/search?q=${encodeURIComponent(query)}`);
        if (!res.ok) return;
        ac.results = await res.json();
        ac.show    = ac.results.length > 0;
    }, 180);
}

async function onAccountBlur(e: FocusEvent, row: number) {
    closeAc(200);
    const line = lines.value[row];
    if (!line.account_code || line.account_obj) return; // empty or already resolved
    // Try exact-code lookup so user can type codes directly without AC selection
    const res = await fetch(`/api/accounts/search?q=${encodeURIComponent(line.account_code)}`);
    if (!res.ok) return;
    const results: AccountResult[] = await res.json();
    const exact = results.find(r => r.code === line.account_code);
    if (exact) {
        line.account_name = exact.name;
        line.account_obj  = exact;
    } else if (results.length === 1) {
        // Single result — accept it (user typed prefix that uniquely matches)
        line.account_code = results[0].code;
        line.account_name = results[0].name;
        line.account_obj  = results[0];
    }
}

function onPartnerInput(e: Event, row: number) {
    const input = e.target as HTMLInputElement;
    const query = input.value;
    lines.value[row].kooperant_name = query;
    lines.value[row].kooperant_id   = null;
    lines.value[row].kooperant_obj  = null;
    isDirty.value = true;

    ac.rect  = input.getBoundingClientRect();
    ac.field = 'partner';
    ac.row   = row;
    ac.idx   = -1;

    if (acTimer) clearTimeout(acTimer);
    if (!query) { ac.show = false; return; }

    acTimer = setTimeout(async () => {
        const res = await fetch(`/api/partners/search?q=${encodeURIComponent(query)}&company_id=${props.companyId}`);
        if (!res.ok) return;
        ac.results = await res.json();
        ac.show    = ac.results.length > 0;
    }, 180);
}

function selectAcResult(idx: number) {
    const result = ac.results[idx];
    if (!result) return;
    const line = lines.value[ac.row];
    if (!line) return;

    if (ac.field === 'account') {
        const r = result as AccountResult;
        line.account_code = r.code;
        line.account_name = r.name;
        line.account_obj  = r;
        nextTick(() => focusCell(ac.row, 'partner'));
    } else {
        const r = result as PartnerResult;
        line.kooperant_id   = r.id;
        line.kooperant_name = r.name;
        line.kooperant_obj  = r;
        nextTick(() => focusCell(ac.row, 'line_date'));
    }

    isDirty.value = true;
    closeAc();
}

// ─── Open invoices ────────────────────────────────────────────────────────────

watch(
    () => selectedLine.value?.kooperant_id,
    async (kooperantId) => {
        openInvoices.purchases = [];
        openInvoices.sales     = [];
        if (!kooperantId) return;
        const res = await fetch(`/api/voucher/open-invoices?kooperant_id=${kooperantId}&company_id=${props.companyId}`);
        if (!res.ok) return;
        const data = await res.json();
        openInvoices.purchases = data.purchases ?? [];
        openInvoices.sales     = data.sales     ?? [];
    }
);

// ─── Global Ctrl+S ───────────────────────────────────────────────────────────

function globalKeydown(e: KeyboardEvent) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        save();
    }
}

onMounted(() => {
    lines.value = entryToLines(props.initialEntry);
    window.addEventListener('keydown', globalKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', globalKeydown);
});

// ─── Formatters ───────────────────────────────────────────────────────────────

function fmtAmt(n: number): string {
    if (!n) return '0,00';
    return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function fmtDate(d: string | null | undefined): string {
    if (!d) return '';
    const [y, m, day] = d.split('-');
    return `${day}.${m}.${y}`;
}
</script>

<template>
    <Head title="Внес на налог" />

    <!-- ── Full-height ERP layout ─────────────────────────────────────────── -->
    <div class="flex flex-col overflow-hidden" style="height: calc(100dvh - 112px)">

        <!-- ── TOOLBAR ─────────────────────────────────────────────────────── -->
        <div class="flex flex-shrink-0 items-center gap-1.5 border-b bg-background px-2 py-1">

            <!-- Group selector -->
            <select
                v-model="selGroup"
                @change="selectGroup(selGroup)"
                class="h-7 rounded border border-input bg-background px-2 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-primary"
            >
                <option v-for="g in groups" :key="g.code" :value="g.code">
                    {{ String(g.code).padStart(2, '0') }} — {{ g.name }}
                </option>
            </select>

            <!-- Year -->
            <input
                v-model.number="year"
                type="number"
                class="h-7 w-16 rounded border border-input bg-background px-2 text-xs text-center focus:outline-none focus:ring-1 focus:ring-primary"
            />

            <!-- Voucher number badge -->
            <span class="rounded bg-primary/10 px-2.5 py-1 font-mono text-sm font-bold text-primary">
                {{ voucherNumber }}
            </span>

            <!-- Entry date -->
            <input
                v-model="form.entry_date"
                type="date"
                @change="isDirty = true"
                class="h-7 rounded border border-input bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-primary"
            />

            <div class="h-5 w-px bg-border mx-0.5" />

            <!-- Action buttons -->
            <Button size="sm" variant="outline" class="h-7 gap-1 text-xs" @click="createNew">
                <FilePlus class="size-3" /> Нов
            </Button>
            <Button size="sm" class="h-7 gap-1 text-xs" :disabled="isSaving" @click="save">
                <Loader2 v-if="isSaving" class="size-3 animate-spin" />
                <Save v-else class="size-3" />
                {{ isSaving ? 'Се зачувува...' : 'Сними (Ctrl+S)' }}
            </Button>
            <Button size="sm" variant="destructive" class="h-7 gap-1 text-xs" :disabled="!entry?.id" @click="deleteEntry">
                <Trash2 class="size-3" /> Бриши
            </Button>

            <!-- Unsaved indicator -->
            <span v-if="isDirty" class="text-xs font-medium text-amber-600">● Незачувано</span>

            <!-- Navigation -->
            <div class="ml-auto flex items-center gap-0.5">
                <Button size="icon" variant="ghost" class="size-7" @click="navigate('first')" :disabled="position <= 1">
                    <ChevronsLeft class="size-4" />
                </Button>
                <Button size="icon" variant="ghost" class="size-7" @click="navigate('prev')" :disabled="position <= 1">
                    <ChevronLeft class="size-4" />
                </Button>
                <span class="px-2 text-xs tabular-nums text-muted-foreground">
                    {{ entry ? `${position} / ${totalCount}` : `0 / ${totalCount}` }}
                </span>
                <Button size="icon" variant="ghost" class="size-7" @click="navigate('next')" :disabled="!entry || position >= totalCount">
                    <ChevronRight class="size-4" />
                </Button>
                <Button size="icon" variant="ghost" class="size-7" @click="navigate('last')" :disabled="!entry || position >= totalCount">
                    <ChevronsRight class="size-4" />
                </Button>
            </div>
        </div>

        <!-- ── THREE-COLUMN BODY ──────────────────────────────────────────── -->
        <div class="flex flex-1 min-h-0 overflow-hidden">

            <!-- ── LEFT PANEL: Groups ──────────────────────────────────────── -->
            <aside class="flex w-48 flex-shrink-0 flex-col overflow-hidden border-r">
                <div class="flex-shrink-0 border-b px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Групи
                </div>
                <div class="flex-1 overflow-y-auto">
                    <button
                        v-for="g in groups"
                        :key="g.code"
                        class="flex w-full items-center justify-between px-3 py-1.5 text-left text-xs transition-colors"
                        :class="selGroup === g.code
                            ? 'bg-primary/10 text-primary font-semibold'
                            : 'text-foreground hover:bg-muted/50'"
                        @click="selectGroup(g.code)"
                    >
                        <span class="font-mono">{{ String(g.code).padStart(2, '0') }}</span>
                        <span class="mx-1.5 flex-1 truncate">{{ g.name }}</span>
                        <span class="text-muted-foreground">{{ g.entry_count }}</span>
                    </button>
                </div>
            </aside>

            <!-- ── CENTER: Description header + table + footer ──────────────── -->
            <div class="flex flex-1 min-w-0 flex-col overflow-hidden">

                <!-- Entry header (description + reference) -->
                <div class="flex flex-shrink-0 items-center gap-2 border-b bg-muted/20 px-3 py-1">
                    <input
                        v-model="form.description"
                        @input="isDirty = true"
                        placeholder="Опис на налогот"
                        class="flex-1 rounded border border-transparent bg-transparent px-1 py-0.5 text-sm focus:border-input focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                    <input
                        v-model="form.reference"
                        @input="isDirty = true"
                        placeholder="Референца"
                        class="w-32 rounded border border-transparent bg-transparent px-1 py-0.5 text-xs focus:border-input focus:outline-none focus:ring-1 focus:ring-primary"
                    />
                </div>

                <!-- ── TABLE ──────────────────────────────────────────────── -->
                <div class="flex-1 overflow-auto" @click.self="closeAc()">
                    <table class="w-full min-w-max border-collapse text-xs">
                        <thead class="sticky top-0 z-10 bg-muted/80 backdrop-blur-sm">
                            <tr class="border-b text-left text-[11px] font-medium text-muted-foreground">
                                <th class="w-7 px-1 py-1.5 text-center">#</th>
                                <th class="w-20 px-1 py-1.5">Конто</th>
                                <th class="w-36 px-1 py-1.5">Назив</th>
                                <th class="w-36 px-1 py-1.5">Партнер</th>
                                <th class="w-24 px-1 py-1.5">Датум</th>
                                <th class="w-40 px-1 py-1.5">Опис</th>
                                <th class="w-20 px-1 py-1.5">Затворање</th>
                                <th class="w-24 px-1 py-1.5 text-right">Долгува</th>
                                <th class="w-24 px-1 py-1.5 text-right">Побарува</th>
                                <th class="w-6"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(line, i) in lines"
                                :key="i"
                                class="group border-b transition-colors"
                                :class="selectedRow === i ? 'bg-primary/5' : 'hover:bg-muted/30'"
                                @click="selectedRow = i"
                            >
                                <!-- # -->
                                <td class="select-none px-1 py-0 text-center text-muted-foreground">{{ i + 1 }}</td>

                                <!-- Конто (account code, autocomplete) -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="account"
                                        :value="line.account_code"
                                        @input="onAccountInput($event, i)"
                                        @keydown="e => handleKeydown(e, i, 'account')"
                                        @focus="selectedRow = i"
                                        @blur="onAccountBlur($event, i)"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 font-mono text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="конто"
                                    />
                                </td>

                                <!-- Назив (readonly) -->
                                <td class="max-w-0 px-1 py-0">
                                    <span class="block truncate text-muted-foreground">{{ line.account_name }}</span>
                                </td>

                                <!-- Партнер (kooperant, autocomplete) -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="partner"
                                        :value="line.kooperant_name"
                                        @input="onPartnerInput($event, i)"
                                        @keydown="e => handleKeydown(e, i, 'partner')"
                                        @focus="selectedRow = i"
                                        @blur="closeAc(200)"
                                        class="h-6 w-full truncate rounded border-0 bg-transparent px-1 text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="—"
                                    />
                                </td>

                                <!-- Датум -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="line_date"
                                        v-model="line.line_date"
                                        type="date"
                                        @change="isDirty = true"
                                        @keydown="e => handleKeydown(e, i, 'line_date')"
                                        @focus="selectedRow = i"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                    />
                                </td>

                                <!-- Опис -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="description"
                                        v-model="line.description"
                                        @input="isDirty = true"
                                        @keydown="e => handleKeydown(e, i, 'description')"
                                        @focus="selectedRow = i"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="опис"
                                    />
                                </td>

                                <!-- Затворање -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="closing_ref"
                                        v-model="line.closing_reference"
                                        @input="isDirty = true"
                                        @keydown="e => handleKeydown(e, i, 'closing_ref')"
                                        @focus="selectedRow = i"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 font-mono text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="—"
                                    />
                                </td>

                                <!-- Долгува (debit) -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="debit"
                                        v-model="line.debit"
                                        type="text"
                                        inputmode="decimal"
                                        @focus="$event.target.select(); selectedRow = i"
                                        @blur="line.debit = parseFloat(line.debit) > 0 ? String(parseFloat(line.debit)) : ''"
                                        @input="isDirty = true"
                                        @keydown="e => handleKeydown(e, i, 'debit')"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 text-right font-mono text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="0.00"
                                    />
                                </td>

                                <!-- Побарува (credit) -->
                                <td class="px-0.5 py-0">
                                    <input
                                        :data-row="i" data-col="credit"
                                        v-model="line.credit"
                                        type="text"
                                        inputmode="decimal"
                                        @focus="$event.target.select(); selectedRow = i"
                                        @blur="line.credit = parseFloat(line.credit) > 0 ? String(parseFloat(line.credit)) : ''"
                                        @input="isDirty = true"
                                        @keydown="e => handleKeydown(e, i, 'credit')"
                                        class="h-6 w-full rounded border-0 bg-transparent px-1 text-right font-mono text-xs outline-none focus:bg-white focus:ring-1 focus:ring-primary dark:focus:bg-zinc-900"
                                        placeholder="0.00"
                                    />
                                </td>

                                <!-- Delete row -->
                                <td class="px-0.5 py-0">
                                    <button
                                        class="flex size-5 items-center justify-center rounded text-muted-foreground opacity-0 transition-opacity hover:bg-destructive/10 hover:text-destructive group-hover:opacity-100"
                                        @click.stop="removeLine(i)"
                                    >
                                        <X class="size-3" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Add row -->
                    <button
                        class="flex w-full items-center gap-1.5 px-3 py-1.5 text-xs text-muted-foreground hover:bg-muted/30 hover:text-foreground"
                        @click="addLine"
                    >
                        <Plus class="size-3" /> Додај ред
                    </button>
                </div>

                <!-- ── FOOTER TOTALS ──────────────────────────────────────── -->
                <div class="flex flex-shrink-0 items-center gap-5 border-t bg-muted/30 px-3 py-1.5">
                    <span class="text-xs text-muted-foreground">
                        Ставки: {{ lines.filter(l => l.account_code).length }}
                    </span>
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="text-muted-foreground">Долгува:</span>
                        <span class="font-mono font-semibold">{{ fmtAmt(totalDebit) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="text-muted-foreground">Побарува:</span>
                        <span class="font-mono font-semibold">{{ fmtAmt(totalCredit) }}</span>
                    </div>
                    <div
                        class="flex items-center gap-1.5 text-xs font-semibold"
                        :class="isBalanced ? 'text-green-600 dark:text-green-400' : 'text-destructive'"
                    >
                        <span>Разлика:</span>
                        <span class="font-mono">{{ fmtAmt(difference) }}</span>
                        <span v-if="isBalanced">✓</span>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT PANEL: Account + Partner + Open invoices ──────────── -->
            <aside class="flex w-56 flex-shrink-0 flex-col overflow-hidden border-l">

                <!-- Account info -->
                <div class="flex-shrink-0 border-b p-2.5">
                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Конто</p>
                    <template v-if="selectedLine?.account_obj">
                        <p class="font-mono text-sm font-bold">{{ selectedLine.account_code }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ selectedLine.account_name }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1">
                            <Badge variant="outline" class="text-[10px]">Класа {{ selectedLine.account_obj.class }}</Badge>
                            <Badge variant="outline" class="text-[10px]">{{ selectedLine.account_obj.account_type }}</Badge>
                        </div>
                    </template>
                    <p v-else class="text-xs italic text-muted-foreground">Изберете ред…</p>
                </div>

                <!-- Partner info -->
                <div class="flex-shrink-0 border-b p-2.5">
                    <p class="mb-1 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">Партнер</p>
                    <template v-if="selectedLine?.kooperant_obj">
                        <p class="text-sm font-medium">{{ selectedLine.kooperant_name }}</p>
                        <div class="mt-1 space-y-0.5 text-[11px] text-muted-foreground">
                            <p v-if="selectedLine.kooperant_obj.edb">ЕДБ: {{ selectedLine.kooperant_obj.edb }}</p>
                            <p v-if="selectedLine.kooperant_obj.embs">ЕМБС: {{ selectedLine.kooperant_obj.embs }}</p>
                            <p v-if="selectedLine.kooperant_obj.address" class="truncate">{{ selectedLine.kooperant_obj.address }}</p>
                            <p v-if="selectedLine.kooperant_obj.phone">{{ selectedLine.kooperant_obj.phone }}</p>
                        </div>
                    </template>
                    <p v-else class="text-xs italic text-muted-foreground">—</p>
                </div>

                <!-- Open invoices -->
                <div class="flex flex-1 flex-col overflow-hidden">
                    <p class="flex-shrink-0 border-b px-2.5 py-1.5 text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
                        Отворени ставки
                        <span v-if="openInvoices.purchases.length + openInvoices.sales.length > 0" class="ml-1 text-primary">
                            {{ openInvoices.purchases.length + openInvoices.sales.length }}
                        </span>
                    </p>
                    <div class="flex-1 overflow-y-auto p-2 text-[11px]">
                        <template v-if="!selectedLine?.kooperant_id">
                            <p class="pt-3 text-center italic text-muted-foreground">Изберете партнер</p>
                        </template>
                        <template v-else-if="openInvoices.purchases.length === 0 && openInvoices.sales.length === 0">
                            <p class="pt-3 text-center italic text-muted-foreground">Нема ставки</p>
                        </template>
                        <template v-else>
                            <!-- Purchase invoices -->
                            <template v-if="openInvoices.purchases.length > 0">
                                <p class="mb-1 font-semibold text-muted-foreground">Влезни</p>
                                <div
                                    v-for="inv in openInvoices.purchases"
                                    :key="'p' + inv.id"
                                    class="mb-1.5 rounded border p-1.5"
                                >
                                    <div class="flex justify-between">
                                        <span class="font-mono font-medium">{{ inv.invoice_number }}</span>
                                        <span class="text-muted-foreground">{{ fmtDate(inv.date) }}</span>
                                    </div>
                                    <div class="mt-0.5 text-right font-mono font-semibold">
                                        {{ fmtAmt(parseFloat(inv.total_amount)) }}
                                    </div>
                                </div>
                            </template>
                            <!-- Sales invoices -->
                            <template v-if="openInvoices.sales.length > 0">
                                <p class="mb-1 mt-1 font-semibold text-muted-foreground">Излезни</p>
                                <div
                                    v-for="inv in openInvoices.sales"
                                    :key="'s' + inv.id"
                                    class="mb-1.5 rounded border p-1.5"
                                >
                                    <div class="flex justify-between">
                                        <span class="font-mono font-medium">{{ inv.invoice_number }}</span>
                                        <span class="text-muted-foreground">{{ fmtDate(inv.date) }}</span>
                                    </div>
                                    <div class="mt-0.5 text-right font-mono font-semibold">
                                        {{ fmtAmt(parseFloat(inv.total_amount)) }}
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- ── AUTOCOMPLETE OVERLAY ──────────────────────────────────────────────── -->
    <Teleport to="body">
        <div
            v-if="ac.show"
            :style="acStyle"
            class="rounded-md border bg-popover text-popover-foreground shadow-lg"
        >
            <div
                v-for="(r, idx) in ac.results"
                :key="ac.field === 'account' ? (r as any).code : (r as any).id"
                class="flex cursor-pointer items-center gap-2 px-2.5 py-1.5 text-xs transition-colors"
                :class="idx === ac.idx ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'"
                @mousedown.prevent="selectAcResult(idx)"
            >
                <template v-if="ac.field === 'account'">
                    <span class="w-14 flex-shrink-0 font-mono font-semibold">{{ (r as any).code }}</span>
                    <span class="flex-1 truncate">{{ (r as any).name }}</span>
                    <span class="flex-shrink-0 text-[10px] text-muted-foreground">к{{ (r as any).class }}</span>
                </template>
                <template v-else>
                    <span class="flex-1 truncate font-medium">{{ (r as any).name }}</span>
                    <span class="flex-shrink-0 font-mono text-[10px] text-muted-foreground">{{ (r as any).edb }}</span>
                </template>
            </div>
        </div>
    </Teleport>
</template>
