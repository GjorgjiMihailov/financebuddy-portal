<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, CheckCircle2, Clock, FileText, AlertCircle, Pencil, Plus } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Company, DocumentFile, DocumentStatus, DocumentType } from '@/types';
import { DOCUMENT_STATUS_LABELS, DOCUMENT_STATUS_VARIANT, DOCUMENT_TYPE_LABELS } from '@/types';

type Stats = {
    total: number;
    processing: number;
    awaiting: number;
    verified: number;
};

type PaginatedDocs = {
    data: DocumentFile[];
    current_page: number;
    last_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
};

defineProps<{
    company: Company;
    stats: Stats;
    documents: PaginatedDocs;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Компании', href: '/companies' },
            { title: 'Детали', href: '#' },
        ],
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
    <Head :title="company.name" />

    <div class="flex flex-col gap-6 p-6">

        <!-- Header -->
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-4">
                <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary/10">
                    <Building2 class="size-6 text-primary" />
                </div>
                <div>
                    <h1 class="text-2xl font-semibold">{{ company.name }}</h1>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="font-mono text-sm text-muted-foreground">{{ company.tax_id }}</span>
                        <Badge v-if="company.is_vat_registered" variant="secondary">ДДВ обврзник</Badge>
                    </div>
                </div>
            </div>
            <Button as-child>
                <Link :href="`/companies/${company.id}/edit`">
                    <Pencil class="mr-2 size-4" />
                    Уреди
                </Link>
            </Button>
        </div>

        <!-- Company info + Stats -->
        <div class="grid gap-4 lg:grid-cols-3">

            <!-- Info card -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Основни податоци</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-3 text-sm">
                        <div v-if="company.vat_number" class="flex justify-between border-b pb-3">
                            <dt class="text-muted-foreground">ДДВ број</dt>
                            <dd class="font-mono">{{ company.vat_number }}</dd>
                        </div>
                        <div v-if="company.address" class="flex justify-between border-b pb-3">
                            <dt class="text-muted-foreground">Адреса</dt>
                            <dd class="text-right">{{ company.address }}</dd>
                        </div>
                        <div v-if="company.email" class="flex justify-between border-b pb-3">
                            <dt class="text-muted-foreground">Е-пошта</dt>
                            <dd>
                                <a :href="`mailto:${company.email}`" class="text-primary hover:underline">
                                    {{ company.email }}
                                </a>
                            </dd>
                        </div>
                        <div v-if="company.phone" class="flex justify-between border-b pb-3">
                            <dt class="text-muted-foreground">Телефон</dt>
                            <dd>
                                <a :href="`tel:${company.phone}`">{{ company.phone }}</a>
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Додадено од</dt>
                            <dd>{{ company.creator?.name ?? '—' }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <!-- Stats -->
            <div class="flex flex-col gap-3 lg:col-span-2">
                <div class="grid grid-cols-2 gap-3">
                    <div class="flex items-center gap-3 rounded-lg border p-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-muted">
                            <FileText class="size-4 text-muted-foreground" />
                        </div>
                        <div>
                            <p class="text-xl font-bold">{{ stats.total }}</p>
                            <p class="text-xs text-muted-foreground">Вкупно документи</p>
                        </div>
                    </div>

                    <Link
                        :href="`/documents?company_id=${company.id}&status=ai_processed`"
                        class="flex items-center gap-3 rounded-lg border bg-amber-50 p-4 transition-colors hover:bg-amber-100 dark:bg-amber-950/20"
                    >
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/40">
                            <AlertCircle class="size-4 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <p class="text-xl font-bold">{{ stats.awaiting }}</p>
                            <p class="text-xs text-muted-foreground">Чека верификација</p>
                        </div>
                    </Link>

                    <div class="flex items-center gap-3 rounded-lg border p-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <Clock class="size-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-xl font-bold">{{ stats.processing }}</p>
                            <p class="text-xs text-muted-foreground">Во обработка</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-lg border p-4">
                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                            <CheckCircle2 class="size-4 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-xl font-bold">{{ stats.verified }}</p>
                            <p class="text-xs text-muted-foreground">Верифицирани</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents table -->
        <div>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="font-semibold">Документи</h2>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="`/documents?company_id=${company.id}`">
                            Сите документи
                        </Link>
                    </Button>
                    <Button size="sm" as-child>
                        <Link :href="`/documents/create?company_id=${company.id}`">
                            <Plus class="mr-2 size-3.5" />
                            Прикачи
                        </Link>
                    </Button>
                </div>
            </div>

            <div class="rounded-lg border">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/50">
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Документ</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Тип</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Статус</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Качил</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Датум</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="documents.data.length === 0">
                            <td colspan="5" class="py-12 text-center text-muted-foreground">
                                <FileText class="mx-auto mb-3 size-8 opacity-30" />
                                <p>Нема документи за оваа компанија</p>
                            </td>
                        </tr>
                        <tr
                            v-for="doc in documents.data"
                            :key="doc.id"
                            class="cursor-pointer border-b last:border-0 transition-colors hover:bg-muted/30"
                            @click="router.visit(`/documents/${doc.id}`)"
                        >
                            <td class="max-w-52 truncate px-4 py-3 font-medium">
                                {{ doc.original_filename }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ DOCUMENT_TYPE_LABELS[doc.type as DocumentType] }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="DOCUMENT_STATUS_VARIANT[doc.status as DocumentStatus]">
                                    {{ DOCUMENT_STATUS_LABELS[doc.status as DocumentStatus] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ doc.uploader?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ formatDate(doc.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="documents.last_page > 1" class="mt-4 flex justify-center gap-1">
                <Button
                    v-for="link in documents.links"
                    :key="link.label"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    :disabled="!link.url"
                    v-html="link.label"
                    @click="link.url && router.visit(link.url, { preserveScroll: true })"
                />
            </div>
        </div>

    </div>
</template>
