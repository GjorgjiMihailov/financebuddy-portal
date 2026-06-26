<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Clock, FileText } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    DOCUMENT_STATUS_LABELS,
    DOCUMENT_STATUS_VARIANT,
    DOCUMENT_TYPE_LABELS,
    type DocumentFile,
    type DocumentStatus,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Документи', href: '/documents' },
            { title: 'Детали', href: '#' },
        ],
    },
});

defineProps<{
    document: DocumentFile;
}>();

function formatSize(bytes: number): string {
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}
</script>

<template>
    <Head :title="document.filename" />

    <div class="mx-auto max-w-2xl p-6">
        <div class="mb-6 flex items-center justify-between">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/documents">
                    <ArrowLeft class="mr-2 size-4" />
                    Назад
                </Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10">
                            <FileText class="size-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-base">{{ document.filename }}</CardTitle>
                            <p class="text-sm text-muted-foreground">{{ formatSize(document.file_size) }}</p>
                        </div>
                    </div>
                    <Badge :variant="DOCUMENT_STATUS_VARIANT[document.status as DocumentStatus]">
                        {{ DOCUMENT_STATUS_LABELS[document.status as DocumentStatus] }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-3 text-sm">
                    <div class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Компанија</dt>
                        <dd>
                            <Link :href="`/companies/${document.company_id}`" class="text-primary hover:underline">
                                {{ document.company?.name }}
                            </Link>
                        </dd>
                    </div>
                    <div class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Тип</dt>
                        <dd>{{ DOCUMENT_TYPE_LABELS[document.type] }}</dd>
                    </div>
                    <div class="flex justify-between border-b pb-3">
                        <dt class="text-muted-foreground">Прикачено од</dt>
                        <dd>{{ document.uploader?.name ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Датум</dt>
                        <dd>{{ new Date(document.created_at).toLocaleDateString('mk-MK') }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>

        <!-- AI резултати (кога ќе бидат достапни) -->
        <div
            v-if="document.status === 'pending' || document.status === 'ai_processing'"
            class="mt-6 flex items-center gap-3 rounded-lg border border-dashed p-6 text-muted-foreground"
        >
            <Clock class="size-5 shrink-0" />
            <div>
                <p class="font-medium">AI обработка во тек</p>
                <p class="text-xs">Документот се обработува. Страната ќе се ажурира автоматски.</p>
            </div>
        </div>
    </div>
</template>
