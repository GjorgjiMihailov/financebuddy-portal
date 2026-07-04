<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { formatDate } from '@/lib/formatDate';
import { Pencil, Trash2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { JournalEntry } from '@/types';
import { JOURNAL_STATUS_LABELS, JOURNAL_STATUS_VARIANT } from '@/types';

const props = defineProps<{
    entry: JournalEntry;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Документи', href: '/documents' },
            { title: 'Книжење', href: '#' },
        ],
    },
});

const postForm = useForm({});

function fmt(val: string | number): string {
    const n = Number(val);
    if (n === 0) return '—';
    return n.toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}


const totalDebit  = props.entry.lines?.reduce((s, l) => s + Number(l.debit  || 0), 0) ?? 0;
const totalCredit = props.entry.lines?.reduce((s, l) => s + Number(l.credit || 0), 0) ?? 0;

function voucherLink(): string | null {
    const e = props.entry;
    if (e.group_code !== null && e.sequence_number !== null && e.year !== null) {
        return `/journal-entries/voucher?group=${e.group_code}&year=${e.year}&seq=${e.sequence_number}`;
    }
    return null;
}

function deleteEntry() {
    if (!confirm(`Избриши налог ${props.entry.group_code}-${String(props.entry.sequence_number).padStart(4,'0')}?`)) return;
    router.delete(`/journal-entries/${props.entry.id}`);
}
</script>

<template>
    <Head title="Книжење" />

    <div class="mx-auto max-w-3xl space-y-6 p-6">

        <!-- Header -->
        <Card>
            <CardHeader>
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3">
                            <span
                                v-if="entry.group_code !== null && entry.sequence_number !== null"
                                class="rounded-md bg-primary/10 px-2.5 py-1 font-mono text-lg font-bold text-primary"
                            >
                                {{ entry.group_code }}-{{ String(entry.sequence_number).padStart(4, '0') }}
                            </span>
                            <div>
                                <CardTitle class="text-base">{{ entry.description }}</CardTitle>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ entry.journal_group?.name ?? '' }}
                                    <span v-if="entry.journal_group && entry.year"> · {{ entry.year }}</span>
                                </p>
                            </div>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">
                            {{ formatDate(entry.entry_date) }}
                            <span v-if="entry.reference"> · {{ entry.reference }}</span>
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Badge :variant="JOURNAL_STATUS_VARIANT[entry.status]">
                            {{ JOURNAL_STATUS_LABELS[entry.status] }}
                        </Badge>
                        <Button
                            v-if="voucherLink()"
                            variant="outline"
                            size="sm"
                            as-child
                        >
                            <Link :href="voucherLink()!">
                                <Pencil class="mr-1.5 size-3.5" />Уреди
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive hover:text-destructive"
                            @click="deleteEntry"
                        >
                            <Trash2 class="mr-1.5 size-3.5" />Избриши
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Компанија</dt>
                        <dd>
                            <Link :href="`/companies/${entry.company_id}`" class="text-primary hover:underline">
                                {{ entry.company?.name }}
                            </Link>
                        </dd>
                    </div>
                    <div class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Документ</dt>
                        <dd>
                            <Link
                                v-if="entry.document"
                                :href="`/documents/${entry.document_id}`"
                                class="text-primary hover:underline"
                            >
                                {{ entry.document.original_filename }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                        </dd>
                    </div>
                    <div class="flex justify-between border-b pb-3 sm:border-b-0 sm:pb-0">
                        <dt class="text-muted-foreground">Внел</dt>
                        <dd>{{ entry.creator?.name ?? '—' }}</dd>
                    </div>
                    <div v-if="entry.poster" class="flex justify-between">
                        <dt class="text-muted-foreground">Прокнижил</dt>
                        <dd>{{ entry.poster.name }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- Lines -->
        <Card>
            <CardHeader>
                <CardTitle class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Ставки
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30 text-left text-xs text-muted-foreground">
                                <th class="px-4 py-2">Сметка</th>
                                <th class="px-4 py-2">Опис</th>
                                <th class="px-4 py-2 text-right">Дебит</th>
                                <th class="px-4 py-2 text-right">Кредит</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="line in entry.lines"
                                :key="line.id"
                                class="border-b last:border-0"
                            >
                                <td class="px-4 py-3">
                                    <span class="rounded bg-primary/10 px-1.5 py-0.5 font-mono text-xs text-primary">
                                        {{ line.account_code }}
                                    </span>
                                    <span class="ml-1.5 text-muted-foreground">{{ line.account?.name }}</span>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ line.description ?? '—' }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(line.debit) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ fmt(line.credit) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/30 font-semibold">
                                <td colspan="2" class="px-4 py-2 text-muted-foreground text-xs uppercase tracking-wide">Вкупно</td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    {{ totalDebit.toLocaleString('mk-MK', { minimumFractionDigits: 2 }) }}
                                </td>
                                <td class="px-4 py-2 text-right tabular-nums">
                                    {{ totalCredit.toLocaleString('mk-MK', { minimumFractionDigits: 2 }) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </CardContent>
        </Card>

        <!-- Post action -->
        <div
            v-if="entry.status === 'draft'"
            class="flex items-center justify-between rounded-lg border bg-card p-4"
        >
            <div>
                <p class="text-sm font-medium">Готово за прокнижување</p>
                <p class="text-xs text-muted-foreground">По прокнижување нацртот не може да се менува.</p>
            </div>
            <Button
                size="sm"
                :disabled="postForm.processing"
                @click="postForm.post(`/journal-entries/${entry.id}/post`)"
            >
                {{ postForm.processing ? 'Се прокнижува...' : 'Прокнижи' }}
            </Button>
        </div>

    </div>
</template>
