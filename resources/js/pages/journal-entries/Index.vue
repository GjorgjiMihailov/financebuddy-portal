<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { formatDate } from '@/lib/formatDate';
import { Eye, FileText } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Финансии', href: '/documents' },
            { title: 'Книжења', href: '/journal-entries' },
        ],
    },
});

type Entry = {
    id: number;
    entry_date: string;
    reference: string | null;
    description: string | null;
    status: 'draft' | 'posted';
    document: { id: number; original_filename: string } | null;
    creator: { id: number; name: string };
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

.${m}.${y}`;
}
</script>

<template>
    <Head title="Книжења" />

    <div class="flex flex-col gap-6 p-6">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Книжења</h1>
                <p class="mt-0.5 text-sm text-muted-foreground">{{ entries.total }} налози</p>
            </div>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Датум</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Референца</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Опис</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Документ</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Составил</th>
                        <th class="px-3 py-1.5"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="entries.data.length === 0">
                        <td colspan="7" class="py-16 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-3 size-10 opacity-30" />
                            Нема книжења
                        </td>
                    </tr>
                    <tr
                        v-for="e in entries.data"
                        :key="e.id"
                        class="border-b last:border-0 hover:bg-muted/30"
                    >
                        <td class="px-3 py-1.5 font-mono text-xs">{{ formatDate(e.entry_date) }}</td>
                        <td class="px-3 py-1.5 font-mono text-xs text-muted-foreground">{{ e.reference ?? '—' }}</td>
                        <td class="px-3 py-1.5">{{ e.description ?? '—' }}</td>
                        <td class="px-3 py-1.5 text-xs text-muted-foreground">
                            {{ e.document?.original_filename ?? '—' }}
                        </td>
                        <td class="px-3 py-1.5">
                            <Badge :variant="STATUS_VARIANT[e.status]" class="text-xs">
                                {{ STATUS_LABEL[e.status] }}
                            </Badge>
                        </td>
                        <td class="px-3 py-1.5 text-muted-foreground">{{ e.creator.name }}</td>
                        <td class="px-3 py-1.5">
                            <Button variant="ghost" size="icon" as-child>
                                <Link :href="`/journal-entries/${e.id}`">
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