<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, FileText } from '@lucide/vue';
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

const props = defineProps<{
    document: DocumentFile;
}>();

function formatSize(bytes: number): string {
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function formatAmount(val: string | number | null): string {
    if (val === null || val === undefined) return '—';
    return Number(val).toLocaleString('mk-MK', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDate(val: string | null): string {
    if (!val) return '—';
    return new Date(val).toLocaleDateString('mk-MK');
}

const ext = props.document.extraction;
const hasExtraction = !!ext;
const isProcessing = ['pending', 'ai_processing'].includes(props.document.status);
const isRejected = props.document.status === 'rejected';

const verifyForm = useForm({});
function verify() {
    verifyForm.post(`/documents/${props.document.id}/verify`);
}
</script>

<template>
    <Head :title="document.original_filename" />

    <div class="mx-auto max-w-3xl space-y-6 p-6">
        <div class="flex items-center justify-between">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/documents">
                    <ArrowLeft class="mr-2 size-4" />
                    Назад
                </Link>
            </Button>
        </div>

        <!-- Заглавие -->
        <Card>
            <CardHeader>
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10">
                            <FileText class="size-5 text-primary" />
                        </div>
                        <div>
                            <CardTitle class="text-base">{{ document.original_filename }}</CardTitle>
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

        <!-- Во обработка -->
        <div
            v-if="isProcessing"
            class="flex items-center gap-3 rounded-lg border border-dashed p-6 text-muted-foreground"
        >
            <Clock class="size-5 shrink-0" />
            <div>
                <p class="font-medium">AI обработка во тек</p>
                <p class="text-xs">Документот се обработува. Страната ќе се ажурира автоматски.</p>
            </div>
        </div>

        <!-- Одбиен -->
        <div
            v-else-if="isRejected"
            class="flex items-center gap-3 rounded-lg border border-dashed border-destructive/40 bg-destructive/5 p-6 text-destructive"
        >
            <div>
                <p class="font-medium">Обработката не успеа</p>
                <p class="text-xs text-muted-foreground">Обиди се повторно со качување на документот.</p>
            </div>
        </div>

        <!-- AI резултати -->
        <template v-else-if="hasExtraction">

            <!-- Податоци од документот -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                        Извадени податоци
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Добавувач</dt>
                            <dd class="font-medium">{{ ext!.vendor_name ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">ДДВ број на добавувач</dt>
                            <dd>{{ ext!.vendor_vat_number ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Купувач</dt>
                            <dd class="font-medium">{{ ext!.customer_name ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Даночен број на купувач</dt>
                            <dd>{{ ext!.customer_tax_id ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Број на документ</dt>
                            <dd>{{ ext!.document_number ?? '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Датум</dt>
                            <dd>{{ formatDate(ext!.document_date) }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Рок на плаќање</dt>
                            <dd>{{ formatDate(ext!.due_date) }}</dd>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <dt class="text-xs text-muted-foreground">Валута</dt>
                            <dd>{{ ext!.currency }}</dd>
                        </div>
                    </dl>

                    <div class="mt-4 grid grid-cols-3 gap-3 rounded-lg bg-muted/40 p-4 text-sm">
                        <div class="flex flex-col gap-0.5 text-center">
                            <span class="text-xs text-muted-foreground">Основица</span>
                            <span class="font-semibold">{{ formatAmount(ext!.subtotal) }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5 text-center">
                            <span class="text-xs text-muted-foreground">ДДВ</span>
                            <span class="font-semibold">{{ formatAmount(ext!.vat_amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-0.5 text-center">
                            <span class="text-xs text-muted-foreground">Вкупно</span>
                            <span class="text-lg font-bold">{{ formatAmount(ext!.total_amount) }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Ставки -->
            <Card v-if="document.line_items && document.line_items.length > 0">
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
                                    <th class="px-4 py-2">Опис</th>
                                    <th class="px-4 py-2 text-right">Кол.</th>
                                    <th class="px-4 py-2 text-right">Цена</th>
                                    <th class="px-4 py-2 text-right">ДДВ%</th>
                                    <th class="px-4 py-2 text-right">Вкупно</th>
                                    <th class="px-4 py-2">Сметка</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="item in document.line_items"
                                    :key="item.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="px-4 py-3">{{ item.description }}</td>
                                    <td class="px-4 py-3 text-right text-muted-foreground">
                                        {{ item.quantity ?? '—' }} {{ item.unit ?? '' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">{{ formatAmount(item.unit_price) }}</td>
                                    <td class="px-4 py-3 text-right">{{ item.vat_rate }}%</td>
                                    <td class="px-4 py-3 text-right font-medium">{{ formatAmount(item.total_amount) }}</td>
                                    <td class="px-4 py-3">
                                        <span v-if="item.suggested_account_code" class="inline-flex items-center gap-1">
                                            <span class="rounded bg-primary/10 px-1.5 py-0.5 font-mono text-xs text-primary">
                                                {{ item.suggested_account_code }}
                                            </span>
                                            <span class="text-xs text-muted-foreground">
                                                {{ item.suggested_account?.name }}
                                            </span>
                                        </span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </CardContent>
            </Card>

            <!-- Верификација -->
            <div
                v-if="document.status === 'ai_processed'"
                class="flex items-center justify-between rounded-lg border bg-card p-4"
            >
                <div class="flex items-center gap-3">
                    <CheckCircle class="size-5 text-primary" />
                    <div>
                        <p class="text-sm font-medium">Готово за верификација</p>
                        <p class="text-xs text-muted-foreground">Тамара треба да ги потврди извадените податоци.</p>
                    </div>
                </div>
                <Button size="sm" :disabled="verifyForm.processing" @click="verify">
                    Верифицирај
                </Button>
            </div>

        </template>
    </div>
</template>
