<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/formatDate';
import { Eye, FileText } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { JournalGroup } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Финансии', href: '/documents' },
            { title: 'Список', href: '/journal-entries' },
        ],
    },
});

type Entry = {
    id: number;
    entry_date: string;
    group_code: number | null;
    year: number | null;
    sequence_number: number | null;
    reference: string | null;
    description: string | null;
    status: 'draft' | 'posted';
    lines_count: number;
    debit_total: string | null;
    credit_total: string | null;
    creator: { id: number; name: string };
    journal_group: JournalGroup | null;
};

type Paginated = {
    data: Entry[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{ entries: Paginated }>();

const STATUS_LABEL: Record<string, string> = { draft: 'Нацрт', posted: 'Прокнижено' };
const STATUS_VARIANT: Record<string, 'outline' | 'secondary'> = { draft: 'outline', posted: 'secondary' };

function voucherNumber(e: Entry): string {
    if (e.group_code === null || e.sequence_number === null) return '—';
    return `${e.group_code}-${String(e.sequence_number).padStart(4, '0')}`;
}

function fmt(v: string | null): string {
    if (!v) return '0,00';
    return Number(v).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function entryLink(e: Entry): string {
    if (e.group_code !== null && e.sequence_number !== null && e.year !== null) {
        return `/journal-entries/voucher?group=${e.group_code}&year=${e.year}&seq=${e.sequence_number}`;
    }
    return `/journal-entries/${e.id}`;
}
</script>

<template>
    <Head title="Список — Налози" />

    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Налози</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ entries.total }} книговодствени налози</p>
            </div>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50 text-xs">
                        <th class="px-3 py-2 text-left font-medium text-muted-foreground">Налог</th>
                        <th class="px-3 py-2 text-left font-medium text-muted-foreground">Датум</th>
                        <th class="px-3 py-2 text-left font-medium text-muted-foreground">Опис</th>
                        <th class="px-3 py-2 text-right font-medium text-muted-foreground">Дебит</th>
                        <th class="px-3 py-2 text-right font-medium text-muted-foreground">Кредит</th>
                        <th class="px-3 py-2 text-center font-medium text-muted-foreground">Ставки</th>
                        <th class="px-3 py-2 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="entries.data.length === 0">
                        <td colspan="8" class="py-16 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-3 size-10 opacity-30" />
                            Нема налози
                        </td>
                    </tr>
                    <tr
                        v-for="e in entries.data"
                        :key="e.id"
                        class="border-b last:border-0 hover:bg-muted/30"
                    >
                        <!-- Voucher number + group chip -->
                        <td class="px-3 py-2">
                            <span class="font-mono text-sm font-bold text-primary">{{ voucherNumber(e) }}</span>
                            <span v-if="e.journal_group" class="ml-2 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                                {{ e.journal_group.name }}
                            </span>
                        </td>

                        <!-- Date -->
                        <td class="px-3 py-2 font-mono text-xs">{{ formatDate(e.entry_date) }}</td>

                        <!-- Description -->
                        <td class="max-w-xs px-3 py-2 text-sm">
                            <span class="line-clamp-1">{{ e.description ?? '—' }}</span>
                            <span v-if="e.reference" class="text-xs text-muted-foreground">{{ e.reference }}</span>
                        </td>

                        <!-- Debit total -->
                        <td class="px-3 py-2 text-right font-mono text-xs">{{ fmt(e.debit_total) }}</td>

                        <!-- Credit total -->
                        <td class="px-3 py-2 text-right font-mono text-xs">{{ fmt(e.credit_total) }}</td>

                        <!-- Lines count -->
                        <td class="px-3 py-2 text-center">
                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                                {{ e.lines_count }}
                            </span>
                        </td>

                        <!-- Status -->
                        <td class="px-3 py-2">
                            <Badge :variant="STATUS_VARIANT[e.status]" class="text-xs">
                                {{ STATUS_LABEL[e.status] }}
                            </Badge>
                        </td>

                        <!-- Actions -->
                        <td class="px-3 py-2">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="entryLink(e)">
                                    <Eye class="size-4" />
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="entries.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in entries.links"
                :key="link.label"
                :variant="link.active ? 'default' : 'outline'"
                size="sm"
                :disabled="!link.url"
                v-html="link.label"
                @click="link.url && $inertia.visit(link.url)"
            />
        </div>

    </div>
</template>