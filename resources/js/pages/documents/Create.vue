<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { FileUp, X } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Company } from '@/types';
import { DOCUMENT_TYPE_LABELS } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Документи', href: '/documents' },
            { title: 'Прикачи', href: '/documents/create' },
        ],
    },
});

const props = defineProps<{
    companies: Pick<Company, 'id' | 'name'>[];
    selectedCompanyId: number | null;
}>();

const form = useForm({
    company_id: props.selectedCompanyId ?? '',
    type: '',
    file: null as File | null,
});

const isDragging = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function handleFile(file: File) {
    form.file = file;
}

function onInputChange(e: Event) {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) handleFile(f);
}

function onDrop(e: DragEvent) {
    isDragging.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (f) handleFile(f);
}

function clearFile() {
    form.file = null;
    if (fileInput.value) fileInput.value.value = '';
}

function formatSize(bytes: number): string {
    if (bytes < 1048576) return `${(bytes / 1024).toFixed(0)} KB`;
    return `${(bytes / 1048576).toFixed(1)} MB`;
}

function submit() {
    form.post('/documents', { forceFormData: true });
}
</script>

<template>
    <Head title="Прикачи документ" />

    <div class="mx-auto max-w-xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold">Прикачи документ</h1>
            <p class="text-sm text-muted-foreground">Документот ќе биде автоматски обработен со AI</p>
        </div>

        <form class="flex flex-col gap-5" @submit.prevent="submit">
            <!-- Компанија -->
            <div class="grid gap-2">
                <Label for="company_id">Компанија <span class="text-destructive">*</span></Label>
                <select
                    id="company_id"
                    v-model="form.company_id"
                    class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    :class="{ 'border-destructive': form.errors.company_id }"
                    required
                >
                    <option value="">— Изберете компанија —</option>
                    <option v-for="c in companies" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
                <InputError :message="form.errors.company_id" />
            </div>

            <!-- Тип на документ -->
            <div class="grid gap-2">
                <Label for="type">Тип на документ <span class="text-destructive">*</span></Label>
                <select
                    id="type"
                    v-model="form.type"
                    class="rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                    :class="{ 'border-destructive': form.errors.type }"
                    required
                >
                    <option value="">— Изберете тип —</option>
                    <option v-for="(label, val) in DOCUMENT_TYPE_LABELS" :key="val" :value="val">{{ label }}</option>
                </select>
                <InputError :message="form.errors.type" />
            </div>

            <!-- Dropzone -->
            <div class="grid gap-2">
                <Label>Документ <span class="text-destructive">*</span></Label>

                <div
                    v-if="!form.file"
                    class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-lg border-2 border-dashed p-10 transition-colors"
                    :class="isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/30 hover:border-primary/50'"
                    @click="fileInput?.click()"
                    @dragover.prevent="isDragging = true"
                    @dragleave="isDragging = false"
                    @drop.prevent="onDrop"
                >
                    <FileUp class="size-8 text-muted-foreground" />
                    <div class="text-center">
                        <p class="text-sm font-medium">Повлечи документ овде или кликни за да избереш</p>
                        <p class="text-xs text-muted-foreground mt-1">PDF, JPG, PNG, TIFF — максимум 20 MB</p>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        class="hidden"
                        accept=".pdf,.jpg,.jpeg,.png,.tiff,.tif,.webp"
                        @change="onInputChange"
                    />
                </div>

                <div
                    v-else
                    class="flex items-center justify-between rounded-lg border bg-muted/30 px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <FileUp class="size-5 text-primary" />
                        <div>
                            <p class="text-sm font-medium">{{ form.file.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ formatSize(form.file.size) }}</p>
                        </div>
                    </div>
                    <Button type="button" variant="ghost" size="icon" @click="clearFile">
                        <X class="size-4" />
                    </Button>
                </div>

                <InputError :message="form.errors.file" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t pt-4">
                <Button type="button" variant="outline" as-child>
                    <a href="/documents">Откажи</a>
                </Button>
                <Button type="submit" :disabled="form.processing || !form.file">
                    {{ form.processing ? 'Се прикачува...' : 'Прикачи и обработи' }}
                </Button>
            </div>
        </form>
    </div>
</template>
