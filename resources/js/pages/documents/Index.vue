<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DOCUMENT_STATUS_LABELS,
    DOCUMENT_STATUS_VARIANT,
    DOCUMENT_TYPE_LABELS,
    type DocumentFile,
    type DocumentStatus,
    type PaginatedDocuments,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Документи', href: '/documents' }],
    },
});

const props = defineProps<{
    documents: PaginatedDocuments;
    filters: { status?: string };
}>();

const page = usePage();
const canVerify = computed(() => {
    const roles = (page.props.auth as any)?.user?.roles ?? [];
    return roles.includes('admin') || roles.includes('accountant');
});

const selectedIds = ref<number[]>([]);

const verifiableIds = computed(() =>
    props.documents.data.filter(d => d.status === 'ai_processed').map(d => d.id)
);

const allSelected = computed(() =>
    verifiableIds.value.length > 0 &&
    verifiableIds.value.every(id => selectedIds.value.includes(id))
);

function toggleAll() {
    if (allSelected.value) {
        selectedIds.value = selectedIds.value.filter(id => !verifiableIds.value.includes(id));
    } else {
        const toAdd = verifiableIds.value.filter(id => !selectedIds.value.includes(id));
        selectedIds.value = [...selectedIds.value, ...toAdd];
    }
}

function toggleOne(id: number) {
    const idx = selectedIds.value.indexOf(id);
    if (idx === -1) {
        selectedIds.value.push(id);
    } else {
        selectedIds.value.splice(idx, 1);
    }
}

function bulkVerify() {
    if (selectedIds.value.length === 0) return;
    router.post('/documents/bulk-verify', { ids: selectedIds.value }, {
        onSuccess: () => { selectedIds.value = []; },
    });
}

const deleteTarget = ref<DocumentFile | null>(null);

function applyFilter(key: string, value: string) {
    selectedIds.value = [];
    router.get('/documents', { ...props.filters, [key]: value || undefined }, {
        preserveState: true,
        replace: true,
    });
}

function doDelete() {
    if (!deleteTarget.value) return;
    router.delete(`/documents/${deleteTarget.value.id}`, {
        onFinish: () => { deleteTarget.value = null; },
    });
}

function formatSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}
</script>

<template>
    <Head title="Документи" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Документи</h1>
                <p class="text-sm text-muted-foreground">
                    {{ documents.total }} {{ documents.total === 1 ? 'документ' : 'документи' }}
                </p>
            </div>
            <Button as-child>
                <Link href="/documents/create">
                    <Plus class="mr-2 size-4" />
                    Прикачи документ
                </Link>
            </Button>
        </div>

        <!-- Филтри -->
        <div class="flex flex-wrap gap-3">
            <select
                class="rounded-md border bg-background px-3 py-1.5 text-sm"
                :value="filters.status ?? ''"
                @change="applyFilter('status', ($event.target as HTMLSelectElement).value)"
            >
                <option value="">Сите статуси</option>
                <option v-for="(label, val) in DOCUMENT_STATUS_LABELS" :key="val" :value="val">{{ label }}</option>
            </select>
        </div>

        <!-- Bulk action bar -->
        <div v-if="canVerify && selectedIds.length > 0" class="flex items-center gap-3 rounded-lg border bg-muted/50 px-4 py-2.5">
            <span class="text-sm font-medium">
                {{ selectedIds.length }} {{ selectedIds.length === 1 ? 'документ избран' : 'документи избрани' }}
            </span>
            <Button size="sm" @click="bulkVerify">Верификувај</Button>
            <Button size="sm" variant="ghost" @click="selectedIds = []">Откажи</Button>
        </div>

        <div class="rounded-lg border">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b bg-muted/50">
                        <th v-if="canVerify" class="w-10 px-3 py-1.5" @click.stop>
                            <Checkbox
                                :checked="allSelected"
                                :disabled="verifiableIds.length === 0"
                                @update:checked="toggleAll"
                            />
                        </th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Документ</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Компанија</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Тип</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Статус</th>
                        <th class="px-3 py-1.5 text-left font-medium text-muted-foreground">Прикачено</th>
                        <th class="w-16 px-3 py-1.5" />
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="documents.data.length === 0">
                        <td :colspan="canVerify ? 7 : 6" class="py-16 text-center text-muted-foreground">
                            <FileText class="mx-auto mb-3 size-10 opacity-30" />
                            <p class="font-medium">Нема документи</p>
                            <p class="text-xs">Прикачи го првиот документ со копчето горе</p>
                        </td>
                    </tr>
                    <tr
                        v-for="doc in documents.data"
                        :key="doc.id"
                        class="cursor-pointer border-b last:border-0 transition-colors hover:bg-muted/30"
                        @click="router.visit(`/documents/${doc.id}`)"
                    >
                        <td v-if="canVerify" class="px-3 py-1.5" @click.stop>
                            <Checkbox
                                :checked="selectedIds.includes(doc.id)"
                                :disabled="doc.status !== 'ai_processed'"
                                @update:checked="toggleOne(doc.id)"
                            />
                        </td>
                        <td class="px-3 py-1.5">
                            <p class="font-medium truncate max-w-48">{{ doc.filename }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatSize(doc.file_size) }}</p>
                        </td>
                        <td class="px-3 py-1.5 text-muted-foreground">{{ doc.company?.name ?? '—' }}</td>
                        <td class="px-3 py-1.5 text-muted-foreground">{{ DOCUMENT_TYPE_LABELS[doc.type] }}</td>
                        <td class="px-3 py-1.5">
                            <Badge :variant="DOCUMENT_STATUS_VARIANT[doc.status as DocumentStatus]">
                                {{ DOCUMENT_STATUS_LABELS[doc.status as DocumentStatus] }}
                            </Badge>
                        </td>
                        <td class="px-3 py-1.5 text-muted-foreground text-xs">
                            {{ new Date(doc.created_at).toLocaleDateString('mk-MK') }}
                        </td>
                        <td class="px-3 py-1.5" @click.stop>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="text-destructive hover:text-destructive"
                                @click="deleteTarget = doc"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="documents.last_page > 1" class="flex justify-center gap-1">
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

    <Dialog :open="!!deleteTarget" @update:open="(v) => { if (!v) deleteTarget = null }">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>Избриши документ</DialogTitle>
                <DialogDescription>
                    Дали сте сигурни дека сакате да го избришете
                    <strong>{{ deleteTarget?.filename }}</strong>?
                    Оваа акција не може да се поврати.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="deleteTarget = null">Откажи</Button>
                <Button variant="destructive" @click="doDelete">Избриши</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
