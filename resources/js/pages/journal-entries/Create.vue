<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import EntitySearchSelect from '@/components/EntitySearchSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { DocumentFile, JournalGroup } from '@/types';

type AccountGroup = {
    [classNum: string]: { code: string; name: string; class: number }[];
};

const CLASS_LABELS: Record<string, string> = {
    '1': '1 — Основни средства',
    '2': '2 — Залихи',
    '3': '3 — Побарувања и парични средства',
    '4': '4 — Обврски',
    '5': '5 — Капитал и резерви',
    '6': '6 — Расходи',
    '7': '7 — Приходи',
    '8': '8 — Резултат',
    '9': '9 — Вонбилансна евиденција',
};

const props = defineProps<{
    document: DocumentFile;
    accounts: AccountGroup;
    prefillLines: {
        account_code: string; description: string; debit: number; credit: number;
        kontragent_id?: number | null; kontragent_name?: string | null; closing_reference?: string | null;
    }[];
    journalGroups: JournalGroup[];
    suggestedGroupCode: number | null;
    defaultDescription: string;
    suggestedKontragent: { id: number; name: string } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Документи', href: '/documents' },
            { title: 'Книжење', href: '#' },
        ],
    },
});

const emptyLine = () => ({ account_code: '', description: '', debit: 0, credit: 0, kontragent_id: null as number | null, kontragent_name: '', closing_reference: '' });

const form = useForm({
    group_code:       props.suggestedGroupCode ?? null as number | null,
    description:      props.defaultDescription || '',
    entry_date:       props.document.extraction?.document_date ?? new Date().toISOString().slice(0, 10),
    reference:        props.document.extraction?.document_number ?? '',
    kontragent_id:    props.suggestedKontragent?.id ?? null as number | null,
    sequence_number:  null as number | null,
    post_immediately: false,
    lines:            props.prefillLines.length >= 2
        ? props.prefillLines.map(l => ({
            account_code: l.account_code,
            description: l.description,
            debit: l.debit,
            credit: l.credit,
            kontragent_id: l.kontragent_id ?? null,
            kontragent_name: l.kontragent_name ?? '',
            closing_reference: l.closing_reference ?? '',
        }))
        : [emptyLine(), emptyLine()],
});

const kontragentLabel = ref(props.suggestedKontragent?.name ?? '');

function onKontragentSelect(item: any) {
    form.kontragent_id = item?.id ?? null;
}

const nextVoucherPreview = ref<string | null>(null);

async function loadNextVoucher() {
    if (form.group_code === null || !form.entry_date) {
        nextVoucherPreview.value = null;
        return;
    }
    const year = new Date(form.entry_date).getFullYear();
    const res = await fetch(`/api/journal-groups/next-sequence?group_code=${form.group_code}&year=${year}&company_id=${props.document.company_id}&date=${form.entry_date}`);
    if (res.ok) {
        const data = await res.json();
        nextVoucherPreview.value = data.voucher_number;
        const parts = (data.voucher_number as string).split('-');
        if (parts.length === 2) {
            form.sequence_number = parseInt(parts[1]);
        }
    }
}

watch(() => [form.group_code, form.entry_date], loadNextVoucher);

const totalDebit  = computed(() => form.lines.reduce((s, l) => s + Number(l.debit  || 0), 0));
const totalCredit = computed(() => form.lines.reduce((s, l) => s + Number(l.credit || 0), 0));
const isBalanced  = computed(() => Math.abs(totalDebit.value - totalCredit.value) < 0.005);

function addLine() {
    form.lines.push(emptyLine());
}

function removeLine(i: number) {
    if (form.lines.length > 2) form.lines.splice(i, 1);
}

function fmt(n: number) {
    return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function submitDraft() {
    form.post_immediately = false;
    form.post(`/documents/${props.document.id}/journal-entry`);
}

function submitPost() {
    form.post_immediately = true;
    form.post(`/documents/${props.document.id}/journal-entry`);
}
</script>

<template>
    <Head title="Ново книжење" />

    <div class="flex flex-col gap-6 p-6">
        <div>
            <h1 class="text-2xl font-semibold">Ново книжење</h1>
            <p class="mt-0.5 flex items-center gap-2 text-sm text-muted-foreground">
                {{ document.original_filename }} —
                <Link :href="`/companies/${document.company_id}`" class="hover:underline">
                    {{ document.company?.name }}
                </Link>
                <span class="rounded bg-muted px-1.5 py-0.5 text-xs font-medium">
                    {{ document.type ?? '—' }}
                </span>
            </p>
        </div>

        <form class="flex flex-col gap-6" @submit.prevent>

            <!-- Header fields -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="sm:col-span-2 grid gap-2">
                    <Label for="description">Опис <span class="text-destructive">*</span></Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        placeholder="пр. Влезна фактура — Добавувач"
                        :class="{ 'border-destructive': form.errors.description }"
                    />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="grid gap-2">
                    <Label for="entry_date">Датум <span class="text-destructive">*</span></Label>
                    <Input
                        id="entry_date"
                        v-model="form.entry_date"
                        type="date"
                        :class="{ 'border-destructive': form.errors.entry_date }"
                    />
                    <InputError :message="form.errors.entry_date" />
                </div>
                <div class="grid gap-2">
                    <Label for="reference">Референца</Label>
                    <Input
                        id="reference"
                        v-model="form.reference"
                        placeholder="бр. на фактура"
                    />
                </div>
                <div class="grid gap-2">
                    <Label>Фирма (партнер)</Label>
                    <EntitySearchSelect
                        endpoint="/api/partners/search"
                        :initial-label="kontragentLabel"
                        placeholder="Име, ЕДБ"
                        @select="onKontragentSelect"
                    />
                </div>
                <div class="grid gap-2">
                    <Label for="group_code">Група налог</Label>
                    <select
                        id="group_code"
                        v-model="form.group_code"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                    >
                        <option :value="null">— без група —</option>
                        <option
                            v-for="g in journalGroups"
                            :key="g.code"
                            :value="g.code"
                        >
                            {{ g.code }} — {{ g.name }}
                        </option>
                    </select>
                </div>
                <div v-if="form.group_code" class="grid gap-2">
                    <Label for="sequence_number">Број на налог</Label>
                    <Input
                        id="sequence_number"
                        v-model.number="form.sequence_number"
                        type="number"
                        min="1"
                        placeholder="авто"
                        class="font-mono"
                    />
                    <p v-if="form.sequence_number && form.group_code" class="font-mono text-xs font-semibold text-primary">
                        {{ form.group_code }}-{{ String(form.sequence_number).padStart(4, '0') }}
                    </p>
                </div>
            </div>

            <!-- Lines table -->
            <div>
                <div class="mb-2 flex items-center justify-between">
                    <Label>Ставки <span class="text-destructive">*</span></Label>
                </div>

                <div class="rounded-lg border overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/50">
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground w-56">Сметка</th>
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground">Опис</th>
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground w-48">Фирма</th>
                                <th class="px-3 py-2 text-left font-medium text-muted-foreground w-36">Затворање</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground w-32">Дебит</th>
                                <th class="px-3 py-2 text-right font-medium text-muted-foreground w-32">Кредит</th>
                                <th class="w-10" />
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(line, i) in form.lines"
                                :key="i"
                                class="border-b last:border-0"
                            >
                                <td class="px-3 py-2">
                                    <select
                                        v-model="line.account_code"
                                        class="w-full rounded-md border border-input bg-background px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-ring"
                                        :class="{ 'border-destructive': form.errors[`lines.${i}.account_code`] }"
                                    >
                                        <option value="">— изберете —</option>
                                        <optgroup
                                            v-for="(accs, cls) in accounts"
                                            :key="cls"
                                            :label="CLASS_LABELS[cls] ?? `Класа ${cls}`"
                                        >
                                            <option
                                                v-for="acc in accs"
                                                :key="acc.code"
                                                :value="acc.code"
                                            >
                                                {{ acc.code }} — {{ acc.name }}
                                            </option>
                                        </optgroup>
                                    </select>
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="line.description"
                                        placeholder="опционален опис"
                                        class="h-8 text-sm"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <EntitySearchSelect
                                        endpoint="/api/partners/search"
                                        :initial-label="line.kontragent_name"
                                        placeholder="—"
                                        @select="(item: any) => { line.kontragent_id = item?.id ?? null; line.kontragent_name = item?.name ?? ''; }"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model="line.closing_reference"
                                        placeholder="нпр. ф-ра: 29/23"
                                        class="h-8 text-sm"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model.number="line.debit"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-8 text-right text-sm"
                                        :class="{ 'border-destructive': form.errors[`lines.${i}.debit`] }"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Input
                                        v-model.number="line.credit"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-8 text-right text-sm"
                                        :class="{ 'border-destructive': form.errors[`lines.${i}.credit`] }"
                                    />
                                </td>
                                <td class="px-3 py-2">
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        class="size-7 text-muted-foreground hover:text-destructive"
                                        :disabled="form.lines.length <= 2"
                                        @click="removeLine(i)"
                                    >
                                        <Trash2 class="size-3.5" />
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/30">
                                <td colspan="4" class="px-3 py-2">
                                    <Button type="button" variant="ghost" size="sm" class="h-7 text-xs" @click="addLine">
                                        <Plus class="mr-1 size-3" />
                                        Додај ред
                                    </Button>
                                </td>
                                <td class="px-3 py-2 text-right font-semibold tabular-nums">
                                    {{ fmt(totalDebit) }}
                                </td>
                                <td class="px-3 py-2 text-right font-semibold tabular-nums">
                                    {{ fmt(totalCredit) }}
                                </td>
                                <td />
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <InputError :message="(form.errors as any).lines" class="mt-1" />

                <div
                    v-if="totalDebit > 0 || totalCredit > 0"
                    class="mt-2 text-xs"
                    :class="isBalanced ? 'text-green-600' : 'text-destructive'"
                >
                    {{ isBalanced ? '✓ Книжењето е балансирано' : `Разлика: ${fmt(Math.abs(totalDebit - totalCredit))}` }}
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 border-t pt-4">
                <Button type="button" variant="outline" as-child>
                    <Link :href="`/documents/${document.id}`">Откажи</Link>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="form.processing"
                    @click="submitDraft"
                >
                    {{ form.processing && !form.post_immediately ? 'Се зачувува...' : 'Зачувај нацрт' }}
                </Button>
                <Button
                    type="button"
                    :disabled="form.processing || !isBalanced"
                    @click="submitPost"
                >
                    {{ form.processing && form.post_immediately ? 'Се прокнижува...' : 'Прокнижи' }}
                </Button>
            </div>
        </form>
    </div>
</template>
