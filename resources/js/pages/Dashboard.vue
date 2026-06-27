<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building2,
    CheckCircle2,
    Clock,
    FileText,
    Users,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { DocumentStatus, DocumentType } from '@/types';
import { DOCUMENT_STATUS_LABELS, DOCUMENT_STATUS_VARIANT, DOCUMENT_TYPE_LABELS } from '@/types';

type Stats = {
    awaiting_verification: number;
    verified: number;
    processing: number;
    total_documents: number;
    total_companies: number | null;
    total_users: number | null;
};

type RecentDocument = {
    id: number;
    original_filename: string;
    type: DocumentType;
    status: DocumentStatus;
    company: { id: number; name: string } | null;
    uploader: { id: number; name: string } | null;
    created_at: string;
};

defineProps<{
    stats: Stats;
    recentDocuments: RecentDocument[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Контролна табла', href: dashboard() }],
    },
});

function formatDate(iso: string): string {
    return new Date(iso).toLocaleDateString('mk-MK', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Контролна табла" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Stats cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <!-- Awaiting verification — primary action card -->
            <Link
                href="/documents?status=ai_processed"
                class="flex items-center gap-4 rounded-lg border bg-amber-50 p-4 transition-colors hover:bg-amber-100 dark:bg-amber-950/20 dark:hover:bg-amber-950/40"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                    <AlertCircle class="size-5 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.awaiting_verification }}</p>
                    <p class="text-sm text-muted-foreground">Чека верификација</p>
                </div>
            </Link>

            <!-- Verified -->
            <div class="flex items-center gap-4 rounded-lg border p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                    <CheckCircle2 class="size-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.verified }}</p>
                    <p class="text-sm text-muted-foreground">Верифицирани</p>
                </div>
            </div>

            <!-- Processing -->
            <div class="flex items-center gap-4 rounded-lg border p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <Clock class="size-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.processing }}</p>
                    <p class="text-sm text-muted-foreground">Во обработка</p>
                </div>
            </div>

            <!-- Total documents -->
            <div class="flex items-center gap-4 rounded-lg border p-4">
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                    <FileText class="size-5 text-muted-foreground" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.total_documents }}</p>
                    <p class="text-sm text-muted-foreground">Вкупно документи</p>
                </div>
            </div>

            <!-- Companies (admin/accountant only) -->
            <Link
                v-if="stats.total_companies !== null"
                href="/companies"
                class="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                    <Building2 class="size-5 text-muted-foreground" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.total_companies }}</p>
                    <p class="text-sm text-muted-foreground">Компании</p>
                </div>
            </Link>

            <!-- Users (admin only) -->
            <Link
                v-if="stats.total_users !== null"
                href="/users"
                class="flex items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/50"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-full bg-muted">
                    <Users class="size-5 text-muted-foreground" />
                </div>
                <div>
                    <p class="text-2xl font-bold">{{ stats.total_users }}</p>
                    <p class="text-sm text-muted-foreground">Корисници</p>
                </div>
            </Link>
        </div>

        <!-- Recent documents -->
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold">Последни документи</h2>
                <Link href="/documents" class="text-sm text-muted-foreground hover:text-foreground">
                    Сите документи →
                </Link>
            </div>

            <div class="rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Документ</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Компанија</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Тип</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Датум</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentDocuments.length === 0">
                            <td colspan="5" class="py-12 text-center text-muted-foreground">
                                <FileText class="mx-auto mb-3 size-8 opacity-30" />
                                <p>Нема документи сè уште</p>
                            </td>
                        </tr>
                        <tr
                            v-for="doc in recentDocuments"
                            :key="doc.id"
                            class="cursor-pointer border-b last:border-0 transition-colors hover:bg-muted/30"
                            @click="$inertia.visit(`/documents/${doc.id}`)"
                        >
                            <td class="max-w-48 truncate px-4 py-3 font-medium">
                                {{ doc.original_filename }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ doc.company?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ DOCUMENT_TYPE_LABELS[doc.type] }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="DOCUMENT_STATUS_VARIANT[doc.status]">
                                    {{ DOCUMENT_STATUS_LABELS[doc.status] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(doc.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
